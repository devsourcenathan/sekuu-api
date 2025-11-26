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
            'certificate_number' => 'CERT-' . date('Y') . '-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8)),
            'verification_code' => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(16)),
            'student_name' => $this->faker->name(),
            'course_title' => $this->faker->sentence(4),
            'instructor_name' => $this->faker->name(),
            'completion_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'final_score' => $this->faker->randomFloat(2, 70, 100),
            'grade' => $this->faker->randomElement(['A', 'B', 'C']),
            'is_verified' => true,
            'verified_at' => now(),
        ];
    }
}
