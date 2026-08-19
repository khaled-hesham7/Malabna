<?php

namespace App\Http\Controllers\Api;

use App\Actions\Booking\GetAvailableSlotsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\GetAvailableSlotsRequest;
use App\Http\Resources\SlotResource; // 👈 1. استدعاء الـ Resource
use App\Models\Pitch;
use Illuminate\Http\JsonResponse;

class PitchSlotController extends Controller
{
    /**
     * Handle the incoming request to fetch available slots for a pitch.
     */
    public function __invoke(
        GetAvailableSlotsRequest $request,
        Pitch $pitch,
        GetAvailableSlotsAction $action
    ): JsonResponse {
        $slots = $action->execute($pitch, $request->validated('date'));

        return response()->json([
            'status'  => 'success',
            'pitch'   => [
                'id'   => $pitch->id,
                'name' => $pitch->name,
            ],
            'date'    => $request->validated('date'),
            'data'    => SlotResource::collection($slots), // 👈 2. تغليف القائمة بـ Resource Collection
        ]);
    }
}
