<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $instructors = User::where('role', 'instructor')->pluck('id');

        Course::factory()->count(20)->create([
            'instructor_id' => fn () => $instructors->random(),
        ]);
    }
}
