<?php

declare(strict_types=1);

namespace App\Countries\Events;

final readonly class CountriesFetched
{
    public function __construct(
        public string $provider,
        public int $count,
    ) {}
}
