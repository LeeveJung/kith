<?php

use App\Enums\AnimalOrigin;
use App\Enums\DatePrecision;
use App\Enums\Sex;
use App\Models\Animal;
use App\Models\AnimalPhoto;
use App\Models\Breed;
use App\Models\Breeder;
use App\Models\Color;
use App\Models\Litter;
use App\Models\Species;
use App\Models\User;
use Database\Seeders\ColorSeeder;
use Database\Seeders\DemoBreederSeeder;
use Database\Seeders\SpeciesSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('derives mother and father of an offspring from its litter', function () {
    $litter = Litter::factory()->create(['letter' => 'A']);
    $kitten = Animal::factory()->inLitter($litter)->create();

    expect($kitten->mother->is($litter->mother))->toBeTrue()
        ->and($kitten->father->is($litter->father))->toBeTrue()
        ->and($kitten->origin)->toBe(AnimalOrigin::Bred)
        ->and($kitten->species_id)->toBe($litter->species_id)
        ->and($litter->offspring)->toHaveCount(1);
});

it('has no parents for foundation and external animals', function () {
    $animal = Animal::factory()->external()->create();

    expect($animal->mother)->toBeNull()
        ->and($animal->father)->toBeNull()
        ->and($animal->origin)->toBe(AnimalOrigin::External);
});

it('lists litters of an animal as mother and as sire', function () {
    $litter = Litter::factory()->create();

    expect($litter->mother->littersAsMother)->toHaveCount(1)
        ->and($litter->father->littersAsFather)->toHaveCount(1)
        ->and($litter->mother->littersAsFather)->toBeEmpty();
});

it('keeps litter letters unique per breeder and species', function () {
    $litter = Litter::factory()->create(['letter' => 'A']);

    Litter::factory()->create(['letter' => 'A']);
    Litter::factory()->create(['breeder_id' => $litter->breeder_id, 'letter' => 'A']);

    expect(fn () => Litter::factory()->create([
        'breeder_id' => $litter->breeder_id,
        'species_id' => $litter->species_id,
        'letter' => 'A',
    ]))->toThrow(QueryException::class);
});

it('allows several litters without a letter', function () {
    $litter = Litter::factory()->create(['letter' => null]);

    Litter::factory()->create([
        'breeder_id' => $litter->breeder_id,
        'species_id' => $litter->species_id,
        'letter' => null,
    ]);

    expect(Litter::count())->toBe(2);
});

it('prevents deleting an animal that is a parent of a litter', function () {
    $litter = Litter::factory()->create();

    expect(fn () => $litter->mother->delete())->toThrow(QueryException::class);
});

it('prevents deleting a species that is in use', function () {
    $animal = Animal::factory()->create();

    expect(fn () => $animal->species->delete())->toThrow(QueryException::class);
});

it('detaches offspring from a deleted litter instead of deleting them', function () {
    $litter = Litter::factory()->create();
    $kitten = Animal::factory()->inLitter($litter)->create();

    $litter->delete();

    expect($kitten->fresh()->litter_id)->toBeNull();
});

it('casts sex, origin and date precision', function () {
    $litter = Litter::factory()->create(['born_on' => '2023-04-01', 'born_on_precision' => DatePrecision::Month]);
    $animal = Animal::factory()->inLitter($litter)->create(['sex' => Sex::Unknown]);

    expect($animal->fresh())
        ->sex->toBe(Sex::Unknown)
        ->born_on->toDateString()->toBe('2023-04-01')
        ->born_on_precision->toBe(DatePrecision::Month);
});

it('describes dates according to their precision', function () {
    $date = now()->setDate(2023, 4, 17);

    expect(DatePrecision::describe($date, DatePrecision::Month))->toBe('April 2023')
        ->and(DatePrecision::describe($date, DatePrecision::Year))->toBe('2023')
        ->and(DatePrecision::describe($date, DatePrecision::Day))->toBe('2023-04-17')
        ->and(DatePrecision::describe(null, null))->toBe('Date unknown');
});

it('orders photos by position', function () {
    $animal = Animal::factory()->create();
    AnimalPhoto::factory()->for($animal)->create(['position' => 1, 'path' => 'second.png']);
    AnimalPhoto::factory()->for($animal)->create(['position' => 0, 'path' => 'first.png']);

    expect($animal->photos->pluck('path')->all())->toBe(['first.png', 'second.png']);
});

it('scopes colors and breeds to a species', function () {
    $cats = Species::factory()->create(['name' => 'Cat']);
    $horses = Species::factory()->create(['name' => 'Horse']);

    Color::factory()->for($cats)->create(['name' => 'Black']);
    Color::factory()->for($horses)->create(['name' => 'Black']);

    expect($cats->colors)->toHaveCount(1)
        ->and($horses->colors)->toHaveCount(1);
});

it('makes global and own custom breeds and colors available to a breeder', function () {
    $species = Species::factory()->create();
    $breeder = Breeder::factory()->create();
    $other = Breeder::factory()->create();

    $global = Breed::factory()->for($species)->create();
    $own = Breed::factory()->for($species)->create(['breeder_id' => $breeder->id]);
    $foreign = Breed::factory()->for($species)->create(['breeder_id' => $other->id]);
    $ownColor = Color::factory()->for($species)->create(['breeder_id' => $breeder->id]);
    $foreignColor = Color::factory()->for($species)->create(['breeder_id' => $other->id]);

    $available = Breed::availableTo($breeder)->pluck('id');

    expect($available->all())->toEqualCanonicalizing([$global->id, $own->id])
        ->and($available)->not->toContain($foreign->id)
        ->and(Color::availableTo($breeder)->pluck('id')->all())->toBe([$ownColor->id])
        ->and(Color::availableTo($breeder)->pluck('id'))->not->toContain($foreignColor->id)
        ->and($global->isGlobal())->toBeTrue()
        ->and($own->isGlobal())->toBeFalse()
        ->and(Breed::availableTo(null)->pluck('id')->all())->toBe([$global->id]);
});

it('assigns users to breeders', function () {
    $user = User::factory()->create();
    $breeder = Breeder::factory()->create();

    $user->breeders()->attach($breeder);

    expect($breeder->users->first()->is($user))->toBeTrue();
});

it('seeds the design export for Nordwald Coons', function () {
    Storage::fake('public');
    User::factory()->create(['email' => 'test@example.com']);

    $this->seed([SpeciesSeeder::class, ColorSeeder::class, DemoBreederSeeder::class]);

    $breeder = Breeder::firstWhere('slug', 'nordwald-coons');

    expect(Color::count())->toBe(18)
        ->and($breeder->litters)->toHaveCount(11)
        ->and($breeder->animals)->toHaveCount(45)
        ->and($breeder->users)->toHaveCount(1);

    $ada = $breeder->animals()->firstWhere('name', 'Ada');
    $freya = $breeder->animals()->firstWhere('name', 'Freya');

    expect($ada->mother->is($freya))->toBeTrue()
        ->and($ada->father->name)->toBe('Azlan')
        ->and($ada->species->name)->toBe('Cat')
        ->and($ada->breed->name)->toBe('Maine Coon')
        ->and($ada->color->name)->toBe('Cream')
        ->and($ada->littersAsMother()->pluck('letter')->sort()->values()->all())->toBe(['F', 'H'])
        ->and($breeder->animals()->firstWhere('name', 'Galileo')->call_name)->toBe('Frankie')
        ->and($breeder->litters()->firstWhere('letter', 'J')->offspring)->toHaveCount(4);
});
