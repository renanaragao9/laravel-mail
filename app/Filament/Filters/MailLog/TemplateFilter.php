<?php

namespace App\Filament\Filters\MailLog;

use Filament\Tables\Filters\SelectFilter;

class TemplateFilter
{
    public static function make(): SelectFilter
    {
        return SelectFilter::make('template_id')
            ->label('Template')
            ->relationship('template', 'name')
            ->searchable()
            ->preload();
    }
}
