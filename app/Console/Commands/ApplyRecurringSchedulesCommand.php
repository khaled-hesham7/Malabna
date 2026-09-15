<?php

namespace App\Console\Commands;

use App\Models\RecurringSchedule;
use App\Services\RecurringScheduleService;
use Illuminate\Console\Command;

class ApplyRecurringSchedulesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'schedules:apply {--weeks=8 : Number of weeks ahead to apply}';

    /**
     * The console command description.
     */
    protected $description = 'Apply active weekly recurring schedules to upcoming pitch slots.';

    /**
     * Execute the console command.
     */
    public function handle(RecurringScheduleService $scheduleService): int
    {
        $weeks = (int) $this->option('weeks');
        $this->info("Applying active recurring schedules for {$weeks} weeks ahead...");

        $activeSchedules = RecurringSchedule::where('status', 'active')->get();
        if ($activeSchedules->isEmpty()) {
            $this->warn('No active recurring schedules found.');
            return self::SUCCESS;
        }

        $totalApplied = 0;
        foreach ($activeSchedules as $schedule) {
            $count = $scheduleService->applyScheduleToUpcomingSlots($schedule, $weeks);
            $totalApplied += $count;
        }

        $this->info("Process completed successfully! Total recurring slots applied: {$totalApplied}");
        return self::SUCCESS;
    }
}
