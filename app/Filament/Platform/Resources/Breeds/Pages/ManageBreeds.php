<?php

namespace App\Filament\Platform\Resources\Breeds\Pages;

use App\Filament\Platform\Resources\Breeds\BreedResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBreeds extends ManageRecords
{
    protected static string $resource = BreedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
