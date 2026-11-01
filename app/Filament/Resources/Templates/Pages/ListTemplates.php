<?php

namespace App\Filament\Resources\Templates\Pages;

use App\Filament\Resources\Templates\TemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListTemplates extends ListRecords
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon(Heroicon::OutlinedPlus),
        ];
    }
}
