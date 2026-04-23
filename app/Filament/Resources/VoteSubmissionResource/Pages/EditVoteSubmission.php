<?php

namespace App\Filament\Resources\VoteSubmissionResource\Pages;

use App\Filament\Resources\VoteSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVoteSubmission extends EditRecord
{
    protected static string $resource = VoteSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
