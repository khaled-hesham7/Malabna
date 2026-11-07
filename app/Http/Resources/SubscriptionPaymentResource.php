<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'tenant_id'            => $this->tenant_id,
            'amount'               => (float) $this->amount,
            'currency'             => $this->currency ?? 'EGP',
            'payment_method'       => $this->payment_method,
            'transaction_id'       => $this->transaction_id,
            'billing_period_start' => $this->billing_period_start?->toDateString(),
            'billing_period_end'   => $this->billing_period_end?->toDateString(),
            'status'               => $this->status,
            'notes'                => $this->notes,
            'paid_at'              => $this->paid_at?->toDateTimeString(),
            'created_at'           => $this->created_at?->toDateTimeString(),
            'tenant'               => new TenantResource($this->whenLoaded('tenant')),
        ];
    }
}
