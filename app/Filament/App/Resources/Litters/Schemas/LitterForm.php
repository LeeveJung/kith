<?php

namespace App\Filament\App\Resources\Litters\Schemas;

use App\Enums\DatePrecision;
use App\Enums\Sex;
use App\Support\PedigreeRules;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class LitterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('Mating'))
                    ->columns(2)
                    ->schema([
                        Select::make('species_id')
                            ->label(__('Species'))
                            ->relationship('species', 'name')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('mother_id', null);
                                $set('father_id', null);
                            })
                            ->disabledOn('edit'),
                        TextInput::make('letter')
                            ->label(__('Litter letter'))
                            ->helperText(__('Optional, unique per species.'))
                            ->maxLength(8)
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                                    ->where('breeder_id', Filament::getTenant()?->getKey())
                                    ->where('species_id', $get('species_id')),
                            ),
                        Select::make('mother_id')
                            ->label(__('Mother'))
                            ->relationship(
                                'mother',
                                'name',
                                modifyQueryUsing: fn (Builder $query, Get $get) => $query
                                    ->where('species_id', $get('species_id'))
                                    ->where('sex', Sex::Female),
                            )
                            ->rules(fn (Get $get): array => [PedigreeRules::parent(Filament::getTenant()?->getKey(), $get('species_id'), Sex::Female)])
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('father_id')
                            ->label(__('Father'))
                            ->relationship(
                                'father',
                                'name',
                                modifyQueryUsing: fn (Builder $query, Get $get) => $query
                                    ->where('species_id', $get('species_id'))
                                    ->where('sex', Sex::Male),
                            )
                            ->rules(fn (Get $get): array => [PedigreeRules::parent(Filament::getTenant()?->getKey(), $get('species_id'), Sex::Male)])
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('born_on')
                            ->label(__('Birth date')),
                        Select::make('born_on_precision')
                            ->label(__('Precision'))
                            ->options(DatePrecision::class)
                            ->helperText(__('How precisely the date is known (e.g. month only).')),
                    ]),
                Section::make(__('Notes'))
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes')
                            ->hiddenLabel()
                            ->rows(4),
                    ]),
            ]);
    }
}
