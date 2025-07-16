<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'room_id',
        'reservation_code',
        'check_in_date',
        'check_out_date',
        'total_nights',
        'adults_count',
        'children_count',
        'needs_parking',
        'parking_fee',
        'room_total',
        'services_total',
        'total_amount',
        'status',
        'special_requests',
        'payment_method'
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'needs_parking' => 'boolean',
        'parking_fee' => 'decimal:2',
        'room_total' => 'decimal:2',
        'services_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'adults_count' => 'integer',
        'children_count' => 'integer',
        'total_nights' => 'integer',
    ];

    // Relaciones
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

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function registration()
    {
        return $this->hasOne(Registration::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'completed']);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopePendingPayment($query)
    {
        return $query->where('status', 'pending_payment');
    }

    // Métodos
    public function generateCode()
    {
        $this->reservation_code = 'RES' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
        return $this->reservation_code;
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending_payment' => 'Pendiente de Pago',
            'payment_submitted' => 'Pago Enviado',
            'confirmed' => 'Confirmada',
            'checked_in' => 'Check-in',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            default => 'Desconocido'
        };
    }

    public function getFormattedTotalAttribute()
    {
        return 'Bs. ' . number_format($this->total_amount, 2);
    }

    public function getTotalGuestsAttribute()
    {
        return $this->adults_count + $this->children_count;
    }
}