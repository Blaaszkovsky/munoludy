<?php

namespace App\Filament\Widgets;

use App\Models\AnswerGroup;
use App\Models\Edition;
use App\Models\Question;
use Filament\Widgets\ChartWidget;

class CategoryTopChart extends ChartWidget
{
    protected static ?string $heading = 'Top 10 w pierwszej kategorii publiczności';

    protected function getData(): array
    {
        $edition = Edition::active();

        if (! $edition) {
            return ['datasets' => [], 'labels' => []];
        }

        $q = Question::where('edition_id', $edition->id)
            ->where('audience', 'public')
            ->orderBy('order')
            ->first();

        if (! $q) {
            return ['datasets' => [], 'labels' => []];
        }

        $groups = AnswerGroup::where('question_id', $q->id)
            ->orderByDesc('aggregated_points')
            ->limit(10)
            ->get();

        return [
            'labels' => $groups->pluck('canonical_label')->all(),
            'datasets' => [
                [
                    'label' => 'Punkty',
                    'data' => $groups->map(fn ($g) => $g->finalPoints())->all(),
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
