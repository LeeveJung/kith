<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesBreederReferenceData;

class BreedPolicy
{
    use AuthorizesBreederReferenceData;
}
