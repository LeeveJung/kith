<?php

namespace App\Filament\App\Resources\Litters\Pages;

use App\Filament\App\Resources\Litters\LitterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLitters extends ListRecords
{
    protected static string $resource = LitterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
