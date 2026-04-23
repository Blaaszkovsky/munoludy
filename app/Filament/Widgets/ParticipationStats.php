<?php

namespace App\Filament\Widgets;

use App\Models\Edition;
use App\Models\Participant;
use Filament\Widgets\StatsOverviewWidget as Base;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ParticipationStats extends Base
{
    protected function getStats(): array
    {
        $edition = Edition::active();

        if (! $edition) {
            return [
                Stat::make('Zarejestrowani', 0),
                Stat::make('Zagłosowali', 0),
                Stat::make('Jury', 0),
            ];
        }

        $total = Participant::where('edition_id', $edition->id)->count();
        $voted = Participant::where('edition_id', $edition->id)
            ->whereNotNull('voted_at')
            ->count();
        $jury = Participant::where('edition_id', $edition->id)
            ->where('type', 'jury')
            ->count();

        return [
            Stat::make('Zarejestrowani', $total)
                ->description('Łączna liczba uczestników w aktywnej edycji')
                ->color('primary'),
            Stat::make('Zagłosowali', $voted)
                ->description($total > 0 ? round(($voted / $total) * 100, 1) . '% uczestników' : null)
                ->color('success'),
            Stat::make('Jury', $jury)
                ->description('Członkowie jury')
                ->color('warning'),
        ];
    }
}
