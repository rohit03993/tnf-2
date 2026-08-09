<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    protected $fillable = [
        'name',
        'language',
        'status',
        'category',
        'param_count',
        'param_mappings',
        'body',
        'components',
        'provider_meta',
        'meta_template_id',
        'is_active',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'components' => 'array',
            'provider_meta' => 'array',
            'param_mappings' => 'array',
            'is_active' => 'boolean',
            'synced_at' => 'datetime',
            'param_count' => 'integer',
        ];
    }

    public function isApproved(): bool
    {
        return strtoupper((string) $this->status) === 'APPROVED';
    }

    public function label(): string
    {
        return $this->name.' · '.$this->language.' · '.strtoupper((string) $this->status);
    }

    /**
     * @return list<string>
     */
    public function paramSources(): array
    {
        $this->ensureParamMappings();

        $mappings = $this->param_mappings ?? [];

        return array_values(array_map(
            static fn ($source): string => (string) $source,
            is_array($mappings) ? $mappings : [],
        ));
    }

    public function ensureParamMappings(): void
    {
        $count = max(0, (int) $this->param_count);
        $mappings = is_array($this->param_mappings) ? array_values($this->param_mappings) : [];

        if ($count === 0) {
            return;
        }

        if (count($mappings) >= $count) {
            return;
        }

        $defaults = match ($count) {
            1 => ['campaign.title'],
            2 => ['campaign.title', 'campaign.url'],
            3 => ['user.name', 'campaign.title', 'campaign.url'],
            default => collect(range(1, $count))
                ->map(fn (int $i): string => $i === 1 ? 'campaign.title' : ($i === 2 ? 'campaign.url' : 'manual.'.$i))
                ->all(),
        };

        while (count($mappings) < $count) {
            $mappings[] = $defaults[count($mappings)] ?? ('manual.'.(count($mappings) + 1));
        }

        $this->forceFill(['param_mappings' => array_slice($mappings, 0, $count)])->save();
    }
}
