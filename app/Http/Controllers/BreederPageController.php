<?php

namespace App\Http\Controllers;

use App\Models\Breeder;
use App\Support\PedigreeGraph;
use App\Support\PedigreeTimeline;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BreederPageController extends Controller
{
    /**
     * Public page of a breeder with the interactive pedigree timeline of one species.
     */
    public function __invoke(Request $request, Breeder $breeder): View
    {
        $speciesOptions = $breeder->speciesWithLitters();
        $speciesId = array_key_exists($request->integer('species'), $speciesOptions)
            ? $request->integer('species')
            : array_key_first($speciesOptions);

        $timeline = $speciesId === null ? null : new PedigreeTimeline(PedigreeGraph::forBreeder($breeder, $speciesId));

        return view('breeders.show', [
            'breeder' => $breeder,
            'speciesOptions' => $speciesOptions,
            'speciesId' => $speciesId,
            'pedigree' => $timeline?->toArray(),
            'stats' => $timeline?->stats(),
        ]);
    }
}
