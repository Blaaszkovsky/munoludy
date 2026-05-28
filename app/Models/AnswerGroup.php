<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AnswerGroup extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_locked' => 'boolean',
        'is_podium' => 'boolean',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function aliases()
    {
        return $this->hasMany(AnswerAlias::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    public function finalPoints(): int
    {
        return $this->points_override ?? $this->aggregated_points;
    }

    /**
     * Normalize a label for fuzzy comparison: lowercase, strip diacritics,
     * collapse whitespace and punctuation.
     */
    public static function normalizeLabel(string $label): string
    {
        $label = mb_strtolower(trim($label), 'UTF-8');

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
            if (is_string($converted) && $converted !== '') {
                $label = $converted;
            }
        }

        $label = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $label) ?? $label;
        $label = preg_replace('/\s+/u', ' ', $label) ?? $label;

        return trim((string) $label);
    }

    /**
     * Find groups within the same question (category) that look similar to this
     * one — by shared tokens, substring overlap, or Levenshtein distance.
     *
     * Returns a collection ordered by descending similarity score.
     *
     * @return Collection<int, self>
     */
    public function suggestMergeCandidates(int $limit = 10): Collection
    {
        $candidates = static::where('question_id', $this->question_id)
            ->where('id', '!=', $this->id)
            ->with('aliases')
            ->get();

        if ($candidates->isEmpty()) {
            return $candidates;
        }

        $sourceNorm = static::normalizeLabel((string) $this->canonical_label);
        $sourceTokens = array_values(array_filter(
            explode(' ', $sourceNorm),
            fn ($t) => mb_strlen($t) >= 3
        ));

        $scored = $candidates->map(function (self $candidate) use ($sourceNorm, $sourceTokens) {
            $score = static::similarityScore($sourceNorm, $sourceTokens, (string) $candidate->canonical_label);

            // Boost: any alias of the candidate also looks similar.
            foreach ($candidate->aliases as $alias) {
                $aliasScore = static::similarityScore($sourceNorm, $sourceTokens, (string) $alias->variant);
                if ($aliasScore > $score) {
                    $score = $aliasScore;
                }
            }

            $candidate->setAttribute('similarity_score', $score);
            return $candidate;
        });

        return $scored
            ->filter(fn (self $c) => $c->getAttribute('similarity_score') >= 60)
            ->sortByDesc(fn (self $c) => $c->getAttribute('similarity_score'))
            ->take($limit)
            ->values();
    }

    /**
     * Compute a 0–100 similarity score between a normalized source label and a
     * raw candidate label.
     *
     * Strategy biased toward "the user's keyword appears in the candidate":
     *  - source-token coverage (what fraction of source's meaningful tokens
     *    appear among candidate's tokens — independent of candidate length),
     *  - full-label substring containment (length-agnostic strong signal),
     *  - per-token substring match (for compound words / typos),
     *  - capped similar_text (Levenshtein-ish fallback, weak signal alone).
     *
     * @param  array<int, string>  $sourceTokens
     */
    public static function similarityScore(string $sourceNorm, array $sourceTokens, string $candidateLabel): float
    {
        $candidateNorm = static::normalizeLabel($candidateLabel);
        if ($sourceNorm === '' || $candidateNorm === '') {
            return 0.0;
        }

        if ($sourceNorm === $candidateNorm) {
            return 100.0;
        }

        $candidateTokens = array_values(array_filter(
            explode(' ', $candidateNorm),
            fn ($t) => mb_strlen($t) >= 3
        ));

        // 1. Source-token coverage — what fraction of source's meaningful tokens
        //    are present in the candidate. NOT a Jaccard: long candidates that
        //    contain all source tokens still score 100.
        $coverage = 0.0;
        if (!empty($sourceTokens)) {
            $matched = array_intersect($sourceTokens, $candidateTokens);
            $coverage = (count($matched) / count($sourceTokens)) * 100;
        }

        // 2. Full-label substring containment — length-agnostic strong signal.
        //    "sunrise" inside "paul kalkbrenner sunrise festival" → 90.
        $substring = 0.0;
        if (str_contains($candidateNorm, $sourceNorm) || str_contains($sourceNorm, $candidateNorm)) {
            $substring = 90.0;
        }

        // 3. Per-token substring — catches "sunrise" vs "sunrises" (plural,
        //    typo, joined word).
        $tokenSubstring = 0.0;
        foreach ($candidateTokens as $tok) {
            foreach ($sourceTokens as $st) {
                if (mb_strlen($st) >= 4 && mb_strlen($tok) >= 4
                    && (str_contains($tok, $st) || str_contains($st, $tok))
                ) {
                    $tokenSubstring = max($tokenSubstring, 75.0);
                }
            }
        }

        // 4. Fuzzy fallback — capped so it can't dominate on coincidental
        //    letter overlap between unrelated long strings.
        similar_text($sourceNorm, $candidateNorm, $similarPct);
        $similarCapped = min((float) $similarPct, 55.0);

        return max($coverage, $substring, $tokenSubstring, $similarCapped);
    }
}
