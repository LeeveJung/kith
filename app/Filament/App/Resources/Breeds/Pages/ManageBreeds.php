<?php

namespace App\Filament\App\Resources\Breeds\Pages;

use App\Filament\App\Resources\Breeds\BreedResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;

class ManageBreeds extends ManageRecords
{
    protected static string $resource = BreedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(fn (array $data): array => [
                    ...$data,
                    'breeder_id' => Filament::getTenant()->getKey(),
                ]),
        ];
    }
}
