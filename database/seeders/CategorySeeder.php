<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Breakfast', 'image' => 'categories/breakfast.png'],
            ['name' => 'Lunch', 'image' => 'categories/lunch.png'],
            ['name' => 'Dinner', 'image' => 'categories/dinner.png'],
            ['name' => 'Dessert', 'image' => 'categories/dessert.png'],
            ['name' => 'Vegan', 'image' => 'categories/vegan.png'],
            ['name' => 'Soup', 'image' => 'categories/soup.png'],
            ['name' => 'Snack', 'image' => 'categories/snack.png'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                ['image' => $category['image']]
            );
        }
    }
}
