<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;

class Template extends BaseModel
{
    use BelongsToUser;

    protected $fillable = [
        'user_id',
        'name',
        'subject',
        'color',
        'background_color',
        'font',
        'blocks',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'blocks' => 'array',
        ];
    }
}
