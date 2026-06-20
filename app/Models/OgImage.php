<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OgImage extends Model
{
    protected $fillable = [
        'entity_type',
        'entity_id',
        'path',
        'signature_hash',
    ];
}
