<template>
  <div class="admin-reservations py-4">
    <div class="container-xl">
      <!-- Header Section -->
      <div class="page-header mb-4">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h2 class="mb-2">
              <i class="fas fa-calendar-check me-2"></i>
              Gestión de Reservas
            </h2>
            <p class="text-white-50 mb-0">Administra todas las reservas del sistema</p>
          </div>
          <div class="col-md-4 text-md-end">
            <button @click="refreshData" class="btn btn-light me-2" :disabled="loading">
              <i class="fas fa-sync-alt me-1" :class="{ 'fa-spin': loading }"></i>
              Actualizar
            </button>
            <button @click="exportReservations" class="btn btn-success">
              <i class="fas fa-file-excel me-1"></i>
              Exportar
            </button>
          </div>
        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-section mb-4">
        <div class="row g-3">
          <div class="col-lg-3 col-md-6">
            <div class="stat-card">
              <div class="stat-icon bg-primary">
                <i class="fas fa-calendar-check text-white"></i>
              </div>
              <div class="stat-content">
                <h3 class="stat-number">{{ stats.total || 0 }}</h3>
                <p class="stat-label">Total Reservas</p>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6">
            <div class="stat-card">
              <div class="stat-icon bg-warning">
                <i class="fas fa-hourglass-half text-white"></i>
              </div>
              <div class="stat-content">
                <h3 class="stat-number">{{ stats.pending || 0 }}</h3>
                <p class="stat-label">Pago Pendiente</p>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6">
            <div class="stat-card">
              <div class="stat-icon bg-success">
                <i class="fas fa-check-circle text-white"></i>
              </div>
              <div class="stat-content">
                <h3 class="stat-number">{{ stats.confirmed || 0 }}</h3>
                <p class="stat-label">Confirmadas</p>
              </div>
            </div>
          </div>
          <div class="col-lg-3 col-md-6">
            <div class="stat-card">
              <div class="stat-icon bg-info">
                <i class="fas fa-calendar-day text-white"></i>
              </div>
              <div class="stat-content">
                <h3 class="stat-number">{{ stats.today || 0 }}</h3>
                <p class="stat-label">Check-ins Hoy</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions-section mb-4">
        <div class="card">
          <div class="card-header">
            <h5 class="mb-0">
              <i class="fas fa-bolt me-2"></i>
              Acciones Rápidas
            </h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-lg-3 col-md-6">
                <button @click="filterByStatus('pending_payment')" class="quick-action-btn">
                  <i class="fas fa-exclamation-triangle text-warning"></i>
                  <span>Pagos Pendientes</span>
                  <div class="action-count">{{ stats.pending || 0 }}</div>
                </button>
              </div>
              <div class="col-lg-3 col-md-6">
                <button @click="filterByStatus('confirmed')" class="quick-action-btn">
                  <i class="fas fa-check-circle text-success"></i>
                  <span>Listas para Check-in</span>
                  <div class="action-count">{{ stats.confirmed || 0 }}</div>
                </button>
              </div>
              <div class="col-lg-3 col-md-6">
                <button @click="filterByStatus('checked_in')" class="quick-action-btn">
                  <i class="fas fa-sign-in-alt text-primary"></i>
                  <span>En Estadía</span>
                  <div class="action-count">{{ getCheckedInCount() }}</div>
                </button>
              </div>
              <div class="col-lg-3 col-md-6">
                <button @click="filterByTodayCheckouts()" class="quick-action-btn">
                  <i class="fas fa-sign-out-alt text-info"></i>
                  <span>Check-outs Hoy</span>
                  <div class="action-count">{{ getTodayCheckoutsCount() }}</div>
                </button>
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
                <label class="form-label">Estado</label>
                <select v-model="filters.status" class="form-select" @change="applyFilters">
                  <option value="">Todos</option>
                  <option value="pending_payment">Pago Pendiente</option>
                  <option value="confirmed">Confirmada</option>
                  <option value="checked_in">Check-in</option>
                  <option value="completed">Completada</option>
                  <option value="cancelled">Cancelada</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label">Fecha desde</label>
                <input v-model="filters.check_in_date" type="date" class="form-control" @change="applyFilters">
              </div>
              <div class="col-md-2">
                <label class="form-label">Fecha hasta</label>
                <input v-model="filters.check_out_date" type="date" class="form-control" @change="applyFilters">
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
                    placeholder="Código, cliente, habitación..."
                    @input="searchReservations"
                  />
                </div>
              </div>
              <div class="col-md-2">
                <button @click="clearFilters" class="btn btn-outline-secondary w-100">
                  <i class="fas fa-times me-1"></i>
                  Limpiar
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Reservations List -->
      <div class="reservations-section">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
              <i class="fas fa-list me-2"></i>
              Lista de Reservas ({{ filteredReservations.length }})
            </h5>
            <div class="btn-group btn-group-sm">
              <button
                @click="sortBy('check_in_date')"
                class="btn"
                :class="sortField === 'check_in_date' ? 'btn-primary' : 'btn-outline-primary'"
              >
                <i class="fas fa-calendar me-1"></i>
                Fecha
              </button>
              <button
                @click="sortBy('status')"
                class="btn"
                :class="sortField === 'status' ? 'btn-primary' : 'btn-outline-primary'"
              >
                <i class="fas fa-tag me-1"></i>
                Estado
              </button>
              <button
                @click="sortBy('total_amount')"
                class="btn"
                :class="sortField === 'total_amount' ? 'btn-primary' : 'btn-outline-primary'"
              >
                <i class="fas fa-dollar-sign me-1"></i>
                Monto
              </button>
            </div>
          </div>
          <div class="card-body">
            <!-- Loading State -->
            <div v-if="loading" class="text-center py-5">
              <div class="spinner-border text-primary" role="status"></div>
              <p class="text-muted mt-3">Cargando reservas...</p>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="alert alert-danger">
              <i class="fas fa-exclamation-triangle me-2"></i>
              {{ error }}
              <button @click="refreshData" class="btn btn-sm btn-outline-danger ms-2">
                Reintentar
              </button>
            </div>

            <!-- Empty State -->
            <div v-else-if="filteredReservations.length === 0" class="text-center py-5">
              <i class="fas fa-calendar-times display-4 text-muted mb-3"></i>
              <h5 class="text-muted">No se encontraron reservas</h5>
              <p class="text-muted">No hay reservas que coincidan con los filtros seleccionados.</p>
              <button @click="clearFilters" class="btn btn-outline-primary">
                <i class="fas fa-refresh me-1"></i>
                Limpiar Filtros
              </button>
            </div>

            <!-- Reservations List -->
            <div v-else class="reservations-list">
              <div
                v-for="reservation in paginatedReservations"
                :key="reservation.id"
                class="reservation-item"
                :class="{ 
                  urgent: isUrgent(reservation),
                  expanded: expandedReservation === reservation.id 
                }"
              >
                <div class="row align-items-center">
                  <!-- Información Básica -->
                  <div class="col-md-4">
                    <div class="reservation-basic">
                      <h6 class="reservation-code">{{ reservation.reservation_code }}</h6>
                      <p class="guest-name">{{ reservation.user?.name || 'N/A' }}</p>
                      <small class="text-muted">
                        <i class="fas fa-bed me-1"></i>
                        {{ reservation.room?.room_number || 'N/A' }} - {{ reservation.room?.room_type?.name || 'N/A' }}
                      </small>
                    </div>
                  </div>

                  <!-- Fechas -->
                  <div class="col-md-3">
                    <div class="reservation-dates">
                      <div class="date-item">
                        <small class="text-muted">Check-in</small>
                        <div class="date-display">{{ formatDate(reservation.check_in_date) }}</div>
                      </div>
                      <div class="date-item">
                        <small class="text-muted">Check-out</small>
                        <div class="date-display">{{ formatDate(reservation.check_out_date) }}</div>
                      </div>
                    </div>
                  </div>

                  <!-- Información Adicional -->
                  <div class="col-md-2">
                    <div class="reservation-info">
                      <div class="nights-count">
                        <i class="fas fa-moon me-1"></i>
                        {{ reservation.total_nights }} noche{{ reservation.total_nights !== 1 ? 's' : '' }}
                      </div>
                      <div class="guests-count">
                        <i class="fas fa-users me-1"></i>
                        {{ reservation.adults_count + reservation.children_count }} huésped{{ (reservation.adults_count + reservation.children_count) !== 1 ? 'es' : '' }}
                      </div>
                      <div v-if="reservation.needs_parking" class="parking-info">
                        <i class="fas fa-car me-1 text-info"></i>
                        <small>Estacionamiento</small>
                      </div>
                    </div>
                  </div>

                  <!-- Estado y Monto -->
                  <div class="col-md-3">
                    <div class="reservation-status">
                      <span :class="getStatusBadgeClass(reservation.status)" class="mb-2">
                        <i :class="getStatusIcon(reservation.status)" class="me-1"></i>
                        {{ getStatusText(reservation.status) }}
                      </span>
                      <div class="amount-display">${{ formatCurrency(reservation.total_amount) }}</div>
                    </div>
                  </div>
                </div>

                <!-- Fila de Acciones Separada -->
                <div class="row mt-3">
                  <div class="col-12">
                    <div class="actions-container">
                      <div class="action-buttons-row">
                        <!-- Ver Más -->
                        <button
                          @click="toggleExpanded(reservation)"
                          class="btn btn-sm btn-outline-info action-btn"
                          :class="{ 'active': expandedReservation === reservation.id }"
                        >
                          <i :class="expandedReservation === reservation.id ? 'fas fa-eye-slash' : 'fas fa-eye'" class="me-1"></i>
                          {{ expandedReservation === reservation.id ? 'Menos' : 'Más' }}
                        </button>
                        
                        <!-- Check-in -->
                        <button
                          v-if="canCheckIn(reservation)"
                          @click="openCheckInModal(reservation)"
                          class="btn btn-sm btn-primary action-btn"
                        >
                          <i class="fas fa-sign-in-alt me-1"></i>
                          Check-in
                        </button>
                        
                        
                        <!-- Confirmar Pago -->
                        <button
                          v-if="canConfirmPayment(reservation)"
                          @click="confirmPayment(reservation)"
                          class="btn btn-sm btn-success action-btn"
                        >
                          <i class="fas fa-check me-1"></i>
                          Confirmar
                        </button>
                        
                        <!-- Cancelar -->
                        <button
                          v-if="canCancel(reservation)"
                          @click="openCancelModal(reservation)"
                          class="btn btn-sm btn-danger action-btn"
                        >
                          <i class="fas fa-times me-1"></i>
                          Cancelar
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Expanded Details -->
                <div v-if="expandedReservation === reservation.id" class="reservation-expanded mt-3">
                  <div class="row">
                    <div class="col-md-6">
                      <h6><i class="fas fa-user me-2"></i>Información del Cliente</h6>
                      <div class="info-grid">
                        <div class="info-item">
                          <strong>Email:</strong> 
                          <a :href="`mailto:${reservation.user?.email}`">{{ reservation.user?.email || 'N/A' }}</a>
                        </div>
                        <div class="info-item" v-if="reservation.user?.phone">
                          <strong>Teléfono:</strong> 
                          <a :href="`tel:${reservation.user?.phone}`">{{ reservation.user.phone }}</a>
                        </div>
                        <div class="info-item" v-if="reservation.user?.document_number">
                          <strong>{{ getDocumentTypeText(reservation.user?.document_type) }}:</strong> 
                          {{ reservation.user.document_number }}
                        </div>
                        <div class="info-item" v-if="reservation.user?.address">
                          <strong>Dirección:</strong> {{ reservation.user.address }}
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <h6><i class="fas fa-info-circle me-2"></i>Detalles de la Reserva</h6>
                      <div class="info-grid">
                        <div class="info-item">
                          <strong>Creada:</strong> {{ formatDateTime(reservation.created_at) }}
                        </div>
                        <div class="info-item">
                          <strong>Método de Pago:</strong> {{ getPaymentMethodText(reservation.payment_method) }}
                        </div>
                        <div class="info-item" v-if="reservation.special_requests">
                          <strong>Solicitudes Especiales:</strong> {{ reservation.special_requests }}
                        </div>
                        <div class="info-item">
                          <strong>Sucursal:</strong> {{ reservation.branch?.name || 'N/A' }}
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Información de Check-in/out si existe -->
                  <div v-if="reservation.registration" class="registration-info mt-3">
                    <h6><i class="fas fa-clipboard-check me-2"></i>Información de Registro</h6>
                    <div class="row">
                      <div class="col-md-6">
                        <div class="info-grid">
                          <div class="info-item" v-if="reservation.registration.actual_check_in">
                            <strong>Check-in Realizado:</strong> {{ formatDateTime(reservation.registration.actual_check_in) }}
                          </div>
                          <div class="info-item" v-if="reservation.registration.actual_check_out">
                            <strong>Check-out Realizado:</strong> {{ formatDateTime(reservation.registration.actual_check_out) }}
                          </div>
                          <div class="info-item" v-if="reservation.registration.registered_by">
                            <strong>Registrado por:</strong> {{ reservation.registration.registeredBy?.name || 'N/A' }}
                          </div>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="info-grid">
                          <div class="info-item" v-if="reservation.registration.additional_guests && reservation.registration.additional_guests.length > 0">
                            <strong>Huéspedes Adicionales:</strong>
                            <ul class="list-unstyled mt-1">
                              <li v-for="guest in reservation.registration.additional_guests" :key="guest.name">
                                {{ guest.name }} - {{ guest.document_type }}: {{ guest.document_number }}
                              </li>
                            </ul>
                          </div>
                          <div class="info-item" v-if="reservation.registration.notes">
                            <strong>Notas:</strong> {{ reservation.registration.notes }}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="pagination-section mt-4">
              <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted">
                  Mostrando {{ ((currentPage - 1) * itemsPerPage) + 1 }} a 
                  {{ Math.min(currentPage * itemsPerPage, filteredReservations.length) }} 
                  de {{ filteredReservations.length }} reservas
                </div>
                <nav>
                  <ul class="pagination mb-0">
                    <li class="page-item" :class="{ disabled: currentPage === 1 }">
                      <button @click="goToPage(currentPage - 1)" class="page-link" :disabled="currentPage === 1">
                        <i class="fas fa-chevron-left"></i>
                      </button>
                    </li>
                    <li
                      v-for="page in visiblePages"
                      :key="page"
                      class="page-item"
                      :class="{ active: page === currentPage }"
                    >
                      <button @click="goToPage(page)" class="page-link">{{ page }}</button>
                    </li>
                    <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                      <button @click="goToPage(currentPage + 1)" class="page-link" :disabled="currentPage === totalPages">
                        <i class="fas fa-chevron-right"></i>
                      </button>
                    </li>
                  </ul>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Check-in Modal -->
    <CheckInModal
      v-if="showCheckInModal"
      :reservation="selectedReservation"
      @close="closeCheckInModal"
      @success="handleCheckInSuccess"
    />

    <!-- Cancel Modal -->
    <CancelReservationModal
      v-if="showCancelModal"
      :reservation="selectedReservation"
      @close="closeCancelModal"
      @success="handleCancelSuccess"
    />
  </div>
