<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'booking_code',
        'tenant_id',
        'pitch_id',
        'pitch_slot_id',
        'user_id',
        'recurring_schedule_id',
        'customer_name',
        'customer_phone',
        'booking_type',
        'unit_price',
        'total_price',
        'deposit_amount',
        'paid_amount',
        'remaining_amount',
        'commission_amount',
        'status',
        'cancellation_reason',
        'cancelled_at',
    ];

    protected $casts = [
        'unit_price'        => 'decimal:2',
        'total_price'       => 'decimal:2',
        'deposit_amount'    => 'decimal:2',
        'paid_amount'       => 'decimal:2',
        'remaining_amount'  => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'cancelled_at'      => 'datetime',
    ];

    // =====================
    // Relationships
    // =====================

    /** النادي الذي تم الحجز فيه */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** الملعب المحجوز */
    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }

    /** الفترة الزمنية (slot) المحجوزة */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(PitchSlot::class, 'pitch_slot_id');
    }

    /**
     * المستخدم (اللاعب) الذي قام بالحجز أونلاين.
     * يكون null للحجوزات اليدوية التي سجّلها الكابتن بدون حساب.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الجدول الأسبوعي الثابت الذي ولّد هذا الحجز تلقائياً.
     * يكون null للحجوزات العادية الفردية.
     */
    public function recurringSchedule(): BelongsTo
    {
        return $this->belongsTo(RecurringSchedule::class);
    }

    /** كل الدفعات المرتبطة بهذا الحجز (عربون، دفعة كاملة، باقي، استرداد) */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
