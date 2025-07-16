<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'category',
        'price',
        'is_available'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    // Relaciones
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function consumptions()
    {
        return $this->hasMany(ServiceConsumption::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Accessors
    public function getFormattedPriceAttribute()
    {
        return 'Bs. ' . number_format($this->price, 2);
    }

    public function getCategoryLabelAttribute()
    {
        return match($this->category) {
            'food' => 'Comida',
            'parking' => 'Estacionamiento',
            'laundry' => 'Lavandería',
            'other' => 'Otros',
            default => 'Otros'
        };
    }
}