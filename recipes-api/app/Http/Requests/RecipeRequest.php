<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Создание и редактирование рецепта (при редактировании изображение необязательно). */
class RecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // владельца проверяет контроллер (403)
    }

    public function rules(): array
    {
        $isUpdate = $this->route('recipe') !== null;

        return [
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:2000'],
            'calories' => ['required', 'integer', 'min:0', 'max:10000'],
            'cooking_time' => ['required', 'integer', 'min:1', 'max:10000'],
            'image' => [$isUpdate ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'ingredients' => ['required', 'array', 'min:1'],
            'ingredients.*' => ['integer', 'distinct', 'exists:ingredients,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Укажите название рецепта.',
            'title.max' => 'Название не должно превышать 100 символов.',
            'description.required' => 'Описание обязательно для заполнения.',
            'description.max' => 'Описание не должно превышать 2000 символов.',
            'calories.required' => 'Укажите калорийность.',
            'calories.integer' => 'Калорийность должна быть целым числом.',
            'calories.min' => 'Калорийность не может быть отрицательной.',
            'cooking_time.required' => 'Укажите время приготовления.',
            'cooking_time.integer' => 'Время приготовления должно быть целым числом минут.',
            'cooking_time.min' => 'Время приготовления должно быть не менее 1 минуты.',
            'image.required' => 'Загрузите изображение.',
            'image.image' => 'Файл должен быть изображением.',
            'image.mimes' => 'Допустимые форматы: jpg, jpeg, png, webp.',
            'image.max' => 'Размер изображения не должен превышать 2 МБ.',
            'ingredients.required' => 'Выберите хотя бы один ингредиент.',
            'ingredients.min' => 'Выберите хотя бы один ингредиент.',
            'ingredients.*.exists' => 'Выбран несуществующий ингредиент.',
            'ingredients.*.distinct' => 'Ингредиенты не должны повторяться.',
        ];
    }
}
