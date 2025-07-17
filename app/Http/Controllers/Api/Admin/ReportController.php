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

class ReportController extends Controller
{
    /**
     * Reporte de reservas enriquecido
     */
    public function reservations(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());
            $branchId = $request->get('branch_id');
            $groupBy = $request->get('group_by', 'day');

            $query = Reservation::with(['user', 'branch', 'room.roomType', 'registration'])
                               ->whereBetween('created_at', [$startDate, $endDate]);
            
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            // Datos generales
            $totalReservations = $query->count();
            $confirmedReservations = $query->where('status', 'confirmed')->count();
            $cancelledReservations = $query->where('status', 'cancelled')->count();
            $completedReservations = $query->where('status', 'completed')->count();
            $checkedInReservations = $query->where('status', 'checked_in')->count();
            $totalRevenue = $query->whereIn('status', ['confirmed', 'checked_in', 'completed'])->sum('total_amount');

            // Agrupación temporal corregida para PostgreSQL
            $timeGrouping = $this->getTimeGrouping($groupBy);
            $reservationsTrend = Reservation::select(
                DB::raw($timeGrouping . ' as period'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed"),
                DB::raw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled"),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as checked_in"),
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

            // Por sucursal con nombres
            $branchDistribution = Reservation::select('branches.name as branch_name', 'reservations.branch_id', DB::raw('COUNT(*) as count'))
                                            ->join('branches', 'reservations.branch_id', '=', 'branches.id')
                                            ->whereBetween('reservations.created_at', [$startDate, $endDate])
                                            ->groupBy('reservations.branch_id', 'branches.name')
                                            ->get();

            // Top habitaciones más reservadas con detalles
            $topRooms = Reservation::select(
                'rooms.room_number',
                'room_types.name as room_type_name',
                'branches.name as branch_name',
                DB::raw('COUNT(*) as reservations'),
                DB::raw('SUM(reservations.total_amount) as total_revenue')
            )
            ->join('rooms', 'reservations.room_id', '=', 'rooms.id')
            ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
            ->join('branches', 'rooms.branch_id', '=', 'branches.id')
            ->whereBetween('reservations.created_at', [$startDate, $endDate])
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('reservations.branch_id', $branchId);
            })
            ->groupBy('rooms.id', 'rooms.room_number', 'room_types.name', 'branches.name')
            ->orderBy('reservations', 'desc')
            ->limit(10)
            ->get();

            // Clientes más activos
            $topCustomers = Reservation::select(
                'users.name',
                'users.email',
                DB::raw('COUNT(*) as total_reservations'),
                DB::raw('SUM(reservations.total_amount) as total_spent'),
                DB::raw('AVG(reservations.total_nights) as avg_stay_duration')
            )
            ->join('users', 'reservations.user_id', '=', 'users.id')
            ->whereBetween('reservations.created_at', [$startDate, $endDate])
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('reservations.branch_id', $branchId);
            })
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderBy('total_reservations', 'desc')
            ->limit(10)
            ->get();

            // Estadísticas adicionales
            $avgGuests = $query->selectRaw('AVG(adults_count + children_count) as avg_guests')->value('avg_guests') ?? 0;
            $avgStayDuration = $query->selectRaw('AVG(total_nights) as avg_nights')->value('avg_nights') ?? 0;
            $avgReservationValue = $totalReservations > 0 ? $totalRevenue / $totalReservations : 0;

            // Reservas por tipo de habitación
            $roomTypeDistribution = Reservation::select(
                'room_types.name as room_type_name',
                DB::raw('COUNT(*) as reservations'),
                DB::raw('SUM(reservations.total_amount) as revenue')
            )
            ->join('rooms', 'reservations.room_id', '=', 'rooms.id')
            ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
            ->whereBetween('reservations.created_at', [$startDate, $endDate])
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('reservations.branch_id', $branchId);
            })
            ->groupBy('room_types.id', 'room_types.name')
            ->orderBy('reservations', 'desc')
            ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_reservations' => $totalReservations,
                        'confirmed_reservations' => $confirmedReservations,
                        'cancelled_reservations' => $cancelledReservations,
                        'completed_reservations' => $completedReservations,
                        'checked_in_reservations' => $checkedInReservations,
                        'total_revenue' => $totalRevenue,
                        'avg_guests' => round($avgGuests, 1),
                        'avg_stay_duration' => round($avgStayDuration, 1),
                        'avg_reservation_value' => round($avgReservationValue, 2),
                        'cancellation_rate' => $totalReservations > 0 ? round(($cancelledReservations / $totalReservations) * 100, 2) : 0,
                        'completion_rate' => $totalReservations > 0 ? round(($completedReservations / $totalReservations) * 100, 2) : 0
                    ],
                    'trends' => $reservationsTrend,
                    'status_distribution' => $statusDistribution,
                    'branch_distribution' => $branchDistribution,
                    'room_type_distribution' => $roomTypeDistribution,
                    'top_rooms' => $topRooms,
                    'top_customers' => $topCustomers,
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
     * Reporte de registros (check-ins) enriquecido
     */
    public function checkins(Request $request): JsonResponse
    {
        try {
            $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth());
            $branchId = $request->get('branch_id');
            $groupBy = $request->get('group_by', 'day');

            $query = Registration::with(['user', 'branch', 'room.roomType', 'reservation'])
                                ->whereBetween('actual_check_in', [$startDate, $endDate]);
            
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            $totalCheckins = $query->count();
            $activeCheckins = $query->where('status', 'active')->count();
            $completedCheckins = $query->where('status', 'completed')->count();

            // Tendencia de check-ins corregida para PostgreSQL
            $timeGrouping = $this->getTimeGrouping($groupBy, 'actual_check_in');
            $checkinTrend = Registration::select(
                DB::raw($timeGrouping . ' as period'),
                DB::raw('COUNT(*) as total_checkins'),
                DB::raw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active"),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw('SUM(CASE WHEN additional_guests IS NULL THEN 1 ELSE COALESCE(json_array_length(additional_guests), 0) + 1 END) as total_guests')
            )
            ->whereBetween('actual_check_in', [$startDate, $endDate])
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->groupBy('period')
            ->orderBy('period')
            ->get();

            // Check-ins por sucursal
            $branchCheckins = Registration::select(
                'branches.name as branch_name',
                'registrations.branch_id',
                DB::raw('COUNT(*) as checkins'),
                DB::raw('SUM(CASE WHEN additional_guests IS NULL THEN 1 ELSE COALESCE(json_array_length(additional_guests), 0) + 1 END) as total_guests')
            )
            ->join('branches', 'registrations.branch_id', '=', 'branches.id')
            ->whereBetween('registrations.actual_check_in', [$startDate, $endDate])
            ->groupBy('registrations.branch_id', 'branches.name')
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
                    'weekday' => $days[$item->weekday],
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

            // Duración promedio de estadía
            $avgStayDuration = Registration::where('status', 'completed')
                                         ->whereBetween('actual_check_in', [$startDate, $endDate])
                                         ->when($branchId, function($q) use ($branchId) {
                                             return $q->where('branch_id', $branchId);
                                         })
                                         ->selectRaw('AVG(EXTRACT(DAY FROM (actual_check_out - actual_check_in))) as avg_duration')
                                         ->value('avg_duration') ?? 0;

            // Top habitaciones por check-ins
            $topRoomsByCheckins = Registration::select(
                'rooms.room_number',
                'room_types.name as room_type_name',
                'branches.name as branch_name',
                DB::raw('COUNT(*) as checkins')
            )
            ->join('rooms', 'registrations.room_id', '=', 'rooms.id')
            ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
            ->join('branches', 'rooms.branch_id', '=', 'branches.id')
            ->whereBetween('registrations.actual_check_in', [$startDate, $endDate])
            ->when($branchId, function($q) use ($branchId) {
                return $q->where('registrations.branch_id', $branchId);
            })
            ->groupBy('rooms.id', 'rooms.room_number', 'room_types.name', 'branches.name')
            ->orderBy('checkins', 'desc')
            ->limit(10)
            ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_checkins' => $totalCheckins,
                        'active_checkins' => $activeCheckins,
                        'completed_checkins' => $completedCheckins,
                        'direct_checkins' => $directCheckins,
                        'reservation_checkins' => $reservationCheckins,
                        'direct_percentage' => $totalCheckins > 0 ? round(($directCheckins / $totalCheckins) * 100, 2) : 0,
                        'avg_stay_duration' => round($avgStayDuration, 1),
                        'completion_rate' => $totalCheckins > 0 ? round(($completedCheckins / $totalCheckins) * 100, 2) : 0
                    ],
                    'trends' => $checkinTrend,
                    'branch_distribution' => $branchCheckins,
                    'weekday_distribution' => $weekdayCheckins,
                    'hourly_distribution' => $hourlyCheckins,
                    'top_rooms_by_checkins' => $topRoomsByCheckins,
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
     * Reporte de usuarios enriquecido
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
            $usersByRole = DB::table('users')
                ->join('model_has_roles', function($join) {
                    $join->on('users.id', '=', 'model_has_roles.model_id')
                         ->where('model_has_roles.model_type', '=', 'App\\Models\\User');
                })
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->select('roles.name as role', DB::raw('COUNT(users.id) as count'))
                ->groupBy('roles.name')
                ->get();

            // Usuarios más activos (con más reservas)
            $activeCustomers = User::select(
                'users.id',
                'users.name',
                'users.email',
                'users.phone',
                'users.created_at',
                'users.is_active',
                DB::raw('COUNT(reservations.id) as reservations_count'),
                DB::raw('SUM(reservations.total_amount) as total_spent'),
                DB::raw('AVG(reservations.total_nights) as avg_stay_duration')
            )
            ->leftJoin('reservations', 'users.id', '=', 'reservations.user_id')
            ->whereBetween('reservations.created_at', [$startDate, $endDate])
            ->groupBy('users.id', 'users.name', 'users.email', 'users.phone', 'users.created_at', 'users.is_active')
            ->having('reservations_count', '>', 0)
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

            // Usuarios por tipo de documento
            $documentTypeDistribution = User::select(
                'document_type',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('document_type')
            ->get();

            // Usuarios nuevos vs existentes con actividad
            $newUsersWithActivity = User::whereBetween('created_at', [$startDate, $endDate])
                                      ->whereHas('reservations')
                                      ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'total_users' => $totalUsers,
                        'active_users' => $activeUsers,
                        'inactive_users' => $totalUsers - $activeUsers,
                        'new_users' => $newUsers,
                        'new_users_with_activity' => $newUsersWithActivity,
                        'activation_rate' => $newUsers > 0 ? round(($newUsersWithActivity / $newUsers) * 100, 2) : 0
                    ],
                    'role_distribution' => $usersByRole,
                    'document_type_distribution' => $documentTypeDistribution,
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
     * Método auxiliar corregido para PostgreSQL
     */
    private function getTimeGrouping($groupBy, $dateColumn = 'created_at')
    {
        return match($groupBy) {
            'day' => "DATE($dateColumn)",
            'week' => "DATE_TRUNC('week', $dateColumn)::date",
            'month' => "DATE_TRUNC('month', $dateColumn)::date",
            'year' => "DATE_TRUNC('year', $dateColumn)::date",
            default => "DATE($dateColumn)"
        };
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

        // Registros directos vs con reserva
        $directRegistrations = $query->whereHas('reservation', function($q) {
            $q->where('reservation_code', 'like', 'DIR%');
        })->count();

        // Duración promedio de estadía
        $avgStayDuration = Registration::where('status', 'completed')
                                     ->whereBetween('created_at', [$startDate, $endDate])
                                     ->when($branchId, function($q) use ($branchId) {
                                         return $q->where('branch_id', $branchId);
                                     })
                                     ->selectRaw('AVG(EXTRACT(DAY FROM (actual_check_out - actual_check_in))) as avg_duration')
                                     ->value('avg_duration') ?? 0;

        // Estadísticas por estado
        $statusStats = $query->select('status', DB::raw('count(*) as count'))
                            ->groupBy('status')
                            ->get()
                            ->pluck('count', 'status');

        // Registros por mes (últimos 6 meses)
        $monthlyStats = Registration::select(
            DB::raw("DATE_TRUNC('month', created_at)::date as month"),
            DB::raw('COUNT(*) as total'),
            DB::raw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active"),
            DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
        )
        ->where('created_at', '>=', Carbon::now()->subMonths(6))
        ->when($branchId, function($q) use ($branchId) {
            return $q->where('branch_id', $branchId);
        })
        ->groupBy('month')
        ->orderBy('month', 'desc')
        ->get();

        // Top branches por registros
        $topBranches = Registration::select(
            'branches.name as branch_name',
            'registrations.branch_id',
            DB::raw('COUNT(*) as total_registrations')
        )
        ->join('branches', 'registrations.branch_id', '=', 'branches.id')
        ->whereBetween('registrations.created_at', [$startDate, $endDate])
        ->groupBy('registrations.branch_id', 'branches.name')
        ->orderBy('total_registrations', 'desc')
        ->limit(5)
        ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'general' => [
                    'total_registrations' => $totalRegistrations,
                    'active_registrations' => $activeRegistrations,
                    'completed_registrations' => $completedRegistrations,
                    'direct_registrations' => $directRegistrations,
                    'reservation_registrations' => $totalRegistrations - $directRegistrations,
                    'completion_rate' => $totalRegistrations > 0 ? round(($completedRegistrations / $totalRegistrations) * 100, 2) : 0,
                    'direct_percentage' => $totalRegistrations > 0 ? round(($directRegistrations / $totalRegistrations) * 100, 2) : 0,
                    'avg_stay_duration' => round($avgStayDuration, 1)
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
            'message' => 'Error al obtener las estadísticas de registros',
            'error' => $e->getMessage()
        ], 500);
    }
}

    // ... resto de métodos (income, occupancy, cancellations, payments, export) 
    // manteniendo las correcciones de PostgreSQL y agregando más datos
}