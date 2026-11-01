<?php

namespace App\Mail;

use App\Enums\TemplateType;
use App\Models\Contact;
use App\Models\Template;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TemplateMail extends Mailable
{
    public function __construct(
        public readonly Template $template,
        public readonly Contact $contact,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->replace($this->template->subject, false));
    }

    public function content(): Content
    {
        $isImage = $this->template->type === TemplateType::Image;

        return new Content(
            view: 'mail.template',
            with: [
                'isImage' => $isImage,
                'imagePath' => $isImage ? storage_path('app/public/'.$this->template->image_path) : null,
                'body' => $isImage ? '' : $this->replace((string) $this->template->html, true),
            ],
        );
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
