<?php

namespace App\Filament\App\Resources\Animals\Tables;

use App\Enums\AnimalOrigin;
use App\Enums\DatePrecision;
use App\Enums\Sex;
use App\Models\Animal;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AnimalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->description(fn (Animal $record): ?string => $record->call_name ? "“{$record->call_name}”" : null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('species.name')
                    ->label(__('Species'))
                    ->sortable(),
                TextColumn::make('breed.name')
                    ->label(__('Breed'))
                    ->toggleable(),
                TextColumn::make('color.name')
                    ->label(__('Color'))
                    ->toggleable(),
                TextColumn::make('sex')
                    ->label(__('Sex'))
                    ->badge(),
                TextColumn::make('origin')
                    ->label(__('Origin'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('born_on')
                    ->label(__('Born'))
                    ->state(fn (Animal $record): string => DatePrecision::describe($record->born_on, $record->born_on_precision))
                    ->sortable(),
                TextColumn::make('litter.letter')
                    ->label(__('Litter'))
                    ->toggleable(),
                TextColumn::make('mother.name')
                    ->label(__('Mother'))
                    ->toggleable(),
                TextColumn::make('father.name')
                    ->label(__('Father'))
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('species_id')
                    ->label(__('Species'))
                    ->relationship('species', 'name'),
                SelectFilter::make('sex')
                    ->label(__('Sex'))
                    ->options(Sex::class),
                SelectFilter::make('origin')
                    ->label(__('Origin'))
                    ->options(AnimalOrigin::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
