<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoteSubmissionResource\Pages;
use App\Models\VoteSubmission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VoteSubmissionResource extends Resource
{
    protected static ?string $model = VoteSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Głosowanie';

    protected static ?string $navigationLabel = 'Oddane głosy';

    protected static ?string $modelLabel = 'Głos';

    protected static ?string $pluralModelLabel = 'Głosy';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('participant_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('edition_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('audience')
                    ->required()
                    ->maxLength(255)
                    ->default('public'),
                Forms\Components\DateTimePicker::make('submitted_at'),
                Forms\Components\TextInput::make('ip')
                    ->maxLength(45),
                Forms\Components\TextInput::make('user_agent')
                    ->maxLength(500),
                Forms\Components\TextInput::make('total_points')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('participant.email')
                    ->label('E-mail')
                    ->searchable(),
                Tables\Columns\TextColumn::make('edition.name')
                    ->label('Edycja')
                    ->sortable(),
                Tables\Columns\TextColumn::make('audience')
                    ->label('Grupa')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'jury' => 'warning',
                        'public' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Oddany')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_points')
                    ->label('Suma punktów')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user_agent')
                    ->label('User-Agent')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('audience')
                    ->label('Grupa')
                    ->options([
                        'public' => 'Publiczność',
                        'jury' => 'Jury',
                    ]),
                Tables\Filters\SelectFilter::make('edition_id')
                    ->label('Edycja')
                    ->relationship('edition', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListVoteSubmissions::route('/'),
            'create' => Pages\CreateVoteSubmission::route('/create'),
            'edit' => Pages\EditVoteSubmission::route('/{record}/edit'),
        ];
    }
}
