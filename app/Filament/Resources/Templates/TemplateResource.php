<?php

namespace App\Filament\Resources\Templates;

use App\Filament\Resources\Templates\Pages\CreateTemplate;
use App\Filament\Resources\Templates\Pages\EditTemplate;
use App\Filament\Resources\Templates\Pages\ListTemplates;
use App\Filament\Resources\Templates\Pages\ViewTemplate;
use App\Filament\Resources\Templates\Schemas\TemplateForm;
use App\Filament\Resources\Templates\Schemas\TemplateInfolist;
use App\Filament\Resources\Templates\Tables\TemplatesTable;
use App\Models\Template;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Template';

    protected static ?string $pluralModelLabel = 'Templates';

    protected static ?string $navigationLabel = 'Templates';

    protected static string|UnitEnum|null $navigationGroup = 'Cadastros';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return TemplateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TemplateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTemplates::route('/'),
            'create' => CreateTemplate::route('/create'),
            'view' => ViewTemplate::route('/{record}'),
            'edit' => EditTemplate::route('/{record}/edit'),
        ];
    }
}
