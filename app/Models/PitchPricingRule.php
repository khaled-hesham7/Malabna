<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PitchPricingRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pitch_id',
        'name',
        'day_of_week',
        'start_time',
        'end_time',
        'manual_price_per_hour',
        'online_price_per_hour',
        'min_deposit_type',
        'min_deposit_amount',
        'status',
    ];

    protected $casts = [
        'day_of_week'           => 'integer',
        'manual_price_per_hour' => 'decimal:2',
        'online_price_per_hour' => 'decimal:2',
        'min_deposit_amount'    => 'decimal:2',
    ];

    // =====================
    // Relationships
    // =====================

    /** الملعب الذي تنتمي إليه هذه القاعدة */
    public function pitch(): BelongsTo
    {
        return $this->belongsTo(Pitch::class);
    }

    /** النادي المالك للملعب (عبر الملعب) */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id')
            ->through('pitch'); // through pitch → pitch.tenant_id
        // ملاحظة: استخدم $this->pitch->tenant للوصول المباشر في الكود
        // أو استخدم HasOneThrough مع تحديد المفاتيح بدقة إذا احتجت eager loading
    }

    // =====================
    // Scopes
    // =====================

    /** القواعد النشطة فقط */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'active');
    }

    // =====================
    // Helpers
    // =====================

    /**
     * احسب قيمة العربون المطلوبة بناءً على إجمالي السعر.
     * percentage: نسبة مئوية من الإجمالي
     * fixed: مبلغ ثابت (بحد أقصى الإجمالي)
     * full: الدفع الكامل
     */
    public function calculateDepositAmount(float $totalPrice): float
    {
        return match ($this->min_deposit_type) {
            'percentage' => round(($totalPrice * $this->min_deposit_amount) / 100, 2),
            'fixed'      => min($this->min_deposit_amount, $totalPrice),
            'full'       => $totalPrice,
            default      => $totalPrice,
        };
    }
}
