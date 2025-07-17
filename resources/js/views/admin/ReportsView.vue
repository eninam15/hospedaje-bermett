<template>
  <div class="reports-dashboard">
    <div class="container-xl">
      <!-- Header profesional -->
      <div class="reports-header">
        <div class="header-content">
          <div class="header-text">
            <h2 class="header-title">📊 Reportes y Estadísticas</h2>
            <p class="header-subtitle">Análisis detallado de datos del sistema</p>
          </div>
          <div class="header-actions" v-if="currentReport">
            <button 
              @click="downloadPDF"
              class="btn btn-success"
              :disabled="loading"
            >
              <i class="pi pi-download"></i>
              Descargar PDF
            </button>
            <button 
              @click="clearReport"
              class="btn btn-outline-secondary"
            >
              <i class="pi pi-times"></i>
              Cerrar
            </button>
          </div>
        </div>
      </div>

      <!-- Panel de Selección de Reportes -->
      <div v-if="!currentReport" class="report-selection">
        <!-- Filtros Globales -->
        <div class="card filters-card">
          <div class="card-header">
            <h5 class="card-title">
              <i class="pi pi-filter"></i>
              Filtros de Búsqueda
            </h5>
          </div>
          <div class="card-body">
            <div class="filters-grid">
              <div class="filter-group">
                <label class="filter-label">Fecha Inicio</label>
                <input 
                  v-model="filters.start_date"
                  type="date" 
                  class="form-control"
                >
              </div>
              <div class="filter-group">
                <label class="filter-label">Fecha Fin</label>
                <input 
                  v-model="filters.end_date"
                  type="date" 
                  class="form-control"
                >
              </div>
              <div class="filter-group">
                <label class="filter-label">Sucursal</label>
                <select v-model="filters.branch_id" class="form-select">
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
            </div>
          </div>
        </div>
        
        <!-- Tipos de reportes -->
        <div class="card reports-grid-card">
          <div class="card-header">
            <h5 class="card-title">
              <i class="pi pi-chart-line"></i>
              Tipos de Reportes
            </h5>
          </div>
          <div class="card-body">
            <div class="reports-grid">
              <div class="report-card" @click="generateReport('reservations')">
                <div class="report-icon reservations">
                  <i class="pi pi-calendar"></i>
                </div>
                <div class="report-content">
                  <h6>Reporte de Reservas</h6>
                  <p>Listado completo de reservas con detalles financieros</p>
                  <div class="report-features">
                    <span class="feature-tag">Ingresos</span>
                    <span class="feature-tag">Estados</span>
                    <span class="feature-tag">Clientes</span>
                  </div>
                </div>
              </div>
              
              <div class="report-card" @click="generateReport('registrations')">
                <div class="report-icon registrations">
                  <i class="pi pi-sign-in"></i>
                </div>
                <div class="report-content">
                  <h6>Reporte de Check-ins</h6>
                  <p>Listado completo de registros y check-ins</p>
                  <div class="report-features">
                    <span class="feature-tag">Duración</span>
                    <span class="feature-tag">Ocupación</span>
                    <span class="feature-tag">Horarios</span>
                  </div>
                </div>
              </div>
              
              <div class="report-card" @click="generateReport('users')">
                <div class="report-icon users">
                  <i class="pi pi-users"></i>
                </div>
                <div class="report-content">
                  <h6>Reporte de Usuarios</h6>
                  <p>Listado y análisis de usuarios registrados</p>
                  <div class="report-features">
                    <span class="feature-tag">Actividad</span>
                    <span class="feature-tag">Registro</span>
                    <span class="feature-tag">Documentos</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="loading-content">
          <div class="loading-spinner">
            <i class="pi pi-spin pi-spinner"></i>
          </div>
          <h4>Generando reporte...</h4>
          <p>Por favor espera mientras procesamos los datos</p>
        </div>
      </div>

      <!-- Vista del Reporte -->
      <div v-if="currentReport && !loading" class="report-view">
        <!-- Header del Reporte -->
        <div class="report-header">
          <div class="report-header-content">
            <div class="report-title-section">
              <h3 class="report-title">{{ getReportTitle(currentReportType) }}</h3>
              <div class="report-meta">
                <span class="meta-item">
                  <i class="pi pi-calendar"></i>
                  {{ formatDate(filters.start_date) }} - {{ formatDate(filters.end_date) }}
                </span>
                <span class="meta-item" v-if="selectedBranchName">
                  <i class="pi pi-building"></i>
                  {{ selectedBranchName }}
                </span>
                <span class="meta-item">
                  <i class="pi pi-clock"></i>
                  {{ new Date().toLocaleString() }}
                </span>
              </div>
            </div>
            <div class="report-actions">
              <button @click="downloadPDF" class="btn btn-primary">
                <i class="pi pi-download"></i>
                Descargar PDF
              </button>
            </div>
          </div>
        </div>

        <!-- Resumen del Reporte -->
        <div class="metrics-section" v-if="currentReport.summary">
          <div class="metrics-grid">
            <div 
              v-for="(value, key) in currentReport.summary" 
              :key="key"
              class="metric-card"
              :class="getMetricClass(key)"
            >
              <div class="metric-icon">
                <i :class="getMetricIcon(key)"></i>
              </div>
              <div class="metric-content">
                <div class="metric-value">{{ formatValue(value, key) }}</div>
                <div class="metric-label">{{ formatLabel(key) }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Datos Detallados -->
        <div class="card detailed-data-card">
          <div class="card-header">
            <h5 class="card-title">
              <i class="pi pi-table"></i>
              {{ getDetailTitle() }}
            </h5>
            <span class="records-count">{{ detailedData.length }} registros</span>
          </div>
          <div class="card-body">
            <div class="table-container">
              <table class="data-table">
                <thead>
                  <tr>
                    <th v-for="header in getTableHeaders()" :key="header" class="table-header">
                      {{ header }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in detailedData" :key="item.id" class="table-row">
                    <!-- Reservations -->
                    <template v-if="currentReportType === 'reservations'">
                      <td class="table-cell">
                        <div class="code-cell">
                          <strong>{{ item.reservation_code }}</strong>
                        </div>
                      </td>
                      <td class="table-cell">
                        <div class="user-cell">
                          <strong>{{ item.user?.name }}</strong>
                          <small>{{ item.user?.email }}</small>
                        </div>
                      </td>
                      <td class="table-cell">
                        <div class="room-cell">
                          <span class="room-number">{{ item.room?.room_number }}</span>
                          <small>{{ item.room?.room_type?.name }}</small>
                        </div>
                      </td>
                      <td class="table-cell">{{ formatDate(item.check_in_date) }}</td>
                      <td class="table-cell">{{ formatDate(item.check_out_date) }}</td>
                      <td class="table-cell">
                        <span class="nights-badge">{{ item.total_nights }}</span>
                      </td>
                      <td class="table-cell">
                        <span class="guests-badge">{{ item.adults_count + item.children_count }}</span>
                      </td>
                      <td class="table-cell">
                        <span :class="getStatusClass(item.status)" class="status-badge">
                          {{ getStatusText(item.status) }}
                        </span>
                      </td>
                      <td class="table-cell">
                        <strong class="amount-text">{{ formatCurrency(item.total_amount) }}</strong>
                      </td>
                    </template>

                    <!-- Registrations -->
                    <template v-if="currentReportType === 'registrations'">
                      <td class="table-cell">
                        <div class="code-cell">
                          <strong>{{ item.registration_code }}</strong>
                        </div>
                      </td>
                      <td class="table-cell">
                        <div class="user-cell">
                          <strong>{{ item.user?.name }}</strong>
                          <small>{{ item.user?.email }}</small>
                        </div>
                      </td>
                      <td class="table-cell">
                        <div class="room-cell">
                          <span class="room-number">{{ item.room?.room_number }}</span>
                          <small>{{ item.room?.room_type?.name }}</small>
                        </div>
                      </td>
                      <td class="table-cell">{{ formatDateTime(item.actual_check_in) }}</td>
                      <td class="table-cell">
                        {{ item.actual_check_out ? formatDateTime(item.actual_check_out) : 'Activo' }}
                      </td>
                      <td class="table-cell">
                        <span class="duration-badge">
                          {{ calculateDuration(item.actual_check_in, item.actual_check_out) }}
                        </span>
                      </td>
                      <td class="table-cell">
                        <span class="guests-badge">{{ getTotalGuests(item) }}</span>
                      </td>
                      <td class="table-cell">
                        <span :class="getRegistrationStatusClass(item.status)" class="status-badge">
                          {{ getRegistrationStatusText(item.status) }}
                        </span>
                      </td>
                      <td class="table-cell">
                        <span class="type-badge">
                          {{ item.reservation?.reservation_code?.startsWith('DIR') ? 'Directo' : 'Reserva' }}
                        </span>
                      </td>
                    </template>

                    <!-- Users -->
                    <template v-if="currentReportType === 'users'">
                      <td class="table-cell">
                        <div class="user-cell">
                          <strong>{{ item.name }}</strong>
                        </div>
                      </td>
                      <td class="table-cell">{{ item.email }}</td>
                      <td class="table-cell">{{ item.phone || '-' }}</td>
                      <td class="table-cell">
                        <div class="document-cell">
                          <span class="doc-type">{{ item.document_type }}</span>
                          <span class="doc-number">{{ item.document_number }}</span>
                        </div>
                      </td>
                      <td class="table-cell">{{ formatDate(item.created_at) }}</td>
                      <td class="table-cell">
                        <span :class="item.is_active ? 'status-badge active' : 'status-badge inactive'">
                          {{ item.is_active ? 'Activo' : 'Inactivo' }}
                        </span>
                      </td>
                    </template>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Análisis Adicional -->
        <div class="additional-analysis">
          <!-- Top Clientes (para reservas) -->
          <div v-if="currentReportType === 'reservations' && currentReport.top_customers && currentReport.top_customers.length > 0" class="analysis-section">
            <div class="analysis-grid">
              <div class="analysis-card">
                <div class="analysis-header">
                  <h6 class="analysis-title">
                    <i class="pi pi-star"></i>
                    Top 5 Clientes por Ingresos
                  </h6>
                </div>
                <div class="analysis-content">
                  <div class="top-customers">
                    <div v-for="(customer, index) in currentReport.top_customers" :key="customer.user.id" class="customer-item">
                      <div class="customer-rank">{{ index + 1 }}</div>
                      <div class="customer-info">
                        <strong>{{ customer.user.name }}</strong>
                        <small>{{ customer.user.email }}</small>
                      </div>
                      <div class="customer-stats">
                        <div class="stat-item">
                          <span class="stat-value">{{ customer.reservations }}</span>
                          <span class="stat-label">Reservas</span>
                        </div>
                        <div class="stat-item">
                          <span class="stat-value">{{ formatCurrency(customer.total_spent) }}</span>
                          <span class="stat-label">Total</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="analysis-card">
                <div class="analysis-header">
                  <h6 class="analysis-title">
                    <i class="pi pi-home"></i>
                    Top 5 Habitaciones por Ingresos
                  </h6>
                </div>
                <div class="analysis-content">
                  <div class="top-rooms">
                    <div v-for="(room, index) in currentReport.top_rooms" :key="room.room.id" class="room-item">
                      <div class="room-rank">{{ index + 1 }}</div>
                      <div class="room-info">
                        <span class="room-number">{{ room.room.room_number }}</span>
                        <small>{{ room.room.room_type?.name }}</small>
                      </div>
                      <div class="room-stats">
                        <div class="stat-item">
                          <span class="stat-value">{{ room.reservations }}</span>
                          <span class="stat-label">Reservas</span>
                        </div>
                        <div class="stat-item">
                          <span class="stat-value">{{ formatCurrency(room.revenue) }}</span>
                          <span class="stat-label">Ingresos</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Análisis por Sucursal -->
          <div v-if="currentReport.branch_stats" class="analysis-section">
            <div class="analysis-card full-width">
              <div class="analysis-header">
                <h6 class="analysis-title">
                  <i class="pi pi-building"></i>
                  Análisis por Sucursal
                </h6>
              </div>
              <div class="analysis-content">
                <div class="branch-stats">
                  <div v-for="(stats, branchName) in currentReport.branch_stats" :key="branchName" class="branch-stat-item">
                    <div class="branch-name">{{ branchName }}</div>
                    <div class="branch-metrics">
                      <div v-if="currentReportType === 'reservations'" class="metric-group">
                        <div class="metric-item">
                          <span class="metric-value">{{ stats.count }}</span>
                          <span class="metric-label">Reservas</span>
                        </div>
                        <div class="metric-item">
                          <span class="metric-value">{{ formatCurrency(stats.revenue) }}</span>
                          <span class="metric-label">Ingresos</span>
                        </div>
                        <div class="metric-item">
                          <span class="metric-value">{{ stats.guests }}</span>
                          <span class="metric-label">Huéspedes</span>
                        </div>
                      </div>
                      <div v-if="currentReportType === 'registrations'" class="metric-group">
                        <div class="metric-item">
                          <span class="metric-value">{{ stats.total }}</span>
                          <span class="metric-label">Total</span>
                        </div>
                        <div class="metric-item">
                          <span class="metric-value">{{ stats.active }}</span>
                          <span class="metric-label">Activos</span>
                        </div>
                        <div class="metric-item">
                          <span class="metric-value">{{ stats.completed }}</span>
                          <span class="metric-label">Completados</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Distribución por Horas (para registros) -->
          <div v-if="currentReportType === 'registrations' && currentReport.hourly_distribution" class="analysis-section">
            <div class="analysis-card full-width">
              <div class="analysis-header">
                <h6 class="analysis-title">
                  <i class="pi pi-clock"></i>
                  Distribución de Check-ins por Hora
                </h6>
              </div>
              <div class="analysis-content">
                <div class="hourly-distribution">
                  <div v-for="(count, hour) in currentReport.hourly_distribution" :key="hour" class="hour-item">
                    <div class="hour-label">{{ hour }}:00</div>
                    <div class="hour-bar">
                      <div class="hour-fill" :style="{ width: getHourlyPercentage(count) + '%' }"></div>
                    </div>
                    <div class="hour-count">{{ count }}</div>
                  </div>
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
import { adminApi, sharedApi } from '@/services/api'

export default {
  name: 'ReportsView',
  
  data() {
    return {
      loading: false,
      currentReport: null,
      currentReportType: null,
      detailedData: [],
      branches: [],
      
      filters: {
        start_date: this.getDefaultStartDate(),
        end_date: this.getDefaultEndDate(),
        branch_id: ''
      }
    }
  },
  
  computed: {
    selectedBranchName() {
      if (!this.filters.branch_id) return null
      const branch = this.branches.find(b => b.id == this.filters.branch_id)
      return branch ? branch.name : null
    }
  },
  
  async mounted() {
    await this.loadBranches()
  },
  
  methods: {
    // Inicialización
    getDefaultStartDate() {
      const date = new Date()
      date.setDate(1)
      return date.toISOString().split('T')[0]
    },
    
    getDefaultEndDate() {
      return new Date().toISOString().split('T')[0]
    },
    
    async loadBranches() {
      try {
        const response = await sharedApi.getBranches()
        this.branches = response.data.branches || response.data.data || response.data || []
        console.log('Branches loaded:', this.branches)
      } catch (error) {
        console.error('Error loading branches:', error)
      }
    },
    
    // Generación de reportes
    async generateReport(reportType) {
      this.loading = true
      this.currentReportType = reportType
      this.detailedData = []
      this.currentReport = null
      
      try {
        console.log(`Generando reporte: ${reportType}`)
        
        let apiCall
        const params = {
          per_page: 1000,
          branch_id: this.filters.branch_id || undefined
        }
        
        switch (reportType) {
          case 'reservations':
            apiCall = adminApi.getReservations(params)
            break
          case 'registrations':
            apiCall = adminApi.getRegistrations(params)
            break
          case 'users':
            apiCall = adminApi.getUsers(params)
            break
          default:
            throw new Error('Tipo de reporte no válido')
        }
        
        const response = await apiCall
        console.log('API Response:', response)
        
        // Extraer datos según la estructura de respuesta
        let allData = []
        if (response.data) {
          if (response.data.success && response.data.data) {
            allData = response.data.data
          } else if (Array.isArray(response.data)) {
            allData = response.data
          } else if (response.data.users) {
            allData = response.data.users
          } else if (response.data.data && Array.isArray(response.data.data)) {
            allData = response.data.data
          }
        }
        
        // Filtrar por fechas
        this.detailedData = this.filterByDate(allData, reportType)
        
        // Generar estadísticas enriquecidas
        this.currentReport = this.generateEnrichedStatistics(reportType)
        
        console.log('Report generated successfully')
        
      } catch (error) {
        console.error('Error generating report:', error)
        alert('Error al generar reporte: ' + error.message)
      } finally {
        this.loading = false
      }
    },
    
    // Filtrar datos por fecha
    filterByDate(data, reportType) {
      if (!data || data.length === 0) return []
      
      const startDate = new Date(this.filters.start_date)
      const endDate = new Date(this.filters.end_date)
      endDate.setHours(23, 59, 59, 999)
      
      return data.filter(item => {
        let itemDate
        
        switch (reportType) {
          case 'reservations':
            itemDate = new Date(item.created_at || item.check_in_date)
            break
          case 'registrations':
            itemDate = new Date(item.actual_check_in || item.created_at)
            break
          case 'users':
            itemDate = new Date(item.created_at)
            break
          default:
            return false
        }
        
        return itemDate >= startDate && itemDate <= endDate
      })
    },
    
    // Generar estadísticas enriquecidas
    generateEnrichedStatistics(reportType) {
      const total = this.detailedData.length
      
      switch (reportType) {
        case 'reservations':
          return this.generateReservationsStatistics(total)
        case 'registrations':
          return this.generateRegistrationsStatistics(total)
        case 'users':
          return this.generateUsersStatistics(total)
        default:
          return { summary: {} }
      }
    },

    generateReservationsStatistics(total) {
      const confirmed = this.detailedData.filter(r => r.status === 'confirmed').length
      const cancelled = this.detailedData.filter(r => r.status === 'cancelled').length
      const completed = this.detailedData.filter(r => r.status === 'completed').length
      const checkedIn = this.detailedData.filter(r => r.status === 'checked_in').length
      const pendingPayment = this.detailedData.filter(r => r.status === 'pending_payment').length

      const reservasConIngresos = this.detailedData.filter(r => 
        ['confirmed', 'checked_in', 'completed'].includes(r.status)
      )
      const totalRevenue = reservasConIngresos.reduce((sum, r) => sum + parseFloat(r.total_amount || 0), 0)
      const totalGuests = this.detailedData.reduce((sum, r) => sum + (r.adults_count + r.children_count), 0)
      const totalNights = this.detailedData.reduce((sum, r) => sum + (r.total_nights || 0), 0)
      
      const paymentMethods = {}
      this.detailedData.forEach(r => {
        const method = r.payment_method || 'unknown'
        paymentMethods[method] = (paymentMethods[method] || 0) + 1
      })

      const branchStats = {}
      this.detailedData.forEach(r => {
        const branchName = r.branch?.name || 'Sin sucursal'
        if (!branchStats[branchName]) {
          branchStats[branchName] = { count: 0, revenue: 0, guests: 0, nights: 0 }
        }
        branchStats[branchName].count++
        if (['confirmed', 'checked_in', 'completed'].includes(r.status)) {
          branchStats[branchName].revenue += parseFloat(r.total_amount || 0)
        }
        branchStats[branchName].guests += (r.adults_count + r.children_count)
        branchStats[branchName].nights += (r.total_nights || 0)
      })

      const customerStats = {}
      this.detailedData.forEach(r => {
        const userId = r.user?.id
        if (userId) {
          if (!customerStats[userId]) {
            customerStats[userId] = {
              user: r.user,
              reservations: 0,
              total_spent: 0,
              total_nights: 0
            }
          }
          customerStats[userId].reservations++
          if (['confirmed', 'checked_in', 'completed'].includes(r.status)) {
            customerStats[userId].total_spent += parseFloat(r.total_amount || 0)
          }
          customerStats[userId].total_nights += (r.total_nights || 0)
        }
      })

      const topCustomers = Object.values(customerStats)
        .sort((a, b) => b.total_spent - a.total_spent)
        .slice(0, 5)

      const roomStats = {}
      this.detailedData.forEach(r => {
        const roomId = r.room?.id
        if (roomId) {
          if (!roomStats[roomId]) {
            roomStats[roomId] = {
              room: r.room,
              reservations: 0,
              revenue: 0,
              occupancy_nights: 0
            }
          }
          roomStats[roomId].reservations++
          if (['confirmed', 'checked_in', 'completed'].includes(r.status)) {
            roomStats[roomId].revenue += parseFloat(r.total_amount || 0)
          }
          roomStats[roomId].occupancy_nights += (r.total_nights || 0)
        }
      })

      const topRooms = Object.values(roomStats)
        .sort((a, b) => b.revenue - a.revenue)
        .slice(0, 5)

      return {
        summary: {
          total_reservations: total,
          confirmed_reservations: confirmed,
          total_revenue: totalRevenue,
          total_guests: totalGuests,
          total_nights: totalNights,
          avg_revenue_per_reservation: total > 0 ? (totalRevenue / total) : 0,
          cancellation_rate: total > 0 ? (cancelled / total * 100) : 0,
          completion_rate: total > 0 ? (completed / total * 100) : 0
        },
        branch_stats: branchStats,
        payment_methods: paymentMethods,
        top_customers: topCustomers,
        top_rooms: topRooms
      }
    },

    generateRegistrationsStatistics(total) {
      const active = this.detailedData.filter(r => r.status === 'active').length
      const completed = this.detailedData.filter(r => r.status === 'completed').length
      const direct = this.detailedData.filter(r => 
        r.reservation?.reservation_code?.startsWith('DIR')
      ).length

      const branchStats = {}
      this.detailedData.forEach(r => {
        const branchName = r.branch?.name || 'Sin sucursal'
        if (!branchStats[branchName]) {
          branchStats[branchName] = { total: 0, active: 0, completed: 0, direct: 0 }
        }
        branchStats[branchName].total++
        if (r.status === 'active') branchStats[branchName].active++
        if (r.status === 'completed') branchStats[branchName].completed++
        if (r.reservation?.reservation_code?.startsWith('DIR')) branchStats[branchName].direct++
      })

      const hourlyStats = {}
      this.detailedData.forEach(r => {
        if (r.actual_check_in) {
          const hour = new Date(r.actual_check_in).getHours()
          hourlyStats[hour] = (hourlyStats[hour] || 0) + 1
        }
      })

      const totalGuests = this.detailedData.reduce((sum, r) => {
        const additionalGuests = r.additional_guests?.length || 0
        return sum + additionalGuests + 1
      }, 0)

      return {
        summary: {
          total_registrations: total,
          active_registrations: active,
          completed_registrations: completed,
          direct_registrations: direct,
          direct_percentage: total > 0 ? (direct / total * 100) : 0,
          completion_rate: total > 0 ? (completed / total * 100) : 0,
          total_guests: totalGuests
        },
        branch_stats: branchStats,
        hourly_distribution: hourlyStats
      }
    },

    generateUsersStatistics(total) {
      const active = this.detailedData.filter(u => u.is_active).length
      const inactive = total - active

      const documentTypes = {}
      this.detailedData.forEach(u => {
        const docType = u.document_type || 'unknown'
        documentTypes[docType] = (documentTypes[docType] || 0) + 1
      })

      return {
        summary: {
          total_new_users: total,
          active_new_users: active,
          inactive_new_users: inactive,
          activation_rate: total > 0 ? (active / total * 100) : 0
        },
        document_type_distribution: documentTypes
      }
    },

    getHourlyPercentage(count) {
      if (!this.currentReport.hourly_distribution) return 0
      const maxCount = Math.max(...Object.values(this.currentReport.hourly_distribution))
      return maxCount > 0 ? (count / maxCount * 100) : 0
    },
    
    clearReport() {
      this.currentReport = null
      this.currentReportType = null
      this.detailedData = []
    },
    
    // Descargar PDF
    downloadPDF() {
      try {
        const htmlContent = this.generateReportHTML()
        const printWindow = window.open('', '_blank')
        printWindow.document.write(htmlContent)
        printWindow.document.close()
        
        setTimeout(() => {
          printWindow.print()
          printWindow.close()
        }, 500)
        
      } catch (error) {
        console.error('Error generating PDF:', error)
        alert('Error al generar PDF: ' + error.message)
      }
    },
    
    generateReportHTML() {
      const title = this.getReportTitle(this.currentReportType)
      const period = `${this.formatDate(this.filters.start_date)} - ${this.formatDate(this.filters.end_date)}`
      const branch = this.selectedBranchName ? ` | ${this.selectedBranchName}` : ''
      
      let html = `
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset="utf-8">
          <title>${title}</title>
          <style>
            body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #007bff; padding-bottom: 20px; }
            .summary { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 30px; }
            .summary-item { background: #f8f9fa; padding: 10px; border-radius: 5px; min-width: 150px; text-align: center; }
            .summary-item h4 { margin: 0; color: #007bff; font-size: 16px; }
            .summary-item small { color: #666; font-size: 10px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 10px; }
            th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
            th { background-color: #007bff; color: white; font-size: 10px; }
            .badge { padding: 2px 6px; border-radius: 3px; color: white; font-size: 9px; }
            .bg-success { background-color: #28a745; }
            .bg-danger { background-color: #dc3545; }
            .bg-warning { background-color: #ffc107; color: #000; }
            .bg-secondary { background-color: #6c757d; }
            .bg-info { background-color: #17a2b8; }
            @media print { body { margin: 0; } }
          </style>
        </head>
        <body>
          <div class="header">
            <h1>${title}</h1>
            <p>Período: ${period}${branch}</p>
            <p><small>Generado: ${new Date().toLocaleString()}</small></p>
          </div>
      `
      
      // Resumen
      if (this.currentReport.summary) {
        html += '<div class="summary">'
        Object.entries(this.currentReport.summary).forEach(([key, value]) => {
          html += `
            <div class="summary-item">
              <h4>${this.formatValue(value, key)}</h4>
              <small>${this.formatLabel(key)}</small>
            </div>
          `
        })
        html += '</div>'
      }
      
      // Datos detallados
      if (this.detailedData.length > 0) {
        html += `<h3>Datos Detallados (${this.detailedData.length} registros)</h3>`
        html += this.generateTableHTML()
      }
      
      html += '</body></html>'
      return html
    },
    
    generateTableHTML() {
      let html = '<table><thead><tr>'
      
      this.getTableHeaders().forEach(header => {
        html += `<th>${header}</th>`
      })
      html += '</tr></thead><tbody>'
      
      this.detailedData.forEach(item => {
        html += '<tr>'
        
        if (this.currentReportType === 'reservations') {
          html += `
            <td>${item.reservation_code}</td>
            <td>${item.user?.name}<br><small>${item.user?.email}</small></td>
            <td>${item.room?.room_number}</td>
            <td>${this.formatDate(item.check_in_date)}</td>
            <td>${this.formatDate(item.check_out_date)}</td>
            <td>${item.total_nights}</td>
            <td>${item.adults_count + item.children_count}</td>
            <td><span class="badge ${this.getStatusClass(item.status).replace('bg-', 'bg-')}">${this.getStatusText(item.status)}</span></td>
            <td>${this.formatCurrency(item.total_amount)}</td>
          `
        } else if (this.currentReportType === 'registrations') {
          html += `
            <td>${item.registration_code}</td>
            <td>${item.user?.name}<br><small>${item.user?.email}</small></td>
            <td>${item.room?.room_number}</td>
            <td>${this.formatDateTime(item.actual_check_in)}</td>
            <td>${item.actual_check_out ? this.formatDateTime(item.actual_check_out) : 'Activo'}</td>
            <td>${this.calculateDuration(item.actual_check_in, item.actual_check_out)}</td>
            <td>${this.getTotalGuests(item)}</td>
            <td><span class="badge ${this.getRegistrationStatusClass(item.status).replace('bg-', 'bg-')}">${this.getRegistrationStatusText(item.status)}</span></td>
            <td>${item.reservation?.reservation_code?.startsWith('DIR') ? 'Directo' : 'Reserva'}</td>
          `
        } else if (this.currentReportType === 'users') {
          html += `
            <td>${item.name}</td>
            <td>${item.email}</td>
            <td>${item.phone || '-'}</td>
            <td>${item.document_type}: ${item.document_number}</td>
            <td>${this.formatDate(item.created_at)}</td>
            <td><span class="badge ${item.is_active ? 'bg-success' : 'bg-danger'}">${item.is_active ? 'Activo' : 'Inactivo'}</span></td>
          `
        }
        
        html += '</tr>'
      })
      
      html += '</tbody></table>'
      return html
    },
    
    // Métodos de utilidad
    getReportTitle(type) {
      const titles = {
        reservations: 'Reporte Detallado de Reservas',
        registrations: 'Reporte Detallado de Check-ins',
        users: 'Reporte de Usuarios Nuevos'
      }
      return titles[type] || 'Reporte'
    },
    
    getDetailTitle() {
      const titles = {
        reservations: 'Detalle de Reservas',
        registrations: 'Detalle de Registros',
        users: 'Detalle de Usuarios'
      }
      return titles[this.currentReportType] || 'Detalle'
    },
    
    getTableHeaders() {
      const headers = {
        reservations: ['Código', 'Cliente', 'Habitación', 'Check-in', 'Check-out', 'Noches', 'Huéspedes', 'Estado', 'Total'],
        registrations: ['Código', 'Cliente', 'Habitación', 'Check-in Real', 'Check-out Real', 'Duración', 'Huéspedes', 'Estado', 'Tipo'],
        users: ['Nombre', 'Email', 'Teléfono', 'Documento', 'Registro', 'Estado']
      }
      return headers[this.currentReportType] || []
    },

    getMetricClass(key) {
      if (key.includes('revenue') || key.includes('total_amount')) return 'revenue'
      if (key.includes('confirmed') || key.includes('active')) return 'success'
      if (key.includes('cancelled') || key.includes('inactive')) return 'danger'
      if (key.includes('pending') || key.includes('rate')) return 'warning'
      return 'primary'
    },

    getMetricIcon(key) {
      if (key.includes('revenue') || key.includes('amount')) return 'pi pi-dollar'
      if (key.includes('reservations') || key.includes('registrations')) return 'pi pi-calendar'
      if (key.includes('users') || key.includes('guests')) return 'pi pi-users'
      if (key.includes('nights')) return 'pi pi-moon'
      if (key.includes('rate') || key.includes('percentage')) return 'pi pi-chart-line'
      return 'pi pi-info-circle'
    },
    
    formatValue(value, key) {
      if (value === null || value === undefined) return '-'
      
      if (key.includes('amount') || key.includes('revenue') || key.includes('total') || key.includes('fee')) {
        return this.formatCurrency(value)
      }
      
      if (key.includes('rate') || key.includes('percentage')) {
        return value.toFixed(1) + '%'
      }
      
      if (typeof value === 'number') {
        return this.formatNumber(value)
      }
      
      return value
    },
    
    formatLabel(key) {
      const labels = {
        total_reservations: 'Total Reservas',
        confirmed_reservations: 'Confirmadas',
        total_revenue: 'Ingresos Totales',
        total_guests: 'Total Huéspedes',
        total_nights: 'Total Noches',
        avg_revenue_per_reservation: 'Promedio por Reserva',
        cancellation_rate: 'Tasa Cancelación',
        completion_rate: 'Tasa Finalización',
        total_registrations: 'Total Registros',
        active_registrations: 'Activos',
        completed_registrations: 'Completados',
        direct_registrations: 'Directos',
        direct_percentage: '% Directos',
        total_new_users: 'Usuarios Nuevos',
        active_new_users: 'Activos',
        inactive_new_users: 'Inactivos',
        activation_rate: 'Tasa Activación'
      }
      return labels[key] || key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
    },
    
    formatCurrency(value) {
      if (!value && value !== 0) return 'Bs. 0.00'
      return 'Bs. ' + Number(value).toLocaleString('es-ES', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
    },
    
    formatNumber(value) {
      if (!value && value !== 0) return '0'
      return Number(value).toLocaleString('es-ES')
    },
    
    formatDate(date) {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('es-ES')
    },
    
    formatDateTime(dateTime) {
      if (!dateTime) return '-'
      return new Date(dateTime).toLocaleString('es-ES')
    },
    
    getStatusClass(status) {
      const classes = {
        confirmed: 'status-badge success',
        checked_in: 'status-badge info',
        completed: 'status-badge secondary',
        cancelled: 'status-badge danger',
        pending_payment: 'status-badge warning'
      }
      return classes[status] || 'status-badge warning'
    },
    
    getStatusText(status) {
      const texts = {
        confirmed: 'Confirmada',
        checked_in: 'Check-in',
        completed: 'Completada',
        cancelled: 'Cancelada',
        pending_payment: 'Pendiente'
      }
      return texts[status] || status
    },
    
    getRegistrationStatusClass(status) {
      const classes = {
        active: 'status-badge success',
        completed: 'status-badge secondary'
      }
      return classes[status] || 'status-badge warning'
    },
    
    getRegistrationStatusText(status) {
      const texts = {
        active: 'Activo',
        completed: 'Completado'
      }
      return texts[status] || status
    },
    
    getTotalGuests(registration) {
      const additional = registration.additional_guests?.length || 0
      return additional + 1
    },
    
    calculateDuration(checkIn, checkOut) {
      if (!checkIn) return '-'
      
      const start = new Date(checkIn)
      const end = checkOut ? new Date(checkOut) : new Date()
      const diffTime = Math.abs(end - start)
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      
      return diffDays + (diffDays === 1 ? ' día' : ' días')
    }
  }
}
</script>

<style scoped>
.reports-dashboard {
  background-color: #f8f9fa;
  min-height: 100vh;
  padding: 1.5rem 0;
}

.container-xl {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
}

/* Header profesional */
.reports-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem;
  border-radius: 1rem;
  margin-bottom: 2rem;
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-title {
  font-size: 1.75rem;
  font-weight: 600;
  margin: 0;
}

.header-subtitle {
  color: rgba(255, 255, 255, 0.9);
  margin: 0.5rem 0 0 0;
  font-size: 0.95rem;
}

.header-actions {
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

.btn-success {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

.btn-success:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-outline-secondary {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-outline-secondary:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-1px);
}

/* Cards */
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

/* Filtros */
.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-label {
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.form-control, .form-select {
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.form-control:focus, .form-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Grid de reportes */
.reports-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.report-card {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 1rem;
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.report-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  border-color: #3b82f6;
}

.report-icon {
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

.report-icon.reservations {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.report-icon.registrations {
  background: linear-gradient(135deg, #f59e0b, #d97706);
}

.report-icon.users {
  background: linear-gradient(135deg, #10b981, #059669);
}

.report-content {
  flex: 1;
}

.report-content h6 {
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
  font-size: 1rem;
}

.report-content p {
  color: #6b7280;
  margin: 0 0 1rem 0;
  font-size: 0.875rem;
}

.report-features {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.feature-tag {
  background: #e5e7eb;
  color: #374151;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 500;
}

/* Loading */
.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 400px;
}

.loading-content {
  text-align: center;
  color: #6b7280;
}

.loading-spinner {
  font-size: 3rem;
  color: #3b82f6;
  margin-bottom: 1rem;
}

.loading-content h4 {
  color: #1f2937;
  margin-bottom: 0.5rem;
}

/* Vista del reporte */
.report-view {
  animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.report-header {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
}

.report-header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.report-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.report-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6b7280;
  font-size: 0.875rem;
}

.meta-item i {
  color: #9ca3af;
}

/* Métricas */
.metrics-section {
  margin-bottom: 2rem;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

.metric-card.revenue::before {
  background: linear-gradient(90deg, #10b981, #059669);
}
.metric-card.success::before {
  background: linear-gradient(90deg, #10b981, #059669);
}
.metric-card.danger::before {
  background: linear-gradient(90deg, #ef4444, #dc2626);
}
.metric-card.warning::before {
  background: linear-gradient(90deg, #f59e0b, #d97706);
}
.metric-card.primary::before {
  background: linear-gradient(90deg, #3b82f6, #1d4ed8);
}

.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.metric-icon {
  width: 50px;
  height: 50px;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: white;
  flex-shrink: 0;
}

.metric-card.revenue .metric-icon {
  background: linear-gradient(135deg, #10b981, #059669);
}
.metric-card.success .metric-icon {
  background: linear-gradient(135deg, #10b981, #059669);
}
.metric-card.danger .metric-icon {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}
.metric-card.warning .metric-icon {
  background: linear-gradient(135deg, #f59e0b, #d97706);
}
.metric-card.primary .metric-icon {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.metric-content {
  flex: 1;
}

.metric-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  line-height: 1;
}

.metric-label {
  color: #6b7280;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

/* Tabla de datos */
.detailed-data-card {
  margin-bottom: 2rem;
}

.records-count {
  background: #f3f4f6;
  color: #6b7280;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.table-container {
  overflow-x: auto;
  max-height: 600px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.table-header {
  background: #1f2937;
  color: white;
  padding: 1rem 0.75rem;
  text-align: left;
  font-weight: 600;
  position: sticky;
  top: 0;
  z-index: 10;
}

.table-row {
  border-bottom: 1px solid #f3f4f6;
  transition: background-color 0.2s;
}

.table-row:hover {
  background-color: #f9fafb;
}

.table-cell {
  padding: 0.75rem;
  vertical-align: top;
}

.code-cell strong {
  color: #1f2937;
  font-weight: 600;
}

.user-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.user-cell strong {
  color: #1f2937;
}

.user-cell small {
  color: #6b7280;
  font-size: 0.75rem;
}

.room-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.room-number {
  background: #f3f4f6;
  color: #374151;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
  font-size: 0.8rem;
  font-weight: 500;
}

.room-cell small {
  color: #6b7280;
  font-size: 0.75rem;
}

.document-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.doc-type {
  font-weight: 500;
  color: #374151;
}

.doc-number {
  color: #6b7280;
  font-size: 0.875rem;
}

.status-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
}

.status-badge.success {
  background: #dcfce7;
  color: #166534;
}

.status-badge.info {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.warning {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.danger {
  background: #fef2f2;
  color: #991b1b;
}

.status-badge.secondary {
  background: #f3f4f6;
  color: #374151;
}

.status-badge.active {
  background: #dcfce7;
  color: #166534;
}

.status-badge.inactive {
  background: #fef2f2;
  color: #991b1b;
}

.nights-badge, .guests-badge, .duration-badge {
  background: #e5e7eb;
  color: #374151;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.type-badge {
  background: #dbeafe;
  color: #1e40af;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.amount-text {
  color: #059669;
  font-weight: 600;
}

/* Análisis adicional */
.additional-analysis {
  margin-top: 2rem;
}

.analysis-section {
  margin-bottom: 2rem;
}

.analysis-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
}

.analysis-card {
  background: white;
  border-radius: 1rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.analysis-card.full-width {
  grid-column: 1 / -1;
}

.analysis-header {
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  padding: 1rem 1.5rem;
}

.analysis-title {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.analysis-content {
  padding: 1.5rem;
}

/* Top clientes */
.top-customers {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.customer-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.75rem;
  border: 1px solid #e5e7eb;
}

.customer-rank {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.customer-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.customer-info strong {
  color: #1f2937;
  font-weight: 600;
}

.customer-info small {
  color: #6b7280;
  font-size: 0.75rem;
}

.customer-stats {
  display: flex;
  gap: 1rem;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.stat-value {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.875rem;
}

.stat-label {
  color: #6b7280;
  font-size: 0.75rem;
}

/* Top habitaciones */
.top-rooms {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.room-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.75rem;
  border: 1px solid #e5e7eb;
}

.room-rank {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.room-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.room-info .room-number {
  background: #e5e7eb;
  color: #374151;
  padding: 0.25rem 0.5rem;
  border-radius: 0.375rem;
  font-size: 0.8rem;
  font-weight: 500;
  display: inline-block;
  width: fit-content;
}

.room-info small {
  color: #6b7280;
  font-size: 0.75rem;
}

.room-stats {
  display: flex;
  gap: 1rem;
}

/* Estadísticas por sucursal */
.branch-stats {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.branch-stat-item {
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.75rem;
  border: 1px solid #e5e7eb;
}

.branch-name {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.75rem;
  font-size: 0.875rem;
}

.branch-metrics {
  display: flex;
  justify-content: space-between;
}

.metric-group {
  display: flex;
  gap: 2rem;
}

.metric-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.metric-item .metric-value {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.875rem;
}

.metric-item .metric-label {
  color: #6b7280;
  font-size: 0.75rem;
}

/* Distribución por horas */
.hourly-distribution {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
  gap: 1rem;
}

.hour-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
}

.hour-label {
  font-weight: 500;
  color: #374151;
  font-size: 0.75rem;
}

.hour-bar {
  width: 100%;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
}

.hour-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #1d4ed8);
  transition: width 0.3s ease;
}

.hour-count {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.875rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .header-actions {
    justify-content: center;
  }

  .filters-grid {
    grid-template-columns: 1fr;
  }

  .reports-grid {
    grid-template-columns: 1fr;
  }

  .report-card {
    flex-direction: column;
    text-align: center;
  }

  .metrics-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }

  .report-header-content {
    flex-direction: column;
    gap: 1rem;
  }

  .report-meta {
    flex-direction: column;
    gap: 0.5rem;
  }

  .analysis-grid {
    grid-template-columns: 1fr;
  }

  .customer-item,
  .room-item {
    flex-direction: column;
    text-align: center;
    gap: 0.75rem;
  }

  .customer-stats,
  .room-stats {
    justify-content: center;
  }

  .branch-metrics {
    flex-direction: column;
    gap: 1rem;
  }

  .metric-group {
    justify-content: center;
  }

  .hourly-distribution {
    grid-template-columns: repeat(4, 1fr);
  }

  .table-container {
    font-size: 0.8rem;
  }
}

@media (max-width: 480px) {
  .container-xl {
    padding: 0 0.5rem;
  }

  .reports-header {
    padding: 1.5rem;
  }

  .card-body {
    padding: 1rem;
  }

  .metrics-grid {
    grid-template-columns: 1fr;
  }

  .metric-card {
    flex-direction: column;
    text-align: center;
    gap: 0.75rem;
  }

  .customer-stats,
  .room-stats {
    flex-direction: column;
    gap: 0.5rem;
  }

  .hourly-distribution {
    grid-template-columns: repeat(3, 1fr);
  }

  .table-container {
    font-size: 0.75rem;
  }

  .table-header,
  .table-cell {
    padding: 0.5rem 0.25rem;
  }
}
</style>