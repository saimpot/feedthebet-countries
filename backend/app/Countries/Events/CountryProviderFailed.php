<?php

declare(strict_types=1);

namespace App\Countries\Events;

use Throwable;

final readonly class CountryProviderFailed
{
    public function __construct(
        public string $provider,
        public Throwable $exception,
    ) {}
}
