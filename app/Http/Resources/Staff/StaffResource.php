<?php

namespace App\Http\Resources\Staff;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
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
            'status'      => $this->status, // active, inactive, banned
            'avatar_url'  => $this->getFirstMediaUrl('avatar') ?: null,
            'role'        => $this->getRoleNames()->first() ?? 'tenant_staff',
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'created_at'  => $this->created_at?->toDateTimeString(),
        ];
    }
}

