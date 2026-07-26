<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\RecipeController;
use Illuminate\Support\Facades\Route;

// Public Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public Category & Recipe routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/recipes', [RecipeController::class, 'index']);
Route::get('/recipes/{id}', [RecipeController::class, 'show']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Profile routes
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::match(['put', 'post'], '/profile', [AuthController::class, 'updateProfile']);

    // User Recipe routes
    Route::get('/my-recipes', [RecipeController::class, 'myRecipes']);
    Route::post('/recipes', [RecipeController::class, 'store']);
    Route::match(['put', 'post'], '/recipes/{id}', [RecipeController::class, 'update']);
    Route::delete('/recipes/{id}', [RecipeController::class, 'destroy']);

    // Favorite routes
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites/{id}', [FavoriteController::class, 'toggle']);
});
