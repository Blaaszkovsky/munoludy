<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnswerGroupResource\Pages;
use App\Models\Answer;
use App\Models\AnswerAlias;
use App\Models\AnswerGroup;
use App\Models\Edition;
use App\Models\Question;
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
