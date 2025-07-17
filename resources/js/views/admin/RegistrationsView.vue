<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <!-- Header Principal -->
        <div class="header-section mb-4">
          <div class="d-flex justify-content-between align-items-center">
            <div class="header-title">
              <h1 class="main-title">
                <i class="fas fa-clipboard-check me-3"></i>
                Registros Check-in/Check-out
              </h1>
              <p class="subtitle">Gestión de ingresos y salidas de huéspedes</p>
            </div>
            <button 
              class="btn btn-success btn-lg btn-create"
              @click="openDirectRegistrationModal"
              :disabled="loading"
            >
              <i class="fas fa-plus me-2"></i>
              Registro Directo
            </button>
          </div>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="row mb-4" v-if="showStats && stats">
          <div class="col-md-3 col-6">
            <div class="stats-card">
              <div class="stats-icon bg-primary">
                <i class="fas fa-users"></i>
              </div>
              <div class="stats-content">
                <h4>{{ stats.general.total_registrations }}</h4>
                <p>Total Registros</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="stats-card">
              <div class="stats-icon bg-success">
                <i class="fas fa-check-circle"></i>
              </div>
              <div class="stats-content">
                <h4>{{ stats.general.active_registrations }}</h4>
                <p>Activos</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="stats-card">
              <div class="stats-icon bg-info">
                <i class="fas fa-calendar-check"></i>
              </div>
              <div class="stats-content">
                <h4>{{ stats.general.completed_registrations }}</h4>
                <p>Completados</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="stats-card">
              <div class="stats-icon bg-warning">
                <i class="fas fa-clock"></i>
              </div>
              <div class="stats-content">
                <h4>{{ stats.general.avg_stay_duration }}</h4>
                <p>Promedio días</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Filtros mejorados -->
        <div class="card modern-card mb-4">
          <div class="card-header bg-light">
            <h5 class="mb-0">
              <i class="fas fa-filter me-2"></i>
              Filtros y Búsqueda
            </h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label">Estado del Registro</label>
                <select 
                  v-model="filters.status" 
                  class="form-select"
                  @change="fetchRegistrations"
                >
                  <option value="">Todos los estados</option>
                  <option value="active">Activo (En estadía)</option>
                  <option value="completed">Completado</option>
                </select>
              </div>
              
              <div class="col-md-3">
                <label class="form-label">Sucursal</label>
                <select 
                  v-model="filters.branch_id" 
                  class="form-select"
                  @change="fetchRegistrations"
                >
                  <option value="">Todas las sucursales</option>
                  <option 
                    v-for="branch in branches" 
                    :key="branch.id" 
                    :value="branch.id"
                  >
                    {{ branch.name }}
                  </option>
                </select>
              </div>
              
              <div class="col-md-4">
                <label class="form-label">Búsqueda</label>
                <input 
                  v-model="filters.search"
                  type="text" 
                  class="form-control"
                  placeholder="Código, cliente, habitación..."
                  @input="debounceSearch"
                >
              </div>
              
              <div class="col-md-2 d-flex align-items-end">
                <div class="filter-actions d-flex gap-2 w-100">
                  <button 
                    class="btn btn-outline-secondary filter-btn"
                    @click="clearFilters"
                    title="Limpiar todos los filtros aplicados"
                  >
                    <i class="fas fa-times me-1"></i>
                    <span class="btn-text">Limpiar</span>
                  </button>
                  <button 
                    class="btn btn-outline-primary filter-btn"
                    @click="fetchStats"
                    title="Mostrar/ocultar estadísticas detalladas"
                  >
                    <i class="fas fa-chart-bar me-1"></i>
                    <span class="btn-text">{{ showStats ? 'Ocultar' : 'Stats' }}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Loading state -->
        <div v-if="loading" class="loading-section">
          <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
              <span class="visually-hidden">Cargando registros...</span>
            </div>
            <h5 class="mt-3 text-muted">Cargando registros...</h5>
          </div>
        </div>
        
        <!-- Error state -->
        <div v-if="error" class="alert alert-danger rounded-3">
          <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
            <div>
              <h6 class="alert-heading mb-1">Error al cargar datos</h6>
              <p class="mb-2">{{ error }}</p>
              <button class="btn btn-sm btn-outline-danger" @click="fetchRegistrations">
                <i class="fas fa-sync me-1"></i>
                Reintentar
              </button>
            </div>
          </div>
        </div>
        
        <!-- Tabla de registros -->
        <div v-if="!loading && !error && registrations.length > 0" class="registrations-section">
          <div class="card modern-card">
            <div class="card-header">
              <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                Registros ({{ pagination?.total || registrations.length }})
              </h5>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover registration-table mb-0">
                  <thead class="table-header">
                    <tr>
                      <th width="12%">Código</th>
                      <th width="18%">Cliente</th>
                      <th width="14%">Habitación</th>
                      <th width="9%">Estado</th>
                      <th width="12%">Check-in</th>
                      <th width="12%">Check-out</th>
                      <th width="7%">Huéspedes</th>
                      <th width="16%">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="registration in registrations" :key="registration.id" class="registration-row">
                      <!-- Código del registro -->
                      <td>
                        <div class="registration-code">
                          <strong>{{ registration.registration_code }}</strong>
                          <div v-if="registration.reservation" class="reservation-tag">
                            <i class="fas fa-ticket-alt me-1"></i>
                            <small>{{ registration.reservation.reservation_code }}</small>
                          </div>
                        </div>
                      </td>
                      
                      <!-- Cliente -->
                      <td>
                        <div class="client-info">
                          <div class="client-name">
                            <i class="fas fa-user me-1"></i>
                            <strong>{{ registration.user.name }}</strong>
                          </div>
                          <div class="client-details">
                            <small class="text-muted">{{ registration.user.email }}</small>
                          </div>
                        </div>
                      </td>
                      
                      <!-- Habitación -->
                      <td>
                        <div class="room-info">
                          <div class="room-number">
                            <i class="fas fa-bed me-1"></i>
                            <strong>{{ registration.room.room_number }}</strong>
                          </div>
                          <div class="room-details">
                            <small class="text-muted">{{ registration.room.room_type?.name }}</small>
                            <br>
                            <small class="text-muted">
                              <i class="fas fa-map-marker-alt me-1"></i>
                              {{ registration.branch?.name }}
                            </small>
                          </div>
                        </div>
                      </td>
                      
                      <!-- Estado -->
                      <td>
                        <span :class="getStatusClass(registration.status)">
                          {{ getStatusText(registration.status) }}
                        </span>
                      </td>
                      
                      <!-- Check-in -->
                      <td>
                        <div class="date-info">
                          <div class="date-value">
                            <i class="fas fa-calendar-check text-success me-1"></i>
                            {{ formatDate(registration.actual_check_in) }}
                          </div>
                          <div class="time-value">
                            <small class="text-muted">{{ formatTime(registration.actual_check_in) }}</small>
                          </div>
                        </div>
                      </td>
                      
                      <!-- Check-out -->
                      <td>
                        <div class="date-info" v-if="registration.actual_check_out">
                          <div class="date-value">
                            <i class="fas fa-calendar-times text-danger me-1"></i>
                            {{ formatDate(registration.actual_check_out) }}
                          </div>
                          <div class="time-value">
                            <small class="text-muted">{{ formatTime(registration.actual_check_out) }}</small>
                          </div>
                        </div>
                        <div v-else class="stay-duration">
                          <i class="fas fa-clock text-warning me-1"></i>
                          <small class="text-muted">{{ getStayDuration(registration.actual_check_in) }}</small>
                        </div>
                      </td>
                      
                      <!-- Huéspedes -->
                      <td>
                        <div class="guests-info">
                          <span class="badge bg-light text-dark">
                            <i class="fas fa-users me-1"></i>
                            {{ getTotalGuests(registration) }}
                          </span>
                        </div>
                      </td>
                      
                      <!-- Acciones -->
                      <td>
                        <div class="action-buttons">
                          <div class="btn-group-vertical d-flex flex-column gap-1">
                            <!-- Botón Ver -->
                            <button 
                              class="btn btn-sm btn-primary action-btn"
                              @click="viewRegistration(registration)"
                              title="Ver detalles completos del registro"
                            >
                              <i class="fas fa-eye me-1"></i>
                              <span class="btn-text">Ver</span>
                            </button>
                            
                            <!-- Botón PDF -->
                            <button 
                              class="btn btn-sm btn-info action-btn"
                              @click="downloadCheckInPDF(registration)"
                              title="Descargar comprobante de check-in en PDF"
                            >
                              <i class="fas fa-file-pdf me-1"></i>
                              <span class="btn-text">PDF</span>
                            </button>
                            
                            <!-- Botón Check-out (solo para activos) -->
                            <button 
                              v-if="registration.status === 'active'"
                              class="btn btn-sm btn-success action-btn"
                              @click="openCheckOutModal(registration)"
                              title="Procesar check-out del huésped"
                            >
                              <i class="fas fa-sign-out-alt me-1"></i>
                              <span class="btn-text">Check-out</span>
                            </button>
                            
                            <!-- Indicador de estado completado -->
                            <span 
                              v-if="registration.status === 'completed'"
                              class="badge bg-secondary mt-1"
                              title="Registro completado - No hay acciones disponibles"
                            >
                              <i class="fas fa-check-circle me-1"></i>
                              Completado
                            </span>
                          </div>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="!loading && !error && registrations.length === 0" class="empty-state">
          <div class="text-center py-5">
            <div class="empty-icon">
              <i class="fas fa-clipboard-list"></i>
            </div>
            <h3 class="empty-title">No hay registros disponibles</h3>
            <p class="empty-description">
              No se encontraron registros que coincidan con los filtros seleccionados.
            </p>
            <div class="empty-actions">
              <button @click="clearFilters" class="btn btn-outline-primary btn-lg me-2">
                <i class="fas fa-filter me-2"></i>
                Limpiar Filtros
              </button>
              <button @click="openDirectRegistrationModal" class="btn btn-success btn-lg">
                <i class="fas fa-plus me-2"></i>
                Crear Registro
              </button>
            </div>
          </div>
        </div>
        
        <!-- Paginación mejorada -->
        <nav v-if="pagination && pagination.last_page > 1" class="pagination-section mt-4">
          <div class="d-flex justify-content-between align-items-center">
            <div class="pagination-info">
              <span class="text-muted">
                Mostrando {{ pagination.from }} a {{ pagination.to }} de {{ pagination.total }} registros
              </span>
            </div>
            <ul class="pagination mb-0">
              <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                <button 
                  class="page-link"
                  @click="changePage(pagination.current_page - 1)"
                  :disabled="pagination.current_page === 1"
                >
                  <i class="fas fa-chevron-left"></i>
                </button>
              </li>
              
              <li 
                v-for="page in getPageNumbers()" 
                :key="page"
                class="page-item"
                :class="{ active: page === pagination.current_page }"
              >
                <button class="page-link" @click="changePage(page)">
                  {{ page }}
                </button>
              </li>
              
              <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                <button 
                  class="page-link"
                  @click="changePage(pagination.current_page + 1)"
                  :disabled="pagination.current_page === pagination.last_page"
                >
                  <i class="fas fa-chevron-right"></i>
                </button>
              </li>
            </ul>
          </div>
        </nav>
      </div>
    </div>

    <!-- Modal de Check-out -->
    <div 
      v-if="showCheckOutModal"
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(0,0,0,0.5);"
    >
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-sign-out-alt text-success me-2"></i>
              Check-out - {{ selectedRegistrationForCheckOut?.registration_code }}
            </h5>
            <button 
              type="button" 
              class="btn-close" 
              @click="closeCheckOutModal"
            ></button>
          </div>
          
          <div class="modal-body" v-if="selectedRegistrationForCheckOut">
            <!-- Información del registro -->
            <div class="row mb-4">
              <div class="col-md-6">
                <div class="card bg-light">
                  <div class="card-body">
                    <h6 class="card-title">
                      <i class="fas fa-user me-2"></i>Cliente
                    </h6>
                    <p class="mb-1"><strong>{{ selectedRegistrationForCheckOut.user.name }}</strong></p>
                    <p class="mb-0 text-muted">{{ selectedRegistrationForCheckOut.user.email }}</p>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card bg-light">
                  <div class="card-body">
                    <h6 class="card-title">
                      <i class="fas fa-bed me-2"></i>Habitación
                    </h6>
                    <p class="mb-1">
                      <strong>{{ selectedRegistrationForCheckOut.room.room_number }}</strong>
                      <span class="badge bg-secondary ms-2">{{ selectedRegistrationForCheckOut.room.room_type?.name }}</span>
                    </p>
                    <p class="mb-0 text-muted">{{ selectedRegistrationForCheckOut.branch?.name }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Información de fechas -->
            <div class="row mb-4">
              <div class="col-md-6">
                <div class="card border-success">
                  <div class="card-body">
                    <h6 class="card-title text-success">
                      <i class="fas fa-calendar-check me-2"></i>Check-in
                    </h6>
                    <p class="mb-0">{{ formatDateTime(selectedRegistrationForCheckOut.actual_check_in) }}</p>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card border-warning">
                  <div class="card-body">
                    <h6 class="card-title text-warning">
                      <i class="fas fa-calendar-times me-2"></i>Check-out
                    </h6>
                    <p class="mb-0 fw-bold">{{ getCurrentDateTime() }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Duración de la estadía -->
            <div class="alert alert-info">
              <i class="fas fa-clock me-2"></i>
              <strong>Duración de la estadía:</strong> {{ getStayDuration(selectedRegistrationForCheckOut.actual_check_in) }}
            </div>

            <!-- Huéspedes adicionales si los hay -->
            <div v-if="selectedRegistrationForCheckOut.additional_guests && selectedRegistrationForCheckOut.additional_guests.length > 0" class="mb-3">
              <h6>
                <i class="fas fa-users me-2"></i>Huéspedes adicionales
              </h6>
              <div class="row">
                <div 
                  v-for="(guest, index) in selectedRegistrationForCheckOut.additional_guests" 
                  :key="index"
                  class="col-md-6 mb-2"
                >
                  <div class="border rounded p-2">
                    <small>
                      <strong>{{ guest.name }}</strong><br>
                      {{ guest.document_type }}: {{ guest.document_number }}
                    </small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Formulario de check-out -->
            <form @submit.prevent="processCheckOut">
              <div class="mb-3">
                <label class="form-label">
                  <i class="fas fa-sticky-note me-2"></i>Notas de check-out
                </label>
                <textarea 
                  v-model="checkOutForm.notes"
                  class="form-control"
                  rows="3"
                  placeholder="Observaciones sobre el check-out, estado de la habitación, etc..."
                ></textarea>
              </div>

              <div class="mb-3">
                <label class="form-label">
                  <i class="fas fa-exclamation-triangle me-2"></i>Reporte de daños (opcional)
                </label>
                <textarea 
                  v-model="checkOutForm.damage_report"
                  class="form-control"
                  rows="2"
                  placeholder="Descripción de cualquier daño encontrado en la habitación..."
                ></textarea>
              </div>

              <!-- Checklist de verificación -->
              <div class="mb-3">
                <h6>Checklist de verificación</h6>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-check">
                      <input 
                        v-model="checkOutForm.room_cleaned"
                        type="checkbox" 
                        class="form-check-input"
                        id="roomCleaned"
                      >
                      <label class="form-check-label" for="roomCleaned">
                        Habitación revisada
                      </label>
                    </div>
                    <div class="form-check">
                      <input 
                        v-model="checkOutForm.keys_returned"
                        type="checkbox" 
                        class="form-check-input"
                        id="keysReturned"
                      >
                      <label class="form-check-label" for="keysReturned">
                        Llaves devueltas *
                      </label>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-check">
                      <input 
                        v-model="checkOutForm.minibar_checked"
                        type="checkbox" 
                        class="form-check-input"
                        id="minibarChecked"
                      >
                      <label class="form-check-label" for="minibarChecked">
                        Minibar revisado
                      </label>
                    </div>
                    <div class="form-check">
                      <input 
                        v-model="checkOutForm.client_satisfied"
                        type="checkbox" 
                        class="form-check-input"
                        id="clientSatisfied"
                      >
                      <label class="form-check-label" for="clientSatisfied">
                        Cliente satisfecho
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Confirmación -->
              <div class="alert alert-warning">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Importante:</strong> Al confirmar el check-out, la habitación será liberada automáticamente y el registro se marcará como completado. Esta acción no se puede deshacer.
              </div>
            </form>
          </div>
          
          <div class="modal-footer">
            <button 
              type="button" 
              class="btn btn-secondary" 
              @click="closeCheckOutModal"
              :disabled="processingCheckOut"
            >
              Cancelar
            </button>
            
            <button 
              type="button" 
              class="btn btn-success"
              @click="processCheckOut"
              :disabled="processingCheckOut || !canProcessCheckOut()"
            >
              <span v-if="processingCheckOut">
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                Procesando...
              </span>
              <span v-else>
                <i class="fas fa-check me-2"></i>Confirmar Check-out
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Registro Directo -->
    <div 
      v-if="showDirectRegistrationModal"
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(0,0,0,0.5);"
    >
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-user-plus me-2"></i>
              Registro Directo
            </h5>
            <button 
              type="button" 
              class="btn-close" 
              @click="showDirectRegistrationModal = false"
            ></button>
          </div>
          
          <div class="modal-body">
            <form @submit.prevent="submitDirectRegistration">
              <!-- Paso 1: Seleccionar Usuario -->
              <div v-if="directRegistration.step === 1">
                <h6 class="mb-3">1. Seleccionar Cliente</h6>
                
                <div class="row mb-3">
                  <div class="col-md-8">
                    <label class="form-label">Buscar cliente existente</label>
                    <input 
                      v-model="userSearch"
                      type="text" 
                      class="form-control"
                      placeholder="Buscar por nombre, email o documento..."
                      @input="searchUsers"
                    >
                  </div>
                  <div class="col-md-4 d-flex align-items-end">
                    <button 
                      type="button"
                      class="btn btn-outline-primary w-100"
                      @click="showNewUserForm = !showNewUserForm"
                    >
                      <i class="fas fa-user-plus"></i> Nuevo Cliente
                    </button>
                  </div>
                </div>

                <!-- Lista de usuarios encontrados -->
                <div v-if="foundUsers.length > 0" class="mb-3">
                  <label class="form-label">Usuarios encontrados:</label>
                  <div class="list-group">
                    <button 
                      v-for="user in foundUsers" 
                      :key="user.id"
                      type="button"
                      class="list-group-item list-group-item-action"
                      :class="{ active: directRegistration.form.user_id === user.id }"
                      @click="selectUser(user)"
                    >
                      <div class="d-flex justify-content-between">
                        <div>
                          <strong>{{ user.name }}</strong>
                          <br>
                          <small>{{ user.email }} - {{ user.document_type }}: {{ user.document_number }}</small>
                        </div>
                        <span v-if="directRegistration.form.user_id === user.id" class="badge bg-primary">
                          Seleccionado
                        </span>
                      </div>
                    </button>
                  </div>
                </div>

                <!-- Formulario de nuevo usuario -->
                <div v-if="showNewUserForm" class="border rounded p-3 mb-3">
                  <h6>Registrar nuevo cliente</h6>
                  <div class="row">
                    <div class="col-md-6">
                      <label class="form-label">Nombre completo *</label>
                      <input 
                        v-model="newUser.name"
                        type="text" 
                        class="form-control"
                        required
                      >
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Email *</label>
                      <input 
                        v-model="newUser.email"
                        type="email" 
                        class="form-control"
                        required
                      >
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Tipo documento *</label>
                      <select v-model="newUser.document_type" class="form-select" required>
                        <option value="">Seleccionar</option>
                        <option value="ci">Cédula de Identidad</option>
                        <option value="passport">Pasaporte</option>
                        <option value="license">Licencia de Conducir</option>
                        <option value="other">Otro</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Número documento *</label>
                      <input 
                        v-model="newUser.document_number"
                        type="text" 
                        class="form-control"
                        required
                      >
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Teléfono</label>
                      <input 
                        v-model="newUser.phone"
                        type="text" 
                        class="form-control"
                      >
                    </div>
                  </div>
                </div>
              </div>

              <!-- Paso 2: Seleccionar Habitación -->
              <div v-if="directRegistration.step === 2">
                <h6 class="mb-3">2. Seleccionar Habitación</h6>
                
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label class="form-label">Sucursal *</label>
                    <select 
                      v-model.number="directRegistration.form.branch_id" 
                      class="form-select"
                      @change="loadAvailableRooms"
                      required
                    >
                      <option value="">Seleccionar sucursal</option>
                      <option 
                        v-for="branch in branches" 
                        :key="branch.id" 
                        :value="branch.id"
                      >
                        {{ branch.name }}
                      </option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Fecha de salida esperada *</label>
                    <input 
                      v-model="directRegistration.form.expected_checkout"
                      type="date" 
                      class="form-control"
                      :min="tomorrow"
                      required
                    >
                  </div>
                </div>

                <!-- Habitaciones disponibles -->
                <div v-if="availableRooms.length > 0">
                  <label class="form-label">Habitaciones disponibles:</label>
                  <div class="row">
                    <div 
                      v-for="room in availableRooms" 
                      :key="room.id"
                      class="col-md-6 mb-2"
                    >
                      <div 
                        class="card cursor-pointer"
                        :class="{ 'border-primary': directRegistration.form.room_id === room.id }"
                        @click="selectRoom(room)"
                      >
                        <div class="card-body">
                          <div class="d-flex justify-content-between">
                            <div>
                              <strong>{{ room.room_number }}</strong>
                              <br>
                              <small>{{ room.room_type?.name }}</small>
                              <br>
                              <span class="text-success">Bs. {{ room.price_per_night }}/noche</span>
                            </div>
                            <div v-if="directRegistration.form.room_id === room.id">
                              <i class="fas fa-check-circle text-primary"></i>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div v-else-if="directRegistration.form.branch_id" class="alert alert-warning">
                  No hay habitaciones disponibles en la sucursal seleccionada.
                </div>
              </div>

              <!-- Paso 3: Detalles adicionales -->
              <div v-if="directRegistration.step === 3">
                <h6 class="mb-3">3. Detalles del Registro</h6>
                
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label class="form-label">Número de adultos *</label>
                    <input 
                      v-model.number="directRegistration.form.adults_count"
                      type="number" 
                      class="form-control"
                      min="1"
                      required
                    >
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Número de niños</label>
                    <input 
                      v-model.number="directRegistration.form.children_count"
                      type="number" 
                      class="form-control"
                      min="0"
                    >
                  </div>
                </div>

                <div class="mb-3">
                  <div class="form-check">
                    <input 
                      v-model="directRegistration.form.needs_parking"
                      type="checkbox" 
                      class="form-check-input"
                      id="needsParking"
                    >
                    <label class="form-check-label" for="needsParking">
                      Necesita estacionamiento (Gratuito)
                    </label>
                  </div>
                </div>

                <!-- Información del vehículo -->
                <div v-if="directRegistration.form.needs_parking" class="mb-3">
                  <div class="card bg-light">
                    <div class="card-body">
                      <h6 class="card-title">
                        <i class="fas fa-car me-2"></i>
                        Información del Vehículo
                      </h6>
                      <div class="row">
                        <div class="col-md-6">
                          <label class="form-label">Modelo del vehículo *</label>
                          <input 
                            v-model="vehicleInfo.model"
                            type="text" 
                            class="form-control"
                            placeholder="Ej: Toyota Corolla 2020"
                            required
                          >
                        </div>
                        <div class="col-md-6">
                          <label class="form-label">Placa del vehículo *</label>
                          <input 
                            v-model="vehicleInfo.license_plate"
                            type="text" 
                            class="form-control"
                            placeholder="Ej: ABC-1234"
                            required
                          >
                        </div>
                      </div>
                      <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Esta información es requerida para el control de acceso al estacionamiento.
                      </small>
                    </div>
                  </div>
                </div>

                <!-- Huéspedes adicionales -->
                <div class="mb-3">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label">Huéspedes adicionales</label>
                    <button 
                      type="button"
                      class="btn btn-sm btn-outline-primary"
                      @click="addAdditionalGuest"
                    >
                      <i class="fas fa-plus"></i> Agregar
                    </button>
                  </div>
                  
                  <div 
                    v-for="(guest, index) in directRegistration.form.additional_guests" 
                    :key="index"
                    class="border rounded p-2 mb-2"
                  >
                    <div class="row align-items-center">
                      <div class="col-md-4">
                        <input 
                          v-model="guest.name"
                          type="text" 
                          class="form-control"
                          placeholder="Nombre completo"
                          required
                        >
                      </div>
                      <div class="col-md-3">
                        <select v-model="guest.document_type" class="form-select" required>
                          <option value="">Tipo doc.</option>
                          <option value="ci">CI</option>
                          <option value="passport">Pasaporte</option>
                          <option value="license">Licencia</option>
                          <option value="other">Otro</option>
                        </select>
                      </div>
                      <div class="col-md-3">
                        <input 
                          v-model="guest.document_number"
                          type="text" 
                          class="form-control"
                          placeholder="Número documento"
                          required
                        >
                      </div>
                      <div class="col-md-2">
                        <button 
                          type="button"
                          class="btn btn-sm btn-outline-danger"
                          @click="removeAdditionalGuest(index)"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Notas adicionales</label>
                  <textarea 
                    v-model="directRegistration.form.notes"
                    class="form-control"
                    rows="3"
                    placeholder="Observaciones especiales..."
                  ></textarea>
                </div>

                <!-- Resumen -->
                <div class="alert alert-info">
                  <h6>Resumen del registro:</h6>
                  <ul class="mb-0">
                    <li><strong>Cliente:</strong> {{ getSelectedUserName() }}</li>
                    <li><strong>Habitación:</strong> {{ getSelectedRoomInfo() }}</li>
                    <li><strong>Check-in:</strong> Ahora</li>
                    <li><strong>Check-out esperado:</strong> {{ directRegistration.form.expected_checkout }}</li>
                    <li><strong>Total huéspedes:</strong> {{ getTotalGuestsCount() }}</li>
                    <li v-if="directRegistration.form.needs_parking"><strong>Estacionamiento:</strong> Sí ({{ vehicleInfo.model }} - {{ vehicleInfo.license_plate }})</li>
                  </ul>
                </div>
              </div>
            </form>
          </div>
          
          <div class="modal-footer">
            <button 
              v-if="directRegistration.step > 1"
              type="button" 
              class="btn btn-secondary"
              @click="directRegistration.step--"
            >
              <i class="fas fa-arrow-left"></i> Anterior
            </button>
            
            <button 
              type="button" 
              class="btn btn-outline-secondary" 
              @click="showDirectRegistrationModal = false"
            >
              Cancelar
            </button>
            
            <button 
              v-if="directRegistration.step < 3"
              type="button" 
              class="btn btn-primary"
              @click="nextStep"
              :disabled="!canProceedToNextStep()"
            >
              Siguiente <i class="fas fa-arrow-right"></i>
            </button>
            
            <button 
              v-if="directRegistration.step === 3"
              type="button" 
              class="btn btn-success"
              @click="submitDirectRegistration"
              :disabled="submitting || !canSubmit()"
            >
              <span v-if="submitting">
                <span class="spinner-border spinner-border-sm" role="status"></span>
                Registrando...
              </span>
              <span v-else>
                <i class="fas fa-save"></i> Registrar
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de detalles -->
    <div 
      v-if="showRegistrationDetailsModal"
      class="modal fade show d-block" 
      tabindex="-1"
      style="background-color: rgba(0,0,0,0.5);"
    >
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-info-circle me-2"></i>
              Detalles del Registro
            </h5>
            <button 
              type="button" 
              class="btn-close" 
              @click="showRegistrationDetailsModal = false"
            ></button>
          </div>
          
          <div class="modal-body" v-if="selectedRegistration">
            <div class="row">
              <div class="col-md-6">
                <h6>Información del Registro</h6>
                <table class="table table-sm">
                  <tr>
                    <td><strong>Código:</strong></td>
                    <td>{{ selectedRegistration.registration_code }}</td>
                  </tr>
                  <tr>
                    <td><strong>Estado:</strong></td>
                    <td>
                      <span 
                        :class="getStatusClass(selectedRegistration.status)"
                        class="badge"
                      >
                        {{ getStatusText(selectedRegistration.status) }}
                      </span>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>Check-in:</strong></td>
                    <td>{{ formatDateTime(selectedRegistration.actual_check_in) }}</td>
                  </tr>
                  <tr>
                    <td><strong>Check-out:</strong></td>
                    <td>
                      {{ selectedRegistration.actual_check_out ? formatDateTime(selectedRegistration.actual_check_out) : 'Pendiente' }}
                    </td>
                  </tr>
                </table>
              </div>
              
              <div class="col-md-6">
                <h6>Información del Cliente</h6>
                <table class="table table-sm">
                  <tr>
                    <td><strong>Nombre:</strong></td>
                    <td>{{ selectedRegistration.user?.name }}</td>
                  </tr>
                  <tr>
                    <td><strong>Email:</strong></td>
                    <td>{{ selectedRegistration.user?.email }}</td>
                  </tr>
                  <tr>
                    <td><strong>Documento:</strong></td>
                    <td>{{ selectedRegistration.user?.document_type }}: {{ selectedRegistration.user?.document_number }}</td>
                  </tr>
                  <tr>
                    <td><strong>Teléfono:</strong></td>
                    <td>{{ selectedRegistration.user?.phone || 'No especificado' }}</td>
                  </tr>
                </table>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-md-6">
                <h6>Habitación</h6>
                <table class="table table-sm">
                  <tr>
                    <td><strong>Número:</strong></td>
                    <td>{{ selectedRegistration.room?.room_number }}</td>
                  </tr>
                  <tr>
                    <td><strong>Tipo:</strong></td>
                    <td>{{ selectedRegistration.room?.room_type?.name }}</td>
                  </tr>
                  <tr>
                    <td><strong>Sucursal:</strong></td>
                    <td>{{ selectedRegistration.branch?.name }}</td>
                  </tr>
                </table>
              </div>
              
              <div class="col-md-6">
                <h6>Huéspedes Adicionales</h6>
                <div v-if="selectedRegistration.additional_guests && selectedRegistration.additional_guests.length > 0">
                  <div 
                    v-for="(guest, index) in selectedRegistration.additional_guests" 
                    :key="index"
                    class="border rounded p-2 mb-1"
                  >
                    <small>
                      <strong>{{ guest.name }}</strong><br>
                      {{ guest.document_type }}: {{ guest.document_number }}
                    </small>
                  </div>
                </div>
                <p v-else class="text-muted"><small>Sin huéspedes adicionales</small></p>
              </div>
            </div>

            <div v-if="selectedRegistration.notes" class="mt-3">
              <h6>Notas</h6>
              <p class="text-muted">{{ selectedRegistration.notes }}</p>
            </div>
          </div>
          
          <div class="modal-footer">
            <button 
              type="button" 
              class="btn btn-secondary" 
              @click="showRegistrationDetailsModal = false"
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'
import { adminApi, sharedApi } from '@/services/api'

export default {
  name: 'RegistrationsView',
  
  data() {
    return {
      // Estados principales
      loading: false,
      error: null,
      submitting: false,
      showStats: false,
      
      // Estados de modales
      showDirectRegistrationModal: false,
      showRegistrationDetailsModal: false,
      showCheckOutModal: false,
      
      // Datos
      registrations: [],
      branches: [],
      stats: null,
      pagination: null,
      
      // Filtros
      filters: {
        status: '',
        branch_id: '',
        search: '',
        page: 1,
        per_page: 15
      },
      
      // Búsqueda con debounce
      searchTimeout: null,
      
      // Modal de registro directo
      directRegistration: {
        step: 1,
        form: {
          user_id: null,
          branch_id: '',
          room_id: '',
          expected_checkout: '',
          adults_count: 1,
          children_count: 0,
          needs_parking: false,
          additional_guests: [],
          notes: ''
        }
      },
      
      // Información del vehículo
      vehicleInfo: {
        model: '',
        license_plate: ''
      },
      
      // Usuarios y habitaciones
      userSearch: '',
      foundUsers: [],
      availableRooms: [],
      selectedUser: null,
      selectedRoom: null,
      showNewUserForm: false,
      newUser: {
        name: '',
        email: '',
        document_type: '',
        document_number: '',
        phone: ''
      },
      
      // Modal de detalles
      selectedRegistration: null,

      // Check-out
      selectedRegistrationForCheckOut: null,
      processingCheckOut: false,
      checkOutForm: {
        notes: '',
        damage_report: '',
        room_cleaned: false,
        keys_returned: false,
        minibar_checked: false,
        client_satisfied: false
      }
    }
  },
  
  computed: {
    tomorrow() {
      const tomorrow = new Date()
      tomorrow.setDate(tomorrow.getDate() + 1)
      return tomorrow.toISOString().split('T')[0]
    }
  },
  
  async mounted() {
    await this.loadInitialData()
    this.fetchRegistrations()
  },
  
  methods: {
    // Carga inicial de datos
    async loadInitialData() {
      try {
        const branchesResponse = await sharedApi.getBranches()
        
        // La respuesta tiene la estructura: { data: { branches: [...] } }
        if (branchesResponse.data && branchesResponse.data.branches) {
          this.branches = branchesResponse.data.branches
        } else if (branchesResponse.data && branchesResponse.data.data) {
          this.branches = branchesResponse.data.data
        } else if (Array.isArray(branchesResponse.data)) {
          this.branches = branchesResponse.data
        } else {
          console.warn('Unexpected branches response structure:', branchesResponse.data)
          this.branches = []
        }
      } catch (error) {
        console.error('Error loading initial data:', error)
        this.error = 'Error al cargar datos iniciales: ' + (error.message || 'Error desconocido')
      }
    },
    
    // Obtener registros
    async fetchRegistrations() {
      this.loading = true
      this.error = null
      
      try {
        const params = { ...this.filters }
        // Limpiar parámetros vacíos
        Object.keys(params).forEach(key => {
          if (params[key] === '' || params[key] === null) {
            delete params[key]
          }
        })
        
        const response = await adminApi.getRegistrations(params)
        
        if (response.data.success) {
          this.registrations = response.data.data || []
          this.pagination = response.data.pagination || null
        } else {
          throw new Error(response.data.message || 'Error al obtener registros')
        }
      } catch (error) {
        console.error('Error fetching registrations:', error)
        this.error = error.response?.data?.message || error.message || 'Error al cargar registros'
        this.registrations = []
      } finally {
        this.loading = false
      }
    },
    
    async fetchStats() {
      try {
        const response = await adminApi.getRegistrationStats()
        if (response.data.success) {
          this.stats = response.data.data
          this.showStats = !this.showStats
        }
      } catch (error) {
        console.error('Error fetching stats:', error)
      }
    },
    
    // Búsqueda con debounce
    debounceSearch() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.filters.page = 1
        this.fetchRegistrations()
      }, 500)
    },
    
    // Limpiar filtros
    clearFilters() {
      this.filters = {
        status: '',
        branch_id: '',
        search: '',
        page: 1,
        per_page: 15
      }
      this.fetchRegistrations()
    },
    
    // Paginación
    changePage(page) {
      if (page >= 1 && page <= this.pagination.last_page) {
        this.filters.page = page
        this.fetchRegistrations()
      }
    },
    
    getPageNumbers() {
      const pages = []
      const current = this.pagination.current_page
      const last = this.pagination.last_page
      
      // Mostrar páginas alrededor de la actual
      for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
        pages.push(i)
      }
      
      return pages
    },

    // Descargar PDF del check-in
    async downloadCheckInPDF(registration) {
      try {
        const doc = new jsPDF()
        const pageWidth = doc.internal.pageSize.width
        const pageHeight = doc.internal.pageSize.height
        
        // Configuración de colores corporativos
        const primaryColor = [13, 110, 253] // Azul bootstrap
        const successColor = [25, 135, 84] // Verde bootstrap
        const infoColor = [13, 202, 240] // Azul info
        const darkColor = [33, 37, 41] // Negro bootstrap
        const secondaryColor = [108, 117, 125] // Gris bootstrap
        
        // Header corporativo con fondo
        doc.setFillColor(...primaryColor)
        doc.rect(0, 0, pageWidth, 40, 'F')
        
        // Título principal
        doc.setTextColor(255, 255, 255)
        doc.setFontSize(22)
        doc.setFont('helvetica', 'bold')
        doc.text('COMPROBANTE DE CHECK-IN', pageWidth / 2, 20, { align: 'center' })
        
        // Subtítulo
        doc.setFontSize(11)
        doc.setFont('helvetica', 'normal')
        doc.text('Confirmación de Ingreso Hotelero', pageWidth / 2, 30, { align: 'center' })
        
        // Información del hotel/sucursal
        let yPos = 50
        doc.setTextColor(...darkColor)
        doc.setFontSize(16)
        doc.setFont('helvetica', 'bold')
        doc.text(registration.branch?.name || 'Hotel', 20, yPos)
        
        yPos += 8
        doc.setFontSize(10)
        doc.setFont('helvetica', 'normal')
        doc.setTextColor(...secondaryColor)
        doc.text(`Dirección: ${registration.branch?.address || 'Dirección no disponible'}`, 20, yPos)
        
        yPos += 5
        doc.text(`Teléfono: ${registration.branch?.phone || 'Teléfono no disponible'}`, 20, yPos)
        
        // Fecha y hora de emisión (esquina superior derecha)
        doc.setFontSize(9)
        doc.setTextColor(...secondaryColor)
        const currentDate = new Date().toLocaleDateString('es-BO', {
          year: 'numeric',
          month: 'long',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        })
        doc.text(`Fecha de emisión: ${currentDate}`, pageWidth - 20, 50, { align: 'right' })
        
        // Línea separadora
        yPos += 15
        doc.setLineWidth(0.8)
        doc.setDrawColor(...primaryColor)
        doc.line(20, yPos, pageWidth - 20, yPos)
        
        // Información del check-in en tabla
        yPos += 10
        
        const checkInData = [
          ['Código de Registro', registration.registration_code],
          ['Estado', this.getStatusText(registration.status)],
          ['Fecha y Hora de Check-in', this.formatDateTime(registration.actual_check_in)],
          ['Fecha de Check-out Esperada', registration.expected_checkout ? this.formatDateTime(registration.expected_checkout) : 'No especificada'],
          ['Huésped Principal', registration.user?.name || 'No especificado'],
          ['Documento de Identidad', registration.user?.document_type ? `${registration.user.document_type.toUpperCase()}: ${registration.user.document_number}` : 'No especificado'],
          ['Email', registration.user?.email || 'No especificado'],
          ['Teléfono', registration.user?.phone || 'No especificado'],
          ['Habitación', `${registration.room?.room_number || 'N/A'} - ${registration.room?.room_type?.name || 'Tipo no especificado'}`],
          ['Capacidad de Habitación', `${registration.room?.room_type?.max_guests || 'N/A'} huéspedes`],
          ['Precio por Noche', `Bs. ${registration.room?.price_per_night || '0.00'}`],
          ['Total de Huéspedes', `${this.getTotalGuests(registration)} persona(s)`],
          ['Estacionamiento', registration.needs_parking ? 'Sí (Incluido)' : 'No solicitado']
        ]
        
        // Si hay reserva asociada, agregar información
        if (registration.reservation) {
          checkInData.push(['Reserva Asociada', registration.reservation.reservation_code])
        }
        
        autoTable(doc, {
          startY: yPos,
          body: checkInData,
          theme: 'striped',
          styles: {
            fontSize: 9,
            cellPadding: 4,
            lineColor: [224, 224, 224],
            lineWidth: 0.1
          },
          columnStyles: {
            0: { 
              cellWidth: 60,
              fontStyle: 'bold',
              textColor: darkColor,
              fillColor: [248, 249, 250]
            },
            1: { 
              cellWidth: 110,
              textColor: darkColor
            }
          },
          alternateRowStyles: {
            fillColor: [248, 249, 250]
          }
        })
        
        // Huéspedes adicionales
        yPos = doc.lastAutoTable.finalY + 15
        if (registration.additional_guests && registration.additional_guests.length > 0) {
          doc.setFontSize(12)
          doc.setFont('helvetica', 'bold')
          doc.setTextColor(...darkColor)
          doc.text('HUÉSPEDES ADICIONALES:', 20, yPos)
          
          yPos += 10
          
          const guestData = [
            ['Nombre Completo', 'Tipo de Documento', 'Número de Documento']
          ]
          
          registration.additional_guests.forEach(guest => {
            guestData.push([
              guest.name,
              guest.document_type?.toUpperCase() || 'N/A',
              guest.document_number || 'N/A'
            ])
          })
          
          autoTable(doc, {
            startY: yPos,
            head: [guestData[0]],
            body: guestData.slice(1),
            theme: 'grid',
            styles: {
              fontSize: 9,
              cellPadding: 4,
              lineColor: [224, 224, 224],
              lineWidth: 0.5
            },
            headStyles: {
              fillColor: infoColor,
              textColor: [255, 255, 255],
              fontStyle: 'bold',
              fontSize: 10
            },
            columnStyles: {
              0: { cellWidth: 70 },
              1: { cellWidth: 50, halign: 'center' },
              2: { cellWidth: 50, halign: 'center' }
            }
          })
          
          yPos = doc.lastAutoTable.finalY + 15
        }
        
        // Notas adicionales
        if (registration.notes) {
          yPos += 5
          doc.setFontSize(12)
          doc.setFont('helvetica', 'bold')
          doc.setTextColor(...darkColor)
          doc.text('NOTAS ADICIONALES:', 20, yPos)
          
          yPos += 8
          doc.setFontSize(10)
          doc.setFont('helvetica', 'normal')
          doc.setTextColor(...secondaryColor)
          
          const splitNotes = doc.splitTextToSize(registration.notes, pageWidth - 40)
          splitNotes.forEach(line => {
            doc.text(line, 20, yPos)
            yPos += 5
          })
          
          yPos += 5
        }
        
        // Información importante
        yPos += 10
        doc.setFontSize(12)
        doc.setFont('helvetica', 'bold')
        doc.setTextColor(...darkColor)
        doc.text('INFORMACIÓN IMPORTANTE:', 20, yPos)
        
        yPos += 8
        doc.setFontSize(10)
        doc.setFont('helvetica', 'normal')
        doc.setTextColor(...secondaryColor)
        
        const importantInfo = [
          '• Conservar este comprobante durante toda su estadía',
          '• Presentar documento de identidad al personal cuando se solicite',
          '• Respetar las políticas y reglamentos del hotel',
          '• Check-out estándar: 12:00 PM (mediodía)',
          '• Para servicios adicionales, contactar a recepción',
          '• En caso de emergencia, comunicarse inmediatamente con el personal'
        ]
        
        importantInfo.forEach(info => {
          doc.text(info, 20, yPos)
          yPos += 6
        })
        
        // Sección de políticas
        yPos += 10
        doc.setFontSize(12)
        doc.setFont('helvetica', 'bold')
        doc.setTextColor(...darkColor)
        doc.text('POLÍTICAS DEL HOTEL:', 20, yPos)
        
        yPos += 8
        doc.setFontSize(10)
        doc.setFont('helvetica', 'normal')
        doc.setTextColor(...secondaryColor)
        
        const policies = [
          '• Prohibido fumar en habitaciones y áreas comunes',
          '• Mantener un ambiente de respeto y tranquilidad',
          '• Responsabilidad por daños causados a la propiedad',
          '• Registro de huéspedes adicionales requerido',
          '• Cumplir con horarios de silencio (22:00 - 06:00)'
        ]
        
        policies.forEach(policy => {
          doc.text(policy, 20, yPos)
          yPos += 6
        })
        
        // Footer con información de contacto
        yPos = pageHeight - 40
        doc.setLineWidth(0.5)
        doc.setDrawColor(...primaryColor)
        doc.line(20, yPos, pageWidth - 20, yPos)
        
        yPos += 10
        doc.setFontSize(11)
        doc.setFont('helvetica', 'bold')
        doc.setTextColor(...primaryColor)
        doc.text('¡Bienvenido y disfrute su estadía!', pageWidth / 2, yPos, { align: 'center' })
        
        yPos += 8
        doc.setFontSize(9)
        doc.setFont('helvetica', 'normal')
        doc.setTextColor(...secondaryColor)
        doc.text('Para cualquier consulta o asistencia, no dude en contactarnos', pageWidth / 2, yPos, { align: 'center' })
        
        // Información del personal (si está disponible)
        if (registration.created_by) {
          yPos += 6
          doc.setFontSize(8)
          doc.text(`Procesado por: ${registration.created_by.name || 'Personal del hotel'}`, pageWidth / 2, yPos, { align: 'center' })
        }
        
        // Guardar el PDF
        doc.save(`CheckIn_${registration.registration_code}.pdf`)
        
      } catch (error) {
        console.error('Error generating check-in PDF:', error)
        alert('Error al generar el PDF. Inténtalo de nuevo.')
      }
    },

    // Calcular duración de la estadía
    getStayDuration(checkInDateTime) {
      if (!checkInDateTime) return 'No disponible'
      
      const checkIn = new Date(checkInDateTime)
      const now = new Date()
      const diffTime = Math.abs(now - checkIn)
      const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
      const diffHours = Math.floor((diffTime % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
      
      if (diffDays > 0) {
        return `${diffDays} día(s), ${diffHours} hora(s)`
      } else if (diffHours > 0) {
        return `${diffHours} hora(s)`
      } else {
        const diffMinutes = Math.floor((diffTime % (1000 * 60 * 60)) / (1000 * 60))
        return `${diffMinutes} minuto(s)`
      }
    },

    // ===== FUNCIONES DE CHECK-OUT =====

    // Abrir modal de check-out
    openCheckOutModal(registration) {
      this.selectedRegistrationForCheckOut = registration
      this.resetCheckOutForm()
      this.showCheckOutModal = true
    },

    // Cerrar modal de check-out
    closeCheckOutModal() {
      this.showCheckOutModal = false
      this.selectedRegistrationForCheckOut = null
      this.resetCheckOutForm()
    },

    // Resetear formulario de check-out
    resetCheckOutForm() {
      this.checkOutForm = {
        notes: '',
        damage_report: '',
        room_cleaned: false,
        keys_returned: false,
        minibar_checked: false,
        client_satisfied: false
      }
    },

    // Verificar si se puede procesar el check-out
    canProcessCheckOut() {
      // Requerir que al menos las llaves hayan sido devueltas
      return this.checkOutForm.keys_returned
    },

    // Obtener fecha y hora actual formateada
    getCurrentDateTime() {
      const now = new Date()
      return now.toLocaleString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      })
    },

    // Procesar check-out
    async processCheckOut() {
      if (!this.canProcessCheckOut()) {
        alert('Debe marcar al menos que las llaves fueron devueltas antes de proceder.')
        return
      }

      this.processingCheckOut = true

      try {
        // Preparar datos para el check-out
        const checkOutData = {
          notes: this.checkOutForm.notes,
          damage_report: this.checkOutForm.damage_report,
          checkout_checklist: {
            room_cleaned: this.checkOutForm.room_cleaned,
            keys_returned: this.checkOutForm.keys_returned,
            minibar_checked: this.checkOutForm.minibar_checked,
            client_satisfied: this.checkOutForm.client_satisfied
          }
        }

        // Llamar al API para procesar el check-out
        const response = await adminApi.checkOutRegistration(
          this.selectedRegistrationForCheckOut.id, 
          checkOutData
        )

        if (response.data.success) {
          // Mostrar mensaje de éxito
          alert('Check-out procesado exitosamente')
          
          // Cerrar modal
          this.closeCheckOutModal()
          
          // Recargar registros para mostrar el estado actualizado
          this.fetchRegistrations()
        } else {
          throw new Error(response.data.message || 'Error al procesar el check-out')
        }
      } catch (error) {
        console.error('Error processing checkout:', error)
        
        let message = 'Error al procesar el check-out'
        
        if (error.response?.data?.errors) {
          // Errores de validación específicos
          const errors = error.response.data.errors
          const errorMessages = []
          
          Object.keys(errors).forEach(field => {
            errorMessages.push(`${field}: ${errors[field].join(', ')}`)
          })
          
          message = 'Errores de validación:\n' + errorMessages.join('\n')
        } else if (error.response?.data?.message) {
          message = error.response.data.message
        } else if (error.message) {
          message = error.message
        }
        
        alert(message)
      } finally {
        this.processingCheckOut = false
      }
    },
    
    // Modal de registro directo
    openDirectRegistrationModal() {
      this.resetDirectRegistrationForm()
      this.showDirectRegistrationModal = true
    },
    
    resetDirectRegistrationForm() {
      this.directRegistration.step = 1
      this.directRegistration.form = {
        user_id: null,
        branch_id: '',
        room_id: '',
        expected_checkout: '',
        adults_count: 1,
        children_count: 0,
        needs_parking: false,
        additional_guests: [],
        notes: ''
      }
      this.vehicleInfo = {
        model: '',
        license_plate: ''
      }
      this.userSearch = ''
      this.foundUsers = []
      this.availableRooms = []
      this.selectedUser = null
      this.selectedRoom = null
      this.showNewUserForm = false
      this.newUser = {
        name: '',
        email: '',
        document_type: '',
        document_number: '',
        phone: ''
      }
    },
    
    // Búsqueda de usuarios
    async searchUsers() {
      if (this.userSearch.length < 2) {
        this.foundUsers = []
        return
      }
      
      try {
        const response = await adminApi.getUsers({
          search: this.userSearch,
          per_page: 5
        })
        
        if (response.data.success) {
          this.foundUsers = response.data.data || []
        }
      } catch (error) {
        console.error('Error searching users:', error)
      }
    },
    
    selectUser(user) {
      this.directRegistration.form.user_id = user.id
      this.selectedUser = user
    },
    
    // Cargar habitaciones disponibles
    async loadAvailableRooms() {
      if (!this.directRegistration.form.branch_id) {
        this.availableRooms = []
        return
      }
      
      try {
        const response = await adminApi.getRooms({
          branch_id: this.directRegistration.form.branch_id,
          status: 'available'
        })
        
        // La respuesta tiene estructura paginada: { success: true, data: { data: [...] } }
        if (response.data.success && response.data.data && response.data.data.data) {
          // Estructura paginada: response.data.data.data contiene el array real
          this.availableRooms = response.data.data.data
        } else if (response.data.data && Array.isArray(response.data.data)) {
          // Array directo
          this.availableRooms = response.data.data
        } else if (response.data.rooms) {
          // Estructura con campo 'rooms'
          this.availableRooms = response.data.rooms
        } else {
          this.availableRooms = []
        }
      } catch (error) {
        console.error('Error loading available rooms:', error)
        this.availableRooms = []
      }
    },
    
    selectRoom(room) {
      this.directRegistration.form.room_id = room.id
      this.selectedRoom = room
    },
    
    // Navegación entre pasos
    nextStep() {
      if (this.canProceedToNextStep()) {
        this.directRegistration.step++
      }
    },
    
    canProceedToNextStep() {
      switch (this.directRegistration.step) {
        case 1:
          return !!(this.directRegistration.form.user_id || this.isNewUserFormValid())
        case 2:
          return !!(this.directRegistration.form.branch_id && 
                   this.directRegistration.form.room_id && 
                   this.directRegistration.form.expected_checkout)
        default:
          return false
      }
    },
    
    isNewUserFormValid() {
      if (!this.showNewUserForm) {
        return false
      }
      
      return !!(this.newUser.name && 
               this.newUser.email && 
               this.newUser.document_type && 
               this.newUser.document_number)
    },
    
    canSubmit() {
      const hasUser = !!(this.directRegistration.form.user_id || this.isNewUserFormValid())
      const hasBranch = !!this.directRegistration.form.branch_id
      const hasRoom = !!this.directRegistration.form.room_id
      const hasCheckout = !!this.directRegistration.form.expected_checkout
      const hasAdults = this.directRegistration.form.adults_count > 0
      const hasVehicleInfo = !this.directRegistration.form.needs_parking || 
                            (this.vehicleInfo.model && this.vehicleInfo.license_plate)
      
      return hasUser && hasBranch && hasRoom && hasCheckout && hasAdults && hasVehicleInfo
    },
    
    // Huéspedes adicionales
    addAdditionalGuest() {
      this.directRegistration.form.additional_guests.push({
        name: '',
        document_type: '',
        document_number: ''
      })
    },
    
    removeAdditionalGuest(index) {
      this.directRegistration.form.additional_guests.splice(index, 1)
    },
    
    // Crear registro directo
    async submitDirectRegistration() {
      if (!this.canSubmit()) return
      
      this.submitting = true
      
      try {
        // Preparar datos para el registro directo
        const registrationData = {
          ...this.directRegistration.form
        }

        // Si hay un nuevo usuario, incluir sus datos en lugar de crear por separado
        if (this.showNewUserForm && this.isNewUserFormValid() && !this.directRegistration.form.user_id) {
          registrationData.new_user = {
            name: this.newUser.name,
            email: this.newUser.email,
            document_type: this.newUser.document_type,
            document_number: this.newUser.document_number,
            phone: this.newUser.phone || null
          }
          // Quitar user_id para que el backend sepa que debe crear usuario
          delete registrationData.user_id
        }

        // Agregar información del vehículo si es necesario (solo para validación en frontend)
        if (this.directRegistration.form.needs_parking) {
          registrationData.vehicle_info = {
            model: this.vehicleInfo.model,
            license_plate: this.vehicleInfo.license_plate
          }
        }
        
        console.log('Creating direct registration with data:', registrationData)
        
        const response = await adminApi.createDirectRegistration(registrationData)
        
        if (response.data.success) {
          // Mostrar mensaje de éxito
          let message = 'Registro directo creado exitosamente'
          if (response.data.data.user_created) {
            message += '\n(Se creó un nuevo usuario)'
          }
          if (this.directRegistration.form.needs_parking) {
            message += `\n(Vehículo registrado: ${this.vehicleInfo.model} - ${this.vehicleInfo.license_plate})`
          }
          alert(message)
          
          // Cerrar modal
          this.showDirectRegistrationModal = false
          
          // Recargar registros
          this.fetchRegistrations()
        } else {
          throw new Error(response.data.message || 'Error al crear el registro')
        }
      } catch (error) {
        console.error('Error creating direct registration:', error)
        let message = 'Error al crear el registro directo'
        
        if (error.response?.data?.errors) {
          // Errores de validación específicos
          const errors = error.response.data.errors
          const errorMessages = []
          
          Object.keys(errors).forEach(field => {
            errorMessages.push(`${field}: ${errors[field].join(', ')}`)
          })
          
          message = 'Errores de validación:\n' + errorMessages.join('\n')
        } else if (error.response?.data?.message) {
          message = error.response.data.message
        } else if (error.message) {
          message = error.message
        }
        
        // Mostrar información adicional de debug si está disponible
        if (error.response?.data?.debug) {
          console.error('Debug info:', error.response.data.debug)
        }
        
        alert(message)
      } finally {
        this.submitting = false
      }
    },
    
    // Ver detalles del registro
    async viewRegistration(registration) {
      try {
        const response = await adminApi.getRegistration(registration.id)
        if (response.data.success) {
          this.selectedRegistration = response.data.data.registration
          this.showRegistrationDetailsModal = true
        }
      } catch (error) {
        console.error('Error fetching registration details:', error)
        alert('Error al cargar detalles del registro')
      }
    },
    
    // Utilidades de formato
    formatDateTime(dateTime) {
      if (!dateTime) return '-'
      
      const date = new Date(dateTime)
      return date.toLocaleString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      })
    },

    formatDate(dateTime) {
      if (!dateTime) return '-'
      
      const date = new Date(dateTime)
      return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
      })
    },

    formatTime(dateTime) {
      if (!dateTime) return '-'
      
      const date = new Date(dateTime)
      return date.toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    
    getStatusClass(status) {
      const classes = {
        active: 'badge bg-success',
        completed: 'badge bg-secondary',
        cancelled: 'badge bg-danger'
      }
      return classes[status] || 'badge bg-warning'
    },
    
    getStatusText(status) {
      const texts = {
        active: 'Activo',
        completed: 'Completado',
        cancelled: 'Cancelado'
      }
      return texts[status] || status
    },
    
    getTotalGuests(registration) {
      const additional = registration.additional_guests?.length || 0
      return additional + 1 // +1 por el huésped principal
    },
    
    getTotalGuestsCount() {
      return this.directRegistration.form.adults_count + 
             this.directRegistration.form.children_count +
             this.directRegistration.form.additional_guests.length
    },
    
    getSelectedUserName() {
      if (this.selectedUser) {
        return this.selectedUser.name
      }
      if (this.showNewUserForm && this.newUser.name) {
        return this.newUser.name
      }
      // Buscar en foundUsers si hay un user_id seleccionado
      if (this.directRegistration.form.user_id && this.foundUsers.length > 0) {
        const foundUser = this.foundUsers.find(user => user.id === this.directRegistration.form.user_id)
        if (foundUser) {
          return foundUser.name
        }
      }
      return 'No seleccionado'
    },
    
    getSelectedRoomInfo() {
      if (this.selectedRoom) {
        return `${this.selectedRoom.room_number} (${this.selectedRoom.room_type?.name || 'Sin tipo'})`
      }
      // Buscar en availableRooms si hay un room_id seleccionado
      if (this.directRegistration.form.room_id && this.availableRooms.length > 0) {
        const foundRoom = this.availableRooms.find(room => room.id === this.directRegistration.form.room_id)
        if (foundRoom) {
          return `${foundRoom.room_number} (${foundRoom.room_type?.name || 'Sin tipo'})`
        }
      }
      return 'No seleccionada'
    }
  }
}
</script>

