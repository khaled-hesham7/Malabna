<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'pitch_id',
        'user_id',
        'customer_name',
        'customer_phone',
        'booking_type',
        'day_of_week',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'agreed_price',
        'status',
        'notes',
    ];

    protected $casts = [
        'day_of_week'  => 'integer',
        'agreed_price' => 'decimal:2',
        'start_date'   => 'date',
        'end_date'     => 'date',
    ];

    // =====================
    // Relationships
    // =====================

    /** النادي المالك لهذا الجدول الأسبوعي */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** الملعب الذي يُطبَّق عليه هذا الجدول الأسبوعي */
    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }

    /**
     * المستخدم (اللاعب) المرتبط بهذا الجدول الأسبوعي.
     * يكون null للحجوزات الثابتة المسجّلة يدوياً بدون حساب أونلاين.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الفترات الزمنية (slots) التي تم توليدها تلقائياً من هذا الجدول الأسبوعي.
     * مفيدة لمعرفة كل الفترات المستقبلية المحجوزة لهذا الحجز الثابت.
     */
    public function slots(): HasMany
    {
        return $this->hasMany(PitchSlot::class);
    }

    /**
     * الحجوزات المرتبطة بهذا الجدول الأسبوعي.
     * مفيدة لتتبع سجل الدفعات لكل أسبوع من الحجز الثابت.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
