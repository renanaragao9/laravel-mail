<?php

namespace App\Filament\Resources\Templates\Schemas;

use App\Mail\TemplateMail;
use App\Models\Contact;
use App\Models\Template;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

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
                    ->helperText('Você pode usar {{nome}}, {{email}} e {{telefone}} do contato.'),
            ]),

            Section::make('Aparência')->columnSpanFull()->columns(3)->schema([
                ColorPicker::make('color')
                    ->label('Cor principal (títulos e botões)')
                    ->default('#2563eb')
                    ->required()
                    ->live(onBlur: true),

                ColorPicker::make('background_color')
                    ->label('Cor de fundo')
                    ->default('#f4f4f5')
                    ->required()
                    ->live(onBlur: true),

                Select::make('font')
                    ->label('Fonte')
                    ->options([
                        'arial' => 'Arial',
                        'georgia' => 'Georgia (com serifa)',
                        'verdana' => 'Verdana',
                        'trebuchet' => 'Trebuchet',
                    ])
                    ->default('arial')
                    ->required()
                    ->live(),
            ]),

            Grid::make(1)->columnSpanFull()->schema([
                Section::make('Conteúdo do e-mail')
                    ->description('Adicione blocos e arraste pela alça (⠿) para mudar a posição.')
                    ->columnSpanFull()
                    ->schema([
                        Builder::make('blocks')
                            ->hiddenLabel()
                            ->addActionLabel('Adicionar bloco')
                            ->blockPickerColumns(['default' => 2, 'md' => 3])
                            ->blockNumbers(false)
                            ->collapsible()
                            ->cloneable()
                            ->reorderableWithButtons()
                            ->live()
                            ->blocks([
                                Block::make('title')
                                    ->label(fn (?array $state): string => filled($state['text'] ?? null) ? 'Título: '.Str::limit($state['text'], 40) : 'Título')
                                    ->icon(Heroicon::OutlinedBars3BottomLeft)
                                    ->schema([
                                        TextInput::make('text')
                                            ->label('Título')
                                            ->required()
                                            ->live(onBlur: true),

                                        Select::make('size')
                                            ->label('Tamanho')
                                            ->options(['small' => 'Pequeno', 'medium' => 'Médio', 'large' => 'Grande'])
                                            ->default('medium')
                                            ->live(),

                                        self::align(),
                                    ]),

                                Block::make('text')
                                    ->label('Texto')
                                    ->icon(Heroicon::OutlinedDocumentText)
                                    ->schema([
                                        RichEditor::make('content')
                                            ->label('Texto')
                                            ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'bulletList', 'orderedList'])
                                            ->required()
                                            ->live(debounce: 800),

                                        self::align(),
                                    ]),

                                Block::make('image')
                                    ->label('Imagem')
                                    ->icon(Heroicon::OutlinedPhoto)
                                    ->schema([
                                        FileUpload::make('image')
                                            ->label('Imagem')
                                            ->image()
                                            ->disk('public')
                                            ->directory('templates')
                                            ->required()
                                            ->live(),

                                        TextInput::make('link')
                                            ->label('Link ao clicar (opcional)')
                                            ->url()
                                            ->live(onBlur: true),

                                        Select::make('width')
                                            ->label('Largura')
                                            ->options(['full' => 'Total', 'medium' => 'Média', 'small' => 'Pequena'])
                                            ->default('full')
                                            ->live(),

                                        Toggle::make('rounded')
                                            ->label('Bordas arredondadas')
                                            ->live(),

                                        self::align(),
                                    ]),

                                Block::make('button')
                                    ->label(fn (?array $state): string => filled($state['label'] ?? null) ? 'Botão: '.Str::limit($state['label'], 30) : 'Botão')
                                    ->icon(Heroicon::OutlinedCursorArrowRays)
                                    ->schema([
                                        TextInput::make('label')
                                            ->label('Texto do botão')
                                            ->required()
                                            ->live(onBlur: true),

                                        TextInput::make('url')
                                            ->label('Link')
                                            ->url()
                                            ->required()
                                            ->live(onBlur: true),

                                        Select::make('style')
                                            ->label('Estilo')
                                            ->options(['filled' => 'Preenchido', 'outline' => 'Apenas contorno'])
                                            ->default('filled')
                                            ->live(),

                                        Select::make('shape')
                                            ->label('Formato')
                                            ->options(['square' => 'Reto', 'rounded' => 'Arredondado', 'pill' => 'Pílula'])
                                            ->default('rounded')
                                            ->live(),

                                        self::align(),
                                    ]),

                                Block::make('footer')
                                    ->label('Rodapé')
                                    ->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
                                    ->schema([
                                        TextInput::make('text')
                                            ->label('Texto pequeno')
                                            ->required()
                                            ->live(onBlur: true),

                                        self::align('center'),
                                    ]),

                                Block::make('spacer')
                                    ->label('Espaço')
                                    ->icon(Heroicon::OutlinedArrowsUpDown)
                                    ->schema([
                                        Select::make('height')
                                            ->label('Altura')
                                            ->options([12 => 'Pequena', 24 => 'Média', 48 => 'Grande'])
                                            ->default(24)
                                            ->live(),
                                    ]),

                                Block::make('divider')
                                    ->label('Divisor')
                                    ->icon(Heroicon::OutlinedMinus)
                                    ->schema([]),
                            ]),
                    ]),

                Section::make('Pré-visualização')->columnSpanFull()->schema([
                    View::make('filament.template-preview')
                        ->viewData(fn (Get $get): array => [
                            'html' => (new TemplateMail(
                                new Template([
                                    'subject' => '',
                                    'color' => $get('color') ?: '#2563eb',
                                    'background_color' => $get('background_color') ?: '#f4f4f5',
                                    'font' => $get('font') ?: 'arial',
                                ]),
                                new Contact(['name' => 'Maria Exemplo', 'email' => 'maria@exemplo.com', 'phone' => '85999990000']),
                                preview: true,
                                draftBlocks: $get('blocks') ?? [],
                            ))->render(),
                        ]),
                ]),
            ]),
        ]);
    }

    private static function align(string $default = 'left'): ToggleButtons
    {
        return ToggleButtons::make('align')
            ->label('Alinhamento')
            ->options(['left' => 'Esquerda', 'center' => 'Centro', 'right' => 'Direita'])
            ->icons([
                'left' => Heroicon::OutlinedBars3BottomLeft,
                'center' => Heroicon::OutlinedBars3,
                'right' => Heroicon::OutlinedBars3BottomRight,
            ])
            ->default($default)
            ->grouped()
            ->inline()
            ->live();
    }
}
