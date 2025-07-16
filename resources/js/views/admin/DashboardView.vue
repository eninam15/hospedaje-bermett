<template>
  <div class="container-fluid py-4">
    <div class="row">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2>Panel de Administración</h2>
          <span class="text-muted">Bienvenido, {{ authStore.userName }}</span>
        </div>

        <!-- Estadísticas principales -->
        <div class="row mb-4">
          <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center bg-primary text-white">
              <div class="card-body">
                <i class="pi pi-calendar mb-2" style="font-size: 2rem;"></i>
                <h4>{{ stats.totalReservations }}</h4>
                <p class="mb-0">Reservas Totales</p>
              </div>
            </div>
          </div>
          
          <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center bg-success text-white">
              <div class="card-body">
                <i class="pi pi-check-circle mb-2" style="font-size: 2rem;"></i>
                <h4>{{ stats.confirmedReservations }}</h4>
                <p class="mb-0">Confirmadas</p>
              </div>
            </div>
          </div>
          
          <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center bg-warning text-white">
              <div class="card-body">
                <i class="pi pi-clock mb-2" style="font-size: 2rem;"></i>
                <h4>{{ stats.pendingPayments }}</h4>
                <p class="mb-0">Pagos Pendientes</p>
              </div>
            </div>
          </div>
          
          <div class="col-lg-3 col-md-6 mb-3">
            <div class="card text-center bg-info text-white">
              <div class="card-body">
                <i class="pi pi-home mb-2" style="font-size: 2rem;"></i>
                <h4>{{ stats.availableRooms }}</h4>
                <p class="mb-0">Habitaciones Disponibles</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Acciones rápidas -->
        <div class="row mb-4">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Acciones Rápidas</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6 col-lg-3 mb-3">
                    <button class="btn btn-outline-primary w-100" @click="navigateTo('users')">
                      <i class="pi pi-users"></i> Gestionar Usuarios
                    </button>
                  </div>
                  <div class="col-md-6 col-lg-3 mb-3">
                    <button class="btn btn-outline-success w-100" @click="navigateTo('rooms')">
                      <i class="pi pi-home"></i> Gestionar Habitaciones
                    </button>
                  </div>
                  <div class="col-md-6 col-lg-3 mb-3">
                    <button class="btn btn-outline-warning w-100" @click="navigateTo('payments')">
                      <i class="pi pi-credit-card"></i> Validar Pagos
                    </button>
                  </div>
                  <div class="col-md-6 col-lg-3 mb-3">
                    <button class="btn btn-outline-info w-100" @click="navigateTo('reports')">
                      <i class="pi pi-chart-bar"></i> Generar Reportes
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Información de sucursales -->
        <div class="row mb-4">
          <div class="col-lg-6 mb-3">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Sucursal Villa Caluyo</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-6">
                    <div class="text-center">
                      <h4 class="text-success">{{ branchStats.villa_caluyo.available }}</h4>
                      <small class="text-muted">Disponibles</small>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="text-center">
                      <h4 class="text-danger">{{ branchStats.villa_caluyo.occupied }}</h4>
                      <small class="text-muted">Ocupadas</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6 mb-3">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Sucursal Cruce Villa Adela</h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-6">
                    <div class="text-center">
                      <h4 class="text-success">{{ branchStats.villa_adela.available }}</h4>
                      <small class="text-muted">Disponibles</small>
                    </div>
                  </div>
                  <div class="col-6">
                    <div class="text-center">
                      <h4 class="text-danger">{{ branchStats.villa_adela.occupied }}</h4>
                      <small class="text-muted">Ocupadas</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Alertas importantes -->
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h5 class="mb-0">Alertas y Notificaciones</h5>
              </div>
              <div class="card-body">
                <div v-if="alerts.length > 0">
                  <div v-for="alert in alerts" :key="alert.id" :class="getAlertClass(alert.type)" class="mb-2">
                    <i :class="getAlertIcon(alert.type)"></i>
                    {{ alert.message }}
                  </div>
                </div>
                <div v-else class="text-center text-muted">
                  <i class="pi pi-check-circle text-success" style="font-size: 2rem;"></i>
                  <p class="mt-2 mb-0">No hay alertas pendientes</p>
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth.js'
import { adminApi } from '../../services/api.js'

export default {
  name: 'AdminDashboard',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    
    const stats = ref({
      totalReservations: 0,
      confirmedReservations: 0,
      pendingPayments: 0,
      availableRooms: 0
    })
    
    const branchStats = ref({
      villa_caluyo: {
        available: 0,
        occupied: 0
      },
      villa_adela: {
        available: 0,
        occupied: 0
      }
    })
    
    const alerts = ref([])
    const loading = ref(false)

    const loadDashboardData = async () => {
      try {
        loading.value = true
        
        // Simulamos datos hasta que implementemos el endpoint real
        stats.value = {
          totalReservations: 25,
          confirmedReservations: 18,
          pendingPayments: 3,
          availableRooms: 7
        }
        
        branchStats.value = {
          villa_caluyo: {
            available: 3,
            occupied: 2
          },
          villa_adela: {
            available: 4,
            occupied: 1
          }
        }
        
        alerts.value = [
          {
            id: 1,
            type: 'warning',
            message: 'Tienes 3 pagos pendientes de validación'
          },
          {
            id: 2,
            type: 'info',
            message: 'Nueva reserva creada hace 10 minutos'
          }
        ]
        
      } catch (error) {
        console.error('Error loading dashboard data:', error)
      } finally {
        loading.value = false
      }
    }

    const navigateTo = (section) => {
      // Por ahora solo mostramos alert, más adelante implementaremos las rutas
      alert(`Navegando a ${section}... (Por implementar)`)
    }

    const getAlertClass = (type) => {
      const classes = {
        'success': 'alert alert-success',
        'warning': 'alert alert-warning',
        'danger': 'alert alert-danger',
        'info': 'alert alert-info'
      }
      return classes[type] || 'alert alert-info'
    }

    const getAlertIcon = (type) => {
      const icons = {
        'success': 'pi pi-check-circle',
        'warning': 'pi pi-exclamation-triangle',
        'danger': 'pi pi-times-circle',
        'info': 'pi pi-info-circle'
      }
      return icons[type] || 'pi pi-info-circle'
    }

    onMounted(() => {
      loadDashboardData()
    })

    return {
      authStore,
      stats,
      branchStats,
      alerts,
      loading,
      navigateTo,
      getAlertClass,
      getAlertIcon
    }
  }
}
</script>

<style scoped>
.container-fluid {
  max-width: 1200px;
}

.card {
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
  transition: all 0.2s ease-in-out;
}

.card:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.bg-primary {
  background-color: #007bff !important;
}

.bg-success {
  background-color: #28a745 !important;
}

.bg-warning {
  background-color: #ffc107 !important;
}

.bg-info {
  background-color: #17a2b8 !important;
}

.text-success {
  color: #28a745 !important;
}

.text-danger {
  color: #dc3545 !important;
}

.btn-outline-primary:hover {
  background-color: #007bff;
  border-color: #007bff;
}

.btn-outline-success:hover {
  background-color: #28a745;
  border-color: #28a745;
}

.btn-outline-warning:hover {
  background-color: #ffc107;
  border-color: #ffc107;
}

.btn-outline-info:hover {
  background-color: #17a2b8;
  border-color: #17a2b8;
}

.alert {
  border: none;
  border-radius: 0.5rem;
}
</style>