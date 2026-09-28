<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivityCompletion;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityCompletion>
 */
class ActivityCompletionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'activity_id' => Activity::factory(),
            'completed_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['completed_at' => now()]);
    }
}
