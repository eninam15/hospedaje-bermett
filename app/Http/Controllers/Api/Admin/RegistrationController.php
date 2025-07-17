<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Models\Branch;
use App\Models\ServiceConsumption;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RegistrationController extends Controller
{
    /**
     * Obtener listado de registros con filtros y paginación
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Registration::with([
                'user',
                'branch',
                'room.roomType',
                'reservation',
                'registeredBy',
                'serviceConsumptions.service'
            ]);

            // Filtros
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            if ($request->has('branch_id') && $request->branch_id !== '') {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->has('check_in_date') && $request->check_in_date !== '') {
                $query->whereDate('actual_check_in', '>=', $request->check_in_date);
            }

            if ($request->has('check_out_date') && $request->check_out_date !== '') {
                $query->whereDate('actual_check_out', '<=', $request->check_out_date);
            }

            if ($request->has('search') && $request->search !== '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('registration_code', 'like', "%{$search}%")
                      ->orWhereHas('user', function($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      })
                      ->orWhereHas('room', function($roomQuery) use ($search) {
                          $roomQuery->where('room_number', 'like', "%{$search}%");
                      });
                });
            }

            // Filtro para registros activos (huéspedes actuales)
            if ($request->has('active_only') && $request->active_only == 'true') {
                $query->where('status', 'active');
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $registrations = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $registrations->items(),
                'pagination' => [
                    'current_page' => $registrations->currentPage(),
                    'last_page' => $registrations->lastPage(),
                    'per_page' => $registrations->perPage(),
                    'total' => $registrations->total(),
                    'from' => $registrations->firstItem(),
                    'to' => $registrations->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los registros',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un registro directo (sin reserva previa)
     */
    public function storeDirect(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'branch_id' => 'required|exists:branches,id',
                'room_id' => 'required|exists:rooms,id',
                'expected_checkout' => 'required|date|after:today',
                'additional_guests' => 'sometimes|array',
                'additional_guests.*.name' => 'required_with:additional_guests|string|max:255',
                'additional_guests.*.document_type' => 'required_with:additional_guests|string|max:50',
                'additional_guests.*.document_number' => 'required_with:additional_guests|string|max:50',
                'notes' => 'sometimes|string|max:1000',
                'adults_count' => 'required|integer|min:1',
                'children_count' => 'sometimes|integer|min:0',
                'needs_parking' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que la habitación esté disponible
            $room = Room::with('branch')->find($request->room_id);
            if (!$room->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La habitación seleccionada no está disponible'
                ], 400);
            }

            // Verificar que el usuario existe y está activo
            $user = User::find($request->user_id);
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario seleccionado no está activo'
                ], 400);
            }

            DB::beginTransaction();

            // Calcular totales para la reserva directa
            $checkIn = Carbon::now();
            $checkOut = Carbon::parse($request->expected_checkout);
            $totalNights = $checkIn->diffInDays($checkOut);
            
            $roomTotal = $room->price_per_night * $totalNights;
            $parkingFee = $request->get('needs_parking', false) ? 10 * $totalNights : 0;
            $totalAmount = $roomTotal + $parkingFee;

            // Crear reserva automática
            $reservation = Reservation::create([
                'user_id' => $request->user_id,
                'branch_id' => $request->branch_id,
                'room_id' => $request->room_id,
                'reservation_code' => 'DIR' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT),
                'check_in_date' => $checkIn->format('Y-m-d'),
                'check_out_date' => $checkOut->format('Y-m-d'),
                'total_nights' => $totalNights,
                'adults_count' => $request->adults_count,
                'children_count' => $request->get('children_count', 0),
                'needs_parking' => $request->get('needs_parking', false),
                'parking_fee' => $parkingFee,
                'room_total' => $roomTotal,
                'services_total' => 0,
                'total_amount' => $totalAmount,
                'status' => 'checked_in',
                'special_requests' => 'Registro directo - ' . $request->get('notes', ''),
                'payment_method' => 'cash'
            ]);

            // Crear registro
            $registration = Registration::create([
                'reservation_id' => $reservation->id,
                'user_id' => $request->user_id,
                'branch_id' => $request->branch_id,
                'room_id' => $request->room_id,
                'registration_code' => 'REG' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT),
                'actual_check_in' => now(),
                'additional_guests' => $request->get('additional_guests', []),
                'status' => 'active',
                'notes' => $request->get('notes'),
                'registered_by' => auth()->id()
            ]);

            // Cambiar estado de la habitación
            $room->update(['status' => 'occupied']);

            DB::commit();

            $registration->load([
                'user',
                'branch',
                'room.roomType',
                'reservation',
                'registeredBy'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registro directo creado exitosamente',
                'data' => [
                    'registration' => $registration,
                    'reservation' => $reservation
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el registro directo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalles de un registro específico
     */
    public function show($id): JsonResponse
    {
        try {
            $registration = Registration::with([
                'user',
                'branch',
                'room.roomType',
                'reservation',
                'registeredBy',
                'serviceConsumptions.service',
                'serviceConsumptions.registeredBy'
            ])->find($id);

            if (!$registration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registro no encontrado'
                ], 404);
            }

            // Calcular totales de servicios
            $totalServices = $registration->serviceConsumptions->sum('total_amount');
            $pendingServices = $registration->serviceConsumptions()
                                          ->where('status', 'pending')
                                          ->sum('total_amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'registration' => $registration,
                    'service_totals' => [
                        'total_services' => $totalServices,
                        'pending_services' => $pendingServices,
                        'paid_services' => $totalServices - $pendingServices
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el registro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un registro
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $registration = Registration::with(['reservation', 'room'])->find($id);

            if (!$registration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registro no encontrado'
                ], 404);
            }

            // No permitir editar registros completados
            if ($registration->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede editar un registro completado'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'additional_guests' => 'sometimes|array',
                'additional_guests.*.name' => 'required_with:additional_guests|string|max:255',
                'additional_guests.*.document_type' => 'required_with:additional_guests|string|max:50',
                'additional_guests.*.document_number' => 'required_with:additional_guests|string|max:50',
                'notes' => 'sometimes|string|max:1000',
                'room_id' => 'sometimes|exists:rooms,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Si se cambia la habitación
            if ($request->has('room_id') && $request->room_id != $registration->room_id) {
                $newRoom = Room::find($request->room_id);
                
                if (!$newRoom->isAvailable()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La habitación seleccionada no está disponible'
                    ], 400);
                }

                // Liberar habitación actual
                $registration->room->update(['status' => 'available']);
                
                // Ocupar nueva habitación
                $newRoom->update(['status' => 'occupied']);
                
                // Actualizar también la reserva si existe
                if ($registration->reservation) {
                    $registration->reservation->update(['room_id' => $request->room_id]);
                }
            }

            // Actualizar campos del registro
            $registration->update($request->only([
                'additional_guests',
                'notes',
                'room_id'
            ]));

            DB::commit();

            $registration->load([
                'user',
                'branch', 
                'room.roomType',
                'reservation',
                'registeredBy'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Registro actualizado exitosamente',
                'data' => $registration
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el registro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de registros
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());
            $branchId = $request->get('branch_id');

            $query = Registration::whereBetween('created_at', [$startDate, $endDate]);
            
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            // Estadísticas generales
            $totalRegistrations = $query->count();
            $activeRegistrations = $query->where('status', 'active')->count();
            $completedRegistrations = $query->where('status', 'completed')->count();
            
            // Registros directos (sin reserva previa)
            $directRegistrations = $query->whereHas('reservation', function($q) {
                $q->where('reservation_code', 'like', 'DIR%');
            })->count();

            // Promedio de estadía
            $avgStayDuration = Registration::where('status', 'completed')
                                         ->whereBetween('created_at', [$startDate, $endDate])
                                         ->when($branchId, function($q) use ($branchId) {
                                             return $q->where('branch_id', $branchId);
                                         })
                                         ->selectRaw('AVG(DATEDIFF(actual_check_out, actual_check_in)) as avg_duration')
                                         ->value('avg_duration') ?? 0;

            // Huéspedes por mes
            $monthlyStats = Registration::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total_registrations'),
                DB::raw('SUM(JSON_LENGTH(COALESCE(additional_guests, "[]")) + 1) as total_guests')
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

            // Registros por sucursal
            $branchStats = Registration::select('branch_id', 
                                              DB::raw('COUNT(*) as total_registrations'),
                                              DB::raw('SUM(JSON_LENGTH(COALESCE(additional_guests, "[]")) + 1) as total_guests'))
                                     ->with('branch:id,name')
                                     ->whereBetween('created_at', [$startDate, $endDate])
                                     ->groupBy('branch_id')
                                     ->orderBy('total_registrations', 'desc')
                                     ->get();

            // Huéspedes actualmente en el hotel
            $currentGuests = Registration::where('status', 'active')
                                        ->when($branchId, function($q) use ($branchId) {
                                            return $q->where('branch_id', $branchId);
                                        })
                                        ->with(['user', 'room', 'branch'])
                                        ->get()
                                        ->map(function($registration) {
                                            return [
                                                'registration_id' => $registration->id,
                                                'guest_name' => $registration->user->name,
                                                'room_number' => $registration->room->room_number,
                                                'branch_name' => $registration->branch->name,
                                                'check_in' => $registration->actual_check_in,
                                                'total_guests' => $registration->total_guests,
                                                'days_stayed' => Carbon::now()->diffInDays($registration->actual_check_in)
                                            ];
                                        });

            // Consumo de servicios
            $serviceStats = ServiceConsumption::whereHas('registration', function($q) use ($startDate, $endDate, $branchId) {
                                               $q->whereBetween('created_at', [$startDate, $endDate]);
                                               if ($branchId) {
                                                   $q->where('branch_id', $branchId);
                                               }
                                           })
                                           ->select(DB::raw('COUNT(*) as total_consumptions'),
                                                   DB::raw('SUM(total_amount) as total_revenue'),
                                                   DB::raw('AVG(total_amount) as avg_consumption'))
                                           ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'general' => [
                        'total_registrations' => $totalRegistrations,
                        'active_registrations' => $activeRegistrations,
                        'completed_registrations' => $completedRegistrations,
                        'direct_registrations' => $directRegistrations,
                        'avg_stay_duration' => round($avgStayDuration, 1),
                        'completion_rate' => $totalRegistrations > 0 ? round(($completedRegistrations / $totalRegistrations) * 100, 2) : 0
                    ],
                    'monthly_trends' => $monthlyStats,
                    'branch_distribution' => $branchStats,
                    'current_guests' => $currentGuests,
                    'service_consumption' => [
                        'total_consumptions' => $serviceStats->total_consumptions ?? 0,
                        'total_revenue' => $serviceStats->total_revenue ?? 0,
                        'avg_consumption' => $serviceStats->avg_consumption ?? 0
                    ],
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}