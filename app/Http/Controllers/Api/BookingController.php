<?php

namespace App\Http\Controllers\Api;

use App\Actions\Booking\CreateBookingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBookingRequest;
use App\Http\Resources\BookingResource; // 👈 1. إضافة الـ Import
use Illuminate\Http\JsonResponse;
use Exception;

class BookingController extends Controller
{
    /**
     * Handle the incoming request to create a new booking.
     */
    public function __invoke(
        CreateBookingRequest $request,
        CreateBookingAction $action
    ): JsonResponse {
        try {
            $booking = $action->execute($request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'Booking created successfully.',
                'data'    => new BookingResource($booking), // 👈 2. التغليف بالـ Resource هنا
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400);
        }
    }
}
