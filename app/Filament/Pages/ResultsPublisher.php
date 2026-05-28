<?php

namespace App\Filament\Pages;

use App\Enums\EditionStatus;
use App\Enums\FormAudience;
use App\Models\Answer;
use App\Models\AnswerGroup;
use App\Models\Edition;
use App\Models\Participant;
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
     * Aggregate engagement stats for a given edition.
     *
     * @return array{
     *   registered_total:int, registered_public:int, registered_jury:int,
     *   voted_total:int, voted_pct:float,
     *   answers_total:int, categories:int
     * }
     */
    public function engagement(?Edition $edition): array
    {
        if (! $edition) {
            return [
                'registered_total' => 0, 'registered_public' => 0, 'registered_jury' => 0,
                'voted_total' => 0, 'voted_pct' => 0.0,
                'answers_total' => 0, 'categories' => 0,
            ];
        }

        $registeredTotal = Participant::where('edition_id', $edition->id)->count();
        $registeredPublic = Participant::where('edition_id', $edition->id)->where('type', 'public')->count();
        $registeredJury = Participant::where('edition_id', $edition->id)->where('type', 'jury')->count();

        $votedTotal = Participant::where('edition_id', $edition->id)
            ->whereNotNull('voted_at')
            ->count();

        $votedPct = $registeredTotal > 0 ? round(($votedTotal / $registeredTotal) * 100, 1) : 0.0;

        $answersTotal = Answer::whereHas('submission', fn ($q) => $q->where('edition_id', $edition->id))->count();

        $categories = Question::where('edition_id', $edition->id)->count();

        return [
            'registered_total' => $registeredTotal,
            'registered_public' => $registeredPublic,
            'registered_jury' => $registeredJury,
            'voted_total' => $votedTotal,
            'voted_pct' => $votedPct,
            'answers_total' => $answersTotal,
            'categories' => $categories,
        ];
    }

    /**
     * Return top answer groups for a given audience, grouped by question.
     *
     * @return array<int, array{
     *   question_id:int, title:string, audience:string,
     *   unique_count:int, total_count:int,
     *   top:array<int, array{id:int, label:string, points:int, count:int, pct:float, is_podium:bool, podium_position:?int}>
     * }>
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
            ->with(['answerGroups'])
            ->orderBy('order')
            ->get();

        $results = [];

        foreach ($questions as $question) {
            // Sort: manually-pinned podium positions first (1, 2, 3),
            // then the rest by final points (descending).
            $groups = $question->answerGroups
                ->sortBy(function (AnswerGroup $g) {
                    $podiumKey = ($g->is_podium && $g->podium_position)
                        ? (int) $g->podium_position
                        : PHP_INT_MAX;
                    return [$podiumKey, -$g->finalPoints()];
                })
                ->values();

            $uniqueCount = $groups->count();
            $totalCount = (int) $groups->sum('aggregated_count');

            $top = $groups->take($limit);
            $topPoints = $top->first()?->finalPoints() ?: 0;

            $items = $top->map(function (AnswerGroup $g) use ($topPoints) {
                $pct = $topPoints > 0 ? round(($g->finalPoints() / $topPoints) * 100, 1) : 0.0;
                return [
                    'id' => $g->id,
                    'label' => $g->canonical_label,
                    'points' => $g->finalPoints(),
                    'points_override' => $g->points_override,
                    'aggregated_points' => (int) $g->aggregated_points,
                    'count' => (int) $g->aggregated_count,
                    'pct' => $pct,
                    'is_podium' => (bool) ($g->is_podium ?? false),
                    'podium_position' => $g->podium_position,
                ];
            })->values()->all();

            $results[] = [
                'question_id' => $question->id,
                'title' => $question->title ?? ('#' . $question->id),
                'audience' => $question->audience?->value ?? 'public',
                'unique_count' => $uniqueCount,
                'total_count' => $totalCount,
                'top' => $items,
            ];
        }

        return $results;
    }

    protected function getHeaderActions(): array
    {
        $edition = $this->edition();
        $isPublished = $edition && $edition->status === EditionStatus::ResultsPublished;

        return [
            Action::make('preview')
                ->label('Otwórz testową stronę')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->visible(fn () => $edition !== null && $edition->slug)
                ->openUrlInNewTab()
                ->url(fn () => $edition && $edition->slug
                    ? route('results.preview', ['editionSlug' => $edition->slug])
                    : null)
                ->tooltip('Podgląd strony /wyniki dostępny wyłącznie dla zalogowanego administratora — działa również, gdy plebiscyt nie jest jeszcze opublikowany.'),

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

    public function togglePodium(int $groupId): void
    {
        $group = AnswerGroup::find($groupId);
        if (! $group) {
            return;
        }

        if ($group->is_podium) {
            $group->update(['is_podium' => false, 'podium_position' => null]);
            Notification::make()->title('Usunięto z podium')->send();
            return;
        }

        // Determine podium position within this question
        $existing = AnswerGroup::where('question_id', $group->question_id)
            ->where('is_podium', true)
            ->pluck('podium_position')
            ->filter()
            ->values()
            ->all();

        $nextPosition = null;
        foreach ([1, 2, 3] as $candidate) {
            if (! \in_array($candidate, $existing, true)) {
                $nextPosition = $candidate;
                break;
            }
        }

        if ($nextPosition === null) {
            Notification::make()->warning()
                ->title('Podium pełne')
                ->body('W tej kategorii wszystkie 3 pozycje podium są już zajęte.')
                ->send();
            return;
        }

        $group->update(['is_podium' => true, 'podium_position' => $nextPosition]);
        Notification::make()->success()
            ->title('Oznaczono jako ' . $nextPosition . '. miejsce')
            ->send();
    }

    /**
     * Manually assign a podium position to a group, swapping with whoever
     * currently holds that position in the same category.
     */
    public function setPodiumPosition(int $groupId, int $position): void
    {
        if (! \in_array($position, [1, 2, 3], true)) {
            return;
        }

        $group = AnswerGroup::find($groupId);
        if (! $group) {
            return;
        }

        $existing = AnswerGroup::where('question_id', $group->question_id)
            ->where('is_podium', true)
            ->where('podium_position', $position)
            ->where('id', '!=', $group->id)
            ->first();

        if ($existing) {
            // Swap: existing takes group's previous slot (if any), else loses podium.
            $existing->update([
                'is_podium' => $group->is_podium ? (bool) $group->podium_position : false,
                'podium_position' => $group->is_podium ? $group->podium_position : null,
            ]);
        }

        $group->update(['is_podium' => true, 'podium_position' => $position]);

        Notification::make()->success()
            ->title('Ręcznie ustawiono ' . $position . '. miejsce')
            ->body($existing ? 'Zamieniono pozycję z: ' . $existing->canonical_label : null)
            ->send();
    }

    /**
     * Clear all podium positions in a category — useful when restarting manual
     * ranking from scratch.
     */
    public function clearPodium(int $questionId): void
    {
        $count = AnswerGroup::where('question_id', $questionId)
            ->where('is_podium', true)
            ->update(['is_podium' => false, 'podium_position' => null]);

        Notification::make()
            ->title($count > 0 ? 'Wyczyszczono podium' : 'Brak ustawionego podium')
            ->body($count > 0 ? 'Usunięto ' . $count . ' pozycji.' : null)
            ->send();
    }

    /**
     * Recompute podium positions from the top 3 groups by final points in a
     * category — recovery shortcut when the manual edits got out of hand.
     */
    public function autoAssignPodium(int $questionId): void
    {
        AnswerGroup::where('question_id', $questionId)
            ->where('is_podium', true)
            ->update(['is_podium' => false, 'podium_position' => null]);

        $groups = AnswerGroup::where('question_id', $questionId)
            ->get()
            ->sortByDesc(fn (AnswerGroup $g) => $g->finalPoints())
            ->take(3)
            ->values();

        foreach ($groups as $i => $group) {
            $group->update(['is_podium' => true, 'podium_position' => $i + 1]);
        }

        Notification::make()->success()
            ->title('Podium ustawione automatycznie')
            ->body('Top 3 wg punktów: ' . $groups->pluck('canonical_label')->join(', '))
            ->send();
    }

    /**
     * Override final points for a group. Pass null to clear and fall back to
     * the auto-computed aggregated points.
     */
    public function setPointsOverride(int $groupId, ?int $points): void
    {
        $group = AnswerGroup::find($groupId);
        if (! $group) {
            return;
        }

        $group->update(['points_override' => $points, 'is_locked' => $points !== null ? true : $group->is_locked]);

        Notification::make()->success()
            ->title($points === null ? 'Przywrócono punkty automatyczne' : 'Zaktualizowano punkty: ' . $points)
            ->send();
    }

    protected function getViewData(): array
    {
        $edition = $this->edition();
        return [
            'edition' => $edition,
            'engagement' => $this->engagement($edition),
            'publicTops' => $this->topPerCategory('public'),
            'juryTops' => $this->topPerCategory('jury'),
        ];
    }
}
