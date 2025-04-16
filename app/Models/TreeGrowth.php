<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreeGrowth extends Model
{
    use HasFactory;

    protected $table = 'tree_growth'; 

    protected $fillable = [
        'species',
        'dbh',
        'height',
        'geotag_photos',
        'latitude',
        'longitude',
        'plantation_id'
    ];

    public function Plantation()
    {
        return $this->belongsTo(Plantation::class, 'plantation_id');
    }
    
}
