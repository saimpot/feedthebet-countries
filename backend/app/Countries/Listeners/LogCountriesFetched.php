<?php

declare(strict_types=1);

namespace App\Countries\Listeners;

use App\Countries\Events\CountriesFetched;
use Psr\Log\LoggerInterface;

final readonly class LogCountriesFetched
{
    public function __construct(private LoggerInterface $logger) {}

    public function handle(CountriesFetched $event): void
    {
        $this->logger->info('Countries fetched.', [
            'provider' => $event->provider,
            'count' => $event->count,
        ]);
    }
}
