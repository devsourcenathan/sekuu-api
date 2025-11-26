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
        // User::factory(10)->create();

        // $this->call([
        //     RoleSeeder::class,
        //     PermissionSeeder::class,
        //     AdminSeeder::class,
        //     CategorySeeder::class,
        // ]);

        $this->call([
            CourseSeeder::class,
            ChapterSeeder::class,
            LessonSeeder::class,
            TestSeeder::class,
            QuestionSeeder::class,
            PaymentSeeder::class,
            EnrollmentSeeder::class,
            CertificateSeeder::class,
            ResourceSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            AdminSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
