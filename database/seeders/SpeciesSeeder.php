<?php

namespace Database\Seeders;

use App\Models\Species;
use Illuminate\Database\Seeder;

class SpeciesSeeder extends Seeder
{
    /**
     * Species with their global breeds. More can be added by platform admins.
     *
     * @var array<string, list<string>>
     */
    private const SPECIES = [
        'Cat' => ['Maine Coon'],
        'Dog' => [],
        'Horse' => [],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::SPECIES as $name => $breeds) {
            $species = Species::updateOrCreate(['slug' => str($name)->slug()->toString()], ['name' => $name]);

            foreach ($breeds as $breed) {
                $species->breeds()->updateOrCreate(['name' => $breed, 'breeder_id' => null]);
            }
        }
    }
}
