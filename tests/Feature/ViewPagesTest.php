<?php

namespace Tests\Feature;

use App\Filament\Resources\Contacts\Pages\CreateContact;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Filament\Resources\MailLogs\Pages\ListMailLogs;
use App\Filament\Resources\Templates\Pages\CreateTemplate;
use App\Filament\Resources\Templates\Pages\ListTemplates;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Mail\TemplateMail;
use App\Models\Contact;
use App\Models\MailLog;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_pages_render(): void
    {
        $master = User::factory()->create(['is_master' => true]);
        $this->actingAs($master);
        $contact = Contact::create(['name' => 'Ana', 'email' => 'a@x.com']);
        $template = Template::create(['name' => 'T', 'subject' => 'S', 'color' => '#2563eb', 'blocks' => []]);

        foreach ([
            '/admin/contacts/create', "/admin/contacts/{$contact->id}", "/admin/contacts/{$contact->id}/edit",
            '/admin/templates/create', "/admin/templates/{$template->id}", "/admin/templates/{$template->id}/edit",
            '/admin/users/create', "/admin/users/{$master->id}", "/admin/users/{$master->id}/edit",
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_template_preview_renders_html_with_contact_data(): void
    {
        $template = Template::withoutGlobalScopes()->create([
            'user_id' => User::factory()->create()->id,
            'name' => 'T',
            'subject' => 'S',
            'color' => '#2563eb',
            'blocks' => [
                ['type' => 'title', 'data' => ['text' => 'Oi {{nome}}']],
                ['type' => 'text', 'data' => ['content' => '<p>Olá {{nome}}</p>']],
                ['type' => 'button', 'data' => ['label' => 'Ver', 'url' => 'https://example.com']],
                ['type' => 'divider', 'data' => []],
            ],
        ]);
        $contact = new Contact(['name' => 'Ana', 'email' => 'a@x.com']);

        $html = (new TemplateMail($template, $contact, preview: true))->render();

        $this->assertStringContainsString('Oi Ana', $html);
        $this->assertStringContainsString('<p>Olá Ana</p>', $html);
        $this->assertStringContainsString('https://example.com', $html);
        $this->assertStringContainsString('#2563eb', $html);
    }

    public function test_template_preview_action_exists(): void
    {
        $this->actingAs(User::factory()->create(['is_master' => true]));
        $template = Template::create(['name' => 'T', 'subject' => 'S', 'color' => '#2563eb', 'blocks' => []]);

        Livewire::test(ListTemplates::class)->assertTableActionExists('preview', record: $template);
    }

    public function test_create_form_renders_live_preview_of_filled_blocks(): void
    {
        $this->actingAs(User::factory()->create(['is_master' => true]));

        Livewire::test(CreateTemplate::class)
            ->fillForm([
                'name' => 'T',
                'subject' => 'S',
                'color' => '#ef4444',
                'blocks' => [
                    ['type' => 'title', 'data' => ['text' => 'Oi {{nome}}']],
                    ['type' => 'button', 'data' => ['label' => 'Comprar', 'url' => 'https://example.com']],
                ],
            ])
            ->assertSee('Oi Maria Exemplo', false)
            ->assertSee('Comprar', false)
            ->assertSee('#ef4444', false);
    }

    public function test_preview_accepts_rich_editor_json_state(): void
    {
        $template = new Template([
            'subject' => '',
            'color' => '#2563eb',
            'blocks' => [[
                'type' => 'text',
                'data' => ['content' => [
                    'type' => 'doc',
                    'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Olá {{nome}}']]]],
                ]],
            ]],
        ]);

        $html = (new TemplateMail($template, new Contact(['name' => 'Ana', 'email' => 'a@x.com']), preview: true))->render();

        $this->assertStringContainsString('Olá Ana', $html);
    }

    public function test_styled_blocks_render_alignment_size_and_button_style(): void
    {
        $template = new Template([
            'subject' => '',
            'color' => '#ef4444',
            'background_color' => '#111111',
            'font' => 'georgia',
            'blocks' => [
                ['type' => 'title', 'data' => ['text' => 'Oi', 'size' => 'large', 'align' => 'center']],
                ['type' => 'button', 'data' => ['label' => 'Ver', 'url' => 'https://example.com', 'style' => 'outline', 'shape' => 'pill', 'align' => 'right']],
                ['type' => 'spacer', 'data' => ['height' => 48]],
                ['type' => 'footer', 'data' => ['text' => 'Descadastrar', 'align' => 'center']],
            ],
        ]);

        $html = (new TemplateMail($template, new Contact(['name' => 'Ana', 'email' => 'a@x.com']), preview: true))->render();

        $this->assertStringContainsString('text-align:center', $html);
        $this->assertStringContainsString('font-size:36px', $html);
        $this->assertStringContainsString('border-radius:999px', $html);
        $this->assertStringContainsString('border:2px solid #ef4444', $html);
        $this->assertStringContainsString('height:48px', $html);
        $this->assertStringContainsString('background:#111111', $html);
        $this->assertStringContainsString('Georgia', $html);
        $this->assertStringContainsString('Descadastrar', $html);
    }

    public function test_master_assigns_contact_to_a_user_but_common_user_cannot(): void
    {
        $owner = User::factory()->create();

        $this->actingAs(User::factory()->create(['is_master' => true]));
        Livewire::test(CreateContact::class)
            ->fillForm(['user_id' => $owner->id, 'name' => 'Ana', 'email' => 'a@x.com'])
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertSame($owner->id, Contact::withoutGlobalScopes()->firstOrFail()->user_id);

        $common = User::factory()->create();
        $this->actingAs($common);
        Livewire::test(CreateContact::class)
            ->assertFormFieldHidden('user_id')
            ->fillForm(['user_id' => $owner->id, 'name' => 'Bia', 'email' => 'b@x.com'])
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertSame($common->id, Contact::withoutGlobalScopes()->where('email', 'b@x.com')->firstOrFail()->user_id);
    }

    public function test_users_table_filters_by_profile_verification_and_creation_date(): void
    {
        $master = User::factory()->create(['is_master' => true, 'name' => 'Chefe']);
        $verified = User::factory()->create(['name' => 'Verificada']);
        $unverified = User::factory()->unverified()->create(['name' => 'Pendente']);
        $old = User::factory()->create(['name' => 'Antiga']);
        $old->forceFill(['created_at' => now()->subYear()])->save();
        $this->actingAs($master);

        Livewire::test(ListUsers::class)
            ->filterTable('is_master', true)
            ->assertCanSeeTableRecords([$master])
            ->assertCanNotSeeTableRecords([$verified, $unverified])
            ->resetTableFilters()
            ->filterTable('is_master', false)
            ->assertCanNotSeeTableRecords([$master])
            ->resetTableFilters()
            ->filterTable('email_verified_at', false)
            ->assertCanSeeTableRecords([$unverified])
            ->assertCanNotSeeTableRecords([$verified])
            ->resetTableFilters()
            ->filterTable('created_at', ['from' => now()->subMonth()->toDateString(), 'until' => null])
            ->assertCanSeeTableRecords([$master, $verified])
            ->assertCanNotSeeTableRecords([$old]);
    }

    public function test_contacts_table_filters(): void
    {
        $master = User::factory()->create(['is_master' => true]);
        $other = User::factory()->create();
        $mine = Contact::create(['user_id' => $master->id, 'name' => 'A', 'email' => 'a@x.com', 'phone' => '85999990000', 'company' => 'ACME']);
        $theirs = Contact::create(['user_id' => $other->id, 'name' => 'B', 'email' => 'b@x.com']);
        $theirs->forceFill(['created_at' => now()->subYear()])->save();
        $this->actingAs($master);

        Livewire::test(ListContacts::class)
            ->filterTable('user_id', $other->id)
            ->assertCanSeeTableRecords([$theirs])
            ->assertCanNotSeeTableRecords([$mine])
            ->resetTableFilters()
            ->filterTable('phone', true)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs])
            ->resetTableFilters()
            ->filterTable('company', false)
            ->assertCanSeeTableRecords([$theirs])
            ->assertCanNotSeeTableRecords([$mine])
            ->resetTableFilters()
            ->filterTable('created_at', ['from' => now()->subMonth()->toDateString(), 'until' => null])
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_common_user_does_not_see_the_user_filter(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ListContacts::class)->assertTableFilterHidden('user_id');
        Livewire::test(ListTemplates::class)->assertTableFilterHidden('user_id');
        Livewire::test(ListMailLogs::class)->assertTableFilterHidden('user_id');
    }

    public function test_templates_and_logs_tables_filters(): void
    {
        $master = User::factory()->create(['is_master' => true]);
        $other = User::factory()->create();
        $mineT = Template::create(['user_id' => $master->id, 'name' => 'Meu', 'subject' => 'S', 'color' => '#2563eb', 'blocks' => []]);
        $theirT = Template::create(['user_id' => $other->id, 'name' => 'Dele', 'subject' => 'S', 'color' => '#2563eb', 'blocks' => []]);
        $mineL = MailLog::create(['user_id' => $master->id, 'template_id' => $mineT->id, 'email' => 'a@x.com', 'subject' => 'S', 'status' => 'sent']);
        $theirL = MailLog::create(['user_id' => $other->id, 'template_id' => $theirT->id, 'email' => 'b@x.com', 'subject' => 'S', 'status' => 'failed']);
        $this->actingAs($master);

        Livewire::test(ListTemplates::class)
            ->filterTable('user_id', $other->id)
            ->assertCanSeeTableRecords([$theirT])
            ->assertCanNotSeeTableRecords([$mineT]);

        Livewire::test(ListMailLogs::class)
            ->filterTable('template_id', $mineT->id)
            ->assertCanSeeTableRecords([$mineL])
            ->assertCanNotSeeTableRecords([$theirL])
            ->resetTableFilters()
            ->filterTable('user_id', $other->id)
            ->assertCanSeeTableRecords([$theirL])
            ->assertCanNotSeeTableRecords([$mineL])
            ->resetTableFilters()
            ->filterTable('status', 'failed')
            ->assertCanSeeTableRecords([$theirL])
            ->assertCanNotSeeTableRecords([$mineL]);
    }
}
