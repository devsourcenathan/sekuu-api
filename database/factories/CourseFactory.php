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
        $title = $this->faker->sentence(4);
        
        return [
            'instructor_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title),
            'description' => $this->faker->paragraphs(3, true),
            'what_you_will_learn' => json_encode([
                $this->faker->sentence(),
                $this->faker->sentence(),
                $this->faker->sentence(),
            ]),
            'requirements' => json_encode([
                $this->faker->sentence(),
                $this->faker->sentence(),
            ]),
            'target_audience' => json_encode([
                $this->faker->sentence(),
                $this->faker->sentence(),
            ]),
            'level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'language' => $this->faker->randomElement(['en', 'fr']),
            'status' => $this->faker->randomElement(['draft', 'published', 'archived']),
            'is_free' => $this->faker->boolean(30),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'currency' => 'USD',
            'is_public' => true,
            'requires_approval' => false,
            'allow_download' => $this->faker->boolean(),
            'has_certificate' => $this->faker->boolean(70),
            'has_forum' => $this->faker->boolean(50),
            'total_duration_minutes' => $this->faker->numberBetween(60, 600),
            'total_lessons' => $this->faker->numberBetween(5, 50),
            'students_enrolled' => $this->faker->numberBetween(0, 1000),
            'average_rating' => $this->faker->randomFloat(2, 3.5, 5.0),
            'total_reviews' => $this->faker->numberBetween(0, 100),
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
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
