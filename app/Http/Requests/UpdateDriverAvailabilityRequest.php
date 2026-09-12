<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDriverAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->driverProfile !== null;
    }

    public function rules(): array
    {
        return [
            'availability_status' => 'required|in:available,offline',
        ];
    }
}
