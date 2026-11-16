<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Http\Resources\Staff\StaffResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PartnerStaffController extends Controller
{
    /**
     * قائمة الموظفين التابعين لمنشأة المالك الحالي
     * GET /api/v1/partner/staff
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $staff = User::where('tenant_id', $tenantId)
            ->role('tenant_staff')
            ->with(['roles', 'permissions', 'media'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data'    => StaffResource::collection($staff),
        ]);
    }

    /**
     * جلب قائمة الصلاحيات المتاحة في السيستم للموظفين (لعرضها كـ Checkboxes في الفرونت)
     * GET /api/v1/partner/staff/permissions-list
     */
    public function permissionsList(): JsonResponse
    {
        $permissionLabels = [
            'slots.view'               => ['label' => 'عرض جدول الساعات والمواعيد', 'category' => 'جدول المواعيد'],
            'slots.toggle_status'      => ['label' => 'قفل أو فتح ساعة معينة', 'category' => 'جدول المواعيد'],
            'slots.toggle_day'         => ['label' => 'قفل أو فتح يوم كامل للملعب', 'category' => 'جدول المواعيد'],
            'slots.update_price'       => ['label' => 'تعديل أسعار الساعات وتثبيتها', 'category' => 'جدول المواعيد'],

            'bookings.view'            => ['label' => 'عرض سجل الحجوزات', 'category' => 'الحجوزات'],
            'bookings.manual_create'   => ['label' => 'تسجيل حجز كاش/يدوي من أرض الملعب', 'category' => 'الحجوزات'],
            'bookings.cancel'          => ['label' => 'إلغاء حجز قائم وإعادة فتح الساعة', 'category' => 'الحجوزات'],

            'recurring.view'           => ['label' => 'عرض الحجوزات الأسبوعية الثابتة', 'category' => 'الحجوزات الثابتة'],
            'recurring.create'         => ['label' => 'تثبيت حجز أسبوعي جديد', 'category' => 'الحجوزات الثابتة'],
            'recurring.update'         => ['label' => 'تعديل بيانات الحجز الثابت', 'category' => 'الحجوزات الثابتة'],
            'recurring.pause'          => ['label' => 'إيقاف مؤقت أو استئناف الحجز الثابت', 'category' => 'الحجوزات الثابتة'],
            'recurring.release_single' => ['label' => 'اعتذار عن أسبوع واحد لحجز ثابت', 'category' => 'الحجوزات الثابتة'],
            'recurring.cancel'         => ['label' => 'إلغاء الحجز الثابت نهائياً', 'category' => 'الحجوزات الثابتة'],

            'pitches.view'             => ['label' => 'عرض الملاعب', 'category' => 'الملاعب'],
            'pitches.manage'           => ['label' => 'إضافة وتعديل وحذف الملاعب', 'category' => 'الملاعب'],

            'reports.financial_view'   => ['label' => 'الاطلاع على التقارير المالية والإيرادات', 'category' => 'التقارير المالية'],
        ];

        $availablePermissions = Permission::whereIn('name', array_keys($permissionLabels))
            ->get()
            ->map(function ($perm) use ($permissionLabels) {
                return [
                    'name'        => $perm->name,
                    'label'       => $permissionLabels[$perm->name]['label'] ?? $perm->name,
                    'category'    => $permissionLabels[$perm->name]['category'] ?? 'عام',
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $availablePermissions,
        ]);
    }

    /**
     * إنشاء حساب موظف جديد وربطه بالمنشأة وتحديد صلاحياته
     * POST /api/v1/partner/staff
     */
    public function store(StoreStaffRequest $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        return DB::transaction(function () use ($request, $tenantId) {
            $user = User::create([
                'tenant_id' => $tenantId,
                'name'      => $request->name,
                'phone'     => $request->phone,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'status'    => 'active',
            ]);

            // إعطاء دور موظف المنشأة
            $staffRole = Role::firstOrCreate(['name' => 'tenant_staff', 'guard_name' => 'web']);
            $user->assignRole($staffRole);

            // إسناد الصلاحيات المحددة فقط من قبل المالك (Zero Trust: لا صلاحيات إلا ما يحدده المالك صراحة)
            $permissions = $request->input('permissions', []);
            $user->syncPermissions($permissions);

            // رفع الصورة الشخصية إن وُجدت
            if ($request->hasFile('avatar')) {
                $user->addMedia($request->file('avatar'))->toMediaCollection('avatar');
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء حساب الموظف وتعيين صلاحياته بنجاح.',
                'data'    => new StaffResource($user->fresh(['roles', 'permissions', 'media'])),
            ], 201);
        });
    }

    /**
     * عرض تفاصيل موظف محدد
     * GET /api/v1/partner/staff/{staff}
     */
    public function show(Request $request, User $staff): JsonResponse
    {
        $this->authorizeStaffAccess($request, $staff);

        $staff->load(['roles', 'permissions', 'media']);

        return response()->json([
            'success' => true,
            'data'    => new StaffResource($staff),
        ]);
    }

    /**
     * تعديل بيانات وصلاحيات الموظف
     * PUT/PATCH /api/v1/partner/staff/{staff}
     */
    public function update(UpdateStaffRequest $request, User $staff): JsonResponse
    {
        $this->authorizeStaffAccess($request, $staff);

        return DB::transaction(function () use ($request, $staff) {
            $data = $request->only(['name', 'phone', 'email', 'status']);

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $staff->update($data);

            // تحديث الصلاحيات إن أُرسلت
            if ($request->has('permissions') && is_array($request->permissions)) {
                $staff->syncPermissions($request->permissions);
            }

            // رفع أو استبدال الصورة الشخصية إن وُجدت
            if ($request->hasFile('avatar')) {
                $staff->clearMediaCollection('avatar');
                $staff->addMedia($request->file('avatar'))->toMediaCollection('avatar');
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات وصلاحيات الموظف بنجاح.',
                'data'    => new StaffResource($staff->fresh(['roles', 'permissions', 'media'])),
            ]);
        });
    }

    /**
     * تجميد أو إعادة تفعيل حساب الموظف بضغطة زر
     * PATCH /api/v1/partner/staff/{staff}/toggle-status
     */
    public function toggleStatus(Request $request, User $staff): JsonResponse
    {
        $this->authorizeStaffAccess($request, $staff);

        $newStatus = $staff->status === 'active' ? 'inactive' : 'active';
        $staff->update(['status' => $newStatus]);

        // إذا تم تجميده، نحذف توكنز جلساته فوراً لمنعه من الاستمرار
        if ($newStatus === 'inactive') {
            $staff->tokens()->delete();
        }

        $statusMsg = $newStatus === 'active' ? 'تفعيل' : 'تجميد وتعطيل';

        return response()->json([
            'success' => true,
            'message' => "تم {$statusMsg} حساب الموظف بنجاح.",
            'data'    => new StaffResource($staff->fresh(['roles', 'permissions', 'media'])),
        ]);
    }

    /**
     * حذف حساب الموظف
     * DELETE /api/v1/partner/staff/{staff}
     */
    public function destroy(Request $request, User $staff): JsonResponse
    {
        $this->authorizeStaffAccess($request, $staff);

        // حذف توكنز جلسات الموظف وحذف الحساب
        $staff->tokens()->delete();
        $staff->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف حساب الموظف بنجاح.',
        ]);
    }

    /**
     * التحقق الأمني: التأكد من أن الموظف يتبع نفس منشأة المالك الحالي وأنه موظف وليس المالك نفسه
     */
    protected function authorizeStaffAccess(Request $request, User $staff): void
    {
        $ownerTenantId = $request->user()->tenant_id;

        if (!$ownerTenantId || (int) $staff->tenant_id !== (int) $ownerTenantId || !$staff->hasRole('tenant_staff')) {
            abort(403, 'غير مصرح لك بالوصول لبيانات هذا الموظف.');
        }
    }
}

