<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cancellation extends Model
{
    protected $fillable = [
        'service_request_id',
        'cancelled_by',
        'cancelled_by_role',
        'reason',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'cancelled_at' => 'datetime',
        ];
    }

    // --- Relaciones ---

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
