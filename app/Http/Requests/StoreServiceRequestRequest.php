<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La ruta ya está protegida por middleware('role:citizen'),
        // aquí solo confirmamos que tenga citizen_profile creado.
        return $this->user()->citizenProfile()->exists();
    }

    public function rules(): array
    {
        return [
            'origin_lat' => 'required|numeric|between:-90,90',
            'origin_lng' => 'required|numeric|between:-180,180',
            'origin_address' => 'nullable|string|max:255',
            'destination_lat' => 'required|numeric|between:-90,90',
            'destination_lng' => 'required|numeric|between:-180,180',
            'destination_address' => 'nullable|string|max:255',
        ];
    }
}
