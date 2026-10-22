<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $categories = [
            [
                'name' => 'Breakfast',
                'slug' => 'breakfast',
            ],
            [
                'name' => 'Lunch',
                'slug' => 'lunch',
            ],
            [
                'name' => 'Dinner',
                'slug' => 'dinner',
            ],
            [
                'name' => 'Desserts',
                'slug' => 'desserts',
            ],
            [
                'name' => 'Drinks',
                'slug' => 'drinks',
            ],
            [
                'name' => 'Coffee',
                'slug' => 'coffee',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    
    }
}
