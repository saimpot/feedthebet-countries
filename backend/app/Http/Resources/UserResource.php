<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Auth0\Laravel\Users\UserContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public view of the authenticated Auth0 user.
 *
 * @property Authenticatable&UserContract $resource
 */
final class UserResource extends JsonResource
{
    /**
     * @return array{name: mixed, email: mixed, picture: mixed}
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource->getAttribute('name'),
            'email' => $this->resource->getAttribute('email'),
            'picture' => $this->resource->getAttribute('picture'),
        ];
    }
}
