<?php

namespace App\Filament\Resources\JuryMemberResource\Pages;

use App\Exports\JuryMembersExport;
use App\Filament\Resources\JuryMemberResource;
use App\Models\JuryMember;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class ListJuryMembers extends ListRecords
{
    protected static string $resource = JuryMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import_csv')
                ->label('Importuj CSV')
                ->icon('heroicon-o-arrow-up-tray')
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
                        JuryMember::firstOrCreate(
                            ['edition_id' => $data['edition_id'], 'email' => trim($row[0])],
                            ['display_name' => $row[1] ?? null]
                        );
                    }
                    fclose($handle);
                    \Filament\Notifications\Notification::make()->success()->title('Import zakończony')->send();
                }),
            Actions\Action::make('export')
                ->label('Eksportuj')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    \Filament\Forms\Components\Select::make('edition_id')
                        ->label('Edycja (opcjonalnie)')
                        ->relationship('edition', 'name')
                        ->placeholder('Wszystkie edycje'),
                    \Filament\Forms\Components\Select::make('format')
                        ->label('Format')
                        ->options(['csv' => 'CSV', 'xlsx' => 'XLSX'])
                        ->default('xlsx')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $editionId = $data['edition_id'] ?? null;
                    $format = $data['format'] ?? 'xlsx';

                    $writer = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
                    $filename = 'jury-' . now()->format('Ymd-His') . '.' . $format;

                    return Excel::download(new JuryMembersExport($editionId), $filename, $writer);
                }),
        ];
    }
}
