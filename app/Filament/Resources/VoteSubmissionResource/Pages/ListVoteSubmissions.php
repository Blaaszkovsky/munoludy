<?php

namespace App\Filament\Resources\VoteSubmissionResource\Pages;

use App\Exports\VoteSubmissionsExport;
use App\Filament\Resources\VoteSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListVoteSubmissions extends ListRecords
{
    protected static string $resource = VoteSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('export')
                ->label('Eksportuj CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => Excel::download(new VoteSubmissionsExport(), 'votes.csv')),
        ];
    }
}
