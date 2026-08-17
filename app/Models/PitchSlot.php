<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Builder;

class PitchSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'pitch_id',
        'date',
        'start_time',
        'end_time',
        'price',
        'status',
        'is_visible_online',
        'locked_by_user_id',
        'locked_until',
    ];

    protected $casts = [
        'date'              => 'date',
        'price'             => 'decimal:2',
        'is_visible_online' => 'boolean',
        'locked_until'      => 'datetime',
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

    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    // Scopes

    // الفترات المتاحة للجمهور أونلاين وغير مقفولة حالياً
    public function scopeAvailableOnline(Builder $query): Builder
    {
        return $query->where('is_visible_online', true)
            ->where('status', 'available')
            ->where(function ($q) {
                $q->whereNull('locked_until')
                    ->orWhere('locked_until', '<', now());
            });
    }

    // الفترات التي انتهت مدة قفلها المؤقت (للـ Cleanup Jobs)
    public function scopeExpiredLocks(Builder $query): Builder
    {
        return $query->where('status', 'locked')
            ->where('locked_until', '<', now());
    }
}
