<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    protected function contact(array $attributes = []): Contact
    {
        return Contact::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'name' => 'Ana',
            'email' => 'ana@example.com',
        ], $attributes));
    }

    public function test_can_create_contact(): void
    {
        $contact = $this->contact();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'Ana',
            'email' => 'ana@example.com',
        ]);
    }

    public function test_can_read_contact(): void
    {
        $contact = $this->contact();

        $found = Contact::find($contact->id);

        $this->assertNotNull($found);
        $this->assertSame('ana@example.com', $found->email);
    }

    public function test_can_update_contact(): void
    {
        $contact = $this->contact();

        $contact->update(['name' => 'Ana Maria', 'phone' => '85999990000']);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'Ana Maria',
            'phone' => '85999990000',
        ]);
    }

    public function test_can_delete_contact(): void
    {
        $contact = $this->contact();

        $contact->delete();

        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
        $this->assertNull(Contact::find($contact->id));
    }

    public function test_email_is_unique_per_user(): void
    {
        $contact = $this->contact();

        $this->contact(['user_id' => User::factory()->create()->id]);

        $this->expectException(QueryException::class);

        Contact::create(['user_id' => $contact->user_id, 'name' => 'Outra', 'email' => 'ana@example.com']);
    }

    public function test_user_id_is_filled_from_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $contact = Contact::create(['name' => 'Ana', 'email' => 'ana@example.com']);

        $this->assertSame($user->id, $contact->user_id);
    }

    public function test_has_user_relationship(): void
    {
        $this->assertInstanceOf(BelongsTo::class, $this->contact()->user());
    }
}
