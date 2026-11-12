<?php

use App\Models\Animal;
use App\Models\AnimalPhoto;
use App\Models\Breeder;
use App\Models\Litter;
use App\Models\Species;
use App\Support\PedigreeGraph;
use App\Support\PedigreeTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Family used by the tests:
 *
 *   dam × sire ─ A (2023) ─▶ daughter
 *   other dam × sire ─ B (2024) ─▶ kitten
 *   daughter × sire ─ C (2025) ─▶ grandchild
 */
beforeEach(function () {
    $this->breeder = Breeder::factory()->create(['slug' => 'nordwald']);
    $this->species = Species::factory()->create();

    $make = fn (string $name, string $sex, array $extra = []) => Animal::factory()->{$sex}()->create([
        'breeder_id' => $this->breeder->id,
        'species_id' => $this->species->id,
        'name' => $name,
        ...$extra,
    ]);
    $litter = fn (string $letter, Animal $mother, string $bornOn) => Litter::factory()->create([
        'breeder_id' => $this->breeder->id, 'species_id' => $this->species->id, 'letter' => $letter,
        'mother_id' => $mother->id, 'father_id' => $this->sire->id, 'born_on' => $bornOn,
    ]);

    $this->dam = $make('Dam', 'female', ['notes' => 'Private note']);
    $this->otherDam = $make('Other Dam', 'female');
    $this->sire = $make('Sire', 'male');

    $this->a = $litter('A', $this->dam, '2023-04-01');
    $this->daughter = $make('Daughter', 'female', ['litter_id' => $this->a->id]);
    $this->b = $litter('B', $this->otherDam, '2024-04-01');
    $this->kitten = $make('Kitten', 'male', ['litter_id' => $this->b->id]);
    $this->c = $litter('C', $this->daughter, '2025-04-01');
    $this->grandchild = $make('Grandchild', 'female', ['litter_id' => $this->c->id]);

    $this->timeline = new PedigreeTimeline(PedigreeGraph::forBreeder($this->breeder, $this->species->id));
});

it('places every daughter lane directly below her mother', function () {
    expect($this->timeline->familyLanes())->toBe([$this->dam->id, $this->daughter->id, $this->otherDam->id]);
});

it('exposes animals and litters without private notes', function () {
    AnimalPhoto::factory()->create(['animal_id' => $this->dam->id, 'path' => 'animal-photos/1/dam.webp']);

    $data = (new PedigreeTimeline(PedigreeGraph::forBreeder($this->breeder, $this->species->id)))->toArray();
    $dam = $data['animals'][$this->dam->id];

    expect($dam)->not->toHaveKey('notes')
        ->and(json_encode($data))->not->toContain('Private note')
        ->and($dam['photos'])->toBe([asset('storage/animal-photos/1/dam.webp')])
        ->and(collect($data['litters'])->firstWhere('id', $this->c->id))->toMatchArray([
            'letter' => 'C',
            'date' => '2025-04-01',
            'motherId' => $this->daughter->id,
            'offspring' => [$this->grandchild->id],
        ])
        ->and($data['relations'][$this->grandchild->id]['a'])->toContain($this->dam->id);
});

it('summarizes the pedigree for the page header', function () {
    expect($this->timeline->stats())->toMatchArray([
        'animals' => 6,
        'litters' => 3,
        'dams' => 3,
        'since' => 2023,
    ]);
});

it('shows the pedigree of the breeder on the public page', function () {
    $this->get('/breeders/nordwald')
        ->assertSuccessful()
        ->assertSee($this->breeder->name)
        ->assertSee('data-pedigree-data', false)
        ->assertSee('Grandchild')
        ->assertDontSee('Private note');
});

it('does not show animals of other breeders', function () {
    Animal::factory()->create(['species_id' => $this->species->id, 'name' => 'Foreign Cat']);

    $this->get('/breeders/nordwald')->assertDontSee('Foreign Cat');
});

it('switches between species and falls back to the first one', function () {
    $this->species->update(['name' => 'Cats']);
    $dogs = Species::factory()->create(['name' => 'Dogs']);
    Litter::factory()->create([
        'breeder_id' => $this->breeder->id,
        'species_id' => $dogs->id,
        'mother_id' => Animal::factory()->female()->create(['breeder_id' => $this->breeder->id, 'species_id' => $dogs->id, 'name' => 'Dog Dam'])->id,
    ]);

    $this->get("/breeders/nordwald?species={$dogs->id}")
        ->assertSee('Dog Dam')
        ->assertDontSee('Grandchild');

    $this->get('/breeders/nordwald?species=999999')
        ->assertSee('Grandchild')
        ->assertDontSee('Dog Dam');
});

it('shows a hint when the breeder has no litters yet', function () {
    $empty = Breeder::factory()->create(['slug' => 'empty']);

    $this->get('/breeders/empty')
        ->assertSuccessful()
        ->assertSee('This breeder has not recorded any litters yet.')
        ->assertDontSee('data-pedigree-data', false);
});
