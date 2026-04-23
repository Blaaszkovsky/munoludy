<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EditionResource\Pages;
use App\Filament\Resources\EditionResource\RelationManagers;
use App\Models\Edition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EditionResource extends Resource
{
    protected static ?string $model = Edition::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Section::make('Okno głosowania')->schema([
                Forms\Components\DateTimePicker::make('starts_at'),
                Forms\Components\DateTimePicker::make('ends_at'),
                Forms\Components\Select::make('status')
                    ->options(collect(\App\Enums\EditionStatus::cases())->mapWithKeys(fn ($c) => [$c->value => ucfirst($c->value)]))
                    ->default('draft'),
                Forms\Components\Toggle::make('is_active'),
            ])->columns(2),
            Forms\Components\Section::make('Integracja user.com')->schema([
                Forms\Components\TextInput::make('user_com_list_id')->numeric()->default(17)->required(),
                Forms\Components\TextInput::make('user_com_link_field')->required()->default('munoludy2026_link'),
                Forms\Components\TextInput::make('user_com_code_field')->required()->default('munoludy2026_kod'),
                Forms\Components\TextInput::make('user_com_type_field')->required()->default('munoludy2026_typ'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_com_list_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_com_link_field')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_com_code_field')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_com_type_field')
                    ->searchable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('results_published_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
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
            'index' => Pages\ListEditions::route('/'),
            'create' => Pages\CreateEdition::route('/create'),
            'edit' => Pages\EditEdition::route('/{record}/edit'),
        ];
    }
}
