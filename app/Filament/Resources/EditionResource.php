<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EditionResource\Pages;
use App\Models\Edition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EditionResource extends Resource
{
    protected static ?string $model = Edition::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Edycje';
    protected static ?string $modelLabel = 'Edycja';
    protected static ?string $pluralModelLabel = 'Edycje';
    protected static ?string $navigationGroup = 'Plebiscyt';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('slug')
                ->label('Slug (identyfikator URL)')
                ->required()
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('name')
                ->label('Nazwa')
                ->required(),
            Forms\Components\Section::make('Okno głosowania')->schema([
                Forms\Components\DateTimePicker::make('starts_at')->label('Start'),
                Forms\Components\DateTimePicker::make('ends_at')->label('Koniec'),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Szkic',
                        'active' => 'Aktywna',
                        'finished' => 'Zakończona',
                        'results_published' => 'Wyniki opublikowane',
                    ])
                    ->default('draft')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktywna edycja')
                    ->helperText('Tylko jedna edycja może być aktywna naraz.'),
            ])->columns(2),
            Forms\Components\Section::make('Integracja user.com')->schema([
                Forms\Components\TextInput::make('user_com_list_id')
                    ->label('ID listy')
                    ->numeric()
                    ->default(17)
                    ->required(),
                Forms\Components\TextInput::make('user_com_link_field')
                    ->label('Nazwa atrybutu z linkiem')
                    ->required()
                    ->default('munoludy2026_link'),
                Forms\Components\TextInput::make('user_com_code_field')
                    ->label('Nazwa atrybutu z kodem')
                    ->required()
                    ->default('munoludy2026_kod'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nazwa')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ((string) ($state?->value ?? $state)) {
                        'draft' => 'Szkic',
                        'active' => 'Aktywna',
                        'finished' => 'Zakończona',
                        'results_published' => 'Wyniki opublikowane',
                        default => (string) $state,
                    })
                    ->color(fn ($state) => match ((string) ($state?->value ?? $state)) {
                        'active' => 'success',
                        'results_published' => 'warning',
                        'finished' => 'gray',
                        default => 'secondary',
                    }),
                Tables\Columns\IconColumn::make('is_active')->label('Aktywna')->boolean(),
                Tables\Columns\TextColumn::make('starts_at')->label('Start')->dateTime('Y-m-d H:i'),
                Tables\Columns\TextColumn::make('ends_at')->label('Koniec')->dateTime('Y-m-d H:i'),
                Tables\Columns\TextColumn::make('user_com_list_id')->label('Lista user.com'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('is_active', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEditions::route('/'),
            'create' => Pages\CreateEdition::route('/create'),
            'edit' => Pages\EditEdition::route('/{record}/edit'),
        ];
    }
}
