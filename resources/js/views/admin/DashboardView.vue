<template>
  <div class="admin-dashboard">
    <div class="container-xl">
      <!-- Header profesional con gradiente -->
      <div class="welcome-section">
        <div class="welcome-content">
          <div class="welcome-text">
            <h2 class="welcome-title">¡Bienvenido, {{ authStore.userName }}! 👋</h2>
            <p class="welcome-subtitle">Panel de control administrativo - {{ currentDate }}</p>
          </div>
          <div class="welcome-actions">
            <button @click="refreshData" class="btn btn-refresh" :disabled="loading">
              <i class="pi pi-refresh"></i>
              <span v-if="loading">Actualizando...</span>
              <span v-else>Actualizar</span>
            </button>
            <button @click="navigateTo('admin.reports')" class="btn btn-primary">
              <i class="pi pi-chart-bar"></i>
              Reportes
            </button>
          </div>
        </div>
      </div>

      <!-- Métricas principales con diseño profesional -->
      <div class="metrics-section">
        <div class="metrics-grid">
          <div class="metric-card reservations">
            <div class="metric-icon">
              <i class="pi pi-calendar"></i>
            </div>
            <div class="metric-content">
              <div class="metric-value">{{ stats.totalReservations }}</div>
              <div class="metric-label">Reservas Totales</div>
              <div class="metric-change positive">
                <i class="pi pi-arrow-up"></i>
                +12.5%
              </div>
            </div>
          </div>

          <div class="metric-card confirmed">
            <div class="metric-icon">
              <i class="pi pi-check-circle"></i>
            </div>
            <div class="metric-content">
              <div class="metric-value">{{ stats.confirmedReservations }}</div>
              <div class="metric-label">Confirmadas</div>
              <div class="metric-change positive">
                <i class="pi pi-arrow-up"></i>
                +8.2%
              </div>
            </div>
          </div>

          <div class="metric-card pending">
            <div class="metric-icon">
              <i class="pi pi-clock"></i>
            </div>
            <div class="metric-content">
              <div class="metric-value">{{ stats.pendingPayments }}</div>
              <div class="metric-label">Pagos Pendientes</div>
              <div class="metric-change warning">
                <i class="pi pi-exclamation-triangle"></i>
                Requiere atención
              </div>
            </div>
          </div>

          <div class="metric-card rooms">
            <div class="metric-icon">
              <i class="pi pi-home"></i>
            </div>
            <div class="metric-content">
              <div class="metric-value">{{ stats.availableRooms }}</div>
              <div class="metric-label">Habitaciones Disponibles</div>
              <div class="metric-change neutral">
                <i class="pi pi-minus"></i>
                Sin cambios
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="dashboard-content">
        <!-- Área principal -->
        <div class="main-content">
          <!-- Acciones principales con diseño profesional -->
          <div class="card">
            <div class="card-header">
              <h5 class="card-title">
                <i class="pi pi-bolt"></i>
                Acciones Principales
              </h5>
            </div>
            <div class="card-body">
              <div class="actions-grid">
                <div class="action-card" @click="navigateTo('admin.users')">
                  <div class="action-icon bg-primary">
                    <i class="pi pi-users"></i>
                  </div>
                  <div class="action-content">
                    <h6>Gestionar Usuarios</h6>
                    <p>Administrar clientes y usuarios</p>
                  </div>
                </div>

                <div class="action-card" @click="navigateTo('admin.rooms')">
                  <div class="action-icon bg-success">
                    <i class="pi pi-home"></i>
                  </div>
                  <div class="action-content">
                    <h6>Gestionar Habitaciones</h6>
                    <p>Administrar habitaciones</p>
                  </div>
                </div>

                <div class="action-card" @click="navigateTo('admin.registrations')">
                  <div class="action-icon bg-warning">
                    <i class="pi pi-credit-card"></i>
                  </div>
                  <div class="action-content">
                    <h6>Registro de ingresos</h6>
                    <p>Revisar ingresos</p>
                    
                  </div>
                </div>

                <div class="action-card" @click="navigateTo('admin.reports')">
                  <div class="action-icon bg-info">
                    <i class="pi pi-chart-bar"></i>
                  </div>
                  <div class="action-content">
                    <h6>Generar Reportes</h6>
                    <p>Informes y estadísticas</p>
                  </div>
                </div>

                <div class="action-card" @click="navigateTo('admin.reservations')">
                  <div class="action-icon bg-purple">
                    <i class="pi pi-calendar"></i>
                  </div>
                  <div class="action-content">
                    <h6>Todas las Reservas</h6>
                    <p>Gestionar reservas</p>
                  </div>
                </div>

                <div class="action-card" @click="navigateTo('admin.branches')">
                  <div class="action-icon bg-teal">
                    <i class="pi pi-building"></i>
                  </div>
                  <div class="action-content">
                    <h6>Sucursales</h6>
                    <p>Administrar ubicaciones</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Actividad reciente profesional -->
          <div class="card">
            <div class="card-header">
              <h5 class="card-title">
                <i class="pi pi-history"></i>
                Actividad Reciente
              </h5>
              <button @click="navigateTo('admin.activity')" class="btn btn-sm btn-outline-primary">
                Ver Todo
              </button>
            </div>
            <div class="card-body">
              <div class="activity-timeline">
                <div v-for="alert in alerts" :key="alert.id" class="activity-item">
                  <div class="activity-icon" :class="`bg-${getActivityType(alert.type)}`">
                    <i :class="getAlertIcon(alert.type)"></i>
                  </div>
                  <div class="activity-content">
                    <div class="activity-header">
                      <span class="activity-title">{{ getActivityTitle(alert.type) }}</span>
                      <span class="activity-time">{{ formatTimeAgo(alert.timestamp) }}</span>
                    </div>
                    <p class="activity-description">{{ alert.message }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Acciones adicionales -->
          <div class="card">
            <div class="card-header">
              <h5 class="card-title">
                <i class="pi pi-cog"></i>
                Gestión del Sistema
              </h5>
            </div>
            <div class="card-body">
              <div class="secondary-actions">
                <button @click="navigateTo('admin.room-types')" class="secondary-action">
                  <i class="pi pi-th-large"></i>
                  <span>Tipos de Habitación</span>
                </button>
                <button @click="navigateTo('admin.services')" class="secondary-action">
                  <i class="pi pi-cog"></i>
                  <span>Servicios</span>
                </button>
                <button @click="navigateTo('admin.payments')" class="secondary-action">
                  <i class="pi pi-money-bill"></i>
                  <span>Historial de Pagos</span>
                </button>
                <button @click="navigateTo('admin.registrations')" class="secondary-action">
                  <i class="pi pi-user-plus"></i>
                  <span>Registros</span>
                </button>
                <button @click="navigateTo('admin.settings')" class="secondary-action">
                  <i class="pi pi-cog"></i>
                  <span>Configuraciones</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar profesional -->
        <div class="sidebar-content">
          <!-- Resumen de hoy -->
          <div class="card">
            <div class="card-header">
              <h5 class="card-title">
                <i class="pi pi-calendar-check"></i>
                Resumen de Hoy
              </h5>
            </div>
            <div class="card-body">
              <div class="quick-stats">
                <div class="stat-item">
                  <div class="stat-icon bg-primary">
                    <i class="pi pi-sign-in"></i>
                  </div>
                  <div class="stat-info">
                    <div class="stat-value">{{ stats.checkInsToday || 0 }}</div>
                    <div class="stat-label">Check-ins Hoy</div>
                  </div>
                </div>

                <div class="stat-item">
                  <div class="stat-icon bg-warning">
                    <i class="pi pi-sign-out"></i>
                  </div>
                  <div class="stat-info">
                    <div class="stat-value">{{ stats.checkOutsToday || 0 }}</div>
                    <div class="stat-label">Check-outs Hoy</div>
                  </div>
                </div>

                <div class="stat-item">
                  <div class="stat-icon bg-info">
                    <i class="pi pi-clock"></i>
                  </div>
                  <div class="stat-info">
                    <div class="stat-value">{{ stats.pendingPayments }}</div>
                    <div class="stat-label">Pagos Pendientes</div>
                  </div>
                </div>

                <div class="stat-item">
                  <div class="stat-icon bg-danger">
                    <i class="pi pi-exclamation-triangle"></i>
                  </div>
                  <div class="stat-info">
                    <div class="stat-value">{{ stats.expiredReservations || 0 }}</div>
                    <div class="stat-label">Reservas Vencidas</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Ocupación por sucursal -->
          <div class="card">
            <div class="card-header">
              <h5 class="card-title">
                <i class="pi pi-building"></i>
                Ocupación por Sucursal
              </h5>
            </div>
            <div class="card-body">
              <div class="branches-occupancy">
                <div class="branch-item">
                  <div class="branch-header">
                    <h6 class="branch-name">Sucursal Villa Caluyo</h6>
                    <span class="occupancy-percentage" :class="getOccupancyClass(getOccupancyPercentage('villa_caluyo'))">
                      {{ getOccupancyPercentage('villa_caluyo') }}%
                    </span>
                  </div>
                  <div class="occupancy-bar">
                    <div class="occupancy-fill" :style="{ width: getOccupancyPercentage('villa_caluyo') + '%' }"></div>
                  </div>
                  <div class="branch-stats">
                    <span class="stat">{{ branchStats.villa_caluyo.occupied }} ocupadas</span>
                    <span class="stat">{{ branchStats.villa_caluyo.available }} disponibles</span>
                  </div>
                </div>

                <div class="branch-item">
                  <div class="branch-header">
                    <h6 class="branch-name">Sucursal Cruce Villa Adela</h6>
                    <span class="occupancy-percentage" :class="getOccupancyClass(getOccupancyPercentage('villa_adela'))">
                      {{ getOccupancyPercentage('villa_adela') }}%
                    </span>
                  </div>
                  <div class="occupancy-bar">
                    <div class="occupancy-fill" :style="{ width: getOccupancyPercentage('villa_adela') + '%' }"></div>
                  </div>
                  <div class="branch-stats">
                    <span class="stat">{{ branchStats.villa_adela.occupied }} ocupadas</span>
                    <span class="stat">{{ branchStats.villa_adela.available }} disponibles</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Accesos rápidos -->
          <div class="card">
            <div class="card-header">
              <h5 class="card-title">
                <i class="pi pi-external-link"></i>
                Accesos Rápidos
              </h5>
            </div>
            <div class="card-body">
              <div class="quick-access">
                <button @click="navigateTo('admin.payments.pending')" class="quick-access-btn warning">
                  <i class="pi pi-clock"></i>
                  <div>
                    <span class="quick-access-label">Pagos Pendientes</span>
                    <span class="quick-access-count">{{ stats.pendingPayments }}</span>
                  </div>
                </button>

                <button @click="navigateTo('admin.reservations')" class="quick-access-btn primary">
                  <i class="pi pi-calendar"></i>
                  <div>
                    <span class="quick-access-label">Todas las Reservas</span>
                    <span class="quick-access-count">{{ stats.totalReservations }}</span>
                  </div>
                </button>

                <button @click="navigateTo('admin.rooms')" class="quick-access-btn success">
                  <i class="pi pi-home"></i>
                  <div>
                    <span class="quick-access-label">Habitaciones</span>
                    <span class="quick-access-count">{{ stats.availableRooms }}</span>
                  </div>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
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
      availableRooms: 0,
      checkInsToday: 0,
      checkOutsToday: 0,
      expiredReservations: 0
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

    const currentDate = computed(() => {
      const today = new Date()
      return today.toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    })

    const loadDashboardData = async () => {
      try {
        loading.value = true
        
        // Simulamos datos hasta que implementemos el endpoint real
        stats.value = {
          totalReservations: 25,
          confirmedReservations: 18,
          pendingPayments: 3,
          availableRooms: 7,
          checkInsToday: 2,
          checkOutsToday: 1,
          expiredReservations: 0
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
            message: 'Tienes 3 pagos pendientes de validación',
            action: 'admin.payments.pending',
            timestamp: new Date(Date.now() - 10 * 60 * 1000).toISOString()
          },
          {
            id: 2,
            type: 'info',
            message: 'Nueva reserva creada hace 10 minutos',
            action: 'admin.reservations',
            timestamp: new Date(Date.now() - 10 * 60 * 1000).toISOString()
          },
          {
            id: 3,
            type: 'success',
            message: 'Check-in completado correctamente - Habitación 205',
            action: 'admin.reservations',
            timestamp: new Date(Date.now() - 30 * 60 * 1000).toISOString()
          }
        ]
        
      } catch (error) {
        console.error('Error loading dashboard data:', error)
      } finally {
        loading.value = false
      }
    }

    const refreshData = async () => {
      loading.value = true
      try {
        await new Promise(resolve => setTimeout(resolve, 1500))
        await loadDashboardData()
      } catch (error) {
        console.error('Error al actualizar:', error)
      } finally {
        loading.value = false
      }
    }

    const navigateTo = (routeName) => {
      try {
        router.push({ name: routeName })
      } catch (error) {
        console.error('Error navigating to:', routeName, error)
        // Fallback para rutas que no existen aún
        alert(`Navegando a ${routeName}... (Vista en desarrollo)`)
      }
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

    const getActivityType = (type) => {
      const types = {
        'success': 'success',
        'warning': 'warning',
        'danger': 'danger',
        'info': 'info'
      }
      return types[type] || 'info'
    }

    const getActivityTitle = (type) => {
      const titles = {
        'success': 'Operación Exitosa',
        'warning': 'Atención Requerida',
        'danger': 'Error Crítico',
        'info': 'Nueva Información'
      }
      return titles[type] || 'Actividad'
    }

    const getOccupancyPercentage = (branch) => {
      const total = branchStats.value[branch].available + branchStats.value[branch].occupied
      if (total === 0) return 0
      return Math.round((branchStats.value[branch].occupied / total) * 100)
    }

    const getOccupancyClass = (occupancy) => {
      if (occupancy >= 90) return 'high'
      if (occupancy >= 70) return 'medium'
      return 'low'
    }

    const formatTimeAgo = (timestamp) => {
      const now = new Date()
      const time = new Date(timestamp)
      const diffInMinutes = Math.floor((now - time) / (1000 * 60))
      
      if (diffInMinutes < 1) return 'Hace menos de 1 minuto'
      if (diffInMinutes < 60) return `Hace ${diffInMinutes} minutos`
      
      const diffInHours = Math.floor(diffInMinutes / 60)
      if (diffInHours < 24) return `Hace ${diffInHours} horas`
      
      const diffInDays = Math.floor(diffInHours / 24)
      return `Hace ${diffInDays} días`
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
      currentDate,
      navigateTo,
      getAlertClass,
      getAlertIcon,
      getActivityType,
      getActivityTitle,
      getOccupancyPercentage,
      getOccupancyClass,
      formatTimeAgo,
      refreshData
    }
  }
}
</script>

