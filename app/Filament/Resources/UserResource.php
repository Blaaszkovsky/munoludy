<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Administratorzy';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $modelLabel = 'Administrator';
    protected static ?string $pluralModelLabel = 'Administratorzy';
    protected static ?int $navigationSort = 99;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Imię i nazwisko')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->label('E-mail')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Forms\Components\TextInput::make('password')
                ->label('Hasło')
                ->password()
                ->revealable()
                ->dehydrated(fn ($state) => filled($state))
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->required(fn (string $context): bool => $context === 'create')
                ->helperText(fn (string $context) => $context === 'edit' ? 'Pozostaw puste, aby zachować aktualne hasło.' : 'Minimum 8 znaków.')
                ->minLength(8),
            Forms\Components\CheckboxList::make('roles')
                ->label('Role')
                ->relationship('roles', 'name')
                ->options(fn () => Role::pluck('name', 'name')->all())
                ->helperText('Tylko użytkownicy z rolą „super_admin" lub „editor" mają dostęp do panelu.')
                ->columns(2)
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Imię')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->separator(','),
                Tables\Columns\TextColumn::make('created_at')->label('Utworzono')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label('Rola'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (User $record) {
                        if ($record->id === auth()->id()) {
                            throw new \RuntimeException('Nie możesz usunąć samego siebie.');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
