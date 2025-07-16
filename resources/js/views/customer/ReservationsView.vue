<template>
  <div class="container py-4">
    <div class="row">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2>Mis Reservas</h2>
          <router-link to="/search" class="btn btn-success">
            <i class="pi pi-plus"></i> Nueva Reserva
          </router-link>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Estado</label>
                <select v-model="filters.status" class="form-select" @change="filterReservations">
                  <option value="">Todos</option>
                  <option value="pending_payment">Pendiente de Pago</option>
                  <option value="payment_submitted">Pago Enviado</option>
                  <option value="confirmed">Confirmada</option>
                  <option value="checked_in">Check-in</option>
                  <option value="completed">Completada</option>
                  <option value="cancelled">Cancelada</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Desde</label>
                <input v-model="filters.from_date" type="date" class="form-control" @change="filterReservations">
              </div>
              <div class="col-md-4">
                <label class="form-label">Hasta</label>
                <input v-model="filters.to_date" type="date" class="form-control" @change="filterReservations">
              </div>
            </div>
          </div>
        </div>

        <!-- Lista de reservas -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
        </div>

        <div v-else-if="filteredReservations.length > 0" class="row">
          <div v-for="reservation in filteredReservations" :key="reservation.id" class="col-lg-6 col-xl-4 mb-4">
            <div class="card h-100 shadow-hover">
              <div class="card-header d-flex justify-content-between align-items-center">
                <strong>{{ reservation.reservation_code }}</strong>
                <span :class="getStatusClass(reservation.status)">
                  {{ reservation.status_label }}
                </span>
              </div>
              
              <div class="card-body">
                <div class="mb-3">
                  <h6 class="card-title">
                    {{ reservation.room.type }} - {{ reservation.room.room_number }}
                  </h6>
                  <p class="text-muted small mb-0">{{ reservation.branch.name }}</p>
                </div>

                <div class="mb-3">
                  <div class="d-flex justify-content-between">
                    <span>Check-in:</span>
                    <strong>{{ formatDate(reservation.check_in_date) }}</strong>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span>Check-out:</span>
                    <strong>{{ formatDate(reservation.check_out_date) }}</strong>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span>Huéspedes:</span>
                    <span>{{ reservation.total_guests }}</span>
                  </div>
                </div>

                <div class="mb-3">
                  <div class="d-flex justify-content-between">
                    <span>Total:</span>
                    <strong class="text-success">{{ reservation.formatted_total }}</strong>
                  </div>
                </div>

                <div class="d-flex gap-2">
                  <router-link 
                    :to="`/customer/reservations/${reservation.id}`" 
                    class="btn btn-sm btn-outline-primary flex-fill"
                  >
                    <i class="pi pi-eye"></i> Ver
                  </router-link>
                  
                  <button 
                    v-if="canUploadPayment(reservation)"
                    @click="openPaymentModal(reservation)"
                    class="btn btn-sm btn-outline-success flex-fill"
                  >
                    <i class="pi pi-upload"></i> Pagar
                  </button>
                  
                  <button 
                    v-if="canCancel(reservation)"
                    @click="cancelReservation(reservation)"
                    class="btn btn-sm btn-outline-danger flex-fill"
                  >
                    <i class="pi pi-times"></i> Cancelar
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-5">
          <i class="pi pi-calendar" style="font-size: 4rem; color: #6c757d;"></i>
          <h4 class="mt-3 text-muted">No hay reservas</h4>
          <p class="text-muted">No tienes reservas que coincidan con los filtros seleccionados.</p>
          <router-link to="/search" class="btn btn-success">
            <i class="pi pi-plus"></i> Crear Primera Reserva
          </router-link>
        </div>
      </div>
    </div>

    <!-- Modal de pago -->
    <div v-if="selectedReservation" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Subir Comprobante de Pago</h5>
            <button type="button" class="btn-close" @click="closePaymentModal"></button>
          </div>
          <div class="modal-body">
            <PaymentUploadForm 
              :reservation="selectedReservation"
              @success="onPaymentSuccess"
              @cancel="closePaymentModal"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { customerApi } from '../../services/api.js'
import PaymentUploadForm from '../../components/customer/PaymentUploadForm.vue'

export default {
  name: 'CustomerReservationsView',
  components: {
    PaymentUploadForm
  },
  setup() {
    const reservations = ref([])
    const selectedReservation = ref(null)
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

    const loadReservations = async () => {
      try {
        loading.value = true
        const response = await customerApi.getReservations()
        reservations.value = response.data.reservations
      } catch (error) {
        console.error('Error loading reservations:', error)
      } finally {
        loading.value = false
      }
    }

    const formatDate = (dateString) => {
      const date = new Date(dateString)
      return date.toLocaleDateString('es-BO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }

    const getStatusClass = (status) => {
      const classes = {
        'pending_payment': 'badge bg-warning',
        'payment_submitted': 'badge bg-info',
        'confirmed': 'badge bg-success',
        'checked_in': 'badge bg-primary',
        'completed': 'badge bg-secondary',
        'cancelled': 'badge bg-danger'
      }
      return classes[status] || 'badge bg-secondary'
    }

    const canUploadPayment = (reservation) => {
      return reservation.status === 'pending_payment' && reservation.payment_method === 'qr'
    }

    const canCancel = (reservation) => {
      return ['pending_payment', 'payment_submitted'].includes(reservation.status)
    }

    const openPaymentModal = (reservation) => {
      selectedReservation.value = reservation
    }

    const closePaymentModal = () => {
      selectedReservation.value = null
    }

    const onPaymentSuccess = () => {
      closePaymentModal()
      loadReservations()
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

    const filterReservations = () => {
      // Los filtros se aplican automáticamente mediante computed
    }

    onMounted(() => {
      loadReservations()
    })

    return {
      reservations,
      filteredReservations,
      selectedReservation,
      loading,
      filters,
      formatDate,
      getStatusClass,
      canUploadPayment,
      canCancel,
      openPaymentModal,
      closePaymentModal,
      onPaymentSuccess,
      cancelReservation,
      filterReservations
    }
  }
}
</script>

<style scoped>
.shadow-hover {
  transition: all 0.2s ease-in-out;
}

.shadow-hover:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
  transform: translateY(-2px);
}

.modal.show {
  display: block !important;
}

.text-success {
  color: #28a745 !important;
}

.btn-success {
  background-color: #28a745;
  border-color: #28a745;
}

.btn-success:hover {
  background-color: #218838;
  border-color: #1e7e34;
}
</style>