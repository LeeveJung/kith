<?php

namespace App\Filament\App\Resources\Litters\Pages;

use App\Filament\App\Resources\Litters\LitterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLitter extends EditRecord
{
    protected static string $resource = LitterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
