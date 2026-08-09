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
}
