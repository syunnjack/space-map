<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VacancySlot extends Model
{
    protected $fillable = [
        'venue_id',
        'available_date',
        'start_time',
        'end_time',
        'comment',
        'nickname',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'available_date' => 'date',
        ];
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}
