<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'enrollment_id' => Enrollment::factory(),
            'student_name' => fake()->name(),
            'course_title' => fake()->sentence(4),
            'instructor_name' => fake()->name(),
            'completion_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'final_score' => fake()->randomFloat(2, 70, 100),
            'grade' => fake()->randomElement(['A', 'B', 'C']),
            'is_verified' => true,
            'verified_at' => now(),
        ];
    }
}
