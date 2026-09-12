<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripStatusLog extends Model
{
    protected $fillable = [
        'service_request_id',
        'status',
        'changed_by',
        'note',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'logged_at' => 'datetime',
        ];
    }

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
