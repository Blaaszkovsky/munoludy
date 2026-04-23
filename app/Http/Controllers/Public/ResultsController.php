<?php

namespace App\Http\Controllers\Public;

use App\Enums\EditionStatus;
use App\Enums\FormAudience;
use App\Http\Controllers\Controller;
use App\Models\AnswerGroup;
use App\Models\Edition;
use App\Models\PageContent;
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

        SEOTools::setTitle($content?->og_title ?? ('Wyniki — ' . $edition->name));
        SEOTools::setDescription($content?->og_description ?? 'Wyniki plebiscytu Munoludy.');
        SEOTools::opengraph()->setUrl(request()->url());

        return view('results', [
            'edition' => $edition,
            'content' => $content,
            'publicTops' => $publicTops,
            'juryTops' => $juryTops,
        ]);
    }

    /**
     * @return array<string, array<int, array{label: string, points: int, count: int}>>
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
            ->orderBy('order')
            ->get();

        $out = [];
        foreach ($questions as $question) {
            $groups = AnswerGroup::query()
                ->where('question_id', $question->id)
                ->get()
                ->sortByDesc(fn (AnswerGroup $g) => $g->finalPoints())
                ->take(10)
                ->values();

            $out[$question->title ?? ('#' . $question->id)] = $groups->map(fn (AnswerGroup $g) => [
                'label' => $g->canonical_label,
                'points' => $g->finalPoints(),
                'count' => $g->aggregated_count,
            ])->all();
        }

        return $out;
    }
}
