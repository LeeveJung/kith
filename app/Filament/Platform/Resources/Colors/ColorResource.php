<?php

namespace App\Filament\Platform\Resources\Colors;

use App\Filament\Platform\Resources\Colors\Pages\ManageColors;
use App\Models\Color;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

/**
 * Platform view of all colors: global ones and the custom colors of breeders.
 * Clearing the breeder promotes a custom color to a global one.
 */
class ColorResource extends Resource
{
    protected static ?string $model = Color::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('Color');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Colors');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Reference data');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('species_id')
                    ->label(__('Species'))
                    ->relationship('species', 'name')
                    ->required(),
                Select::make('breeder_id')
                    ->label(__('Breeder'))
                    ->relationship('breeder', 'name')
                    ->placeholder(__('Global (all breeders)'))
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                            ->where('species_id', $get('species_id'))
                            ->where('breeder_id', $get('breeder_id')),
                    ),
                ColorPicker::make('hex')
                    ->label(__('Color'))
                    ->required(),
                Toggle::make('is_dark')
                    ->label(__('Needs light text'))
                    ->helperText(__('Enable for dark colors so text on top stays readable.')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('hex')
                    ->label(__('Color')),
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('species.name')
                    ->label(__('Species'))
                    ->sortable(),
                TextColumn::make('breeder.name')
                    ->label(__('Breeder'))
                    ->placeholder(__('Global'))
                    ->sortable(),
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
            'index' => ManageColors::route('/'),
        ];
    }
}