<style scoped>
/* Variables CSS */
:root {
  --primary-color: #0d6efd;
  --success-color: #198754;
  --warning-color: #ffc107;
  --danger-color: #dc3545;
  --info-color: #0dcaf0;
  --secondary-color: #6c757d;
  --light-gray: #f8f9fa;
  --border-color: #dee2e6;
  --text-muted: #6c757d;
  --shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  --shadow-hover: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* Header Section */
.header-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: 2rem;
  color: white;
  margin-bottom: 2rem;
}

.main-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.subtitle {
  font-size: 1.1rem;
  opacity: 0.9;
  margin-bottom: 0;
}

.btn-create {
  background: rgba(255, 255, 255, 0.2);
  border: 2px solid rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
}

.btn-create:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

/* Stats Cards */
.stats-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: var(--shadow);
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stats-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-hover);
}

.stats-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
}

.stats-content h4 {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  color: #2c3e50;
}

.stats-content p {
  color: var(--text-muted);
  font-size: 0.9rem;
  margin-bottom: 0;
}

/* Modern Cards */
.modern-card {
  border: none;
  border-radius: 12px;
  box-shadow: var(--shadow);
  transition: all 0.3s ease;
}

.modern-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-hover);
}

.modern-card .card-header {
  border-radius: 12px 12px 0 0 !important;
  border-bottom: none;
  padding: 1rem 1.5rem;
}

