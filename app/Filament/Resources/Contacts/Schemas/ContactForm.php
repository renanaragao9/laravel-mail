<?php

namespace App\Filament\Resources\Contacts\Schemas;

use App\Models\Contact;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Dados do Contato')->columnSpanFull()->columns(2)->schema([
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),

                TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->required()
                    ->unique(
                        Contact::class,
                        'email',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule) => $rule->where('user_id', auth()->id()),
                    ),

                TextInput::make('phone')
                    ->label('Telefone')
                    ->tel(),

                TextInput::make('company')
                    ->label('Empresa'),

                Textarea::make('notes')
                    ->label('Observações')
                    ->columnSpanFull(),
            ]),
        ]);
    }
}
