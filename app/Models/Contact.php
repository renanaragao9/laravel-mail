<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;

class Contact extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'company',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
        ];
    }
}
