<?php

namespace App\Http\Requests;

use App\Models\DriverDocument;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDriverDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La ruta ya está detrás de middleware('role:driver').
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('type');
        $requiresExpiry = $type && DriverDocument::requiresExpiryFor($type);

        return [
            'type' => ['required', 'string', Rule::in(array_keys(DriverDocument::TYPES))],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'expires_at' => [$requiresExpiry ? 'required' : 'nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Indica qué tipo de documento es.',
            'type.in' => 'Ese tipo de documento no está en la lista esperada.',
            'file.required' => 'Selecciona una foto o PDF del documento.',
            'file.file' => 'El archivo no se pudo leer, intenta de nuevo.',
            'file.mimes' => 'El archivo debe ser una foto (JPG/PNG) o un PDF.',
            'file.max' => 'El archivo no debe pesar más de 10 MB.',
            'expires_at.required' => 'Este documento necesita la fecha de vencimiento.',
            'expires_at.date' => 'La fecha de vencimiento no es válida.',
            'expires_at.after' => 'La fecha de vencimiento debe ser futura (el documento ya estaría vencido).',
        ];
    }
}
