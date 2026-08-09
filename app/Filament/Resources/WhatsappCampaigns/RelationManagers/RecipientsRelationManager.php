<?php

namespace App\Filament\Resources\WhatsappCampaigns\RelationManagers;

use App\Enums\WhatsAppRecipientStatus;
use App\Support\PhoneNumber;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    protected static ?string $title = 'Delivery log';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('User')->placeholder('—'),
                TextColumn::make('phone')
                    ->formatStateUsing(fn (?string $state) => PhoneNumber::formatDisplay($state) ?? $state)
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (WhatsAppRecipientStatus $state): string => $state->label())
                    ->color(fn (WhatsAppRecipientStatus $state): string => match ($state) {
                        WhatsAppRecipientStatus::Sent => 'success',
                        WhatsAppRecipientStatus::Failed => 'danger',
                        WhatsAppRecipientStatus::Processing => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('message_sent')->label('Preview')->limit(40)->toggleable(),
                TextColumn::make('error_message')->limit(40)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id')
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'sent' => 'Sent',
                    'failed' => 'Failed',
                ]),
            ]);
    }
}
