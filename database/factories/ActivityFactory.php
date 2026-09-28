<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->sentence(3),
            'is_required' => true,
        ];
    }

    public function optional(): static
    {
        return $this->state(fn () => ['is_required' => false]);
    }
}
