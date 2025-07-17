<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Reportes y Estadísticas</h4>
            <div>
              <button 
                v-if="currentReport"
                class="btn btn-success me-2"
                @click="downloadPDF"
                :disabled="loading"
              >
                <i class="fas fa-download"></i> Descargar PDF
              </button>
              <button 
                v-if="currentReport"
                class="btn btn-outline-secondary"
                @click="clearReport"
              >
                <i class="fas fa-times"></i> Cerrar
              </button>
            </div>
          </div>
          
          <div class="card-body">
            <!-- Panel de Selección de Reportes -->
            <div v-if="!currentReport" class="report-selection">
              <!-- Filtros Globales -->
              <div class="row mb-4">
                <div class="col-md-4">
                  <label class="form-label">Fecha Inicio</label>
                  <input 
                    v-model="filters.start_date"
                    type="date" 
                    class="form-control"
                  >
                </div>
                <div class="col-md-4">
                  <label class="form-label">Fecha Fin</label>
                  <input 
                    v-model="filters.end_date"
                    type="date" 
                    class="form-control"
                  >
                </div>
                <div class="col-md-4">
                  <label class="form-label">Sucursal</label>
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
              
              <!-- Tipos de reportes -->
              <div class="row">
                <div class="col-md-6 col-lg-4 mb-3">
                  <div class="card report-card" @click="generateReport('reservations')">
                    <div class="card-body text-center">
                      <i class="fas fa-calendar-alt fa-3x mb-3 text-primary"></i>
                      <h5 class="card-title">Reporte de Reservas</h5>
                      <p class="card-text">Listado completo de reservas con detalles</p>
                    </div>
                  </div>
                </div>
                
                <div class="col-md-6 col-lg-4 mb-3">
                  <div class="card report-card" @click="generateReport('registrations')">
                    <div class="card-body text-center">
                      <i class="fas fa-sign-in-alt fa-3x mb-3 text-warning"></i>
                      <h5 class="card-title">Reporte de Check-ins</h5>
                      <p class="card-text">Listado completo de registros y check-ins</p>
                    </div>
                  </div>
                </div>
                
                <div class="col-md-6 col-lg-4 mb-3">
                  <div class="card report-card" @click="generateReport('users')">
                    <div class="card-body text-center">
                      <i class="fas fa-users fa-3x mb-3 text-info"></i>
                      <h5 class="card-title">Reporte de Usuarios</h5>
                      <p class="card-text">Listado y análisis de usuarios</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="text-center p-5">
              <div class="spinner-border spinner-border-lg text-primary" role="status">
                <span class="visually-hidden">Generando reporte...</span>
              </div>
              <p class="mt-3">Generando reporte...</p>
            </div>

            <!-- Vista del Reporte -->
            <div v-if="currentReport && !loading" class="report-view">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                  <h3>{{ getReportTitle(currentReportType) }}</h3>
                  <p class="text-muted mb-0">
                    Período: {{ formatDate(filters.start_date) }} - {{ formatDate(filters.end_date) }}
                    <span v-if="selectedBranchName"> | {{ selectedBranchName }}</span>
                  </p>
                </div>
                <div class="text-end">
                  <small class="text-muted">Generado: {{ new Date().toLocaleString() }}</small>
                </div>
              </div>

              <!-- Resumen del Reporte -->
              <div class="row mb-4" v-if="currentReport.summary">
                <div 
                  v-for="(value, key) in currentReport.summary" 
                  :key="key"
                  class="col-md-3 mb-3"
                >
                  <div class="card border-0 bg-light">
                    <div class="card-body text-center p-3">
                      <h4 class="mb-1">{{ formatValue(value, key) }}</h4>
                      <small class="text-muted">{{ formatLabel(key) }}</small>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Datos Detallados -->
              <div class="mb-4">
                <div class="card">
                  <div class="card-header">
                    <h5>{{ getDetailTitle() }} ({{ detailedData.length }} registros)</h5>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-sm table-striped">
                        <thead class="table-dark">
                          <tr>
                            <th v-for="header in getTableHeaders()" :key="header">{{ header }}</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="item in detailedData" :key="item.id">
                            <td v-if="currentReportType === 'reservations'">
                              <strong>{{ item.reservation_code }}</strong>
                            </td>
                            <td v-if="currentReportType === 'reservations'">
                              <strong>{{ item.user?.name }}</strong><br>
                              <small class="text-muted">{{ item.user?.email }}</small>
                            </td>
                            <td v-if="currentReportType === 'reservations'">
                              <span class="badge bg-secondary">{{ item.room?.room_number }}</span><br>
                              <small>{{ item.room?.room_type?.name }}</small>
                            </td>
                            <td v-if="currentReportType === 'reservations'">{{ formatDate(item.check_in_date) }}</td>
                            <td v-if="currentReportType === 'reservations'">{{ formatDate(item.check_out_date) }}</td>
                            <td v-if="currentReportType === 'reservations'">{{ item.total_nights }}</td>
                            <td v-if="currentReportType === 'reservations'">{{ item.adults_count + item.children_count }}</td>
                            <td v-if="currentReportType === 'reservations'">
                              <span :class="getStatusClass(item.status)" class="badge">
                                {{ getStatusText(item.status) }}
                              </span>
                            </td>
                            <td v-if="currentReportType === 'reservations'">
                              <strong>{{ formatCurrency(item.total_amount) }}</strong>
                            </td>

                            <!-- Registrations -->
                            <td v-if="currentReportType === 'registrations'">
                              <strong>{{ item.registration_code }}</strong>
                            </td>
                            <td v-if="currentReportType === 'registrations'">
                              <strong>{{ item.user?.name }}</strong><br>
                              <small class="text-muted">{{ item.user?.email }}</small>
                            </td>
                            <td v-if="currentReportType === 'registrations'">
                              <span class="badge bg-secondary">{{ item.room?.room_number }}</span><br>
                              <small>{{ item.room?.room_type?.name }}</small>
                            </td>
                            <td v-if="currentReportType === 'registrations'">{{ formatDateTime(item.actual_check_in) }}</td>
                            <td v-if="currentReportType === 'registrations'">
                              {{ item.actual_check_out ? formatDateTime(item.actual_check_out) : 'Activo' }}
                            </td>
                            <td v-if="currentReportType === 'registrations'">{{ calculateDuration(item.actual_check_in, item.actual_check_out) }}</td>
                            <td v-if="currentReportType === 'registrations'">{{ getTotalGuests(item) }}</td>
                            <td v-if="currentReportType === 'registrations'">
                              <span :class="getRegistrationStatusClass(item.status)" class="badge">
                                {{ getRegistrationStatusText(item.status) }}
                              </span>
                            </td>
                            <td v-if="currentReportType === 'registrations'">
                              <span class="badge bg-info">
                                {{ item.reservation?.reservation_code?.startsWith('DIR') ? 'Directo' : 'Reserva' }}
                              </span>
                            </td>

                            <!-- Users -->
                            <td v-if="currentReportType === 'users'"><strong>{{ item.name }}</strong></td>
                            <td v-if="currentReportType === 'users'">{{ item.email }}</td>
                            <td v-if="currentReportType === 'users'">{{ item.phone || '-' }}</td>
                            <td v-if="currentReportType === 'users'">{{ item.document_type }}: {{ item.document_number }}</td>
                            <td v-if="currentReportType === 'users'">{{ formatDate(item.created_at) }}</td>
                            <td v-if="currentReportType === 'users'">
                              <span :class="item.is_active ? 'badge bg-success' : 'badge bg-danger'">
                                {{ item.is_active ? 'Activo' : 'Inactivo' }}
                              </span>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Datos Enriquecidos Adicionales -->
              
              <!-- Top Clientes (para reservas) -->
              <div v-if="currentReportType === 'reservations' && currentReport.top_customers && currentReport.top_customers.length > 0" class="mb-4">
                <div class="row">
                  <div class="col-md-6">
                    <div class="card">
                      <div class="card-header">
                        <h5>Top 5 Clientes por Ingresos</h5>
                      </div>
                      <div class="card-body">
                        <div class="table-responsive">
                          <table class="table table-sm">
                            <thead class="table-dark">
                              <tr>
                                <th>Cliente</th>
                                <th>Reservas</th>
                                <th>Total Gastado</th>
                                <th>Noches</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr v-for="customer in currentReport.top_customers" :key="customer.user.id">
                                <td>
                                  <strong>{{ customer.user.name }}</strong><br>
                                  <small class="text-muted">{{ customer.user.email }}</small>
                                </td>
                                <td>{{ customer.reservations }}</td>
                                <td><strong class="text-success">{{ formatCurrency(customer.total_spent) }}</strong></td>
                                <td>{{ customer.total_nights }}</td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <div class="col-md-6">
                    <div class="card">
                      <div class="card-header">
                        <h5>Top 5 Habitaciones por Ingresos</h5>
                      </div>
                      <div class="card-body">
                        <div class="table-responsive">
                          <table class="table table-sm">
                            <thead class="table-dark">
                              <tr>
                                <th>Habitación</th>
                                <th>Reservas</th>
                                <th>Ingresos</th>
                                <th>Noches</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr v-for="room in currentReport.top_rooms" :key="room.room.id">
                                <td>
                                  <span class="badge bg-secondary">{{ room.room.room_number }}</span><br>
                                  <small>{{ room.room.room_type?.name }}</small>
                                </td>
                                <td>{{ room.reservations }}</td>
                                <td><strong class="text-success">{{ formatCurrency(room.revenue) }}</strong></td>
                                <td>{{ room.occupancy_nights }}</td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Análisis por Sucursal -->
              <div v-if="currentReport.branch_stats" class="mb-4">
                <div class="card">
                  <div class="card-header">
                    <h5>Análisis por Sucursal</h5>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-sm">
                        <thead class="table-dark">
                          <tr>
                            <th>Sucursal</th>
                            <th v-if="currentReportType === 'reservations'">Reservas</th>
                            <th v-if="currentReportType === 'reservations'">Ingresos</th>
                            <th v-if="currentReportType === 'reservations'">Huéspedes</th>
                            <th v-if="currentReportType === 'reservations'">Noches</th>
                            <th v-if="currentReportType === 'registrations'">Total</th>
                            <th v-if="currentReportType === 'registrations'">Activos</th>
                            <th v-if="currentReportType === 'registrations'">Completados</th>
                            <th v-if="currentReportType === 'registrations'">Directos</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(stats, branchName) in currentReport.branch_stats" :key="branchName">
                            <td><strong>{{ branchName }}</strong></td>
                            <td v-if="currentReportType === 'reservations'">{{ stats.count }}</td>
                            <td v-if="currentReportType === 'reservations'">
                              <strong class="text-success">{{ formatCurrency(stats.revenue) }}</strong>
                            </td>
                            <td v-if="currentReportType === 'reservations'">{{ stats.guests }}</td>
                            <td v-if="currentReportType === 'reservations'">{{ stats.nights }}</td>
                            <td v-if="currentReportType === 'registrations'">{{ stats.total }}</td>
                            <td v-if="currentReportType === 'registrations'">
                              <span class="badge bg-success">{{ stats.active }}</span>
                            </td>
                            <td v-if="currentReportType === 'registrations'">
                              <span class="badge bg-secondary">{{ stats.completed }}</span>
                            </td>
                            <td v-if="currentReportType === 'registrations'">
                              <span class="badge bg-info">{{ stats.direct }}</span>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Métodos de Pago (para reservas) -->
              <div v-if="currentReportType === 'reservations' && currentReport.payment_methods" class="mb-4">
                <div class="row">
                  <div class="col-md-6">
                    <div class="card">
                      <div class="card-header">
                        <h5>Métodos de Pago</h5>
                      </div>
                      <div class="card-body">
                        <div class="table-responsive">
                          <table class="table table-sm">
                            <thead class="table-dark">
                              <tr>
                                <th>Método</th>
                                <th>Cantidad</th>
                                <th>Porcentaje</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr v-for="(count, method) in currentReport.payment_methods" :key="method">
                                <td><strong>{{ formatPaymentMethod(method) }}</strong></td>
                                <td>{{ count }}</td>
                                <td>
                                  <span class="badge bg-primary">
                                    {{ Math.round((count / detailedData.length) * 100) }}%
                                  </span>
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Check-ins por Hora (para registros) -->
              <div v-if="currentReportType === 'registrations' && currentReport.hourly_distribution" class="mb-4">
                <div class="card">
                  <div class="card-header">
                    <h5>Distribución de Check-ins por Hora</h5>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div v-for="(count, hour) in currentReport.hourly_distribution" :key="hour" class="col-md-2 col-sm-3 mb-2">
                        <div class="text-center p-2 border rounded">
                          <strong>{{ hour }}:00</strong><br>
                          <span class="badge bg-info">{{ count }}</span>
                        </div>
                      </div>
                    </div>
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
    
    // Generación de reportes simplificada
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
        
        // Llamar solo a las APIs que funcionan
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
          // Para registrations
          if (response.data.success && response.data.data) {
            allData = response.data.data
          }
          // Para reservations (array directo)
          else if (Array.isArray(response.data)) {
            allData = response.data
          }
          // Para users
          else if (response.data.users) {
            allData = response.data.users
          }
          // Fallback
          else if (response.data.data && Array.isArray(response.data.data)) {
            allData = response.data.data
          }
        }
        
        console.log('All data extracted:', allData.length, 'items')
        
        // Filtrar por fechas
        this.detailedData = this.filterByDate(allData, reportType)
        
        console.log('Filtered data:', this.detailedData.length, 'items')
        
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
      endDate.setHours(23, 59, 59, 999) // Incluir todo el día final
      
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
    
    // Generar estadísticas enriquecidas basadas en los datos
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
      // Estadísticas básicas por estado
      const confirmed = this.detailedData.filter(r => r.status === 'confirmed').length
      const cancelled = this.detailedData.filter(r => r.status === 'cancelled').length
      const completed = this.detailedData.filter(r => r.status === 'completed').length
      const checkedIn = this.detailedData.filter(r => r.status === 'checked_in').length
      const pendingPayment = this.detailedData.filter(r => r.status === 'pending_payment').length

      // Cálculos financieros
      const reservasConIngresos = this.detailedData.filter(r => 
        ['confirmed', 'checked_in', 'completed'].includes(r.status)
      )
      const totalRevenue = reservasConIngresos.reduce((sum, r) => sum + parseFloat(r.total_amount || 0), 0)
      const roomRevenue = reservasConIngresos.reduce((sum, r) => sum + parseFloat(r.room_total || 0), 0)
      const parkingRevenue = reservasConIngresos.reduce((sum, r) => sum + parseFloat(r.parking_fee || 0), 0)
      
      // Análisis de huéspedes
      const totalGuests = this.detailedData.reduce((sum, r) => sum + (r.adults_count + r.children_count), 0)
      const totalNights = this.detailedData.reduce((sum, r) => sum + (r.total_nights || 0), 0)
      
      // Reservas con estacionamiento
      const withParking = this.detailedData.filter(r => r.needs_parking).length
      
      // Métodos de pago
      const paymentMethods = {}
      this.detailedData.forEach(r => {
        const method = r.payment_method || 'unknown'
        paymentMethods[method] = (paymentMethods[method] || 0) + 1
      })

      // Análisis por sucursal
      const branchStats = {}
      this.detailedData.forEach(r => {
        const branchName = r.branch?.name || 'Sin sucursal'
        if (!branchStats[branchName]) {
          branchStats[branchName] = { 
            count: 0, 
            revenue: 0, 
            guests: 0,
            nights: 0
          }
        }
        branchStats[branchName].count++
        if (['confirmed', 'checked_in', 'completed'].includes(r.status)) {
          branchStats[branchName].revenue += parseFloat(r.total_amount || 0)
        }
        branchStats[branchName].guests += (r.adults_count + r.children_count)
        branchStats[branchName].nights += (r.total_nights || 0)
      })

      // Top clientes
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

      // Top habitaciones
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
          cancelled_reservations: cancelled,
          completed_reservations: completed,
          checked_in_reservations: checkedIn,
          pending_payment_reservations: pendingPayment,
          total_revenue: totalRevenue,
          room_revenue: roomRevenue,
          parking_revenue: parkingRevenue,
          total_guests: totalGuests,
          total_nights: totalNights,
          avg_guests_per_reservation: total > 0 ? (totalGuests / total) : 0,
          avg_nights_per_reservation: total > 0 ? (totalNights / total) : 0,
          avg_revenue_per_reservation: total > 0 ? (totalRevenue / total) : 0,
          reservations_with_parking: withParking,
          parking_percentage: total > 0 ? (withParking / total * 100) : 0,
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
      // Estadísticas básicas
      const active = this.detailedData.filter(r => r.status === 'active').length
      const completed = this.detailedData.filter(r => r.status === 'completed').length
      
      // Tipos de registro
      const direct = this.detailedData.filter(r => 
        r.reservation?.reservation_code?.startsWith('DIR')
      ).length
      const withReservation = total - direct

      // Análisis de duración de estadía
      const completedRegistrations = this.detailedData.filter(r => 
        r.status === 'completed' && r.actual_check_in && r.actual_check_out
      )
      
      let totalDurationDays = 0
      completedRegistrations.forEach(r => {
        const checkIn = new Date(r.actual_check_in)
        const checkOut = new Date(r.actual_check_out)
        const duration = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24))
        totalDurationDays += duration
      })
      
      const avgStayDuration = completedRegistrations.length > 0 ? 
        totalDurationDays / completedRegistrations.length : 0

      // Análisis por sucursal
      const branchStats = {}
      this.detailedData.forEach(r => {
        const branchName = r.branch?.name || 'Sin sucursal'
        if (!branchStats[branchName]) {
          branchStats[branchName] = { 
            total: 0, 
            active: 0, 
            completed: 0,
            direct: 0
          }
        }
        branchStats[branchName].total++
        if (r.status === 'active') branchStats[branchName].active++
        if (r.status === 'completed') branchStats[branchName].completed++
        if (r.reservation?.reservation_code?.startsWith('DIR')) branchStats[branchName].direct++
      })

      // Análisis por hora de check-in
      const hourlyStats = {}
      this.detailedData.forEach(r => {
        if (r.actual_check_in) {
          const hour = new Date(r.actual_check_in).getHours()
          hourlyStats[hour] = (hourlyStats[hour] || 0) + 1
        }
      })

      // Top habitaciones por registros
      const roomStats = {}
      this.detailedData.forEach(r => {
        const roomId = r.room?.id
        if (roomId) {
          if (!roomStats[roomId]) {
            roomStats[roomId] = {
              room: r.room,
              checkins: 0,
              current_guest: null
            }
          }
          roomStats[roomId].checkins++
          if (r.status === 'active') {
            roomStats[roomId].current_guest = r.user
          }
        }
      })

      const topRooms = Object.values(roomStats)
        .sort((a, b) => b.checkins - a.checkins)
        .slice(0, 5)

      // Huéspedes totales
      const totalGuests = this.detailedData.reduce((sum, r) => {
        const additionalGuests = r.additional_guests?.length || 0
        return sum + additionalGuests + 1 // +1 por el huésped principal
      }, 0)

      return {
        summary: {
          total_registrations: total,
          active_registrations: active,
          completed_registrations: completed,
          direct_registrations: direct,
          reservation_registrations: withReservation,
          direct_percentage: total > 0 ? (direct / total * 100) : 0,
          completion_rate: total > 0 ? (completed / total * 100) : 0,
          avg_stay_duration: avgStayDuration,
          total_guests: totalGuests,
          avg_guests_per_registration: total > 0 ? (totalGuests / total) : 0
        },
        branch_stats: branchStats,
        hourly_distribution: hourlyStats,
        top_rooms: topRooms
      }
    },

    generateUsersStatistics(total) {
      // Estadísticas básicas
      const active = this.detailedData.filter(u => u.is_active).length
      const inactive = total - active

      // Análisis por tipo de documento
      const documentTypes = {}
      this.detailedData.forEach(u => {
        const docType = u.document_type || 'unknown'
        documentTypes[docType] = (documentTypes[docType] || 0) + 1
      })

      // Usuarios con reservas (si hay información de reservas)
      const usersWithReservations = this.detailedData.filter(u => 
        u.reservations_count && u.reservations_count > 0
      ).length

      // Análisis temporal de registro
      const registrationsByMonth = {}
      this.detailedData.forEach(u => {
        const month = new Date(u.created_at).toISOString().substring(0, 7) // YYYY-MM
        registrationsByMonth[month] = (registrationsByMonth[month] || 0) + 1
      })

      return {
        summary: {
          total_new_users: total,
          active_new_users: active,
          inactive_new_users: inactive,
          activation_rate: total > 0 ? (active / total * 100) : 0,
          users_with_reservations: usersWithReservations,
          reservation_rate: total > 0 ? (usersWithReservations / total * 100) : 0
        },
        document_type_distribution: documentTypes,
        monthly_registrations: registrationsByMonth
      }
    },
    
    clearReport() {
      this.currentReport = null
      this.currentReportType = null
      this.detailedData = []
    },
    
    // Descargar PDF simplificado
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
      
      // Headers
      this.getTableHeaders().forEach(header => {
        html += `<th>${header}</th>`
      })
      html += '</tr></thead><tbody>'
      
      // Datos
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
    
    // Utilidades
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
        cancelled_reservations: 'Canceladas',
        completed_reservations: 'Completadas',
        checked_in_reservations: 'Con Check-in',
        total_revenue: 'Ingresos Totales',
        avg_reservation_value: 'Valor Promedio',
        cancellation_rate: 'Tasa Cancelación',
        total_registrations: 'Total Registros',
        active_registrations: 'Activos',
        completed_registrations: 'Completados',
        direct_registrations: 'Directos',
        reservation_registrations: 'Con Reserva',
        direct_percentage: '% Directos',
        completion_rate: 'Tasa Finalización',
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
        confirmed: 'bg-success',
        checked_in: 'bg-info',
        completed: 'bg-secondary',
        cancelled: 'bg-danger',
        pending_payment: 'bg-warning'
      }
      return classes[status] || 'bg-warning'
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
        active: 'bg-success',
        completed: 'bg-secondary'
      }
      return classes[status] || 'bg-warning'
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
    },

    formatPaymentMethod(method) {
      const methods = {
        'cash': 'Efectivo',
        'qr': 'QR/Transferencia',
        'card': 'Tarjeta',
        'unknown': 'No especificado'
      }
      return methods[method] || method
    }
  }
}
</script>

<style scoped>
.report-card {
  cursor: pointer;
  transition: all 0.3s ease;
  border: 2px solid transparent;
}

.report-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
  border-color: #007bff;
}

.spinner-border-lg {
  width: 3rem;
  height: 3rem;
}

.report-view {
  animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.table-responsive {
  max-height: 600px;
  overflow-y: auto;
}
</style>