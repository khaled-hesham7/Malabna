<?php

namespace App\Http\Controllers\Api;

use App\Actions\Booking\BlockPitchSlotAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\BlockPitchSlotRequest;
use App\Http\Resources\BlockedSlotResource;
use Illuminate\Http\JsonResponse;
use Exception;

class BlockPitchSlotController extends Controller
{
    public function __invoke(
        BlockPitchSlotRequest $request,
        BlockPitchSlotAction $action
    ): JsonResponse {
        try {
            $blockedSlot = $action->execute($request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'Slot blocked successfully.',
                'data'    => new BlockedSlotResource($blockedSlot),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400);
        }
    }
}
