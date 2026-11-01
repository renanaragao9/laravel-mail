<?php

namespace App\Enums;

enum TemplateType: string
{
    case Image = 'image';
    case Html = 'html';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Imagem',
            self::Html => 'HTML',
        };
    }
}
