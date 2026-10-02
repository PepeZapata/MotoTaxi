<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DriverDocumentDecisionRequest extends FormRequest
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
            'reason' => 'required_if:decision,rejected|nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required_if' => 'Indica el motivo del rechazo, para que el conductor sepa qué corregir.',
        ];
    }
}
