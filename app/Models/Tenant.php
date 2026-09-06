<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Tenant extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'company_name',
        'description',
        'city',
        'phone',
        'address',
        'latitude',
        'longitude',
        'status',
        'rejection_reason',
        'subscription_price',
        'subscription_billing_period',
        'subscription_starts_at',
        'subscription_expires_at',
        'commission_type',
        'commission_rate',
        'cancellation_deadline_hours',
        'currency',
    ];

    protected $casts = [
        'subscription_price'          => 'decimal:2',
        'commission_rate'             => 'decimal:2',
        'subscription_starts_at'      => 'datetime',
        'subscription_expires_at'     => 'date',
        'latitude'                    => 'float',
        'longitude'                   => 'float',
        'cancellation_deadline_hours' => 'integer',
    ];

    // =====================
    // Relationships
    // =====================

    /** المالك/صاحب النادي (owner_id → users) */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /** كل المستخدمين المنتسبين لهذا النادي (موظفون + مالك) */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** الملاعب الخاصة بهذا النادي */
    public function pitches(): HasMany
    {
        return $this->hasMany(Pitch::class);
    }

    /** كل الحجوزات التي تمت في هذا النادي */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** كل المدفوعات الخاصة بحجوزات هذا النادي */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** الجداول الأسبوعية الثابتة في هذا النادي */
    public function recurringSchedules(): HasMany
    {
        return $this->hasMany(RecurringSchedule::class);
    }

    /** فواتير ومدفوعات الاشتراك المدفوعة من هذا النادي */
    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    // =====================
    // Media Collections
    // =====================
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }
}
