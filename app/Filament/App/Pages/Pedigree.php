<?php

namespace App\Filament\App\Pages;

use App\Models\Species;
use App\Support\PedigreeGraph;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * Simple pedigree overview for breeders: one section per breeding mother with her litters,
 * every animal and litter links to its edit page. The interactive view is planned for the frontend.
 */
class Pedigree extends Page
{
    protected string $view = 'filament.app.pages.pedigree';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShare;

    protected static ?int $navigationSort = 1;

    #[Url]
    public ?int $speciesId = null;

    public static function getNavigationLabel(): string
    {
        return __('Pedigree');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Breeding');
    }

    public function getTitle(): string|Htmlable
    {
        return __('Pedigree');
    }

    public function mount(): void
    {
        $this->speciesId ??= array_key_first($this->speciesOptions);
    }

    /**
     * Species of the current breeder that have litters.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function speciesOptions(): array
    {
        return Species::query()
            ->whereIn('id', Filament::getTenant()->litters()->select('species_id'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    #[Computed]
    public function graph(): PedigreeGraph
    {
        return PedigreeGraph::forBreeder(Filament::getTenant(), $this->speciesId);
    }
}
