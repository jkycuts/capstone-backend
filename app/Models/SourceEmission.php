<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SourceEmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'sourceID',
        'year',
        'quarter',
        'fuel_consumption',
        'electricity_usage'
    ];

    public function source()
    {
        return $this->belongsTo(Source::class);
    }
}
