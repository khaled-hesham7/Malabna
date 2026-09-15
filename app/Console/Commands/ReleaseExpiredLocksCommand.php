<?php

namespace App\Console\Commands;

use App\Services\PitchSlotService;
use Illuminate\Console\Command;

class ReleaseExpiredLocksCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'slots:release-locks';

    /**
     * The console command description.
     */
    protected $description = 'Release expired checkout locks on pitch slots.';

    /**
     * Execute the console command.
     */
    public function handle(PitchSlotService $slotService): int
    {
        $releasedCount = $slotService->releaseExpiredLocks();
        if ($releasedCount > 0) {
            $this->info("Successfully released {$releasedCount} expired slot checkout locks.");
        }

        return self::SUCCESS;
    }
}
