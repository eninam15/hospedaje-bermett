<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Public\BranchController;
use App\Http\Controllers\Api\Public\RoomController;
use App\Http\Controllers\Api\Public\AvailabilityController;
use App\Http\Controllers\Api\Customer\ReservationController as CustomerReservationController;
use App\Http\Controllers\Api\Customer\PaymentController as CustomerPaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas de autenticación (sin middleware)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    // Rutas protegidas de autenticación
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::put('change-password', [AuthController::class, 'changePassword']);
    });
});

// Rutas públicas (sin autenticación)
Route::prefix('public')->group(function () {
    Route::get('branches', [BranchController::class, 'index']);
    Route::get('branches/{id}', [BranchController::class, 'show']);
    Route::get('rooms', [RoomController::class, 'index']);
    Route::get('rooms/{id}', [RoomController::class, 'show']);
    Route::get('room-types', [RoomController::class, 'roomTypes']);
    Route::post('rooms/check-availability', [AvailabilityController::class, 'checkAvailability']);
});

// Rutas de cliente (requiere autenticación)
Route::middleware('auth:sanctum')->prefix('customer')->group(function () {
    Route::get('reservations', [CustomerReservationController::class, 'index']);
    Route::post('reservations', [CustomerReservationController::class, 'store']);
    Route::get('reservations/{id}', [CustomerReservationController::class, 'show']);
    Route::put('reservations/{id}/cancel', [CustomerReservationController::class, 'cancel']);
    
    Route::post('payments/upload-proof', [CustomerPaymentController::class, 'uploadProof']);
    Route::get('payments/reservation/{id}', [CustomerPaymentController::class, 'getPaymentsByReservation']);
});

// Rutas de administrador (requiere autenticación y rol admin)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('dashboard/stats', function () {
        return response()->json(['message' => 'Dashboard stats - Coming soon']);
    });
    
    // Usuarios
    Route::get('users', function () {
        return response()->json(['message' => 'Users management - Coming soon']);
    });
    
    // Reservas
    Route::get('reservations', function () {
        return response()->json(['message' => 'Admin reservations - Coming soon']);
    });
    
    // Pagos
    Route::get('payments/pending', function () {
        return response()->json(['message' => 'Pending payments - Coming soon']);
    });
    
    // Reportes
    Route::get('reports/{type}', function ($type) {
        return response()->json(['message' => "Report $type - Coming soon"]);
    });
});

// Rutas de empleado (requiere autenticación y rol employee)
Route::middleware(['auth:sanctum', 'role:employee'])->prefix('employee')->group(function () {
    Route::get('reservations', function () {
        return response()->json(['message' => 'Employee reservations - Coming soon']);
    });
});