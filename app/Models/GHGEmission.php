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
        'travel_co2_emission',

        // Emission totals
        'fuel_tco2',
        'electricity_tco2',
        'business_travel_tco2',
        'total_tco2',

        'date_recorded',

        // Foreign key to company
        'company_id',
    ];

    // Appended calculated attributes
    protected $appends = ['total_emission_tco2'];

    /**
     * Accessor: Calculate total emissions (Scopes 1, 2, 3)
     */
    public function getTotalEmissionTco2Attribute()
    {
        return round(
            ($this->fuel_co2_emission ?? 0) +
            ($this->electricity_co2_emission ?? 0) +
            ($this->travel_co2_emission ?? 0),
            4
        );
    }

    /**
     * Relationship: Emission belongs to a company
     */
    public function company()
    {
        return $this->belongsTo(MiningCompany::class);
    }

    /**
     * Optional: Track which user submitted the emission
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
