<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreeGrowth extends Model
{
    use HasFactory;

    protected $table = 'tree_growth'; 

    protected $fillable = [
        'dbh',
        'height',
        'latitude',
        'longitude',
        'plantation_id'
    ];
}
