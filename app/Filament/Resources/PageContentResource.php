<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageContentResource\Pages;
use App\Models\PageContent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PageContentResource extends Resource
{
    protected static ?string $model = PageContent::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Treści';

    protected static ?string $navigationLabel = 'Treści podstron';

    protected static ?string $modelLabel = 'Treść podstrony';

    protected static ?string $pluralModelLabel = 'Treści podstron';

    public const VIEW_LABELS = [
        'landing' => 'Strona główna (landing)',
        'vote_start_public' => 'Start głosowania – Publiczność',
        'vote_start_jury' => 'Start głosowania – Jury',
        'vote_thank_you' => 'Podziękowanie po głosowaniu',
        'results' => 'Wyniki',
        'vote_closed' => 'Głosowanie zamknięte',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema(function ($livewire) {
            if (method_exists($livewire, 'getFormSchema')) {
                $schema = $livewire->getFormSchema();
                if (!empty($schema)) {
                    return $schema;
                }
            }

            // Fallback schema (used e.g. in create mode).
            return [
                Forms\Components\Select::make('edition_id')
                    ->label('Edycja')
                    ->relationship('edition', 'name')
                    ->required(),
                Forms\Components\Select::make('view')
                    ->label('Podstrona')
                    ->options(self::VIEW_LABELS)
                    ->required(),
                Forms\Components\KeyValue::make('content')
                    ->label('Treść (raw)')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('og_title')->maxLength(255),
                Forms\Components\Textarea::make('og_description')->rows(3)->maxLength(500),
                Forms\Components\TextInput::make('og_image')
                    ->label('Ścieżka obrazka OG')
                    ->maxLength(255),
            ];
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('edition.name')
                    ->label('Edycja')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('view')
                    ->label('Podstrona')
                    ->formatStateUsing(fn (string $state): string => self::VIEW_LABELS[$state] ?? $state)
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('og_title')
                    ->label('OG Title')
                    ->limit(60)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modyfikacja')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultGroup('edition.name')
            ->defaultSort('view')
            ->filters([
                Tables\Filters\SelectFilter::make('view')
                    ->options(self::VIEW_LABELS),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPageContents::route('/'),
            'create' => Pages\CreatePageContent::route('/create'),
            'edit' => Pages\EditPageContent::route('/{record}/edit'),
        ];
    }
}
