<?php

namespace Database\Seeders;

use App\Enums\AnimalOrigin;
use App\Enums\DatePrecision;
use App\Enums\Sex;
use App\Models\Animal;
use App\Models\AnimalPhoto;
use App\Models\Breeder;
use App\Models\Litter;
use App\Models\Species;
use App\Models\User;
use App\Support\WebpImage;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Demo data from the "Katzen-Stammbaum" design export (breeder "Nordwald Coons").
 * Requires SpeciesSeeder and ColorSeeder. Photos are imported from data/uploads if present
 * and stored as WebP under anonymous names, like uploads in the backend.
 */
class DemoBreederSeeder extends Seeder
{
    private const MONTHS = [
        'January' => 1, 'February' => 2, 'March' => 3, 'April' => 4, 'May' => 5, 'June' => 6,
        'July' => 7, 'August' => 8, 'September' => 9, 'October' => 10, 'November' => 11, 'December' => 12,
    ];

    /**
     * [key, name, sex, color, origin]
     *
     * @var list<array{0: string, 1: string, 2: Sex, 3: string, 4: AnimalOrigin}>
     */
    private const FOUNDERS = [
        ['luna', 'Luna', Sex::Female, 'Black', AnimalOrigin::Foundation],
        ['greven', 'Greven', Sex::Male, 'Cream', AnimalOrigin::Foundation],
        ['dexter', 'Dexter', Sex::Male, 'Black Silver', AnimalOrigin::Foundation],
        ['azlan', 'Azlan', Sex::Male, 'Red', AnimalOrigin::External],
        ['auri', 'Auri', Sex::Female, 'Black Smoke Tortie', AnimalOrigin::External],
        ['mia', 'Mia', Sex::Female, 'Brown Tortie', AnimalOrigin::External],
    ];

    /**
     * [letter, date (month year), mother, father, [[name, sex (w/m/?), color], ...]]
     * Parents must be defined in an earlier litter or among the founders.
     *
     * @var list<array{0: string, 1: ?string, 2: string, 3: string, 4: list<array{0: string, 1: string, 2: string}>}>
     */
    private const LITTERS = [
        ['A', null, 'luna', 'greven', [['Grim', 'm', 'Black Silver'], ['Idun', 'w', 'Silver Tortie'], ['Thor', 'm', 'Blue Silver'], ['Freya', 'w', 'Blue Silver Tortie']]],
        ['B', 'April 2023', 'luna', 'greven', [['Sirius', 'm', 'Black Silver'], ['Aurora', 'w', 'Silver Tortie'], ['Orion', 'm', 'Black Silver']]],
        ['C', 'June 2024', 'luna', 'dexter', [['Misty', 'w', 'Blue Silver'], ['Ash', 'm', 'Blue'], ['Brock', 'm', 'Blue Silver']]],
        ['D', 'November 2023', 'freya', 'azlan', [['Ada', 'w', 'Cream'], ['Leonardo', 'm', 'Cream'], ['Alfred', 'm', 'Black Silver'], ['Isaac', 'm', 'Black Silver'], ['Galileo', 'm', 'Blue Silver']]],
        ['E', 'June 2025', 'freya', 'azlan', [['Edvard', 'm', 'Red'], ['Antonio', 'm', 'Cream'], ['Amadeus', 'm', 'Cream'], ['Clara', 'w', 'Cream'], ['Richard', 'm', 'Cream'], ['Ludwig', 'm', 'Black Silver'], ['Sebastian', 'm', 'Cream']]],
        ['F', 'November 2024', 'ada', 'dexter', [['Icarus', 'm', 'Cream'], ['Achilles', 'm', 'Cream'], ['Midas', 'm', 'Red'], ['Iris', 'w', 'Black Tortie']]],
        ['G', 'December 2024', 'auri', 'dexter', [['Rhaegal', 'm', 'Black Silver']]],
        ['H', 'February 2026', 'ada', 'rhaegal', [['Olof', 'm', 'White'], ['Elsa', 'w', 'Silver Tortie'], ['Anna', 'w', 'Black White']]],
        ['I', null, 'mia', 'galileo', [['Merritt', 'w', 'Silver Tortie']]],
        ['J', null, 'clara', 'dexter', [['Kitten 1', '?', ''], ['Kitten 2', '?', ''], ['Kitten 3', '?', ''], ['Kitten 4', '?', '']]],
        ['K', null, 'merritt', 'dexter', [['Kitten 1', '?', ''], ['Kitten 2', '?', ''], ['Kitten 3', '?', ''], ['Kitten 4', '?', '']]],
    ];

    /**
     * Litters marked as "new" in the design (just born, names to follow).
     */
    private const NEW_LITTERS = ['J', 'K'];

    /**
     * Call names that differ from the name in the pedigree.
     */
    private const CALL_NAMES = ['galileo' => 'Frankie'];

