<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\IngredientResource;
use App\Models\Ingredient;

class IngredientController extends Controller
{
    /** GET /api/ingredients — для формы рецепта и конструктора */
    public function index()
    {
        return IngredientResource::collection(Ingredient::orderBy('name')->get());
    }
}
