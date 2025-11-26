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
            'question_text' => $this->faker->sentence() . '?',
            'explanation' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['multiple_choice', 'true_false', 'short_answer']),
            'points' => $this->faker->numberBetween(1, 10),
            'order' => $this->faker->numberBetween(1, 50),
            'is_required' => true,
            'requires_manual_grading' => false,
        ];
    }
}
