<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\TenantResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantRegisterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'tenant' => new TenantResource($this->resource),
        ];
    }
}
