<?php

declare(strict_types=1);

namespace App\Services\Contact;

use App\Models\Contact;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader;

class ImportContactsService
{
    public const MAX_ROWS = 5000;

    public const HEADERS = ['nome', 'email', 'telefone', 'empresa', 'observacoes'];

    private const ALIASES = [
        'nome' => 'name',
        'name' => 'name',
        'email' => 'email',
        'e-mail' => 'email',
        'telefone' => 'phone',
        'celular' => 'phone',
        'whatsapp' => 'phone',
        'phone' => 'phone',
        'empresa' => 'company',
        'company' => 'company',
        'observacoes' => 'notes',
        'observacao' => 'notes',
        'obs' => 'notes',
        'notes' => 'notes',
    ];

    /**
     * @return array{created: int, updated: int, skipped: int, errors: list<string>}
     */
    public function run(string $path, int $userId): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        $reader = new Reader;
        $reader->open($path);

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $columns = null;
                $line = 0;

                foreach ($sheet->getRowIterator() as $row) {
                    $line++;
                    $cells = array_map(fn (mixed $cell): string => trim((string) $cell), $row->toArray());

                    if ($columns === null) {
                        $columns = $this->mapHeader($cells);

                        if (! in_array('email', $columns, true)) {
                            $result['errors'][] = 'A planilha precisa ter a coluna "email" na primeira linha.';

                            break 2;
                        }

                        continue;
                    }

                    if (implode('', $cells) === '') {
                        continue;
                    }

                    if ($line - 1 > self::MAX_ROWS) {
                        $result['errors'][] = 'Limite de '.self::MAX_ROWS.' linhas atingido; o restante foi ignorado.';

                        break 2;
                    }

                    $this->importRow($this->rowData($columns, $cells), $userId, $line, $result);
                }

                break;
            }
        } finally {
            $reader->close();
        }

        return $result;
    }

    /**
     * @param  list<string>  $cells
     * @return list<string|null>
     */
    private function mapHeader(array $cells): array
    {
        return array_map(
            fn (string $cell): ?string => self::ALIASES[Str::lower(Str::ascii($cell))] ?? null,
            $cells,
        );
    }

    /**
     * @param  list<string|null>  $columns
     * @param  list<string>  $cells
     * @return array<string, string>
     */
    private function rowData(array $columns, array $cells): array
    {
        $data = [];

        foreach ($columns as $index => $column) {
            if ($column !== null) {
                $data[$column] = $cells[$index] ?? '';
            }
        }

        return $data;
    }

    /**
     * @param  array<string, string>  $data
     * @param  array{created: int, updated: int, skipped: int, errors: list<string>}  $result
     */
    private function importRow(array $data, int $userId, int $line, array &$result): void
    {
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
        ], attributes: ['name' => 'nome', 'email' => 'e-mail', 'phone' => 'telefone', 'company' => 'empresa']);

        if ($validator->fails()) {
            $result['skipped']++;
            $result['errors'][] = "Linha {$line}: ".$validator->errors()->first();

            return;
        }

        $email = Str::lower($data['email']);
        $contact = Contact::withoutGlobalScopes()->withTrashed()
            ->where('user_id', $userId)
            ->where('email', $email)
            ->first();

        $values = array_filter([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? '',
            'company' => $data['company'] ?? '',
            'notes' => $data['notes'] ?? '',
        ], fn (string $value): bool => $value !== '');

        if ($contact === null) {
            Contact::withoutGlobalScopes()->create($values + ['user_id' => $userId, 'email' => $email]);
            $result['created']++;

            return;
        }

        if ($contact->trashed()) {
            $contact->restore();
        }

        $contact->update($values);
        $result['updated']++;
    }
}
