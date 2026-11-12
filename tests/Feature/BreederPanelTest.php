<?php

use App\Enums\AnimalOrigin;
use App\Enums\Sex;
use App\Filament\App\Resources\Animals\Pages\CreateAnimal;
use App\Filament\App\Resources\Animals\Pages\ListAnimals;
use App\Filament\App\Resources\Breeds\Pages\ManageBreeds;
use App\Filament\App\Resources\Colors\Pages\ManageColors;
use App\Filament\App\Resources\Litters\Pages\CreateLitter;
use App\Filament\App\Resources\Litters\Pages\EditLitter;
use App\Filament\App\Resources\Litters\Pages\ListLitters;
use App\Filament\App\Resources\Litters\RelationManagers\OffspringRelationManager;
use App\Models\Animal;
use App\Models\Breed;
use App\Models\Breeder;
use App\Models\Color;
use App\Models\Litter;
use App\Models\Species;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->breeder = Breeder::factory()->create();
    $this->user = User::factory()->create();
    $this->user->breeders()->attach($this->breeder);
    $this->species = Species::factory()->create();

    $this->actingAs($this->user);
    Filament::setCurrentPanel('app');
    Filament::setTenant($this->breeder);
    Filament::bootCurrentPanel();
});

it('lets a member open the animal and litter lists of their breeder', function () {
    $litter = Litter::factory()->create(['breeder_id' => $this->breeder->id]);

    Livewire::test(ListAnimals::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(Animal::all());

    Livewire::test(ListLitters::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$litter]);
});

it('does not list animals of other breeders', function () {
    $own = Animal::factory()->create(['breeder_id' => $this->breeder->id]);
    $foreign = Animal::withoutEvents(fn () => Animal::factory()->create());

    Livewire::test(ListAnimals::class)
        ->assertCanSeeTableRecords([$own])
        ->assertCanNotSeeTableRecords([$foreign]);
});

