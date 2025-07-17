<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    /**
     * Display a listing of the rooms.
     */
    public function index(Request $request)
    {
        $query = Room::with(['branch', 'roomType']);

        // Filtros
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('room_type_id') && $request->room_type_id) {
            $query->where('room_type_id', $request->room_type_id);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('room_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'room_number');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $rooms = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $rooms,
            'message' => 'Habitaciones obtenidas exitosamente'
        ]);
    }

    /**
     * Store a newly created room in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'room_type_id' => 'required|exists:room_types,id',
            'room_number' => [
                'required',
                'string',
                'max:10',
                Rule::unique('rooms')->where(function ($query) use ($request) {
                    return $query->where('branch_id', $request->branch_id);
                })
            ],
            'floor' => 'required|integer|min:1|max:20',
            'price_per_night' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string|max:100',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:available,occupied,maintenance,cleaning',
            'is_active' => 'boolean'
        ]);

        $roomData = $request->except(['photos']);
        $roomData['is_active'] = $request->boolean('is_active', true);

        // Procesar fotos si se enviaron
        if ($request->hasFile('photos')) {
            $photos = [];
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('rooms', 'public');
                $photos[] = $path;
            }
            $roomData['photos'] = $photos;
        }

        $room = Room::create($roomData);
        $room->load(['branch', 'roomType']);

        return response()->json([
            'success' => true,
            'data' => $room,
            'message' => 'Habitación creada exitosamente'
        ], 201);
    }

    /**
     * Display the specified room.
     */
    public function show($id)
    {
        $room = Room::with(['branch', 'roomType', 'reservations' => function ($query) {
            $query->where('status', '!=', 'cancelled')
                  ->orderBy('check_in_date', 'desc')
                  ->limit(5);
        }])->findOrFail($id);

        // Obtener estadísticas adicionales
        $stats = [
            'total_reservations' => $room->reservations()->count(),
            'active_reservations' => $room->reservations()->active()->count(),
            'completed_reservations' => $room->reservations()->where('status', 'completed')->count(),
            'total_revenue' => $room->reservations()
                ->where('status', 'completed')
                ->sum('total_amount'),
            'average_occupancy' => $this->calculateAverageOccupancy($room),
        ];

        return response()->json([
            'success' => true,
            'data' => $room,
            'stats' => $stats,
            'message' => 'Habitación obtenida exitosamente'
        ]);
    }

    /**
     * Update the specified room in storage.
     */
    public function update(Request $request, $id)
    {
        $room = Room::findOrFail($id);

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'room_type_id' => 'required|exists:room_types,id',
            'room_number' => [
                'required',
                'string',
                'max:10',
                Rule::unique('rooms')->where(function ($query) use ($request) {
                    return $query->where('branch_id', $request->branch_id);
                })->ignore($room->id)
            ],
            'floor' => 'required|integer|min:1|max:20',
            'price_per_night' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'amenities' => 'nullable|array',
            'amenities.*' => 'string|max:100',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:available,occupied,maintenance,cleaning',
            'is_active' => 'boolean'
        ]);

        $roomData = $request->except(['photos']);
        $roomData['is_active'] = $request->boolean('is_active');

        // Procesar fotos si se enviaron
        if ($request->hasFile('photos')) {
            // Eliminar fotos anteriores
            if ($room->photos) {
                foreach ($room->photos as $photo) {
                    Storage::disk('public')->delete($photo);
                }
            }

            $photos = [];
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('rooms', 'public');
                $photos[] = $path;
            }
            $roomData['photos'] = $photos;
        }

        $room->update($roomData);
        $room->load(['branch', 'roomType']);

        return response()->json([
            'success' => true,
            'data' => $room,
            'message' => 'Habitación actualizada exitosamente'
        ]);
    }

    /**
     * Remove the specified room from storage.
     */
    public function destroy($id)
    {
        $room = Room::findOrFail($id);

        // Verificar si tiene reservas activas
        $activeReservations = $room->reservations()->active()->count();
        if ($activeReservations > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la habitación porque tiene reservas activas'
            ], 400);
        }

        // Eliminar fotos
        if ($room->photos) {
            foreach ($room->photos as $photo) {
                Storage::disk('public')->delete($photo);
            }
        }

        $room->delete();

        return response()->json([
            'success' => true,
            'message' => 'Habitación eliminada exitosamente'
        ]);
    }

    /**
     * Toggle room status.
     */
    public function toggleStatus($id)
    {
        $room = Room::findOrFail($id);
        $room->is_active = !$room->is_active;
        $room->save();

        return response()->json([
            'success' => true,
            'data' => $room,
            'message' => 'Estado de la habitación actualizado exitosamente'
        ]);
    }

    /**
     * Get rooms statistics.
     */
    public function getStats()
    {
        $stats = [
            'total_rooms' => Room::count(),
            'active_rooms' => Room::where('is_active', true)->count(),
            'available_rooms' => Room::where('status', 'available')->where('is_active', true)->count(),
            'occupied_rooms' => Room::where('status', 'occupied')->count(),
            'maintenance_rooms' => Room::where('status', 'maintenance')->count(),
            'cleaning_rooms' => Room::where('status', 'cleaning')->count(),
            'average_price' => Room::where('is_active', true)->avg('price_per_night'),
            'total_revenue' => Room::join('reservations', 'rooms.id', '=', 'reservations.room_id')
                ->where('reservations.status', 'completed')
                ->sum('reservations.total_amount'),
            'occupancy_rate' => $this->calculateOccupancyRate(),
        ];

        // Estadísticas por sucursal - CORREGIDO
        $statsByBranch = Room::selectRaw('
            branches.name as branch_name,
            COUNT(rooms.id) as total_rooms,
            COUNT(CASE WHEN rooms.status = \'available\' AND rooms.is_active = true THEN 1 END) as available_rooms,
            COUNT(CASE WHEN rooms.status = \'occupied\' THEN 1 END) as occupied_rooms,
            AVG(rooms.price_per_night) as avg_price
        ')
        ->join('branches', 'rooms.branch_id', '=', 'branches.id')
        ->groupBy('branches.id', 'branches.name')
        ->get();

        // Estadísticas por tipo de habitación - CORREGIDO
        $statsByRoomType = Room::selectRaw('
            room_types.name as room_type_name,
            COUNT(rooms.id) as total_rooms,
            COUNT(CASE WHEN rooms.status = \'available\' AND rooms.is_active = true THEN 1 END) as available_rooms,
            COUNT(CASE WHEN rooms.status = \'occupied\' THEN 1 END) as occupied_rooms,
            AVG(rooms.price_per_night) as avg_price
        ')
        ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
        ->groupBy('room_types.id', 'room_types.name')
        ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'general' => $stats,
                'by_branch' => $statsByBranch,
                'by_room_type' => $statsByRoomType,
            ],
            'message' => 'Estadísticas obtenidas exitosamente'
        ]);
    }

    /**
     * Get room types for dropdown.
     */
    public function getRoomTypes()
    {
        $roomTypes = RoomType::select('id', 'name', 'description', 'max_adults', 'max_children')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $roomTypes,
            'message' => 'Tipos de habitación obtenidos exitosamente'
        ]);
    }

    /**
     * Get branches for dropdown.
     */
    public function getBranches()
    {
        $branches = Branch::select('id', 'name', 'address')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $branches,
            'message' => 'Sucursales obtenidas exitosamente'
        ]);
    }

    /**
     * Calculate average occupancy for a room.
     */
    private function calculateAverageOccupancy($room)
    {
        $totalDays = $room->reservations()
            ->where('status', 'completed')
            ->sum('total_nights');

        $totalPossibleDays = now()->diffInDays($room->created_at);

        if ($totalPossibleDays == 0) {
            return 0;
        }

        return round(($totalDays / $totalPossibleDays) * 100, 2);
    }

    /**
     * Calculate overall occupancy rate.
     */
    private function calculateOccupancyRate()
    {
        $totalRooms = Room::where('is_active', true)->count();
        $occupiedRooms = Room::where('status', 'occupied')->count();

        if ($totalRooms == 0) {
            return 0;
        }

        return round(($occupiedRooms / $totalRooms) * 100, 2);
    }

    /**
     * Update room status.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:available,occupied,maintenance,cleaning'
        ]);

        $room = Room::findOrFail($id);

        // Verificar si puede cambiar el estado
        if ($request->status === 'available' && $room->status === 'occupied') {
            $activeRegistration = $room->registrations()->where('status', 'active')->first();
            if ($activeRegistration) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cambiar a disponible porque hay un registro activo'
                ], 400);
            }
        }

        $room->status = $request->status;
        $room->save();

        return response()->json([
            'success' => true,
            'data' => $room,
            'message' => 'Estado de la habitación actualizado exitosamente'
        ]);
    }
}