<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes, HasApiTokens, InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // =====================
    // Relationships
    // =====================

    /** النادي/المجمع الذي ينتمي إليه الموظف أو المالك (null للـ super_admin والـ customer) */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** الحجوزات التي قام بها هذا المستخدم (أونلاين) */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** الجداول الأسبوعية الثابتة المرتبطة بهذا المستخدم */
    public function recurringSchedules(): HasMany
    {
        return $this->hasMany(RecurringSchedule::class);
    }

    /** المدفوعات التي سجّلها هذا الموظف/المسؤول يدوياً */
    public function recordedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'created_by_user_id');
    }

    /** مدفوعات الاشتراكات التي أنشأها هذا المستخدم (super_admin) */
    public function recordedSubscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class, 'created_by_user_id');
    }
}
