<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AvailabilityController extends Controller
{
    public function checkAvailability(Request $request)
    {
        $request->validate([
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'branch_id' => 'nullable|exists:branches,id',
            'room_type_id' => 'nullable|exists:room_types,id',
            'adults' => 'nullable|integer|min:1|max:10',
            'children' => 'nullable|integer|min:0|max:10',
        ]);

        $checkIn = Carbon::parse($request->check_in_date);
        $checkOut = Carbon::parse($request->check_out_date);
        $totalNights = $checkIn->diffInDays($checkOut);

        // Obtener habitaciones ocupadas en el período
        $occupiedRoomIds = Reservation::whereIn('status', ['confirmed', 'checked_in', 'payment_submitted'])
            ->where(function($query) use ($checkIn, $checkOut) {
                $query->where(function($q) use ($checkIn, $checkOut) {
                    $q->where('check_in_date', '<=', $checkIn)
                      ->where('check_out_date', '>', $checkIn);
                })
                ->orWhere(function($q) use ($checkIn, $checkOut) {
                    $q->where('check_in_date', '<', $checkOut)
                      ->where('check_out_date', '>=', $checkOut);
                })
                ->orWhere(function($q) use ($checkIn, $checkOut) {
                    $q->where('check_in_date', '>=', $checkIn)
                      ->where('check_out_date', '<=', $checkOut);
                });
            })
            ->pluck('room_id')
            ->toArray();

        // Obtener habitaciones disponibles
        $query = Room::with(['branch', 'roomType'])
            ->active()
            ->available()
            ->whereNotIn('id', $occupiedRoomIds);

        // Filtros adicionales
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->room_type_id);
        }

        // Filtrar por capacidad
        if ($request->filled('adults') || $request->filled('children')) {
            $adults = $request->adults ?? 1;
            $children = $request->children ?? 0;
            $totalGuests = $adults + $children;

            $query->whereHas('roomType', function($q) use ($adults, $children, $totalGuests) {
                $q->where('max_adults', '>=', $adults)
                  ->where('max_children', '>=', $children)
                  ->whereRaw('(max_adults + max_children) >= ?', [$totalGuests]);
            });
        }

        $availableRooms = $query->get()->map(function($room) use ($totalNights) {
            $roomTotal = $room->price_per_night * $totalNights;
            
            return [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'floor' => $room->floor,
                'price_per_night' => $room->price_per_night,
                'formatted_price' => $room->formatted_price,
                'room_total' => $roomTotal,
                'formatted_room_total' => 'Bs. ' . number_format($roomTotal, 2),
                'description' => $room->description,
                'photos' => $room->photos,
                'amenities' => $room->amenities,
                'main_photo' => $room->main_photo,
                'branch' => [
                    'id' => $room->branch->id,
                    'name' => $room->branch->name,
                    'category' => $room->branch->category,
                    'address' => $room->branch->address,
                    'qr_payment_info' => $room->branch->qr_payment_info,
                ],
                'room_type' => [
                    'id' => $room->roomType->id,
                    'name' => $room->roomType->name,
                    'description' => $room->roomType->description,
                    'max_adults' => $room->roomType->max_adults,
                    'max_children' => $room->roomType->max_children,
                    'max_guests' => $room->roomType->max_guests,
                    'amenities' => $room->roomType->amenities,
                ]
            ];
        });

        return response()->json([
            'available_rooms' => $availableRooms,
            'search_params' => [
                'check_in_date' => $checkIn->format('Y-m-d'),
                'check_out_date' => $checkOut->format('Y-m-d'),
                'total_nights' => $totalNights,
                'adults' => $request->adults ?? 1,
                'children' => $request->children ?? 0,
            ]
        ]);
    }
}