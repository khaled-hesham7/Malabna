<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'];
        $tenant = $this->resource['tenant'] ?? $user->tenant ?? null;

        // Determine redirect URL on web according to Spatie roles
        $redirectUrl = match (true) {
            $user->hasRole('super_admin')                     => '/admin/dashboard',
            $user->hasRole(['tenant_owner', 'tenant_staff'])  => '/partner/dashboard',
            default                                           => '/explore',
        };

        return [
            'user'         => new UserResource($user->load('roles')),
            'roles'        => $user->getRoleNames(),
            'permissions'  => $user->getAllPermissions()->pluck('name'),
            'token'        => $this->resource['token'],
            'redirect_url' => $redirectUrl,
            'tenant'       => $tenant ? new TenantResource($tenant) : null,
        ];
    }
}
