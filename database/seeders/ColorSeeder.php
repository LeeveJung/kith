<?php

namespace Database\Seeders;

use App\Models\Species;
use Illuminate\Database\Seeder;

/**
 * Global cat colors from the design export. "Unknown" is not a color but
 * stays empty on the animal (color_id = null). Requires SpeciesSeeder.
 */
class ColorSeeder extends Seeder
{
    /**
     * @var array<string, array{0: string, 1: bool}>
     */
    private const CAT_COLORS = [
        'Black' => ['#24221F', true],
        'Black Smoke' => ['#3B3936', true],
        'Black Smoke Tortie' => ['#3E3733', false],
        'Black Silver' => ['#A9A9A4', false],
        'Black Tortie' => ['#4A3326', true],
        'Black White' => ['#2B2926', false],
        'Blue' => ['#7F8792', true],
        'Blue Cream' => ['#A39C98', false],
        'Blue Silver' => ['#B4B8BE', false],
        'Blue Silver Tortie' => ['#C2B8B2', false],
        'Blue Tortie' => ['#8B8F98', true],
        'Brown Tabby' => ['#8A6540', true],
        'Brown Tortie' => ['#6B4A33', false],
        'Cream' => ['#E6D5B8', false],
        'Red' => ['#B86B3C', true],
        'Red Silver' => ['#D2AB8C', false],
        'Silver Tortie' => ['#A59A92', false],
        'White' => ['#F4F1EC', false],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cats = Species::firstWhere('slug', 'cat');

        foreach (self::CAT_COLORS as $name => [$hex, $isDark]) {
            $cats->colors()->updateOrCreate(
                ['name' => $name, 'breeder_id' => null],
                ['hex' => $hex, 'is_dark' => $isDark],
            );
        }
    }
}
