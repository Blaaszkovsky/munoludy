<?php

namespace App\Filament\Resources\AnswerGroupResource\Pages;

use App\Filament\Resources\AnswerGroupResource;
use App\Filament\Resources\AnswerGroupResource\Widgets\AnswerGroupsStats;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnswerGroups extends ListRecords
{
    protected static string $resource = AnswerGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AnswerGroupsStats::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | string | array
    {
        return 4;
    }

    /**
     * Pass currently filtered question_id to the widget if user selected one.
     */
    public function getWidgetData(): array
    {
        $questionId = $this->tableFilters['question_id']['value'] ?? null;

        return [
            'questionId' => $questionId ? (int) $questionId : null,
        ];
    }
}
