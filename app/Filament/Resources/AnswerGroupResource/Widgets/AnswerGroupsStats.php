<?php

namespace App\Filament\Resources\AnswerGroupResource\Widgets;

use App\Enums\FormAudience;
use App\Models\AnswerGroup;
use App\Models\Edition;
use App\Models\Participant;
use App\Models\Question;
use App\Models\VoteSubmission;
use Filament\Widgets\StatsOverviewWidget as Base;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnswerGroupsStats extends Base
{
    public ?int $questionId = null;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $edition = Edition::active() ?? Edition::orderByDesc('id')->first();

        if (! $edition) {
            return [
                Stat::make('Brak edycji', '—')->description('Utwórz aktywną edycję'),
            ];
        }

        if ($this->questionId) {
            $question = Question::with('answerGroups')->find($this->questionId);
            if (! $question) {
                return [
                    Stat::make('Pytanie', 'nie znaleziono')->color('danger'),
                ];
            }

            $groups = $question->answerGroups;
            $uniqueGroups = $groups->count();
            $totalCount = (int) $groups->sum('aggregated_count');

            $publicCount = VoteSubmission::query()
                ->where('edition_id', $edition->id)
                ->where('audience', FormAudience::Public_->value)
                ->whereHas('answers', fn ($q) => $q->where('question_id', $question->id))
                ->distinct('participant_id')
                ->count('participant_id');

            $juryCount = VoteSubmission::query()
                ->where('edition_id', $edition->id)
                ->where('audience', FormAudience::Jury->value)
                ->whereHas('answers', fn ($q) => $q->where('question_id', $question->id))
                ->distinct('participant_id')
                ->count('participant_id');

            $top3 = $groups
                ->sortByDesc(fn (AnswerGroup $g) => $g->finalPoints())
                ->take(3)
                ->map(fn (AnswerGroup $g, $idx) => ($idx + 1) . '. ' . $g->canonical_label . ' (' . $g->finalPoints() . ' pkt)')
                ->values()
                ->all();

            $audienceLabel = match ($question->audience?->value) {
                'public' => 'Publiczność',
                'jury' => 'Jury',
                'both' => 'Publiczność i Jury',
                default => '—',
            };

            return [
                Stat::make('Kategoria', $question->title)
                    ->description($audienceLabel)
                    ->color('primary'),
                Stat::make('Unikalnych grup', $uniqueGroups)
                    ->description('Łącznie wskazań: ' . $totalCount)
                    ->color('info'),
                Stat::make('Głosujący', $publicCount + $juryCount)
                    ->description('Publiczność: ' . $publicCount . ' · Jury: ' . $juryCount)
                    ->color('success'),
                Stat::make('Top 3', empty($top3) ? '—' : (string) count($top3))
                    ->description(empty($top3) ? 'Brak danych' : implode(' · ', $top3))
                    ->color('warning'),
            ];
        }

        // Global stats
        $questionIds = Question::where('edition_id', $edition->id)->pluck('id');
        $groups = AnswerGroup::whereIn('question_id', $questionIds)->get();

        $uniqueGroups = $groups->count();
        $totalCount = (int) $groups->sum('aggregated_count');

        $publicCount = Participant::where('edition_id', $edition->id)
            ->where('type', 'public')
            ->whereNotNull('voted_at')
            ->count();

        $juryCount = Participant::where('edition_id', $edition->id)
            ->where('type', 'jury')
            ->whereNotNull('voted_at')
            ->count();

        $top3 = $groups
            ->sortByDesc(fn (AnswerGroup $g) => $g->finalPoints())
            ->take(3)
            ->map(fn (AnswerGroup $g, $idx) => ($idx + 1) . '. ' . $g->canonical_label)
            ->values()
            ->all();

        return [
            Stat::make('Edycja', $edition->name)
                ->description('Statystyki globalne')
                ->color('primary'),
            Stat::make('Unikalnych grup', $uniqueGroups)
                ->description('Łącznie wskazań: ' . $totalCount)
                ->color('info'),
            Stat::make('Głosujący', $publicCount + $juryCount)
                ->description('Publiczność: ' . $publicCount . ' · Jury: ' . $juryCount)
                ->color('success'),
            Stat::make('Top 3 (globalnie)', empty($top3) ? '—' : (string) count($top3))
                ->description(empty($top3) ? 'Brak danych' : implode(' · ', $top3))
                ->color('warning'),
        ];
    }
}
