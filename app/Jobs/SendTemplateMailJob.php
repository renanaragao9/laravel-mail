<?php

namespace App\Jobs;

use App\Enums\MailStatus;
use App\Mail\TemplateMail;
use App\Models\Contact;
use App\Models\MailLog;
use App\Models\Template;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendTemplateMailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public readonly int $templateId,
        public readonly int $contactId,
    ) {}

    public function handle(): void
    {
        $template = Template::withoutGlobalScopes()->findOrFail($this->templateId);
        $contact = Contact::withoutGlobalScopes()->findOrFail($this->contactId);

        $log = [
            'user_id' => $template->user_id,
            'contact_id' => $contact->id,
            'template_id' => $template->id,
            'email' => $contact->email,
            'subject' => $template->subject,
        ];

        try {
            Mail::to($contact->email)->send(new TemplateMail($template, $contact));
            MailLog::create($log + ['status' => MailStatus::Sent, 'sent_at' => now()]);
        } catch (Throwable $e) {
            MailLog::create($log + ['status' => MailStatus::Failed, 'error' => $e->getMessage()]);
        }
    }
}
