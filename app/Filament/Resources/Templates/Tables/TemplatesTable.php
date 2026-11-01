<?php

namespace App\Filament\Resources\Templates\Tables;

use App\Jobs\SendTemplateMailJob;
use App\Models\Contact;
use App\Models\Template;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('subject')
                    ->label('Assunto')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label()),

                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->visible(fn () => (bool) auth()->user()?->is_master),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('send')
                    ->label('Disparar')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->schema([
                        Toggle::make('all')
                            ->label('Todos os contatos')
                            ->default(true)
                            ->live(),

                        Select::make('contacts')->label('Contatos')->multiple()->searchable()
                            ->options(fn (Template $record) => Contact::query()
                                ->where('user_id', $record->user_id)
                                ->pluck('email', 'id'))
                            ->visible(fn (Get $get) => ! $get('all'))
                            ->required(fn (Get $get) => ! $get('all')),
                    ])
                    ->action(function (Template $record, array $data): void {
                        $contacts = Contact::query()->where('user_id', $record->user_id);

                        if (! $data['all']) {
                            $contacts->whereKey($data['contacts']);
                        }

                        $ids = $contacts->pluck('id');

                        foreach ($ids as $id) {
                            SendTemplateMailJob::dispatch($record->id, $id);
                        }

                        Notification::make()->title($ids->count().' e-mail(s) na fila')->success()->send();
                    }),
                ViewAction::make()
                    ->icon(Heroicon::OutlinedEye),

                EditAction::make()
                    ->icon(Heroicon::OutlinedPencil),

                DeleteAction::make()
                    ->icon(Heroicon::OutlinedTrash),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
