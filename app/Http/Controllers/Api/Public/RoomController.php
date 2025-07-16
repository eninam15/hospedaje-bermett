<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::with(['branch', 'roomType'])
            ->active()
            ->available();

        // Filtrar por sucursal
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filtrar por tipo de habitación
        if ($request->filled('room_type_id')) {
            $query->where('room_type_id', $request->room_type_id);
        }

        // Filtrar por precio
        if ($request->filled('min_price')) {
            $query->where('price_per_night', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price_per_night', '<=', $request->max_price);
        }

        $rooms = $query->get()->map(function($room) {
            return [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'floor' => $room->floor,
                'price_per_night' => $room->price_per_night,
                'formatted_price' => $room->formatted_price,
                'description' => $room->description,
                'photos' => $room->photos,
                'amenities' => $room->amenities,
                'main_photo' => $room->main_photo,
                'branch' => [
                    'id' => $room->branch->id,
                    'name' => $room->branch->name,
                    'category' => $room->branch->category,
                    'address' => $room->branch->address,
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
            'rooms' => $rooms
        ]);
    }

    public function show($id)
    {
        $room = Room::with(['branch', 'roomType'])
            ->active()
            ->findOrFail($id);

        return response()->json([
            'room' => [
                'id' => $room->id,
                'room_number' => $room->room_number,
                'floor' => $room->floor,
                'price_per_night' => $room->price_per_night,
                'formatted_price' => $room->formatted_price,
                'status' => $room->status,
                'description' => $room->description,
                'photos' => $room->photos,
                'amenities' => $room->amenities,
                'main_photo' => $room->main_photo,
                'is_available' => $room->isAvailable(),
                'branch' => [
                    'id' => $room->branch->id,
                    'name' => $room->branch->name,
                    'category' => $room->branch->category,
                    'address' => $room->branch->address,
                    'phone' => $room->branch->phone,
                    'check_in_time' => $room->branch->check_in_time,
                    'check_out_time' => $room->branch->check_out_time,
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
            ]
        ]);
    }

    public function roomTypes()
    {
        $roomTypes = RoomType::all();

        return response()->json([
            'room_types' => $roomTypes
        ]);
    }
}