<?php

namespace App\Filament\App\Pages\Tenancy;

use App\Models\Breeder;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RegisterBreeder extends RegisterTenant
{
    public static function getLabel(): string
    {
        return __('Register breeder');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('Breeder name'))
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')
                    ->label(__('Short name (URL)'))
                    ->required()
                    ->maxLength(255)
                    ->alphaDash()
                    ->unique(Breeder::class, 'slug'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Breeder
    {
        $breeder = Breeder::create($data);

        $breeder->users()->attach(auth()->user());

        return $breeder;
    }
}
