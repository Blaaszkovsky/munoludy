<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnswerGroupResource\Pages;
use App\Models\Answer;
use App\Models\AnswerAlias;
use App\Models\AnswerGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AnswerGroupResource extends Resource
{
    protected static ?string $model = AnswerGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Głosowanie';

    protected static ?string $navigationLabel = 'Grupy odpowiedzi';

    protected static ?string $modelLabel = 'Grupa odpowiedzi';

    protected static ?string $pluralModelLabel = 'Grupy odpowiedzi';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('canonical_label')
                ->label('Etykieta kanoniczna')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('points_override')
                ->label('Override punktów')
                ->numeric()
                ->helperText('Pozostaw puste, aby używać obliczonych punktów.'),
            Forms\Components\Toggle::make('is_locked')
                ->label('Zablokowana')
                ->helperText('Po włączeniu grupy nie modyfikuje auto-merge.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question.title')
                    ->label('Kategoria')
                    ->limit(40)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('canonical_label')
                    ->label('Etykieta')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('aggregated_count')
                    ->label('Liczba wskazań')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('aggregated_points')
                    ->label('Auto punkty')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('points_override')
                    ->label('Override')
                    ->numeric()
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('is_locked')
                    ->label('Zablokowana')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Zaktualizowano')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('aggregated_points', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('question_id')
                    ->label('Pytanie')
                    ->relationship('question', 'title'),
                Tables\Filters\TernaryFilter::make('is_locked')
                    ->label('Zablokowana'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('merge')
                    ->label('Scal z...')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Scalanie grup odpowiedzi')
                    ->modalDescription('Ta operacja jest nieodwracalna. Wszystkie odpowiedzi i aliasy tej grupy zostaną przeniesione do grupy docelowej, a bieżąca grupa zostanie usunięta.')
                    ->form([
                        Forms\Components\Select::make('target_id')
                            ->label('Grupa docelowa')
                            ->options(fn ($record) => AnswerGroup::where('question_id', $record->question_id)
                                ->where('id', '!=', $record->id)
                                ->pluck('canonical_label', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $target = AnswerGroup::find($data['target_id']);
                        if (! $target) {
                            return;
                        }
                        Answer::where('answer_group_id', $record->id)
                            ->update(['answer_group_id' => $target->id]);
                        AnswerAlias::where('answer_group_id', $record->id)
                            ->update(['answer_group_id' => $target->id]);
                        $target->increment('aggregated_count', (int) $record->aggregated_count);
                        $target->increment('aggregated_points', (int) $record->aggregated_points);
                        $target->update(['is_locked' => true]);
                        $record->delete();
                    }),
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
            'index' => Pages\ListAnswerGroups::route('/'),
            'create' => Pages\CreateAnswerGroup::route('/create'),
            'edit' => Pages\EditAnswerGroup::route('/{record}/edit'),
        ];
    }
}
