<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_pages_render(): void
    {
        $master = User::factory()->create(['is_master' => true]);
        $this->actingAs($master);
        $contact = Contact::create(['name' => 'Ana', 'email' => 'a@x.com']);
        $template = Template::create(['name' => 'T', 'subject' => 'S', 'type' => 'html', 'html' => '<p>x</p>']);

        foreach ([
            '/admin/contacts/create', "/admin/contacts/{$contact->id}", "/admin/contacts/{$contact->id}/edit",
            '/admin/templates/create', "/admin/templates/{$template->id}", "/admin/templates/{$template->id}/edit",
            '/admin/users/create', "/admin/users/{$master->id}", "/admin/users/{$master->id}/edit",
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
