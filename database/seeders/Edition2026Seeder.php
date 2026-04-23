<?php

namespace Database\Seeders;

use App\Enums\EditionStatus;
use App\Enums\FormAudience;
use App\Enums\QuestionFieldType;
use App\Models\Edition;
use App\Models\Question;
use Illuminate\Database\Seeder;

class Edition2026Seeder extends Seeder
{
    public function run(): void
    {
        $edition = Edition::firstOrCreate(
            ['slug' => 'munoludy-2026'],
            [
                'name' => 'Munoludy 2026',
                'user_com_list_id' => 17,
                'user_com_link_field' => 'munoludy2026_link',
                'user_com_code_field' => 'munoludy2026_kod',
                'starts_at' => now(),
                'ends_at' => now()->addDays(30),
                'status' => EditionStatus::Active,
                'is_active' => true,
            ]
        );

        $publicCategories = [
            ['DJ/DJ-ka/LIVE ACT ROKU POLSKA 2025', 'Bez podziału na gatunki i jakiekolwiek inne dziedziny. Wskaż 5 DJów/DJek lub artystów_tki live act, dla których Twoim zdaniem rok 2025 był szczególnie udany.'],
            ['KLUB ROKU POLSKA 2025', 'Niezależnie od wielkości, lokalizacji czy częstotliwości działania. Wskaż 5 Twoim zdaniem topowych klubów elektronicznych w Polsce, do których należał rok 2025.'],
            ['FESTIWAL ROKU POLSKA 2025', 'Niezależnie od obszerności line-upu, lokalizacji czy wielkości frekwencji. W tej kategorii nagradzasz ulubione polskie festiwale! Warunek: bierzemy pod uwagę wyłącznie te imprezy, które trwają więcej niż 1 dzień. Dla 1-dniowych wydarzeń przygotowaliśmy kolejną kategorię.'],
            ['EVENT ROKU POLSKA 2025', 'Niezależnie czy mowa o imprezie w klubie, evencie halowym czy widowisku na stadionie. Ta kategoria przeznaczona jest dla najlepszych jednodniowych wydarzeń o elektronicznym charakterze.'],
            ['INICJATYWA ROKU POLSKA 2025', 'Nowy cykl imprez? Kolektyw? Szkoła DJingu? Podcast o kulturze klubowej? Wytwórnia? A może jeszcze coś innego? Wskaż inicjatywy, które Twoim zdaniem wnoszą coś wartościowego i świeżego na polską scenę!'],
        ];

        foreach ($publicCategories as $i => [$title, $description]) {
            Question::firstOrCreate(
                ['edition_id' => $edition->id, 'audience' => FormAudience::Public_, 'order' => $i + 1],
                [
                    'field_type' => QuestionFieldType::RankedText5,
                    'title' => $title,
                    'description' => $description,
                    'is_required' => false,
                    'ranked_points' => [5, 4, 3, 2, 1],
                ]
            );
        }

        $juryCategories = [
            'DJ/DJKA/LIVE ACT ROKU HOUSE POLSKA',
            'DJ/DJKA/LIVE ACT ROKU TECHNO POLSKA',
            'DJ/DJKA/LIVE ACT ROKU BASS POLSKA',
            'DJ/DJKA/LIVE ACT ROKU ELEKTRONIKA POLSKA',
            'DJ/DJKA/LIVE ACT ROKU EDM',
            'PRODUCENT / PRODUCENTKA ROKU',
            'ODKRYCIE ROKU POLSKA',
        ];

        foreach ($juryCategories as $i => $title) {
            Question::firstOrCreate(
                ['edition_id' => $edition->id, 'audience' => FormAudience::Jury, 'order' => $i + 1],
                [
                    'field_type' => QuestionFieldType::RankedText5,
                    'title' => $title,
                    'description' => 'Wskaż 5 artystów/artystki, punktacja automatyczna od 5 do 1 punktu.',
                    'is_required' => false,
                    'ranked_points' => [5, 4, 3, 2, 1],
                ]
            );
        }
    }
}
