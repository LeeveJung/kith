<?php

namespace App\Support;

use App\Models\Animal;
use App\Models\Breeder;
use App\Models\Litter;
use Illuminate\Support\Collection;

/**
 * In-memory view of a breeder's pedigree. Parents are derived from litters:
 * an animal's mother and father are the parents of the litter it was born in.
 * Used to render the pedigree page (lanes, relatives highlighting, profiles).
 */
final class PedigreeGraph
{
    /** @var Collection<int, Animal> */
    private Collection $animals;

    /** @var Collection<int, Litter> */
    private Collection $litters;

    /** @var array<int, array{0: int, 1: int}> animal id => [mother id, father id] */
    private array $parents = [];

    /** @var array<int, list<int>> animal id => ids of its children */
    private array $children = [];

    /** @var array<int, int> litter id => sort key (memoized) */
    private array $sortKeys = [];

    /**
     * @param  Collection<int, Animal>  $animals
     * @param  Collection<int, Litter>  $litters
     */
    public function __construct(Collection $animals, Collection $litters)
    {
        $this->animals = $animals->keyBy('id');
        $this->litters = $litters->keyBy('id');

        foreach ($this->animals as $animal) {
            $litter = $animal->litter_id !== null ? $this->litters->get($animal->litter_id) : null;

            if ($litter === null) {
                continue;
            }

            $this->parents[$animal->id] = [$litter->mother_id, $litter->father_id];
            $this->children[$litter->mother_id][] = $animal->id;
            $this->children[$litter->father_id][] = $animal->id;
        }
    }

    public static function forBreeder(Breeder $breeder, ?int $speciesId = null): self
    {
        $animals = $breeder->animals()
            ->when($speciesId, fn ($query) => $query->where('species_id', $speciesId))
            ->with(['color', 'photos'])
            ->get();

        $litters = $breeder->litters()
            ->when($speciesId, fn ($query) => $query->where('species_id', $speciesId))
            ->get();

        return new self($animals, $litters);
    }

    public function animal(int $id): ?Animal
    {
        return $this->animals->get($id);
    }

    public function litter(int $id): ?Litter
    {
        return $this->litters->get($id);
    }

    /**
     * @return Collection<int, Animal>
     */
    public function offspringOf(Litter $litter): Collection
    {
        return $this->animals->where('litter_id', $litter->id)->values();
    }

    /**
     * Litter mates of an animal (excluding itself).
     *
     * @return Collection<int, Animal>
     */
    public function siblingsOf(Animal $animal): Collection
    {
        if ($animal->litter_id === null) {
            return collect();
        }

        return $this->animals->where('litter_id', $animal->litter_id)->where('id', '!=', $animal->id)->values();
    }

    /**
     * Litters in which the animal is mother or father, oldest first.
     *
     * @return Collection<int, Litter>
     */
    public function littersOf(Animal $animal): Collection
    {
        return $this->orderedLitters()
            ->filter(fn (Litter $litter): bool => $litter->mother_id === $animal->id || $litter->father_id === $animal->id)
            ->values();
    }

    /**
     * All ancestors of an animal as [ancestor id => generations away].
     *
     * @return array<int, int>
     */
    public function ancestorsOf(int $id): array
    {
        $found = [];
        $this->walk($this->parents, $id, 1, $found);

        return $found;
    }

    /**
     * All descendants of an animal as [descendant id => generations away].
     *
     * @return array<int, int>
     */
    public function descendantsOf(int $id): array
    {
        $found = [];
        $this->walk($this->children, $id, 1, $found);

        return $found;
    }

    /**
     * Ancestor and descendant ids for every animal, for client-side highlighting.
     *
     * @return array<int, array{a: list<int>, d: list<int>}>
     */
    public function relations(): array
    {
        $relations = [];

        foreach ($this->animals->keys() as $id) {
            $relations[$id] = [
                'a' => array_keys($this->ancestorsOf($id)),
                'd' => array_keys($this->descendantsOf($id)),
            ];
        }

        return $relations;
    }

