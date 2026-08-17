<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Builder;

class PitchPricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'pitch_id',
        'name',
        'day_of_week',
        'start_time',
        'end_time',
        'price_per_hour',
        'min_deposit_type',
        'min_deposit_amount',
        'status',
    ];

    protected $casts = [
        'day_of_week'        => 'integer',
        'price_per_hour'     => 'decimal:2',
        'min_deposit_amount' => 'decimal:2',
    ];

    // Relationships
    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }

    public function tenant(): HasOneThrough
    {
        return $this->hasOneThrough(Tenant::class, Pitch::class, 'id', 'id', 'pitch_id', 'tenant_id');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    // Helper: حساب قيمة العربون المطلوبة بناءً على إجمالي السعر
    public function calculateDepositAmount(float $totalPrice): float
    {
        return match ($this->min_deposit_type) {
            'percentage' => round(($totalPrice * $this->min_deposit_amount) / 100, 2),
            'fixed'      => min($this->min_deposit_amount, $totalPrice),
            'full'       => $totalPrice,
            default      => $totalPrice,
        };
    }
}
