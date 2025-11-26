<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        // Get users with the 'instructor' role using the role relationship
        $instructors = User::whereHas('roles', function ($query) {
            $query->where('slug', 'instructor');
        })->pluck('id');

        // Only create courses if there are instructors
        if ($instructors->isNotEmpty()) {
            Course::factory()->count(20)->create([
                'instructor_id' => fn () => $instructors->random(),
            ]);
        }
    }
}
