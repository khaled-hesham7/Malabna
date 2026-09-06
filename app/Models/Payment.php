<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'created_by_user_id',
        'payment_code',
        'payment_type',
        'payment_method',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'notes',
        'paid_at',
        'payload',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'paid_at' => 'datetime',
        'payload' => 'array',
    ];

    // =====================
    // Relationships
    // =====================

    /** الحجز المرتبط بهذه الدفعة */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** النادي المرتبط بهذه الدفعة (للتقارير المالية السريعة بدون join) */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * الموظف/المسؤول الذي سجّل هذه الدفعة يدوياً في اللوحة.
     * يكون null للمدفوعات الأونلاين التي تُسجَّل تلقائياً عبر webhook.
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
