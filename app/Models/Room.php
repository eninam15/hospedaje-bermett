<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'room_type_id',
        'room_number',
        'floor',
        'price_per_night',
        'status',
        'description',
        'photos',
        'amenities',
        'is_active'
    ];

    protected $casts = [
        'price_per_night' => 'decimal:2',
        'photos' => 'array',
        'amenities' => 'array',
        'is_active' => 'boolean',
        'floor' => 'integer',
    ];

    // Relaciones
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
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

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', 'occupied');
    }

    // Métodos
    public function isAvailable()
    {
        return $this->status === 'available' && $this->is_active;
    }

    public function getFormattedPriceAttribute()
    {
        return 'Bs. ' . number_format($this->price_per_night, 2);
    }

    public function getMainPhotoAttribute()
    {
        return $this->photos[0] ?? '/images/default-room.jpg';
    }
}