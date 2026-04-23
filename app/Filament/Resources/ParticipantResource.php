<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ParticipantResource\Pages;
use App\Filament\Resources\ParticipantResource\RelationManagers;
use App\Models\Participant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ParticipantResource extends Resource
{
    protected static ?string $model = Participant::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Uczestnicy';
    protected static ?string $modelLabel = 'Uczestnik';
    protected static ?string $pluralModelLabel = 'Uczestnicy';
    protected static ?string $navigationGroup = 'Głosowanie';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('edition_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('type')
                    ->required()
                    ->maxLength(255)
                    ->default('public'),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn ($state) => (is_string($state) ? $state : $state?->value) === 'public' ? 'primary' : 'warning'),
                Tables\Columns\TextColumn::make('access_code')->label('Kod'),
                Tables\Columns\TextColumn::make('link_hash')
                    ->label('Link')
                    ->formatStateUsing(fn ($state) => url('/glosowanie/' . $state))
                    ->copyable(),
                Tables\Columns\IconColumn::make('voted_at')->boolean()->label('Zagłosował'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options([
                    'public' => 'Publiczność',
                    'jury' => 'Jury',
                ]),
                Tables\Filters\SelectFilter::make('edition_id')->relationship('edition', 'name'),
                Tables\Filters\TernaryFilter::make('voted_at')->label('Zagłosował')
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListParticipants::route('/'),
            'create' => Pages\CreateParticipant::route('/create'),
            'edit' => Pages\EditParticipant::route('/{record}/edit'),
        ];
    }
}
