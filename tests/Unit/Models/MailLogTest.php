<?php

namespace Tests\Unit\Models;

use App\Enums\MailStatus;
use App\Models\Contact;
use App\Models\MailLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailLogTest extends TestCase
{
    use RefreshDatabase;

    protected function log(array $attributes = []): MailLog
    {
        return MailLog::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'email' => 'ana@example.com',
            'subject' => 'Oi',
            'status' => MailStatus::Sent,
            'sent_at' => now(),
        ], $attributes));
    }

    public function test_can_create_log(): void
    {
        $log = $this->log();

        $this->assertDatabaseHas('mail_logs', [
            'id' => $log->id,
            'email' => 'ana@example.com',
            'status' => 'sent',
        ]);
    }

    public function test_can_read_log(): void
    {
        $log = $this->log();

        $this->assertNotNull(MailLog::find($log->id));
    }

    public function test_can_update_log(): void
    {
        $log = $this->log();

        $log->update(['status' => MailStatus::Failed, 'error' => 'smtp caiu']);

        $this->assertDatabaseHas('mail_logs', ['id' => $log->id, 'status' => 'failed', 'error' => 'smtp caiu']);
    }

    public function test_can_delete_log(): void
    {
        $log = $this->log();

        $log->delete();

        $this->assertDatabaseMissing('mail_logs', ['id' => $log->id]);
    }

    public function test_status_is_cast_to_enum(): void
    {
        $this->assertSame(MailStatus::Sent, $this->log()->fresh()->status);
    }

    public function test_log_keeps_contact_relationship_after_contact_is_deleted(): void
    {
        $user = User::factory()->create();
        $contact = Contact::create(['user_id' => $user->id, 'name' => 'Ana', 'email' => 'ana@example.com']);
        $log = $this->log(['user_id' => $user->id, 'contact_id' => $contact->id]);

        $contact->delete();

        $this->assertNotNull($log->fresh()->contact);
    }

    public function test_has_relationships(): void
    {
        $log = $this->log();

        $this->assertInstanceOf(BelongsTo::class, $log->user());
        $this->assertInstanceOf(BelongsTo::class, $log->contact());
        $this->assertInstanceOf(BelongsTo::class, $log->template());
    }
}
