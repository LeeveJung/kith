<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Local credentials: test@example.com (breeder) and admin@example.com
     * (platform super admin), both with the password "password".
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::factory()->superAdmin()->create([
            'name' => 'Platform Admin',
            'email' => 'admin@example.com',
        ]);

        $this->call([
            SpeciesSeeder::class,
            ColorSeeder::class,
            DemoBreederSeeder::class,
        ]);
    }
}
