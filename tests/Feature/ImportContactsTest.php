<?php

namespace Tests\Feature;

use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Models\Contact;
use App\Models\User;
use App\Services\Contact\ImportContactsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class ImportContactsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  list<list<string>>  $rows
     */
    private function xlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'contacts').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return $path;
    }

    public function test_service_imports_updates_and_skips_invalid_rows(): void
    {
        $user = User::factory()->create();
        Contact::create(['user_id' => $user->id, 'name' => 'Antigo', 'email' => 'ana@x.com', 'company' => 'Mantida']);

        $path = $this->xlsx([
            ['Nome', 'E-mail', 'Telefone', 'Empresa', 'Observações'],
            ['Ana Nova', 'ANA@x.com', '85999990000', '', ''],
            ['Bia', 'bia@x.com', '85988880000', 'ACME', 'VIP'],
            ['Sem Email', 'invalido', '', '', ''],
            ['', 'semnome@x.com', '', '', ''],
            ['', '', '', '', ''],
        ]);

        $result = app(ImportContactsService::class)->run($path, $user->id);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(2, $result['skipped']);
        $this->assertCount(2, $result['errors']);
        $this->assertDatabaseHas('contacts', ['user_id' => $user->id, 'email' => 'ana@x.com', 'name' => 'Ana Nova', 'phone' => '85999990000', 'company' => 'Mantida']);
        $this->assertDatabaseHas('contacts', ['user_id' => $user->id, 'email' => 'bia@x.com', 'company' => 'ACME', 'notes' => 'VIP']);
        $this->assertSame(2, Contact::withoutGlobalScopes()->count());
    }

    public function test_service_restores_soft_deleted_contact(): void
    {
        $user = User::factory()->create();
        $contact = Contact::create(['user_id' => $user->id, 'name' => 'Ana', 'email' => 'ana@x.com']);
        $contact->delete();

        app(ImportContactsService::class)->run($this->xlsx([['nome', 'email'], ['Ana', 'ana@x.com']]), $user->id);

        $this->assertNull($contact->fresh()->deleted_at);
    }

    public function test_service_requires_email_column(): void
    {
        $result = app(ImportContactsService::class)->run($this->xlsx([['nome'], ['Ana']]), User::factory()->create()->id);

        $this->assertSame(0, $result['created']);
        $this->assertStringContainsString('email', $result['errors'][0]);
    }

    public function test_common_user_import_action_always_assigns_to_self(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->createWithContent('contatos.xlsx', file_get_contents($this->xlsx([['nome', 'email'], ['Ana', 'ana@x.com']])));

        Livewire::test(ListContacts::class)
            ->callAction('import', ['file' => $file, 'user_id' => $other->id])
            ->assertHasNoActionErrors();

        $this->assertSame($user->id, Contact::withoutGlobalScopes()->firstOrFail()->user_id);
    }

    public function test_master_can_import_for_another_user(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $this->actingAs(User::factory()->create(['is_master' => true]));

        $file = UploadedFile::fake()->createWithContent('contatos.xlsx', file_get_contents($this->xlsx([['nome', 'email'], ['Ana', 'ana@x.com']])));

        Livewire::test(ListContacts::class)
            ->callAction('import', ['file' => $file, 'user_id' => $owner->id])
            ->assertHasNoActionErrors();

        $this->assertSame($owner->id, Contact::withoutGlobalScopes()->firstOrFail()->user_id);
    }
}
