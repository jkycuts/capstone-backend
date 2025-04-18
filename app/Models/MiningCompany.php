<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiningCompany extends Model
{
    use HasFactory;

    protected $table = 'companies'; // Ensure this matches your database table name

    protected $fillable = [
        'name', 
        'location', 
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'company_id');
    }

    public function AnnualSummary()
    {
        return $this->hasMany(AnnualSummary::class, 'company_id');
    }

    public function ghgEmissions()
{
    return $this->hasMany(GHGEmission::class);
}




public function carbonSequestrations() {
    return $this->hasMany(CarbonSequestration::class);
}

    protected $primaryKey = 'id';

}