</template>

<script>
import { adminApi, apiHelpers } from '../../services/api'
import CheckInModal from './modals/CheckInModal.vue'
import CancelReservationModal from './modals/CancelReservationModal.vue'

export default {
  name: 'ReservationsView',
  components: {
    CheckInModal,
    CancelReservationModal
  },
  data() {
    return {
      loading: false,
      error: null,
      reservations: [],
      filteredReservations: [],
      stats: {},
      searchTimeout: null,
      expandedReservation: null,
      
      // Modales
      showCheckInModal: false,
      showCancelModal: false,
      selectedReservation: null,
      
      // Filtros
      filters: {
        status: '',
        check_in_date: '',
        check_out_date: '',
        search: ''
      },
      
      // Paginación
      currentPage: 1,
      itemsPerPage: 10,
      
      // Ordenamiento
      sortField: 'created_at',
      sortDirection: 'desc'
    }
  },
  
  computed: {
    paginatedReservations() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredReservations.slice(start, end)
    },
    
    totalPages() {
      return Math.ceil(this.filteredReservations.length / this.itemsPerPage)
    },
    
    visiblePages() {
      const pages = []
      const total = this.totalPages
      const current = this.currentPage
      
      if (total <= 5) {
        for (let i = 1; i <= total; i++) {
          pages.push(i)
        }
      } else {
        if (current <= 3) {
          for (let i = 1; i <= 5; i++) {
            pages.push(i)
          }
        } else if (current >= total - 2) {
          for (let i = total - 4; i <= total; i++) {
            pages.push(i)
          }
        } else {
          for (let i = current - 2; i <= current + 2; i++) {
            pages.push(i)
          }
        }
      }
      return pages
    }
  },
  
  async mounted() {
    console.log('ReservationsView mounted')
    await this.loadReservations()
    await this.loadStats()
  },
  
  beforeUnmount() {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout)
    }
  },
  
  methods: {
    async loadReservations() {
      this.loading = true
      this.error = null
      
      try {
        const params = apiHelpers.formatQueryParams(this.filters)
        const response = await adminApi.getReservations(params)
        this.reservations = response.data.data || response.data
        this.applySort()
        this.filteredReservations = [...this.reservations]
        this.currentPage = 1
      } catch (error) {
        console.error('Error cargando reservas:', error)
        this.error = 'Error al cargar las reservas. Por favor, intenta nuevamente.'
      } finally {
        this.loading = false
      }
    },
    
    async loadStats() {
      try {
        const response = await adminApi.getReservationStats()
        this.stats = response.data.data || response.data
      } catch (error) {
        console.error('Error cargando estadísticas:', error)
      }
    },
    
    async refreshData() {
      await Promise.all([
        this.loadReservations(),
        this.loadStats()
      ])
    },
    
    // Métodos de modales
    openCheckInModal(reservation) {
      this.selectedReservation = reservation
      this.showCheckInModal = true
    },
    
    closeCheckInModal() {
      this.showCheckInModal = false
      this.selectedReservation = null
    },
    
    
    
    openCancelModal(reservation) {
      this.selectedReservation = reservation
      this.showCancelModal = true
    },
    
    closeCancelModal() {
      this.showCancelModal = false
      this.selectedReservation = null
    },
    
    // Handlers de eventos de modales
    async handleCheckInSuccess() {
      this.closeCheckInModal()
      await this.refreshData()
      this.$toast.success('Check-in realizado exitosamente')
    },
    
   
    
    async handleCancelSuccess() {
      this.closeCancelModal()
      await this.refreshData()
      this.$toast.success('Reserva cancelada exitosamente')
    },
    
    // Métodos de filtrado
    filterByStatus(status) {
      this.filters.status = status
      this.applyFilters()
    },
    
    filterByTodayCheckouts() {
      const today = new Date().toISOString().split('T')[0]
      this.filters.check_out_date = today
      this.filters.status = 'checked_in'
      this.applyFilters()
    },
    
    applyFilters() {
      this.loadReservations()
    },
    
    clearFilters() {
      this.filters = {
        status: '',
        check_in_date: '',
        check_out_date: '',
        search: ''
      }
      this.expandedReservation = null
      this.loadReservations()
    },
    
    searchReservations() {
      clearTimeout(this.searchTimeout)
      this.searchTimeout = setTimeout(() => {
        this.applyFilters()
      }, 500)
    },
    
    // Métodos de ordenamiento
    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortField = field
        this.sortDirection = 'asc'
      }
      this.applySort()
    },
    
    applySort() {
      this.filteredReservations.sort((a, b) => {
        let valueA = this.getNestedValue(a, this.sortField)
        let valueB = this.getNestedValue(b, this.sortField)
        
        if (typeof valueA === 'string') {
          valueA = valueA.toLowerCase()
          valueB = valueB.toLowerCase()
        }
        
        const comparison = valueA < valueB ? -1 : valueA > valueB ? 1 : 0
        return this.sortDirection === 'asc' ? comparison : -comparison
      })
    },
    
    getNestedValue(obj, path) {
      return path.split('.').reduce((o, p) => (o ? o[p] : undefined), obj) || ''
    },
    
    // Métodos de paginación
    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page
      }
    },
    
    // Métodos de vista y acciones
    toggleExpanded(reservation) {
      if (this.expandedReservation === reservation.id) {
        this.expandedReservation = null
      } else {
        this.expandedReservation = reservation.id
      }
    },
    
    // Métodos de confirmación de pago
    async confirmPayment(reservation) {
      if (!confirm('¿Confirmar el pago de esta reserva?')) return
      
      try {
        const pendingPayment = reservation.payments?.find(p => p.status === 'pending')
        if (pendingPayment) {
          await adminApi.verifyPayment(pendingPayment.id, {
            notes: 'Pago confirmado por administrador'
          })
          await this.refreshData()
          this.$toast.success('Pago confirmado exitosamente')
        }
      } catch (error) {
        console.error('Error confirmando pago:', error)
        this.$toast.error('Error al confirmar el pago')
      }
    },
    
    async exportReservations() {
      try {
        const response = await adminApi.exportReport('reservations', this.filters)
        const blob = new Blob([response.data], { 
          type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
        })
        apiHelpers.downloadBlob(blob, `reservas_${new Date().toISOString().split('T')[0]}.xlsx`)
      } catch (error) {
        console.error('Error exportando reservas:', error)
        this.$toast.error('Error al exportar las reservas')
      }
    },
    
    // Métodos de validación
    canCheckIn(reservation) {
      // Puede hacer check-in si está confirmada y es hoy o después de la fecha
      if (reservation.status !== 'confirmed') return false
      
      const today = new Date()
      const checkInDate = new Date(reservation.check_in_date)
      
      // Permitir check-in desde el día anterior
      const yesterday = new Date(today)
      yesterday.setDate(yesterday.getDate() - 1)
      
      return checkInDate >= yesterday
    },
    
    canCheckOut(reservation) {
      return reservation.status === 'checked_in'
    },
    
    canConfirmPayment(reservation) {
      return reservation.status === 'pending_payment' && 
             reservation.payments?.some(p => p.status === 'pending')
    },
    
    canCancel(reservation) {
      return ['pending_payment', 'confirmed'].includes(reservation.status)
    },
    
    isUrgent(reservation) {
      if (reservation.status === 'pending_payment') {
        const checkInDate = new Date(reservation.check_in_date)
        const today = new Date()
        const diffDays = (checkInDate - today) / (1000 * 60 * 60 * 24)
        return diffDays <= 1 && diffDays >= 0
      }
      return false
    },
    
    // Métodos de cálculo
    getCheckedInCount() {
      return this.reservations.filter(r => r.status === 'checked_in').length
    },
    
    getTodayCheckoutsCount() {
      const today = new Date().toISOString().split('T')[0]
      return this.reservations.filter(r => 
        r.status === 'checked_in' && r.check_out_date === today
      ).length
    },
    
    // Métodos de formato
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    },
    
    formatDateTime(dateTimeString) {
      if (!dateTimeString) return 'N/A'
      return new Date(dateTimeString).toLocaleString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    
    formatCurrency(amount) {
      if (amount === null || amount === undefined) return '0.00'
      return new Intl.NumberFormat('es-ES', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(parseFloat(amount))
    },
    
    // Métodos de estado y badges
    getStatusBadgeClass(status) {
      const classes = {
        'pending_payment': 'badge bg-warning text-dark',
        'confirmed': 'badge bg-success',
        'checked_in': 'badge bg-primary',
        'completed': 'badge bg-info',
        'cancelled': 'badge bg-danger'
      }
      return classes[status] || 'badge bg-secondary'
    },
    
    getStatusIcon(status) {
      const icons = {
        'pending_payment': 'fas fa-hourglass-half',
        'confirmed': 'fas fa-check-circle',
        'checked_in': 'fas fa-sign-in-alt',
        'completed': 'fas fa-check-double',
        'cancelled': 'fas fa-times-circle'
      }
      return icons[status] || 'fas fa-question-circle'
    },
    
    getStatusText(status) {
      const texts = {
        'pending_payment': 'Pago Pendiente',
        'confirmed': 'Confirmada',
        'checked_in': 'Check-in',
        'completed': 'Completada',
        'cancelled': 'Cancelada'
      }
      return texts[status] || 'Desconocido'
    },
    
    getPaymentMethodText(method) {
      const texts = {
        'qr': 'Código QR',
        'cash': 'Efectivo',
        'card': 'Tarjeta',
        'transfer': 'Transferencia'
      }
      return texts[method] || 'Otro'
    },
    
    getDocumentTypeText(type) {
      const texts = {
        'ci': 'Cédula de Identidad',
        'passport': 'Pasaporte',
        'dni': 'DNI'
      }
      return texts[type] || 'Documento'
    }
  }
}
</script>

