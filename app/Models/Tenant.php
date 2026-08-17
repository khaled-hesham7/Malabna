<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Tenant extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'company_name',
        'city',
        'phone',
        'address',
        'latitude',
        'longitude',
        'subscription_price',
        'subscription_expires_at',
        'commission_type',
        'commission_rate',
        'status',
    ];

    protected $casts = [
        'subscription_price'      => 'decimal:2',
        'commission_rate'         => 'decimal:2',
        'subscription_expires_at' => 'date',
        'latitude'                => 'float',
        'longitude'               => 'float',
    ];

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function pitches(): HasMany
    {
        return $this->hasMany(Pitch::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function recurringSchedules(): HasMany
    {
        return $this->hasMany(RecurringSchedule::class);
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }
}
