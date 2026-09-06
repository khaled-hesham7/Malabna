<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class PitchSlot extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pitch_id',
        'date',
        'start_time',
        'end_time',
        'manual_price',
        'online_price',
        'status',
        'is_recurring',
        'recurring_customer_name',
        'recurring_schedule_id',
        'locked_by_user_id',
        'locked_until',
    ];

    protected $casts = [
        'date'         => 'date',
        'manual_price' => 'decimal:2',
        'online_price' => 'decimal:2',
        'is_recurring' => 'boolean',
        'locked_until' => 'datetime',
    ];

    // =====================
    // Relationships
    // =====================

    /** الملعب الذي تنتمي إليه هذه الفترة */
    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }

    /**
     * المستخدم الذي قفل هذه الفترة مؤقتاً أثناء عملية الدفع الأونلاين.
     * يُستخدم لمنع تعارض الحجوزات المتزامنة (Race Condition).
     */
    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    /** الحجز المرتبط بهذه الفترة (إن وُجد) */
    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class, 'pitch_slot_id');
    }

    /**
     * جدول الحجز الأسبوعي الثابت الذي ولّد هذه الفترة تلقائياً.
     * يكون null للفترات العادية غير الدورية.
     */
    public function recurringSchedule(): BelongsTo
    {
        return $this->belongsTo(RecurringSchedule::class);
    }

    // =====================
    // Scopes
    // =====================

    /**
     * الفترات المتاحة للحجز عبر التطبيق (أونلاين).
     * تشمل: available, blocked_manual (مفتوحة للأونلاين لكن مقفولة لليدوي).
     * تستثني الفترات المقفولة مؤقتاً أثناء الدفع (locked_until لم تنته بعد).
     */
    public function scopeAvailableOnline(Builder $query): Builder
    {
        return $query->whereIn('status', ['available', 'blocked_manual'])
            ->where(function ($q) {
                $q->whereNull('locked_until')
                    ->orWhere('locked_until', '<', now());
            });
    }

    /**
     * الفترات التي انتهت مدة قفلها المؤقت (للـ Cleanup Cron Job).
     * هذه الفترات يجب إعادة تعيين locked_by_user_id و locked_until لـ null.
     */
    public function scopeExpiredLocks(Builder $query): Builder
    {
        return $query->whereNotNull('locked_until')
            ->where('locked_until', '<', now());
    }
}
