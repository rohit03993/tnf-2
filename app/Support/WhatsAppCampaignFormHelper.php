<?php

namespace App\Support;

use App\Models\WhatsappCampaign;
use App\Models\WhatsappTemplate;
use App\Services\WhatsAppTemplateParamResolver;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Illuminate\Support\HtmlString;

class WhatsAppCampaignFormHelper
{
    /** @var list<string> */
    private const AUTO_SOURCES = [
        'user.name',
        'user.phone',
    ];

    public static function generateDefaultName(): string
    {
        $date = now()->format('Y-m-d');

        try {
            $sequence = WhatsappCampaign::query()
                ->whereDate('created_at', today())
                ->count() + 1;
        } catch (\Throwable) {
            $sequence = 1;
        }

        return sprintf('%s-%03d', $date, $sequence);
    }

    public static function template(?int $templateId): ?WhatsappTemplate
    {
        if (blank($templateId)) {
            return null;
        }

        $template = WhatsappTemplate::query()->find($templateId);
        $template?->ensureParamMappings();

        return $template?->fresh() ?? $template;
    }

    public static function renderTemplatePreviewCard(?int $templateId): HtmlString
    {
        $template = self::template($templateId);

        if (! $template) {
            return new HtmlString('');
        }

        $body = filled($template->body)
            ? e($template->body)
            : '<span class="text-gray-500">No preview text from Meta for this template.</span>';

        $badges = self::paramBadgeHtml($template);

        return new HtmlString(
            '<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">'
            .'<p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Message preview</p>'
            .'<div class="mt-2 text-sm leading-relaxed whitespace-pre-wrap text-gray-900">'.$body.'</div>'
            .($badges !== '' ? '<div class="mt-3 flex flex-wrap gap-2">'.$badges.'</div>' : '')
            .'</div>'
        );
    }

    public static function renderRecipientEstimate(int $count): HtmlString
    {
        if ($count < 1) {
            return new HtmlString(
                '<div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm text-gray-500">'
                .'No users with WhatsApp opt-in + phone match this audience.'
                .'</div>'
            );
        }

        $note = $count > 50
            ? '<p class="mt-1 text-xs text-gray-600">Large campaign — sending may take a few minutes.</p>'
            : '<p class="mt-1 text-xs text-gray-600">Per-user fields (name, etc.) are filled automatically for each recipient.</p>';

        return new HtmlString(
            '<div class="rounded-xl border border-primary-200 bg-primary-50 px-4 py-3">'
            .'<p class="text-sm text-gray-700">'
            .'<strong class="text-2xl tabular-nums text-primary-700">'.$count.'</strong>'
            .' user'.($count === 1 ? '' : 's').' will receive this message'
            .'</p>'
            .$note
            .'</div>'
        );
    }

    /**
     * @return list<\Filament\Forms\Components\Field|\Filament\Forms\Components\Placeholder>
     */
    public static function messageDetailFields(?int $templateId): array
    {
        $template = self::template($templateId);

        if (! $template) {
            return [];
        }

        if ((int) $template->param_count < 1) {
            return [
                Placeholder::make('no_template_params')
                    ->label('')
                    ->content('This template has no parameters — the same message goes to every recipient.'),
            ];
        }

        $fields = [];
        $autoItems = [];

        foreach ($template->paramSources() as $index => $source) {
            if (self::isAutoSource($source)) {
                $autoItems[] = [
                    'slot' => $index + 1,
                    'label' => self::variableLabel($template, $index),
                    'source' => self::sourceLabel($source),
                ];

                continue;
            }

            if (self::isLiteralSource($source)) {
                $autoItems[] = [
                    'slot' => $index + 1,
                    'label' => self::variableLabel($template, $index),
                    'source' => 'Fixed: '.self::literalValue($source),
                ];

                continue;
            }

            $fields[] = self::fieldForParamSlot($template, $index, $source);
        }

        if ($autoItems !== []) {
            array_unshift($fields, Placeholder::make('auto_params_summary')
                ->label('Automatic per recipient')
                ->content(new HtmlString(self::renderAutoParamsList($autoItems)))
                ->columnSpanFull());
        }

        return $fields;
    }

