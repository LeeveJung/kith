<?php

namespace App\Filament\App\Resources\Colors\Pages;

use App\Filament\App\Resources\Colors\ColorResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;

class ManageColors extends ManageRecords
{
    protected static string $resource = ColorResource::class;

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
