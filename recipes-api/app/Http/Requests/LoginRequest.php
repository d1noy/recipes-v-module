<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Поле email обязательно для заполнения.',
            'email.email' => 'Введите корректный email.',
            'password.required' => 'Поле «Пароль» обязательно для заполнения.',
        ];
    }
}
