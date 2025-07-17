<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        // Debug: Verificar autenticación
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado',
                'debug_info' => [
                    'headers' => $request->headers->all(),
                    'auth_header' => $request->header('Authorization'),
                    'bearer_token' => $request->bearerToken(),
                ]
            ], 401);
        }

        try {
            $reservations = Reservation::with(['room.roomType', 'branch', 'payments'])
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($reservation) {
                    return [
                        'id' => $reservation->id,
                        'reservation_code' => $reservation->reservation_code,
                        'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->format('Y-m-d') : null,
                        'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('Y-m-d') : null,
                        'total_nights' => $reservation->total_nights ?? 0,
                        'adults_count' => $reservation->adults_count ?? 0,
                        'children_count' => $reservation->children_count ?? 0,
                        'total_guests' => $reservation->total_guests ?? 0,
                        'needs_parking' => $reservation->needs_parking ?? false,
                        'parking_fee' => $reservation->parking_fee ?? 0,
                        'room_total' => $reservation->room_total ?? 0,
                        'total_amount' => $reservation->total_amount ?? 0,
                        'formatted_total' => $reservation->formatted_total ?? 'Bs. 0.00',
                        'status' => $reservation->status ?? 'unknown',
                        'status_label' => $reservation->status_label ?? 'Desconocido',
                        'payment_method' => $reservation->payment_method ?? 'unknown',
                        'special_requests' => $reservation->special_requests ?? '',
                        'created_at' => $reservation->created_at ? $reservation->created_at->format('Y-m-d H:i:s') : null,
                        'room' => $reservation->room ? [
                            'id' => $reservation->room->id,
                            'room_number' => $reservation->room->room_number ?? 'N/A',
                            'floor' => $reservation->room->floor ?? 0,
                            'price_per_night' => $reservation->room->price_per_night ?? 0,
                            'type' => $reservation->room->roomType ? $reservation->room->roomType->name : 'N/A',
                            'photos' => $reservation->room->photos ?? [],
                        ] : [
                            'id' => null,
                            'room_number' => 'N/A',
                            'floor' => 0,
                            'price_per_night' => 0,
                            'type' => 'N/A',
                            'photos' => [],
                        ],
                        'branch' => $reservation->branch ? [
                            'id' => $reservation->branch->id,
                            'name' => $reservation->branch->name ?? 'N/A',
                            'address' => $reservation->branch->address ?? 'N/A',
                            'phone' => $reservation->branch->phone ?? 'N/A',
                        ] : [
                            'id' => null,
                            'name' => 'N/A',
                            'address' => 'N/A',
                            'phone' => 'N/A',
                        ],
                        'payments' => $reservation->payments ? $reservation->payments->map(function($payment) {
                            return [
                                'id' => $payment->id,
                                'amount' => $payment->amount ?? 0,
                                'status' => $payment->status ?? 'unknown',
                                'status_label' => $payment->status_label ?? 'Desconocido',
                                'payment_method' => $payment->payment_method ?? 'unknown',
                                'payment_proof' => $payment->payment_proof ?? null,
                                'created_at' => $payment->created_at ? $payment->created_at->format('Y-m-d H:i:s') : null,
                            ];
                        }) : [],
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Reservas obtenidas exitosamente',
                'reservations' => $reservations,
                'count' => $reservations->count(),
                'user_info' => [
                    'id' => $user->id,
                    'name' => $user->name ?? 'N/A',
                    'email' => $user->email ?? 'N/A'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las reservas',
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        // Debug: Verificar autenticación
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado para crear reserva',
            ], 401);
        }

        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'adults_count' => 'required|integer|min:1|max:10',
            'children_count' => 'nullable|integer|min:0|max:10',
            'needs_parking' => 'boolean',
            'payment_method' => 'required|in:qr,cash',
            'special_requests' => 'nullable|string|max:500',
        ]);

        $room = Room::with(['branch', 'roomType'])->findOrFail($request->room_id);
        $checkIn = Carbon::parse($request->check_in_date);
        $checkOut = Carbon::parse($request->check_out_date);
        $totalNights = $checkIn->diffInDays($checkOut);

        // Verificar disponibilidad
        $isOccupied = Reservation::where('room_id', $request->room_id)
            ->whereIn('status', ['confirmed', 'checked_in', 'payment_submitted'])
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
            ->exists();

        if ($isOccupied) {
            return response()->json([
                'success' => false,
                'message' => 'La habitación no está disponible en las fechas seleccionadas'
            ], 422);
        }

        // Calcular totales
        $roomTotal = $room->price_per_night * $totalNights;
        $parkingFee = $request->needs_parking ? 15 * $totalNights : 0; // 15 Bs por noche
        $totalAmount = $roomTotal + $parkingFee;

        // Crear reserva - CORREGIDO: usar el usuario autenticado
        $reservation = new Reservation([
            'user_id' => $user->id, // CORREGIDO: usar usuario autenticado
            'branch_id' => $room->branch_id,
            'room_id' => $request->room_id,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'total_nights' => $totalNights,
            'adults_count' => $request->adults_count,
            'children_count' => $request->children_count ?? 0,
            'needs_parking' => $request->needs_parking ?? false,
            'parking_fee' => $parkingFee,
            'room_total' => $roomTotal,
            'total_amount' => $totalAmount,
            'payment_method' => $request->payment_method,
            'special_requests' => $request->special_requests,
            'status' => 'pending_payment',
        ]);

        // Generar código antes de guardar
        $reservation->generateCode();
        $reservation->save();

        // Crear registro de pago
        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'amount' => $totalAmount,
            'payment_method' => $request->payment_method,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reserva creada exitosamente',
            'reservation' => [
                'id' => $reservation->id,
                'reservation_code' => $reservation->reservation_code,
                'total_amount' => $totalAmount,
                'formatted_total' => 'Bs. ' . number_format($totalAmount, 2),
                'status' => $reservation->status,
                'payment_method' => $reservation->payment_method,
                'qr_payment_info' => $room->branch->qr_payment_info ?? null,
            ],
            'payment' => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'status' => $payment->status,
            ]
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado',
            ], 401);
        }

        try {
            $reservation = Reservation::with(['room.roomType', 'branch', 'payments'])
                ->where('user_id', $user->id)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Reserva obtenida exitosamente',
                'reservation' => [
                    'id' => $reservation->id,
                    'reservation_code' => $reservation->reservation_code,
                    'check_in_date' => $reservation->check_in_date ? $reservation->check_in_date->format('Y-m-d') : null,
                    'check_out_date' => $reservation->check_out_date ? $reservation->check_out_date->format('Y-m-d') : null,
                    'total_nights' => $reservation->total_nights ?? 0,
                    'adults_count' => $reservation->adults_count ?? 0,
                    'children_count' => $reservation->children_count ?? 0,
                    'total_guests' => $reservation->total_guests ?? 0,
                    'needs_parking' => $reservation->needs_parking ?? false,
                    'parking_fee' => $reservation->parking_fee ?? 0,
                    'room_total' => $reservation->room_total ?? 0,
                    'total_amount' => $reservation->total_amount ?? 0,
                    'formatted_total' => $reservation->formatted_total ?? 'Bs. 0.00',
                    'status' => $reservation->status ?? 'unknown',
                    'status_label' => $reservation->status_label ?? 'Desconocido',
                    'payment_method' => $reservation->payment_method ?? 'unknown',
                    'special_requests' => $reservation->special_requests ?? '',
                    'created_at' => $reservation->created_at ? $reservation->created_at->format('Y-m-d H:i:s') : null,
                    'room' => $reservation->room ? [
                        'id' => $reservation->room->id,
                        'room_number' => $reservation->room->room_number ?? 'N/A',
                        'floor' => $reservation->room->floor ?? 0,
                        'price_per_night' => $reservation->room->price_per_night ?? 0,
                        'type' => $reservation->room->roomType ? $reservation->room->roomType->name : 'N/A',
                        'photos' => $reservation->room->photos ?? [],
                        'description' => $reservation->room->description ?? '',
                    ] : null,
                    'branch' => $reservation->branch ? [
                        'id' => $reservation->branch->id,
                        'name' => $reservation->branch->name ?? 'N/A',
                        'address' => $reservation->branch->address ?? 'N/A',
                        'phone' => $reservation->branch->phone ?? 'N/A',
                        'qr_payment_info' => $reservation->branch->qr_payment_info ?? null,
                    ] : null,
                    'payments' => $reservation->payments ? $reservation->payments->map(function($payment) {
                        return [
                            'id' => $payment->id,
                            'amount' => $payment->amount ?? 0,
                            'status' => $payment->status ?? 'unknown',
                            'status_label' => $payment->status_label ?? 'Desconocido',
                            'payment_method' => $payment->payment_method ?? 'unknown',
                            'payment_proof' => $payment->payment_proof ?? null,
                            'notes' => $payment->notes ?? '',
                            'created_at' => $payment->created_at ? $payment->created_at->format('Y-m-d H:i:s') : null,
                        ];
                    }) : [],
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado',
            ], 401);
        }

        try {
            $reservation = Reservation::where('user_id', $user->id)
                ->findOrFail($id);

            if (in_array($reservation->status, ['completed', 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar esta reserva'
                ], 422);
            }

            $reservation->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Reserva cancelada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la reserva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Agrega este método al ReservationController

public function debugSanctum(Request $request)
{
    try {
        // 1. Verificar token del header
        $bearerToken = $request->bearerToken();
        
        if (!$bearerToken) {
            return response()->json([
                'error' => 'No bearer token found',
                'headers' => $request->headers->all()
            ]);
        }

        // 2. Buscar el token en la base de datos
        $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($bearerToken);
        
        if (!$personalAccessToken) {
            return response()->json([
                'error' => 'Token not found in database',
                'token_id' => explode('|', $bearerToken)[0] ?? 'unknown',
                'token_part' => substr($bearerToken, 0, 20) . '...'
            ]);
        }

        // 3. Verificar si el token ha expirado
        if ($personalAccessToken->expires_at && $personalAccessToken->expires_at < now()) {
            return response()->json([
                'error' => 'Token expired',
                'expires_at' => $personalAccessToken->expires_at,
                'current_time' => now()
            ]);
        }

        // 4. Obtener el usuario asociado
        $user = $personalAccessToken->tokenable;
        
        if (!$user) {
            return response()->json([
                'error' => 'User not found for this token',
                'token_info' => [
                    'id' => $personalAccessToken->id,
                    'name' => $personalAccessToken->name,
                    'tokenable_type' => $personalAccessToken->tokenable_type,
                    'tokenable_id' => $personalAccessToken->tokenable_id,
                ]
            ]);
        }

        // 5. Todo está bien
        return response()->json([
            'success' => true,
            'message' => 'Token is valid',
            'token_info' => [
                'id' => $personalAccessToken->id,
                'name' => $personalAccessToken->name,
                'abilities' => $personalAccessToken->abilities,
                'last_used_at' => $personalAccessToken->last_used_at,
                'expires_at' => $personalAccessToken->expires_at,
            ],
            'user_info' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'no_role',
            ],
            'auth_user' => $request->user() ? 'Found via request->user()' : 'NOT found via request->user()'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Exception occurred',
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ]);
    }
}
}