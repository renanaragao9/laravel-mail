<?php

namespace App\Filament\Resources\MailLogs\Pages;

use App\Filament\Resources\MailLogs\MailLogResource;
use Filament\Resources\Pages\ListRecords;

class ListMailLogs extends ListRecords
{
    protected static string $resource = MailLogResource::class;
}
