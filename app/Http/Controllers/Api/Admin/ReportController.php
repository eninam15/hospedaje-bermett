<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Models\Room;
use App\Models\Branch;
use App\Models\Service;
use App\Models\ServiceConsumption;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Reporte de reservas
     */
    public function reservations(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());
            $branchId = $request->get('branch_id');
            $groupBy = $request->get('group_by', 'day'); // day, week, month

            $query = Reservation::whereBetween('created_at', [$startDate, $endDate]);
            
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            // Datos generales
            $totalReservations = $query->count();
            $confirmedReservations = $query->where('status', 'confirmed')->count();
            $cancelledReservations = $query->where('status', 'cancelled')->count();
            $completedReservations = $query->where('status', 'completed')->count();
            $totalRevenue = $query->whereIn('status', ['confirmed', 'checked_in', 'completed'])->sum('total_amount');

            // Agrupación temporal
            $timeGrouping = $this->getTimeGrouping($groupBy);
            $reservationsTrend = Reservation::select(
                DB::raw($timeGrouping . ' as period'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed"),
                DB::raw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled"),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN status IN ('confirmed', 'checked_in', 'completed') THEN total_amount ELSE 0 END) as revenue")
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->groupBy('period')
            ->orderBy('period')
            ->get();

            // Por estado
            $statusDistribution = $query->select('status', DB::raw('COUNT(*) as count'))
                                       ->groupBy('status')
                                       ->get();

            // Por sucursal
            $branchDistribution = Reservation::select('branch_id', DB::raw('COUNT(*) as count'))
                                            ->with('branch:id,name')
                                            ->whereBetween('created_at', [$startDate, $endDate])
                                            ->groupBy('branch_id')
                                            ->get();

            // Top habitaciones más reservadas
            $topRooms = Reservation::select('room_id', DB::raw('COUNT(*) as reservations'))
                                  ->with('room:id,room_number,branch_id')
                                  ->whereBetween('created_at', [$startDate, $endDate])
                                  ->when($branchId, function($q) use ($branchId) {
                                      return $q->where('branch_id', $branchId);
                                  })
                                  ->groupBy('room_id')
                                  ->orderBy('reservations', 'desc')
                                  ->limit(10)
                                  ->get();

            // Promedio de huéspedes
            $avgGuests = $query->selectRaw('AVG(adults_count + children_count) as avg_guests')->value('avg_guests') ?? 0;

            // Duración promedio de estadía
            $avgStayDuration = $query->selectRaw('AVG(total_nights) as avg_nights')->value('avg_nights') ?? 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_reservations' => $totalReservations,
                        'confirmed_reservations' => $confirmedReservations,
                        'cancelled_reservations' => $cancelledReservations,
                        'completed_reservations' => $completedReservations,
                        'total_revenue' => $totalRevenue,
                        'avg_guests' => round($avgGuests, 1),
                        'avg_stay_duration' => round($avgStayDuration, 1),
                        'cancellation_rate' => $totalReservations > 0 ? round(($cancelledReservations / $totalReservations) * 100, 2) : 0
                    ],
                    'trends' => $reservationsTrend,
                    'status_distribution' => $statusDistribution,
                    'branch_distribution' => $branchDistribution,
                    'top_rooms' => $topRooms,
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'group_by' => $groupBy
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de reservas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de ingresos
     */
    public function income(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());
            $branchId = $request->get('branch_id');
            $groupBy = $request->get('group_by', 'day');

            // Ingresos por reservas
            $reservationQuery = Reservation::whereBetween('created_at', [$startDate, $endDate])
                                          ->whereIn('status', ['confirmed', 'checked_in', 'completed']);
            
            if ($branchId) {
                $reservationQuery->where('branch_id', $branchId);
            }

            $totalReservationIncome = $reservationQuery->sum('total_amount');
            $roomIncome = $reservationQuery->sum('room_total');
            $parkingIncome = $reservationQuery->sum('parking_fee');

            // Ingresos por servicios
            $serviceQuery = ServiceConsumption::whereBetween('created_at', [$startDate, $endDate])
                                             ->where('status', 'paid');
            
            if ($branchId) {
                $serviceQuery->whereHas('registration', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            }

            $totalServiceIncome = $serviceQuery->sum('total_amount');

            // Tendencia de ingresos
            $timeGrouping = $this->getTimeGrouping($groupBy);
            $incomeTrend = Reservation::select(
                DB::raw($timeGrouping . ' as period'),
                DB::raw('SUM(room_total) as room_income'),
                DB::raw('SUM(parking_fee) as parking_income'),
                DB::raw('SUM(total_amount) as total_income')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->groupBy('period')
            ->orderBy('period')
            ->get();

            // Ingresos por sucursal
            $branchIncome = Reservation::select(
                'branch_id',
                DB::raw('SUM(total_amount) as total_income'),
                DB::raw('COUNT(*) as reservations')
            )
            ->with('branch:id,name')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
            ->groupBy('branch_id')
            ->orderBy('total_income', 'desc')
            ->get();

            // Ingresos por tipo de habitación
            $roomTypeIncome = Reservation::select(
                'rooms.room_type_id',
                DB::raw('SUM(reservations.room_total) as income'),
                DB::raw('COUNT(*) as reservations')
            )
            ->join('rooms', 'reservations.room_id', '=', 'rooms.id')
            ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
            ->whereBetween('reservations.created_at', [$startDate, $endDate])
            ->whereIn('reservations.status', ['confirmed', 'checked_in', 'completed'])
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('reservations.branch_id', $branchId);
            })
            ->with('roomType:id,name')
            ->groupBy('rooms.room_type_id')
            ->orderBy('income', 'desc')
            ->get();

            // Ingresos por servicios
            $serviceIncome = ServiceConsumption::select(
                'service_id',
                DB::raw('SUM(total_amount) as income'),
                DB::raw('SUM(quantity) as quantity')
            )
            ->with('service:id,name,category')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'paid')
            ->when($branchId, function($q) use ($branchId) {
                return $q->whereHas('registration', function($subQ) use ($branchId) {
                    $subQ->where('branch_id', $branchId);
                });
            })
            ->groupBy('service_id')
            ->orderBy('income', 'desc')
            ->get();

            $totalIncome = $totalReservationIncome + $totalServiceIncome;

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_income' => $totalIncome,
                        'reservation_income' => $totalReservationIncome,
                        'service_income' => $totalServiceIncome,
                        'room_income' => $roomIncome,
                        'parking_income' => $parkingIncome,
                        'avg_reservation_value' => $reservationQuery->count() > 0 ? round($totalReservationIncome / $reservationQuery->count(), 2) : 0
                    ],
                    'trends' => $incomeTrend,
                    'branch_income' => $branchIncome,
                    'room_type_income' => $roomTypeIncome,
                    'service_income' => $serviceIncome,
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'group_by' => $groupBy
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de ingresos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de ocupación
     */
    public function occupancy(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());
            $branchId = $request->get('branch_id');
            $groupBy = $request->get('group_by', 'day');

            // Obtener todas las habitaciones
            $roomsQuery = Room::query();
            if ($branchId) {
                $roomsQuery->where('branch_id', $branchId);
            }
            $totalRooms = $roomsQuery->count();

            // Ocupación actual
            $occupiedRooms = $roomsQuery->where('status', 'occupied')->count();
            $currentOccupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0;

            // Ocupación histórica por período
            $period = Carbon::parse($startDate);
            $endPeriod = Carbon::parse($endDate);
            $occupancyData = [];

            while ($period <= $endPeriod) {
                $nextPeriod = $this->getNextPeriod($period, $groupBy);
                
                $occupiedCount = Registration::where('status', 'active')
                                            ->where('actual_check_in', '<=', $nextPeriod)
                                            ->where(function($q) use ($nextPeriod) {
                                                $q->whereNull('actual_check_out')
                                                  ->orWhere('actual_check_out', '>', $nextPeriod);
                                            })
                                            ->when($branchId, function($q) use ($branchId) {
                                                return $q->where('branch_id', $branchId);
                                            })
                                            ->count();

                $occupancyRate = $totalRooms > 0 ? round(($occupiedCount / $totalRooms) * 100, 2) : 0;

                $occupancyData[] = [
                    'period' => $period->format($this->getPeriodFormat($groupBy)),
                    'occupied_rooms' => $occupiedCount,
                    'total_rooms' => $totalRooms,
                    'occupancy_rate' => $occupancyRate
                ];

                $period = $nextPeriod;
            }

            // Ocupación por sucursal
            $branchOccupancy = Branch::select('branches.id', 'branches.name')
                                   ->selectRaw('COUNT(rooms.id) as total_rooms')
                                   ->selectRaw("SUM(CASE WHEN rooms.status = 'occupied' THEN 1 ELSE 0 END) as occupied_rooms")
                                   ->leftJoin('rooms', 'branches.id', '=', 'rooms.branch_id')
                                   ->groupBy('branches.id', 'branches.name')
                                   ->get()
                                   ->map(function($branch) {
                                       $occupancyRate = $branch->total_rooms > 0 ? 
                                           round(($branch->occupied_rooms / $branch->total_rooms) * 100, 2) : 0;
                                       return [
                                           'branch_id' => $branch->id,
                                           'branch_name' => $branch->name,
                                           'total_rooms' => $branch->total_rooms,
                                           'occupied_rooms' => $branch->occupied_rooms,
                                           'occupancy_rate' => $occupancyRate
                                       ];
                                   });

            // Duración promedio de estadía
            $avgStayDuration = Registration::where('status', 'completed')
                                         ->whereBetween('created_at', [$startDate, $endDate])
                                         ->when($branchId, function($q) use ($branchId) {
                                             return $q->where('branch_id', $branchId);
                                         })
                                         ->selectRaw('AVG(EXTRACT(DAY FROM (actual_check_out - actual_check_in))) as avg_duration')
                                         ->value('avg_duration') ?? 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_rooms' => $totalRooms,
                        'occupied_rooms' => $occupiedRooms,
                        'available_rooms' => $totalRooms - $occupiedRooms,
                        'current_occupancy_rate' => $currentOccupancyRate,
                        'avg_stay_duration' => round($avgStayDuration, 1)
                    ],
                    'occupancy_trends' => $occupancyData,
                    'branch_occupancy' => $branchOccupancy,
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'group_by' => $groupBy
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de ocupación',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de check-ins
     */
    public function checkins(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());
            $branchId = $request->get('branch_id');
            $groupBy = $request->get('group_by', 'day');

            $query = Registration::whereBetween('actual_check_in', [$startDate, $endDate]);
            
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            $totalCheckins = $query->count();
            $activeCheckins = $query->where('status', 'active')->count();
            $completedCheckins = $query->where('status', 'completed')->count();

            // Tendencia de check-ins
            $timeGrouping = $this->getTimeGrouping($groupBy, 'actual_check_in');
            $checkinTrend = Registration::select(
                DB::raw($timeGrouping . ' as period'),
                DB::raw('COUNT(*) as total_checkins'),
                DB::raw('SUM(CASE WHEN additional_guests IS NULL THEN 1 ELSE json_array_length(additional_guests) + 1 END) as total_guests')
            )
            ->whereBetween('actual_check_in', [$startDate, $endDate])
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->groupBy('period')
            ->orderBy('period')
            ->get();

            // Check-ins por sucursal
            $branchCheckins = Registration::select('branch_id', DB::raw('COUNT(*) as checkins'))
                                        ->with('branch:id,name')
                                        ->whereBetween('actual_check_in', [$startDate, $endDate])
                                        ->groupBy('branch_id')
                                        ->get();

            // Check-ins por día de la semana
            $weekdayCheckins = Registration::select(
                DB::raw('EXTRACT(DOW FROM actual_check_in) as weekday'),
                DB::raw('COUNT(*) as checkins')
            )
            ->whereBetween('actual_check_in', [$startDate, $endDate])
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->groupBy('weekday')
            ->orderBy('weekday')
            ->get()
            ->map(function($item) {
                $days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                return [
                    'weekday' => $days[$item->weekday], // PostgreSQL DOW: 0=Sunday
                    'checkins' => $item->checkins
                ];
            });

            // Check-ins por hora del día
            $hourlyCheckins = Registration::select(
                DB::raw('EXTRACT(HOUR FROM actual_check_in) as hour'),
                DB::raw('COUNT(*) as checkins')
            )
            ->whereBetween('actual_check_in', [$startDate, $endDate])
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

            // Registros directos vs con reserva
            $directCheckins = $query->whereHas('reservation', function($q) {
                $q->where('reservation_code', 'like', 'DIR%');
            })->count();

            $reservationCheckins = $totalCheckins - $directCheckins;

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_checkins' => $totalCheckins,
                        'active_checkins' => $activeCheckins,
                        'completed_checkins' => $completedCheckins,
                        'direct_checkins' => $directCheckins,
                        'reservation_checkins' => $reservationCheckins,
                        'direct_percentage' => $totalCheckins > 0 ? round(($directCheckins / $totalCheckins) * 100, 2) : 0
                    ],
                    'trends' => $checkinTrend,
                    'branch_distribution' => $branchCheckins,
                    'weekday_distribution' => $weekdayCheckins,
                    'hourly_distribution' => $hourlyCheckins,
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'group_by' => $groupBy
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de check-ins',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de usuarios
     */
    public function users(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());

            // Estadísticas generales de usuarios
            $totalUsers = User::count();
            $activeUsers = User::where('is_active', true)->count();
            $newUsers = User::whereBetween('created_at', [$startDate, $endDate])->count();

            // Usuarios por rol
            $usersByRole = User::select('roles.name as role', DB::raw('COUNT(users.id) as count'))
                             ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                             ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                             ->where('model_has_roles.model_type', 'App\\Models\\User')
                             ->groupBy('roles.name')
                             ->get();

            // Usuarios más activos (con más reservas)
            $activeCustomers = User::select('users.*', DB::raw('COUNT(reservations.id) as reservations_count'))
                                 ->leftJoin('reservations', 'users.id', '=', 'reservations.user_id')
                                 ->whereBetween('reservations.created_at', [$startDate, $endDate])
                                 ->groupBy('users.id')
                                 ->orderBy('reservations_count', 'desc')
                                 ->limit(10)
                                 ->get();

            // Registro de nuevos usuarios por período
            $timeGrouping = $this->getTimeGrouping($request->get('group_by', 'day'));
            $userRegistrationTrend = User::select(
                DB::raw($timeGrouping . ' as period'),
                DB::raw('COUNT(*) as new_users')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_users' => $totalUsers,
                        'active_users' => $activeUsers,
                        'inactive_users' => $totalUsers - $activeUsers,
                        'new_users' => $newUsers
                    ],
                    'role_distribution' => $usersByRole,
                    'active_customers' => $activeCustomers,
                    'registration_trends' => $userRegistrationTrend,
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de usuarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de servicios
     */
    public function services(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());
            $branchId = $request->get('branch_id');

            $query = ServiceConsumption::whereBetween('created_at', [$startDate, $endDate]);
            
            if ($branchId) {
                $query->whereHas('registration', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            }

            $totalConsumptions = $query->count();
            $totalRevenue = $query->where('status', 'paid')->sum('total_amount');
            $pendingAmount = $query->where('status', 'pending')->sum('total_amount');

            // Servicios más consumidos
            $topServices = ServiceConsumption::select(
                'service_id',
                DB::raw('COUNT(*) as consumptions'),
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->with('service:id,name,category')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, function($q) use ($branchId) {
                return $q->whereHas('registration', function($subQ) use ($branchId) {
                    $subQ->where('branch_id', $branchId);
                });
            })
            ->groupBy('service_id')
            ->orderBy('revenue', 'desc')
            ->get();

            // Consumo por categoría
            $categoryConsumption = ServiceConsumption::select(
                'services.category',
                DB::raw('COUNT(service_consumptions.id) as consumptions'),
                DB::raw('SUM(service_consumptions.total_amount) as revenue')
            )
            ->join('services', 'service_consumptions.service_id', '=', 'services.id')
            ->whereBetween('service_consumptions.created_at', [$startDate, $endDate])
            ->when($branchId, function($q) use ($branchId) {
                return $q->whereHas('registration', function($subQ) use ($branchId) {
                    $subQ->where('branch_id', $branchId);
                });
            })
            ->groupBy('services.category')
            ->get();

            // Tendencia de consumo
            $timeGrouping = $this->getTimeGrouping($request->get('group_by', 'day'));
            $consumptionTrend = ServiceConsumption::select(
                DB::raw($timeGrouping . ' as period'),
                DB::raw('COUNT(*) as consumptions'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, function($q) use ($branchId) {
                return $q->whereHas('registration', function($subQ) use ($branchId) {
                    $subQ->where('branch_id', $branchId);
                });
            })
            ->groupBy('period')
            ->orderBy('period')
            ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_consumptions' => $totalConsumptions,
                        'total_revenue' => $totalRevenue,
                        'pending_amount' => $pendingAmount,
                        'avg_consumption_value' => $totalConsumptions > 0 ? round($totalRevenue / $totalConsumptions, 2) : 0
                    ],
                    'top_services' => $topServices,
                    'category_consumption' => $categoryConsumption,
                    'consumption_trends' => $consumptionTrend,
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de servicios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de cancelaciones
     */
    public function cancellations(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());
            $branchId = $request->get('branch_id');

            $query = Reservation::where('status', 'cancelled')
                               ->whereBetween('updated_at', [$startDate, $endDate]);
            
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            $totalCancellations = $query->count();
            $lostRevenue = $query->sum('total_amount');

            // Tendencia de cancelaciones
            $timeGrouping = $this->getTimeGrouping($request->get('group_by', 'day'), 'updated_at');
            $cancellationTrend = Reservation::select(
                DB::raw($timeGrouping . ' as period'),
                DB::raw('COUNT(*) as cancellations'),
                DB::raw('SUM(total_amount) as lost_revenue')
            )
            ->where('status', 'cancelled')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->groupBy('period')
            ->orderBy('period')
            ->get();

            // Cancelaciones por sucursal
            $branchCancellations = Reservation::select('branch_id', DB::raw('COUNT(*) as cancellations'))
                                            ->with('branch:id,name')
                                            ->where('status', 'cancelled')
                                            ->whereBetween('updated_at', [$startDate, $endDate])
                                            ->groupBy('branch_id')
                                            ->get();

            // Análisis de razones de cancelación (extrayendo de special_requests)
            $cancellationReasons = Reservation::where('status', 'cancelled')
                                            ->whereBetween('updated_at', [$startDate, $endDate])
                                            ->when($branchId, function($q) use ($branchId) {
                                                return $q->where('branch_id', $branchId);
                                            })
                                            ->whereNotNull('special_requests')
                                            ->pluck('special_requests')
                                            ->filter(function($reason) {
                                                return strpos($reason, 'Cancelado:') !== false;
                                            })
                                            ->take(20);

            // Tasa de cancelación
            $totalReservations = Reservation::whereBetween('created_at', [$startDate, $endDate])
                                          ->when($branchId, function($q) use ($branchId) {
                                              return $q->where('branch_id', $branchId);
                                          })
                                          ->count();

            $cancellationRate = $totalReservations > 0 ? round(($totalCancellations / $totalReservations) * 100, 2) : 0;

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_cancellations' => $totalCancellations,
                        'lost_revenue' => $lostRevenue,
                        'cancellation_rate' => $cancellationRate,
                        'avg_cancelled_value' => $totalCancellations > 0 ? round($lostRevenue / $totalCancellations, 2) : 0
                    ],
                    'trends' => $cancellationTrend,
                    'branch_distribution' => $branchCancellations,
                    'recent_reasons' => $cancellationReasons,
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de cancelaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reporte de pagos
     */
    public function payments(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());
            $branchId = $request->get('branch_id');

            $query = Payment::whereBetween('created_at', [$startDate, $endDate]);
            
            if ($branchId) {
                $query->whereHas('reservation', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            }

            $totalPayments = $query->count();
            $verifiedPayments = $query->where('status', 'verified')->count();
            $rejectedPayments = $query->where('status', 'rejected')->count();
            $pendingPayments = $query->where('status', 'pending')->count();

            $totalAmount = $query->sum('amount');
            $verifiedAmount = $query->where('status', 'verified')->sum('amount');

            // Tiempo promedio de verificación
            $avgVerificationTime = Payment::where('status', 'verified')
                                         ->whereBetween('created_at', [$startDate, $endDate])
                                         ->when($branchId, function($q) use ($branchId) {
                                             return $q->whereHas('reservation', function($subQ) use ($branchId) {
                                                 $subQ->where('branch_id', $branchId);
                                             });
                                         })
                                         ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, verified_at)) as avg_hours')
                                         ->value('avg_hours') ?? 0;

            // Tendencia de pagos
            $timeGrouping = $this->getTimeGrouping($request->get('group_by', 'day'));
            $paymentTrend = Payment::select(
                DB::raw($timeGrouping . ' as period'),
                DB::raw('COUNT(*) as total_payments'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw("SUM(CASE WHEN status = 'verified' THEN amount ELSE 0 END) as verified_amount")
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, function($q) use ($branchId) {
                return $q->whereHas('reservation', function($subQ) use ($branchId) {
                    $subQ->where('branch_id', $branchId);
                });
            })
            ->groupBy('period')
            ->orderBy('period')
            ->get();

            // Pagos por método
            $methodDistribution = $query->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
                                       ->groupBy('payment_method')
                                       ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_payments' => $totalPayments,
                        'verified_payments' => $verifiedPayments,
                        'rejected_payments' => $rejectedPayments,
                        'pending_payments' => $pendingPayments,
                        'total_amount' => $totalAmount,
                        'verified_amount' => $verifiedAmount,
                        'verification_rate' => $totalPayments > 0 ? round(($verifiedPayments / $totalPayments) * 100, 2) : 0,
                        'avg_verification_time_hours' => round($avgVerificationTime, 1)
                    ],
                    'trends' => $paymentTrend,
                    'method_distribution' => $methodDistribution,
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de pagos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar reportes a PDF
     */
    public function export(Request $request)
    {
        try {
            $reportType = $request->get('report_type'); // reservations, income, occupancy, etc.
            $format = $request->get('format', 'pdf'); // pdf, excel, csv
            
            if (!$reportType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe especificar el tipo de reporte (report_type)'
                ], 400);
            }

            // Obtener datos del reporte
            $reportData = $this->getReportData($reportType, $request);
            
            if ($format === 'pdf') {
                return $this->generatePDF($reportType, $reportData, $request);
            } elseif ($format === 'json') {
                // Alternativa: devolver JSON formateado para descargar
                $filename = "{$reportType}_report_" . now()->format('Y-m-d_H-i-s') . ".json";
                
                return response()->json($reportData)
                    ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
                    ->header('Content-Type', 'application/json');
            }
            
            // Para otros formatos (futuro)
            return response()->json([
                'success' => false,
                'message' => 'Formato no soportado. Use: pdf, json'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar reporte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Métodos auxiliares
     */
    private function getTimeGrouping($groupBy, $dateColumn = 'created_at')
    {
        return match($groupBy) {
            'day' => "DATE($dateColumn)",
            'week' => "DATE_TRUNC('week', $dateColumn)",
            'month' => "DATE_TRUNC('month', $dateColumn)",
            'year' => "EXTRACT(YEAR FROM $dateColumn)",
            default => "DATE($dateColumn)"
        };
    }

    private function getPeriodFormat($groupBy)
    {
        return match($groupBy) {
            'day' => 'Y-m-d',
            'week' => 'Y-W',
            'month' => 'Y-m',
            'year' => 'Y',
            default => 'Y-m-d'
        };
    }

    private function getNextPeriod($period, $groupBy)
    {
        return match($groupBy) {
            'day' => $period->copy()->addDay(),
            'week' => $period->copy()->addWeek(),
            'month' => $period->copy()->addMonth(),
            'year' => $period->copy()->addYear(),
            default => $period->copy()->addDay()
        };
    }

    private function getReportData($reportType, $request)
    {
        try {
            $response = match($reportType) {
                'reservations' => $this->reservations($request),
                'income' => $this->income($request),
                'occupancy' => $this->occupancy($request),
                'checkins' => $this->checkins($request),
                'users' => $this->users($request),
                'services' => $this->services($request),
                'cancellations' => $this->cancellations($request),
                'payments' => $this->payments($request),
                default => throw new \Exception('Tipo de reporte no válido')
            };

            $responseData = json_decode($response->getContent(), true);
            
            // Debug: verificar la estructura de la respuesta
            if (!isset($responseData['data'])) {
                throw new \Exception('Estructura de respuesta inválida: ' . json_encode($responseData));
            }
            
            return $responseData['data'];
            
        } catch (\Exception $e) {
            throw new \Exception('Error al obtener datos del reporte: ' . $e->getMessage());
        }
    }

    private function generatePDF($reportType, $reportData, $request)
    {
        // Verificar si la vista existe, si no, usar una vista genérica
        $viewName = "reports.{$reportType}";
        if (!view()->exists($viewName)) {
            $viewName = 'reports.generic';
        }

        try {
            $pdf = Pdf::loadView($viewName, [
                'data' => $reportData,
                'title' => ucfirst($reportType) . ' Report',
                'reportType' => $reportType,
                'generated_at' => now(),
                'period' => [
                    'start' => $request->get('start_date'),
                    'end' => $request->get('end_date')
                ],
                'branch_id' => $request->get('branch_id')
            ]);

            $filename = "{$reportType}_report_" . now()->format('Y-m-d_H-i-s') . ".pdf";
            
            return $pdf->download($filename);
            
        } catch (\Exception $e) {
            // Si hay error con PDF, devolver respuesta JSON con los datos
            return response()->json([
                'success' => false,
                'message' => 'Error al generar PDF. Datos del reporte:',
                'data' => $reportData,
                'error' => $e->getMessage(),
                'note' => 'Instale dompdf: composer require barryvdh/laravel-dompdf y cree las vistas en resources/views/reports/'
            ]);
        }
    }
}