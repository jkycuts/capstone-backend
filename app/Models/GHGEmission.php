<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GhgEmission extends Model
{
    use HasFactory;

    protected $table = 'ghg_emission';

    protected $fillable = [
        'year',
        'quarter',

        // Scope 1 - Fuel Consumption
        'fuel_source',
        'fuel_type',
        'fuel_liters_used',
        'fuel_co2_emission',

        // Scope 2 - Purchased Electricity
        'electricity_kwh',
        'electricity_co2_emission',

        // Scope 3 - Business Travel
        'travel_category',
        'travel_distance_miles',
        'travel_number_of_trips',
        'travel_co2_emission',

        'company_id',
    ];

    // Automatically calculated attributes
    protected $appends = ['total_emission_tco2'];
    

    /**
     * Accessor to compute total emissions for CO₂
     * Combines Scope 1, 2, and 3
     */
    public function getTotalEmissionTco2Attribute()
    {
        return round(
            ($this->fuel_co2_emission ?? 0) +
            ($this->electricity_co2_emission ?? 0) +
            ($this->travel_co2_emission ?? 0), 4
        );
    }

    /**
     * Optional: If tracking which user submitted the data
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
