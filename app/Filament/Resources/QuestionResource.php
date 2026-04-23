<?php

namespace App\Filament\Resources;

use App\Enums\QuestionFieldType;
use App\Filament\Resources\QuestionResource\Pages;
use App\Filament\Resources\QuestionResource\RelationManagers;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'Pytania';
    protected static ?string $modelLabel = 'Pytanie';
    protected static ?string $pluralModelLabel = 'Pytania';
    protected static ?string $navigationGroup = 'Plebiscyt';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Podstawowe')->schema([
                Forms\Components\Select::make('edition_id')
                    ->label('Edycja')
                    ->relationship('edition', 'name')
                    ->required(),
                Forms\Components\Select::make('audience')
                    ->label('Odbiorca formularza')
                    ->options([
                        'public' => 'Publiczność',
                        'jury' => 'Jury',
                        'both' => 'Obie grupy',
                    ])
                    ->required(),
                Forms\Components\Select::make('field_type')
                    ->label('Typ pola')
                    ->options(collect(QuestionFieldType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('order')
                    ->label('Kolejność')
                    ->numeric()
                    ->default(0)
                    ->helperText('Niższa wartość = wyższa pozycja. Możesz też przeciągać wiersze na liście.'),
            ])->columns(2),
            Forms\Components\Section::make('Treść')->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Tytuł (nazwa kategorii)')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->label('Opis / instrukcja')
                    ->rows(4),
                Forms\Components\Toggle::make('is_required')
                    ->label('Wymagane')
                    ->helperText('Jeżeli włączone, użytkownik musi odpowiedzieć przed wysłaniem.'),
            ]),
            Forms\Components\Section::make('Punktacja ranking')->schema([
                Forms\Components\TagsInput::make('ranked_points')
                    ->label('Punkty za miejsca 1→5')
                    ->default(['5', '4', '3', '2', '1'])
                    ->helperText('Pierwsza wartość = 1. miejsce (najwyższe), ostatnia = 5. miejsce.'),
            ])->visible(fn ($get) => $get('field_type') === QuestionFieldType::RankedText5->value),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('order')
            ->defaultSort('order')
            ->groups([
                Group::make('audience')
                    ->label('Odbiorca')
                    ->getTitleFromRecordUsing(fn (Question $record) => match ($record->audience->value) {
                        'public' => 'Publiczność',
                        'jury' => 'Jury',
                        'both' => 'Obie grupy',
                        default => (string) $record->audience->value,
                    })
                    ->collapsible(),
            ])
            ->defaultGroup('audience')
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->label('#')
                    ->sortable()
                    ->width(60),
                Tables\Columns\TextColumn::make('title')
                    ->label('Tytuł')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('field_type')
                    ->label('Typ pola')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ($state instanceof QuestionFieldType)
                        ? $state->label()
                        : (QuestionFieldType::tryFrom((string) $state)?->label() ?? (string) $state)),
                Tables\Columns\TextColumn::make('audience')
                    ->label('Odbiorca')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ((string) ($state?->value ?? $state)) {
                        'public' => 'Publiczność',
                        'jury' => 'Jury',
                        'both' => 'Obie grupy',
                        default => (string) $state,
                    })
                    ->color(fn ($state) => match ((string) ($state?->value ?? $state)) {
                        'jury' => 'warning',
                        'public' => 'primary',
                        'both' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_required')->label('Wymagane')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('audience')
                    ->label('Odbiorca')
                    ->options([
                        'public' => 'Publiczność',
                        'jury' => 'Jury',
                        'both' => 'Obie grupy',
                    ]),
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
