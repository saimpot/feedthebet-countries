<?php

declare(strict_types=1);

namespace App\Countries\Listeners;

use App\Countries\Events\CountryProviderFailed;
use Psr\Log\LoggerInterface;

final readonly class LogCountryProviderFailure
{
    public function __construct(private LoggerInterface $logger) {}

    public function handle(CountryProviderFailed $event): void
    {
        $this->logger->warning('Country provider failed.', [
            'provider' => $event->provider,
            'exception' => $event->exception::class,
            'message' => $event->exception->getMessage(),
        ]);
    }
}
