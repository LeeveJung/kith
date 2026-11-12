<?php

namespace App\Filament\App\Widgets;

use App\Enums\DatePrecision;
use App\Enums\Sex;
use App\Filament\App\Pages\Pedigree;
use App\Filament\App\Resources\Animals\AnimalResource;
use App\Filament\App\Resources\Litters\LitterResource;
use App\Models\Breeder;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Dashboard boxes for animals, litters and pedigree of the current breeder, each linking to its page.
 */
class BreedingOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -2;

    protected ?string $pollingInterval = null;

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        /** @var Breeder $breeder */
        $breeder = Filament::getTenant();

        return [
            $this->animalsStat($breeder),
            $this->littersStat($breeder),
            $this->pedigreeStat($breeder),
        ];
    }

    private function animalsStat(Breeder $breeder): Stat
    {
        $countsBySex = $breeder->animals()
            ->toBase()
            ->selectRaw('sex, count(*) as total')
            ->groupBy('sex')
            ->pluck('total', 'sex');

        return Stat::make(__('Animals'), $countsBySex->sum())
            ->description('♀ '.($countsBySex[Sex::Female->value] ?? 0).' · ♂ '.($countsBySex[Sex::Male->value] ?? 0))
            ->icon(Heroicon::OutlinedHeart)
            ->url(AnimalResource::getUrl());
    }

    private function littersStat(Breeder $breeder): Stat
    {
        $latestLitter = $breeder->litters()->whereNotNull('born_on')->latest('born_on')->first();

        return Stat::make(__('Litters'), $breeder->litters()->count())
            ->description($latestLitter === null
                ? __('No dated litter yet')
                : __('Latest: :litter · :date', [
                    'litter' => __('Litter :letter', ['letter' => $latestLitter->letter ?? '#'.$latestLitter->id]),
                    'date' => DatePrecision::describe($latestLitter->born_on, $latestLitter->born_on_precision),
                ]))
            ->icon(Heroicon::OutlinedUserGroup)
            ->url(LitterResource::getUrl());
    }

    private function pedigreeStat(Breeder $breeder): Stat
    {
        $dams = $breeder->litters()->distinct()->count('mother_id');
        $sires = $breeder->litters()->distinct()->count('father_id');

        return Stat::make(__('Pedigree'), $dams + $sires)
            ->description(__('Breeding animals: :dams · :sires', ['dams' => '♀ '.$dams, 'sires' => '♂ '.$sires]))
            ->icon(Heroicon::OutlinedShare)
            ->url(Pedigree::getUrl());
    }
}
