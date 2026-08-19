<?php

namespace App\Jobs;

use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExpirePendingBookingsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        // إلغاء أي حجز pending فات عليه أكتر من 15 دقيقة
        $expiredCount = Booking::where('status', 'pending')
            ->where('created_at', '<=', Carbon::now()->subMinutes(15))
            ->update([
                'status' => 'cancelled',
            ]);

        if ($expiredCount > 0) {
            Log::info("Expired {$expiredCount} pending booking(s).");
        }
    }
}
