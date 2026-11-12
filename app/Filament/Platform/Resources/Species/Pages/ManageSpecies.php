<?php

namespace App\Filament\Platform\Resources\Species\Pages;

use App\Filament\Platform\Resources\Species\SpeciesResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSpecies extends ManageRecords
{
    protected static string $resource = SpeciesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
