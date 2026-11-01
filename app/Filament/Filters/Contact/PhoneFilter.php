<?php

namespace App\Filament\Filters\Contact;

use Filament\Tables\Filters\TernaryFilter;

class PhoneFilter
{
    public static function make(): TernaryFilter
    {
        return TernaryFilter::make('phone')
            ->label('Telefone')
            ->placeholder('Todos')
            ->trueLabel('Com telefone')
            ->falseLabel('Sem telefone')
            ->nullable();
    }
}
