<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $anna = User::create(['name' => 'Анна', 'email' => 'user@example.com', 'password' => 'password123']);
        $ivan = User::create(['name' => 'Иван', 'email' => 'ivan@example.com', 'password' => 'password123']);

        // id ингредиентов идут по порядку: 1..12
        foreach (['Говядина', 'Свёкла', 'Картофель', 'Морковь', 'Лук', 'Капуста', 'Тесто', 'Яйца', 'Молоко', 'Мука', 'Сметана', 'Рис'] as $name) {
            Ingredient::create(['name' => $name]);
        }

        // название, описание, ккал, минут, ингредиенты, владелец
        $recipes = [
            ['Борщ', 'Классический борщ на говяжьем бульоне со свёклой, капустой и сметаной.', 320, 120, [1, 2, 3, 4, 5, 6, 11], $anna],
            ['Блины', 'Тонкие ажурные блины на молоке. Подходят и с вареньем, и со сметаной.', 230, 25, [8, 9, 10], $anna],
            ['Плов', 'Рассыпчатый плов с говядиной, морковью и луком, приготовленный в казане.', 450, 80, [1, 4, 5, 12], $ivan],
            ['Окрошка', 'Освежающий холодный суп с овощами и яйцом. Идеален в жаркий день.', 180, 15, [3, 8, 11], $ivan],
            ['Пельмени', 'Домашние пельмени с мясной начинкой и тонким тестом.', 380, 150, [1, 5, 7, 8, 10], $ivan],
            ['Омлет', 'Воздушный омлет на молоке за 10 минут. Простой завтрак на каждый день.', 210, 10, [8, 9], $anna],
            ['Голубцы', 'Нежные голубцы в капустных листьях с мясом и рисом в сметанном соусе.', 290, 110, [1, 5, 6, 12], $ivan],
            ['Сырники', 'Румяные сырники с хрустящей корочкой и нежной серединой.', 270, 30, [8, 10, 11], $anna],
        ];

        foreach ($recipes as $i => [$title, $description, $calories, $time, $ingredients, $owner]) {
            // картинка из database/seeders/images → storage/app/public/recipes (нужен storage:link)
            $n = $i + 1;
            Storage::disk('public')->put("recipes/seed-r{$n}.svg", file_get_contents(__DIR__ . "/images/r{$n}.svg"));

            $recipe = $owner->recipes()->create([
                'title' => $title, 'description' => $description, 'calories' => $calories, 'cooking_time' => $time,
                'image' => "recipes/seed-r{$n}.svg",
            ]);
            $recipe->created_at = now()->subDays(count($recipes) - $i); // чтобы сортировка по дате была наглядной
            $recipe->save();
            $recipe->ingredients()->sync($ingredients);
        }
    }
}