    /**
     * Photo ids of the design (data/uploads/grafik-<id>.png), main photo at position 0.
     *
     * @var array<string, list<string>>
     */
    private const PHOTOS = [
        'luna' => ['c14f9090'], 'greven' => ['942ef56f'], 'grim' => ['e6c2bcae'], 'idun' => ['fd05d4d2'],
        'thor' => ['456759d6'], 'freya' => ['4019fbd6', '11ba1e8e'], 'azlan' => ['5d5f828c'],
        'ada' => ['3beead7e', 'febb3eec'], 'icarus' => ['bef7558f'], 'achilles' => ['a1fa09e2'],
        'midas' => ['2a7b369e'], 'iris' => ['8d7e45e5'], 'auri' => ['b7ccc285'], 'rhaegal' => ['f1be7952'],
        'olof' => ['c7149384'], 'elsa' => ['d1510a30'], 'anna' => ['a68ac65c'], 'merritt' => ['a2c890b2'],
        'mia' => ['58864c24'], 'leonardo' => ['240ff190'], 'alfred' => ['061569e8'], 'isaac' => ['bc966c37'],
        'galileo' => ['4a2647fe', '1bbb715f'], 'edvard' => ['1f9295d0'], 'antonio' => ['9762754a'],
        'amadeus' => ['824b2818'], 'clara' => ['40df78a7'], 'richard' => ['c37835e2'], 'ludwig' => ['a1d28f82'],
        'sebastian' => ['4a2e23a6'], 'sirius' => ['d29685d4'], 'aurora' => ['c0e5f475'], 'orion' => ['e8bb68a2'],
        'dexter' => ['5e841bc2'], 'misty' => ['e29adb7a'], 'ash' => ['02ab22b0'], 'brock' => ['dc12c906'],
    ];

    private Breeder $breeder;

    private Species $species;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->breeder = Breeder::updateOrCreate(['slug' => 'nordwald-coons'], ['name' => 'Nordwald Coons']);
        $this->species = Species::firstWhere('slug', 'cat');

        User::firstWhere('email', 'test@example.com')?->breeders()->syncWithoutDetaching($this->breeder);

        /** @var array<string, Animal> $animals */
        $animals = [];

        foreach (self::FOUNDERS as [$key, $name, $sex, $color, $origin]) {
            $animals[$key] = $this->createAnimal($key, $name, $sex, $color, $origin);
        }

        foreach (self::LITTERS as [$letter, $date, $motherKey, $fatherKey, $kittens]) {
            [$bornOn, $precision] = $this->parseDate($date);

            $litter = Litter::updateOrCreate(
                ['breeder_id' => $this->breeder->id, 'species_id' => $this->species->id, 'letter' => $letter],
                [
                    'mother_id' => $animals[$motherKey]->id,
                    'father_id' => $animals[$fatherKey]->id,
                    'born_on' => $bornOn,
                    'born_on_precision' => $precision,
                    'notes' => in_array($letter, self::NEW_LITTERS, true) ? 'Just born, names to follow.' : null,
                ],
            );

            foreach ($kittens as [$name, $sex, $color]) {
                $key = Str::lower(preg_replace('/[^\p{L}\p{N}]/u', '', $name));
                $key = isset($animals[$key]) ? $key.Str::lower($letter) : $key;

                $animals[$key] = $this->createAnimal(
                    $key,
                    $name,
                    match ($sex) {
                        'w' => Sex::Female,
                        'm' => Sex::Male,
                        default => Sex::Unknown,
                    },
                    $color,
                    AnimalOrigin::Bred,
                    $litter,
                );
            }
        }
    }

    private function createAnimal(
        string $key,
        string $name,
        Sex $sex,
        string $color,
        AnimalOrigin $origin,
        ?Litter $litter = null,
    ): Animal {
        $animal = Animal::updateOrCreate(
            ['breeder_id' => $this->breeder->id, 'name' => $name, 'litter_id' => $litter?->id],
            [
                'species_id' => $this->species->id,
                'breed_id' => $this->species->breeds()->whereNull('breeder_id')->firstWhere('name', 'Maine Coon')?->id,
                'color_id' => $color === '' ? null : $this->species->colors()->whereNull('breeder_id')->firstWhere('name', $color)?->id,
                'call_name' => self::CALL_NAMES[$key] ?? null,
                'sex' => $sex,
                'origin' => $origin,
                'born_on' => $litter?->born_on,
                'born_on_precision' => $litter?->born_on_precision,
            ],
        );

        $this->importPhotos($animal, $key);

        return $animal;
    }

    private function importPhotos(Animal $animal, string $key): void
    {
        foreach (self::PHOTOS[$key] ?? [] as $position => $photoId) {
            $source = base_path("data/uploads/grafik-{$photoId}.png");

            if (! File::exists($source)) {
                continue;
            }

            $photo = AnimalPhoto::firstOrNew(['animal_id' => $animal->id, 'position' => $position]);

            if ($photo->path !== null) {
                Storage::disk('public')->delete($photo->path);
            }

            $photo->path = WebpImage::store(File::get($source), 'public', AnimalPhoto::directoryFor($this->breeder));
            $photo->save();
        }
    }

    /**
     * Turns "April 2023" into [2023-04-01, month precision]; null stays unknown.
     *
     * @return array{0: ?CarbonImmutable, 1: ?DatePrecision}
     */
    private function parseDate(?string $date): array
    {
        if ($date === null) {
            return [null, null];
        }

        [$month, $year] = explode(' ', $date);

        return [CarbonImmutable::create((int) $year, self::MONTHS[$month], 1), DatePrecision::Month];
    }
}
