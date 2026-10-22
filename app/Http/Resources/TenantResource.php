<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                          => $this->id,
            'name'                        => $this->name,
            'slug'                        => $this->slug,
            'company_name'                => $this->company_name,
            'logo'                        => $this->getFirstMediaUrl('logo') ?: null,
            'phone'                       => $this->phone,
            'city'                        => $this->city,
            'address'                     => $this->address,
            'description'                 => $this->description,
            'latitude'                    => $this->latitude,
            'longitude'                   => $this->longitude,
            'status'                      => $this->status,
            'rejection_reason'            => $this->when($this->status === 'rejected', $this->rejection_reason),
            'subscription_price'          => $this->subscription_price,
            'subscription_billing_period' => $this->subscription_billing_period,
            'subscription_starts_at'      => $this->subscription_starts_at?->toDateTimeString(),
            'subscription_expires_at'     => $this->subscription_expires_at?->toDateTimeString(),
            'commission_type'             => $this->commission_type,
            'commission_rate'             => $this->commission_rate,
            'preferred_plan'              => $this->preferred_plan,
            'owner'                       => new UserResource($this->whenLoaded('owner')),
            'created_at'                  => $this->created_at?->toDateTimeString(),
        ];
    }
}
