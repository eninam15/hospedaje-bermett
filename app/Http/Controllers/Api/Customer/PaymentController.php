<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function uploadProof(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'payment_reference' => 'required|string|max:100',
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Verificar que la reserva pertenece al usuario
        $reservation = Reservation::where('id', $request->reservation_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Verificar que la reserva esté en estado pending_payment
        if ($reservation->status !== 'pending_payment') {
            return response()->json([
                'message' => 'La reserva no está en estado válido para subir comprobante'
            ], 422);
        }

        // Subir archivo
        $file = $request->file('payment_proof');
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('payments', $filename, 'public');

        // Buscar o crear el pago
        $payment = Payment::where('reservation_id', $reservation->id)->first();
        
        if (!$payment) {
            $payment = Payment::create([
                'reservation_id' => $reservation->id,
                'amount' => $reservation->total_amount,
                'payment_method' => 'qr',
                'status' => 'pending',
            ]);
        }

        // Actualizar pago
        $payment->update([
            'payment_reference' => $request->payment_reference,
            'payment_proof' => $path,
            'status' => 'pending',
        ]);

        // Actualizar estado de reserva
        $reservation->update(['status' => 'payment_submitted']);

        return response()->json([
            'message' => 'Comprobante subido exitosamente',
            'payment' => [
                'id' => $payment->id,
                'status' => $payment->status,
                'status_label' => $payment->status_label,
                'payment_proof' => $payment->payment_proof,
            ]
        ]);
    }

    public function getPaymentsByReservation(Request $request, $reservationId)
    {
        // Verificar que la reserva pertenece al usuario
        $reservation = Reservation::where('id', $reservationId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $payments = Payment::where('reservation_id', $reservationId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'formatted_amount' => $payment->formatted_amount,
                    'payment_method' => $payment->payment_method,
                    'payment_method_label' => $payment->payment_method_label,
                    'payment_reference' => $payment->payment_reference,
                    'payment_proof' => $payment->payment_proof,
                    'status' => $payment->status,
                    'status_label' => $payment->status_label,
                    'notes' => $payment->notes,
                    'verified_at' => $payment->verified_at?->format('Y-m-d H:i:s'),
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'payments' => $payments,
            'reservation' => [
                'id' => $reservation->id,
                'reservation_code' => $reservation->reservation_code,
                'total_amount' => $reservation->total_amount,
                'formatted_total' => $reservation->formatted_total,
                'status' => $reservation->status,
                'status_label' => $reservation->status_label,
                'qr_payment_info' => $reservation->branch->qr_payment_info,
            ]
        ]);
    }
}