<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\TenantResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    /**
     * Transform the user profile resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'phone'       => $this->phone,
            'email'       => $this->email,
            'status'      => $this->status,
            'roles'       => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'tenant'      => $this->when(
                $this->relationLoaded('tenant') && $this->tenant !== null,
                fn () => new TenantResource($this->tenant)
            ),
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}
