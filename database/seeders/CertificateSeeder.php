<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Enrollment;
use Illuminate\Database\Seeder;

class CertificateSeeder extends Seeder
{
    public function run(): void
    {
        $enrollments = Enrollment::all();

        foreach ($enrollments as $e) {
            Certificate::factory()->create([
                'user_id' => $e->user_id,
                'course_id' => $e->course_id,
            ]);
        }
    }
}
