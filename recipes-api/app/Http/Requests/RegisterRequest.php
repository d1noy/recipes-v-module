<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'name' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Поле email обязательно для заполнения.',
            'email.email' => 'Введите корректный email.',
            'email.unique' => 'Пользователь с таким email уже существует.',
            'name.required' => 'Укажите имя.',
            'name.max' => 'Имя не должно превышать 100 символов.',
            'password.required' => 'Поле «Пароль» обязательно для заполнения.',
            'password.min' => 'Пароль должен содержать не менее 8 символов.',
        ];
    }
}
