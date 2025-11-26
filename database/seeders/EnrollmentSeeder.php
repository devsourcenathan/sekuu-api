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
        $students = User::where('role', 'student')->get();
        $courses = Course::all();

        foreach ($students as $student) {
            Enrollment::factory()->count(3)->create([
                'user_id' => $student->id,
                'course_id' => $courses->random()->id,
            ]);
        }
    }
}
