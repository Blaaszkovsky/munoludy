<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JuryMemberResource\Pages;
use App\Filament\Resources\JuryMemberResource\RelationManagers;
use App\Models\JuryMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('edition_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('display_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
        return [
            //
        ];
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
