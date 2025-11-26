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
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'order' => $this->faker->numberBetween(1, 20),
            'content_type' => $this->faker->randomElement(['video', 'text', 'pdf', 'audio', 'quiz', 'slides']),
            'content' => $this->faker->paragraphs(5, true),
            'video_url' => $this->faker->url(),
            'video_provider' => $this->faker->randomElement(['youtube', 'vimeo']),
            'is_free' => $this->faker->boolean(15),
            'is_preview' => $this->faker->boolean(10),
            'is_downloadable' => $this->faker->boolean(30),
            'is_published' => $this->faker->boolean(85),
            'duration_minutes' => $this->faker->numberBetween(5, 60),
            'auto_complete' => false,
            'completion_threshold' => 80,
        ];
    }
}
