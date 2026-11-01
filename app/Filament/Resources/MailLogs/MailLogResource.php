<?php

namespace App\Filament\Resources\MailLogs;

use App\Filament\Resources\MailLogs\Pages\ListMailLogs;
use App\Filament\Resources\MailLogs\Tables\MailLogsTable;
use App\Models\MailLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MailLogResource extends Resource
{
    protected static ?string $model = MailLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'Log';

    protected static ?string $pluralModelLabel = 'Logs';

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?string $navigationLabel = 'Logs';

    protected static string|UnitEnum|null $navigationGroup = 'Disparos';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return MailLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return ['index' => ListMailLogs::route('/')];
    }
}
