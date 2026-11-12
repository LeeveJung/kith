<?php

namespace App\Filament\App\Resources\Litters\Pages;

use App\Filament\App\Resources\Litters\LitterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLitter extends CreateRecord
{
    protected static string $resource = LitterResource::class;
}
