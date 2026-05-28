<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnswerGroupResource\Pages;
use App\Models\Answer;
use App\Models\AnswerAlias;
use App\Models\AnswerGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
            Forms\Components\Toggle::make('is_podium')
                ->label('Na podium')
                ->helperText('Oznacz jako wyróżnienie podium (1–3 miejsce).'),
            Forms\Components\Select::make('podium_position')
                ->label('Pozycja podium')
                ->options([1 => '1. miejsce', 2 => '2. miejsce', 3 => '3. miejsce'])
                ->placeholder('—')
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['question']))
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
                    ->label('Wskazań')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('aggregated_points')
                    ->label('Auto pkt')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('points_override')
                    ->label('Override')
                    ->numeric()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('final_points')
                    ->label('Suma pkt')
                    ->state(fn (AnswerGroup $record) => $record->finalPoints())
                    ->weight('bold')
                    ->sortable(query: function (Builder $query, string $direction) {
                        $query->orderByRaw('COALESCE(points_override, aggregated_points) ' . ($direction === 'desc' ? 'desc' : 'asc'));
                    }),
                Tables\Columns\TextColumn::make('share_percent')
                    ->label('% kategorii')
                    ->state(function (AnswerGroup $record) {
                        static $cache = [];
                        $qid = $record->question_id;
                        if (!isset($cache[$qid])) {
                            $cache[$qid] = (int) AnswerGroup::where('question_id', $qid)
                                ->get()
                                ->sum(fn (AnswerGroup $g) => $g->finalPoints());
                        }
                        $total = $cache[$qid];
                        if ($total <= 0) {
                            return '0,0%';
                        }
                        return number_format(($record->finalPoints() / $total) * 100, 1, ',', ' ') . '%';
                    }),
                Tables\Columns\IconColumn::make('is_locked')
                    ->label('Lock')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_podium')
                    ->label('Podium')
                    ->boolean()
                    ->trueIcon('heroicon-o-trophy')
                    ->trueColor('warning'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Zaktualizowano')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('final_points', 'desc')
            ->groups([
                Tables\Grouping\Group::make('question.title')
                    ->label('Kategoria')
                    ->collapsible(),
            ])
            ->defaultGroup('question.title')
            ->filters([
                Tables\Filters\SelectFilter::make('question_id')
                    ->label('Pytanie')
                    ->relationship('question', 'title')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('audience')
                    ->label('Adresat')
                    ->options([
                        'public' => 'Publiczność',
                        'jury' => 'Jury',
                        'both' => 'Oba',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'] ?? null)) {
                            $query->whereHas('question', fn ($q) => $q->where('audience', $data['value']));
                        }
                    }),
                Tables\Filters\TernaryFilter::make('is_locked')
                    ->label('Tylko zablokowane')
                    ->trueLabel('Tak')
                    ->falseLabel('Nie')
                    ->placeholder('Wszystkie'),
                Tables\Filters\TernaryFilter::make('is_podium')
                    ->label('Tylko podium')
                    ->trueLabel('Tak')
                    ->falseLabel('Nie')
                    ->placeholder('Wszystkie'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('merge')
                    ->label('Scal z...')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(fn (AnswerGroup $record) => 'Scalanie w kategorii: ' . ($record->question?->title ?? '—'))
                    ->modalDescription('Wybierz grupę docelową. Lista zawiera wyłącznie grupy z tej samej kategorii. Operacja jest nieodwracalna — wszystkie odpowiedzi i aliasy bieżącej grupy zostaną przeniesione do grupy docelowej, a bieżąca grupa zostanie usunięta.')
                    ->form(function (AnswerGroup $record) {
                        $suggested = $record->suggestMergeCandidates(8);

                        $suggestedOptions = $suggested
                            ->mapWithKeys(function (AnswerGroup $g) {
                                $score = (int) round((float) $g->getAttribute('similarity_score'));
                                $label = $g->canonical_label . ' — ' . $score . '% podobieństwa'
                                    . ' · ' . (int) $g->aggregated_count . ' wskazań';
                                return [$g->id => $label];
                            })
                            ->all();

                        return [
                            Forms\Components\Placeholder::make('source_info')
                                ->label('Scalasz')
                                ->content(fn () => $record->canonical_label
                                    . ' (kategoria: ' . ($record->question?->title ?? '—') . ')'),

                            Forms\Components\Radio::make('target_id')
                                ->label('Sugerowane grupy do scalenia')
                                ->options($suggestedOptions)
                                ->helperText('Sugestie wyliczone automatycznie na podstawie podobieństwa nazw i aliasów w obrębie tej samej kategorii.')
                                ->visible(!empty($suggestedOptions))
                                ->dehydrated(fn ($state) => filled($state)),

                            Forms\Components\Select::make('target_id_fallback')
                                ->label(empty($suggestedOptions) ? 'Grupa docelowa' : 'lub wybierz dowolną grupę z tej kategorii')
                                ->options(fn () => AnswerGroup::where('question_id', $record->question_id)
                                    ->where('id', '!=', $record->id)
                                    ->orderBy('canonical_label')
                                    ->pluck('canonical_label', 'id'))
                                ->searchable()
                                ->preload()
                                ->dehydrated(fn ($state) => filled($state)),
                        ];
                    })
                    ->action(function (AnswerGroup $record, array $data) {
                        $targetId = $data['target_id'] ?? $data['target_id_fallback'] ?? null;
                        $target = $targetId ? AnswerGroup::find($targetId) : null;

                        if (! $target) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Nie wybrano grupy docelowej')
                                ->send();
                            return;
                        }

                        if ($target->question_id !== $record->question_id) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Nie można scalać między kategoriami')
                                ->body('Bieżąca grupa i docelowa muszą należeć do tej samej kategorii.')
                                ->send();
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

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Scalono z grupą: ' . $target->canonical_label)
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mergeBulk')
                        ->label('Scal zaznaczone w jedną grupę')
                        ->icon('heroicon-o-arrows-pointing-in')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Masowe scalanie grup odpowiedzi')
                        ->modalDescription('Wszystkie zaznaczone grupy zostaną scalone w jedną wybraną grupę docelową. Operacja jest nieodwracalna i działa tylko w obrębie jednej kategorii.')
                        ->form(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $questionIds = $records->pluck('question_id')->unique();

                            if ($questionIds->count() > 1) {
                                return [
                                    Forms\Components\Placeholder::make('error')
                                        ->label('Błąd')
                                        ->content('Zaznaczone grupy należą do różnych kategorii. Zaznacz wyłącznie grupy z jednej kategorii.'),
                                ];
                            }

                            $records = $records->loadMissing('question');
                            $categoryTitle = $records->first()?->question?->title ?? '—';

                            // Default target = group with the most votes/points.
                            $default = $records->sortByDesc(fn (AnswerGroup $g) => $g->finalPoints())->first();

                            $options = $records
                                ->sortByDesc(fn (AnswerGroup $g) => $g->finalPoints())
                                ->mapWithKeys(fn (AnswerGroup $g) => [
                                    $g->id => $g->canonical_label
                                        . ' — ' . $g->finalPoints() . ' pkt · '
                                        . (int) $g->aggregated_count . ' wskazań',
                                ])
                                ->all();

                            return [
                                Forms\Components\Placeholder::make('info')
                                    ->label('Kategoria')
                                    ->content($categoryTitle . ' · zaznaczono ' . $records->count() . ' grup'),

                                Forms\Components\Select::make('target_id')
                                    ->label('Grupa docelowa (wszystkie pozostałe zostaną do niej scalone)')
                                    ->options($options)
                                    ->default($default?->id)
                                    ->required()
                                    ->searchable(),
                            ];
                        })
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $questionIds = $records->pluck('question_id')->unique();
                            if ($questionIds->count() > 1) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Różne kategorie')
                                    ->body('Zaznaczone grupy muszą należeć do tej samej kategorii.')
                                    ->send();
                                return;
                            }

                            $targetId = (int) ($data['target_id'] ?? 0);
                            $target = AnswerGroup::find($targetId);

                            if (! $target) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Brak grupy docelowej')
                                    ->send();
                                return;
                            }

                            $sources = $records->reject(fn (AnswerGroup $g) => $g->id === $target->id);

                            if ($sources->isEmpty()) {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title('Nic do scalenia')
                                    ->body('Zaznaczono tylko grupę docelową.')
                                    ->send();
                                return;
                            }

                            $sourceIds = $sources->pluck('id')->all();

                            \DB::transaction(function () use ($sourceIds, $sources, $target) {
                                Answer::whereIn('answer_group_id', $sourceIds)
                                    ->update(['answer_group_id' => $target->id]);
                                AnswerAlias::whereIn('answer_group_id', $sourceIds)
                                    ->update(['answer_group_id' => $target->id]);

                                $countSum = (int) $sources->sum('aggregated_count');
                                $pointsSum = (int) $sources->sum('aggregated_points');

                                $target->increment('aggregated_count', $countSum);
                                $target->increment('aggregated_points', $pointsSum);
                                $target->update(['is_locked' => true]);

                                AnswerGroup::whereIn('id', $sourceIds)->delete();
                            });

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Scalono ' . count($sourceIds) . ' grup w: ' . $target->canonical_label)
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informacje o grupie')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('canonical_label')
                            ->label('Etykieta kanoniczna')
                            ->weight('bold')
                            ->columnSpan(2),
                        Infolists\Components\TextEntry::make('question.title')
                            ->label('Kategoria'),
                        Infolists\Components\TextEntry::make('aggregated_count')
                            ->label('Wskazań')
                            ->numeric(),
                        Infolists\Components\TextEntry::make('aggregated_points')
                            ->label('Punkty auto')
                            ->numeric(),
                        Infolists\Components\TextEntry::make('points_override')
                            ->label('Override')
                            ->numeric()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('finalPoints')
                            ->label('Suma pkt')
                            ->state(fn (AnswerGroup $record) => $record->finalPoints())
                            ->weight('bold'),
                        Infolists\Components\IconEntry::make('is_locked')
                            ->label('Zablokowana')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('is_podium')
                            ->label('Na podium')
                            ->boolean(),
                    ]),

                Infolists\Components\Section::make('Aliasy')
                    ->schema([
                        Infolists\Components\ViewEntry::make('aliases_view')
                            ->view('filament.resources.answer-group.aliases'),
                    ]),

                Infolists\Components\Section::make('Odpowiedzi w grupie')
                    ->schema([
                        Infolists\Components\ViewEntry::make('answers_view')
                            ->view('filament.resources.answer-group.answers'),
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
            'view' => Pages\ViewAnswerGroup::route('/{record}'),
            'edit' => Pages\EditAnswerGroup::route('/{record}/edit'),
        ];
    }
}
