<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ParticipantResource\Pages;
use App\Models\Participant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ParticipantResource extends Resource
{
    protected static ?string $model = Participant::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Uczestnicy';
    protected static ?string $modelLabel = 'Uczestnik';
    protected static ?string $pluralModelLabel = 'Uczestnicy';
    protected static ?string $navigationGroup = 'Głosowanie';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'public');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('edition_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('link_hash')
                    ->required()
                    ->maxLength(64),
                Forms\Components\TextInput::make('access_code')
                    ->required()
                    ->maxLength(10),
                Forms\Components\Toggle::make('consented_privacy')
                    ->required(),
                Forms\Components\Toggle::make('consented_marketing')
                    ->required(),
                Forms\Components\TextInput::make('registered_ip')
                    ->maxLength(45),
                Forms\Components\TextInput::make('registered_user_agent')
                    ->maxLength(500),
                Forms\Components\TextInput::make('registered_fingerprint')
                    ->maxLength(255),
                Forms\Components\TextInput::make('user_com_user_id')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('voted_at'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Dane uczestnika')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('email')
                            ->label('E-mail')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('edition.name')
                            ->label('Edycja'),
                        Infolists\Components\TextEntry::make('access_code')
                            ->label('Kod dostępu')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('link_hash')
                            ->label('Link głosowania')
                            ->formatStateUsing(fn ($state) => $state ? url('/glosowanie/' . $state) : '—')
                            ->copyable()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Zgody')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\IconEntry::make('consented_privacy')
                            ->label('Zgoda RODO')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('consented_marketing')
                            ->label('Zgoda marketingowa')
                            ->boolean(),
                    ]),

                Infolists\Components\Section::make('Metadane rejestracji')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Infolists\Components\TextEntry::make('registered_ip')
                            ->label('IP rejestracji')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('registered_user_agent')
                            ->label('User-Agent')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('registered_fingerprint')
                            ->label('Fingerprint')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('user_com_user_id')
                            ->label('user.com ID')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Utworzono')
                            ->dateTime('Y-m-d H:i'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Zaktualizowano')
                            ->dateTime('Y-m-d H:i'),
                    ]),

                Infolists\Components\Section::make('Status głosowania')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\IconEntry::make('voted_at')
                            ->label('Czy zagłosował')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('voted_at')
                            ->label('Data oddania głosu')
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('submission.ip')
                            ->label('IP głosowania')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('submission.total_points')
                            ->label('Suma punktów')
                            ->numeric()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('submission.user_agent')
                            ->label('User-Agent głosowania')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Oddane głosy')
                    ->schema([
                        Infolists\Components\ViewEntry::make('submission_view')
                            ->view('filament.resources.participant.votes-summary'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('access_code')->label('Kod'),
                Tables\Columns\TextColumn::make('link_hash')
                    ->label('Link')
                    ->formatStateUsing(fn ($state) => url('/glosowanie/' . $state))
                    ->copyable(),
                Tables\Columns\IconColumn::make('voted_at')->boolean()->label('Zagłosował'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('edition_id')->relationship('edition', 'name'),
                Tables\Filters\TernaryFilter::make('voted_at')->label('Zagłosował')
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParticipants::route('/'),
            'create' => Pages\CreateParticipant::route('/create'),
            'view' => Pages\ViewParticipant::route('/{record}'),
            'edit' => Pages\EditParticipant::route('/{record}/edit'),
        ];
    }
}