.modern-card .card-body {
  padding: 1.5rem;
}

/* Tabla de registros */
.registration-table {
  font-size: 0.9rem;
}

.table-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-bottom: 2px solid var(--border-color);
}

.table-header th {
  font-weight: 600;
  color: #2c3e50;
  padding: 1rem 0.75rem;
  border-bottom: none;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.registration-row {
  transition: all 0.3s ease;
  border-bottom: 1px solid var(--light-gray);
}

.registration-row:hover {
  background-color: rgba(13, 110, 253, 0.05);
  transform: translateY(-1px);
}

.registration-row td {
  padding: 1rem 0.75rem;
  vertical-align: middle;
  border-bottom: 1px solid var(--light-gray);
}

/* Registration Code */
.registration-code {
  font-size: 0.9rem;
  color: #2c3e50;
  font-weight: 600;
}

.reservation-tag {
  margin-top: 0.25rem;
  color: var(--info-color);
  font-size: 0.75rem;
}

/* Client Info */
.client-info {
  line-height: 1.4;
}

.client-name {
  font-size: 0.9rem;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.client-details {
  color: var(--text-muted);
  font-size: 0.8rem;
}

/* Room Info */
.room-info {
  line-height: 1.4;
}

.room-number {
  font-size: 0.9rem;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.room-details {
  color: var(--text-muted);
  font-size: 0.8rem;
}

/* Date Info */
.date-info {
  line-height: 1.4;
}

.date-value {
  font-size: 0.85rem;
  color: #2c3e50;
  margin-bottom: 0.25rem;
}

.time-value {
  font-size: 0.75rem;
  color: var(--text-muted);
}

.stay-duration {
  font-size: 0.8rem;
  color: var(--text-muted);
}

/* Guests Info */
.guests-info {
  text-align: center;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100px;
}

.action-btn {
  min-width: 90px;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.8rem;
  transition: all 0.3s ease;
  border: 2px solid transparent;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  border-color: currentColor;
}

.action-btn.btn-primary {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: white;
}

.action-btn.btn-primary:hover {
  background-color: #0b5ed7;
  border-color: #0b5ed7;
}

.action-btn.btn-info {
  background-color: var(--info-color);
  border-color: var(--info-color);
  color: white;
}

.action-btn.btn-info:hover {
  background-color: #0bb2d4;
  border-color: #0bb2d4;
}

.action-btn.btn-warning {
  background-color: var(--warning-color);
  border-color: var(--warning-color);
  color: #212529;
}

.action-btn.btn-warning:hover {
  background-color: #e0a800;
  border-color: #e0a800;
}

.action-btn.btn-success {
  background-color: var(--success-color);
  border-color: var(--success-color);
  color: white;
}

.action-btn.btn-success:hover {
  background-color: #157347;
  border-color: #157347;
}

.action-btn i {
  font-size: 0.85rem;
}

.btn-text {
  font-size: 0.8rem;
  font-weight: 600;
  letter-spacing: 0.3px;
}

.action-buttons .dropdown-menu {
  border: none;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  border-radius: 8px;
}

.action-buttons .dropdown-item {
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
  transition: all 0.3s ease;
}

.action-buttons .dropdown-item:hover {
  background-color: rgba(13, 110, 253, 0.1);
}

/* Loading Section */
.loading-section {
  background: white;
  border-radius: 12px;
  margin: 2rem 0;
}

/* Empty State */
.empty-state {
  background: white;
  border-radius: 16px;
  padding: 3rem;
  text-align: center;
  margin: 2rem 0;
}

.empty-icon {
  font-size: 4rem;
  color: var(--text-muted);
  margin-bottom: 1.5rem;
}

.empty-title {
  font-size: 1.8rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 1rem;
}

.empty-description {
  font-size: 1.1rem;
  color: var(--text-muted);
  margin-bottom: 2rem;
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
}

.empty-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

/* Pagination */
.pagination-section {
  background: white;
  border-radius: 12px;
  padding: 1rem 1.5rem;
  box-shadow: var(--shadow);
}

.pagination-info {
  color: var(--text-muted);
  font-size: 0.9rem;
}

.pagination .page-link {
  border-radius: 8px;
  margin: 0 2px;
  border: 1px solid var(--border-color);
  padding: 0.5rem 0.75rem;
  font-weight: 500;
  transition: all 0.3s ease;
}

.pagination .page-link:hover {
  background-color: var(--primary-color);
  color: white;
  transform: translateY(-1px);
}

.pagination .page-item.active .page-link {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
}

/* Forms */
.form-control,
.form-select {
  border-radius: 8px;
  border: 1px solid var(--border-color);
  padding: 0.75rem 1rem;
  transition: all 0.3s ease;
}

.form-control:focus,
.form-select:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.form-label {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

/* Filter Actions */
.filter-actions {
  display: flex;
  gap: 0.5rem;
  width: 100%;
}

.filter-btn {
  flex: 1;
  padding: 0.625rem 1rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.85rem;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  border: 2px solid;
  min-height: 42px;
}

.filter-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.filter-btn.btn-outline-secondary {
  color: var(--danger-color);
  border-color: var(--danger-color);
}

.filter-btn.btn-outline-secondary:hover {
  background-color: var(--danger-color);
  border-color: var(--danger-color);
  color: white;
}

.filter-btn.btn-outline-primary {
  color: var(--primary-color);
  border-color: var(--primary-color);
}

.filter-btn.btn-outline-primary:hover {
  background-color: var(--primary-color);
  border-color: var(--primary-color);
  color: white;
}

.filter-btn i {
  font-size: 0.9rem;
}

.filter-btn .btn-text {
  font-size: 0.85rem;
  font-weight: 600;
  letter-spacing: 0.3px;
}

/* Modals */
.modal-content {
  border-radius: 12px;
  border: none;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.modal-header {
  border-bottom: 1px solid var(--border-color);
  padding: 1rem 1.5rem;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  border-top: 1px solid var(--border-color);
  padding: 1rem 1.5rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .header-section {
    padding: 1.5rem;
    text-align: center;
  }

  .main-title {
    font-size: 2rem;
  }

  .btn-create {
    margin-top: 1rem;
  }

  .stats-card {
    margin-bottom: 1rem;
  }

  .registration-table {
    font-size: 0.8rem;
  }

  .table-header th {
    padding: 0.75rem 0.5rem;
    font-size: 0.75rem;
  }

  .registration-row td {
    padding: 0.75rem 0.5rem;
  }

  .action-btn {
    min-width: 70px;
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
  }

  .btn-text {
    font-size: 0.7rem;
  }

  .action-buttons {
    min-height: 80px;
  }

  .filter-actions {
    flex-direction: column;
    gap: 0.5rem;
  }

  .filter-btn {
    min-height: 38px;
    padding: 0.5rem 0.75rem;
    font-size: 0.8rem;
  }

  .filter-btn .btn-text {
    font-size: 0.8rem;
  }

  .empty-actions {
    flex-direction: column;
  }

  .pagination-section {
    flex-direction: column;
    gap: 1rem;
  }
}

@media (max-width: 576px) {
  .table-responsive {
    font-size: 0.75rem;
  }

  .action-btn {
    min-width: 50px;
    padding: 0.25rem 0.375rem;
  }

  .btn-text {
    display: none;
  }

  .action-btn i {
    font-size: 0.9rem;
  }

  .action-buttons {
    min-height: 70px;
  }

  .filter-btn .btn-text {
    display: none;
  }

  .filter-btn {
    min-height: 36px;
    padding: 0.375rem 0.5rem;
  }

  .filter-btn i {
    font-size: 1rem;
  }
}

/* Utility Classes */
.cursor-pointer {
  cursor: pointer;
}

.text-truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.rounded-3 {
  border-radius: 12px !important;
}

/* Tabla responsive mejorada */
@media (max-width: 992px) {
  .registration-table th:nth-child(6),
  .registration-table td:nth-child(6) {
    display: none;
  }
}

@media (max-width: 768px) {
  .registration-table th:nth-child(4),
  .registration-table td:nth-child(4),
  .registration-table th:nth-child(5),
  .registration-table td:nth-child(5) {
    display: none;
  }
}

@media (max-width: 576px) {
  .registration-table th:nth-child(7),
  .registration-table td:nth-child(7) {
    display: none;
  }
}
</style>