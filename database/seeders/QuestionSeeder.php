<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Test;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $tests = Test::all();

        foreach ($tests as $test) {
            $questions = Question::factory()->count(4)->create([
                'test_id' => $test->id,
            ]);

            foreach ($questions as $question) {
                QuestionOption::factory()->count(4)->create([
                    'question_id' => $question->id,
                ]);
            }
        }
    }
}
