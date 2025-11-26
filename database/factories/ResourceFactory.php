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
        $fileName = $this->faker->word() . '.pdf';
        
        return [
            'resourceable_id' => Course::factory(),
            'resourceable_type' => Course::class,
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'file_path' => 'resources/' . $fileName,
            'file_name' => $fileName,
            'file_type' => 'application/pdf',
            'file_size' => $this->faker->numberBetween(100000, 5000000),
            'is_free' => $this->faker->boolean(30),
            'is_downloadable' => true,
            'download_limit' => null,
            'downloads_count' => $this->faker->numberBetween(0, 100),
            'order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
