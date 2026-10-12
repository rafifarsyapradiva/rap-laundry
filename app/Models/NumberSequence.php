<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NumberSequence extends Model
{
    protected $fillable = [
        'sequence_key',
        'period',
        'last_value',
    ];

    protected function casts(): array
    {
        return [
            'last_value' => 'integer',
        ];
    }
}
