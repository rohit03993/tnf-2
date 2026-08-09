<?php

namespace App\Filament\Resources\WhatsappTemplates\Pages;

use App\Filament\Resources\WhatsappTemplates\WhatsappTemplateResource;
use App\Services\WhatsAppTemplateParamResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewWhatsappTemplate extends ViewRecord
{
    protected static string $resource = WhatsappTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mapParams')
                ->label('Map campaign variables')
                ->icon('heroicon-o-adjustments-horizontal')
                ->visible(fn (): bool => (int) $this->record->param_count > 0)
                ->fillForm(function (): array {
                    $this->record->ensureParamMappings();
                    $this->record->refresh();

                    return [
                        'param_mapping_rows' => collect($this->record->paramSources())
                            ->map(fn (string $source): array => ['source' => $source])
                            ->all(),
                    ];
                })
                ->form([
                    Repeater::make('param_mapping_rows')
                        ->label('{{n}} data source')
                        ->schema([
                            Select::make('source')
                                ->label('Maps to')
                                ->options(WhatsAppTemplateParamResolver::sourceOptions())
                                ->required(),
                        ])
                        ->addable(false)
                        ->deletable(false)
                        ->reorderable(false),
                ])
                ->action(function (array $data): void {
                    $mappings = collect($data['param_mapping_rows'] ?? [])
                        ->pluck('source')
                        ->filter()
                        ->values()
                        ->all();

                    $this->record->update(['param_mappings' => $mappings]);

                    Notification::make()
                        ->title('Variable mapping saved')
                        ->body('Campaigns will use these sources when sending this template.')
                        ->success()
                        ->send();
                }),
            Action::make('back')
                ->label('Back to list')
                ->url(WhatsappTemplateResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
