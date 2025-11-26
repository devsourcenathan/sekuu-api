<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    public function run(): void
    {
        $chapters = Chapter::all();

        foreach ($chapters as $chapter) {
            Lesson::factory()->count(5)->create([
                'chapter_id' => $chapter->id,
            ]);
        }
    }
}
