<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_code',
        'tenant_id',
        'pitch_id',
        'pitch_slot_id',
        'user_id',
        'customer_name',
        'customer_phone',
        'total_price',
        'deposit_amount',
        'paid_amount',
        'remaining_amount',
        'commission_amount',
        'status',
        'booking_type',
        'cancellation_reason',
        'cancelled_at',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(PitchSlot::class, 'pitch_slot_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
