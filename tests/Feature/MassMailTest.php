<?php

namespace Tests\Feature;

use App\Enums\MailStatus;
use App\Jobs\SendTemplateMailJob;
use App\Models\Contact;
use App\Models\MailLog;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MassMailTest extends TestCase
{
    use RefreshDatabase;

    private function contactFor(User $user, string $email): Contact
    {
        return Contact::withoutGlobalScopes()->create(['user_id' => $user->id, 'name' => 'Ana', 'email' => $email]);
    }

    public function test_common_user_only_sees_own_records_and_master_sees_all(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $master = User::factory()->create(['is_master' => true]);
        $this->contactFor($a, 'a@x.com');
        $this->contactFor($b, 'b@x.com');

        $this->actingAs($a);
        $this->assertSame(['a@x.com'], Contact::pluck('email')->all());

        $this->actingAs($master);
        $this->assertCount(2, Contact::all());
    }

    public function test_job_sends_mail_and_logs_success(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $contact = $this->contactFor($user, 'a@x.com');
        $template = Template::withoutGlobalScopes()->create([
            'user_id' => $user->id, 'name' => 'T', 'subject' => 'Oi {{nome}}',
            'color' => '#2563eb', 'blocks' => [['type' => 'title', 'data' => ['text' => 'Oi {{nome}}']], ['type' => 'text', 'data' => ['content' => '<p>Olá {{nome}}</p>']], ['type' => 'button', 'data' => ['label' => 'Ver', 'url' => 'https://example.com']], ['type' => 'divider', 'data' => []]],
        ]);

        (new SendTemplateMailJob($template->id, $contact->id))->handle();

        $log = MailLog::withoutGlobalScopes()->firstOrFail();
        $this->assertSame(MailStatus::Sent, $log->status);
        $this->assertSame($user->id, $log->user_id);
    }

    public function test_job_logs_failure_when_sending_throws(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp caiu'));
        $user = User::factory()->create();
        $contact = $this->contactFor($user, 'a@x.com');
        $template = Template::withoutGlobalScopes()->create([
            'user_id' => $user->id, 'name' => 'T', 'subject' => 'S',
            'color' => '#2563eb', 'blocks' => [],
        ]);

        (new SendTemplateMailJob($template->id, $contact->id))->handle();

        $log = MailLog::withoutGlobalScopes()->firstOrFail();
        $this->assertSame(MailStatus::Failed, $log->status);
        $this->assertSame('smtp caiu', $log->error);
    }

    public function test_only_master_can_open_users_resource(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/users')->assertForbidden();
        $this->actingAs(User::factory()->create(['is_master' => true]))->get('/admin/users')->assertOk();
    }

    public function test_common_user_can_open_panel_pages(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['/admin', '/admin/contacts', '/admin/templates', '/admin/mail-logs'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