<style scoped>
/* Mantenemos los estilos existentes del componente original */
.admin-reservations {
  background-color: #f8f9fa;
  min-height: calc(100vh - 80px);
}

.page-header {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  color: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border: 1px solid #e9ecef;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
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
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border-radius: 12px;
}

.card-header {
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  font-weight: 600;
}

.quick-action-btn {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 1.5rem;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
  text-decoration: none;
  color: inherit;
}

.quick-action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  border-color: #007bff;
  color: inherit;
  text-decoration: none;
}

.quick-action-btn i {
  font-size: 2rem;
}

.action-count {
  background-color: #495057;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.9rem;
}

.reservation-item {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1rem;
  transition: all 0.3s ease;
}

.reservation-item:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  border-color: #007bff;
}

.reservation-item.urgent {
  border-left: 4px solid #dc3545;
  background-color: #fff5f5;
}

.reservation-item.expanded {
  border-color: #007bff;
  box-shadow: 0 4px 16px rgba(0, 123, 255, 0.2);
}

.reservation-code {
  font-weight: 700;
  color: #007bff;
  margin-bottom: 0.25rem;
  font-size: 1.1rem;
}

.guest-name {
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.25rem;
}

.reservation-dates {
  display: flex;
  gap: 1rem;
}

.date-item {
  flex: 1;
}

