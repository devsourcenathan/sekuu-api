<?php

namespace Database\Seeders;

use App\Models\Chapter;
use App\Models\Test;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        $chapters = Chapter::all();

        foreach ($chapters as $chapter) {
            Test::factory()->create([
                'testable_type' => Chapter::class,
                'testable_id' => $chapter->id,
            ]);
        }
    }
}
