<?php

namespace App\Filament\App\Resources\Animals\Pages;

use App\Filament\App\Resources\Animals\AnimalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnimal extends CreateRecord
{
    protected static string $resource = AnimalResource::class;
}
