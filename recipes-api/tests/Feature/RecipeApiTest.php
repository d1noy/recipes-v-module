<?php

namespace Tests\Feature;

use App\Events\RecipeCreated;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecipeApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        $a = Ingredient::firstOrCreate(['name' => 'Яйца']);
        $b = Ingredient::firstOrCreate(['name' => 'Молоко']);

        return array_merge([
            'title' => 'Омлет',
            'description' => 'Воздушный омлет на молоке за 10 минут.',
            'calories' => 210,
            'cooking_time' => 10,
            'image' => UploadedFile::fake()->image('omelet.jpg', 600, 400),
            'ingredients' => [$a->id, $b->id],
        ], $override);
    }

    public function test_register_and_login(): void
    {
        $this->postJson('/api/register', ['email' => 'a@b.com', 'name' => 'Аня', 'password' => 'password123'])->assertCreated();
        $this->postJson('/api/login', ['email' => 'a@b.com', 'password' => 'password123'])
            ->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
        $this->postJson('/api/login', ['email' => 'a@b.com', 'password' => 'wrong'])->assertStatus(401);
    }

    public function test_register_validation(): void
    {
        $this->postJson('/api/register', [])->assertStatus(422)->assertJsonValidationErrors(['email', 'name', 'password']);
    }

    public function test_create_recipe_broadcasts_and_validates(): void
    {
        Storage::fake('public');
        Event::fake([RecipeCreated::class]);
        $user = User::create(['name' => 'Аня', 'email' => 'a@b.com', 'password' => 'password123']);

        $this->postJson('/api/recipes', $this->payload())->assertUnauthorized();

        $this->actingAs($user, 'sanctum')->postJson('/api/recipes', $this->payload())
            ->assertCreated()->assertJsonPath('data.title', 'Омлет')->assertJsonPath('data.is_mine', true);
        Event::assertDispatched(RecipeCreated::class);

        $this->actingAs($user, 'sanctum')->postJson('/api/recipes', ['title' => ''])
            ->assertStatus(422)->assertJsonValidationErrors(['title', 'description', 'calories', 'cooking_time', 'image', 'ingredients']);
    }

    public function test_list_sort_and_constructor_filter(): void
    {
        $user = User::create(['name' => 'Аня', 'email' => 'a@b.com', 'password' => 'password123']);
        [$egg, $milk, $flour] = [Ingredient::create(['name' => 'Яйца']), Ingredient::create(['name' => 'Молоко']), Ingredient::create(['name' => 'Мука'])];

        $omelet = $user->recipes()->create(['title' => 'Омлет', 'description' => 'd', 'calories' => 210, 'cooking_time' => 10]);
        $omelet->ingredients()->sync([$egg->id, $milk->id]);
        $pancake = $user->recipes()->create(['title' => 'Блины', 'description' => 'd', 'calories' => 230, 'cooking_time' => 25]);
        $pancake->ingredients()->sync([$egg->id, $milk->id, $flour->id]);

        // конструктор: все выбранные ингредиенты должны быть в рецепте
        $this->getJson('/api/recipes?ingredients[]=' . $flour->id)->assertOk()->assertJsonCount(1, 'data');
        $this->getJson("/api/recipes?ingredients[]={$egg->id}&ingredients[]={$milk->id}")->assertJsonCount(2, 'data');

        // сортировка
        $this->getJson('/api/recipes?sort=calories&order=asc')->assertJsonPath('data.0.title', 'Омлет');
        $this->getJson('/api/recipes?sort=calories&order=desc')->assertJsonPath('data.0.title', 'Блины');
        $this->getJson('/api/recipes?sort=bad')->assertStatus(422);
    }

    public function test_only_owner_can_delete(): void
    {
        $owner = User::create(['name' => 'Аня', 'email' => 'a@b.com', 'password' => 'password123']);
        $other = User::create(['name' => 'Иван', 'email' => 'i@b.com', 'password' => 'password123']);
        $recipe = $owner->recipes()->create(['title' => 'Омлет', 'description' => 'd', 'calories' => 1, 'cooking_time' => 1]);

        $this->actingAs($other, 'sanctum')->deleteJson("/api/recipes/{$recipe->id}")->assertForbidden();
        $this->actingAs($owner, 'sanctum')->deleteJson("/api/recipes/{$recipe->id}")->assertNoContent();
        $this->assertNull(Recipe::find($recipe->id));
    }
}
