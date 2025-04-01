<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SourceEmission extends Model
{
    use HasFactory;

    protected $table = 'source_emission'; 

    protected $fillable = [
        'sourceID', 
        'year', 
        'quarter', 
        'fuel_consumption', 
        'electricity_usage',
        'co2_emission',
        'n2o_emission',
        'electricity_emission',
        'total_emission'
    ];
}
