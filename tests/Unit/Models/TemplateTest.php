<?php

namespace Tests\Unit\Models;

use App\Enums\TemplateType;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function template(array $attributes = []): Template
    {
        return Template::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'name' => 'Promoção',
            'subject' => 'Oi {{nome}}',
            'type' => TemplateType::Html,
            'html' => '<p>Olá {{nome}}</p>',
        ], $attributes));
    }

    public function test_can_create_template(): void
    {
        $template = $this->template();

        $this->assertDatabaseHas('templates', [
            'id' => $template->id,
            'name' => 'Promoção',
            'type' => 'html',
        ]);
    }

    public function test_can_read_template(): void
    {
        $template = $this->template();

        $found = Template::find($template->id);

        $this->assertNotNull($found);
        $this->assertSame('Oi {{nome}}', $found->subject);
    }

    public function test_can_update_template(): void
    {
        $template = $this->template();

        $template->update(['name' => 'Novidades']);

        $this->assertDatabaseHas('templates', ['id' => $template->id, 'name' => 'Novidades']);
    }

    public function test_can_delete_template(): void
    {
        $template = $this->template();

        $template->delete();

        $this->assertSoftDeleted('templates', ['id' => $template->id]);
        $this->assertNull(Template::find($template->id));
    }

    public function test_type_is_cast_to_enum(): void
    {
        $template = $this->template(['type' => TemplateType::Image, 'image_path' => 'templates/a.png', 'html' => null]);

        $this->assertSame(TemplateType::Image, $template->fresh()->type);
    }

    public function test_has_user_relationship(): void
    {
        $this->assertInstanceOf(BelongsTo::class, $this->template()->user());
    }
}