it('assigns new animals to the current breeder', function () {
    Livewire::test(CreateAnimal::class)
        ->fillForm([
            'species_id' => $this->species->id,
            'name' => 'Luna',
            'sex' => Sex::Female,
            'origin' => AnimalOrigin::Foundation,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Animal::firstWhere('name', 'Luna'))
        ->breeder_id->toBe($this->breeder->id)
        ->species_id->toBe($this->species->id);
});

it('stores uploaded photos as webp scaled down to at most 1000px width', function (int $width, int $expectedWidth) {
    Storage::fake('public');
    $undoRepeaterFake = Repeater::fake();

    Livewire::test(CreateAnimal::class)
        ->fillForm([
            'species_id' => $this->species->id,
            'name' => 'Luna',
            'sex' => Sex::Female,
            'origin' => AnimalOrigin::Foundation,
            'photos' => [['path' => [UploadedFile::fake()->image('luna.png', $width, $width / 2)], 'caption' => null]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    $path = Animal::firstWhere('name', 'Luna')->photos->sole()->path;
    $size = getimagesizefromstring(Storage::disk('public')->get($path));

    expect($path)->toStartWith("animal-photos/{$this->breeder->id}/")
        ->toEndWith('.webp')
        ->not->toContain('luna')
        ->and($size['mime'])->toBe('image/webp')
        ->and($size[0])->toBe($expectedWidth)
        ->and($size[1])->toBe($expectedWidth / 2);
})->with([
    'larger image' => [1600, 1000],
    'smaller image' => [600, 600],
]);

it('allows at most five photos per animal', function () {
    Storage::fake('public');
    $undoRepeaterFake = Repeater::fake();

    Livewire::test(CreateAnimal::class)
        ->fillForm([
            'species_id' => $this->species->id,
            'name' => 'Luna',
            'sex' => Sex::Female,
            'origin' => AnimalOrigin::Foundation,
            'photos' => collect(range(1, 6))
                ->map(fn (int $number): array => ['path' => [UploadedFile::fake()->image("luna-{$number}.png")], 'caption' => null])
                ->all(),
        ])
        ->call('create')
        ->assertHasFormErrors(['photos' => 'max']);

    $undoRepeaterFake();

    expect(Animal::firstWhere('name', 'Luna'))->toBeNull();
});

it('requires a litter for animals from own breeding', function () {
    Livewire::test(CreateAnimal::class)
        ->fillForm([
            'species_id' => $this->species->id,
            'name' => 'Kitten',
            'sex' => Sex::Female,
            'origin' => AnimalOrigin::Bred,
        ])
        ->call('create')
        ->assertHasFormErrors(['litter_id' => 'required']);
});

it('rejects breeds and colors of other breeders even if the ids are submitted directly', function () {
    $foreignBreed = Breed::factory()->for($this->species)->create(['breeder_id' => Breeder::factory()->create()->id]);
    $foreignColor = Color::factory()->for($this->species)->create(['breeder_id' => Breeder::factory()->create()->id]);

    Livewire::test(CreateAnimal::class)
        ->fillForm([
            'species_id' => $this->species->id,
            'name' => 'Luna',
            'sex' => Sex::Female,
            'origin' => AnimalOrigin::Foundation,
            'breed_id' => $foreignBreed->id,
            'color_id' => $foreignColor->id,
        ])
        ->call('create')
        ->assertHasFormErrors(['breed_id', 'color_id']);

    expect(Animal::where('name', 'Luna')->exists())->toBeFalse();
});

it('accepts global and own custom breeds and colors for an animal', function () {
    $global = Breed::factory()->for($this->species)->create();
    $own = Color::factory()->for($this->species)->create(['breeder_id' => $this->breeder->id]);

    Livewire::test(CreateAnimal::class)
        ->fillForm([
            'species_id' => $this->species->id,
            'name' => 'Luna',
            'sex' => Sex::Female,
            'origin' => AnimalOrigin::Foundation,
            'breed_id' => $global->id,
            'color_id' => $own->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Animal::firstWhere('name', 'Luna'))
        ->breed_id->toBe($global->id)
        ->color_id->toBe($own->id);
});

it('only offers female animals of the chosen species as mother', function () {
    $mare = Animal::factory()->female()->create(['breeder_id' => $this->breeder->id, 'species_id' => $this->species->id]);
    $stallion = Animal::factory()->male()->create(['breeder_id' => $this->breeder->id, 'species_id' => $this->species->id]);
    $foreignMare = Animal::withoutEvents(fn () => Animal::factory()->female()->create(['species_id' => $this->species->id]));

    Livewire::test(CreateLitter::class)
        ->fillForm(['species_id' => $this->species->id])
        ->assertFormFieldExists('mother_id', function ($field) use ($mare, $stallion, $foreignMare) {
            $options = $field->getOptions();

            return array_key_exists($mare->id, $options)
                && ! array_key_exists($stallion->id, $options)
                && ! array_key_exists($foreignMare->id, $options);
        });
});

it('rejects parents that are of the wrong sex or belong to another breeder', function () {
    $sire = Animal::factory()->male()->create(['breeder_id' => $this->breeder->id, 'species_id' => $this->species->id]);
    $otherMale = Animal::factory()->male()->create(['breeder_id' => $this->breeder->id, 'species_id' => $this->species->id]);
    $foreignMare = Animal::withoutEvents(fn () => Animal::factory()->female()->create(['species_id' => $this->species->id]));

    Livewire::test(CreateLitter::class)
        ->fillForm(['species_id' => $this->species->id, 'mother_id' => $otherMale->id, 'father_id' => $sire->id])
        ->call('create')
        ->assertHasFormErrors(['mother_id']);

    Livewire::test(CreateLitter::class)
        ->fillForm(['species_id' => $this->species->id, 'mother_id' => $foreignMare->id, 'father_id' => $sire->id])
        ->call('create')
        ->assertHasFormErrors(['mother_id']);

    expect(Litter::count())->toBe(0);
});

it('creates a litter for valid parents', function () {
    $mare = Animal::factory()->female()->create(['breeder_id' => $this->breeder->id, 'species_id' => $this->species->id]);
    $sire = Animal::factory()->male()->create(['breeder_id' => $this->breeder->id, 'species_id' => $this->species->id]);

    Livewire::test(CreateLitter::class)
        ->fillForm(['species_id' => $this->species->id, 'letter' => 'A', 'mother_id' => $mare->id, 'father_id' => $sire->id])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Litter::firstWhere('letter', 'A'))
        ->breeder_id->toBe($this->breeder->id)
        ->mother_id->toBe($mare->id);
});

it('adds offspring to a litter and inherits breeder, species, origin and birth date', function () {
    $litter = Litter::factory()->create([
        'breeder_id' => $this->breeder->id,
        'species_id' => $this->species->id,
        'born_on' => '2024-06-01',
    ]);

    Livewire::test(OffspringRelationManager::class, ['ownerRecord' => $litter, 'pageClass' => EditLitter::class])
        ->assertSuccessful()
        ->callAction(TestAction::make('create')->table(), data: ['name' => 'Misty', 'sex' => Sex::Female])
        ->assertHasNoFormErrors();

    expect(Animal::firstWhere('name', 'Misty'))
        ->litter_id->toBe($litter->id)
        ->breeder_id->toBe($this->breeder->id)
        ->species_id->toBe($this->species->id)
        ->origin->toBe(AnimalOrigin::Bred)
        ->born_on->toDateString()->toBe('2024-06-01');
});

it('adds photos when editing offspring of a litter', function () {
    Storage::fake('public');
    $litter = Litter::factory()->create(['breeder_id' => $this->breeder->id, 'species_id' => $this->species->id]);
    $kitten = Animal::factory()->create([
        'breeder_id' => $this->breeder->id,
        'species_id' => $this->species->id,
        'litter_id' => $litter->id,
        'origin' => AnimalOrigin::Bred,
    ]);
    $undoRepeaterFake = Repeater::fake();

    Livewire::test(OffspringRelationManager::class, ['ownerRecord' => $litter, 'pageClass' => EditLitter::class])
        ->callAction(TestAction::make('edit')->table($kitten), data: [
            'photos' => [['path' => [UploadedFile::fake()->image('kitten.png', 1200, 800)], 'caption' => null]],
        ])
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    $path = $kitten->photos()->sole()->path;

    expect($path)->toEndWith('.webp')
        ->and(getimagesizefromstring(Storage::disk('public')->get($path))[0])->toBe(1000);
});

it('lets breeders create their own breeds and colors', function () {
    Livewire::test(ManageBreeds::class)
        ->callAction('create', data: ['species_id' => $this->species->id, 'name' => 'Wild Coon'])
        ->assertHasNoFormErrors();

    Livewire::test(ManageColors::class)
        ->callAction('create', data: ['species_id' => $this->species->id, 'name' => 'Mocha', 'hex' => '#8a6540', 'is_dark' => true])
        ->assertHasNoFormErrors();

    expect(Breed::firstWhere('name', 'Wild Coon')->breeder_id)->toBe($this->breeder->id)
        ->and(Color::firstWhere('name', 'Mocha')->breeder_id)->toBe($this->breeder->id);
});

it('shows global entries read-only and hides the custom entries of other breeders', function () {
    $global = Breed::factory()->for($this->species)->create();
    $own = Breed::factory()->for($this->species)->create(['breeder_id' => $this->breeder->id]);
    $foreign = Breed::factory()->for($this->species)->create(['breeder_id' => Breeder::factory()->create()->id]);

    Livewire::test(ManageBreeds::class)
        ->assertCanSeeTableRecords([$global, $own])
        ->assertCanNotSeeTableRecords([$foreign])
        ->assertActionHidden(TestAction::make('edit')->table($global))
        ->assertActionHidden(TestAction::make('delete')->table($global))
        ->assertActionVisible(TestAction::make('edit')->table($own))
        ->assertActionVisible(TestAction::make('delete')->table($own));
});

it('does not let a breeder change global or foreign reference data through policies', function () {
    $global = Color::factory()->for($this->species)->create();
    $own = Color::factory()->for($this->species)->create(['breeder_id' => $this->breeder->id]);
    $foreign = Color::factory()->for($this->species)->create(['breeder_id' => Breeder::factory()->create()->id]);

    expect($this->user->can('update', $global))->toBeFalse()
        ->and($this->user->can('delete', $global))->toBeFalse()
        ->and($this->user->can('update', $foreign))->toBeFalse()
        ->and($this->user->can('view', $foreign))->toBeFalse()
        ->and($this->user->can('view', $global))->toBeTrue()
        ->and($this->user->can('update', $own))->toBeTrue()
        ->and($this->user->can('delete', $own))->toBeTrue();
});

it('forbids access to a breeder the user does not belong to', function () {
    $foreign = Breeder::factory()->create();

    $this->get("/app/{$foreign->slug}/animals")->assertNotFound();
});

it('lets the user reach their own breeder panel over http', function () {
    $this->get("/app/{$this->breeder->slug}/animals")->assertSuccessful();
});

it('renders every page of the breeder panel', function (string $path) {
    $this->get("/app/{$this->breeder->slug}/{$path}")->assertSuccessful();
})->with(['animals', 'animals/create', 'litters', 'litters/create', 'breeds', 'colors', 'pedigree']);

it('renders the edit pages of animals and litters including the offspring manager', function () {
    $litter = Litter::factory()->create(['breeder_id' => $this->breeder->id, 'species_id' => $this->species->id]);
    $kitten = Animal::factory()->inLitter($litter)->create();

    $this->get("/app/{$this->breeder->slug}/litters/{$litter->id}/edit")->assertSuccessful()->assertSee('Offspring');
    $this->get("/app/{$this->breeder->slug}/animals/{$kitten->id}/edit")->assertSuccessful();
});
