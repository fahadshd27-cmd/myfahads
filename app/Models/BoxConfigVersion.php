<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoxConfigVersion extends Model
{
    protected $fillable = [
        'mystery_box_id',
        'version',
        'snapshot',
    ];

    protected $casts = [
        'version' => 'integer',
        'snapshot' => 'array',
    ];

    public function box(): BelongsTo
    {
        return $this->belongsTo(MysteryBox::class, 'mystery_box_id');
    }
}
