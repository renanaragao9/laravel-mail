<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Dados do Contato')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(false)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nome'),

                        TextEntry::make('email')
                            ->label('E-mail'),

                        TextEntry::make('phone')
                            ->label('Telefone')
                            ->placeholder('-'),

                        TextEntry::make('company')
                            ->label('Empresa')
                            ->placeholder('-'),

                        TextEntry::make('notes')
                            ->label('Observações')
                            ->placeholder('-')
                            ->columnSpanFull(),

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
