<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Registration;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReservationController extends Controller
{
    /**
     * Obtener listado de reservas con filtros y paginación
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Reservation::with(['user', 'branch', 'room.roomType', 'payments', 'registration']);

            // Filtros
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            if ($request->has('branch_id') && $request->branch_id !== '') {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->has('check_in_date') && $request->check_in_date !== '') {
                $query->whereDate('check_in_date', '>=', $request->check_in_date);
            }

            if ($request->has('check_out_date') && $request->check_out_date !== '') {
                $query->whereDate('check_out_date', '<=', $request->check_out_date);
            }

            if ($request->has('search') && $request->search !== '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('reservation_code', 'like', "%{$search}%")
                      ->orWhereHas('user', function($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $reservations = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $reservations->items(),
                'pagination' => [
                    'current_page' => $reservations->currentPage(),
                    'last_page' => $reservations->lastPage(),
                    'per_page' => $reservations->perPage(),
                    'total' => $reservations->total(),
                    'from' => $reservations->firstItem(),
                    'to' => $reservations->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las reservas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalles de una reserva específica
     */
    public function show($id): JsonResponse
    {
        try {
            $reservation = Reservation::with([
                'user',
                'branch',
                'room.roomType',
                'payments.verifiedBy',
                'registration.serviceConsumptions'
            ])->find($id);

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reserva no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $reservation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar una reserva
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $reservation = Reservation::find($id);

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reserva no encontrada'
                ], 404);
            }

            // Validaciones
            $validator = Validator::make($request->all(), [
                'check_in_date' => 'sometimes|date|after_or_equal:today',
                'check_out_date' => 'sometimes|date|after:check_in_date',
                'adults_count' => 'sometimes|integer|min:1',
                'children_count' => 'sometimes|integer|min:0',
                'needs_parking' => 'sometimes|boolean',
                'special_requests' => 'sometimes|string|max:1000',
                'room_id' => 'sometimes|exists:rooms,id',
                'status' => 'sometimes|in:pending_payment,payment_submitted,confirmed,checked_in,completed,cancelled'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // No permitir editar reservas completadas o canceladas
            if (in_array($reservation->status, ['completed', 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede editar una reserva completada o cancelada'
                ], 400);
            }

            // Si se cambia la habitación, verificar disponibilidad
            if ($request->has('room_id') && $request->room_id != $reservation->room_id) {
                $newRoom = Room::find($request->room_id);
                if (!$newRoom || !$newRoom->isAvailable()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La habitación seleccionada no está disponible'
                    ], 400);
                }
            }

            DB::beginTransaction();

            // Actualizar campos
            $reservation->update($request->only([
                'check_in_date',
                'check_out_date',
                'adults_count',
                'children_count',
                'needs_parking',
                'special_requests',
                'room_id',
                'status'
            ]));

            // Recalcular totales si cambiaron las fechas o habitación
            if ($request->has('check_in_date') || $request->has('check_out_date') || $request->has('room_id')) {
                $this->recalculateTotals($reservation);
            }

            DB::commit();

            $reservation->load(['user', 'branch', 'room.roomType', 'payments']);

            return response()->json([
                'success' => true,
                'message' => 'Reserva actualizada exitosamente',
                'data' => $reservation
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Realizar check-in de una reserva
     */
    public function checkIn(Request $request, $id): JsonResponse
    {
        try {
            $reservation = Reservation::with(['room', 'user'])->find($id);

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reserva no encontrada'
                ], 404);
            }

            // Validar que la reserva esté confirmada
            if ($reservation->status !== 'confirmed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se puede hacer check-in de reservas confirmadas'
                ], 400);
            }

            // Validar que sea el día de check-in o posterior
            if (Carbon::today()->lt($reservation->check_in_date)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede hacer check-in antes de la fecha programada'
                ], 400);
            }

            // Validar que la habitación esté disponible
            if (!$reservation->room->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La habitación no está disponible para check-in'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'additional_guests' => 'sometimes|array',
                'additional_guests.*.name' => 'required_with:additional_guests|string|max:255',
                'additional_guests.*.document_type' => 'required_with:additional_guests|string|max:50',
                'additional_guests.*.document_number' => 'required_with:additional_guests|string|max:50',
                'notes' => 'sometimes|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Actualizar estado de la reserva
            $reservation->update(['status' => 'checked_in']);

            // Cambiar estado de la habitación
            $reservation->room->update(['status' => 'occupied']);

            // Crear registro
            $registration = Registration::create([
                'reservation_id' => $reservation->id,
                'user_id' => $reservation->user_id,
                'branch_id' => $reservation->branch_id,
                'room_id' => $reservation->room_id,
                'registration_code' => (new Registration())->generateCode(),
                'actual_check_in' => now(),
                'additional_guests' => $request->get('additional_guests', []),
                'status' => 'active',
                'notes' => $request->get('notes'),
                'registered_by' => auth()->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Check-in realizado exitosamente',
                'data' => [
                    'reservation' => $reservation->load(['user', 'branch', 'room']),
                    'registration' => $registration->load(['registeredBy'])
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al realizar check-in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Realizar check-out de una reserva
     */
    public function checkOut(Request $request, $id): JsonResponse
    {
        try {
            $reservation = Reservation::with(['room', 'registration'])->find($id);

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reserva no encontrada'
                ], 404);
            }

            // Validar que la reserva tenga check-in
            if ($reservation->status !== 'checked_in') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se puede hacer check-out de reservas con check-in'
                ], 400);
            }

            if (!$reservation->registration) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el registro de check-in'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'notes' => 'sometimes|string|max:1000',
                'damage_report' => 'sometimes|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Actualizar estado de la reserva
            $reservation->update(['status' => 'completed']);

            // Cambiar estado de la habitación
            $reservation->room->update(['status' => 'available']);

            // Actualizar registro
            $reservation->registration->update([
                'actual_check_out' => now(),
                'status' => 'completed',
                'notes' => $reservation->registration->notes . "\n" . $request->get('notes', '')
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Check-out realizado exitosamente',
                'data' => [
                    'reservation' => $reservation->load(['user', 'branch', 'room']),
                    'registration' => $reservation->registration
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
     * Cancelar una reserva
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        try {
            $reservation = Reservation::with(['room'])->find($id);

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

            // No permitir cancelar si ya tiene check-in
            if ($reservation->status === 'checked_in') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar una reserva con check-in activo'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'cancellation_reason' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe proporcionar una razón para la cancelación',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Actualizar estado de la reserva
            $reservation->update([
                'status' => 'cancelled',
                'special_requests' => $reservation->special_requests . "\nCancelado: " . $request->cancellation_reason
            ]);

            // Si la habitación estaba reservada, liberarla
            if ($reservation->room && $reservation->room->status === 'reserved') {
                $reservation->room->update(['status' => 'available']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reserva cancelada exitosamente',
                'data' => $reservation->load(['user', 'branch', 'room'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
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