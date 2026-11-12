<?php

namespace App\Filament\App\Resources\Breeds;

use App\Filament\App\Resources\Breeds\Pages\ManageBreeds;
use App\Models\Breed;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

/**
 * Breeds available to the current breeder: all global breeds (read-only) plus
 * the breeder's own custom breeds. Authorization lives in BreedPolicy.
 */
class BreedResource extends Resource
{
    protected static ?string $model = Breed::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('Breed');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Breeds');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Reference data');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->availableTo(Filament::getTenant());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('species_id')
                    ->label(__('Species'))
                    ->relationship('species', 'name')
                    ->required(),
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                            ->where('species_id', $get('species_id'))
                            ->where('breeder_id', Filament::getTenant()?->getKey()),
                    ),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('species.name')
                    ->label(__('Species'))
                    ->sortable(),
                TextColumn::make('breeder_id')
                    ->label(__('Source'))
                    ->badge()
                    ->formatStateUsing(fn (?int $state): string => $state === null ? __('Global') : __('Custom'))
                    ->color(fn (?int $state): string => $state === null ? 'gray' : 'primary'),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('species_id')
                    ->label(__('Species'))
                    ->relationship('species', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBreeds::route('/'),
        ];
    }
}
