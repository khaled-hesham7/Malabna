<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveTenantRequest;
use App\Http\Requests\Admin\RejectTenantRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminTenantApprovalController extends Controller
{
    /**
     * List all venue partner applications filtered by status
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status');

        $query = Tenant::with(['owner', 'media']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $tenants = $query->latest()->paginate(15);

        // إحصائيات عدد الملاعب في كل حالة
        $counts = [
            'all'              => Tenant::count(),
            'pending'          => Tenant::where('status', 'pending')->count(),
            'awaiting_payment' => Tenant::where('status', 'awaiting_payment')->count(),
            'active'           => Tenant::where('status', 'active')->count(),
            'rejected'         => Tenant::where('status', 'rejected')->count(),
            'suspended'        => Tenant::where('status', 'suspended')->count(),
        ];

        return response()->json([
            'success' => true,
            'counts'  => $counts,
            'data'    => TenantResource::collection($tenants)->response()->getData(true),
        ]);
    }

    /**
     * Show detailed profile and application info of a specific tenant
     */
    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load(['owner', 'media']);

        return response()->json([
            'success' => true,
            'data'    => new TenantResource($tenant),
        ]);
    }

    /**
     * Super Admin approves venue application and configures subscription and commission rates
     */
    public function approve(ApproveTenantRequest $request, Tenant $tenant): JsonResponse
    {

        return DB::transaction(function () use ($request, $tenant) {
            $hasSubscriptionFee = (float) $request->subscription_price > 0;
            $newStatus = $hasSubscriptionFee ? 'awaiting_payment' : 'active';

            $commissionType = trim($request->commission_type);

            $tenant->update([
                'subscription_price'          => $request->subscription_price,
                'subscription_billing_period' => $request->subscription_billing_period ?? 'monthly',
                'subscription_starts_at'      => null,
                'subscription_expires_at'     => null,
                'commission_type'             => $commissionType,
                'commission_rate'             => $request->commission_rate ?? 0,
                'status'                      => $newStatus,
                'rejection_reason'            => null,
            ]);

            // تفعيل حساب المالك مباشرة فقط إذا كانت الخطة مجانية (عمولة فقط)، وإلا ينتظر سداد الاشتراك
            if ($tenant->owner) {
                $tenant->owner->update([
                    'status' => $hasSubscriptionFee ? 'inactive' : 'active',
                ]);
            }

            $message = $hasSubscriptionFee
                ? 'Venue application approved. The venue status is now awaiting subscription payment before activation.'
                : 'Venue application approved and activated immediately (Commission-only model).';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data'    => new TenantResource($tenant->load('owner')),
            ]);
        });
    }

    /**
     * Super Admin rejects venue application with specified reason
     */
    public function reject(RejectTenantRequest $request, Tenant $tenant): JsonResponse
    {

        $tenant->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Venue application rejected successfully with specified reason.',
            'data'    => new TenantResource($tenant),
        ]);
    }
}
