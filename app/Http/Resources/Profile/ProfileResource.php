<?php

namespace App\Http\Resources\Profile;

use App\Http\Resources\TenantResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'phone'       => $this->phone,
            'status'      => $this->status,
            'avatar_url'  => $this->getFirstMediaUrl('avatar') ?: null,
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

