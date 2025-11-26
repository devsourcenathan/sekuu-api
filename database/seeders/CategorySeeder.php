<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Programming', 'slug' => 'programming', 'icon' => 'code'],
            ['name' => 'Design', 'slug' => 'design', 'icon' => 'palette'],
            ['name' => 'Business', 'slug' => 'business', 'icon' => 'briefcase'],
            ['name' => 'Marketing', 'slug' => 'marketing', 'icon' => 'trending-up'],
            ['name' => 'Photography', 'slug' => 'photography', 'icon' => 'camera'],
            ['name' => 'Music', 'slug' => 'music', 'icon' => 'music'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
