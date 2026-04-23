<?php

namespace App\Http\Controllers\Public;

use App\Enums\EditionStatus;
use App\Enums\FormAudience;
use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\AnswerGroup;
use App\Models\Edition;
use App\Models\PageContent;
use App\Models\Participant;
use App\Models\Question;
use Artesaos\SEOTools\Facades\SEOTools;

class ResultsController extends Controller
{
    public function index(?string $editionSlug = null)
    {
        $edition = $editionSlug
            ? Edition::where('slug', $editionSlug)->first()
            : Edition::where('status', EditionStatus::ResultsPublished)
                ->orderByDesc('results_published_at')
                ->first();

        abort_unless($edition && $edition->status === EditionStatus::ResultsPublished, 404);

        $content = PageContent::where('edition_id', $edition->id)
            ->where('view', 'results')
            ->first();

        $publicTops = $this->topsForAudience($edition, 'public');
        $juryTops = $this->topsForAudience($edition, 'jury');

        $stats = $this->engagementStats($edition);

        SEOTools::setTitle($content?->og_title ?? ('Wyniki — ' . $edition->name));
        SEOTools::setDescription($content?->og_description ?? 'Wyniki plebiscytu Munoludy.');
        SEOTools::opengraph()->setUrl(request()->url());

        return view('results', [
            'edition' => $edition,
            'content' => $content,
            'publicTops' => $publicTops,
            'juryTops' => $juryTops,
            'stats' => $stats,
        ]);
    }

    /**
     * @return array{participants:int, votes:int, categories:int}
     */
    protected function engagementStats(Edition $edition): array
    {
        $participants = Participant::where('edition_id', $edition->id)
            ->whereNotNull('voted_at')
            ->count();

        $votes = Answer::whereHas('submission', fn ($q) => $q->where('edition_id', $edition->id))->count();

        $categories = Question::where('edition_id', $edition->id)->count();

        return [
            'participants' => $participants,
            'votes' => $votes,
            'categories' => $categories,
        ];
    }

    /**
     * @return array<int, array{
     *   question_id:int, title:string, audience:string,
     *   items:array<int, array{label:string, points:int, count:int, pct:float}>
     * }>
     */
    protected function topsForAudience(Edition $edition, string $audience): array
    {
        $audienceValues = match ($audience) {
            'public' => [FormAudience::Public_->value, FormAudience::Both->value],
            'jury' => [FormAudience::Jury->value, FormAudience::Both->value],
            default => [$audience],
        };

        $questions = Question::query()
            ->where('edition_id', $edition->id)
            ->whereIn('audience', $audienceValues)
            ->with(['answerGroups'])
            ->orderBy('order')
            ->get();

        $out = [];
        foreach ($questions as $question) {
            $groups = $question->answerGroups
                ->sortByDesc(fn (AnswerGroup $g) => $g->finalPoints())
                ->take(10)
                ->values();

            $topPoints = $groups->first()?->finalPoints() ?: 0;

            $out[] = [
                'question_id' => $question->id,
                'title' => $question->title ?? ('#' . $question->id),
                'audience' => $question->audience?->value ?? $audience,
                'items' => $groups->map(function (AnswerGroup $g) use ($topPoints) {
                    $pct = $topPoints > 0 ? round(($g->finalPoints() / $topPoints) * 100, 1) : 0.0;
                    return [
                        'label' => $g->canonical_label,
                        'points' => $g->finalPoints(),
                        'count' => (int) $g->aggregated_count,
                        'pct' => $pct,
                    ];
                })->values()->all(),
            ];
        }

        return $out;
    }
}
