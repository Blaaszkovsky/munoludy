<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuestionResource\Pages;
use App\Filament\Resources\QuestionResource\RelationManagers;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('edition_id')
                ->relationship('edition', 'name')->required(),
            Forms\Components\Select::make('audience')
                ->options([
                    'public' => 'Publiczność',
                    'jury' => 'Jury',
                    'both' => 'Oboje',
                ])->required(),
            Forms\Components\Select::make('field_type')
                ->options(collect(\App\Enums\QuestionFieldType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                ->required()
                ->live(),
            Forms\Components\TextInput::make('order')->numeric()->default(0),
            Forms\Components\TextInput::make('title')->required()->maxLength(255),
            Forms\Components\Textarea::make('description')->rows(4),
            Forms\Components\Toggle::make('is_required'),
            Forms\Components\TagsInput::make('ranked_points')
                ->label('Punkty (pozycje 1→5)')
                ->visible(fn ($get) => $get('field_type') === 'ranked_text_5')
                ->default(['5', '4', '3', '2', '1']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('edition_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('audience')
                    ->searchable(),
                Tables\Columns\TextColumn::make('field_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_required')
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
            RelationManagers\OptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit' => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}
