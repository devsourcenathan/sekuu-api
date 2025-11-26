<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Test;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestFactory extends Factory
{
    protected $model = Test::class;

    public function definition(): array
    {
        return [
            'testable_id' => Course::factory(),
            'testable_type' => Course::class,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'instructions' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['formative', 'summative']),
            'position' => $this->faker->randomElement(['after_lesson', 'after_chapter', 'end_of_course']),
            'duration_minutes' => $this->faker->numberBetween(15, 120),
            'max_attempts' => $this->faker->numberBetween(1, 5),
            'passing_score' => $this->faker->numberBetween(60, 80),
            'show_results_immediately' => $this->faker->boolean(70),
            'show_correct_answers' => $this->faker->boolean(60),
            'randomize_questions' => $this->faker->boolean(50),
            'randomize_options' => $this->faker->boolean(50),
            'one_question_per_page' => $this->faker->boolean(40),
            'allow_back_navigation' => $this->faker->boolean(60),
            'auto_save_draft' => true,
            'validation_type' => 'automatic',
            'is_published' => $this->faker->boolean(80),
            'disable_copy_paste' => $this->faker->boolean(30),
            'full_screen_required' => $this->faker->boolean(20),
            'webcam_monitoring' => false,
            'total_questions' => 0,
            'total_points' => 0,
            'attempts_count' => 0,
            'average_score' => 0,
        ];
    }
}
