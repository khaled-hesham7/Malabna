<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\RecurringScheduleFilterRequest;
use App\Http\Requests\Partner\StoreRecurringScheduleRequest;
use App\Http\Requests\Partner\UpdateRecurringScheduleRequest;
use App\Http\Resources\PitchSlotResource;
use App\Http\Resources\RecurringScheduleResource;
use App\Models\Pitch;
use App\Models\PitchSlot;
use App\Models\RecurringSchedule;
use App\Services\RecurringScheduleService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerRecurringScheduleController extends Controller
{
    protected RecurringScheduleService $scheduleService;

    public function __construct(RecurringScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * قائمة الحجوزات الثابتة الخاصة بنادي الشريك
     * GET /api/v1/partner/recurring-schedules
     */
    public function index(RecurringScheduleFilterRequest $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد منشأة مرتبطة بهذا الحساب.',
            ], 403);
        }

        $query = RecurringSchedule::with(['pitch'])
            ->where('tenant_id', $tenant->id);

        if ($request->filled('pitch_id')) {
            $query->where('pitch_id', $request->pitch_id);
        }

        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $schedules = $query->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => RecurringScheduleResource::collection($schedules),
        ]);
    }

    /**
     * إنشاء حجز أسبوعي ثابت جديد ومستمر للأبد (أو بمدة محددة)
     * POST /api/v1/partner/recurring-schedules
     */
    public function store(StoreRecurringScheduleRequest $request): JsonResponse
    {
        $tenant = $request->user()->tenant;
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد منشأة مرتبطة بهذا الحساب.',
            ], 403);
        }

        $pitch = Pitch::where('id', $request->pitch_id)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (!$pitch) {
            return response()->json([
                'success' => false,
                'message' => 'الملعب المحدد غير موجود أو غير تابع لمنشأتك.',
            ], 404);
        }

        try {
            $schedule = $this->scheduleService->createRecurringSchedule($tenant, $pitch, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء وتثبيت الحجز الدوري الأسبوعي بنجاح، وتسكينه على جدول الساعات القادمة.',
                'data'    => new RecurringScheduleResource($schedule->load('pitch')),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * تفاصيل حجز ثابت محدد
     * GET /api/v1/partner/recurring-schedules/{recurringSchedule}
     */
    public function show(Request $request, RecurringSchedule $recurringSchedule): JsonResponse
    {
        $this->authorizeTenantSchedule($request, $recurringSchedule);

        $recurringSchedule->load(['pitch', 'slots' => function ($q) {
            $q->where('date', '>=', now()->toDateString())->orderBy('date')->limit(12);
        }]);

        return response()->json([
            'success' => true,
            'data'    => new RecurringScheduleResource($recurringSchedule),
        ]);
    }

    /**
     * تعديل بيانات الحجز الثابت وتحديث الفترات المستقبلية
     * PUT/PATCH /api/v1/partner/recurring-schedules/{recurringSchedule}
     */
    public function update(UpdateRecurringScheduleRequest $request, RecurringSchedule $recurringSchedule): JsonResponse
    {
        $this->authorizeTenantSchedule($request, $recurringSchedule);

        try {
            $updated = $this->scheduleService->updateRecurringSchedule($recurringSchedule, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات الحجز الثابت وتعديل الفترات المستقبلية بنجاح.',
                'data'    => new RecurringScheduleResource($updated->load('pitch')),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * إيقاف مؤقت أو استئناف الحجز الثابت
     * PATCH /api/v1/partner/recurring-schedules/{recurringSchedule}/toggle-pause
     */
    public function togglePause(Request $request, RecurringSchedule $recurringSchedule): JsonResponse
    {
        $this->authorizeTenantSchedule($request, $recurringSchedule);

        $newStatus = $this->scheduleService->togglePauseRecurringSchedule($recurringSchedule);

        $message = $newStatus === 'paused'
            ? 'تم إيقاف الحجز الثابت مؤقتاً وتحرير الفترات المستقبلية لتصبح متاحة للحجز.'
            : 'تم استئناف الحجز الثابت وتسكينه على الفترات القادمة مجدداً.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => new RecurringScheduleResource($recurringSchedule->fresh(['pitch'])),
        ]);
    }

    /**
     * إلغاء الحجز الثابت بالكامل وتحرير جميع ساعاته المستقبلية
     * DELETE /api/v1/partner/recurring-schedules/{recurringSchedule}
     */
    public function destroy(Request $request, RecurringSchedule $recurringSchedule): JsonResponse
    {
        $this->authorizeTenantSchedule($request, $recurringSchedule);

        $this->scheduleService->cancelRecurringSchedule($recurringSchedule);
        $recurringSchedule->delete(); // Soft delete

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الحجز الثابت بنجاح وإتاحة جميع الفترات المستقبلية للحجز.',
        ]);
    }

    /**
     * اعتذار/إلغاء أسبوع واحد فقط لحجز ثابت بتمرير التاريخ مباشرة من شاشة الحجوزات الثابتة
     * POST /api/v1/partner/recurring-schedules/{recurringSchedule}/release-date
     */
    public function releaseDate(Request $request, RecurringSchedule $recurringSchedule): JsonResponse
    {
        $this->authorizeTenantSchedule($request, $recurringSchedule);

        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ], [
            'date.required'    => 'تاريخ الأسبوع المراد فتحه مطلوب.',
            'date.date_format' => 'صيغة التاريخ يجب أن تكون بتنسيق YYYY-MM-DD.',
        ]);

        try {
            $slot = $this->scheduleService->releaseRecurringByDate($recurringSchedule, $request->date);

            return response()->json([
                'success' => true,
                'message' => "تم فتح فترة يوم ({$request->date}) للحجز بهذا الأسبوع فقط مع استمرار الحجز الثابت لباقي الأسابيع.",
                'data'    => new PitchSlotResource($slot),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * التحقق من صلاحية وصول الشريك للحجز الثابت
     */
    protected function authorizeTenantSchedule(Request $request, RecurringSchedule $schedule): void
    {
        $tenantId = $request->user()->tenant_id;
        if (!$tenantId || (int) $schedule->tenant_id !== (int) $tenantId) {
            abort(403, 'غير مصرح لك بالوصول لهذا الحجز الثابت.');
        }
    }

    /**
     * التحقق من صلاحية وصول الشريك للملعب
     */
    protected function authorizeTenantPitch(Request $request, Pitch $pitch): void
    {
        $tenantId = $request->user()->tenant_id;
        if (!$tenantId || (int) $pitch->tenant_id !== (int) $tenantId) {
            abort(403, 'غير مصرح لك بالوصول لهذا الملعب.');
        }
    }
}
