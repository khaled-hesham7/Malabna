<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\LoginResource;
use App\Http\Resources\Auth\UserProfileResource;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Unified Login endpoint for all users (Super Admin, Venue Owner, Staff, Player).
     * Accepts Phone Number OR Email + Password.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $loginInput = $request->login;

        // البحث برقم الهاتف أو بالبريد الإلكتروني
        $user = User::where('phone', $loginInput)
            ->orWhere('email', $loginInput)
            ->with(['tenant', 'roles', 'permissions'])
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone/email or password.',
            ], 401);
        }

        // فحص حالة الحساب إن كان محظوراً
        if ($user->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been banned. Please contact platform support.',
            ], 403);
        }

        // فحص حساب صاحب المنشأة أو الموظفين بالاعتماد على Spatie Roles
        if ($user->hasRole(['tenant_owner', 'tenant_staff'])) {
            $tenant = $user->tenant;

            if (! $tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'No venue affiliated with this account.',
                ], 403);
            }

            if ($tenant->status === 'pending') {
                return response()->json([
                    'success' => false,
                    'status'  => 'pending',
                    'message' => 'Your venue application is pending Super Admin review.',
                ], 403);
            }

            // فحص انتهاء صلاحية الاشتراك تلقائياً وتحويله لمعلق (suspended)
            $hasExpiredSubscription = (float) $tenant->subscription_price > 0 
                && $tenant->subscription_expires_at 
                && $tenant->subscription_expires_at->isPast();

            if ($hasExpiredSubscription && $tenant->status === 'active') {
                $tenant->update(['status' => 'suspended']);
                $tenant->refresh();
            }

            if ($tenant->status === 'awaiting_payment' || $tenant->status === 'suspended') {
                // السماح للمالك بالدخول فقط لصفحة سداد الاشتراك / التجديد
                $user->tokens()->delete();
                $token = $user->createToken('web_session_token')->plainTextToken;

                $isSuspended = $tenant->status === 'suspended';

                return response()->json([
                    'success' => true,
                    'status'  => $tenant->status,
                    'message' => $isSuspended 
                        ? 'Your venue subscription has expired. Please renew your subscription to reactivate your dashboard.'
                        : 'Application approved! Please complete your subscription payment to activate your dashboard.',
                    'data'    => [
                        'user'         => new UserResource($user),
                        'token'        => $token,
                        'redirect_url' => '/partner/subscription/checkout',
                        'subscription' => [
                            'price'          => $tenant->subscription_price,
                            'billing_period' => $tenant->subscription_billing_period,
                            'currency'       => $tenant->currency ?? 'EGP',
                            'expires_at'     => $tenant->subscription_expires_at?->toDateTimeString(),
                        ],
                        'tenant'       => new TenantResource($tenant),
                    ],
                ]);
            }

            if ($tenant->status === 'rejected') {
                return response()->json([
                    'success'          => false,
                    'status'           => 'rejected',
                    'rejection_reason' => $tenant->rejection_reason,
                    'message'          => 'Your venue application was rejected. Reason: ' . ($tenant->rejection_reason ?? 'Not specified'),
                ], 403);
            }

            if ($user->status === 'inactive') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your staff account is currently inactive. Contact your venue manager.',
                ], 403);
            }
        }

        // فحص الحساب العادي لو كان inactive
        if ($user->hasRole('player') && $user->status === 'inactive') {
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive.',
            ], 403);
        }

        // مسح التوكنز القديمة وتوليد توكن جلسة جديدة
        $user->tokens()->delete();
        $token = $user->createToken('web_session_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully.',
            'data'    => new LoginResource([
                'user'   => $user,
                'token'  => $token,
                'tenant' => $user->tenant,
            ]),
        ]);
    }

    /**
     * Get Authenticated User Profile
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles', 'permissions', 'tenant']);

        return response()->json([
            'success' => true,
            'data'    => new UserProfileResource($user),
        ]);
    }

    /**
     * Logout and revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
