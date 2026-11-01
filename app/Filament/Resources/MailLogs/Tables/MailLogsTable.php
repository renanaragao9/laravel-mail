<?php

namespace App\Filament\Resources\MailLogs\Tables;

use App\Enums\MailStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MailLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),

                TextColumn::make('subject')
                    ->label('Assunto')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (MailStatus $state) => $state->label())
                    ->color(fn (MailStatus $state) => $state === MailStatus::Sent ? 'success' : 'danger'),

                TextColumn::make('error')
                    ->label('Erro')
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->error),

                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->visible(fn () => (bool) auth()->user()?->is_master),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(MailStatus::cases())->mapWithKeys(fn (MailStatus $s) => [$s->value => $s->label()])),
            ]);
    }
}
