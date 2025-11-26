<?php


namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Full access to all features'
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrative access'
            ],
            [
                'name' => 'Instructor',
                'slug' => 'instructor',
                'description' => 'Can create and manage courses'
            ],
            [
                'name' => 'Student',
                'slug' => 'student',
                'description' => 'Can enroll in courses'
            ],
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}