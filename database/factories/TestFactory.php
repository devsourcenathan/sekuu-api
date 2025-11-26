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
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'instructions' => fake()->paragraph(),
            'type' => fake()->randomElement(['formative', 'summative']),
            'position' => fake()->randomElement(['after_lesson', 'after_chapter', 'end_of_course']),
            'duration_minutes' => fake()->numberBetween(15, 120),
            'max_attempts' => fake()->numberBetween(1, 5),
            'passing_score' => fake()->numberBetween(60, 80),
            'show_results_immediately' => fake()->boolean(70),
            'show_correct_answers' => fake()->boolean(60),
            'randomize_questions' => fake()->boolean(50),
            'randomize_options' => fake()->boolean(50),
            'one_question_per_page' => fake()->boolean(40),
            'allow_back_navigation' => fake()->boolean(60),
            'auto_save_draft' => true,
            'validation_type' => 'automatic',
            'is_published' => fake()->boolean(80),
            'disable_copy_paste' => fake()->boolean(30),
            'full_screen_required' => fake()->boolean(20),
            'webcam_monitoring' => false,
            'total_questions' => 0,
            'total_points' => 0,
            'attempts_count' => 0,
            'average_score' => 0,
        ];
    }
}
