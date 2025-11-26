<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        $title = fake()->sentence(4);
        
        return [
            'instructor_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title),
            'description' => fake()->paragraphs(3, true),
            'what_you_will_learn' => json_encode([
                fake()->sentence(),
                fake()->sentence(),
                fake()->sentence(),
            ]),
            'requirements' => json_encode([
                fake()->sentence(),
                fake()->sentence(),
            ]),
            'target_audience' => json_encode([
                fake()->sentence(),
                fake()->sentence(),
            ]),
            'level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'language' => fake()->randomElement(['en', 'fr']),
            'status' => fake()->randomElement(['draft', 'published', 'archived']),
            'is_free' => fake()->boolean(30),
            'price' => fake()->randomFloat(2, 10, 500),
            'currency' => 'USD',
            'is_public' => true,
            'requires_approval' => false,
            'allow_download' => fake()->boolean(),
            'has_certificate' => fake()->boolean(70),
            'has_forum' => fake()->boolean(50),
            'total_duration_minutes' => fake()->numberBetween(60, 600),
            'total_lessons' => fake()->numberBetween(5, 50),
            'students_enrolled' => fake()->numberBetween(0, 1000),
            'average_rating' => fake()->randomFloat(2, 3.5, 5.0),
            'total_reviews' => fake()->numberBetween(0, 100),
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_free' => true,
            'price' => 0,
        ]);
    }
}
