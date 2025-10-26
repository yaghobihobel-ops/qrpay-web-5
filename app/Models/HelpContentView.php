<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpContentView extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
        'duration_seconds' => 'integer',
    ];

    public function viewer()
    {
        return $this->morphTo();
    }
}
