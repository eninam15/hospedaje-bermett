<template>
  <div class="container-fluid py-4">
    <div class="row">
      <div class="col-12">
        <!-- Header Section -->
        <div class="header-section mb-4">
          <div class="d-flex justify-content-between align-items-center">
            <div class="header-title">
              <h1 class="main-title">
                <i class="fas fa-calendar-alt me-3"></i>
                Mis Reservas
              </h1>
              <p class="subtitle">Gestiona y consulta todas tus reservas</p>
            </div>
            <router-link to="/search" class="btn btn-success btn-lg btn-create">
              <i class="fas fa-plus me-2"></i>
              Nueva Reserva
            </router-link>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
          <div class="col-md-3 col-6">
            <div class="stats-card">
              <div class="stats-icon bg-primary">
                <i class="fas fa-calendar-check"></i>
              </div>
              <div class="stats-content">
                <h4>{{ getReservationCount('confirmed') }}</h4>
                <p>Confirmadas</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="stats-card">
              <div class="stats-icon bg-warning">
                <i class="fas fa-clock"></i>
              </div>
              <div class="stats-content">
                <h4>{{ getReservationCount('pending_payment') }}</h4>
                <p>Pendientes</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="stats-card">
              <div class="stats-icon bg-success">
                <i class="fas fa-check-circle"></i>
              </div>
              <div class="stats-content">
                <h4>{{ getReservationCount('completed') }}</h4>
                <p>Completadas</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-6">
            <div class="stats-card">
              <div class="stats-icon bg-info">
                <i class="fas fa-chart-line"></i>
              </div>
              <div class="stats-content">
                <h4>{{ reservations.length }}</h4>
                <p>Total</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section mb-4">
          <div class="card border-0 shadow-sm">
            <div class="card-body">
              <h6 class="card-title mb-3">
                <i class="fas fa-filter me-2"></i>
                Filtros
              </h6>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label">Estado de la Reserva</label>
                  <select v-model="filters.status" class="form-select" @change="filterReservations">
                    <option value="">Todos los estados</option>
                    <option value="pending_payment">Pendiente de Pago</option>
                    <option value="payment_submitted">Pago Enviado</option>
                    <option value="confirmed">Confirmada</option>
                    <option value="checked_in">Check-in Realizado</option>
                    <option value="completed">Completada</option>
                    <option value="cancelled">Cancelada</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Fecha desde</label>
                  <input v-model="filters.from_date" type="date" class="form-control" @change="filterReservations">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Fecha hasta</label>
                  <input v-model="filters.to_date" type="date" class="form-control" @change="filterReservations">
                </div>
              </div>
              <div class="row mt-3" v-if="hasActiveFilters">
                <div class="col-12">
                  <button @click="clearFilters" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times me-1"></i>
                    Limpiar Filtros
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-section">
          <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
              <span class="visually-hidden">Cargando reservas...</span>
            </div>
            <h5 class="mt-3 text-muted">Cargando tus reservas...</h5>
          </div>
        </div>

        <!-- Reservations List -->
        <div v-else-if="filteredReservations.length > 0" class="reservations-section">
          <div class="row">
            <div v-for="reservation in filteredReservations" :key="reservation.id" class="col-lg-6 col-xl-4 mb-4">
              <div class="reservation-card">
                <!-- Card Header -->
                <div class="card-header">
                  <div class="d-flex justify-content-between align-items-center">
                    <div class="reservation-code">
                      <i class="fas fa-hashtag me-1"></i>
                      <strong>{{ reservation.reservation_code }}</strong>
                    </div>
                    <span :class="getStatusClass(reservation.status)">
                      {{ getStatusText(reservation.status) }}
                    </span>
                  </div>
                </div>
                
                <!-- Card Body -->
                <div class="card-body">
                  <!-- Room Info -->
                  <div class="room-info mb-3">
                    <h6 class="room-title">
                      <i class="fas fa-bed me-2"></i>
                      {{ reservation.room?.type || 'Habitación' }} - {{ reservation.room?.room_number || 'N/A' }}
                    </h6>
                    <p class="branch-name">
                      <i class="fas fa-map-marker-alt me-1"></i>
                      {{ reservation.branch?.name || 'N/A' }}
                    </p>
                  </div>

                  <!-- Reservation Details -->
                  <div class="reservation-details mb-3">
                    <div class="detail-row">
                      <div class="detail-item">
                        <i class="fas fa-calendar-check text-success me-2"></i>
                        <span class="detail-label">Check-in:</span>
                        <strong>{{ formatDate(reservation.check_in_date) }}</strong>
                      </div>
                    </div>
                    <div class="detail-row">
                      <div class="detail-item">
                        <i class="fas fa-calendar-times text-danger me-2"></i>
                        <span class="detail-label">Check-out:</span>
                        <strong>{{ formatDate(reservation.check_out_date) }}</strong>
                      </div>
                    </div>
                    <div class="detail-row">
                      <div class="detail-item">
                        <i class="fas fa-users text-info me-2"></i>
                        <span class="detail-label">Huéspedes:</span>
                        <span>{{ reservation.total_guests || `${reservation.adults_count || 1} adulto(s)` }}</span>
                      </div>
                    </div>
                    <div class="detail-row">
                      <div class="detail-item">
                        <i class="fas fa-moon text-primary me-2"></i>
                        <span class="detail-label">Noches:</span>
                        <span>{{ calculateNights(reservation.check_in_date, reservation.check_out_date) }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Payment Info -->
                  <div class="payment-info mb-3">
                    <div class="payment-method">
                      <i class="fas fa-credit-card me-2"></i>
                      <span class="payment-label">Pago:</span>
                      <span class="payment-value">{{ getPaymentMethodText(reservation.payment_method) }}</span>
                    </div>
                    <div class="total-amount">
                      <span class="total-label">Total:</span>
                      <strong class="total-value">{{ reservation.formatted_total || `Bs. ${reservation.total_amount?.toFixed(2) || '0.00'}` }}</strong>
                    </div>
                  </div>

                  <!-- Actions -->
                  <div class="card-actions">
                    <router-link 
                      :to="`/customer/reservations/${reservation.id}`" 
                      class="btn btn-primary btn-action"
                    >
                      <i class="fas fa-eye me-2"></i>
                      Ver Detalles
                    </router-link>
                    
                    <button 
                      v-if="canCancel(reservation)"
                      @click="cancelReservation(reservation)"
                      class="btn btn-outline-danger btn-action"
                    >
                      <i class="fas fa-times me-2"></i>
                      Cancelar
                    </button>
                    
                    <button 
                      v-else-if="canDownloadReceipt(reservation)"
                      @click="downloadReceipt(reservation)"
                      class="btn btn-outline-success btn-action"
                    >
                      <i class="fas fa-download me-2"></i>
                      Descargar Boleta
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
          <div class="text-center py-5">
            <div class="empty-icon">
              <i class="fas fa-calendar-times"></i>
            </div>
            <h3 class="empty-title">{{ hasActiveFilters ? 'No hay reservas que coincidan' : 'No tienes reservas aún' }}</h3>
            <p class="empty-description">
              {{ hasActiveFilters ? 'Intenta modificar los filtros para ver más resultados.' : 'Comienza creando tu primera reserva y disfruta de una experiencia increíble.' }}
            </p>
            <div class="empty-actions">
              <router-link to="/search" class="btn btn-success btn-lg">
                <i class="fas fa-plus me-2"></i>
                {{ hasActiveFilters ? 'Crear Nueva Reserva' : 'Crear Primera Reserva' }}
              </router-link>
              <button v-if="hasActiveFilters" @click="clearFilters" class="btn btn-outline-secondary btn-lg ms-2">
                <i class="fas fa-filter me-2"></i>
                Limpiar Filtros
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { customerApi } from '../../services/api.js'

export default {
  name: 'CustomerReservationsView',
  setup() {
    const reservations = ref([])
    const loading = ref(false)
    
    const filters = ref({
      status: '',
      from_date: '',
      to_date: ''
    })

    const filteredReservations = computed(() => {
      let filtered = [...reservations.value]
      
      if (filters.value.status) {
        filtered = filtered.filter(r => r.status === filters.value.status)
      }
      
      if (filters.value.from_date) {
        filtered = filtered.filter(r => r.check_in_date >= filters.value.from_date)
      }
      
      if (filters.value.to_date) {
        filtered = filtered.filter(r => r.check_out_date <= filters.value.to_date)
      }
      
      return filtered
    })

    const hasActiveFilters = computed(() => {
      return filters.value.status || filters.value.from_date || filters.value.to_date
    })

    const loadReservations = async () => {
      try {
        loading.value = true
        const response = await customerApi.getReservations()
        reservations.value = response.data.reservations || []
      } catch (error) {
        console.error('Error loading reservations:', error)
        reservations.value = []
      } finally {
        loading.value = false
      }
    }

    const getReservationCount = (status) => {
      return reservations.value.filter(r => r.status === status).length
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      const date = new Date(dateString)
      return date.toLocaleDateString('es-BO', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }

    const calculateNights = (checkIn, checkOut) => {
      if (!checkIn || !checkOut) return 0
      const start = new Date(checkIn)
      const end = new Date(checkOut)
      const diffTime = end - start
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      return diffDays > 0 ? diffDays : 0
    }

    const getStatusClass = (status) => {
      const classes = {
        'pending_payment': 'status-badge status-warning',
        'payment_submitted': 'status-badge status-info',
        'confirmed': 'status-badge status-success',
        'checked_in': 'status-badge status-primary',
        'completed': 'status-badge status-secondary',
        'cancelled': 'status-badge status-danger'
      }
      return classes[status] || 'status-badge status-secondary'
    }

    const getStatusText = (status) => {
      const texts = {
        'pending_payment': 'Pendiente de Pago',
        'payment_submitted': 'Pago Enviado',
        'confirmed': 'Confirmada',
        'checked_in': 'Check-in',
        'completed': 'Completada',
        'cancelled': 'Cancelada'
      }
      return texts[status] || 'Desconocido'
    }

    const getPaymentMethodText = (method) => {
      const texts = {
        'qr': 'QR/Transferencia',
        'cash': 'Efectivo',
        'card': 'Tarjeta'
      }
      return texts[method] || 'No especificado'
    }

    const canCancel = (reservation) => {
      return ['pending_payment', 'payment_submitted'].includes(reservation.status)
    }

    const canDownloadReceipt = (reservation) => {
      return ['confirmed', 'checked_in', 'completed'].includes(reservation.status)
    }

    const cancelReservation = async (reservation) => {
      if (!confirm('¿Estás seguro de que quieres cancelar esta reserva?')) {
        return
      }

      try {
        await customerApi.cancelReservation(reservation.id)
        await loadReservations()
        alert('Reserva cancelada exitosamente')
      } catch (error) {
        console.error('Error cancelling reservation:', error)
        alert('Error al cancelar la reserva')
      }
    }

    const downloadReceipt = (reservation) => {
      // Redirigir a la página de detalles donde se puede descargar el PDF
      window.location.href = `/customer/reservations/${reservation.id}`
    }

    const clearFilters = () => {
      filters.value = {
        status: '',
        from_date: '',
        to_date: ''
      }
    }

    const filterReservations = () => {
      // Los filtros se aplican automáticamente mediante computed
    }

    onMounted(() => {
      loadReservations()
    })

    return {
      reservations,
      filteredReservations,
      loading,
      filters,
      hasActiveFilters,
      getReservationCount,
      formatDate,
      calculateNights,
      getStatusClass,
      getStatusText,
      getPaymentMethodText,
      canCancel,
      canDownloadReceipt,
      cancelReservation,
      downloadReceipt,
      clearFilters,
      filterReservations
    }
  }
}
</script>

<style scoped>
/* Variables CSS */
:root {
  --primary-color: #007bff;
  --success-color: #28a745;
  --warning-color: #ffc107;
  --danger-color: #dc3545;
  --info-color: #17a2b8;
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

/* Filters Section */
.filters-section .card {
  border-radius: 12px;
}

.filters-section .card-title {
  color: #2c3e50;
  font-weight: 600;
}

.form-control, .form-select {
  border-radius: 8px;
  border: 1px solid var(--border-color);
  padding: 0.75rem 1rem;
  transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Loading Section */
.loading-section {
  background: white;
  border-radius: 12px;
  margin: 2rem 0;
}

/* Reservation Cards */
.reservation-card {
  background: white;
  border-radius: 16px;
  border: none;
  box-shadow: var(--shadow);
  transition: all 0.3s ease;
  overflow: hidden;
  height: 100%;
}

.reservation-card:hover {
  transform: translateY(-8px);
  box-shadow: var(--shadow-hover);
}

.reservation-card .card-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-bottom: 1px solid var(--border-color);
  padding: 1rem 1.5rem;
}

.reservation-code {
  font-size: 1.1rem;
  color: #2c3e50;
}

.reservation-card .card-body {
  padding: 1.5rem;
}

/* Room Info */
.room-info {
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--light-gray);
}

.room-title {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.branch-name {
  color: var(--text-muted);
  font-size: 0.9rem;
  margin-bottom: 0;
}

/* Reservation Details */
.reservation-details {
  padding: 1rem 0;
  border-bottom: 1px solid var(--light-gray);
}

.detail-row {
  margin-bottom: 0.75rem;
}

.detail-row:last-child {
  margin-bottom: 0;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.detail-label {
  color: var(--text-muted);
  font-size: 0.9rem;
  min-width: 80px;
}

/* Payment Info */
.payment-info {
  padding: 1rem 0;
  border-bottom: 1px solid var(--light-gray);
}

.payment-method {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.payment-label {
  color: var(--text-muted);
  font-size: 0.9rem;
}

.payment-value {
  font-weight: 500;
  color: #2c3e50;
}

.total-amount {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 1.1rem;
}

.total-label {
  color: var(--text-muted);
}

.total-value {
  color: var(--success-color);
  font-weight: 700;
}

/* Status Badges */
.status-badge {
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-warning {
  background: rgba(255, 193, 7, 0.15);
  color: #856404;
}

.status-info {
  background: rgba(23, 162, 184, 0.15);
  color: #0c5460;
}

.status-success {
  background: rgba(40, 167, 69, 0.15);
  color: #155724;
}

.status-primary {
  background: rgba(0, 123, 255, 0.15);
  color: #004085;
}

.status-secondary {
  background: rgba(108, 117, 125, 0.15);
  color: #383d41;
}

.status-danger {
  background: rgba(220, 53, 69, 0.15);
  color: #721c24;
}

/* Card Actions */
.card-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.btn-action {
  flex: 1;
  min-width: 140px;
  border-radius: 8px;
  padding: 0.6rem 1rem;
  font-weight: 600;
  font-size: 0.9rem;
  transition: all 0.3s ease;
}

.btn-action:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
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

  .card-actions {
    flex-direction: column;
  }

  .btn-action {
    width: 100%;
  }

  .empty-actions {
    flex-direction: column;
  }
}

@media (max-width: 576px) {
  .detail-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }

  .detail-label {
    min-width: auto;
  }

  .payment-method {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
}
</style>