<?php

namespace App\Filament\Resources\Templates\Pages;

use App\Filament\Resources\Templates\TemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditTemplate extends EditRecord
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->icon(Heroicon::OutlinedEye),

            DeleteAction::make()
                ->icon(Heroicon::OutlinedTrash),
        ];
    }
}
