<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        // Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@trainingplatform.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $superAdmin->assignRole('super-admin');

        // Admin
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@trainingplatform.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Instructor
        $instructor = User::create([
            'name' => 'John Instructor',
            'email' => 'instructor@trainingplatform.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $instructor->assignRole('instructor');

        // Student
        $student = User::create([
            'name' => 'Jane Student',
            'email' => 'student@trainingplatform.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $student->assignRole('student');
    }
}
