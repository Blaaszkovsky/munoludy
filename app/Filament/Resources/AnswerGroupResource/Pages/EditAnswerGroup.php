<?php

namespace App\Filament\Resources\AnswerGroupResource\Pages;

use App\Filament\Resources\AnswerGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnswerGroup extends EditRecord
{
    protected static string $resource = AnswerGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
