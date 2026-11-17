<?php

namespace App\Http\Controllers\Api\Player;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\ExplorePitchesRequest;
use App\Http\Resources\Player\ExploreVenueResource;
use App\Http\Resources\Player\PlayerPitchDetailResource;
use App\Models\Pitch;
use App\Models\PitchSlot;
use App\Models\Tenant;
use App\QueryFilters\Venue\PitchCourtSizeFilter;
use App\QueryFilters\Venue\PitchIndoorFilter;
use App\QueryFilters\Venue\PitchMaxPriceFilter;
use App\QueryFilters\Venue\PitchSportTypeFilter;
use App\QueryFilters\Venue\PitchSurfaceTypeFilter;
use App\QueryFilters\Venue\VenueAvailabilityFilter;
use App\QueryFilters\Venue\VenueDistanceSort;
use App\QueryFilters\Venue\VenueMaxDistanceFilter;
use App\QueryFilters\Venue\VenueSearchFilter;
use App\Services\PitchSlotService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class PlayerPitchExploreController extends Controller
{
    protected PitchSlotService $slotService;

    public function __construct(PitchSlotService $slotService)
    {
        $this->slotService = $slotService;
    }

    /**
     * استكشاف وتصفح النوادي والملاعب باستخدام Spatie QueryBuilder
     * GET /api/v1/player/explore
     */
    public function explore(ExplorePitchesRequest $request): JsonResponse
    {
        // 1. الاستعلام الأساسي: النوادي النشطة التي تحتوي على ملاعب نشطة
        $baseQuery = Tenant::query()
            ->where('tenants.status', 'active')
            ->whereNull('tenants.deleted_at')
            ->whereHas('pitches', function ($q) {
                $q->where('status', 'active');
            });

        // 2. إذا توفرت إحداثيات GPS، نقوم بحساب المسافة بالكيلومتر عبر Haversine
        $lat = $request->input('latitude', $request->input('filter.latitude'));
        $lng = $request->input('longitude', $request->input('filter.longitude'));
        $hasCoords = !blank($lat) && !blank($lng);

        if ($hasCoords) {
            $latFloat = (float) $lat;
            $lngFloat = (float) $lng;

            $haversine = "(6371 * acos(least(1.0, greatest(-1.0, cos(radians(?)) * cos(radians(tenants.latitude)) * cos(radians(tenants.longitude) - radians(?)) + sin(radians(?)) * sin(radians(tenants.latitude))))))";

            $baseQuery->select('tenants.*')
                ->selectRaw("{$haversine} AS distance", [$latFloat, $lngFloat, $latFloat])
                ->whereNotNull('tenants.latitude')
                ->whereNotNull('tenants.longitude');
        } else {
            $baseQuery->select('tenants.*');
        }

        // 3. بناء الفلاتر والترتيب عبر Spatie QueryBuilder
        $queryBuilder = QueryBuilder::for($baseQuery, $request)
            ->allowedFilters(
                AllowedFilter::partial('city'),
                AllowedFilter::custom('search', new VenueSearchFilter()),
                AllowedFilter::custom('sport_type', new PitchSportTypeFilter()),
                AllowedFilter::custom('court_size', new PitchCourtSizeFilter()),
                AllowedFilter::custom('surface_type', new PitchSurfaceTypeFilter()),
                AllowedFilter::custom('is_indoor', new PitchIndoorFilter()),
                AllowedFilter::custom('max_price', new PitchMaxPriceFilter()),
                AllowedFilter::custom('date', new VenueAvailabilityFilter()),
                AllowedFilter::callback('time', fn() => null),
                AllowedFilter::callback('time_from', fn() => null),
                AllowedFilter::callback('time_to', fn() => null),
                AllowedFilter::callback('latitude', fn() => null),
                AllowedFilter::callback('longitude', fn() => null),
                AllowedFilter::custom('max_distance', new VenueMaxDistanceFilter()),
            )
            ->allowedSorts(
                AllowedSort::custom('distance', new VenueDistanceSort()),
                AllowedSort::field('name'),
                AllowedSort::field('city'),
                AllowedSort::field('created_at'),
            );

        // ترتيب افتراضي: الأقرب مسافة إذا وُجدت الإحداثيات، أو الأحدث
        if (!$request->filled('sort')) {
            if ($hasCoords) {
                $queryBuilder->orderBy('distance', 'asc');
            } else {
                $queryBuilder->latest('tenants.id');
            }
        }

        // 4. تجهيز بارامترات التاريخ والوقت لفلترة الملاعب المحملة مسبقاً (Eager Loading)
        $searchDate = $request->input('date', $request->input('filter.date'));
        $searchTime = $request->input('time', $request->input('filter.time'));
        $timeFrom   = $request->input('time_from', $request->input('filter.time_from'));
        $timeTo     = $request->input('time_to', $request->input('filter.time_to'));
        $sportType  = $request->input('sport_type', $request->input('filter.sport_type'));

        $formattedTime = $searchTime ? Carbon::parse($searchTime)->format('H:i:s') : null;
        $formattedFrom = $timeFrom ? Carbon::parse($timeFrom)->format('H:i:s') : null;
        $formattedTo   = $timeTo ? Carbon::parse($timeTo)->format('H:i:s') : null;

        // 5. تحميل العلاقات بكفاءة وبدون إرجاع الساعات (خفيف وسريع للفرونت إند)
        $queryBuilder->with([
            'media',
            'pitches' => function ($pitchQ) use ($sportType, $searchDate, $formattedTime, $formattedFrom, $formattedTo) {
                $pitchQ->where('status', 'active')->with('media');

                if (!blank($sportType)) {
                    if (is_array($sportType)) {
                        $pitchQ->whereIn('sport_type', $sportType);
                    } else {
                        $pitchQ->where('sport_type', $sportType);
                    }
                }

                if (!blank($searchDate)) {
                    $pitchQ->whereHas('slots', function ($slotQ) use ($searchDate, $formattedTime, $formattedFrom, $formattedTo) {
                        $slotQ->availableOnline()->where('date', $searchDate);
                        if ($formattedTime) {
                            $slotQ->where('start_time', $formattedTime);
                        } elseif ($formattedFrom && $formattedTo) {
                            $slotQ->where('start_time', '>=', $formattedFrom)->where('start_time', '<=', $formattedTo);
                        }
                    });
                }
            },
        ]);

        $perPage = (int) $request->input('per_page', 15);
        $venues = $queryBuilder->paginate($perPage)->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب النوادي والملاعب المتاحة بنجاح.',
            'meta'    => [
                'applied_filters' => [
                    'has_coordinates' => $hasCoords,
                    'latitude'        => $lat,
                    'longitude'       => $lng,
                    'sport_type'      => $sportType,
                    'city'            => $request->input('city', $request->input('filter.city')),
                    'date'            => $searchDate,
                    'time'            => $searchTime,
                    'time_from'       => $timeFrom,
                    'time_to'         => $timeTo,
                    'sort'            => $request->input('sort', $hasCoords ? 'distance' : '-created_at'),
                ],
                'pagination' => [
                    'current_page' => $venues->currentPage(),
                    'per_page'     => $venues->perPage(),
                    'total'        => $venues->total(),
                    'last_page'    => $venues->lastPage(),
                ],
            ],
            'data'    => ExploreVenueResource::collection($venues->items()),
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
