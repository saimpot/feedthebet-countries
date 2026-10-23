<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Countries\Contracts\CountryProvider;
use App\Http\Resources\CountryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class CountryController
{
    public function __construct(private CountryProvider $countries) {}

    public function __invoke(): AnonymousResourceCollection
    {
        return CountryResource::collection($this->countries->all());
    }
}
