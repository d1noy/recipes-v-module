<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class RecipeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = auth('sanctum')->id(); // токен необязателен: гость увидит is_mine = false

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'calories' => $this->calories,
            'cooking_time' => $this->cooking_time,
            'image' => $this->image ? Storage::disk('public')->url($this->image) : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'author' => ['id' => $this->user_id, 'name' => $this->whenLoaded('user', fn () => $this->user->name)],
            'ingredients' => IngredientResource::collection($this->whenLoaded('ingredients')),
            'is_mine' => $userId !== null && $this->user_id === $userId,
        ];
    }
}
