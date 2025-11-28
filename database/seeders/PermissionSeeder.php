<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // Course permissions
            ['name' => 'View Courses', 'slug' => 'courses.view', 'module' => 'courses'],
            ['name' => 'Create Courses', 'slug' => 'courses.create', 'module' => 'courses'],
            ['name' => 'Edit Courses', 'slug' => 'courses.edit', 'module' => 'courses'],
            ['name' => 'Delete Courses', 'slug' => 'courses.delete', 'module' => 'courses'],
            ['name' => 'Publish Courses', 'slug' => 'courses.publish', 'module' => 'courses'],
            
            // Chapter permissions
            ['name' => 'Manage Chapters', 'slug' => 'chapters.manage', 'module' => 'courses'],
            
            // Lesson permissions
            ['name' => 'Manage Lessons', 'slug' => 'lessons.manage', 'module' => 'courses'],
            
            // Test permissions
            ['name' => 'View Tests', 'slug' => 'tests.view', 'module' => 'tests'],
            ['name' => 'Create Tests', 'slug' => 'tests.create', 'module' => 'tests'],
            ['name' => 'Edit Tests', 'slug' => 'tests.edit', 'module' => 'tests'],
            ['name' => 'Delete Tests', 'slug' => 'tests.delete', 'module' => 'tests'],
            ['name' => 'Evaluate Tests', 'slug' => 'tests.evaluate', 'module' => 'tests'],
            
            // User permissions
            ['name' => 'View Users', 'slug' => 'users.view', 'module' => 'users'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'module' => 'users'],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'module' => 'users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'module' => 'users'],
            
            // Role permissions
            ['name' => 'Manage Roles', 'slug' => 'roles.manage', 'module' => 'users'],
            
            // Payment permissions
            ['name' => 'View Payments', 'slug' => 'payments.view', 'module' => 'payments'],
            ['name' => 'Manage Payments', 'slug' => 'payments.manage', 'module' => 'payments'],
            
            // Analytics permissions
            ['name' => 'View Analytics', 'slug' => 'analytics.view', 'module' => 'analytics'],
            
            // Pack permissions
            ['name' => 'View Packs', 'slug' => 'packs.view', 'module' => 'packs'],
            ['name' => 'Create Packs', 'slug' => 'packs.create', 'module' => 'packs'],
            ['name' => 'Edit Packs', 'slug' => 'packs.edit', 'module' => 'packs'],
            ['name' => 'Delete Packs', 'slug' => 'packs.delete', 'module' => 'packs'],
            ['name' => 'Publish Packs', 'slug' => 'packs.publish', 'module' => 'packs'],
            
            // Group permissions
            ['name' => 'View Groups', 'slug' => 'groups.view', 'module' => 'groups'],
            ['name' => 'Manage Groups', 'slug' => 'groups.manage', 'module' => 'groups'],
            
            // Session permissions
            ['name' => 'View Sessions', 'slug' => 'sessions.view', 'module' => 'sessions'],
            ['name' => 'Manage Sessions', 'slug' => 'sessions.manage', 'module' => 'sessions'],
            
            // Meeting Request permissions
            ['name' => 'View Meeting Requests', 'slug' => 'meeting-requests.view', 'module' => 'meeting-requests'],
            ['name' => 'Manage Meeting Requests', 'slug' => 'meeting-requests.manage', 'module' => 'meeting-requests'],
            
            // Category permissions
            ['name' => 'Manage Categories', 'slug' => 'categories.manage', 'module' => 'categories'],
            
            // Resource permissions
            ['name' => 'Manage Resources', 'slug' => 'resources.manage', 'module' => 'resources'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Assign permissions to roles
        $superAdmin = Role::where('slug', 'super-admin')->first();
        $superAdmin->permissions()->attach(Permission::all());

        $admin = Role::where('slug', 'admin')->first();
        $admin->permissions()->attach(
            Permission::whereIn('module', [
                'courses', 'tests', 'users', 'payments', 'analytics', 
                'packs', 'groups', 'sessions', 'meeting-requests', 'categories', 'resources'
            ])->pluck('id')
        );

        $instructor = Role::where('slug', 'instructor')->first();
        $instructor->permissions()->attach(
            Permission::whereIn('slug', [
                'courses.view', 'courses.create', 'courses.edit', 'courses.publish',
                'chapters.manage', 'lessons.manage',
                'tests.view', 'tests.create', 'tests.edit', 'tests.evaluate',
                'payments.view', 'analytics.view',
                'packs.view', 'packs.create', 'packs.edit', 'packs.publish',
                'groups.view', 'groups.manage',
                'sessions.view', 'sessions.manage',
                'meeting-requests.view', 'meeting-requests.manage',
                'resources.manage'
            ])->pluck('id')
        );

        $student = Role::where('slug', 'student')->first();
        $student->permissions()->attach(
            Permission::whereIn('slug', ['courses.view', 'tests.view'])->pluck('id')
        );
    }
}