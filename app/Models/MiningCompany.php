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

    public function Source()
    {
        return $this->hasMany(Source::class, 'company_id');
    }

    protected $primaryKey = 'id';

}
