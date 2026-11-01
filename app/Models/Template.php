<?php

namespace App\Models;

use App\Enums\TemplateType;
use App\Models\Concerns\BelongsToUser;

class Template extends BaseModel
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'name', 'subject', 'type', 'image_path', 'html'];

    protected function casts(): array
    {
        return ['type' => TemplateType::class];
    }
}
