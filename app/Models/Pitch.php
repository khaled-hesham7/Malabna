<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Pitch extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'sport_type',
        'court_size',
        'surface_type',
        'is_indoor',
        'manual_day_price',
        'manual_night_price',
        'online_day_price',
        'online_night_price',
        'night_start_at',
        'amenities',
        'status',
    ];

    protected $casts = [
        'is_indoor'          => 'boolean',
        'manual_day_price'   => 'decimal:2',
        'manual_night_price' => 'decimal:2',
        'online_day_price'   => 'decimal:2',
        'online_night_price' => 'decimal:2',
        'amenities'          => 'array',
    ];

    // =====================
    // Relationships
    // =====================

    /** النادي المالك لهذا الملعب */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** قواعد التسعير الديناميكي لهذا الملعب */
    public function pricingRules(): HasMany
    {
        return $this->hasMany(PitchPricingRule::class);
    }

    /** الفترات الزمنية (slots) الخاصة بهذا الملعب */
    public function slots(): HasMany
    {
        return $this->hasMany(PitchSlot::class);
    }

    /** كل الحجوزات التي تمت على هذا الملعب */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** الجداول الأسبوعية الثابتة على هذا الملعب */
    public function recurringSchedules(): HasMany
    {
        return $this->hasMany(RecurringSchedule::class);
    }

    // =====================
    // Scopes
    // =====================

    /** الملاعب النشطة فقط */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    // =====================
    // Media Collections
    // =====================
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery');
    }
}
