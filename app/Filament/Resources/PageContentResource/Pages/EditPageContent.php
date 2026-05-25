<?php

namespace App\Filament\Resources\PageContentResource\Pages;

use App\Filament\Resources\PageContentResource;
use Filament\Actions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;

class EditPageContent extends EditRecord
{
    protected static string $resource = PageContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function getFormSchema(): array
    {
        $view = $this->record?->view;

        return match ($view) {
            'landing' => $this->landingSchema(),
            'vote_start_public' => $this->voteStartSchema('public'),
            'vote_start_jury' => $this->voteStartSchema('jury'),
            'vote_thank_you' => $this->voteThankYouSchema(),
            'results' => $this->resultsSchema(),
            'vote_closed' => $this->voteClosedSchema(),
            default => $this->fallbackSchema(),
        };
    }

    // ------------------------------------------------------------------
    // Mutators — keep intro_paragraphs flat in DB but editable as Repeater.
    // ------------------------------------------------------------------

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['content']['intro_paragraphs']) && is_array($data['content']['intro_paragraphs'])) {
            $data['content']['intro_paragraphs'] = collect($data['content']['intro_paragraphs'])
                ->map(fn ($item) => is_array($item) ? $item : ['paragraph' => (string) $item])
                ->values()
                ->all();
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['content']['intro_paragraphs']) && is_array($data['content']['intro_paragraphs'])) {
            $data['content']['intro_paragraphs'] = collect($data['content']['intro_paragraphs'])
                ->map(function ($item) {
                    if (is_array($item)) {
                        return $item['paragraph'] ?? ($item[0] ?? '');
                    }
                    return (string) $item;
                })
                ->filter(fn ($v) => trim((string) $v) !== '')
                ->values()
                ->all();
        }

        return $data;
    }

    // ------------------------------------------------------------------
    // Per-view schemas.
    // ------------------------------------------------------------------

    protected function ogSection(): Section
    {
        return Section::make('Open Graph / SEO')
            ->description('Dane używane w tagach OG i meta tytule tej podstrony.')
            ->collapsible()
            ->collapsed()
            ->schema([
                TextInput::make('og_title')->label('OG Title')->maxLength(255),
                Textarea::make('og_description')->label('OG Description')->rows(3)->maxLength(500),
                TextInput::make('og_image')->label('OG Image (ścieżka storage/…)')->maxLength(255),
            ])->columns(1);
    }

    protected function landingSchema(): array
    {
        return [
            Section::make('Nagłówek')->collapsible()->schema([
                TextInput::make('content.hero_title')->label('Tytuł hero')->required(),
                TextInput::make('content.hero_powered_by')->label('Tekst „powered by”'),
            ])->columns(2),

            Section::make('Opis')->collapsible()->schema([
                Textarea::make('content.intro')->label('Wprowadzenie')->rows(6)->required(),
            ]),

            Section::make('Formularz rejestracji')->collapsible()->schema([
                TextInput::make('content.form_title')->label('Tytuł formularza'),
                TextInput::make('content.form_subtitle')->label('Podtytuł'),
                TextInput::make('content.form_email_label')->label('Etykieta pola e-mail'),
                TextInput::make('content.form_email_placeholder')->label('Placeholder e-mail'),
                TextInput::make('content.form_submit_label')->label('Etykieta przycisku'),
            ])->columns(2),

            Section::make('Checkboxy (zgody)')->collapsible()->schema([
                Textarea::make('content.form_privacy_label')
                    ->label('Checkbox: polityka prywatności (wymagany)')
                    ->helperText('Zaznacz gwiazdką * na końcu, jeśli ma być wyróżniony jako wymagany.')
                    ->rows(3),
                Textarea::make('content.form_marketing_label')
                    ->label('Checkbox: zgoda marketingowa (opcjonalny)')
                    ->helperText('Zaznaczenie tego checkboxa ustawia atrybut „Marketing email = true" w user.com.')
                    ->rows(3),
            ]),

            Section::make('Sukces rejestracji')->collapsible()->schema([
                TextInput::make('content.success_title')->label('Tytuł sukcesu'),
                Textarea::make('content.success_text')
                    ->label('Tekst sukcesu (zawierać :email)')
                    ->helperText('Użyj tokenu :email aby wstawić adres e-mail użytkownika.')
                    ->rows(3),
            ])->columns(2),

            Section::make('RODO')->collapsible()->collapsed()->schema([
                Textarea::make('content.rodo')->label('Tekst RODO')->rows(5),
            ]),

            $this->ogSection(),
        ];
    }

    protected function voteStartSchema(string $audience = 'public'): array
    {
        $formSection = $audience === 'jury'
            ? Section::make('Formularz weryfikacji e-mail')->collapsible()->schema([
                TextInput::make('content.email_label')->label('Etykieta pola')->required(),
                TextInput::make('content.email_placeholder')->label('Placeholder'),
                TextInput::make('content.start_button')->label('Tekst przycisku')->required(),
            ])->columns(2)
            : Section::make('Formularz kodu dostępu')->collapsible()->schema([
                TextInput::make('content.code_label')->label('Etykieta pola')->required(),
                TextInput::make('content.code_placeholder')->label('Placeholder'),
                TextInput::make('content.start_button')->label('Tekst przycisku')->required(),
            ])->columns(2);

        return [
            Section::make('Nagłówek')->collapsible()->schema([
                TextInput::make('content.title')->label('Tytuł')->required(),
            ]),

            Section::make('Wprowadzenie')
                ->description('Każdy akapit to osobny wpis. Zapisywane są jako lista tekstów.')
                ->collapsible()
                ->schema([
                    Repeater::make('content.intro_paragraphs')
                        ->label('Akapity wprowadzenia')
                        ->schema([
                            Textarea::make('paragraph')->label('Treść akapitu')->rows(3)->required(),
                        ])
                        ->reorderable()
                        ->collapsible()
                        ->defaultItems(1)
                        ->addActionLabel('Dodaj akapit'),
                ]),

            Section::make('Podpis (opcjonalnie)')->collapsible()->collapsed()->schema([
                TextInput::make('content.signature_name')->label('Imię i nazwisko'),
                TextInput::make('content.signature_role')->label('Funkcja / rola'),
            ])->columns(2),

            $formSection,

            $this->ogSection(),
        ];
    }

    protected function voteThankYouSchema(): array
    {
        return [
            Section::make('Treść podziękowania')->collapsible()->schema([
                TextInput::make('content.title')->label('Tytuł')->required(),
                Textarea::make('content.text')->label('Tekst')->rows(5)->required(),
            ]),
            $this->ogSection(),
        ];
    }

    protected function resultsSchema(): array
    {
        return [
            Section::make('Nagłówek strony wyników')->collapsible()->schema([
                TextInput::make('content.title')->label('Tytuł'),
                Textarea::make('content.subtitle')->label('Podtytuł')->rows(3),
            ]),
            $this->ogSection(),
        ];
    }

    protected function voteClosedSchema(): array
    {
        return [
            Section::make('Głosowanie zamknięte')->collapsible()->schema([
                TextInput::make('content.title')->label('Tytuł')->required(),
                Textarea::make('content.text')->label('Tekst')->rows(5)->required(),
            ]),
            $this->ogSection(),
        ];
    }

    protected function fallbackSchema(): array
    {
        return [
            Section::make('Treść (surowy JSON)')
                ->description('Widok nie ma dedykowanego formularza. Edycja przez pary klucz-wartość.')
                ->schema([
                    \Filament\Forms\Components\KeyValue::make('content')
                        ->label('Treść')
                        ->columnSpanFull(),
                ]),
            $this->ogSection(),
        ];
    }
}
