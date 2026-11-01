<?php

namespace App\Filament\Filters\Contact;

use Filament\Tables\Filters\TernaryFilter;

class CompanyFilter
{
    public static function make(): TernaryFilter
    {
        return TernaryFilter::make('company')
            ->label('Empresa')
            ->placeholder('Todos')
            ->trueLabel('Com empresa')
            ->falseLabel('Sem empresa')
            ->nullable();
    }
}
