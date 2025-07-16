<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'amount',
        'payment_method',
        'payment_reference',
        'payment_proof',
        'status',
        'verified_by',
        'verified_at',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    // Relaciones
    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Métodos
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'Pendiente',
            'verified' => 'Verificado',
            'rejected' => 'Rechazado',
            default => 'Desconocido'
        };
    }

    public function getFormattedAmountAttribute()
    {
        return 'Bs. ' . number_format($this->amount, 2);
    }

    public function getPaymentMethodLabelAttribute()
    {
        return match($this->payment_method) {
            'qr' => 'QR',
            'cash' => 'Efectivo',
            default => 'Desconocido'
        };
    }

    public function hasProof()
    {
        return !empty($this->payment_proof);
    }
}