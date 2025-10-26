<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HelpContentCompletion extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'meta' => 'array',
        'total_steps' => 'integer',
        'completed_steps' => 'integer',
    ];

    public function viewer()
    {
        return $this->morphTo();
    }
}
