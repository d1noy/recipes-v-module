<?php

namespace App\Events;

use App\Models\Recipe;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/** Публичный канал `recipes`, событие `.recipe.created`. ShouldBroadcastNow — без очереди и воркера. */
class RecipeCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Recipe $recipe)
    {
    }

    public function broadcastOn(): array
    {
        return [new Channel('recipes')];
    }

    public function broadcastAs(): string
    {
        return 'recipe.created';
    }

    public function broadcastWith(): array
    {
        $r = $this->recipe->loadMissing('user');

        return [
            'id' => $r->id,
            'title' => $r->title,
            'description' => $r->description,
            'calories' => $r->calories,
            'cooking_time' => $r->cooking_time,
            'image' => $r->image ? Storage::disk('public')->url($r->image) : null,
            'created_at' => $r->created_at?->toIso8601String(),
            'author' => ['id' => $r->user_id, 'name' => $r->user?->name],
        ];
    }
}
