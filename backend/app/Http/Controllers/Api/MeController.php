<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

final readonly class MeController
{
    public function __invoke(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
