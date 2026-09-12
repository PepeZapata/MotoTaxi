<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Solo el conductor dueño de esta asignación puede cambiar su estado.
        $tripAssignment = $this->route('tripAssignment');

        return $tripAssignment
            && $this->user()->driverProfile
            && $tripAssignment->driver_profile_id === $this->user()->driverProfile->id;
    }

    public function rules(): array
    {
        return [
            'acceptance_status' => 'required|in:en_route_to_pickup,arrived,started,finished,cancelled',
        ];
    }
}
