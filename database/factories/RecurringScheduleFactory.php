namespace Database\Factories;

use App\Models\Pitch;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecurringScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pitch_id'   => Pitch::factory(),
            'day_of_week' => 1, // Monday مثلاً
            'start_time' => '18:00:00',
            'end_time'   => '19:00:00',
            'status'     => 'active',
        ];
    }
}
