<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Branch;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::active()
            ->with(['rooms' => function($query) {
                $query->active();
            }])
            ->get()
            ->map(function($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'category' => $branch->category,
                    'address' => $branch->address,
                    'phone' => $branch->phone,
                    'city' => $branch->city,
                    'description' => $branch->description,
                    'available_rooms' => $branch->available_rooms,
                    'total_rooms' => $branch->rooms->count(),
                    'check_in_time' => $branch->check_in_time,
                    'check_out_time' => $branch->check_out_time,
                ];
            });

        return response()->json([
            'branches' => $branches
        ]);
    }

    public function show($id)
    {
        $branch = Branch::active()
            ->with(['rooms.roomType', 'services'])
            ->findOrFail($id);

        return response()->json([
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'category' => $branch->category,
                'address' => $branch->address,
                'phone' => $branch->phone,
                'city' => $branch->city,
                'description' => $branch->description,
                'check_in_time' => $branch->check_in_time,
                'check_out_time' => $branch->check_out_time,
                'available_rooms' => $branch->available_rooms,
                'occupied_rooms' => $branch->occupied_rooms,
                'rooms' => $branch->rooms->map(function($room) {
                    return [
                        'id' => $room->id,
                        'room_number' => $room->room_number,
                        'type' => $room->roomType->name,
                        'price' => $room->price_per_night,
                        'status' => $room->status,
                        'photos' => $room->photos,
                    ];
                }),
                'services' => $branch->services->where('is_available', true)->map(function($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'description' => $service->description,
                        'category' => $service->category,
                        'price' => $service->price,
                    ];
                }),
            ]
        ]);
    }
}