<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_id',
        'user_id',
        'service_id',
        'quantity',
        'unit_price',
        'total_amount',
        'consumption_date',
        'status',
        'notes',
        'registered_by'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'consumption_date' => 'datetime',
    ];

    // Relaciones
    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // Métodos
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            default => 'Desconocido'
        };
    }

    public function getFormattedTotalAttribute()
    {
        return 'Bs. ' . number_format($this->total_amount, 2);
    }

    public function getFormattedUnitPriceAttribute()
    {
        return 'Bs. ' . number_format($this->unit_price, 2);
    }
}