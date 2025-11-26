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

        if ($students->isEmpty() || $courses->isEmpty()) {
            return;
        }

        foreach ($students as $student) {
            // Get a random number of courses (1-5, but not more than available courses)
            $numEnrollments = min(rand(1, 5), $courses->count());
            
            // Get random unique courses for this student
            $selectedCourses = $courses->random($numEnrollments);
            
            foreach ($selectedCourses as $course) {
                Enrollment::factory()->create([
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                ]);
            }
        }
    }
}