    /**
     * @param  list<array{slot: int, label: string, source: string}>  $items
     */
    public static function renderAutoParamsList(array $items): string
    {
        $rows = collect($items)->map(function (array $item): string {
            return '<li class="flex items-start gap-3 rounded-lg bg-white px-3 py-2 ring-1 ring-gray-200">'
                .'<span class="mt-0.5 inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-success-100 text-xs font-semibold text-success-700">'
                .e((string) $item['slot'])
                .'</span>'
                .'<span><span class="font-medium text-gray-950">'.e($item['label']).'</span>'
                .'<span class="block text-xs text-gray-500">'.e($item['source']).' for each recipient</span></span>'
                .'</li>';
        })->implode('');

        return '<ul class="grid gap-2 sm:grid-cols-2">'.$rows.'</ul>';
    }

    public static function fieldForParamSlot(WhatsappTemplate $template, int $index, ?string $source): TextInput|DatePicker|TimePicker
    {
        if (is_string($source) && str_starts_with($source, 'campaign.')) {
            return self::campaignVariableField($template, $index, $source);
        }

        $label = self::variableLabel($template, $index);

        return TextInput::make('template_manual_params.'.$index)
            ->label($label)
            ->helperText('Same value for all recipients')
            ->required()
            ->maxLength(255);
    }

    /**
     * @return array<string, string>
     */
    public static function defaultCampaignVariables(?int $templateId): array
    {
        $template = self::template($templateId);

        if (! $template) {
            return [];
        }

        $variables = [];

        foreach ($template->paramSources() as $source) {
            if ($source === 'campaign.date' && ! array_key_exists('date', $variables)) {
                $variables['date'] = now()->toDateString();
            }

            if ($source === 'campaign.time' && ! array_key_exists('time', $variables)) {
                $variables['time'] = now()->format('H:i');
            }
        }

        return $variables;
    }

    public static function isAutoSource(?string $source): bool
    {
        return filled($source) && in_array($source, self::AUTO_SOURCES, true);
    }

    public static function isLiteralSource(?string $source): bool
    {
        return is_string($source)
            && str_starts_with($source, '"')
            && str_ends_with($source, '"');
    }

    public static function literalValue(?string $source): string
    {
        return trim((string) $source, '"');
    }

    public static function sourceLabel(?string $source): string
    {
        if (blank($source)) {
            return 'Parameter value';
        }

        if (self::isLiteralSource($source)) {
            return self::literalValue($source);
        }

        return WhatsAppTemplateParamResolver::sourceOptions()[$source] ?? (string) $source;
    }

    public static function variableLabel(WhatsappTemplate $template, int $index): string
    {
        $variables = data_get($template->provider_meta, 'body_variables', []);

        if (is_array($variables) && filled($variables[$index] ?? null)) {
            return ucwords(str_replace('_', ' ', (string) $variables[$index]));
        }

        return 'Parameter '.($index + 1);
    }

    private static function campaignVariableField(WhatsappTemplate $template, int $index, string $source): TextInput|DatePicker|TimePicker
    {
        $key = substr($source, strlen('campaign.'));
        $label = self::variableLabel($template, $index);
        $statePath = 'campaign_variables.'.$key;

        return match ($key) {
            'date' => DatePicker::make($statePath)
                ->label($label)
                ->helperText('Same date for all recipients')
                ->native(false)
                ->required(),
            'time' => TimePicker::make($statePath)
                ->label($label)
                ->helperText('Same time for all recipients')
                ->seconds(false)
                ->required(),
            'url', 'link' => TextInput::make($statePath)
                ->label($label)
                ->helperText('Same link for all recipients')
                ->url()
                ->required()
                ->maxLength(500),
            default => TextInput::make($statePath)
                ->label($label)
                ->helperText('Same value for all recipients')
                ->required()
                ->maxLength(255),
        };
    }

    private static function paramBadgeHtml(WhatsappTemplate $template): string
    {
        $variables = data_get($template->provider_meta, 'body_variables', []);
        $sources = $template->paramSources();
        $badges = [];

        for ($i = 0; $i < (int) $template->param_count; $i++) {
            $label = is_array($variables) && filled($variables[$i] ?? null)
                ? (string) $variables[$i]
                : (string) ($i + 1);
            $source = $sources[$i] ?? null;

            if (self::isAutoSource($source)) {
                $badges[] = self::badge($label, 'success', 'Auto');
            } else {
                $badges[] = self::badge($label, 'warning', 'You fill once');
            }
        }

        return implode('', $badges);
    }

    private static function badge(string $label, string $tone, string $prefix): string
    {
        $classes = $tone === 'success'
            ? 'bg-success-50 text-success-700 ring-success-600/20'
            : 'bg-warning-50 text-warning-800 ring-warning-600/20';

        return '<span class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset '.$classes.'">'
            .'<span class="opacity-70">'.e($prefix).'</span> '
            .e(str_replace('_', ' ', $label))
            .'</span>';
    }
}
