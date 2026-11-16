<?php

namespace App\Http\Controllers\Api\Player;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\ExplorePitchesRequest;
use App\Http\Resources\Player\ExplorePitchResource;
use App\Http\Resources\Player\PlayerPitchDetailResource;
use App\Models\Pitch;
use App\Models\PitchSlot;
use App\Services\PitchSlotService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerPitchExploreController extends Controller
{
    protected PitchSlotService $slotService;

    public function __construct(PitchSlotService $slotService)
    {
        $this->slotService = $slotService;
    }

    /**
     * استكشاف والبحث في الملاعب بالأقرب مسافة والوقت المتاح
     * GET /api/v1/player/explore
     */
    public function explore(ExplorePitchesRequest $request): JsonResponse
    {
        $query = Pitch::query()
            ->join('tenants', 'pitches.tenant_id', '=', 'tenants.id')
            ->where('pitches.status', 'active')
            ->where('tenants.status', 'active')
            ->whereNull('tenants.deleted_at')
            ->whereNull('pitches.deleted_at');

        // 1. حساب المسافة الجغرافية (Haversine Formula بالكيلومتر)
        $hasCoords = $request->filled(['latitude', 'longitude']);
        if ($hasCoords) {
            $lat = (float) $request->latitude;
            $lng = (float) $request->longitude;

            // معادلة هافيرسين لحساب المسافة بدقة ومنع أخطاء تجاوز النطاق (ACos Domain error)
            $haversine = "(6371 * acos(least(1.0, greatest(-1.0, cos(radians(?)) * cos(radians(tenants.latitude)) * cos(radians(tenants.longitude) - radians(?)) + sin(radians(?)) * sin(radians(tenants.latitude))))))";

            $query->select('pitches.*')
                ->selectRaw("{$haversine} AS distance", [$lat, $lng, $lat])
                ->whereNotNull('tenants.latitude')
                ->whereNotNull('tenants.longitude');

            // تصفية بأقصى مسافة إذا تم تحديدها
            if ($request->filled('max_distance')) {
                $query->havingRaw("distance <= ?", [(float) $request->max_distance]);
            }

            // الترتيب من الأقرب للأبعد
            $query->orderBy('distance', 'asc');
        } else {
            $query->select('pitches.*')
                ->orderByDesc('pitches.id');
        }

        // 2. فلاتر الملعب العامة
        if ($request->filled('sport_type')) {
            $query->where('pitches.sport_type', $request->sport_type);
        }

        if ($request->filled('court_size')) {
            $query->where('pitches.court_size', $request->court_size);
        }

        if ($request->filled('surface_type')) {
            $query->where('pitches.surface_type', $request->surface_type);
        }

        if ($request->has('is_indoor') && $request->input('is_indoor') !== null) {
            $query->where('pitches.is_indoor', $request->boolean('is_indoor'));
        }

        if ($request->filled('city')) {
            $query->where('tenants.city', 'like', "%{$request->city}%");
        }

        if ($request->filled('max_price')) {
            $maxPrice = (float) $request->max_price;
            $query->where(function ($q) use ($maxPrice) {
                $q->where('pitches.online_day_price', '<=', $maxPrice)
                  ->orWhere('pitches.online_night_price', '<=', $maxPrice);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('pitches.name', 'like', "%{$search}%")
                  ->orWhere('tenants.name', 'like', "%{$search}%")
                  ->orWhere('tenants.address', 'like', "%{$search}%");
            });
        }

        // 3. فلترة الوقت والميعاد الفاضي (Availability Filter)
        $searchDate = $request->date;
        $searchTime = $request->time ? Carbon::parse($request->time)->format('H:i:s') : null;
        $timeFrom   = $request->time_from ? Carbon::parse($request->time_from)->format('H:i:s') : null;
        $timeTo     = $request->time_to ? Carbon::parse($request->time_to)->format('H:i:s') : null;

        // إذا حدد وقتاً ولم يحدد تاريخاً، نبحث في تاريخ اليوم تلقائياً
        if (($searchTime || ($timeFrom && $timeTo)) && !$searchDate) {
            $searchDate = now()->toDateString();
        }

        if ($searchDate) {
            $query->whereHas('slots', function ($q) use ($searchDate, $searchTime, $timeFrom, $timeTo) {
                $q->availableOnline()
                  ->where('date', $searchDate);

                if ($searchTime) {
                    $q->where('start_time', $searchTime);
                } elseif ($timeFrom && $timeTo) {
                    $q->where('start_time', '>=', $timeFrom)
                      ->where('start_time', '<=', $timeTo);
                }
            });
        }

        // تحميل العلاقات والساعات المتطابقة مع البحث
        $query->with([
            'tenant.media',
            'media',
            'slots' => function ($q) use ($searchDate, $searchTime, $timeFrom, $timeTo) {
                $q->availableOnline();
                if ($searchDate) {
                    $q->where('date', $searchDate);
                }
                if ($searchTime) {
                    $q->where('start_time', $searchTime);
                } elseif ($timeFrom && $timeTo) {
                    $q->where('start_time', '>=', $timeFrom)
                      ->where('start_time', '<=', $timeTo);
                }
                $q->orderBy('start_time');
            },
        ]);

        $perPage = (int) $request->input('per_page', 15);
        $pitches = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => ExplorePitchResource::collection($pitches)->response()->getData(true),
        ]);
    }

    /**
     * عرض تفاصيل ملعب كاملة للاعب
     * GET /api/v1/player/pitches/{pitch}
     */
    public function show(Request $request, Pitch $pitch): JsonResponse
    {
        $tenant = $pitch->tenant;

        if ($pitch->status !== 'active' || !$tenant || $tenant->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، هذا الملعب غير متاح حالياً.',
            ], 404);
        }

        // حساب المسافة إن أرسل اللاعب إحداثياته
        if ($request->filled(['latitude', 'longitude']) && $tenant->latitude && $tenant->longitude) {
            $lat = (float) $request->latitude;
            $lng = (float) $request->longitude;
            $pitch->distance = $this->calculateDistance($lat, $lng, (float) $tenant->latitude, (float) $tenant->longitude);
        }

        $pitch->load(['tenant.media', 'media']);

        return response()->json([
            'success' => true,
            'data'    => new PlayerPitchDetailResource($pitch),
        ]);
    }

    /**
     * عرض كل الساعات المتاحة للحجز أونلاين لملعب محدد في تاريخ معين
     * GET /api/v1/player/pitches/{pitch}/available-slots?date=YYYY-MM-DD
     */
    public function availableSlots(Request $request, Pitch $pitch): JsonResponse
    {
        $tenant = $pitch->tenant;

        if ($pitch->status !== 'active' || !$tenant || $tenant->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، هذا الملعب غير متاح حالياً.',
            ], 404);
        }

        $dateString = $request->query('date', now()->toDateString());
        $carbonDate = Carbon::parse($dateString);

        if ($carbonDate->startOfDay()->lt(now()->startOfDay())) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن استعراض فترات في أيام سابقة.',
            ], 422);
        }

        if ($carbonDate->startOfDay()->gt(now()->addDays(60)->startOfDay())) {
            return response()->json([
                'success' => false,
                'message' => 'أقصى فترة مسموح باستعراضها هي 60 يوماً مقدماً.',
            ], 422);
        }

        // توليد الساعات تلقائياً إن لم تكن متولدة لهذا اليوم
        $existingCount = PitchSlot::where('pitch_id', $pitch->id)
            ->where('date', $dateString)
            ->count();

        if ($existingCount === 0) {
            $this->slotService->generateSlotForDateRange($pitch, $dateString, $dateString);
        }

        // جلب الساعات المتاحة للأونلاين فقط (المفتوحة وغير المقفولة وغير المحجوزة)
        $availableSlots = PitchSlot::where('pitch_id', $pitch->id)
            ->where('date', $dateString)
            ->availableOnline()
            ->orderBy('start_time')
            ->get()
            ->map(function ($slot) {
                return [
                    'id'           => $slot->id,
                    'date'         => $slot->date?->toDateString(),
                    'start_time'   => substr($slot->start_time, 0, 5),
                    'end_time'     => substr($slot->end_time, 0, 5),
                    'time_label'   => substr($slot->start_time, 0, 5) . ' - ' . substr($slot->end_time, 0, 5),
                    'online_price' => (float) $slot->online_price,
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => [
                'pitch' => [
                    'id'   => $pitch->id,
                    'name' => $pitch->name,
                ],
                'date'            => $dateString,
                'available_count' => $availableSlots->count(),
                'slots'           => $availableSlots,
            ],
        ]);
    }

    /**
     * دالة مساعدة لحساب المسافة بين نقطتين بالكيلومتر
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos(min(max($dist, -1.0), 1.0));
        $dist = rad2deg($dist);
        return $dist * 60 * 1.1515 * 1.609344;
    }
}

