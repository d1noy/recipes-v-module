<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\RecipeController;
use Illuminate\Support\Facades\Route;

// Публичные
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('ingredients', [IngredientController::class, 'index']);
Route::get('recipes', [RecipeController::class, 'index']);

// Только с токеном (Authorization: Bearer ...)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'me']);

    Route::get('my/recipes', [RecipeController::class, 'mine']);
    Route::post('recipes', [RecipeController::class, 'store']);
    Route::put('recipes/{recipe}', [RecipeController::class, 'update']);
    Route::delete('recipes/{recipe}', [RecipeController::class, 'destroy']);
});

Route::get('recipes/{recipe}', [RecipeController::class, 'show']);
