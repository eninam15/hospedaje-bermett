<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     * Obtener listado de pagos con filtros y paginación
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Payment::with([
                'reservation.user',
                'reservation.branch',
                'reservation.room',
                'verifiedBy'
            ]);

            // Filtros
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            if ($request->has('payment_method') && $request->payment_method !== '') {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->has('branch_id') && $request->branch_id !== '') {
                $query->whereHas('reservation', function($q) use ($request) {
                    $q->where('branch_id', $request->branch_id);
                });
            }

            if ($request->has('date_from') && $request->date_from !== '') {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to !== '') {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->has('amount_min') && $request->amount_min !== '') {
                $query->where('amount', '>=', $request->amount_min);
            }

            if ($request->has('amount_max') && $request->amount_max !== '') {
                $query->where('amount', '<=', $request->amount_max);
            }

            if ($request->has('search') && $request->search !== '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('payment_reference', 'like', "%{$search}%")
                      ->orWhereHas('reservation', function($reservationQuery) use ($search) {
                          $reservationQuery->where('reservation_code', 'like', "%{$search}%")
                                          ->orWhereHas('user', function($userQuery) use ($search) {
                                              $userQuery->where('name', 'like', "%{$search}%")
                                                       ->orWhere('email', 'like', "%{$search}%");
                                          });
                      });
                });
            }

            // Filtro para pagos con comprobante
            if ($request->has('has_proof') && $request->has_proof !== '') {
                if ($request->has_proof === 'true') {
                    $query->whereNotNull('payment_proof');
                } else {
                    $query->whereNull('payment_proof');
                }
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $payments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $payments->items(),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'from' => $payments->firstItem(),
                    'to' => $payments->lastItem(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los pagos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener pagos pendientes de verificación
     */
    public function pending(Request $request): JsonResponse
    {
        try {
            $query = Payment::pending()->with([
                'reservation.user',
                'reservation.branch',
                'reservation.room'
            ]);

            // Filtros adicionales para pagos pendientes
            if ($request->has('branch_id') && $request->branch_id !== '') {
                $query->whereHas('reservation', function($q) use ($request) {
                    $q->where('branch_id', $request->branch_id);
                });
            }

            if ($request->has('payment_method') && $request->payment_method !== '') {
                $query->where('payment_method', $request->payment_method);
            }

            // Ordenar por fecha de creación (más antiguos primero para priorizar)
            $query->orderBy('created_at', 'asc');

            // Paginación
            $perPage = $request->get('per_page', 20);
            $pendingPayments = $query->paginate($perPage);

            // Contar total de pagos pendientes por método
            $pendingStats = Payment::pending()
                                  ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
                                  ->groupBy('payment_method')
                                  ->get();

            return response()->json([
                'success' => true,
                'data' => $pendingPayments->items(),
                'pagination' => [
                    'current_page' => $pendingPayments->currentPage(),
                    'last_page' => $pendingPayments->lastPage(),
                    'per_page' => $pendingPayments->perPage(),
                    'total' => $pendingPayments->total(),
                    'from' => $pendingPayments->firstItem(),
                    'to' => $pendingPayments->lastItem(),
                ],
                'summary' => [
                    'total_pending' => $pendingPayments->total(),
                    'by_method' => $pendingStats->keyBy('payment_method'),
                    'total_amount' => $pendingStats->sum('total_amount')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los pagos pendientes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar un pago
     */
    public function verify(Request $request, $id): JsonResponse
    {
        try {
            $payment = Payment::with(['reservation'])->find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pago no encontrado'
                ], 404);
            }

            // Validar que el pago esté pendiente
            if ($payment->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden verificar pagos pendientes'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'notes' => 'sometimes|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Actualizar estado del pago
            $payment->update([
                'status' => 'verified',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'notes' => $request->get('notes')
            ]);

            // Actualizar estado de la reserva si corresponde
            if ($payment->reservation) {
                $reservation = $payment->reservation;
                
                // Si la reserva estaba pendiente de pago, cambiarla a confirmada
                if ($reservation->status === 'pending_payment') {
                    $reservation->update(['status' => 'confirmed']);
                }
                
                // Si la reserva tenía estado "payment_submitted", también confirmar
                if ($reservation->status === 'payment_submitted') {
                    $reservation->update(['status' => 'confirmed']);
                }
            }

            DB::commit();

            $payment->load([
                'reservation.user',
                'reservation.branch',
                'reservation.room',
                'verifiedBy'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pago verificado exitosamente',
                'data' => $payment
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar un pago
     */
    public function reject(Request $request, $id): JsonResponse
    {
        try {
            $payment = Payment::with(['reservation'])->find($id);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pago no encontrado'
                ], 404);
            }

            // Validar que el pago esté pendiente
            if ($payment->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden rechazar pagos pendientes'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'rejection_reason' => 'required|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debe proporcionar una razón para el rechazo',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Actualizar estado del pago
            $payment->update([
                'status' => 'rejected',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'notes' => 'Rechazado: ' . $request->rejection_reason
            ]);

            // Actualizar estado de la reserva
            if ($payment->reservation) {
                $reservation = $payment->reservation;
                
                // Si la reserva estaba en "payment_submitted", regresarla a "pending_payment"
                if ($reservation->status === 'payment_submitted') {
                    $reservation->update(['status' => 'pending_payment']);
                }
            }

            DB::commit();

            $payment->load([
                'reservation.user',
                'reservation.branch',
                'reservation.room',
                'verifiedBy'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pago rechazado exitosamente',
                'data' => $payment
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de pagos
     */
    public function getStats(Request $request): JsonResponse
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

            // Estadísticas generales
            $totalPayments = $query->count();
            $pendingPayments = $query->where('status', 'pending')->count();
            $verifiedPayments = $query->where('status', 'verified')->count();
            $rejectedPayments = $query->where('status', 'rejected')->count();

            // Montos totales
            $totalAmount = $query->sum('amount');
            $verifiedAmount = $query->where('status', 'verified')->sum('amount');
            $pendingAmount = $query->where('status', 'pending')->sum('amount');
            $rejectedAmount = $query->where('status', 'rejected')->sum('amount');

            // Estadísticas por método de pago
            $paymentMethodStats = $query->select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('AVG(amount) as avg_amount')
            )
            ->groupBy('payment_method')
            ->get();

            // Estadísticas por estado
            $statusStats = $query->select(
                'status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('status')
            ->get()
            ->keyBy('status');

            // Tendencias mensuales
            $monthlyStats = Payment::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as total_payments'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('SUM(CASE WHEN status = "verified" THEN amount ELSE 0 END) as verified_amount')
            )
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->when($branchId, function($q) use ($branchId) {
                return $q->whereHas('reservation', function($subQuery) use ($branchId) {
                    $subQuery->where('branch_id', $branchId);
                });
            })
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

            // Estadísticas por sucursal
            $branchStats = Payment::select(
                'reservations.branch_id',
                DB::raw('COUNT(payments.id) as total_payments'),
                DB::raw('SUM(payments.amount) as total_amount'),
                DB::raw('SUM(CASE WHEN payments.status = "verified" THEN payments.amount ELSE 0 END) as verified_amount')
            )
            ->join('reservations', 'payments.reservation_id', '=', 'reservations.id')
            ->with('reservation.branch:id,name')
            ->whereBetween('payments.created_at', [$startDate, $endDate])
            ->groupBy('reservations.branch_id')
            ->orderBy('total_amount', 'desc')
            ->get();

            // Tiempo promedio de verificación
            $avgVerificationTime = Payment::where('status', 'verified')
                                         ->whereBetween('created_at', [$startDate, $endDate])
                                         ->when($branchId, function($q) use ($branchId) {
                                             return $q->whereHas('reservation', function($subQuery) use ($branchId) {
                                                 $subQuery->where('branch_id', $branchId);
                                             });
                                         })
                                         ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, verified_at)) as avg_hours')
                                         ->value('avg_hours') ?? 0;

            // Pagos recientes pendientes (últimas 24 horas)
            $recentPendingPayments = Payment::pending()
                                           ->where('created_at', '>=', Carbon::now()->subDay())
                                           ->when($branchId, function($q) use ($branchId) {
                                               return $q->whereHas('reservation', function($subQuery) use ($branchId) {
                                                   $subQuery->where('branch_id', $branchId);
                                               });
                                           })
                                           ->with(['reservation.user', 'reservation.branch'])
                                           ->orderBy('created_at', 'desc')
                                           ->limit(10)
                                           ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'general' => [
                        'total_payments' => $totalPayments,
                        'pending_payments' => $pendingPayments,
                        'verified_payments' => $verifiedPayments,
                        'rejected_payments' => $rejectedPayments,
                        'total_amount' => $totalAmount,
                        'verified_amount' => $verifiedAmount,
                        'pending_amount' => $pendingAmount,
                        'rejected_amount' => $rejectedAmount,
                        'verification_rate' => $totalPayments > 0 ? round(($verifiedPayments / $totalPayments) * 100, 2) : 0,
                        'rejection_rate' => $totalPayments > 0 ? round(($rejectedPayments / $totalPayments) * 100, 2) : 0,
                        'avg_verification_time_hours' => round($avgVerificationTime, 1)
                    ],
                    'payment_methods' => $paymentMethodStats,
                    'status_distribution' => $statusStats,
                    'monthly_trends' => $monthlyStats,
                    'branch_distribution' => $branchStats,
                    'recent_pending' => $recentPendingPayments,
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas de pagos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}