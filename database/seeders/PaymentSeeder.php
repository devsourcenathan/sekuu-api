<?php

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $enrollments = Enrollment::all();

        foreach ($enrollments as $enrollment) {
            Payment::factory()->create([
                'user_id' => $enrollment->user_id,
                'course_id' => $enrollment->course_id,
            ]);
        }
    }
}
