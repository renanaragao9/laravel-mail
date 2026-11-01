<?php

namespace App\Mail;

use App\Models\Contact;
use App\Models\Template;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TemplateMail extends Mailable
{
    public const FONTS = [
        'arial' => 'Arial, Helvetica, sans-serif',
        'georgia' => 'Georgia, \'Times New Roman\', serif',
        'verdana' => 'Verdana, Geneva, sans-serif',
        'trebuchet' => '\'Trebuchet MS\', Helvetica, sans-serif',
    ];

    public const TITLE_SIZES = ['small' => 20, 'medium' => 26, 'large' => 36];

    public const IMAGE_WIDTHS = ['full' => '100%', 'medium' => '70%', 'small' => '40%'];

    public function __construct(
        public readonly Template $template,
        public readonly Contact $contact,
        public readonly bool $preview = false,
        public readonly ?array $draftBlocks = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->replace($this->template->subject, false));
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.template',
            with: [
                'color' => $this->template->color ?: '#2563eb',
                'background' => $this->template->background_color ?: '#f4f4f5',
                'font' => self::FONTS[$this->template->font] ?? self::FONTS['arial'],
                'preview' => $this->preview,
                'blocks' => $this->blocks(),
            ],
        );
    }

    private function blocks(): array
    {
        return collect($this->draftBlocks ?? $this->template->blocks ?? [])
            ->values()
            ->map(function (array $block): array {
                $data = $block['data'] ?? [];
                $align = in_array($data['align'] ?? null, ['left', 'center', 'right'], true) ? $data['align'] : 'left';

                return match ($block['type']) {
                    'title' => [
                        'type' => 'title',
                        'align' => $align,
                        'size' => self::TITLE_SIZES[$data['size'] ?? 'medium'] ?? 26,
                        'text' => $this->replace((string) ($data['text'] ?? ''), true),
                    ],
                    'text' => [
                        'type' => 'text',
                        'align' => $align,
                        'html' => $this->replace($this->richText($data['content'] ?? ''), true),
                    ],
                    'image' => $this->imageBlock($data, $align),
                    'button' => [
                        'type' => 'button',
                        'align' => $align,
                        'label' => $this->replace((string) ($data['label'] ?? ''), true),
                        'url' => $data['url'] ?? '#',
                        'style' => $data['style'] ?? 'filled',
                        'radius' => ['square' => 0, 'rounded' => 6, 'pill' => 999][$data['shape'] ?? 'rounded'] ?? 6,
                    ],
                    'spacer' => ['type' => 'spacer', 'height' => (int) ($data['height'] ?? 24)],
                    'footer' => [
                        'type' => 'footer',
                        'align' => $align,
                        'text' => $this->replace((string) ($data['text'] ?? ''), true),
                    ],
                    default => ['type' => 'divider'],
                };
            })
            ->all();
    }

    private function richText(string|array|null $content): string
    {
        return RichContentRenderer::make($content)->toHtml();
    }

    private function imageBlock(array $data, string $align): array
    {
        $image = $data['image'] ?? '';
        $image = is_array($image) ? (reset($image) ?: '') : $image;

        $isUpload = $image instanceof TemporaryUploadedFile;

        return [
            'type' => 'image',
            'align' => $align,
            'width' => self::IMAGE_WIDTHS[$data['width'] ?? 'full'] ?? '100%',
            'radius' => ! empty($data['rounded']) ? 12 : 0,
            'path' => $isUpload ? $image->getRealPath() : storage_path('app/public/'.$image),
            'url' => $isUpload ? $image->temporaryUrl() : Storage::disk('public')->url((string) $image),
            'link' => $data['link'] ?? null,
        ];
    }

    private function replace(string $text, bool $escape): string
    {
        $values = [
            '{{nome}}' => $this->contact->name,
            '{{email}}' => $this->contact->email,
            '{{telefone}}' => (string) $this->contact->phone,
        ];

        if ($escape) {
            $values = array_map('e', $values);
        }

        return strtr($text, $values);
    }
}
