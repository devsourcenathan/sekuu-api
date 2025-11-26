<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChapterFactory extends Factory
{
    protected $model = Chapter::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'order' => fake()->numberBetween(1, 10),
            'is_free' => fake()->boolean(20),
            'is_published' => fake()->boolean(80),
            'duration_minutes' => fake()->numberBetween(30, 180),
        ];
    }
}
