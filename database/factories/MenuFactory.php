<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::inRandomOrder()->first()->id,

            'title' => fake()->words(3, true),

            'description' => fake()->sentence(),

            'price' => fake()->randomFloat(2, 20, 150),

            'image' => null,

            'is_available' => true,
        ];
    }
}