<style scoped>
.admin-dashboard {
  background-color: #f8f9fa;
  min-height: 100vh;
  padding: 1.5rem 0;
}

.container-xl {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* Header profesional con gradiente */
.welcome-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem;
  border-radius: 1rem;
  margin-bottom: 2rem;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.welcome-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.welcome-title {
  font-size: 1.75rem;
  font-weight: 600;
  margin: 0;
}

.welcome-subtitle {
  color: rgba(255, 255, 255, 0.9);
  margin: 0.5rem 0 0 0;
  font-size: 0.95rem;
}

.welcome-actions {
  display: flex;
  gap: 1rem;
}

.btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border-radius: 0.5rem;
  font-weight: 500;
  text-decoration: none;
  border: none;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-refresh {
  background-color: rgba(255, 255, 255, 0.2);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-refresh:hover:not(:disabled) {
  background-color: rgba(255, 255, 255, 0.3);
  transform: translateY(-1px);
}

.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background-color: white;
  color: #667eea;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Métricas con diseño profesional */
.metrics-section {
  margin-bottom: 2rem;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 1.5rem;
}

.metric-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 1rem;
  position: relative;
  overflow: hidden;
}

.metric-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
}

.metric-card.reservations::before {
  background: linear-gradient(90deg, #3b82f6, #1d4ed8);
}
.metric-card.confirmed::before {
  background: linear-gradient(90deg, #10b981, #059669);
}
.metric-card.pending::before {
  background: linear-gradient(90deg, #f59e0b, #d97706);
}
.metric-card.rooms::before {
  background: linear-gradient(90deg, #06b6d4, #0891b2);
}

.metric-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

.metric-icon {
  width: 60px;
  height: 60px;
  border-radius: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
  flex-shrink: 0;
}

.metric-card.reservations .metric-icon {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}
.metric-card.confirmed .metric-icon {
  background: linear-gradient(135deg, #10b981, #059669);
}
.metric-card.pending .metric-icon {
  background: linear-gradient(135deg, #f59e0b, #d97706);
}
.metric-card.rooms .metric-icon {
  background: linear-gradient(135deg, #06b6d4, #0891b2);
}

.metric-content {
  flex: 1;
}

.metric-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  line-height: 1;
}

.metric-label {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0.5rem 0;
}

.metric-change {
  font-size: 0.8rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.metric-change.positive {
  color: #10b981;
}
.metric-change.negative {
  color: #ef4444;
}
.metric-change.neutral {
  color: #6b7280;
}
.metric-change.warning {
  color: #f59e0b;
}

/* Layout principal */
.dashboard-content {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
}

/* Cards profesionales */
.card {
  background: white;
  border-radius: 1rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.card-header {
  background-color: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  padding: 1.25rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.card-body {
  padding: 1.5rem;
}

/* Acciones con estilo profesional */
.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.action-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 0.75rem;
  padding: 1.25rem;
  cursor: pointer;
  transition: all 0.3s;
  position: relative;
}

.action-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  border-color: #3b82f6;
}

.action-icon {
  width: 48px;
  height: 48px;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
  flex-shrink: 0;
}

.bg-primary {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}
.bg-success {
  background: linear-gradient(135deg, #10b981, #059669);
}
.bg-warning {
  background: linear-gradient(135deg, #f59e0b, #d97706);
}
.bg-info {
  background: linear-gradient(135deg, #06b6d4, #0891b2);
}
.bg-purple {
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}
.bg-teal {
  background: linear-gradient(135deg, #14b8a6, #0d9488);
}
.bg-danger {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

.action-content {
  flex: 1;
}

.action-content h6 {
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.25rem 0;
  font-size: 0.9rem;
}

.action-content p {
  font-size: 0.8rem;
  color: #6b7280;
  margin: 0;
}

.action-badge {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  background: #ef4444;
  color: white;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
  min-width: 1.25rem;
  text-align: center;
}

/* Actividad con timeline profesional */
.activity-timeline {
  max-height: 400px;
  overflow-y: auto;
}

.activity-item {
  display: flex;
  gap: 1rem;
  padding: 1rem 0;
  border-bottom: 1px solid #f3f4f6;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  flex-shrink: 0;
}

.activity-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.activity-title {
  font-weight: 600;
  color: #1f2937;
}

.activity-time {
  font-size: 0.8rem;
  color: #9ca3af;
}

.activity-description {
  color: #6b7280;
  margin: 0;
  font-size: 0.875rem;
}

/* Acciones secundarias */
.secondary-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.secondary-action {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.875rem;
}

.secondary-action:hover {
  background: #f3f4f6;
  border-color: #d1d5db;
  color: #374151;
}

/* Estadísticas rápidas */
.quick-stats {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 0;
  border-bottom: 1px solid #f3f4f6;
}

.stat-item:last-child {
  border-bottom: none;
}

.stat-icon {
  width: 40px;
  height: 40px;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-info {
  flex: 1;
}

.stat-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
}

.stat-label {
  font-size: 0.8rem;
  color: #6b7280;
}

/* Ocupación por sucursal */
.branch-item {
  padding: 1rem 0;
  border-bottom: 1px solid #f3f4f6;
}

.branch-item:last-child {
  border-bottom: none;
}

.branch-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.branch-name {
  font-weight: 600;
  color: #1f2937;
  margin: 0;
  font-size: 0.9rem;
}

.occupancy-percentage {
  font-size: 0.875rem;
  font-weight: 600;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
}

.occupancy-percentage.high {
  background-color: #dcfce7;
  color: #166534;
}

.occupancy-percentage.medium {
  background-color: #fef3c7;
  color: #92400e;
}

.occupancy-percentage.low {
  background-color: #fef2f2;
  color: #991b1b;
}

.occupancy-bar {
  height: 6px;
  background-color: #f3f4f6;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.occupancy-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #1d4ed8);
  transition: width 0.5s ease;
}

.branch-stats {
  display: flex;
  gap: 1rem;
  font-size: 0.75rem;
  color: #6b7280;
}

/* Accesos rápidos */
.quick-access {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.quick-access-btn {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
  width: 100%;
}

.quick-access-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.quick-access-btn.warning {
  border-color: #f59e0b;
}

.quick-access-btn.primary {
  border-color: #3b82f6;
}

.quick-access-btn.success {
  border-color: #10b981;
}

.quick-access-btn i {
  font-size: 1.25rem;
}

.quick-access-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #1f2937;
}

.quick-access-count {
  font-size: 0.8rem;
  color: #6b7280;
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.8rem;
}

.btn-outline-primary {
  color: #3b82f6;
  border: 1px solid #3b82f6;
  background: transparent;
}

.btn-outline-primary:hover {
  background-color: #3b82f6;
  color: white;
}

/* Responsive */
@media (max-width: 768px) {
  .welcome-content {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .dashboard-content {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .metrics-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }

  .actions-grid {
    grid-template-columns: 1fr;
  }

  .action-card {
    flex-direction: column;
    text-align: center;
    gap: 0.75rem;
  }

  .activity-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }

  .card-header {
    flex-direction: column;
    gap: 0.75rem;
    align-items: flex-start;
  }

  .secondary-actions {
    flex-direction: column;
  }

  .secondary-action {
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .metrics-grid {
    grid-template-columns: 1fr;
  }

  .container-xl {
    padding: 0 0.5rem;
  }

  .welcome-section {
    padding: 1.5rem;
  }

  .card-body {
    padding: 1rem;
  }
}
</style>