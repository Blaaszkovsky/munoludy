<?php

namespace App\Filament\Resources\VoteSubmissionResource\Pages;

use App\Exports\VoteSubmissionsExport;
use App\Filament\Resources\VoteSubmissionResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class ListVoteSubmissions extends ListRecords
{
    protected static string $resource = VoteSubmissionResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Wszystkie'),
            'public' => Tab::make('Publiczność')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('audience', 'public'))
                ->badge(fn () => \App\Models\VoteSubmission::where('audience', 'public')->count()),
            'jury' => Tab::make('Jury')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('audience', 'jury'))
                ->badge(fn () => \App\Models\VoteSubmission::where('audience', 'jury')->count()),
        ];
    }

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
