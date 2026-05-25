<?php

namespace Database\Seeders;

use App\Models\Edition;
use App\Models\PageContent;
use Illuminate\Database\Seeder;

class PageContent2026Seeder extends Seeder
{
    public function run(): void
    {
        $edition = Edition::where('slug', 'munoludy-2026')->firstOrFail();

        $views = [
            'landing' => [
                'og_title' => 'Munoludy 2025 — zagłosuj w plebiscycie polskiej sceny klubowej',
                'og_description' => 'Plebiscyt Muno.pl i biletomat.pl. Wybierz DJów, kluby, festiwale i inicjatywy roku 2025.',
                'content' => [
                    'hero_title' => 'Munoludy 2025',
                    'hero_powered_by' => 'powered by',
                    'intro' => 'Munoludy to jeden z najważniejszych plebiscytów polskiej sceny klubowo-festiwalowej, organizowany przez redakcję Muno.pl oraz biletomat.pl. W jego ramach wyróżniane są najciekawsze postacie, miejsca, wydarzenia i inicjatywy związane przede wszystkim z muzyką elektroniczną. Głosowanie obejmuje kategorie takie jak DJ/DJ-ka/Live Act Roku, Event Roku, Festiwal Roku, Klub Roku czy Inicjatywa Roku, a laureaci otrzymują prestiżowe statuetki Munoludów.',
                    'form_title' => 'Weź udział w plebiscycie',
                    'form_subtitle' => 'Na podany adres e-mail zostanie wysłany indywidualny link do formularza głosowań.',
                    'form_email_label' => 'Adres e-mail',
                    'form_email_placeholder' => 'twoj@email.pl',
                    'form_privacy_label' => 'Wyrażam zgodę na przetwarzanie moich danych osobowych oraz akceptuję <a href="https://muno.pl/regulamin-plebiscytu-munoludy-2025/" target="_blank" rel="noopener noreferrer" class="underline hover:text-white">regulamin plebiscytu</a>. Więcej informacji o przetwarzaniu danych osobowych w naszej <a href="https://muno.pl/regulamin-plebiscytu-munoludy-2025/" target="_blank" rel="noopener noreferrer" class="underline hover:text-white">polityce prywatności</a>.',
                    'form_marketing_label' => 'TAK, chcę być informowany/a e-mailowo o promocjach i ofertach specjalnych i w związku z tym zgadzam się na otrzymywanie informacji handlowych dotyczących usług oraz współpracy wysyłanych przez Kicket.com sp. z o.o. z siedzibą w Warszawie. Zgoda obejmuje możliwość kontaktu również po zakończeniu plebiscytu.',
                    'form_submit_label' => 'Wyślij',
                    'rodo' => 'RODO: Administratorem Twoich danych osobowych jest Kicket.com sp. z o.o. z siedzibą w Warszawie, ul. Zajęcza 15, NIP 1132896755, REGON: 3627622198, KRS: 0000579264 kontakt: info@biletomat.pl.',
                    'success_title' => 'Sprawdź swoją skrzynkę!',
                    'success_text' => 'Na adres :email został wysłany link do formularza głosowania.',
                    'success_hint' => 'Nie widzisz maila? Sprawdź folder SPAM.',
                ],
            ],
            'vote_start_public' => [
                'og_title' => 'Munoludy 2025 — głosowanie',
                'og_description' => 'Weź udział w plebiscycie Munoludy 2025.',
                'content' => [
                    'title' => 'Witaj w głosowaniu!',
                    'intro_paragraphs' => [
                        'Jeśli czytasz ten tekst, to znaczy, że jesteś częścią plebiscytu Munoludy 2025 powered by biletomat.pl.',
                        'Wspólnie wypełnijmy formularz dedykowany Nagrodom Publiczności. Lada moment podzielisz się z nami swoimi typami w 5 kluczowych kategoriach.',
                        'Uwaga: podanie 5 razy tej samej nazwy w obrębie jednej kategorii skutkować będzie przyznaniem maksymalnie 5 punktów.',
                    ],
                    'signature_name' => 'Hubert Grupa',
                    'signature_role' => 'redaktor naczelny Muno',
                    'code_label' => 'Wprowadź kod dostępu',
                    'code_placeholder' => '000000',
                    'start_button' => 'Rozpocznij głosowanie',
                ],
            ],
            'vote_start_jury' => [
                'og_title' => 'Munoludy 2025 — głosowanie jury',
                'og_description' => 'Głosowanie jury w plebiscycie Munoludy 2025.',
                'content' => [
                    'title' => 'Głosowanie jury',
                    'intro_paragraphs' => [
                        'Dziękujemy za udział w jury plebiscytu Munoludy 2025.',
                        'Masz 7 kategorii do wypełnienia. W każdej wskaż 5 propozycji, punktowanych od 5 do 1 punktu.',
                    ],
                    'email_label' => 'Podaj swój adres e-mail, aby się zweryfikować',
                    'email_placeholder' => 'jan.kowalski@example.com',
                    'start_button' => 'Rozpocznij głosowanie',
                ],
            ],
            'vote_thank_you' => [
                'content' => [
                    'title' => 'Dziękujemy!',
                    'subtitle' => 'Twój głos został pomyślnie zapisany.',
                    'text' => 'Dziękujemy za udział w plebiscycie Munoludy 2025. Twoje zdanie pomoże wyłonić najlepszych przedstawicieli polskiej sceny elektronicznej.',
                ],
            ],
            'results' => [
                'og_title' => 'Wyniki Munoludy 2025',
                'og_description' => 'Oto laureaci plebiscytu Munoludy 2025.',
                'content' => [
                    'title' => 'Wyniki plebiscytu Munoludy 2025',
                    'subtitle' => 'Oto laureaci wybrani przez publiczność i jury.',
                ],
            ],
            'vote_closed' => [
                'content' => [
                    'title' => 'Głosowanie zakończone',
                    'text' => 'Dziękujemy za zaangażowanie. Wyniki pojawią się wkrótce na stronie głównej.',
                ],
            ],
        ];

        foreach ($views as $view => $data) {
            PageContent::updateOrCreate(
                ['edition_id' => $edition->id, 'view' => $view],
                [
                    'content' => $data['content'],
                    'og_title' => $data['og_title'] ?? null,
                    'og_description' => $data['og_description'] ?? null,
                ]
            );
        }
    }
}
