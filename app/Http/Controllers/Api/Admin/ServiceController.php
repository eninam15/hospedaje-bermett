<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceConsumption;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    /**
     * Display a listing of services
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Service::with('branch');

            // Filtros
            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->filled('category')) {
                $query->byCategory($request->category);
            }

            if ($request->filled('is_available')) {
                $query->where('is_available', $request->boolean('is_available'));
            }

            if ($request->filled('available_only')) {
                $query->available();
            }

            // Búsqueda
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                      ->orWhere('description', 'ilike', "%{$search}%");
                });
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $services = $query->paginate($perPage);

            // Agregar información adicional
            $services->getCollection()->transform(function ($service) {
                $service->formatted_price = $service->formatted_price;
                $service->category_label = $service->category_label;
                return $service;
            });

            return response()->json([
                'success' => true,
                'data' => $services,
                'message' => 'Servicios obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los servicios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created service
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'branch_id' => 'required|exists:branches,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'category' => 'required|in:food,parking,laundry,other',
                'price' => 'required|numeric|min:0|max:999999.99',
                'is_available' => 'boolean'
            ]);

            // Verificar que no exista un servicio con el mismo nombre en la misma sucursal
            $existingService = Service::where('branch_id', $validatedData['branch_id'])
                ->where('name', $validatedData['name'])
                ->first();

            if ($existingService) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un servicio con este nombre en la sucursal seleccionada'
                ], 422);
            }

            $service = Service::create($validatedData);
            $service->load('branch');

            return response()->json([
                'success' => true,
                'data' => $service,
                'message' => 'Servicio creado exitosamente'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el servicio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified service
     */
    public function show(int $id): JsonResponse
    {
        try {
            $service = Service::with([
                'branch', 
                'consumptions' => function($query) {
                    $query->with(['user', 'registration', 'registeredBy'])
                          ->latest()
                          ->take(10);
                }
            ])->find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Servicio no encontrado'
                ], 404);
            }

            // Estadísticas del servicio
            $stats = [
                'total_consumptions' => $service->consumptions()->count(),
                'total_revenue' => $service->consumptions()->sum('total_amount'),
                'average_consumption' => $service->consumptions()->avg('total_amount'),
                'last_consumption' => $service->consumptions()->latest()->first()?->created_at,
                'pending_payments' => $service->consumptions()->pending()->count(),
                'paid_consumptions' => $service->consumptions()->paid()->count(),
                'pending_revenue' => $service->consumptions()->pending()->sum('total_amount'),
                'paid_revenue' => $service->consumptions()->paid()->sum('total_amount'),
                'most_frequent_user' => $service->consumptions()
                    ->selectRaw('user_id, COUNT(*) as consumption_count')
                    ->with('user')
                    ->groupBy('user_id')
                    ->orderBy('consumption_count', 'desc')
                    ->first(),
            ];

            $service->formatted_price = $service->formatted_price;
            $service->category_label = $service->category_label;
            $service->stats = $stats;

            return response()->json([
                'success' => true,
                'data' => $service,
                'message' => 'Servicio obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el servicio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified service
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $service = Service::find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Servicio no encontrado'
                ], 404);
            }

            $validatedData = $request->validate([
                'branch_id' => 'sometimes|required|exists:branches,id',
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'category' => 'sometimes|required|in:food,parking,laundry,other',
                'price' => 'sometimes|required|numeric|min:0|max:999999.99',
                'is_available' => 'boolean'
            ]);

            // Verificar duplicados si se cambia el nombre o la sucursal
            if (isset($validatedData['name']) || isset($validatedData['branch_id'])) {
                $branchId = $validatedData['branch_id'] ?? $service->branch_id;
                $name = $validatedData['name'] ?? $service->name;

                $existingService = Service::where('branch_id', $branchId)
                    ->where('name', $name)
                    ->where('id', '!=', $id)
                    ->first();

                if ($existingService) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ya existe un servicio con este nombre en la sucursal seleccionada'
                    ], 422);
                }
            }

            $service->update($validatedData);
            $service->load('branch');

            return response()->json([
                'success' => true,
                'data' => $service,
                'message' => 'Servicio actualizado exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el servicio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified service
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $service = Service::find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Servicio no encontrado'
                ], 404);
            }

            // Verificar si el servicio tiene consumos
            if ($service->consumptions()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el servicio porque tiene consumos asociados'
                ], 422);
            }

            $serviceName = $service->name;
            $service->delete();

            return response()->json([
                'success' => true,
                'message' => "Servicio '{$serviceName}' eliminado exitosamente"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el servicio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle service availability status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $service = Service::find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Servicio no encontrado'
                ], 404);
            }

            $service->is_available = !$service->is_available;
            $service->save();

            $status = $service->is_available ? 'disponible' : 'no disponible';

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $service->id,
                    'is_available' => $service->is_available,
                    'status' => $status
                ],
                'message' => "El servicio ahora está {$status}"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del servicio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total_services' => Service::count(),
                'available_services' => Service::available()->count(),
                'unavailable_services' => Service::where('is_available', false)->count(),
                'services_by_category' => Service::selectRaw('category, COUNT(*) as count')
                    ->groupBy('category')
                    ->get()
                    ->mapWithKeys(function ($item) {
                        $service = new Service();
                        $service->category = $item->category;
                        return [$item->category => [
                            'count' => $item->count,
                            'label' => $service->category_label
                        ]];
                    }),
                'services_by_branch' => Service::selectRaw('
                    branches.name as branch_name,
                    COUNT(services.id) as total_services,
                    COUNT(CASE WHEN services.is_available = true THEN 1 END) as available_services,
                    AVG(services.price) as avg_price
                ')
                ->join('branches', 'services.branch_id', '=', 'branches.id')
                ->groupBy('branches.id', 'branches.name')
                ->get(),
                'price_stats' => [
                    'average_price' => Service::avg('price'),
                    'min_price' => Service::min('price'),
                    'max_price' => Service::max('price'),
                    'total_services_value' => Service::sum('price'),
                ],
                'consumption_stats' => [
                    'total_revenue' => ServiceConsumption::sum('total_amount'),
                    'pending_revenue' => ServiceConsumption::pending()->sum('total_amount'),
                    'paid_revenue' => ServiceConsumption::paid()->sum('total_amount'),
                    'total_consumptions' => ServiceConsumption::count(),
                    'pending_consumptions' => ServiceConsumption::pending()->count(),
                    'paid_consumptions' => ServiceConsumption::paid()->count(),
                ],
                'top_services' => Service::selectRaw('
                    services.id,
                    services.name,
                    services.category,
                    services.price,
                    COUNT(service_consumptions.id) as total_consumptions,
                    SUM(service_consumptions.total_amount) as total_revenue
                ')
                ->leftJoin('service_consumptions', 'services.id', '=', 'service_consumptions.service_id')
                ->groupBy('services.id', 'services.name', 'services.category', 'services.price')
                ->orderBy('total_revenue', 'desc')
                ->take(10)
                ->get(),
                'recent_activity' => ServiceConsumption::with(['service', 'user'])
                    ->latest()
                    ->take(5)
                    ->get()
                    ->map(function ($consumption) {
                        return [
                            'id' => $consumption->id,
                            'service_name' => $consumption->service->name,
                            'user_name' => $consumption->user->name ?? 'N/A',
                            'total_amount' => $consumption->total_amount,
                            'formatted_total' => $consumption->formatted_total,
                            'status' => $consumption->status,
                            'status_label' => $consumption->status_label,
                            'consumption_date' => $consumption->consumption_date,
                        ];
                    }),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get services by branch
     */
    public function getByBranch(int $branchId): JsonResponse
    {
        try {
            $services = Service::with('branch')
                ->where('branch_id', $branchId)
                ->available()
                ->orderBy('name')
                ->get();

            $services->transform(function ($service) {
                $service->formatted_price = $service->formatted_price;
                $service->category_label = $service->category_label;
                return $service;
            });

            return response()->json([
                'success' => true,
                'data' => $services,
                'message' => 'Servicios obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los servicios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get services by category
     */
    public function getByCategory(string $category): JsonResponse
    {
        try {
            $validCategories = ['food', 'parking', 'laundry', 'other'];
            
            if (!in_array($category, $validCategories)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no válida'
                ], 400);
            }

            $services = Service::with('branch')
                ->byCategory($category)
                ->available()
                ->orderBy('name')
                ->get();

            $services->transform(function ($service) {
                $service->formatted_price = $service->formatted_price;
                $service->category_label = $service->category_label;
                return $service;
            });

            return response()->json([
                'success' => true,
                'data' => $services,
                'message' => 'Servicios obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los servicios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service consumption history
     */
    public function getConsumptionHistory(int $id, Request $request): JsonResponse
    {
        try {
            $service = Service::find($id);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Servicio no encontrado'
                ], 404);
            }

            $query = $service->consumptions()
                ->with(['user', 'registration', 'registeredBy']);

            // Filtros
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('consumption_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('consumption_date', '<=', $request->date_to);
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'consumption_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $request->get('per_page', 15);
            $consumptions = $query->paginate($perPage);

            // Agregar información formateada
            $consumptions->getCollection()->transform(function ($consumption) {
                $consumption->formatted_total = $consumption->formatted_total;
                $consumption->formatted_unit_price = $consumption->formatted_unit_price;
                $consumption->status_label = $consumption->status_label;
                return $consumption;
            });

            return response()->json([
                'success' => true,
                'data' => $consumptions,
                'message' => 'Historial de consumo obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial: ' . $e->getMessage()
            ], 500);
        }
    }
}
