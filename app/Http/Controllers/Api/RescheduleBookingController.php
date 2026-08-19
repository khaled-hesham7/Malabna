<?php

namespace App\Http\Controllers\Api;

use App\Actions\Booking\RescheduleBookingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\RescheduleBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking; // 👈 إمبورت الموديل
use Exception;
use Illuminate\Http\JsonResponse;

class RescheduleBookingController extends Controller
{
    public function __invoke(
        RescheduleBookingRequest $request,
        Booking $booking, // 👈 استلام الحجز من الـ URL
        RescheduleBookingAction $action
    ): JsonResponse {
        try {
            $updatedBooking = $action->execute($booking, $request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'Booking rescheduled successfully.',
                'data'    => new BookingResource($updatedBooking),
            ]);
        } catch (Exception $e) {
            $code = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 400;

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $code);
        }
    }
}
