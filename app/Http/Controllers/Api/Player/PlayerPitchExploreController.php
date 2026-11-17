<?php

namespace App\Http\Controllers\Api\Player;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\ExplorePitchesRequest;
use App\Http\Resources\Player\ExploreVenueResource;
use App\Http\Resources\Player\PlayerPitchDetailResource;
use App\Models\Pitch;
use App\Models\PitchSlot;
use App\Models\Tenant;
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
     * استكشاف وتصفح النوادي والملاعب بالأقرب مسافة والوقت المتاح
     * GET /api/v1/player/explore
     */
    public function explore(ExplorePitchesRequest $request): JsonResponse
    {
        $query = Tenant::query()
            ->where('tenants.status', 'active')
            ->whereNull('tenants.deleted_at')
            ->whereHas('pitches', function ($q) {
                $q->where('status', 'active');
            });

        // 1. حساب المسافة الجغرافية بالكيلومتر (Haversine Formula) وترتيب الأقرب أولاً
        $hasCoords = $request->filled(['latitude', 'longitude']);
        if ($hasCoords) {
            $lat = (float) $request->latitude;
            $lng = (float) $request->longitude;

            $haversine = "(6371 * acos(least(1.0, greatest(-1.0, cos(radians(?)) * cos(radians(tenants.latitude)) * cos(radians(tenants.longitude) - radians(?)) + sin(radians(?)) * sin(radians(tenants.latitude))))))";

            $query->select('tenants.*')
                ->selectRaw("{$haversine} AS distance", [$lat, $lng, $lat])
                ->whereNotNull('tenants.latitude')
                ->whereNotNull('tenants.longitude');

            if ($request->filled('max_distance')) {
                $query->havingRaw("distance <= ?", [(float) $request->max_distance]);
            }

            $query->orderBy('distance', 'asc');
        } else {
            $query->select('tenants.*')
                ->latest('tenants.id');
        }

        // 2. فلاتر المدينة والبحث
        if ($request->filled('city')) {
            $query->where('tenants.city', 'like', "%{$request->city}%");
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tenants.name', 'like', "%{$search}%")
                  ->orWhere('tenants.address', 'like', "%{$search}%")
                  ->orWhereHas('pitches', fn ($sub) => $sub->where('status', 'active')->where('name', 'like', "%{$search}%"));
            });
        }

        // 3. فلاتر الملاعب (نوع الرياضة، المقاس، الأرضية، السعر)
        if ($request->filled('sport_type')) {
            $query->whereHas('pitches', fn ($q) => $q->where('status', 'active')->where('sport_type', $request->sport_type));
        }

        if ($request->filled('court_size')) {
            $query->whereHas('pitches', fn ($q) => $q->where('status', 'active')->where('court_size', $request->court_size));
        }

        if ($request->filled('surface_type')) {
            $query->whereHas('pitches', fn ($q) => $q->where('status', 'active')->where('surface_type', $request->surface_type));
        }

        if ($request->has('is_indoor') && $request->input('is_indoor') !== null) {
            $query->whereHas('pitches', fn ($q) => $q->where('status', 'active')->where('is_indoor', $request->boolean('is_indoor')));
        }

        if ($request->filled('max_price')) {
            $maxPrice = (float) $request->max_price;
            $query->whereHas('pitches', function ($q) use ($maxPrice) {
                $q->where('status', 'active')
                  ->where(function ($sub) use ($maxPrice) {
                      $sub->where('online_day_price', '<=', $maxPrice)
                          ->orWhere('online_night_price', '<=', $maxPrice);
                  });
            });
        }

        // 4. فلترة التوفر باليوم والساعة (Availability Filter)
        $searchDate = $request->date;
        $searchTime = $request->time ? Carbon::parse($request->time)->format('H:i:s') : null;
        $timeFrom   = $request->time_from ? Carbon::parse($request->time_from)->format('H:i:s') : null;
        $timeTo     = $request->time_to ? Carbon::parse($request->time_to)->format('H:i:s') : null;

        if (($searchTime || ($timeFrom && $timeTo)) && !$searchDate) {
            $searchDate = now()->toDateString();
        }

        if ($searchDate) {
            $query->whereHas('pitches.slots', function ($q) use ($searchDate, $searchTime, $timeFrom, $timeTo) {
                $q->availableOnline()->where('date', $searchDate);

                if ($searchTime) {
                    $q->where('start_time', $searchTime);
                } elseif ($timeFrom && $timeTo) {
                    $q->where('start_time', '>=', $timeFrom)
                      ->where('start_time', '<=', $timeTo);
                }
            });
        }

        // تحميل العلاقات بكفاءة وبدون إرجاع الساعات (لتقليل حجم الـ Payload وسرعة الاستجابة)
        $query->with([
            'media',
            'pitches' => function ($q) use ($request, $searchDate, $searchTime, $timeFrom, $timeTo) {
                $q->where('status', 'active')->with('media');

                if ($request->filled('sport_type')) {
                    $q->where('sport_type', $request->sport_type);
                }

                if ($searchDate) {
                    $q->whereHas('slots', function ($slotQ) use ($searchDate, $searchTime, $timeFrom, $timeTo) {
                        $slotQ->availableOnline()->where('date', $searchDate);
                        if ($searchTime) {
                            $slotQ->where('start_time', $searchTime);
                        } elseif ($timeFrom && $timeTo) {
                            $slotQ->where('start_time', '>=', $timeFrom)->where('start_time', '<=', $timeTo);
                        }
                    });
                }
            },
        ]);

        $perPage = (int) $request->input('per_page', 15);
        $venues = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => ExploreVenueResource::collection($venues)->response()->getData(true),
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
