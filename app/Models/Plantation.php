<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plantation extends Model
{
    use HasFactory;

    protected $table = 'plantation'; 

    protected $primaryKey = 'id';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function treeGrowth() {
        return $this->hasMany(TreeGrowth::class, 'plantation_id');
    }

    protected $fillable = [
        'area_planted',
        'seedlings_planted',
        'plantation_age',
        'date_recorded',
        'company_id'
    ];
}
