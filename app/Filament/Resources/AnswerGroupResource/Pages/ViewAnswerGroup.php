<?php

namespace App\Filament\Resources\AnswerGroupResource\Pages;

use App\Filament\Resources\AnswerGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAnswerGroup extends ViewRecord
{
    protected static string $resource = AnswerGroupResource::class;

    protected function resolveRecord(int | string $key): \Illuminate\Database\Eloquent\Model
    {
        return static::getResource()::getEloquentQuery()
            ->with([
                'question',
                'aliases',
                'answers.submission.participant',
                'answers.question',
            ])
            ->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
