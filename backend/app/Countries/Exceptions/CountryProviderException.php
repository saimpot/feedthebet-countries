<?php

declare(strict_types=1);

namespace App\Countries\Exceptions;

use RuntimeException;
use Throwable;

final class CountryProviderException extends RuntimeException
{
    public static function requestFailed(string $provider, Throwable $previous): self
    {
        return new self("Country provider [{$provider}] request failed: {$previous->getMessage()}", 0, $previous);
    }

    public static function invalidPayload(string $provider): self
    {
        return new self("Country provider [{$provider}] returned an unexpected payload.");
    }

    public static function unreadableSource(string $provider, string $path): self
    {
        return new self("Country provider [{$provider}] could not read data from [{$path}].");
    }
}
