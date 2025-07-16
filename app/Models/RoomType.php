<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'max_adults',
        'max_children',
        'amenities'
    ];

    protected $casts = [
        'amenities' => 'array',
        'max_adults' => 'integer',
        'max_children' => 'integer',
    ];

    // Relaciones
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    // Accessors
    public function getMaxGuestsAttribute()
    {
        return $this->max_adults + $this->max_children;
    }

    public function getFormattedAmenitiesAttribute()
    {
        return implode(', ', $this->amenities ?? []);
    }
}