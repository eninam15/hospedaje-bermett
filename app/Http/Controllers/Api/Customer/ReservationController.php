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
        $reservations = Reservation::with(['room.roomType', 'branch', 'payments'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($reservation) {
                return [
                    'id' => $reservation->id,
                    'reservation_code' => $reservation->reservation_code,
                    'check_in_date' => $reservation->check_in_date->format('Y-m-d'),
                    'check_out_date' => $reservation->check_out_date->format('Y-m-d'),
                    'total_nights' => $reservation->total_nights,
                    'adults_count' => $reservation->adults_count,
                    'children_count' => $reservation->children_count,
                    'total_guests' => $reservation->total_guests,
                    'needs_parking' => $reservation->needs_parking,
                    'parking_fee' => $reservation->parking_fee,
                    'room_total' => $reservation->room_total,
                    'total_amount' => $reservation->total_amount,
                    'formatted_total' => $reservation->formatted_total,
                    'status' => $reservation->status,
                    'status_label' => $reservation->status_label,
                    'payment_method' => $reservation->payment_method,
                    'special_requests' => $reservation->special_requests,
                    'created_at' => $reservation->created_at->format('Y-m-d H:i:s'),
                    'room' => [
                        'id' => $reservation->room->id,
                        'room_number' => $reservation->room->room_number,
                        'floor' => $reservation->room->floor,
                        'price_per_night' => $reservation->room->price_per_night,
                        'type' => $reservation->room->roomType->name,
                        'photos' => $reservation->room->photos,
                    ],
                    'branch' => [
                        'id' => $reservation->branch->id,
                        'name' => $reservation->branch->name,
                        'address' => $reservation->branch->address,
                        'phone' => $reservation->branch->phone,
                    ],
                    'payments' => $reservation->payments->map(function($payment) {
                        return [
                            'id' => $payment->id,
                            'amount' => $payment->amount,
                            'status' => $payment->status,
                            'status_label' => $payment->status_label,
                            'payment_method' => $payment->payment_method,
                            'payment_proof' => $payment->payment_proof,
                            'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                ];
            });

        return response()->json([
            'reservations' => $reservations
        ]);
    }

    public function store(Request $request)
    {
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
                'message' => 'La habitación no está disponible en las fechas seleccionadas'
            ], 422);
        }

        // Calcular totales
        $roomTotal = $room->price_per_night * $totalNights;
        $parkingFee = $request->needs_parking ? 15 * $totalNights : 0; // 15 Bs por noche
        $totalAmount = $roomTotal + $parkingFee;

        // Crear reserva
        // Crear instancia sin guardar
        $reservation = new Reservation([
            'user_id' => 1,
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
            'status' => $request->payment_method === 'cash' ? 'pending_payment' : 'pending_payment',
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
            'message' => 'Reserva creada exitosamente',
            'reservation' => [
                'id' => $reservation->id,
                'reservation_code' => $reservation->reservation_code,
                'total_amount' => $totalAmount,
                'formatted_total' => 'Bs. ' . number_format($totalAmount, 2),
                'status' => $reservation->status,
                'payment_method' => $reservation->payment_method,
                'qr_payment_info' => $room->branch->qr_payment_info,
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
        $reservation = Reservation::with(['room.roomType', 'branch', 'payments'])
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'reservation' => [
                'id' => $reservation->id,
                'reservation_code' => $reservation->reservation_code,
                'check_in_date' => $reservation->check_in_date->format('Y-m-d'),
                'check_out_date' => $reservation->check_out_date->format('Y-m-d'),
                'total_nights' => $reservation->total_nights,
                'adults_count' => $reservation->adults_count,
                'children_count' => $reservation->children_count,
                'total_guests' => $reservation->total_guests,
                'needs_parking' => $reservation->needs_parking,
                'parking_fee' => $reservation->parking_fee,
                'room_total' => $reservation->room_total,
                'total_amount' => $reservation->total_amount,
                'formatted_total' => $reservation->formatted_total,
                'status' => $reservation->status,
                'status_label' => $reservation->status_label,
                'payment_method' => $reservation->payment_method,
                'special_requests' => $reservation->special_requests,
                'created_at' => $reservation->created_at->format('Y-m-d H:i:s'),
                'room' => [
                    'id' => $reservation->room->id,
                    'room_number' => $reservation->room->room_number,
                    'floor' => $reservation->room->floor,
                    'price_per_night' => $reservation->room->price_per_night,
                    'type' => $reservation->room->roomType->name,
                    'photos' => $reservation->room->photos,
                    'description' => $reservation->room->description,
                ],
                'branch' => [
                    'id' => $reservation->branch->id,
                    'name' => $reservation->branch->name,
                    'address' => $reservation->branch->address,
                    'phone' => $reservation->branch->phone,
                    'qr_payment_info' => $reservation->branch->qr_payment_info,
                ],
                'payments' => $reservation->payments->map(function($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'status' => $payment->status,
                        'status_label' => $payment->status_label,
                        'payment_method' => $payment->payment_method,
                        'payment_proof' => $payment->payment_proof,
                        'notes' => $payment->notes,
                        'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
            ]
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $reservation = Reservation::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if (in_array($reservation->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'No se puede cancelar esta reserva'
            ], 422);
        }

        $reservation->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'Reserva cancelada exitosamente'
        ]);
    }
}