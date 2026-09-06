<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'amount',
        'currency',
        'payment_method',
        'transaction_id',
        'billing_period_start',
        'billing_period_end',
        'status',
        'created_by_user_id',
        'notes',
        'paid_at',
        'payload',
    ];

    protected $casts = [
        'amount'               => 'decimal:2',
        'billing_period_start' => 'date',
        'billing_period_end'   => 'date',
        'paid_at'              => 'datetime',
        'payload'              => 'array',
    ];

    // =====================
    // Relationships
    // =====================

    /** النادي الذي سدّد هذه الدفعة الاشتراكية */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * الـ Super Admin الذي أنشأ/وثّق هذه الدفعة.
     * يكون null إذا تمت عبر بوابة دفع أونلاين تلقائياً.
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
