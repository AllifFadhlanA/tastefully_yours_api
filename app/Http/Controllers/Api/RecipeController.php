<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RecipeController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $query = Recipe::with(['category', 'user']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $recipes = $query->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Recipes retrieved successfully',
            'data' => $recipes
        ]);
    }

    public function show($id): JsonResponse
    {
        $recipe = Recipe::with(['category', 'user', 'ingredients', 'instructions'])->find($id);

        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Recipe retrieved successfully',
            'data' => $recipe
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:4096',
            'cook_time' => 'required|integer|min:1',
            'difficulty' => 'required|string|in:Easy,Medium,Hard',
            'servings' => 'nullable|integer|min:1',
            'calories' => 'nullable|integer|min:1',
            'ingredients' => 'required|array|min:1',
            'ingredients.*.name' => 'required|string|max:255',
            'ingredients.*.amount' => 'required|string|max:255',
            'instructions' => 'required|array|min:1',
            'instructions.*.step_number' => 'required|integer|min:1',
            'instructions.*.description' => 'required|string',
        ]);

        $recipe = DB::transaction(function () use ($request, $validated) {
            $recipeData = [
                'user_id' => $request->user()->id,
                'category_id' => $validated['category_id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'cook_time' => $validated['cook_time'],
                'difficulty' => $validated['difficulty'],
                'servings' => $validated['servings'] ?? 1,
                'calories' => $validated['calories'] ?? null,
            ];

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('recipes', 'public');
                $recipeData['image'] = $path;
            }

            $recipe = Recipe::create($recipeData);

            // Create ingredients
            foreach ($validated['ingredients'] as $ingredient) {
                $recipe->ingredients()->create([
                    'name' => $ingredient['name'],
                    'amount' => $ingredient['amount'],
                ]);
            }

            // Create instructions
            foreach ($validated['instructions'] as $instruction) {
                $recipe->instructions()->create([
                    'step_number' => $instruction['step_number'],
                    'description' => $instruction['description'],
                ]);
            }

            return $recipe;
        });

        return response()->json([
            'success' => true,
            'message' => 'Recipe created successfully',
            'data' => $recipe->load(['category', 'user', 'ingredients', 'instructions'])
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $recipe = Recipe::find($id);

        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }

        // Check ownership
        if ($recipe->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this recipe'
            ], 403);
        }

        $validated = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:4096',
            'cook_time' => 'sometimes|required|integer|min:1',
            'difficulty' => 'sometimes|required|string|in:Easy,Medium,Hard',
            'servings' => 'nullable|integer|min:1',
            'calories' => 'nullable|integer|min:1',
            'ingredients' => 'sometimes|required|array|min:1',
            'ingredients.*.name' => 'required|string|max:255',
            'ingredients.*.amount' => 'required|string|max:255',
            'instructions' => 'sometimes|required|array|min:1',
            'instructions.*.step_number' => 'required|integer|min:1',
            'instructions.*.description' => 'required|string',
        ]);

        $recipe = DB::transaction(function () use ($request, $recipe, $validated) {
            $recipeData = [];
            foreach (['category_id', 'title', 'description', 'cook_time', 'difficulty', 'servings', 'calories'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $recipeData[$field] = $validated[$field];
                }
            }

            if ($request->hasFile('image')) {
                if ($recipe->image) {
                    Storage::disk('public')->delete($recipe->image);
                }
                $path = $request->file('image')->store('recipes', 'public');
                $recipeData['image'] = $path;
            }

            $recipe->update($recipeData);

            if (isset($validated['ingredients'])) {
                // Delete old and insert new
                $recipe->ingredients()->delete();
                foreach ($validated['ingredients'] as $ingredient) {
                    $recipe->ingredients()->create([
                        'name' => $ingredient['name'],
                        'amount' => $ingredient['amount'],
                    ]);
                }
            }

            if (isset($validated['instructions'])) {
                // Delete old and insert new
                $recipe->instructions()->delete();
                foreach ($validated['instructions'] as $instruction) {
                    $recipe->instructions()->create([
                        'step_number' => $instruction['step_number'],
                        'description' => $instruction['description'],
                    ]);
                }
            }

            return $recipe;
        });

        return response()->json([
            'success' => true,
            'message' => 'Recipe updated successfully',
            'data' => $recipe->load(['category', 'user', 'ingredients', 'instructions'])
        ]);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $recipe = Recipe::find($id);

        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }

        // Check ownership
        if ($recipe->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this recipe'
            ], 403);
        }

        if ($recipe->image) {
            Storage::disk('public')->delete($recipe->image);
        }

        $recipe->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recipe deleted successfully'
        ]);
    }

    public function myRecipes(Request $request): JsonResponse
    {
        $recipes = Recipe::with(['category', 'user'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'User recipes retrieved successfully',
            'data' => $recipes
        ]);
    }
}
