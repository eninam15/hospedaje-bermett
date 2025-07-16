<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'user_id',
        'branch_id',
        'room_id',
        'registration_code',
        'actual_check_in',
        'actual_check_out',
        'additional_guests',
        'status',
        'notes',
        'registered_by'
    ];

    protected $casts = [
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
        'additional_guests' => 'array',
    ];

    // Relaciones
    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function serviceConsumptions()
    {
        return $this->hasMany(ServiceConsumption::class);
    }

    // Métodos
    public function generateCode()
    {
        $this->registration_code = 'REG' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        return $this->registration_code;
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'active' => 'Activo',
            'completed' => 'Completado',
            default => 'Desconocido'
        };
    }

    public function isActive()
    {
        return $this->status === 'active';
    }

    public function getTotalGuestsAttribute()
    {
        return 1 + count($this->additional_guests ?? []);
    }
}