<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        // Get users with the 'student' role using the role relationship
        $students = User::whereHas('roles', function ($query) {
            $query->where('slug', 'student');
        })->get();

        $courses = Course::all();

        foreach ($students as $student) {
            Enrollment::factory()->count(rand(1, 5))->create([
                'user_id' => $student->id,
                'course_id' => $courses->random()->id,
            ]);
        }
    }
}
