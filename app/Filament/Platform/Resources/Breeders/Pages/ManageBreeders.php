<?php

namespace App\Filament\Platform\Resources\Breeders\Pages;

use App\Filament\Platform\Resources\Breeders\BreederResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBreeders extends ManageRecords
{
    protected static string $resource = BreederResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
