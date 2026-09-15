<?php

namespace App\Console\Commands;

use App\Models\Pitch;
use App\Services\PitchSlotService;
use Illuminate\Console\Command;

class GenerateSlotsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'slots:generate {--days=60 : Number of days ahead to generate}';

    /**
     * The console command description.
     */
    protected $description = 'Generate bulk pitch slots for active pitches for upcoming days.';

    /**
     * Execute the console command.
     */
    public function handle(PitchSlotService $slotService): int
    {
        $days = (int) $this->option('days');
        $this->info("Generating pitch slots for active pitches for {$days} days ahead...");

        $activePitches = Pitch::where('status', 'active')->get();
        if ($activePitches->isEmpty()) {
            $this->warn('No active pitches found for generating slots.');
            return self::SUCCESS;
        }

        $startDate = now()->toDateString();
        $endDate   = now()->addDays($days)->toDateString();
        $totalGenerated = 0;

        foreach ($activePitches as $pitch) {
            $count = $slotService->generateSlotForDateRange($pitch, $startDate, $endDate);
            $totalGenerated += $count;
            $this->line("Generated {$count} new slots for pitch: {$pitch->name}");
        }

        $this->info("Process completed successfully! Total new slots generated: {$totalGenerated}");
        return self::SUCCESS;
    }
}
