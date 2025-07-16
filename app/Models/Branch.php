<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'address',
        'phone',
        'email',
        'city',
        'check_in_time',
        'check_out_time',
        'manager_name',
        'description',
        'qr_payment_info',
        'is_active'
    ];

    protected $casts = [
        'check_in_time' => 'datetime:H:i',
        'check_out_time' => 'datetime:H:i',
        'is_active' => 'boolean',
    ];

    // Relaciones
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Accessors
    public function getAvailableRoomsAttribute()
    {
        return $this->rooms()->where('status', 'available')->count();
    }

    public function getOccupiedRoomsAttribute()
    {
        return $this->rooms()->where('status', 'occupied')->count();
    }
}