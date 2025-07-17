<template>
  <div class="admin-rooms py-4">
    <div class="container-xl">
      <!-- Header Section -->
      <div class="page-header mb-4">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h2 class="mb-2">
              <i class="fas fa-bed me-2"></i>
              Gestión de Habitaciones
            </h2>
            <p class="text-muted mb-0">Administra habitaciones, precios y disponibilidad</p>
          </div>
          <div class="col-md-4 text-md-end">
            <button @click="showCreateModal" class="btn btn-primary">
              <i class="fas fa-plus me-2"></i>
              Nueva Habitación
            </button>
          </div>
        </div>
      </div>

      <!-- Debug Info -->
      <div v-if="debugMode" class="alert alert-info mb-3">
        <strong>Debug Info:</strong><br>
        API Base URL: {{ apiBaseUrl }}<br>
        Rooms Count: {{ rooms.length }}<br>
        Branches Count: {{ branches.length }}<br>
        Room Types Count: {{ roomTypes.length }}<br>
        Last Request: {{ lastRequest }}<br>
        Last Error: {{ lastError }}
      </div>

      <!-- Stats Cards -->
      <div class="stats-section mb-4">
        <div class="row g-3">
          <div class="col-lg-3 col-md-6">
            <div class="stat-card">
              <div class="stat-icon bg-primary">
                <i class="fas fa-bed text-white"></i>
              </div>
              <div class="stat-content">
                <h3 class="stat-number">{{ stats.total_rooms || 0 }}</h3>
                <p class="stat-label">Total Habitaciones</p>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6">
            <div class="stat-card">
              <div class="stat-icon bg-success">
                <i class="fas fa-door-open text-white"></i>
              </div>
              <div class="stat-content">
                <h3 class="stat-number">{{ stats.available_rooms || 0 }}</h3>
                <p class="stat-label">Disponibles</p>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6">
            <div class="stat-card">
              <div class="stat-icon bg-danger">
                <i class="fas fa-door-closed text-white"></i>
              </div>
              <div class="stat-content">
                <h3 class="stat-number">{{ stats.occupied_rooms || 0 }}</h3>
                <p class="stat-label">Ocupadas</p>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6">
            <div class="stat-card">
              <div class="stat-icon bg-warning">
                <i class="fas fa-chart-pie text-white"></i>
              </div>
              <div class="stat-content">
                <h3 class="stat-number">{{ stats.occupancy_rate || 0 }}%</h3>
                <p class="stat-label">Ocupación</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters and Search -->
      <div class="filters-section mb-4">
        <div class="card">
          <div class="card-body">
            <div class="row g-3 align-items-end">
              <div class="col-md-2">
                <label class="form-label">Sucursal</label>
                <select v-model="filters.branch_id" class="form-select" @change="loadRooms">
                  <option value="">Todas</option>
                  <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                    {{ branch.name }}
                  </option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label">Tipo</label>
                <select v-model="filters.room_type_id" class="form-select" @change="loadRooms">
                  <option value="">Todos</option>
                  <option v-for="roomType in roomTypes" :key="roomType.id" :value="roomType.id">
                    {{ roomType.name }}
                  </option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select v-model="filters.status" class="form-select" @change="loadRooms">
                  <option value="">Todos</option>
                  <option value="available">Disponible</option>
                  <option value="occupied">Ocupada</option>
                  <option value="maintenance">Mantenimiento</option>
                  <option value="cleaning">Limpieza</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Buscar</label>
                <div class="input-group">
                  <span class="input-group-text">
                    <i class="fas fa-search"></i>
                  </span>
                  <input
                    v-model="filters.search"
                    type="text"
                    class="form-control"
                    placeholder="Número o descripción..."
                    @keyup.enter="loadRooms"
                  />
                </div>
              </div>
              <div class="col-md-2">
                <button @click="clearFilters" class="btn btn-outline-secondary w-100">
                  <i class="fas fa-redo me-1"></i>
                  Limpiar
                </button>
              </div>
            </div>
            <div class="row mt-2">
              <div class="col-12">
                <button class="btn btn-sm btn-outline-info me-2" @click="toggleDebug">
                  {{ debugMode ? 'Ocultar Debug' : 'Mostrar Debug' }}
                </button>
                <button class="btn btn-sm btn-outline-warning me-2" @click="testConnection">
                  Probar Conexión
                </button>
                <button class="btn btn-sm btn-outline-success" @click="forceReload">
                  Recargar Todo
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- View Toggle -->
      <div class="view-controls mb-3">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="btn-group btn-group-sm">
              <button
                @click="viewMode = 'grid'"
                class="btn"
                :class="viewMode === 'grid' ? 'btn-primary' : 'btn-outline-primary'"
              >
                <i class="fas fa-th me-1"></i>
                Vista Grid
              </button>
              <button
                @click="viewMode = 'table'"
                class="btn"
                :class="viewMode === 'table' ? 'btn-primary' : 'btn-outline-primary'"
              >
                <i class="fas fa-table me-1"></i>
                Vista Tabla
              </button>
            </div>
          </div>
          <div class="col-md-6 text-md-end">
            <button
              @click="forceReload"
              class="btn btn-outline-info btn-sm"
              :disabled="loading"
            >
              <i class="fas fa-sync-alt me-1" :class="{ 'fa-spin': loading }"></i>
              Actualizar
            </button>
          </div>
        </div>
      </div>

      <!-- Rooms Content -->
      <div class="rooms-section">
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="text-muted mt-3">Cargando habitaciones...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="rooms.length === 0" class="text-center py-5">
          <i class="fas fa-home display-4 text-muted mb-3"></i>
          <h5 class="text-muted">No se encontraron habitaciones</h5>
          <p class="text-muted">{{ lastError || 'No hay habitaciones que coincidan con los filtros seleccionados.' }}</p>
          <button @click="showCreateModal" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>
            Crear Primera Habitación
          </button>
        </div>

        <!-- Grid View -->
        <div v-else-if="viewMode === 'grid'" class="rooms-grid">
          <div class="row g-3">
            <div v-for="room in paginatedRooms" :key="room.id" class="col-xl-3 col-lg-4 col-md-6">
              <div class="room-card">
                <div class="card h-100">
                  <div class="room-image">
                    <img 
                      :src="getRoomImage(room)" 
                      :alt="'Habitación ' + room.room_number" 
                      class="card-img-top" 
                    />
                    <div class="room-status-overlay">
                      <span :class="`badge bg-${getStatusColor(room.status)}`">
                        {{ getStatusLabel(room.status) }}
                      </span>
                    </div>
                    <div class="room-number-overlay">
                      {{ room.room_number }}
                    </div>
                    <div v-if="!room.is_active" class="room-inactive-overlay">
                      <span class="badge bg-secondary">Inactiva</span>
                    </div>
                  </div>
                  <div class="card-body">
                    <div class="room-header mb-2">
                      <h6 class="room-name">Habitación {{ room.room_number }}</h6>
                      <small class="text-muted">Piso {{ room.floor }}</small>
                    </div>

                    <div class="room-details mb-3">
                      <div class="room-type mb-1">
                        <i class="fas fa-door-open me-1"></i>
                        {{ room.room_type?.name || 'N/A' }}
                      </div>
                      <div class="room-capacity mb-1">
                        <i class="fas fa-users me-1"></i>
                        {{ room.room_type?.max_adults || 0 }} adultos
                        {{ room.room_type?.max_children ? ', ' + room.room_type.max_children + ' niños' : '' }}
                      </div>
                      <div class="room-branch">
                        <i class="fas fa-building me-1"></i>
                        {{ room.branch?.name || 'N/A' }}
                      </div>
                    </div>

                    <div class="room-amenities mb-3">
                      <div class="amenities-list">
                        <span
                          v-for="amenity in room.amenities?.slice(0, 3)"
                          :key="amenity"
                          class="badge bg-light text-dark me-1 mb-1"
                        >
                          {{ amenity }}
                        </span>
                        <span v-if="room.amenities?.length > 3" class="badge bg-secondary">
                          +{{ room.amenities.length - 3 }} más
                        </span>
                      </div>
                    </div>

                    <div class="room-price mb-3">
                      <div class="price-display">
                        <span class="price-amount">Bs/ {{ Number(room.price_per_night || 0).toFixed(2) }}</span>
                        <small class="price-period">por noche</small>
                      </div>
                    </div>

                    <div class="room-actions">
                      <div class="dropdown w-100">
                        <button
                          class="btn btn-outline-primary btn-sm dropdown-toggle w-100"
                          type="button"
                          data-bs-toggle="dropdown"
                        >
                          Acciones
                        </button>
                        <ul class="dropdown-menu">
                          <li>
                            <button @click="viewRoom(room)" class="dropdown-item">
                              <i class="fas fa-eye me-2"></i>Ver Detalles
                            </button>
                          </li>
                          <li>
                            <button @click="editRoom(room)" class="dropdown-item">
                              <i class="fas fa-edit me-2"></i>Editar
                            </button>
                          </li>
                          <li>
                            <button @click="toggleStatus(room)" class="dropdown-item">
                              <i class="fas fa-toggle-on me-2"></i>
                              {{ room.is_active ? 'Desactivar' : 'Activar' }}
                            </button>
                          </li>
                          <li><hr class="dropdown-divider" /></li>
                          <li>
                            <button @click="deleteRoom(room)" class="dropdown-item text-danger">
                              <i class="fas fa-trash me-2"></i>Eliminar
                            </button>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Table View -->
        <div v-else-if="viewMode === 'table'" class="rooms-table">
          <div class="card">
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th @click="sortBy('room_number')" class="sortable">
                        Número
                        <i class="fas fa-sort ms-1" :class="getSortIcon('room_number')"></i>
                      </th>
                      <th>Tipo</th>
                      <th>Sucursal</th>
                      <th>Piso</th>
                      <th @click="sortBy('price_per_night')" class="sortable">
                        Precio
                        <i class="fas fa-sort ms-1" :class="getSortIcon('price_per_night')"></i>
                      </th>
                      <th @click="sortBy('status')" class="sortable">
                        Estado
                        <i class="fas fa-sort ms-1" :class="getSortIcon('status')"></i>
                      </th>
                      <th>Activo</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="room in paginatedRooms" :key="room.id">
                      <td>
                        <div class="room-number-cell">
                          <strong>{{ room.room_number }}</strong>
                          <small class="text-muted d-block">Piso {{ room.floor }}</small>
                        </div>
                      </td>
                      <td>
                        <div class="room-info-cell">
                          <div class="room-type">{{ room.room_type?.name || 'N/A' }}</div>
                          <small class="text-muted">
                            {{ room.room_type?.max_adults || 0 }} adultos
                            {{ room.room_type?.max_children ? ', ' + room.room_type.max_children + ' niños' : '' }}
                          </small>
                        </div>
                      </td>
                      <td>
                        <span class="room-branch-badge">
                          {{ room.branch?.name || 'N/A' }}
                        </span>
                      </td>
                      <td>{{ room.floor }}</td>
                      <td>
                        <div class="price-cell">
                          <strong>Bs/ {{ Number(room.price_per_night || 0).toFixed(2) }}</strong>
                          <small class="text-muted d-block">por noche</small>
                        </div>
                      </td>
                      <td>
                        <span :class="`badge bg-${getStatusColor(room.status)}`">
                          {{ getStatusLabel(room.status) }}
                        </span>
                      </td>
                      <td>
                        <span :class="room.is_active ? 'badge bg-success' : 'badge bg-danger'">
                          {{ room.is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                      </td>
                      <td>
                        <div class="dropdown">
                          <button
                            class="btn btn-outline-secondary btn-sm dropdown-toggle"
                            type="button"
                            data-bs-toggle="dropdown"
                          >
                            <i class="fas fa-ellipsis-v"></i>
                          </button>
                          <ul class="dropdown-menu">
                            <li>
                              <button @click="viewRoom(room)" class="dropdown-item">
                                <i class="fas fa-eye me-2"></i>Ver Detalles
                              </button>
                            </li>
                            <li>
                              <button @click="editRoom(room)" class="dropdown-item">
                                <i class="fas fa-edit me-2"></i>Editar
                              </button>
                            </li>
                            <li>
                              <button @click="toggleStatus(room)" class="dropdown-item">
                                <i class="fas fa-toggle-on me-2"></i>
                                {{ room.is_active ? 'Desactivar' : 'Activar' }}
                              </button>
                            </li>
                            <li><hr class="dropdown-divider" /></li>
                            <li>
                              <button @click="deleteRoom(room)" class="dropdown-item text-danger">
                                <i class="fas fa-trash me-2"></i>Eliminar
                              </button>
                            </li>
                          </ul>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="pagination-section mt-4">
          <nav>
            <ul class="pagination justify-content-center">
              <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                <button @click="changePage(pagination.current_page - 1)" class="page-link">
                  <i class="fas fa-chevron-left"></i>
                </button>
              </li>
              <li
                v-for="page in getVisiblePages()"
                :key="page"
                class="page-item"
                :class="{ active: page === pagination.current_page }"
              >
                <button @click="changePage(page)" class="page-link">{{ page }}</button>
              </li>
              <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                <button @click="changePage(pagination.current_page + 1)" class="page-link">
                  <i class="fas fa-chevron-right"></i>
                </button>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Create/Edit Room Modal -->
    <div
      v-if="modalVisible"
      class="modal fade show"
      style="display: block"
      tabindex="-1"
    >
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              {{ editingRoom ? 'Editar Habitación' : 'Crear Nueva Habitación' }}
            </h5>
            <button @click="closeModal" type="button" class="btn-close"></button>
          </div>
          <form @submit.prevent="saveRoom">
            <div class="modal-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Sucursal *</label>
                  <select v-model="form.branch_id" class="form-select" required>
                    <option value="">Seleccionar sucursal</option>
                    <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                      {{ branch.name }}
                    </option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Tipo de Habitación *</label>
                  <select v-model="form.room_type_id" class="form-select" required>
                    <option value="">Seleccionar tipo</option>
                    <option v-for="roomType in roomTypes" :key="roomType.id" :value="roomType.id">
                      {{ roomType.name }}
                    </option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Número de Habitación *</label>
                  <input v-model="form.room_number" type="text" class="form-control" required maxlength="10" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Piso *</label>
                  <input v-model="form.floor" type="number" class="form-control" required min="1" max="20" />
                </div>
                <div class="col-md-4">
                  <label class="form-label">Precio por Noche *</label>
                  <input v-model="form.price_per_night" type="number" class="form-control" required min="0" step="0.01" />
                </div>
                <div class="col-md-6">
                  <label class="form-label">Estado *</label>
                  <select v-model="form.status" class="form-select" required>
                    <option value="available">Disponible</option>
                    <option value="occupied">Ocupada</option>
                    <option value="maintenance">Mantenimiento</option>
                    <option value="cleaning">Limpieza</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <div class="form-check mt-4">
                    <input v-model="form.is_active" class="form-check-input" type="checkbox" id="is_active" />
                    <label class="form-check-label" for="is_active">
                      Habitación activa
                    </label>
                  </div>
                </div>
                <div class="col-12">
                  <label class="form-label">Descripción</label>
                  <textarea v-model="form.description" class="form-control" rows="3" maxlength="1000"></textarea>
                </div>
                <div class="col-12">
                  <label class="form-label">Amenidades</label>
                  <div class="amenities-checkboxes">
                    <div class="row g-2">
                      <div v-for="amenity in availableAmenities" :key="amenity" class="col-md-4">
                        <div class="form-check">
                          <input
                            v-model="form.amenities"
                            :value="amenity"
                            type="checkbox"
                            class="form-check-input"
                            :id="`amenity-${amenity}`"
                          />
                          <label :for="`amenity-${amenity}`" class="form-check-label">
                            {{ amenity }}
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-12">
                  <label class="form-label">Fotos</label>
                  <input 
                    ref="photoInput"
                    type="file" 
                    class="form-control" 
                    multiple
                    accept="image/*"
                    @change="handlePhotos"
                  />
                  <small class="form-text text-muted">
                    Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB por imagen.
                  </small>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button @click="closeModal" type="button" class="btn btn-secondary">Cancelar</button>
              <button type="submit" class="btn btn-primary" :disabled="saving">
                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                {{ editingRoom ? 'Actualizar' : 'Crear' }} Habitación
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Room Detail Modal -->
    <div v-if="showDetailModal" class="modal fade show" style="display: block" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Detalles de la Habitación {{ selectedRoom?.room_number }}</h5>
            <button @click="showDetailModal = false" type="button" class="btn-close"></button>
          </div>
          <div class="modal-body">
            <div v-if="selectedRoom" class="room-detail">
              <div class="row">
                <div class="col-md-6">
                  <div class="room-detail-image">
                    <img
                      :src="getRoomImage(selectedRoom)"
                      :alt="'Habitación ' + selectedRoom.room_number"
                      class="img-fluid rounded"
                    />
                  </div>
                </div>
                <div class="col-md-6">
                  <h5>Habitación {{ selectedRoom.room_number }}</h5>
                  <p class="text-muted">{{ selectedRoom.description || 'Sin descripción disponible' }}</p>

                  <table class="table table-sm">
                    <tr>
                      <td><strong>Número:</strong></td>
                      <td>{{ selectedRoom.room_number }}</td>
                    </tr>
                    <tr>
                      <td><strong>Tipo:</strong></td>
                      <td>{{ selectedRoom.room_type?.name || 'N/A' }}</td>
                    </tr>
                    <tr>
                      <td><strong>Sucursal:</strong></td>
                      <td>{{ selectedRoom.branch?.name || 'N/A' }}</td>
                    </tr>
                    <tr>
                      <td><strong>Piso:</strong></td>
                      <td>{{ selectedRoom.floor }}</td>
                    </tr>
                    <tr>
                      <td><strong>Capacidad:</strong></td>
                      <td>
                        {{ selectedRoom.room_type?.max_adults || 0 }} adultos
                        {{ selectedRoom.room_type?.max_children ? ', ' + selectedRoom.room_type.max_children + ' niños' : '' }}
                      </td>
                    </tr>
                    <tr>
                      <td><strong>Precio:</strong></td>
                      <td>Bs/ {{ Number(selectedRoom.price_per_night || 0).toFixed(2) }} por noche</td>
                    </tr>
                    <tr>
                      <td><strong>Estado:</strong></td>
                      <td>
                        <span :class="`badge bg-${getStatusColor(selectedRoom.status)}`">
                          {{ getStatusLabel(selectedRoom.status) }}
                        </span>
                      </td>
                    </tr>
                    <tr>
                      <td><strong>Activo:</strong></td>
                      <td>
                        <span :class="selectedRoom.is_active ? 'badge bg-success' : 'badge bg-danger'">
                          {{ selectedRoom.is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                      </td>
                    </tr>
                  </table>

                  <h6 class="mt-3">Amenidades</h6>
                  <div class="amenities-list">
                    <span
                      v-for="amenity in selectedRoom.amenities"
                      :key="amenity"
                      class="badge bg-light text-dark me-2 mb-1"
                    >
                      {{ amenity }}
                    </span>
                    <span v-if="!selectedRoom.amenities || selectedRoom.amenities.length === 0" class="text-muted">
                      Sin amenidades especificadas
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button @click="editRoom(selectedRoom)" class="btn btn-warning">
              <i class="fas fa-edit me-2"></i>
              Editar
            </button>
            <button @click="showDetailModal = false" type="button" class="btn btn-secondary">
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
// Importar los servicios API
import { adminApi } from '@/services/api' // Ajusta la ruta según tu estructura

export default {
  name: 'RoomsView',
  data() {
    return {
      // Debug
      debugMode: false,
      apiBaseUrl: '/api',
      lastRequest: '',
      lastError: '',
      
      // UI State
      modalVisible: false,
      showDetailModal: false,
      loading: false,
      saving: false,
      editingRoom: null,
      selectedRoom: null,
      viewMode: 'grid', // 'grid' o 'table'
      
      // Data
      rooms: [],
      branches: [],
      roomTypes: [],
      stats: {},
      
      // Filters & Pagination
      filters: {
        branch_id: '',
        room_type_id: '',
        status: '',
        is_active: '',
        search: ''
      },
      sorting: {
        sort_by: 'room_number',
        sort_order: 'asc'
      },
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 12,
        total: 0,
        from: 0,
        to: 0
      },
      
      // Form
      form: {
        branch_id: '',
        room_type_id: '',
        room_number: '',
        floor: 1,
        price_per_night: 0,
        status: 'available',
        description: '',
        amenities: [],
        is_active: true
      },
      availableAmenities: [
        'WiFi',
        'TV',
        'Aire Acondicionado',
        'Calefacción',
        'Minibar',
        'Caja Fuerte',
        'Balcón',
        'Vista al Mar',
        'Baño Privado',
        'Ducha',
        'Bañera',
        'Secador de Pelo'
      ]
    }
  },

  computed: {
    paginatedRooms() {
      // Para vista en grid/table, usar los rooms ya filtrados desde el backend
      return this.rooms
    },

    getVisiblePages() {
      return () => {
        const current = this.pagination.current_page
        const last = this.pagination.last_page
        const pages = []
        
        for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
          pages.push(i)
        }
        
        return pages
      }
    }
  },
  
  mounted() {
    console.log('🚀 RoomsView component mounted')
    this.loadInitialData()
  },
  
  methods: {
    toggleDebug() {
      this.debugMode = !this.debugMode
      console.log('🔍 Debug mode:', this.debugMode ? 'ON' : 'OFF')
    },

    async testConnection() {
      console.log('🔍 Testing API connection...')
      try {
        const response = await adminApi.getRooms({ test: true })
        console.log('✅ Connection test successful:', response.status)
        alert('✅ Conexión exitosa con la API')
        this.lastError = ''
      } catch (error) {
        console.error('❌ Connection test failed:', error)
        this.lastError = `Error de conexión: ${error.message}`
        alert(`❌ Error de conexión: ${error.message}`)
      }
    },

    async forceReload() {
      console.log('🔄 Force reloading all data...')
      this.rooms = []
      this.branches = []
      this.roomTypes = []
      this.stats = {}
      this.lastError = ''
      await this.loadInitialData()
    },

    clearFilters() {
      this.filters = {
        branch_id: '',
        room_type_id: '',
        status: '',
        is_active: '',
        search: ''
      }
      this.pagination.current_page = 1
      this.loadRooms()
    },

    async loadInitialData() {
      console.log('🔄 Loading initial data...')
      this.lastRequest = 'Initial data load'
      
      try {
        const promises = [
          this.loadBranches(),
          this.loadRoomTypes(),
          this.loadStats(),
          this.loadRooms()
        ]
        
        await Promise.allSettled(promises)
        console.log('✅ Initial data load completed')
      } catch (error) {
        console.error('❌ Error in initial data load:', error)
        this.lastError = error.message
      }
    },

    async loadBranches() {
      console.log('📍 Loading branches...')
      
      try {
        this.lastRequest = 'GET /admin/branches'
        const response = await adminApi.getBranches()
        
        console.log('📍 Branches response:', response.data)
        
        let branchesData = []
        if (response.data.success && response.data.data) {
          branchesData = response.data.data
        } else if (response.data.branches) {
          branchesData = response.data.branches
        } else if (Array.isArray(response.data)) {
          branchesData = response.data
        }
        
        this.branches = branchesData
        console.log('✅ Branches loaded successfully:', this.branches.length, 'items')
        
      } catch (error) {
        console.error('❌ Error loading branches:', error)
        this.lastError = `Branches: ${error.message}`
        this.branches = []
      }
    },

    async loadRoomTypes() {
      console.log('🏠 Loading room types...')
      
      try {
        this.lastRequest = 'GET /admin/room-types'
        const response = await adminApi.getRoomTypes()
        
        console.log('🏠 Room types response:', response.data)
        
        let roomTypesData = []
        if (response.data.success && response.data.data) {
          roomTypesData = response.data.data
        } else if (response.data.room_types) {
          roomTypesData = response.data.room_types
        } else if (Array.isArray(response.data)) {
          roomTypesData = response.data
        }
        
        this.roomTypes = roomTypesData
        console.log('✅ Room types loaded successfully:', this.roomTypes.length, 'items')
        
      } catch (error) {
        console.error('❌ Error loading room types:', error)
        this.lastError = `Room types: ${error.message}`
        this.roomTypes = []
      }
    },

    async loadStats() {
      console.log('📊 Loading stats...')
      
      try {
        this.lastRequest = 'GET /admin/rooms/stats/summary'
        const response = await adminApi.getRoomStats()
        
        console.log('📊 Stats response:', response.data)
        
        if (response.data.success && response.data.data) {
          this.stats = response.data.data
        } else if (response.data.stats) {
          this.stats = response.data.stats
        } else {
          this.stats = response.data
        }
        
        console.log('✅ Stats loaded successfully:', this.stats)
        
      } catch (error) {
        console.error('❌ Error loading stats:', error)
        this.lastError = `Stats: ${error.message}`
        
        // Stats por defecto
        this.stats = {
          total_rooms: 0,
          available_rooms: 0,
          occupied_rooms: 0,
          occupancy_rate: 0
        }
      }
    },

    async loadRooms(page = 1) {
      console.log(`🏨 Loading rooms (page ${page})...`)
      this.loading = true
      
      try {
        const params = {
          page,
          per_page: this.pagination.per_page,
          ...this.filters,
          ...this.sorting
        }

        // Limpiar parámetros vacíos
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null || params[key] === undefined) {
            delete params[key]
          }
        })

        this.lastRequest = `GET /admin/rooms with params: ${JSON.stringify(params)}`
        console.log('🏨 Request params:', params)

        const response = await adminApi.getRooms(params)
        console.log('🏨 Rooms response:', response.data)
        
        let roomsData = []
        let paginationData = {}
        
        if (response.data.success) {
          if (response.data.data && response.data.data.data) {
            // Respuesta paginada de Laravel
            roomsData = response.data.data.data
            paginationData = {
              current_page: response.data.data.current_page || 1,
              last_page: response.data.data.last_page || 1,
              per_page: response.data.data.per_page || 12,
              total: response.data.data.total || 0,
              from: response.data.data.from || 0,
              to: response.data.data.to || 0
            }
          } else if (response.data.data) {
            roomsData = Array.isArray(response.data.data) ? response.data.data : []
          }
        } else if (response.data.rooms) {
          roomsData = response.data.rooms
        } else if (Array.isArray(response.data)) {
          roomsData = response.data
        }
        
        this.rooms = roomsData
        
        // Actualizar paginación
        if (Object.keys(paginationData).length > 0) {
          this.pagination = paginationData
        } else {
          this.pagination = {
            current_page: page,
            last_page: 1,
            per_page: this.pagination.per_page,
            total: this.rooms.length,
            from: 1,
            to: this.rooms.length
          }
        }
        
        console.log('✅ Rooms loaded successfully:', this.rooms.length, 'items')
        
        // Actualizar estadísticas si están vacías
        if (this.stats.total_rooms === 0 && this.rooms.length > 0) {
          this.updateStatsFromRooms()
        }
        
        this.lastError = ''
        
      } catch (error) {
        console.error('❌ Error loading rooms:', error)
        this.rooms = []
        this.lastError = `Rooms: ${error.message}`
        
        let errorMessage = 'Error al cargar las habitaciones'
        if (error.response) {
          const status = error.response.status
          const data = error.response.data
          
          if (status === 404) {
            errorMessage = 'Endpoint no encontrado (404). Verifica las rutas del backend.'
          } else if (status === 500) {
            errorMessage = 'Error interno del servidor (500). Revisa los logs del backend.'
          } else if (status === 403) {
            errorMessage = 'Sin permisos (403). Verifica la autenticación.'
          } else if (status === 401) {
            errorMessage = 'No autorizado (401). Inicia sesión nuevamente.'
          } else if (data?.message) {
            errorMessage = `Error (${status}): ${data.message}`
          }
        } else if (error.code === 'ECONNREFUSED') {
          errorMessage = 'No se puede conectar al servidor. Verifica que el backend esté ejecutándose.'
        }
        
        alert(`❌ ${errorMessage}`)
        
      } finally {
        this.loading = false
      }
    },

    updateStatsFromRooms() {
      if (this.rooms.length > 0) {
        const total = this.rooms.length
        const available = this.rooms.filter(room => room.status === 'available' && room.is_active).length
        const occupied = this.rooms.filter(room => room.status === 'occupied').length
        const occupancy = total > 0 ? Math.round((occupied / total) * 100) : 0
        
        this.stats = {
          total_rooms: total,
          available_rooms: available,
          occupied_rooms: occupied,
          occupancy_rate: occupancy
        }
        
        console.log('📊 Stats updated from rooms data:', this.stats)
      }
    },

    getRoomImage(room) {
      if (room.photos && room.photos.length > 0) {
        // Asumiendo que las fotos están almacenadas en storage/app/public/
        return `/storage/${room.photos[0]}`
      }
      // Imagen por defecto
      return '/images/room-placeholder.jpg'
    },

    showCreateModal() {
      console.log('➕ Showing create modal')
      this.editingRoom = null
      this.resetForm()
      this.modalVisible = true
    },

    viewRoom(room) {
      console.log('👁️ Viewing room:', room)
      this.selectedRoom = room
      this.showDetailModal = true
    },

    editRoom(room) {
      console.log('✏️ Editing room:', room)
      this.editingRoom = room
      this.form = {
        branch_id: room.branch_id || room.branch?.id,
        room_type_id: room.room_type_id || room.room_type?.id,
        room_number: room.room_number,
        floor: room.floor,
        price_per_night: room.price_per_night,
        status: room.status,
        description: room.description || '',
        amenities: room.amenities || [],
        is_active: room.is_active
      }
      this.showDetailModal = false
      this.modalVisible = true
    },

    async saveRoom() {
      console.log('💾 Saving room...')
      this.saving = true
      
      try {
        const formData = new FormData()
        
        // Agregar datos básicos
        formData.append('branch_id', this.form.branch_id)
        formData.append('room_type_id', this.form.room_type_id)
        formData.append('room_number', this.form.room_number)
        formData.append('floor', this.form.floor)
        formData.append('price_per_night', this.form.price_per_night)
        formData.append('status', this.form.status)
        formData.append('description', this.form.description || '')
        formData.append('is_active', this.form.is_active ? '1' : '0')
        
        // Agregar amenidades
        if (this.form.amenities && this.form.amenities.length > 0) {
          this.form.amenities.forEach((amenity, index) => {
            formData.append(`amenities[${index}]`, amenity)
          })
        }

        // Agregar fotos si hay
        if (this.$refs.photoInput && this.$refs.photoInput.files) {
          Array.from(this.$refs.photoInput.files).forEach((file, index) => {
            formData.append(`photos[${index}]`, file)
          })
        }

        let response
        if (this.editingRoom) {
          this.lastRequest = `PUT /admin/rooms/${this.editingRoom.id}`
          response = await adminApi.updateRoom(this.editingRoom.id, formData)
        } else {
          this.lastRequest = 'POST /admin/rooms'
          response = await adminApi.createRoom(formData)
        }

        console.log('💾 Save response:', response.data)
        
        // Mostrar mensaje de éxito
        if (response.data.success) {
          alert(response.data.message || 'Habitación guardada exitosamente')
        }
        
        this.loadRooms()
        this.loadStats()
        this.closeModal()
        
      } catch (error) {
        console.error('❌ Error saving room:', error)
        this.lastError = `Save room: ${error.message}`
        
        let errorMessage = 'Error al guardar la habitación'
        if (error.response?.data?.message) {
          errorMessage = error.response.data.message
        } else if (error.response?.data?.errors) {
          const errors = Object.values(error.response.data.errors).flat()
          errorMessage = errors.join(', ')
        }
        
        alert('❌ ' + errorMessage)
      } finally {
        this.saving = false
      }
    },

    async deleteRoom(room) {
      if (confirm(`¿Estás seguro de que quieres eliminar la habitación ${room.room_number}?`)) {
        try {
          console.log('🗑️ Deleting room:', room.id)
          this.lastRequest = `DELETE /admin/rooms/${room.id}`
          const response = await adminApi.deleteRoom(room.id)
          
          console.log('🗑️ Delete response:', response.data)
          
          if (response.data.success) {
            alert(response.data.message || 'Habitación eliminada exitosamente')
            this.loadRooms()
            this.loadStats()
          }
        } catch (error) {
          console.error('❌ Error deleting room:', error)
          this.lastError = `Delete room: ${error.message}`
          alert('❌ Error al eliminar la habitación: ' + error.message)
        }
      }
    },

    async toggleStatus(room) {
      try {
        console.log('🔄 Toggling room status:', room.id)
        this.lastRequest = `PUT /admin/rooms/${room.id}/toggle-status`
        const response = await adminApi.toggleRoomStatus(room.id)
        
        console.log('🔄 Toggle status response:', response.data)
        
        if (response.data.success) {
          alert(response.data.message || 'Estado actualizado exitosamente')
          this.loadRooms()
          this.loadStats()
        }
      } catch (error) {
        console.error('❌ Error toggling room status:', error)
        this.lastError = `Toggle status: ${error.message}`
        alert('❌ Error al cambiar el estado: ' + error.message)
      }
    },

    resetForm() {
      this.form = {
        branch_id: '',
        room_type_id: '',
        room_number: '',
        floor: 1,
        price_per_night: 0,
        status: 'available',
        description: '',
        amenities: [],
        is_active: true
      }
      if (this.$refs.photoInput) {
        this.$refs.photoInput.value = ''
      }
    },

    handlePhotos(event) {
      const files = event.target.files
      if (files.length > 5) {
        alert('Máximo 5 fotos permitidas')
        event.target.value = ''
      }
    },

    sortBy(column) {
      if (this.sorting.sort_by === column) {
        this.sorting.sort_order = this.sorting.sort_order === 'asc' ? 'desc' : 'asc'
      } else {
        this.sorting.sort_by = column
        this.sorting.sort_order = 'asc'
      }
      this.loadRooms()
    },

    getSortIcon(column) {
      if (this.sorting.sort_by !== column) return ''
      return this.sorting.sort_order === 'asc' ? 'fa-sort-up' : 'fa-sort-down'
    },

    changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.loadRooms(page)
      }
    },

    closeModal() {
      console.log('❌ Closing modal')
      this.modalVisible = false
      this.editingRoom = null
      this.resetForm()
    },

    getStatusLabel(status) {
      const labels = {
        available: 'Disponible',
        occupied: 'Ocupada',
        maintenance: 'Mantenimiento',
        cleaning: 'Limpieza'
      }
      return labels[status] || status
    },

    getStatusColor(status) {
      const colors = {
        available: 'success',
        occupied: 'danger',
        maintenance: 'warning',
        cleaning: 'info'
      }
      return colors[status] || 'secondary'
    }
  }
}
</script>

