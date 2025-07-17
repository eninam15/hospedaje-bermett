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
use Illuminate\Support\Facades\Hash; // ← ESTE IMPORT FALTABA
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
                'user_id' => 'nullable|exists:users,id',
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
                'needs_parking' => 'sometimes|boolean',
                
                // Campos para crear usuario nuevo (si no se proporciona user_id)
                'new_user.name' => 'required_without:user_id|string|max:255',
                'new_user.email' => 'required_without:user_id|string|email|max:255|unique:users,email',
                'new_user.document_type' => 'required_without:user_id|in:ci,passport,license,other',
                'new_user.document_number' => 'required_without:user_id|string|max:50',
                'new_user.phone' => 'nullable|string|max:20'
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
            if (!$room || !$room->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La habitación seleccionada no está disponible'
                ], 400);
            }

            DB::beginTransaction();

            try {
                $userId = $request->user_id;
                
                // Si no se proporciona user_id, crear usuario nuevo
                if (!$userId && $request->has('new_user')) {
                    $newUserData = $request->new_user;
                    
                    $user = User::create([
                        'name' => $newUserData['name'],
                        'email' => $newUserData['email'],
                        'password' => Hash::make('temporal123'), // Password temporal
                        'phone' => $newUserData['phone'] ?? null,
                        'document_type' => $newUserData['document_type'],
                        'document_number' => $newUserData['document_number'],
                        'is_active' => true,
                        'email_verified_at' => now()
                    ]);
                    
                    // Asignar rol de customer
                    $user->assignRole('customer');
                    $userId = $user->id;
                }

                // Verificar que el usuario existe y está activo
                $user = User::find($userId);
                if (!$user || !$user->is_active) {
                    throw new \Exception('El usuario seleccionado no está activo o no existe');
                }

                // Calcular totales para la reserva directa
                $checkIn = Carbon::now()->startOfDay(); // Usar startOfDay() para evitar decimales
                $checkOut = Carbon::parse($request->expected_checkout)->startOfDay(); // Usar startOfDay()

                // CORRECCIÓN: Usar diffInDays() pero convertir a entero explícitamente
                $totalNights = (int) $checkIn->diffInDays($checkOut);

                // Asegurar que sea al menos 1 noche
                if ($totalNights < 1) {
                    $totalNights = 1;
                }

                // Asegurar que todos los cálculos sean con números enteros/decimales apropiados
                $roomTotal = (float) ($room->price_per_night * $totalNights);
                $parkingFee = $request->get('needs_parking', false) ? (float) (10 * $totalNights) : 0.0;
                $totalAmount = $roomTotal + $parkingFee;

                // Crear reserva automática
                $reservation = Reservation::create([
                    'user_id' => $userId,
                    'branch_id' => $request->branch_id,
                    'room_id' => $request->room_id,
                    'reservation_code' => 'DIR' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT),
                    'check_in_date' => $checkIn->format('Y-m-d'),
                    'check_out_date' => $checkOut->format('Y-m-d'),
                    'total_nights' => $totalNights, // Ahora es entero
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
                    'user_id' => $userId,
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
                        'reservation' => $reservation,
                        'user_created' => !$request->user_id // Indica si se creó un usuario nuevo
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e; // Re-lanzar para ser capturado por el catch externo
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el registro directo',
                'error' => $e->getMessage(),
                'debug' => [
                    'line' => $e->getLine(),
                    'file' => $e->getFile()
                ]
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
     * Realizar check-out de un registro
     */
    public function checkOut(Request $request, $id): JsonResponse
    {
        try {
            // Buscar el registro con sus relaciones
            $registration = Registration::with(['reservation', 'room', 'user', 'branch'])->find($id);

            if (!$registration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registro no encontrado'
                ], 404);
            }

            // Validar que el registro esté activo
            if ($registration->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se puede hacer check-out de registros activos'
                ], 400);
            }

            // Verificar que no tenga ya check-out
            if ($registration->actual_check_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este registro ya tiene check-out realizado'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'notes' => 'sometimes|string|max:1000',
                'damage_report' => 'sometimes|string|max:1000',
                'checkout_checklist' => 'sometimes|array',
                'checkout_checklist.room_cleaned' => 'sometimes|boolean',
                'checkout_checklist.keys_returned' => 'sometimes|boolean',
                'checkout_checklist.minibar_checked' => 'sometimes|boolean',
                'checkout_checklist.client_satisfied' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Preparar notas de check-out
            $checkoutNotes = [];
            
            if ($request->has('notes') && !empty($request->notes)) {
                $checkoutNotes[] = "Notas de check-out: " . $request->notes;
            }
            
            if ($request->has('damage_report') && !empty($request->damage_report)) {
                $checkoutNotes[] = "Reporte de daños: " . $request->damage_report;
            }

            // Agregar información del checklist si está presente
            if ($request->has('checkout_checklist')) {
                $checklist = $request->checkout_checklist;
                $checklistItems = [];
                
                if (isset($checklist['room_cleaned']) && $checklist['room_cleaned']) {
                    $checklistItems[] = "✓ Habitación revisada";
                }
                if (isset($checklist['keys_returned']) && $checklist['keys_returned']) {
                    $checklistItems[] = "✓ Llaves devueltas";
                }
                if (isset($checklist['minibar_checked']) && $checklist['minibar_checked']) {
                    $checklistItems[] = "✓ Minibar revisado";
                }
                if (isset($checklist['client_satisfied']) && $checklist['client_satisfied']) {
                    $checklistItems[] = "✓ Cliente satisfecho";
                }
                
                if (!empty($checklistItems)) {
                    $checkoutNotes[] = "Checklist: " . implode(', ', $checklistItems);
                }
            }

            // Combinar notas existentes con las nuevas
            $finalNotes = $registration->notes;
            if (!empty($checkoutNotes)) {
                $finalNotes .= "\n\n--- CHECK-OUT ---\n" . 
                            "Fecha: " . now()->format('d/m/Y H:i') . "\n" .
                            implode("\n", $checkoutNotes);
            }

            // Actualizar el registro
            $registration->update([
                'actual_check_out' => now(),
                'status' => 'completed',
                'notes' => $finalNotes
            ]);

            // Cambiar estado de la habitación a disponible
            $registration->room->update(['status' => 'available']);

            // Si existe una reserva asociada, actualizarla también
            if ($registration->reservation) {
                $registration->reservation->update(['status' => 'completed']);
            }

            DB::commit();

            // Recargar las relaciones para devolver datos actualizados
            $registration->load([
                'user',
                'branch',
                'room.roomType',
                'reservation',
                'registeredBy'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Check-out realizado exitosamente',
                'data' => [
                    'registration' => $registration,
                    'checkout_time' => now()->format('Y-m-d H:i:s'),
                    'stay_duration' => $this->calculateStayDuration($registration->actual_check_in, now())
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar check-out',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Método auxiliar para calcular la duración de la estadía
     */
    private function calculateStayDuration($checkIn, $checkOut): string
    {
        if (!$checkIn || !$checkOut) {
            return 'No disponible';
        }
        
        $checkInDate = Carbon::parse($checkIn);
        $checkOutDate = Carbon::parse($checkOut);
        
        $diffInDays = $checkInDate->diffInDays($checkOutDate);
        $diffInHours = $checkInDate->diffInHours($checkOutDate) % 24;
        $diffInMinutes = $checkInDate->diffInMinutes($checkOutDate) % 60;
        
        if ($diffInDays > 0) {
            return "{$diffInDays} día(s), {$diffInHours} hora(s)";
        } else if ($diffInHours > 0) {
            return "{$diffInHours} hora(s), {$diffInMinutes} minuto(s)";
        } else {
            return "{$diffInMinutes} minuto(s)";
        }
    }

    /**
     * Cancelar una reserva
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        try {
            $reservation = Reservation::with(['room', 'payments'])->find($id);

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reserva no encontrada'
                ], 404);
            }

            // No permitir cancelar reservas ya completadas o canceladas
            if (in_array($reservation->status, ['completed', 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar una reserva completada o ya cancelada'
                ], 400);
            }

            // No permitir cancelar si ya tiene check-in activo (opcional: permitir con check-out forzado)
            if ($reservation->status === 'checked_in') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar una reserva con check-in activo. Realice el check-out primero.'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'cancellation_reason' => 'required|string|max:1000',
                'post_actions' => 'sometimes|array',
                'post_actions.notify_client' => 'sometimes|boolean',
                'post_actions.process_refund' => 'sometimes|boolean',
                'post_actions.block_dates' => 'sometimes|boolean',
                'post_actions.update_pricing' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe proporcionar una razón válida para la cancelación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Preparar la información de cancelación
            $cancellationInfo = [
                'reason' => $request->cancellation_reason,
                'cancelled_by' => auth()->id(),
                'cancelled_at' => now(),
                'post_actions' => $request->get('post_actions', [])
            ];

            // Actualizar estado de la reserva
            $reservation->update([
                'status' => 'cancelled',
                'special_requests' => $reservation->special_requests . "\n\n--- CANCELACIÓN ---\n" . 
                                    "Fecha: " . now()->format('d/m/Y H:i') . "\n" .
                                    "Motivo: " . $request->cancellation_reason . "\n" .
                                    "Cancelado por: " . auth()->user()->name,
                'cancellation_data' => json_encode($cancellationInfo) // Guardar información adicional
            ]);

            // Si la habitación estaba reservada, liberarla
            if ($reservation->room && $reservation->room->status === 'reserved') {
                $reservation->room->update(['status' => 'available']);
            }

            // Procesar acciones post-cancelación
            $postActions = $request->get('post_actions', []);
            
            // Notificar al cliente si se solicita
            if ($postActions['notify_client'] ?? false) {
                // Aquí puedes agregar la lógica para enviar email
                // Mail::to($reservation->user->email)->send(new ReservationCancelled($reservation));
            }

            // Bloquear fechas si se solicita
            if ($postActions['block_dates'] ?? false) {
                // Lógica para bloquear las fechas en el calendario
                // Esto dependería de tu sistema de gestión de disponibilidad
            }

            // Registrar la cancelación en logs o auditoría
            \Log::info('Reserva cancelada', [
                'reservation_id' => $reservation->id,
                'reservation_code' => $reservation->reservation_code,
                'cancelled_by' => auth()->id(),
                'reason' => $request->cancellation_reason,
                'post_actions' => $postActions
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reserva cancelada exitosamente',
                'data' => [
                    'reservation' => $reservation->load(['user', 'branch', 'room']),
                    'cancellation_info' => $cancellationInfo
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al cancelar reserva', [
                'reservation_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de reservas
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());
            $branchId = $request->get('branch_id');

            $query = Reservation::whereBetween('created_at', [$startDate, $endDate]);
            
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            // Estadísticas generales
            $totalReservations = $query->count();
            $confirmedReservations = $query->where('status', 'confirmed')->count();
            $cancelledReservations = $query->where('status', 'cancelled')->count();
            $completedReservations = $query->where('status', 'completed')->count();
            $checkedInReservations = $query->where('status', 'checked_in')->count();

            // Ingresos
            $totalRevenue = $query->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                                 ->sum('total_amount');

            // Estadísticas por estado
            $statusStats = $query->select('status', DB::raw('count(*) as count'))
                                ->groupBy('status')
                                ->get()
                                ->pluck('count', 'status');

            // Reservas por mes (últimos 6 meses)
            $monthlyStats = Reservation::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

            // Top branches por reservas
            $topBranches = Reservation::select('branch_id', DB::raw('COUNT(*) as total_reservations'))
                                    ->with('branch:id,name')
                                    ->whereBetween('created_at', [$startDate, $endDate])
                                    ->groupBy('branch_id')
                                    ->orderBy('total_reservations', 'desc')
                                    ->limit(5)
                                    ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'general' => [
                        'total_reservations' => $totalReservations,
                        'confirmed_reservations' => $confirmedReservations,
                        'cancelled_reservations' => $cancelledReservations,
                        'completed_reservations' => $completedReservations,
                        'checked_in_reservations' => $checkedInReservations,
                        'total_revenue' => $totalRevenue,
                        'cancellation_rate' => $totalReservations > 0 ? round(($cancelledReservations / $totalReservations) * 100, 2) : 0,
                        'completion_rate' => $totalReservations > 0 ? round(($completedReservations / $totalReservations) * 100, 2) : 0
                    ],
                    'status_distribution' => $statusStats,
                    'monthly_trends' => $monthlyStats,
                    'top_branches' => $topBranches,
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

    /**
     * Método auxiliar para recalcular totales de una reserva
     */
    private function recalculateTotals(Reservation $reservation): void
    {
        if ($reservation->check_in_date && $reservation->check_out_date && $reservation->room) {
            $checkIn = Carbon::parse($reservation->check_in_date);
            $checkOut = Carbon::parse($reservation->check_out_date);
            $totalNights = $checkIn->diffInDays($checkOut);
            
            $roomTotal = $reservation->room->price_per_night * $totalNights;
            $parkingFee = $reservation->needs_parking ? 10 * $totalNights : 0; // Asumiendo 10 Bs por noche
            
            $reservation->update([
                'total_nights' => $totalNights,
                'room_total' => $roomTotal,
                'parking_fee' => $parkingFee,
                'total_amount' => $roomTotal + $parkingFee + ($reservation->services_total ?? 0)
            ]);
        }
    }

}