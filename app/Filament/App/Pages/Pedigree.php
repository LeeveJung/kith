<?php

namespace App\Filament\App\Pages;

use App\Support\PedigreeGraph;
use BackedEnum;
use Filament\Actions\Action;
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

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label(__('Preview in frontend'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (): string => route('breeders.show', Filament::getTenant()))
                ->openUrlInNewTab(),
        ];
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
        return Filament::getTenant()->speciesWithLitters();
    }

    #[Computed]
    public function graph(): PedigreeGraph
    {
        return PedigreeGraph::forBreeder(Filament::getTenant(), $this->speciesId);
    }
}
