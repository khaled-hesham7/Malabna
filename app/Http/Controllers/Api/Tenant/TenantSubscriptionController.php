<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\PaySubscriptionRequest;
use App\Http\Resources\SubscriptionPaymentResource;
use App\Http\Resources\TenantResource;
use App\Models\SubscriptionPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantSubscriptionController extends Controller
{
    /**
     * Show subscription details and billing info for the tenant
     */
    public function show(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'No venue found for this account.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'tenant'                  => new TenantResource($tenant),
                'subscription_price'      => $tenant->subscription_price,
                'billing_period'          => $tenant->subscription_billing_period,
                'currency'                => $tenant->currency ?? 'EGP',
                'status'                  => $tenant->status,
                'commission_type'         => $tenant->commission_type,
                'commission_rate'         => $tenant->commission_rate,
            ],
        ]);
    }

    /**
     * Complete and confirm subscription payment (Hybrid or Subscription-only model)
     * Simulates payment gateway callback/confirmation and activates the venue
     */
    public function pay(PaySubscriptionRequest $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $user->tenant;

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'No venue found for this account.',
            ], 404);
        }

        if ($tenant->status === 'active' && $tenant->subscription_expires_at && $tenant->subscription_expires_at->isFuture()) {
            return response()->json([
                'success' => false,
                'message' => 'Your subscription is already active and up to date.',
            ], 400);
        }

        return DB::transaction(function () use ($request, $user, $tenant) {
            $billingPeriod = $tenant->subscription_billing_period;
            $startDate = now();
            $expiresDate = $billingPeriod === 'yearly' ? now()->addYear() : now()->addMonth();

            // 1. تسجيل دفعة الاشتراك في جدول subscription_payments
            $payment = SubscriptionPayment::create([
                'tenant_id'            => $tenant->id,
                'amount'               => $tenant->subscription_price,
                'currency'             => $tenant->currency ?? 'EGP',
                'payment_method'       => $request->payment_method,
                'transaction_id'       => $request->transaction_id ?? ('SUB-PAY-' . strtoupper(Str::random(10))),
                'billing_period_start' => $startDate->toDateString(),
                'billing_period_end'   => $expiresDate->toDateString(),
                'status'               => 'completed',
                'created_by_user_id'   => $user->id,
                'notes'                => 'Platform subscription fee settled successfully.',
                'paid_at'              => now(),
            ]);

            // 2. تفعيل النادي رسمياً
            $tenant->update([
                'status'                  => 'active',
                'subscription_starts_at'  => $startDate,
                'subscription_expires_at' => $expiresDate,
            ]);

            // 3. تفعيل حساب المالك
            $user->update(['status' => 'active']);

            return response()->json([
                'success' => true,
                'message' => 'Subscription payment completed successfully! Your venue and dashboard are now fully active.',
                'data'    => [
                    'redirect_url' => '/partner/dashboard',
                    'tenant'       => new TenantResource($tenant->fresh()),
                    'payment'      => new SubscriptionPaymentResource($payment),
                ],
            ]);
        });
    }
}
