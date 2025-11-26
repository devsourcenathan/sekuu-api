<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    public function definition(): array
    {
        $fileName = fake()->word() . '.pdf';
        
        return [
            'resourceable_id' => Course::factory(),
            'resourceable_type' => Course::class,
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'file_path' => 'resources/' . $fileName,
            'file_name' => $fileName,
            'file_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(100000, 5000000),
            'is_free' => fake()->boolean(30),
            'is_downloadable' => true,
            'download_limit' => null,
            'downloads_count' => fake()->numberBetween(0, 100),
            'order' => fake()->numberBetween(1, 10),
        ];
    }
}
