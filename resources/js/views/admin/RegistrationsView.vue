<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Registros Check-in/Check-out</h4>
            <button 
              class="btn btn-primary"
              @click="openDirectRegistrationModal"
              :disabled="loading"
            >
              <i class="fas fa-plus"></i> Registro Directo
            </button>
          </div>
          
          <div class="card-body">
            <!-- Filtros -->
            <div class="row mb-3">
              <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select 
                  v-model="filters.status" 
                  class="form-select"
                  @change="fetchRegistrations"
                >
                  <option value="">Todos los estados</option>
                  <option value="active">Activo</option>
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
              
              <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <input 
                  v-model="filters.search"
                  type="text" 
                  class="form-control"
                  placeholder="Código, cliente, habitación..."
                  @input="debounceSearch"
                >
              </div>
              
              <div class="col-md-3 d-flex align-items-end">
                <button 
                  class="btn btn-outline-secondary me-2"
                  @click="clearFilters"
                >
                  <i class="fas fa-times"></i> Limpiar
                </button>
                <button 
                  class="btn btn-outline-info"
                  @click="fetchStats"
                >
                  <i class="fas fa-chart-bar"></i> Stats
                </button>
              </div>
            </div>

            <!-- Estadísticas rápidas -->
            <div class="row mb-3" v-if="showStats && stats">
              <div class="col-md-3">
                <div class="card bg-primary text-white">
                  <div class="card-body text-center">
                    <h5>{{ stats.general.total_registrations }}</h5>
                    <small>Total Registros</small>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-success text-white">
                  <div class="card-body text-center">
                    <h5>{{ stats.general.active_registrations }}</h5>
                    <small>Activos</small>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-info text-white">
                  <div class="card-body text-center">
                    <h5>{{ stats.general.completed_registrations }}</h5>
                    <small>Completados</small>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card bg-warning text-white">
                  <div class="card-body text-center">
                    <h5>{{ stats.general.avg_stay_duration }}</h5>
                    <small>Promedio días</small>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Loading state -->
            <div v-if="loading" class="text-center p-4">
              <div class="spinner-border" role="status">
                <span class="visually-hidden">Cargando...</span>
              </div>
            </div>
            
            <!-- Error state -->
            <div v-if="error" class="alert alert-danger">
              <i class="fas fa-exclamation-triangle"></i>
              {{ error }}
              <button class="btn btn-sm btn-outline-danger ms-2" @click="fetchRegistrations">
                Reintentar
              </button>
            </div>
            
            <!-- Tabla de registros -->
            <div class="table-responsive" v-if="!loading">
              <table class="table table-striped table-hover">
                <thead class="table-dark">
                  <tr>
                    <th>Código</th>
                    <th>Reserva</th>
                    <th>Cliente</th>
                    <th>Habitación</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Estado</th>
                    <th>Huéspedes</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="registrations.length === 0">
                    <td colspan="9" class="text-center py-4">
                      <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                      <p class="text-muted">No hay registros disponibles</p>
                    </td>
                  </tr>
                  
                  <tr v-for="registration in registrations" :key="registration.id">
                    <td>
                      <strong>{{ registration.registration_code }}</strong>
                    </td>
                    <td>
                      <span v-if="registration.reservation" class="badge bg-info">
                        {{ registration.reservation.reservation_code }}
                      </span>
                      <span v-else class="badge bg-warning">Sin reserva</span>
                    </td>
                    <td>
                      <div>
                        <strong>{{ registration.user.name }}</strong>
                        <br>
                        <small class="text-muted">{{ registration.user.email }}</small>
                      </div>
                    </td>
                    <td>
                      <span class="badge bg-secondary">
                        {{ registration.room.room_number }}
                      </span>
                      <br>
                      <small>{{ registration.room.room_type?.name }}</small>
                    </td>
                    <td>
                      {{ formatDateTime(registration.actual_check_in) }}
                    </td>
                    <td>
                      <span v-if="registration.actual_check_out">
                        {{ formatDateTime(registration.actual_check_out) }}
                      </span>
                      <span v-else class="text-muted">-</span>
                    </td>
                    <td>
                      <span 
                        :class="getStatusClass(registration.status)"
                        class="badge"
                      >
                        {{ getStatusText(registration.status) }}
                      </span>
                    </td>
                    <td>
                      <span class="badge bg-light text-dark">
                        {{ getTotalGuests(registration) }}
                      </span>
                    </td>
                    <td>
                      <div class="btn-group" role="group">
                        <button 
                          class="btn btn-sm btn-outline-primary"
                          @click="viewRegistration(registration)"
                          title="Ver detalles"
                        >
                          <i class="fas fa-eye"></i>
                        </button>
                        
                        <button 
                          v-if="registration.status === 'active'"
                          class="btn btn-sm btn-outline-warning"
                          @click="editRegistration(registration)"
                          title="Editar"
                        >
                          <i class="fas fa-edit"></i>
                        </button>

                        <!-- Botón de Check-out -->
                        <button 
                          v-if="registration.status === 'active'"
                          class="btn btn-sm btn-outline-success"
                          @click="openCheckOutModal(registration)"
                          title="Check-out"
                        >
                          <i class="fas fa-sign-out-alt"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <!-- Paginación -->
            <nav v-if="pagination && pagination.last_page > 1" class="mt-3">
              <ul class="pagination justify-content-center">
                <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                  <button 
                    class="page-link"
                    @click="changePage(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                  >
                    Anterior
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
                    Siguiente
                  </button>
                </li>
              </ul>
            </nav>
          </div>
        </div>
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
                    Llaves devueltas
                  </label>
                </div>
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
                    Cliente satisfecho con la estadía
                  </label>
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
            <h5 class="modal-title">Registro Directo</h5>
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
                      Necesita estacionamiento (+Bs. 10/noche)
                    </label>
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
            <h5 class="modal-title">Detalles del Registro</h5>
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

    // Calcular duración de la estadía
    getStayDuration(checkInDateTime) {
      if (!checkInDateTime) return 'No disponible'
      
      const checkIn = new Date(checkInDateTime)
      const now = new Date()
      const diffTime = Math.abs(now - checkIn)
      const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
      const diffHours = Math.floor((diffTime % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
      const diffMinutes = Math.floor((diffTime % (1000 * 60 * 60)) / (1000 * 60))
      
      if (diffDays > 0) {
        return `${diffDays} día(s), ${diffHours} hora(s)`
      } else if (diffHours > 0) {
        return `${diffHours} hora(s), ${diffMinutes} minuto(s)`
      } else {
        return `${diffMinutes} minuto(s)`
      }
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
      
      return hasUser && hasBranch && hasRoom && hasCheckout && hasAdults
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
      
      console.log('Creating direct registration with data:', registrationData)
      
      const response = await adminApi.createDirectRegistration(registrationData)
      
      if (response.data.success) {
        // Mostrar mensaje de éxito
        let message = 'Registro directo creado exitosamente'
        if (response.data.data.user_created) {
          message += '\n(Se creó un nuevo usuario)'
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
    
    // Editar registro
    editRegistration(registration) {
      // TODO: Implementar modal de edición
      console.log('Edit registration:', registration)
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
    
    getStatusClass(status) {
      const classes = {
        active: 'bg-success',
        completed: 'bg-secondary',
        cancelled: 'bg-danger'
      }
      return classes[status] || 'bg-warning'
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
.cursor-pointer {
  cursor: pointer;
}

.card:hover {
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.list-group-item:hover {
  background-color: #f8f9fa;
}

.table th {
  border-top: none;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

.modal-lg {
  max-width: 900px;
}

.badge {
  font-size: 0.75em;
}

.btn-group .btn {
  margin-right: 2px;
}

.alert {
  border-radius: 8px;
}

.card {
  border-radius: 10px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-control:focus,
.form-select:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Estilos específicos para el modal de check-out */
.modal-header .modal-title i {
  font-size: 1.2em;
}

.card.bg-light {
  background-color: #f8f9fa !important;
}

.card.border-success {
  border-color: #198754 !important;
}

.card.border-warning {
  border-color: #ffc107 !important;
}
</style>