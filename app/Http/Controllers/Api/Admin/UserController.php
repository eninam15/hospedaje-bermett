<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');
        
        // Filtros
        if ($request->filled('role')) {
            $query->role($request->role);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        $users = $query->orderBy('created_at', 'desc')
                      ->paginate($request->per_page ?? 15);
        
        return response()->json([
            'users' => $users->items(),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total()
            ]
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'required|string|max:20',
            'document_type' => 'required|in:ci,passport,other',
            'document_number' => 'required|string|max:50',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:500',
            'role' => 'required|exists:roles,name',
            'is_active' => 'boolean'
        ]);
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'birth_date' => $request->birth_date,
            'address' => $request->address,
            'is_active' => $request->is_active ?? true,
            'email_verified_at' => now()
        ]);
        
        $user->assignRole($request->role);
        
        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'user' => $user->load('roles')
        ], 201);
    }
    
    public function show($id)
    {
        $user = User::with(['roles', 'reservations', 'registrations'])
                   ->findOrFail($id);
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'document_type' => $user->document_type,
                'document_number' => $user->document_number,
                'birth_date' => $user->birth_date?->format('Y-m-d'),
                'address' => $user->address,
                'profile_photo' => $user->profile_photo,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at?->format('Y-m-d H:i:s'),
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'roles' => $user->roles->pluck('name'),
                'current_role' => $user->roles->first()?->name,
                'stats' => [
                    'total_reservations' => $user->reservations->count(),
                    'confirmed_reservations' => $user->reservations->where('status', 'confirmed')->count(),
                    'completed_stays' => $user->registrations->where('status', 'completed')->count(),
                ]
            ]
        ]);
    }
    
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'required|string|max:20',
            'document_type' => 'required|in:ci,passport,other',
            'document_number' => 'required|string|max:50',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string|max:500',
            'role' => 'required|exists:roles,name',
            'is_active' => 'boolean'
        ]);
        
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'document_type' => $request->document_type,
            'document_number' => $request->document_number,
            'birth_date' => $request->birth_date,
            'address' => $request->address,
            'is_active' => $request->is_active ?? true,
        ]);
        
        // Actualizar rol
        $user->syncRoles([$request->role]);
        
        return response()->json([
            'message' => 'Usuario actualizado exitosamente',
            'user' => $user->load('roles')
        ]);
    }
    
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        // Verificar que no sea el usuario actual
        if ($user->id === auth()->id()) {
            throw ValidationException::withMessages([
                'user' => ['No puedes eliminar tu propio usuario']
            ]);
        }
        
        // Verificar que no tenga reservas activas
        $activeReservations = $user->reservations()
                                  ->whereIn('status', ['confirmed', 'checked_in'])
                                  ->count();
        
        if ($activeReservations > 0) {
            throw ValidationException::withMessages([
                'user' => ['No se puede eliminar usuario con reservas activas']
            ]);
        }
        
        $user->delete();
        
        return response()->json([
            'message' => 'Usuario eliminado exitosamente'
        ]);
    }
    
    public function changePassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);
        
        $user = User::findOrFail($id);
        
        $user->update([
            'password' => Hash::make($request->password)
        ]);
        
        return response()->json([
            'message' => 'Contraseña actualizada exitosamente'
        ]);
    }
    
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        
        // Verificar que no sea el usuario actual
        if ($user->id === auth()->id()) {
            throw ValidationException::withMessages([
                'user' => ['No puedes desactivar tu propio usuario']
            ]);
        }
        
        $user->update([
            'is_active' => !$user->is_active
        ]);
        
        return response()->json([
            'message' => $user->is_active ? 'Usuario activado' : 'Usuario desactivado',
            'user' => $user->load('roles')
        ]);
    }
    
    public function getRoles()
    {
        $roles = Role::all(['id', 'name']);
        
        return response()->json([
            'roles' => $roles
        ]);
    }
    
    public function getStats()
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'customers' => User::role('customer')->count(),
            'employees' => User::role('employee')->count(),
            'admins' => User::role('admin')->count(),
            'recent_users' => User::where('created_at', '>=', now()->subDays(7))->count(),
        ];
        
        return response()->json(['stats' => $stats]);
    }
}