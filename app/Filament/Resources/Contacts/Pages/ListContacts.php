<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use App\Models\User;
use App\Services\Contact\ImportContactsService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import')
                ->label('Importar planilha')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->modalHeading('Importar contatos (.xlsx)')
                ->modalDescription('Colunas na primeira linha: nome, email, telefone, empresa e observacoes. Só "nome" e "email" são obrigatórios. E-mails que já existem são atualizados.')
                ->modalSubmitActionLabel('Importar')
                ->schema([
                    Select::make('user_id')
                        ->label('Usuário')
                        ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->default(fn () => auth()->id())
                        ->required()
                        ->visible(fn () => (bool) auth()->user()?->is_master),

                    FileUpload::make('file')
                        ->label('Planilha')
                        ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                        ->disk('local')
                        ->directory('imports')
                        ->maxSize(5120)
                        ->required(),
                ])
                ->extraModalFooterActions([
                    Action::make('template')
                        ->label('Baixar modelo')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->color('gray')
                        ->action(fn (): StreamedResponse => $this->downloadTemplate())
                        ->cancelParentActions(),
                ])
                ->action(function (array $data): void {
                    $path = Storage::disk('local')->path($data['file']);
                    $userId = auth()->user()?->is_master ? (int) $data['user_id'] : (int) auth()->id();

                    try {
                        $result = app(ImportContactsService::class)->run($path, $userId);
                    } finally {
                        Storage::disk('local')->delete($data['file']);
                    }

                    $summary = "{$result['created']} criado(s), {$result['updated']} atualizado(s), {$result['skipped']} ignorado(s).";
                    $notification = Notification::make()->title('Importação concluída')->body($summary);

                    if ($result['errors'] === []) {
                        $notification->success();
                    } else {
                        $notification->warning()->body($summary.' '.implode(' | ', array_slice($result['errors'], 0, 5)));
                    }

                    $notification->send();
                }),

            CreateAction::make()
                ->icon(Heroicon::OutlinedPlus),
        ];
    }

    private function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(ImportContactsService::HEADERS));
            $writer->addRow(Row::fromValues(['Maria Exemplo', 'maria@exemplo.com', '85999990000', 'Empresa Exemplo', 'Cliente VIP']));
            $writer->close();
        }, 'modelo-contatos.xlsx');
    }
}
