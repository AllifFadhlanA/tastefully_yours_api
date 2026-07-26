<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $recipes = $request->user()
            ->favoriteRecipes()
            ->with(['category', 'user'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Favorite recipes retrieved successfully',
            'data' => $recipes
        ]);
    }

    public function toggle(Request $request, $id): JsonResponse
    {
        $recipe = Recipe::find($id);

        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }

        $user = $request->user();
        $favorite = Favorite::where('user_id', $user->id)->where('recipe_id', $id)->first();

        if ($favorite) {
            $favorite->delete();
            $isFavorited = false;
            $message = 'Recipe removed from favorites';
        } else {
            Favorite::create([
                'user_id' => $user->id,
                'recipe_id' => $id
            ]);
            $isFavorited = true;
            $message = 'Recipe added to favorites';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'is_favorited' => $isFavorited
            ]
        ]);
    }
}
