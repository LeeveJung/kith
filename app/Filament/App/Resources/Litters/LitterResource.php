<?php

namespace App\Filament\App\Resources\Litters;

use App\Filament\App\Resources\Litters\Pages\CreateLitter;
use App\Filament\App\Resources\Litters\Pages\EditLitter;
use App\Filament\App\Resources\Litters\Pages\ListLitters;
use App\Filament\App\Resources\Litters\RelationManagers\OffspringRelationManager;
use App\Filament\App\Resources\Litters\Schemas\LitterForm;
use App\Filament\App\Resources\Litters\Tables\LittersTable;
use App\Models\Litter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class LitterResource extends Resource
{
    protected static ?string $model = Litter::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function getModelLabel(): string
    {
        return __('Litter');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Litters');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Breeding');
    }

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record ? __('Litter').' '.($record->letter ?? '#'.$record->getKey()) : null;
    }

    public static function form(Schema $schema): Schema
    {
        return LitterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LittersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            OffspringRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLitters::route('/'),
            'create' => CreateLitter::route('/create'),
            'edit' => EditLitter::route('/{record}/edit'),
        ];
    }
}
