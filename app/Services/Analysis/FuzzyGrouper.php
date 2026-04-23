<?php

namespace App\Services\Analysis;

use App\Models\Answer;
use App\Models\AnswerAlias;
use App\Models\AnswerGroup;

class FuzzyGrouper
{
    private const THRESHOLD_PERCENT = 85;

    public function __construct(private AnswerNormalizer $normalizer) {}

    public function assign(Answer $answer): ?AnswerGroup
    {
        if (!$answer->value) {
            return null;
        }
        $normalized = $this->normalizer->normalize($answer->value);
        $answer->value_normalized = $normalized;

        $existingAlias = AnswerAlias::where('variant', $normalized)
            ->whereHas('group', fn ($q) => $q->where('question_id', $answer->question_id))
            ->first();

        if ($existingAlias) {
            $group = $existingAlias->group;
        } else {
            $group = $this->findSimilarGroup($answer->question_id, $normalized)
                ?? $this->createGroup($answer->question_id, $answer->value, $normalized);
            AnswerAlias::firstOrCreate([
                'answer_group_id' => $group->id,
                'variant' => $normalized,
            ]);
        }

        $answer->answer_group_id = $group->id;
        $answer->save();

        $group->increment('aggregated_count');
        $group->increment('aggregated_points', $answer->points);

        return $group;
    }

    private function findSimilarGroup(int $questionId, string $normalized): ?AnswerGroup
    {
        $candidates = AnswerGroup::where('question_id', $questionId)
            ->where('is_locked', false)
            ->get();

        $needle = $this->tokenSort($normalized);
        foreach ($candidates as $group) {
            $haystack = $this->tokenSort($this->normalizer->normalize($group->canonical_label));
            $percent = 0.0;
            similar_text($needle, $haystack, $percent);
            if ($percent >= self::THRESHOLD_PERCENT) {
                return $group;
            }
        }
        return null;
    }

    private function tokenSort(string $value): string
    {
        $tokens = array_values(array_filter(explode(' ', $value), fn ($t) => $t !== ''));
        sort($tokens);
        return implode(' ', $tokens);
    }

    private function createGroup(int $questionId, string $originalLabel, string $normalized): AnswerGroup
    {
        return AnswerGroup::create([
            'question_id' => $questionId,
            'canonical_label' => $originalLabel,
            'aggregated_count' => 0,
            'aggregated_points' => 0,
        ]);
    }
}