    /**
     * Whether the parents of a litter are related (one is an ancestor of the
     * other, or they share an ancestor).
     */
    public function isRelatedPairing(Litter $litter): bool
    {
        $motherLine = $this->ancestorsOf($litter->mother_id);
        $fatherLine = $this->ancestorsOf($litter->father_id);

        return isset($motherLine[$litter->father_id])
            || isset($fatherLine[$litter->mother_id])
            || array_intersect_key($motherLine, $fatherLine) !== [];
    }

    /**
     * Litters in chronological order. Litters without a birth date are placed
     * right after the newest litter of their parents.
     *
     * @return Collection<int, Litter>
     */
    public function orderedLitters(): Collection
    {
        return $this->litters
            ->sortBy(fn (Litter $litter): array => [$this->sortKey($litter), $litter->id])
            ->values();
    }

    /**
     * Whether the animal is mother or father of at least one litter.
     */
    public function isBreedingAnimal(Animal $animal): bool
    {
        return $this->litters->contains(
            fn (Litter $litter): bool => $litter->mother_id === $animal->id || $litter->father_id === $animal->id,
        );
    }

    /**
     * Short origin description, e.g. "Own breeding · litter A" or "External animal".
     */
    public function describeOrigin(Animal $animal): string
    {
        $litter = $animal->litter_id !== null ? $this->litters->get($animal->litter_id) : null;

        if ($litter === null) {
            return $animal->origin->getLabel();
        }

        return __('Own breeding · litter :letter', ['letter' => $litter->letter ?? '#'.$litter->id]);
    }

    /**
     * One lane per breeding mother, most litters first; ties keep the order of the first litter.
     *
     * @return list<array{queen: Animal, litters: Collection<int, Litter>}>
     */
    public function lanes(): array
    {
        $lanes = [];

        foreach ($this->orderedLitters()->groupBy('mother_id') as $motherId => $litters) {
            $queen = $this->animals->get($motherId);

            if ($queen !== null) {
                $lanes[] = ['queen' => $queen, 'litters' => $litters];
            }
        }

        usort($lanes, fn (array $a, array $b): int => $b['litters']->count() <=> $a['litters']->count());

        return $lanes;
    }

    /**
     * Fathers with their number of litters and offspring, most litters first.
     *
     * @return list<array{animal: Animal, litters: int, offspring: int}>
     */
    public function sires(): array
    {
        $sires = [];

        foreach ($this->litters->groupBy('father_id') as $fatherId => $litters) {
            $sire = $this->animals->get($fatherId);

            if ($sire !== null) {
                $sires[] = [
                    'animal' => $sire,
                    'litters' => $litters->count(),
                    'offspring' => $litters->sum(fn (Litter $litter): int => $this->offspringOf($litter)->count()),
                ];
            }
        }

        usort($sires, fn (array $a, array $b): int => $b['litters'] <=> $a['litters']);

        return $sires;
    }

    /**
     * @param  array<int, array<int, int>|list<int>>  $edges
     * @param  array<int, int>  $found
     */
    private function walk(array $edges, int $id, int $depth, array &$found): void
    {
        foreach ($edges[$id] ?? [] as $next) {
            if (! isset($found[$next]) || $found[$next] > $depth) {
                $found[$next] = $depth;
                $this->walk($edges, $next, $depth + 1, $found);
            }
        }
    }

    private function sortKey(Litter $litter, int $depth = 0): int
    {
        if (isset($this->sortKeys[$litter->id])) {
            return $this->sortKeys[$litter->id];
        }

        if ($litter->born_on !== null) {
            return $this->sortKeys[$litter->id] = $litter->born_on->getTimestamp();
        }

        $parentKeys = [];

        if ($depth < 50) {
            foreach ([$litter->mother_id, $litter->father_id] as $parentId) {
                $parentLitterId = $this->animals->get($parentId)?->litter_id;
                $parentLitter = $parentLitterId !== null ? $this->litters->get($parentLitterId) : null;

                if ($parentLitter !== null) {
                    $parentKeys[] = $this->sortKey($parentLitter, $depth + 1);
                }
            }
        }

        return $this->sortKeys[$litter->id] = $parentKeys === [] ? 0 : max($parentKeys) + 1;
    }
}
