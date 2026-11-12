<?php

namespace App\Support;

use App\Enums\DatePrecision;
use App\Models\Animal;
use App\Models\AnimalPhoto;
use App\Models\Litter;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;

/**
 * Public data of a pedigree for the interactive timeline in the frontend.
 * Contains only what visitors may see: no notes and no markers for related pairings.
 */
final class PedigreeTimeline
{
    public function __construct(private PedigreeGraph $graph) {}

    /**
     * @return array{
     *     animals: array<int, array{id: int, name: string, callName: ?string, sex: string, color: ?array{name: string, hex: string}, breed: ?string, born: ?string, origin: string, photos: list<string>, litterId: ?int}>,
     *     litters: list<array{id: int, letter: string, date: ?string, born: string, motherId: int, fatherId: int, offspring: list<int>}>,
     *     lanes: list<int>,
     *     relations: array<int, array{a: list<int>, d: list<int>}>
     * }
     */
    public function toArray(): array
    {
        return [
            'animals' => $this->graph->animals()->map(fn (Animal $animal): array => $this->animal($animal))->all(),
            'litters' => $this->graph->orderedLitters()->map(fn (Litter $litter): array => $this->litter($litter))->all(),
            'lanes' => $this->familyLanes(),
            'relations' => $this->graph->relations(),
        ];
    }

    /**
     * Ids of all breeding mothers, each daughter directly below her mother (depth-first),
     * siblings and unrelated dams in the order of their first litter.
     *
     * @return list<int>
     */
    public function familyLanes(): array
    {
        $queens = [];

        foreach ($this->graph->orderedLitters() as $litter) {
            if ($this->graph->animal($litter->mother_id) !== null) {
                $queens[$litter->mother_id] ??= true;
            }
        }

        $roots = [];
        $daughters = [];

        foreach (array_keys($queens) as $queenId) {
            $motherId = $this->motherIdOf($this->graph->animal($queenId));

            if ($motherId !== null && isset($queens[$motherId])) {
                $daughters[$motherId][] = $queenId;
            } else {
                $roots[] = $queenId;
            }
        }

        $lanes = [];
        $visit = function (int $queenId) use (&$visit, &$lanes, $daughters): void {
            $lanes[] = $queenId;

            foreach ($daughters[$queenId] ?? [] as $daughterId) {
                $visit($daughterId);
            }
        };

        foreach ($roots as $rootId) {
            $visit($rootId);
        }

        return $lanes;
    }

    /**
     * Key figures for the page header.
     *
     * @return array{animals: int, litters: int, dams: int, since: ?int, breeds: list<string>}
     */
    public function stats(): array
    {
        $litters = $this->graph->orderedLitters();

        return [
            'animals' => $this->graph->animals()->count(),
            'litters' => $litters->count(),
            'dams' => $litters->pluck('mother_id')->unique()->count(),
            'since' => $litters->pluck('born_on')->filter()->min()?->year,
            'breeds' => $this->graph->animals()->pluck('breed.name')->filter()->unique()->sort()->values()->all(),
        ];
    }

    /**
     * @return array{id: int, name: string, callName: ?string, sex: string, color: ?array{name: string, hex: string}, breed: ?string, born: ?string, origin: string, photos: list<string>, litterId: ?int}
     */
    private function animal(Animal $animal): array
    {
        return [
            'id' => $animal->id,
            'name' => $animal->name,
            'callName' => $animal->call_name,
            'sex' => $animal->sex->value,
            'color' => $animal->color === null ? null : ['name' => $animal->color->name, 'hex' => $animal->color->hex],
            'breed' => $animal->breed?->name,
            'born' => $animal->born_on === null ? null : $this->formatDate($animal->born_on, $animal->born_on_precision),
            'origin' => $this->graph->describeOrigin($animal),
            'photos' => $animal->photos
                ->map(fn (AnimalPhoto $photo): string => Storage::disk('public')->url($photo->path))
                ->values()
                ->all(),
            'litterId' => $animal->litter_id,
        ];
    }

    /**
     * @return array{id: int, letter: string, date: ?string, born: string, motherId: int, fatherId: int, offspring: list<int>}
     */
    private function litter(Litter $litter): array
    {
        return [
            'id' => $litter->id,
            'letter' => $litter->letter ?? '#'.$litter->id,
            'date' => $litter->born_on?->toDateString(),
            'born' => $litter->born_on === null ? __('Date unknown') : $this->formatDate($litter->born_on, $litter->born_on_precision),
            'motherId' => $litter->mother_id,
            'fatherId' => $litter->father_id,
            'offspring' => $this->graph->offspringOf($litter)->pluck('id')->all(),
        ];
    }

    private function motherIdOf(?Animal $animal): ?int
    {
        if ($animal?->litter_id === null) {
            return null;
        }

        return $this->graph->litter($animal->litter_id)?->mother_id;
    }

    private function formatDate(CarbonInterface $date, ?DatePrecision $precision): string
    {
        return match ($precision) {
            DatePrecision::Month => $date->isoFormat('MMMM YYYY'),
            DatePrecision::Year => $date->format('Y'),
            default => $date->isoFormat('LL'),
        };
    }
}
