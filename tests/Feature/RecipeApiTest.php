<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                    'token'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'token'
                ]
            ]);
    }

    public function test_can_get_categories()
    {
        Category::create(['name' => 'Breakfast', 'image' => 'breakfast.png']);
        Category::create(['name' => 'Lunch', 'image' => 'lunch.png']);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_authenticated_user_can_create_recipe()
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Breakfast', 'image' => 'breakfast.png']);

        $response = $this->actingAs($user)
            ->postJson('/api/recipes', [
                'category_id' => $category->id,
                'title' => 'Pancakes',
                'description' => 'Delicious fluffy pancakes',
                'cook_time' => 20,
                'difficulty' => 'Easy',
                'ingredients' => [
                    ['name' => 'Flour', 'amount' => '2 cups'],
                    ['name' => 'Milk', 'amount' => '1 cup'],
                ],
                'instructions' => [
                    ['step_number' => 1, 'description' => 'Mix dry ingredients'],
                    ['step_number' => 2, 'description' => 'Add milk and mix'],
                ]
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Pancakes');

        $this->assertDatabaseHas('recipes', ['title' => 'Pancakes']);
        $this->assertDatabaseHas('ingredients', ['name' => 'Flour']);
        $this->assertDatabaseHas('instructions', ['step_number' => 1]);
    }

    public function test_owner_can_update_recipe()
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Breakfast', 'image' => 'breakfast.png']);
        $recipe = Recipe::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Old Title',
            'cook_time' => 10,
            'difficulty' => 'Easy',
            'servings' => 2,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/recipes/{$recipe->id}", [
                '_method' => 'PUT',
                'title' => 'New Title',
                'ingredients' => [
                    ['name' => 'New Ingredient', 'amount' => '1 unit']
                ],
                'instructions' => [
                    ['step_number' => 1, 'description' => 'New Step']
                ]
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('recipes', ['title' => 'New Title']);
    }

    public function test_non_owner_cannot_update_recipe()
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $category = Category::create(['name' => 'Breakfast', 'image' => 'breakfast.png']);
        $recipe = Recipe::create([
            'user_id' => $owner->id,
            'category_id' => $category->id,
            'title' => 'Old Title',
            'cook_time' => 10,
            'difficulty' => 'Easy',
            'servings' => 2,
        ]);

        $response = $this->actingAs($nonOwner)
            ->postJson("/api/recipes/{$recipe->id}", [
                '_method' => 'PUT',
                'title' => 'New Title',
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_toggle_favorite()
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Breakfast', 'image' => 'breakfast.png']);
        $recipe = Recipe::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Fav Recipe',
            'cook_time' => 10,
            'difficulty' => 'Easy',
            'servings' => 2,
        ]);

        // Toggle on
        $response = $this->actingAs($user)->postJson("/api/favorites/{$recipe->id}");
        $response->assertStatus(200)->assertJsonPath('data.is_favorited', true);
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'recipe_id' => $recipe->id]);

        // Toggle off
        $response = $this->actingAs($user)->postJson("/api/favorites/{$recipe->id}");
        $response->assertStatus(200)->assertJsonPath('data.is_favorited', false);
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'recipe_id' => $recipe->id]);
    }
}
