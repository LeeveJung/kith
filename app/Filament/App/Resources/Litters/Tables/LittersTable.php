<?php

namespace App\Filament\App\Resources\Litters\Tables;

use App\Enums\DatePrecision;
use App\Models\Litter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LittersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('letter')
                    ->label(__('Litter'))
                    ->placeholder('–')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('species.name')
                    ->label(__('Species'))
                    ->sortable(),
                TextColumn::make('mother.name')
                    ->label(__('Mother'))
                    ->searchable(),
                TextColumn::make('father.name')
                    ->label(__('Father'))
                    ->searchable(),
                TextColumn::make('born_on')
                    ->label(__('Born'))
                    ->state(fn (Litter $record): string => DatePrecision::describe($record->born_on, $record->born_on_precision))
                    ->sortable(),
                TextColumn::make('offspring_count')
                    ->label(__('Offspring'))
                    ->counts('offspring'),
            ])
            ->defaultSort('born_on', 'desc')
            ->filters([
                SelectFilter::make('species_id')
                    ->label(__('Species'))
                    ->relationship('species', 'name'),
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