<style scoped>
.admin-rooms {
  background-color: #f8f9fa;
  min-height: calc(100vh - 80px);
}

.page-header {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  color: white;
  padding: 2rem;
  border-radius: 12px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  border: 1px solid #e9ecef;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.stat-number {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  color: #495057;
}

.stat-label {
  color: #6c757d;
  margin-bottom: 0;
  font-size: 0.9rem;
}

.card {
  border: 1px solid #e9ecef;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  border-radius: 12px;
}

.view-controls {
  background: white;
  padding: 1rem;
  border-radius: 12px;
  border: 1px solid #e9ecef;
}

.room-card {
  transition: all 0.3s ease;
}

.room-card:hover {
  transform: translateY(-4px);
}

.room-card:hover .card {
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border-color: #007bff;
}

.room-image {
  position: relative;
  height: 200px;
  overflow: hidden;
}

.room-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.room-status-overlay {
  position: absolute;
  top: 10px;
  right: 10px;
}

.room-number-overlay {
  position: absolute;
  top: 10px;
  left: 10px;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 0.5rem;
  border-radius: 8px;
  font-weight: 700;
}

.room-inactive-overlay {
  position: absolute;
  bottom: 10px;
  right: 10px;
}

.room-name {
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.25rem;
}

.room-details {
  color: #6c757d;
  font-size: 0.9rem;
}

