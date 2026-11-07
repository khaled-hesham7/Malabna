<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\PitchFilterRequest;
use App\Http\Requests\Partner\StorePitchRequest;
use App\Http\Requests\Partner\TogglePitchStatusRequest;
use App\Http\Requests\Partner\UpdatePitchRequest;
use App\Http\Resources\PitchResource;
use App\Models\Pitch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PartnerPitchController extends Controller
{
    /**
     * List all pitches belonging to the authenticated partner's venue
     */
    public function index(PitchFilterRequest $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'No venue found for this account.',
            ], 404);
        }

        $query = $tenant->pitches()->with('media');

        // فلترة اختيارية بنوع الرياضة أو الحالة
        if ($request->filled('sport_type')) {
            $query->where('sport_type', $request->sport_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $pitches = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data'    => PitchResource::collection($pitches),
        ]);
    }

    /**
     * Create a new pitch with multiple images and videos
     */
    public function store(StorePitchRequest $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'No venue affiliated with this account.',
            ], 403);
        }

        return DB::transaction(function () use ($request, $tenant) {
            $validated = $request->validated();

            // 1. إنشاء الملعب وربطه بالنادي
            $pitch = $tenant->pitches()->create([
                'name'               => $validated['name'],
                'description'        => $validated['description'] ?? null,
                'sport_type'         => $validated['sport_type'],
                'court_size'         => $validated['court_size'] ?? null,
                'surface_type'       => $validated['surface_type'] ?? null,
                'is_indoor'          => $validated['is_indoor'] ?? false,
                'manual_day_price'   => $validated['manual_day_price'],
                'manual_night_price' => $validated['manual_night_price'],
                'online_day_price'   => $validated['online_day_price'],
                'online_night_price' => $validated['online_night_price'],
                'night_start_at'        => $validated['night_start_at'] ?? '16:00:00',
                'opening_time'          => $validated['opening_time'] ?? '08:00:00',
                'closing_time'          => $validated['closing_time'] ?? '02:00:00',
                'slot_duration_minutes' => $validated['slot_duration_minutes'] ?? 60,
                'amenities'             => $validated['amenities'] ?? [],
                'status'                => $validated['status'] ?? 'active',
            ]);

            // 2. رفع الصور المتعددة
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $pitch->addMedia($imageFile)->toMediaCollection('images');
                }
            }

            // 3. رفع الفيديوهات المتعددة
            if ($request->hasFile('videos')) {
                foreach ($request->file('videos') as $videoFile) {
                    $pitch->addMedia($videoFile)->toMediaCollection('videos');
                }
            }

            // 4. توليد الساعات تلقائياً لمدة 30 يوماً فور إنشاء الملعب إذا كان نشطاً
            if ($pitch->status === 'active') {
                app(\App\Services\PitchSlotService::class)->generateSlotForDateRange(
                    $pitch,
                    now()->toDateString(),
                    now()->addDays(30)->toDateString()
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الملعب وتوليد فترات الحجز بنجاح.',
                'data'    => new PitchResource($pitch->fresh(['media'])),
            ], 201);
        });
    }

    /**
     * Show single pitch details
     */
    public function show(Request $request, Pitch $pitch): JsonResponse
    {
        $this->authorizeTenantPitch($request, $pitch);

        $pitch->load(['media', 'pricingRules']);

        return response()->json([
            'success' => true,
            'data'    => new PitchResource($pitch),
        ]);
    }

    /**
     * Update existing pitch details and optionally add/remove media
     */
    public function update(UpdatePitchRequest $request, Pitch $pitch): JsonResponse
    {
        $this->authorizeTenantPitch($request, $pitch);

        return DB::transaction(function () use ($request, $pitch) {
            $validated = $request->validated();

            // استبعاد حقول الميديا من التحديث لأنها ليست أعمدة في قاعدة البيانات
            $pitch->update(collect($validated)->except([
                'images', 'videos', 'replace_images', 'replace_videos', 'delete_media_ids',
            ])->toArray());

            // 1. التعامل مع الصور: استبدال أو إضافة
            if ($request->hasFile('images')) {
                // إذا طلب الاستبدال صراحةً، نحذف صور الملعب القديمة أولاً
                if ($request->boolean('replace_images')) {
                    $pitch->clearMediaCollection('images');
                }

                foreach ($request->file('images') as $imageFile) {
                    $pitch->addMedia($imageFile)->toMediaCollection('images');
                }
            }

            // 2. التعامل مع الفيديوهات: استبدال أو إضافة
            if ($request->hasFile('videos')) {
                // إذا طلب الاستبدال صراحةً، نحذف فيديوهات الملعب القديمة أولاً
                if ($request->boolean('replace_videos')) {
                    $pitch->clearMediaCollection('videos');
                }

                foreach ($request->file('videos') as $videoFile) {
                    $pitch->addMedia($videoFile)->toMediaCollection('videos');
                }
            }

            // 3. حذف وسائط محددة بالـ ID (لو عايز يحذف صورة أو فيديو بعينه)
            if ($request->filled('delete_media_ids')) {
                Media::whereIn('id', $request->delete_media_ids)
                    ->where('model_type', Pitch::class)
                    ->where('model_id', $pitch->id)
                    ->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات الملعب والوسائط بنجاح.',
                'data'    => new PitchResource($pitch->fresh(['media'])),
            ]);
        });
    }

    /**
     * Delete pitch (Soft delete)
     */
    public function destroy(Request $request, Pitch $pitch): JsonResponse
    {
        $this->authorizeTenantPitch($request, $pitch);

        $pitch->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الملعب بنجاح.',
        ]);
    }

    /**
     * Toggle or set pitch operational status (active, maintenance, inactive)
     */
    public function toggleStatus(TogglePitchStatusRequest $request, Pitch $pitch): JsonResponse
    {
        $this->authorizeTenantPitch($request, $pitch);

        // إذا تم تحديد حالة معينة نطبقها، وإلا نقلب الحالة بين active و inactive
        if ($request->filled('status')) {
            $newStatus = $request->status;
        } else {
            $newStatus = $pitch->status === 'active' ? 'inactive' : 'active';
        }

        $pitch->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'message' => "تم تغيير حالة الملعب إلى ({$newStatus}) بنجاح.",
            'data'    => new PitchResource($pitch->fresh(['media'])),
        ]);
    }

    /**
     * Security check: ensure pitch belongs to the authenticated user's venue
     */
    protected function authorizeTenantPitch(Request $request, Pitch $pitch): void
    {
        $tenantId = $request->user()->tenant_id;

        if (!$tenantId || (int) $pitch->tenant_id !== (int) $tenantId) {
            abort(403, 'غير مصرح لك بالوصول لهذا الملعب.');
        }
    }
}
