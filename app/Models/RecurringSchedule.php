<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'pitch_id',
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'price_per_slot',
        'status',
    ];

    protected $casts = [
        'day_of_week'    => 'integer',
        'price_per_slot' => 'decimal:2',
        'start_date'     => 'date',
        'end_date'       => 'date',
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
}
