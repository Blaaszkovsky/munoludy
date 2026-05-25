<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JuryMemberResource\Pages;
use App\Models\JuryMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JuryMemberResource extends Resource
{
    protected static ?string $model = JuryMember::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Jury';
    protected static ?string $modelLabel = 'Członek jury';
    protected static ?string $pluralModelLabel = 'Jury';
    protected static ?string $navigationGroup = 'Plebiscyt';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('edition_id')->label('Edycja')->relationship('edition', 'name')->required(),
            Forms\Components\TextInput::make('email')->label('E-mail')->email()->required(),
            Forms\Components\TextInput::make('display_name')->label('Imię i nazwisko'),
            Forms\Components\Textarea::make('notes')->label('Notatki')->rows(3),
            Forms\Components\Placeholder::make('voting_url')
                ->label('Link do głosowania')
                ->content(fn (?JuryMember $record) => $record?->votingUrl() ?? '—')
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('edition.name')
                    ->label('Edycja')
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Imię i nazwisko')
                    ->searchable(),
                Tables\Columns\TextColumn::make('voting_url')
                    ->label('Link do głosowania')
                    ->state(fn (JuryMember $record) => $record->votingUrl())
                    ->copyable()
                    ->copyMessage('Skopiowano link')
                    ->limit(40)
                    ->toggleable(),
                Tables\Columns\IconColumn::make('has_voted')
                    ->label('Zagłosował')
                    ->state(fn (JuryMember $record) => $record->hasVoted())
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dodano')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('edition_id')
                    ->label('Edycja')
                    ->relationship('edition', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJuryMembers::route('/'),
            'create' => Pages\CreateJuryMember::route('/create'),
            'edit' => Pages\EditJuryMember::route('/{record}/edit'),
        ];
    }
}