.date-display {
  font-weight: 600;
  color: #495057;
}

.reservation-info {
  color: #6c757d;
  font-size: 0.9rem;
}

.nights-count,
.guests-count,
.parking-info {
  margin-bottom: 0.25rem;
}

.amount-display {
  font-size: 1.25rem;
  font-weight: 700;
  color: #28a745;
}

.actions-container {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  padding-top: 0.5rem;
  border-top: 1px solid #f1f3f4;
}

.action-buttons-row {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.action-btn {
  border-radius: 6px;
  font-weight: 500;
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
  transition: all 0.2s ease;
  border-width: 1px;
  min-width: 80px;
  white-space: nowrap;
}

.action-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
}

.action-btn.active {
  background-color: #17a2b8;
  border-color: #17a2b8;
  color: white;
}

.reservation-expanded {
  border-top: 1px solid #e9ecef;
  padding-top: 1rem;
  background-color: #f8f9fa;
  margin: 1rem -1.5rem -1.5rem -1.5rem;
  padding: 1.5rem;
  border-radius: 0 0 12px 12px;
}

.info-grid {
  display: grid;
  gap: 0.5rem;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.25rem 0;
}

.registration-info {
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 1rem;
}

.badge {
  font-size: 0.75rem;
  font-weight: 500;
  padding: 0.375rem 0.75rem;
}

.pagination .page-link {
  color: #007bff;
  border-color: #e9ecef;
  border-radius: 8px;
  margin: 0 0.125rem;
}

.pagination .page-item.active .page-link {
  background-color: #007bff;
  border-color: #007bff;
}

@media (max-width: 768px) {
  .page-header {
    text-align: center;
    padding: 1.5rem;
  }

  .stat-card {
    text-align: center;
    flex-direction: column;
  }

  .quick-action-btn {
    padding: 1rem;
  }

  .reservation-dates {
    flex-direction: column;
    gap: 0.5rem;
  }

  .reservation-item {
    padding: 1rem;
  }

  .reservation-expanded {
    margin: 1rem -1rem -1rem -1rem;
    padding: 1rem;
  }

  .actions-container {
    justify-content: center;
  }

  .action-buttons-row {
    justify-content: center;
    width: 100%;
  }

  .action-btn {
    flex: 1;
    font-size: 0.75rem;
    padding: 0.35rem 0.5rem;
    min-width: 60px;
  }
}
</style>