<?php

namespace App\Filament\Resources\Templates\Schemas;

use App\Enums\TemplateType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Dados do Template')->columnSpanFull()->columns(2)->schema([
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),

                TextInput::make('subject')
                    ->label('Assunto')
                    ->required()
                    ->helperText('Variáveis: {{nome}}, {{email}}, {{telefone}}'),

                Select::make('type')
                    ->label('Tipo')
                    ->options(collect(TemplateType::cases())->mapWithKeys(fn (TemplateType $t) => [$t->value => $t->label()]))
                    ->default(TemplateType::Html->value)
                    ->required()
                    ->live(),

                FileUpload::make('image_path')
                    ->label('Imagem')
                    ->image()
                    ->disk('public')
                    ->directory('templates')
                    ->visible(fn (Get $get) => $get('type') === TemplateType::Image->value)
                    ->required(fn (Get $get) => $get('type') === TemplateType::Image->value),

                Textarea::make('html')
                    ->label('HTML')
                    ->rows(18)
                    ->columnSpanFull()
                    ->helperText('Variáveis: {{nome}}, {{email}}, {{telefone}}')
                    ->visible(fn (Get $get) => $get('type') === TemplateType::Html->value)
                    ->required(fn (Get $get) => $get('type') === TemplateType::Html->value),
            ]),
        ]);
    }
}
