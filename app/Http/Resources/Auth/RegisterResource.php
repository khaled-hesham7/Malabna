<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegisterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'];

        return [
            'user'         => new UserResource($user->load('roles')),
            'roles'        => $user->getRoleNames(),
            'token'        => $this->resource['token'],
            'redirect_url' => '/explore',
        ];
    }
}
