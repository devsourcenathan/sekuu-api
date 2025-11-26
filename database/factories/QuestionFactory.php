<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Test;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'test_id' => Test::factory(),
            'question_text' => fake()->sentence() . '?',
            'explanation' => fake()->paragraph(),
            'type' => fake()->randomElement(['multiple_choice', 'true_false', 'short_answer']),
            'points' => fake()->numberBetween(1, 10),
            'order' => fake()->numberBetween(1, 50),
            'is_required' => true,
            'requires_manual_grading' => false,
        ];
    }
}
