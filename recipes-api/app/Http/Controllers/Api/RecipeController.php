<?php

namespace App\Http\Controllers\Api;

use App\Events\RecipeCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecipeRequest;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RecipeController extends Controller
{
    /**
     * GET /api/recipes — публичный список (гость и авторизованный).
     * ?sort=date|time|calories  &order=asc|desc (по умолчанию date desc — новые сверху)
     * ?ingredients[]=1&ingredients[]=2 — конструктор: рецепты, где есть ВСЕ выбранные ингредиенты
     */
    public function index(Request $request)
    {
        $this->validateSort($request);

        $query = Recipe::with(['user', 'ingredients']);

        $ids = $request->input('ingredients', []);
        $ids = is_string($ids) ? explode(',', $ids) : Arr::wrap($ids);
        foreach (array_filter(array_map('intval', $ids)) as $id) {
            $query->whereHas('ingredients', fn (Builder $q) => $q->where('ingredients.id', $id));
        }

        return RecipeResource::collection($this->sorted($query, $request)->get());
    }

    /** GET /api/my/recipes — свои рецепты (нужен токен) */
    public function mine(Request $request)
    {
        $this->validateSort($request);

        $query = $request->user()->recipes()->with(['user', 'ingredients']);

        return RecipeResource::collection($this->sorted($query, $request)->get());
    }

    /** GET /api/recipes/{recipe} */
    public function show(Recipe $recipe): RecipeResource
    {
        return new RecipeResource($recipe->load(['user', 'ingredients']));
    }

    /** POST /api/recipes (multipart/form-data) */
    public function store(RecipeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $recipe = DB::transaction(function () use ($request, $data) {
            $recipe = $request->user()->recipes()->create([
                ...Arr::only($data, ['title', 'description', 'calories', 'cooking_time']),
                'image' => $request->file('image')?->store('recipes', 'public'),
            ]);
            $recipe->ingredients()->sync($data['ingredients']);

            return $recipe;
        });

        $recipe->load(['user', 'ingredients']);

        // Уведомление всем подписчикам канала. Если WebSocket-сервер недоступен — API не падает.
        try {
            RecipeCreated::dispatch($recipe);
        } catch (\Throwable $e) {
            report($e);
        }

        return (new RecipeResource($recipe))->response()->setStatusCode(201);
    }

    /**
     * PUT /api/recipes/{recipe}
     * Для multipart (с файлом) шлите POST + поле _method=PUT: PHP не разбирает multipart в PUT.
     */
    public function update(RecipeRequest $request, Recipe $recipe): RecipeResource
    {
        $this->authorizeOwner($request, $recipe);
        $data = $request->validated();

        DB::transaction(function () use ($request, $recipe, $data) {
            $recipe->fill(Arr::only($data, ['title', 'description', 'calories', 'cooking_time']));

            if ($request->hasFile('image')) {
                if ($recipe->image) {
                    Storage::disk('public')->delete($recipe->image);
                }
                $recipe->image = $request->file('image')->store('recipes', 'public');
            }

            $recipe->save();
            $recipe->ingredients()->sync($data['ingredients']);
        });

        return new RecipeResource($recipe->load(['user', 'ingredients']));
    }

    /** DELETE /api/recipes/{recipe} — только владелец */
    public function destroy(Request $request, Recipe $recipe)
    {
        $this->authorizeOwner($request, $recipe);

        if ($recipe->image) {
            Storage::disk('public')->delete($recipe->image);
        }
        $recipe->delete();

        return response()->noContent();
    }

    private function authorizeOwner(Request $request, Recipe $recipe): void
    {
        abort_if($recipe->user_id !== $request->user()->id, 403, 'Вы можете изменять и удалять только свои рецепты.');
    }

    private function validateSort(Request $request): void
    {
        $request->validate([
            'sort' => ['nullable', 'in:date,time,calories'],
            'order' => ['nullable', 'in:asc,desc'],
        ], [
            'sort.in' => 'Сортировка возможна по: date, time, calories.',
            'order.in' => 'Порядок сортировки: asc или desc.',
        ]);
    }

    private function sorted($query, Request $request)
    {
        $column = ['date' => 'created_at', 'time' => 'cooking_time', 'calories' => 'calories'][$request->query('sort', 'date')];
        $order = $request->query('order', 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($column, $order)->orderBy('id', $order);
    }
}
