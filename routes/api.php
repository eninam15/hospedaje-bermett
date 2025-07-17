<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Public\BranchController;
use App\Http\Controllers\Api\Public\RoomController;
use App\Http\Controllers\Api\Public\AvailabilityController;
use App\Http\Controllers\Api\Customer\ReservationController as CustomerReservationController;
use App\Http\Controllers\Api\Customer\PaymentController as CustomerPaymentController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\Admin\ReservationController as AdminReservationController;
use App\Http\Controllers\Api\Admin\RegistrationController as AdminRegistrationController;
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Api\Admin\ServiceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('debug-auth', function (Request $request) {
    return response()->json([
        'message' => 'Authenticated successfully',
        'user' => $request->user(),
        'user_id' => $request->user()->id,
        'user_name' => $request->user()->name,
    ]);
});

// Agrega esta ruta temporal fuera de cualquier middleware
Route::get('debug-sanctum', [CustomerReservationController::class, 'debugSanctum']);

// Rutas de autenticación
Route::prefix('auth')->group(function () {
    // Rutas públicas de autenticación
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

// Rutas de cliente (requieren autenticación)
Route::prefix('customer')->middleware('auth:sanctum')->group(function () {
    Route::get('reservations', [CustomerReservationController::class, 'index']);
    Route::post('reservations', [CustomerReservationController::class, 'store']);
    Route::get('reservations/{id}', [CustomerReservationController::class, 'show']);
    Route::put('reservations/{id}/cancel', [CustomerReservationController::class, 'cancel']);
    
    Route::post('payments/upload-proof', [CustomerPaymentController::class, 'uploadProof']);
    Route::get('payments/reservation/{id}', [CustomerPaymentController::class, 'getPaymentsByReservation']);
});

// Rutas de administrador (requieren autenticación + rol admin)
// Solución temporal: Remover el middleware role y verificar manualmente

// Usar el middleware role de Spatie que ya configuramos

Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Dashboard
    Route::get('dashboard/stats', [DashboardController::class, 'getStats']);
    
    // Gestión de usuarios
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::put('users/{id}', [UserController::class, 'update']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);
    Route::put('users/{id}/change-password', [UserController::class, 'changePassword']);
    Route::put('users/{id}/toggle-status', [UserController::class, 'toggleStatus']);
    Route::get('users/stats/summary', [UserController::class, 'getStats']);
    Route::get('roles', [UserController::class, 'getRoles']);
    
    // Gestión de habitaciones
    Route::get('rooms', [AdminRoomController::class, 'index']);
    Route::post('rooms', [AdminRoomController::class, 'store']);
    Route::get('rooms/{id}', [AdminRoomController::class, 'show']);
    Route::put('rooms/{id}', [AdminRoomController::class, 'update']);
    Route::delete('rooms/{id}', [AdminRoomController::class, 'destroy']);
    Route::put('rooms/{id}/toggle-status', [AdminRoomController::class, 'toggleStatus']);
    Route::get('rooms/stats/summary', [AdminRoomController::class, 'getStats']);
    Route::put('rooms/{id}/status', [AdminRoomController::class, 'updateStatus']);
    
    // Datos de apoyo para habitaciones
    Route::get('room-types', [AdminRoomController::class, 'getRoomTypes']);
    Route::get('branches', [AdminRoomController::class, 'getBranches']);

    // Gestión de servicios
    Route::get('services', [ServiceController::class, 'index']);
    Route::post('services', [ServiceController::class, 'store']);
    Route::get('services/{id}', [ServiceController::class, 'show']);
    Route::put('services/{id}', [ServiceController::class, 'update']);
    Route::delete('services/{id}', [ServiceController::class, 'destroy']);
    Route::put('services/{id}/toggle-status', [ServiceController::class, 'toggleStatus']);
    
    // Gestión de reservas
    Route::get('reservations', [AdminReservationController::class, 'index']);
    Route::get('reservations/{id}', [AdminReservationController::class, 'show']);
    Route::put('reservations/{id}', [AdminReservationController::class, 'update']);
    Route::post('reservations/{id}/check-in', [AdminReservationController::class, 'checkIn']);
    Route::post('reservations/{id}/check-out', [AdminReservationController::class, 'checkOut']);
    Route::put('reservations/{id}/cancel', [AdminReservationController::class, 'cancel']);
    Route::get('reservations/stats/summary', [AdminReservationController::class, 'getStats']);
    
    // Gestión de pagos
    Route::get('payments', [AdminPaymentController::class, 'index']);
    Route::get('payments/pending', [AdminPaymentController::class, 'pending']);
    Route::put('payments/{id}/verify', [AdminPaymentController::class, 'verify']);
    Route::put('payments/{id}/reject', [AdminPaymentController::class, 'reject']);
    Route::get('payments/stats/summary', [AdminPaymentController::class, 'getStats']);
    
    // Gestión de registros
    Route::get('registrations', [AdminRegistrationController::class, 'index']);
    Route::post('registrations/direct', [AdminRegistrationController::class, 'storeDirect']);
    Route::get('registrations/{id}', [AdminRegistrationController::class, 'show']);
    Route::put('registrations/{id}', [AdminRegistrationController::class, 'update']);
    Route::post('registrations/{id}/check-out', [AdminRegistrationController::class, 'checkOut']); // NUEVA RUTA
    Route::get('registrations/stats/summary', [AdminRegistrationController::class, 'getStats']);
    
    // Reportes
    Route::get('reports/reservations', [ReportController::class, 'reservations']);
    Route::get('reports/income', [ReportController::class, 'income']);
    Route::get('reports/occupancy', [ReportController::class, 'occupancy']);
    Route::get('reports/checkins', [ReportController::class, 'checkins']);
    Route::get('reports/users', [ReportController::class, 'users']);
    Route::get('reports/services', [ReportController::class, 'services']);
    Route::get('reports/cancellations', [ReportController::class, 'cancellations']);
    Route::get('reports/payments', [ReportController::class, 'payments']);
    Route::post('reports/export', [ReportController::class, 'export']);
});
// Rutas de empleado (requieren autenticación + rol employee)
Route::prefix('employee')->middleware(['auth:sanctum', 'role:employee'])->group(function () {
    // Gestión de reservas (limitada)
    Route::get('reservations', [AdminReservationController::class, 'index']);
    Route::get('reservations/{id}', [AdminReservationController::class, 'show']);
    Route::post('reservations/{id}/check-in', [AdminReservationController::class, 'checkIn']);
    Route::post('reservations/{id}/check-out', [AdminReservationController::class, 'checkOut']);
    
    // Gestión de registros
    Route::get('registrations', [AdminRegistrationController::class, 'index']);
    Route::post('registrations/direct', [AdminRegistrationController::class, 'storeDirect']);
    Route::get('registrations/{id}', [AdminRegistrationController::class, 'show']);
    Route::put('registrations/{id}', [AdminRegistrationController::class, 'update']);
    
    // Gestión de pagos (solo visualización)
    Route::get('payments', [AdminPaymentController::class, 'index']);
    Route::get('payments/pending', [AdminPaymentController::class, 'pending']);
});

// Rutas compartidas entre admin y employee (requieren autenticación)
Route::prefix('shared')->middleware(['auth:sanctum', 'role:admin|employee'])->group(function () {
    // Consumo de servicios
    Route::get('service-consumptions', [ServiceController::class, 'getConsumptions']);
    Route::post('service-consumptions', [ServiceController::class, 'storeConsumption']);
    Route::put('service-consumptions/{id}', [ServiceController::class, 'updateConsumption']);
    Route::delete('service-consumptions/{id}', [ServiceController::class, 'destroyConsumption']);
    
    // Datos básicos
    Route::get('branches', [BranchController::class, 'index']);
    Route::get('room-types', [RoomController::class, 'roomTypes']);
    Route::get('services', [ServiceController::class, 'index']);
});