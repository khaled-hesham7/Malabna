<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class RecurringSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'pitch_id',
        'user_id',
        'customer_name',
        'customer_phone',
        'day_of_week',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'agreed_price',
        'status',
    ];

    protected $casts = [
        'day_of_week'  => 'integer',
        'agreed_price' => 'decimal:2',
        'start_date'   => 'date',
        'end_date'     => 'date',
    ];

    // Relations
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }
}
