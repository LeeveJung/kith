<?php

use App\Filament\App\Pages\Pedigree;
use App\Filament\App\Resources\Animals\AnimalResource;
use App\Filament\App\Resources\Litters\LitterResource;
use App\Models\Animal;
use App\Models\Breeder;
use App\Models\Litter;
use App\Models\Species;
use App\Models\User;
use App\Support\PedigreeGraph;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * Family used by the tests:
 *
 *   dam × sire ─ L1 ─▶ daughter, son
 *   daughter × son ─ L2 ─▶ grandchild        (related pairing)
 *   dam × other ─ L3 (no date) ─▶ half
 */
beforeEach(function () {
    $this->breeder = Breeder::factory()->create();
    $this->species = Species::factory()->create();

    $make = fn (string $name, string $sex, array $extra = []) => Animal::factory()->{$sex}()->create([
        'breeder_id' => $this->breeder->id,
        'species_id' => $this->species->id,
        'name' => $name,
        ...$extra,
    ]);

    $this->dam = $make('Dam', 'female');
    $this->sire = $make('Sire', 'male');
    $this->other = $make('Other', 'male');

    $this->l1 = Litter::factory()->create([
        'breeder_id' => $this->breeder->id, 'species_id' => $this->species->id, 'letter' => 'A',
        'mother_id' => $this->dam->id, 'father_id' => $this->sire->id, 'born_on' => '2023-04-01',
    ]);
    $this->daughter = $make('Daughter', 'female', ['litter_id' => $this->l1->id]);
    $this->son = $make('Son', 'male', ['litter_id' => $this->l1->id]);

    $this->l2 = Litter::factory()->create([
        'breeder_id' => $this->breeder->id, 'species_id' => $this->species->id, 'letter' => 'B',
        'mother_id' => $this->daughter->id, 'father_id' => $this->son->id, 'born_on' => '2024-05-01',
    ]);
    $this->grandchild = $make('Grandchild', 'female', ['litter_id' => $this->l2->id]);

    $this->l3 = Litter::factory()->create([
        'breeder_id' => $this->breeder->id, 'species_id' => $this->species->id, 'letter' => 'C',
        'mother_id' => $this->dam->id, 'father_id' => $this->other->id, 'born_on' => null,
    ]);
    $this->half = $make('Half', 'male', ['litter_id' => $this->l3->id]);

    $this->graph = PedigreeGraph::forBreeder($this->breeder, $this->species->id);
});

it('finds ancestors with their generation distance', function () {
    expect($this->graph->ancestorsOf($this->grandchild->id))->toEqual([
        $this->daughter->id => 1,
        $this->son->id => 1,
        $this->dam->id => 2,
        $this->sire->id => 2,
    ])->and($this->graph->ancestorsOf($this->dam->id))->toBe([]);
});

it('finds descendants with their generation distance', function () {
    $descendants = $this->graph->descendantsOf($this->dam->id);

    expect($descendants)->toHaveCount(4)
        ->and($descendants[$this->daughter->id])->toBe(1)
        ->and($descendants[$this->half->id])->toBe(1)
        ->and($descendants[$this->grandchild->id])->toBe(2)
        ->and($this->graph->descendantsOf($this->grandchild->id))->toBe([]);
});

it('exposes ancestors and descendants of every animal for highlighting', function () {
    $relations = $this->graph->relations();

    expect($relations)->toHaveCount(7)
        ->and($relations[$this->grandchild->id]['a'])->toContain($this->dam->id, $this->sire->id)
        ->and($relations[$this->dam->id]['d'])->toContain($this->grandchild->id);
});

it('detects pairings between related animals', function () {
    expect($this->graph->isRelatedPairing($this->l2))->toBeTrue()
        ->and($this->graph->isRelatedPairing($this->l1))->toBeFalse()
        ->and($this->graph->isRelatedPairing($this->l3))->toBeFalse();
});

it('orders litters chronologically and places undated ones after their parents litters', function () {
    expect($this->graph->orderedLitters()->pluck('letter')->all())->toBe(['C', 'A', 'B']);
});

it('groups litters into one lane per mother', function () {
    $lanes = $this->graph->lanes();

    expect($lanes)->toHaveCount(2)
        ->and($lanes[0]['queen']->is($this->dam))->toBeTrue()
        ->and($lanes[0]['litters']->pluck('letter')->all())->toBe(['C', 'A'])
        ->and($lanes[1]['queen']->is($this->daughter))->toBeTrue();
});

it('counts litters and offspring per sire', function () {
    $sires = collect($this->graph->sires())->keyBy(fn (array $sire) => $sire['animal']->name);

    expect($sires['Sire']['litters'])->toBe(1)
        ->and($sires['Sire']['offspring'])->toBe(2)
        ->and($sires['Son']['offspring'])->toBe(1)
        ->and($sires['Other']['offspring'])->toBe(1);
});

it('finds litter mates and own litters of an animal', function () {
    expect($this->graph->siblingsOf($this->daughter)->pluck('name')->all())->toBe(['Son'])
        ->and($this->graph->siblingsOf($this->dam))->toBeEmpty()
        ->and($this->graph->littersOf($this->dam)->pluck('letter')->all())->toBe(['C', 'A']);
});

it('renders the pedigree page for a breeder member with links to the edit pages', function () {
    $user = User::factory()->create();
    $user->breeders()->attach($this->breeder);
    $this->actingAs($user);
    Filament::setCurrentPanel('app');
    Filament::setTenant($this->breeder);
    Filament::bootCurrentPanel();

    Livewire::test(Pedigree::class)
        ->assertSuccessful()
        ->assertSet('speciesId', $this->species->id)
        ->assertSee('Dam')
        ->assertSee('Grandchild')
        ->assertSee('Common ancestors')
        ->assertSee(AnimalResource::getUrl('edit', ['record' => $this->grandchild]))
        ->assertSee(LitterResource::getUrl('edit', ['record' => $this->l2]));
});

it('does not show animals of other breeders', function () {
    $foreignBreeder = Breeder::factory()->create();
    Animal::withoutEvents(fn () => Litter::withoutEvents(fn () => Litter::factory()->create([
        'breeder_id' => $foreignBreeder->id,
        'species_id' => $this->species->id,
        'mother_id' => Animal::factory()->female()->create(['breeder_id' => $foreignBreeder->id, 'species_id' => $this->species->id, 'name' => 'Foreign Queen'])->id,
        'father_id' => Animal::factory()->male()->create(['breeder_id' => $foreignBreeder->id, 'species_id' => $this->species->id, 'name' => 'Foreign Sire'])->id,
    ])));

    $user = User::factory()->create();
    $user->breeders()->attach($this->breeder);
    $this->actingAs($user);
    Filament::setCurrentPanel('app');
    Filament::setTenant($this->breeder);
    Filament::bootCurrentPanel();

    Livewire::test(Pedigree::class)
        ->assertSee('Dam')
        ->assertDontSee('Foreign Queen')
        ->assertDontSee('Foreign Sire');
});

it('shows a hint when the breeder has no litters yet', function () {
    $user = User::factory()->create();
    $empty = Breeder::factory()->create();
    $user->breeders()->attach($empty);
    $this->actingAs($user);
    Filament::setCurrentPanel('app');
    Filament::setTenant($empty);
    Filament::bootCurrentPanel();

    Livewire::test(Pedigree::class)
        ->assertSuccessful()
        ->assertSee('There are no litters yet');
});
