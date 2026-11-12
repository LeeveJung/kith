<?php

namespace App\Filament\App\Resources\Litters\RelationManagers;

use App\Enums\AnimalOrigin;
use App\Enums\Sex;
use App\Filament\App\Resources\Animals\Schemas\AnimalForm;
use App\Models\Litter;
use App\Support\PedigreeRules;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Offspring of a litter. New animals inherit breeder, species, origin and
 * birth date from the litter so breeders only enter what differs per animal.
 */
class OffspringRelationManager extends RelationManager
{
    protected static string $relationship = 'offspring';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Offspring');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->getAttribute('breeder_id') === Filament::getTenant()?->getKey();
    }

    public function form(Schema $schema): Schema
    {
        /** @var Litter $litter */
        $litter = $this->getOwnerRecord();

        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('call_name')
                    ->label(__('Call name'))
                    ->maxLength(255),
                Select::make('sex')
                    ->label(__('Sex'))
                    ->options(Sex::class)
                    ->default(Sex::Unknown)
                    ->required(),
                Select::make('color_id')
                    ->label(__('Color'))
                    ->relationship(
                        'color',
                        'name',
                        modifyQueryUsing: fn (Builder $query) => $query
                            ->availableTo($litter->breeder)
                            ->where('species_id', $litter->species_id),
                    )
                    ->rules([PedigreeRules::color($litter->breeder_id, $litter->species_id)])
                    ->searchable()
                    ->preload(),
                Select::make('breed_id')
                    ->label(__('Breed'))
                    ->relationship(
                        'breed',
                        'name',
                        modifyQueryUsing: fn (Builder $query) => $query
                            ->availableTo($litter->breeder)
                            ->where('species_id', $litter->species_id),
                    )
                    ->rules([PedigreeRules::breed($litter->breeder_id, $litter->species_id)])
                    ->searchable()
                    ->preload(),
                Textarea::make('notes')
                    ->label(__('Notes'))
                    ->rows(3)
                    ->columnSpanFull(),
                Section::make(__('Photos'))
                    ->columnSpanFull()
                    ->schema([
                        AnimalForm::photos(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable(),
                TextColumn::make('sex')
                    ->label(__('Sex'))
                    ->badge(),
                TextColumn::make('color.name')
                    ->label(__('Color')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        /** @var Litter $litter */
                        $litter = $this->getOwnerRecord();

                        return [
                            ...$data,
                            'breeder_id' => $litter->breeder_id,
                            'species_id' => $litter->species_id,
                            'origin' => AnimalOrigin::Bred,
                            'born_on' => $litter->born_on,
                            'born_on_precision' => $litter->born_on_precision,
                        ];
                    }),
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
}
