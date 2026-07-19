<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EpaperRead extends Model
{
    protected $fillable = [
        'epaper_edition_id',
        'user_id',
        'reader_key',
        'first_read_at',
        'last_read_at',
        'liked_at',
    ];

    protected function casts(): array
    {
        return [
            'first_read_at' => 'datetime',
            'last_read_at' => 'datetime',
            'liked_at' => 'datetime',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(EpaperEdition::class, 'epaper_edition_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
