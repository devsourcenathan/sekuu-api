<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    { 
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            AdminSeeder::class,
            CategorySeeder::class,
            CourseSeeder::class,
            ChapterSeeder::class,
            LessonSeeder::class,
            TestSeeder::class,
            QuestionSeeder::class,
            PaymentSeeder::class,
            EnrollmentSeeder::class,
            CertificateSeeder::class,
            ResourceSeeder::class, 
        ]);
    }
}
