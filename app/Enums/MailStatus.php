<?php

namespace App\Enums;

enum MailStatus: string
{
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Sent => 'Enviado',
            self::Failed => 'Falhou',
        };
    }
}
