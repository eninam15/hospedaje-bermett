<template>
  <div class="container py-4">
    <div class="row">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2>Mi Dashboard</h2>
          <span class="text-muted">Bienvenido, {{ authStore.userName }}</span>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="row mb-4">
          <div class="col-md-3 mb-3">
            <div class="card text-center">
              <div class="card-body">
                <i class="pi pi-calendar text-primary mb-2" style="font-size: 2rem;"></i>
                <h4 class="text-primary">{{ stats.totalReservations }}</h4>
                <p class="text-muted mb-0">Reservas Totales</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="card text-center">
              <div class="card-body">
                <i class="pi pi-check-circle text-success mb-2" style="font-size: 2rem;"></i>
                <h4 class="text-success">{{ stats.confirmedReservations }}</h4>
                <p class="text-muted mb-0">Confirmadas</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="card text-center">
              <div class="card-body">
                <i class="pi pi-clock text-warning mb-2" style="font-size: 2rem;"></i>
                <h4 class="text-warning">{{ stats.pendingReservations }}</h4>
                <p class="text-muted mb-0">Pendientes</p>
              </div>
            </div>
          </div>
          <div class="col-md-3 mb-3">
            <div class="card text-center">
              <div class="card-body">
                <i class="pi pi-star text-info mb-2" style="font-size: 2rem;"></i>
                <h4 class="text-info">{{ stats.completedStays }}</h4>
                <p class="text-muted mb-0">Estadías</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Acciones rápidas -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Acciones Rápidas</h5>
                <div class="row">
                  <div class="col-md-6 col-lg-3 mb-3">
                    <router-link to="/search" class="btn btn-primary w-100">
                      <i class="pi pi-search"></i> Nueva Reserva
                    </router-link>
                  </div>
                  <div class="col-md-6 col-lg-3 mb-3">
                    <router-link to="/customer/reservations" class="btn btn-outline-primary w-100">
                      <i class="pi pi-list"></i> Mis Reservas
                    </router-link>
                  </div>
                  <div class="col-md-6 col-lg-3 mb-3">
                    <button class="btn btn-outline-info w-100" @click="loadData">
                      <i class="pi pi-refresh"></i> Actualizar
                    </button>
                  </div>
                  <div class="col-md-6 col-lg-3 mb-3">
                    <button class="btn btn-outline-secondary w-100">
                      <i class="pi pi-user"></i> Mi Perfil
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Reservas recientes -->
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="card-title mb-0">Reservas Recientes</h5>
                  <router-link to="/customer/reservations" class="btn btn-sm btn-outline-primary">
                    Ver todas
                  </router-link>
                </div>

                <div v-if="loading" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                  </div>
                </div>

                <div v-else-if="recentReservations.length > 0" class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Código</th>
                        <th>Habitación</th>
                        <th>Fechas</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="reservation in recentReservations" :key="reservation.id">
                        <td>
                          <strong>{{ reservation.reservation_code }}</strong>
                        </td>
                        <td>
                          <div>
                            <strong>{{ reservation.room.type }} - {{ reservation.room.room_number }}</strong>
                            <br>
                            <small class="text-muted">{{ reservation.branch.name }}</small>
                          </div>
                        </td>
                        <td>
                          <div>
                            <strong>{{ formatDate(reservation.check_in_date) }}</strong>
                            <br>
                            <small class="text-muted">{{ formatDate(reservation.check_out_date) }}</small>
                          </div>
                        </td>
                        <td>
                          <span :class="getStatusClass(reservation.status)">
                            {{ reservation.status_label }}
                          </span>
                        </td>
                        <td>
                          <strong>{{ reservation.formatted_total }}</strong>
                        </td>
                        <td>
                          <router-link 
                            :to="`/customer/reservations/${reservation.id}`" 
                            class="btn btn-sm btn-outline-primary"
                          >
                            Ver
                          </router-link>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div v-else class="text-center py-4">
                  <i class="pi pi-calendar" style="font-size: 3rem; color: #6c757d;"></i>
                  <h6 class="mt-3 text-muted">No tienes reservas aún</h6>
                  <router-link to="/search" class="btn btn-primary mt-2">
                    Hacer mi primera reserva
                  </router-link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '../../stores/auth.js'
import { customerApi } from '../../services/api.js'

export default {
  name: 'CustomerDashboard',
  setup() {
    const authStore = useAuthStore()
    const reservations = ref([])
    const loading = ref(false)

    const stats = computed(() => {
      return {
        totalReservations: reservations.value.length,
        confirmedReservations: reservations.value.filter(r => r.status === 'confirmed').length,
        pendingReservations: reservations.value.filter(r => r.status === 'pending_payment' || r.status === 'payment_submitted').length,
        completedStays: reservations.value.filter(r => r.status === 'completed').length
      }
    })

    const recentReservations = computed(() => {
      return reservations.value.slice(0, 5)
    })

    const loadData = async () => {
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

    onMounted(() => {
      loadData()
    })

    return {
      authStore,
      reservations,
      recentReservations,
      stats,
      loading,
      loadData,
      formatDate,
      getStatusClass
    }
  }
}
</script>

<style scoped>
.card {
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  transition: box-shadow 0.15s ease-in-out;
}

.card:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.text-primary {
  color: #007bff !important;
}

.text-success {
  color: #28a745 !important;
}

.text-warning {
  color: #ffc107 !important;
}

.text-info {
  color: #17a2b8 !important;
}

.table-hover tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.025);
}

.btn-outline-primary:hover {
  background-color: #007bff;
  border-color: #007bff;
}
</style>