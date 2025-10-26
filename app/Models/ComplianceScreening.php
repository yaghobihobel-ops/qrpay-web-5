<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplianceScreening extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'triggered_rules' => 'array',
        'recommendations' => 'array',
    ];

    public function subject()
    {
        return $this->morphTo();
    }
}
