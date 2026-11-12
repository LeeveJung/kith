<?php

namespace App\Filament\App\Resources\Animals\Schemas;

use App\Enums\AnimalOrigin;
use App\Enums\DatePrecision;
use App\Enums\Sex;
use App\Models\AnimalPhoto;
use App\Models\Breed;
use App\Models\Color;
use App\Models\Litter;
use App\Support\PedigreeRules;
use App\Support\WebpImage;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AnimalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('General'))
                    ->columns(2)
                    ->schema([
                        Select::make('species_id')
                            ->label(__('Species'))
                            ->relationship('species', 'name')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('breed_id', null);
                                $set('color_id', null);
                                $set('litter_id', null);
                            })
                            ->disabledOn('edit'),
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('call_name')
                            ->label(__('Call name'))
                            ->helperText(__('Alternative name, e.g. used in the buyer\'s family.'))
                            ->maxLength(255),
                        Select::make('sex')
                            ->label(__('Sex'))
                            ->options(Sex::class)
                            ->default(Sex::Unknown)
                            ->required(),
                        Select::make('origin')
                            ->label(__('Origin'))
                            ->options(AnimalOrigin::class)
                            ->default(AnimalOrigin::Foundation)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                if (! self::isBred($state)) {
                                    $set('litter_id', null);
                                }
                            }),
                    ]),
                Section::make(__('Breeding'))
                    ->columns(2)
                    ->schema([
                        Select::make('breed_id')
                            ->label(__('Breed'))
                            ->relationship(
                                'breed',
                                'name',
                                modifyQueryUsing: fn (Builder $query, Get $get) => $query
                                    ->availableTo(Filament::getTenant())
                                    ->where('species_id', $get('species_id')),
                            )
                            ->rules(fn (Get $get): array => [PedigreeRules::breed(Filament::getTenant()?->getKey(), $get('species_id'))])
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Select::make('species_id')
                                    ->label(__('Species'))
                                    ->relationship('species', 'name')
                                    ->required(),
                                TextInput::make('name')
                                    ->label(__('Name'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionUsing(fn (array $data): int => Breed::firstOrCreate([
                                'species_id' => $data['species_id'],
                                'breeder_id' => Filament::getTenant()->getKey(),
                                'name' => $data['name'],
                            ])->getKey())
                            ->createOptionAction(fn (Action $action) => $action->authorize(
                                fn (): bool => Filament::auth()->user()->can('create', Breed::class),
                            )),
                        Select::make('color_id')
                            ->label(__('Color'))
                            ->relationship(
                                'color',
                                'name',
                                modifyQueryUsing: fn (Builder $query, Get $get) => $query
                                    ->availableTo(Filament::getTenant())
                                    ->where('species_id', $get('species_id')),
                            )
                            ->rules(fn (Get $get): array => [PedigreeRules::color(Filament::getTenant()?->getKey(), $get('species_id'))])
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Select::make('species_id')
                                    ->label(__('Species'))
                                    ->relationship('species', 'name')
                                    ->required(),
                                TextInput::make('name')
                                    ->label(__('Name'))
                                    ->required()
                                    ->maxLength(255),
                                ColorPicker::make('hex')
                                    ->label(__('Color'))
                                    ->required(),
                                Toggle::make('is_dark')
                                    ->label(__('Needs light text')),
                            ])
                            ->createOptionUsing(fn (array $data): int => Color::firstOrCreate(
                                [
                                    'species_id' => $data['species_id'],
                                    'breeder_id' => Filament::getTenant()->getKey(),
                                    'name' => $data['name'],
                                ],
                                ['hex' => $data['hex'], 'is_dark' => $data['is_dark'] ?? false],
                            )->getKey())
                            ->createOptionAction(fn (Action $action) => $action->authorize(
                                fn (): bool => Filament::auth()->user()->can('create', Color::class),
                            )),
                        Select::make('litter_id')
                            ->label(__('Litter'))
                            ->helperText(__('The parents are derived from the litter.'))
                            ->visible(fn (Get $get): bool => self::isBred($get('origin')))
                            ->required(fn (Get $get): bool => self::isBred($get('origin')))
                            ->dehydratedWhenHidden()
                            ->relationship(
                                'litter',
                                'letter',
                                modifyQueryUsing: fn (Builder $query, Get $get) => $query
                                    ->with(['mother', 'father'])
                                    ->where('species_id', $get('species_id')),
                            )
                            ->rules(fn (Get $get): array => [PedigreeRules::litter(Filament::getTenant()?->getKey(), $get('species_id'))])
                            ->getOptionLabelFromRecordUsing(fn (Litter $record): string => ($record->letter ?? __('Litter').' #'.$record->getKey())
                                .' · '.$record->mother->name.' × '.$record->father->name)
                            ->searchable()
                            ->preload(),
                        DatePicker::make('born_on')
                            ->label(__('Birth date')),
                        Select::make('born_on_precision')
                            ->label(__('Precision'))
                            ->options(DatePrecision::class)
                            ->helperText(__('How precisely the date is known (e.g. month only).')),
                    ]),
                Section::make(__('Photos'))
                    ->schema([
                        self::photos(),
                    ]),
                Section::make(__('Notes'))
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes')
                            ->hiddenLabel()
                            ->rows(4),
                    ]),
            ]);
    }

    /**
     * Photos of an animal (up to five, stored as WebP). Shared with the offspring form of a litter.
     */
    public static function photos(): Repeater
    {
        return Repeater::make('photos')
            ->hiddenLabel()
            ->relationship()
            ->orderColumn('position')
            ->defaultItems(0)
            ->maxItems(AnimalPhoto::MAX_PER_ANIMAL)
            ->addActionLabel(__('Add photo'))
            ->schema([
                FileUpload::make('path')
                    ->label(__('Photo'))
                    ->helperText(__('Stored as WebP, at most :width px wide.', ['width' => WebpImage::MAX_WIDTH]))
                    ->image()
                    ->acceptedFileTypes(WebpImage::ACCEPTED_MIME_TYPES)
                    ->disk('public')
                    ->directory('animal-photos')
                    ->saveUploadedFileUsing(function (FileUpload $component, TemporaryUploadedFile $file): string {
                        $path = WebpImage::store($file->get(), $component->getDiskName(), $component->getDirectory());
                        $file->delete();

                        return $path;
                    })
                    ->required(),
                TextInput::make('caption')
                    ->label(__('Caption'))
                    ->maxLength(255),
            ]);
    }

    private static function isBred(mixed $origin): bool
    {
        $origin = $origin instanceof AnimalOrigin ? $origin : AnimalOrigin::tryFrom((string) $origin);

        return $origin === AnimalOrigin::Bred;
    }
}
