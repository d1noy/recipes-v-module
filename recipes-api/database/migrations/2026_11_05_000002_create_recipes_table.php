<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 100);
            $table->text('description');
            $table->unsignedInteger('calories');
            $table->unsignedInteger('cooking_time'); // минуты
            $table->string('image')->nullable();
            $table->timestamps();
        });

        // Связь «рецепт — ингредиенты»
        Schema::create('ingredient_recipe', function (Blueprint $table) {
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->primary(['recipe_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_recipe');
        Schema::dropIfExists('recipes');
    }
};
