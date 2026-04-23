<?php

namespace App\Filament\Pages;

use App\Enums\EditionStatus;
use App\Enums\FormAudience;
use App\Models\AnswerGroup;
use App\Models\Edition;
use App\Models\Question;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ResultsPublisher extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-trophy';

    protected static string $view = 'filament.pages.results-publisher';

    protected static ?string $navigationGroup = 'Analiza';

    protected static ?string $navigationLabel = 'Publikacja wyników';

    protected static ?string $title = 'Publikacja wyników';

    protected static ?int $navigationSort = 90;

    /**
     * Get the active (or latest) edition that we are publishing results for.
     */
    public function edition(): ?Edition
    {
        return Edition::active()
            ?? Edition::orderByDesc('id')->first();
    }

    /**
     * Return top answer groups for a given audience, grouped by question title.
     *
     * @return array<string, array<int, array{label: string, points: int, count: int}>>
     */
    public function topPerCategory(string $audience, int $limit = 10): array
    {
        $edition = $this->edition();
        if (!$edition) {
            return [];
        }

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

        $results = [];

        foreach ($questions as $question) {
            $groups = AnswerGroup::query()
                ->where('question_id', $question->id)
                ->get()
                ->sortByDesc(fn (AnswerGroup $g) => $g->finalPoints())
                ->take($limit)
                ->values();

            $results[$question->title ?? ('#' . $question->id)] = $groups->map(fn (AnswerGroup $g) => [
                'label' => $g->canonical_label,
                'points' => $g->finalPoints(),
                'count' => $g->aggregated_count,
            ])->all();
        }

        return $results;
    }

    protected function getHeaderActions(): array
    {
        $edition = $this->edition();
        $isPublished = $edition && $edition->status === EditionStatus::ResultsPublished;

        return [
            Action::make('publish')
                ->label('Opublikuj wyniki')
                ->icon('heroicon-o-megaphone')
                ->color('success')
                ->visible(fn () => $edition !== null && !$isPublished)
                ->requiresConfirmation()
                ->modalHeading('Opublikować wyniki?')
                ->modalDescription('Strona /wyniki stanie się publicznie dostępna.')
                ->action(function () use ($edition) {
                    if (!$edition) {
                        return;
                    }
                    $edition->update([
                        'status' => EditionStatus::ResultsPublished,
                        'results_published_at' => now(),
                    ]);
                    Notification::make()->success()->title('Wyniki opublikowane')->send();
                }),

            Action::make('unpublish')
                ->label('Cofnij publikację')
                ->icon('heroicon-o-eye-slash')
                ->color('danger')
                ->visible(fn () => $edition !== null && $isPublished)
                ->requiresConfirmation()
                ->modalHeading('Cofnąć publikację wyników?')
                ->modalDescription('Strona /wyniki wróci do 404.')
                ->action(function () use ($edition) {
                    if (!$edition) {
                        return;
                    }
                    $edition->update([
                        'status' => EditionStatus::Finished,
                        'results_published_at' => null,
                    ]);
                    Notification::make()->warning()->title('Publikacja cofnięta')->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'edition' => $this->edition(),
            'publicTops' => $this->topPerCategory('public'),
            'juryTops' => $this->topPerCategory('jury'),
        ];
    }
}
