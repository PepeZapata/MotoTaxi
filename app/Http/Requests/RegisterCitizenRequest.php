<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCitizenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ruta pública de registro
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe una cuenta con ese correo.',
            'phone.unique' => 'Ya existe una cuenta con ese teléfono.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
        ];
    }
}
