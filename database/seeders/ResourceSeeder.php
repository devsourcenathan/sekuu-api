<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Resource;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::all();

        foreach ($courses as $course) {
            Resource::factory()->count(2)->create([
                'resourceable_type' => Course::class,
                'resourceable_id' => $course->id,
            ]);
        }
    }
}
