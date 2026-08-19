<?php

namespace App\Http\Controllers\Api;

use App\Actions\Booking\CreateRecurringScheduleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRecurringScheduleRequest;
use App\Http\Resources\RecurringScheduleResource; // 👈 1. استدعاء الـ Resource
use Illuminate\Http\JsonResponse;
use Exception;

class RecurringScheduleController extends Controller
{
    public function __invoke(
        CreateRecurringScheduleRequest $request,
        CreateRecurringScheduleAction $action
    ): JsonResponse {
        try {
            $recurring = $action->execute($request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'Recurring schedule created successfully.',
                'data'    => new RecurringScheduleResource($recurring), // 👈 2. التغليف بالـ Resource
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400);
        }
    }
}
