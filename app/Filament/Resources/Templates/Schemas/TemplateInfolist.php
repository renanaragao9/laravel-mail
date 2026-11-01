<?php

namespace App\Filament\Resources\Templates\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TemplateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Dados do Template')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(false)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nome'),

                        TextEntry::make('subject')
                            ->label('Assunto'),

                        TextEntry::make('type')
                            ->label('Tipo')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->label()),

                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),

                        TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
