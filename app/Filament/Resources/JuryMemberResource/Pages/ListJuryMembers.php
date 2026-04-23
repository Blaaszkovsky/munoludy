<?php

namespace App\Filament\Resources\JuryMemberResource\Pages;

use App\Filament\Resources\JuryMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJuryMembers extends ListRecords
{
    protected static string $resource = JuryMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import_csv')
                ->label('Importuj CSV')
                ->form([
                    \Filament\Forms\Components\Select::make('edition_id')->relationship('edition', 'name')->required(),
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv'])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $path = storage_path('app/public/' . $data['file']);
                    $handle = fopen($path, 'r');
                    while (($row = fgetcsv($handle)) !== false) {
                        if (! filter_var($row[0] ?? '', FILTER_VALIDATE_EMAIL)) {
                            continue;
                        }
                        \App\Models\JuryMember::firstOrCreate(
                            ['edition_id' => $data['edition_id'], 'email' => trim($row[0])],
                            ['display_name' => $row[1] ?? null]
                        );
                    }
                    fclose($handle);
                    \Filament\Notifications\Notification::make()->success()->title('Import zakończony')->send();
                }),
        ];
    }
}
