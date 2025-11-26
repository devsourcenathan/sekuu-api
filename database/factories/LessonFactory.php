<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'chapter_id' => Chapter::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'order' => fake()->numberBetween(1, 20),
            'content_type' => fake()->randomElement(['video', 'text', 'pdf', 'audio', 'quiz', 'slides']),
            'content' => fake()->paragraphs(5, true),
            'video_url' => fake()->url(),
            'video_provider' => fake()->randomElement(['youtube', 'vimeo']),
            'is_free' => fake()->boolean(15),
            'is_preview' => fake()->boolean(10),
            'is_downloadable' => fake()->boolean(30),
            'is_published' => fake()->boolean(85),
            'duration_minutes' => fake()->numberBetween(5, 60),
            'auto_complete' => false,
            'completion_threshold' => 80,
        ];
    }
}
