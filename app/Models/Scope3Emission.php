<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scope3Emission extends Model
{
    use HasFactory;

     // The table associated with the model
     protected $table = 'scope3_emission';

     // The attributes that are mass assignable
     protected $fillable = [
        'company_id',
        'year',
        'travel_type',
        'travel_distance_miles',
        'emission_tco2e',
         
         'month',
         'mode',
         'quarter',

        'co2_emission_factor',
        'ch4_emission_factor',
        'n2o_emission_factor',
        'co2_gwp',
        'ch4_gwp',
        'n2o_gwp',
         
     ];
 
     // Optionally define relationships to other models, if needed
     public function company()
     {
         return $this->belongsTo(MiningCompany::class); // assuming you have a Company model
     }
}
