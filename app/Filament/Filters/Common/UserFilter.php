<?php

namespace App\Filament\Filters\Common;

use Filament\Tables\Filters\SelectFilter;

class UserFilter
{
    public static function make(): SelectFilter
    {
        return SelectFilter::make('user_id')
            ->label('Usuário')
            ->relationship('user', 'name')
            ->searchable()
            ->preload()
            ->visible(fn () => (bool) auth()->user()?->is_master);
    }
}
