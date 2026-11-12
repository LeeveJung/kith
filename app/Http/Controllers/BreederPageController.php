<?php

namespace App\Http\Controllers;

use App\Models\Breeder;
use Illuminate\Contracts\View\View;

class BreederPageController extends Controller
{
    /**
     * Public page of a breeder. For now only a placeholder until the frontend pedigree is built.
     */
    public function __invoke(Breeder $breeder): View
    {
        return view('breeders.show', ['breeder' => $breeder]);
    }
}
