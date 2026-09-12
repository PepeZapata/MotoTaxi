<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DriverDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La ruta ya está detrás de middleware('role:admin').
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => 'required|in:approved,rejected',
        ];
    }
}
