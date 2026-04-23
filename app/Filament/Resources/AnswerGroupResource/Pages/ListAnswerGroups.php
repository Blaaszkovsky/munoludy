<?php

namespace App\Filament\Resources\AnswerGroupResource\Pages;

use App\Filament\Resources\AnswerGroupResource;
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
}
