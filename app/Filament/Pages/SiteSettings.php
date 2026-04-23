<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SiteSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.site-settings';
    protected static ?string $title = 'Ustawienia';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'og_default_title' => SiteSetting::get('og_default_title'),
            'og_default_description' => SiteSetting::get('og_default_description'),
            'og_default_image' => SiteSetting::get('og_default_image'),
            'rodo_admin' => SiteSetting::get('rodo_admin'),
            'user_com_api_key' => SiteSetting::get('user_com_api_key'),
            'user_com_base_url' => SiteSetting::get('user_com_base_url', config('munoludy.user_com.base_url')),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Open Graph (fallback)')->schema([
                    TextInput::make('og_default_title'),
                    Textarea::make('og_default_description')->rows(3),
                    TextInput::make('og_default_image')->label('Ścieżka do obrazu (storage/images/...)'),
                ])->columns(1),
                Section::make('RODO i stopka')->schema([
                    Textarea::make('rodo_admin')->rows(5)->label('Tekst RODO'),
                ]),
                Section::make('Integracja user.com')->schema([
                    TextInput::make('user_com_base_url'),
                    TextInput::make('user_com_api_key')->password()->revealable()
                        ->helperText('Nadpisuje wartość z .env'),
                ])->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        foreach ($data as $key => $value) {
            SiteSetting::set($key, $value, $key === 'user_com_api_key');
        }
        Notification::make()->success()->title('Zapisano')->send();
    }

    protected function getFormActions(): array
    {
        return [Action::make('save')->label('Zapisz')->submit('save')];
    }
}
