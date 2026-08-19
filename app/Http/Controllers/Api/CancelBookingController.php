<?php

namespace App\Http\Controllers\Api;

use App\Actions\Booking\CancelBookingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CancelBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Exception;
use Illuminate\Http\JsonResponse;

class CancelBookingController extends Controller
{
    public function __invoke(
        CancelBookingRequest $request,
        Booking $booking,
        CancelBookingAction $action
    ): JsonResponse {
        try {
            $cancelledBooking = $action->execute($booking, $request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'Booking cancelled successfully.',
                'data'    => new BookingResource($cancelledBooking),
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
