<?php

namespace App\Filament\Resources\Templates\Pages;

use App\Filament\Resources\Templates\TemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewTemplate extends ViewRecord
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->icon(Heroicon::OutlinedPencil),

            DeleteAction::make()
                ->icon(Heroicon::OutlinedTrash),
        ];
    }
}
