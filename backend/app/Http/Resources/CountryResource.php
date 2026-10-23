<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Countries\Data\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Country
 */
final class CountryResource extends JsonResource
{
    /**
     * @return array{code: string, name: string, flagUrl: string, flagAlt: string|null}
     */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
