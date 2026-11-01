<?php

namespace App\Filament\Filters\MailLog;

use App\Enums\MailStatus;
use Filament\Tables\Filters\SelectFilter;

class StatusFilter
{
    public static function make(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label('Status')
            ->options(collect(MailStatus::cases())->mapWithKeys(fn (MailStatus $s) => [$s->value => $s->label()]));
    }
}