.room-amenities {
  min-height: 60px;
}

.price-display {
  text-align: center;
  padding: 0.75rem;
  background-color: #f8f9fa;
  border-radius: 8px;
}

.price-amount {
  font-size: 1.5rem;
  font-weight: 700;
  color: #007bff;
}

.price-period {
  color: #6c757d;
  display: block;
}

.sortable {
  cursor: pointer;
  user-select: none;
}

.sortable:hover {
  background-color: #f8f9fa;
}

.room-number-cell strong {
  font-size: 1.1rem;
  color: #007bff;
}

.room-info-cell .room-type {
  font-weight: 600;
  color: #495057;
}

.price-cell strong {
  color: #007bff;
}

.room-branch-badge {
  background-color: #e9ecef;
  color: #495057;
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.8rem;
}

.amenities-checkboxes {
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 1rem;
}

.room-detail-image img {
  width: 100%;
  height: 250px;
  object-fit: cover;
}

.pagination .page-link {
  color: #007bff;
  border-color: #e9ecef;
}

.pagination .page-item.active .page-link {
  background-color: #007bff;
  border-color: #007bff;
}

.modal.show {
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
  border-radius: 12px;
  border: none;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

@media (max-width: 768px) {
  .page-header {
    text-align: center;
  }

  .stat-card {
    text-align: center;
    flex-direction: column;
  }

  .view-controls .row {
    flex-direction: column;
    gap: 1rem;
  }

  .rooms-grid .col-xl-3 {
    max-width: 100%;
  }
}
</style>