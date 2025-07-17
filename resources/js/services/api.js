import axios from 'axios'

// Configuración base de axios
axios.defaults.baseURL = 'http://localhost:8000/api' // Cambia por tu URL
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.headers.common['Content-Type'] = 'application/json'

// Interceptor para incluir token de autenticación si existe
axios.interceptors.request.use(
  config => {
    const token = localStorage.getItem('auth_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  error => Promise.reject(error)
)

// Servicios de autenticación
export const authApi = {
  register(userData) {
    return axios.post('/auth/register', userData)
  },
  
  login(credentials) {
    return axios.post('/auth/login', credentials)
  },
  
  logout() {
    return axios.post('/auth/logout')
  },
  
  getUser() {
    return axios.get('/auth/user')
  },
  
  updateProfile(profileData) {
    return axios.put('/auth/profile', profileData)
  },
  
  changePassword(passwordData) {
    return axios.put('/auth/change-password', passwordData)
  }
}

// Servicios públicos
export const publicApi = {
  // Sucursales
  getBranches() {
    return axios.get('/public/branches')
  },
  
  getBranch(id) {
    return axios.get(`/public/branches/${id}`)
  },
  
  // Habitaciones
  getRooms(params = {}) {
    return axios.get('/public/rooms', { params })
  },
  
  getRoom(id) {
    return axios.get(`/public/rooms/${id}`)
  },
  
  getRoomTypes() {
    return axios.get('/public/room-types')
  },
  
  // Disponibilidad
  checkAvailability(data) {
    return axios.post('/public/rooms/check-availability', data)
  }
}

// Servicios de cliente
export const customerApi = {
  // Reservas
  getReservations() {
    return axios.get('/customer/reservations')
  },
  
  createReservation(data) {
    console.log('Creating reservation with data:', data);
    
    return axios.post('/customer/reservations', data)
  },
  
  getReservation(id) {
    return axios.get(`/customer/reservations/${id}`)
  },
  
  cancelReservation(id) {
    return axios.put(`/customer/reservations/${id}/cancel`)
  },
  
  // Pagos
  uploadPaymentProof(data) {
    return axios.post('/customer/payments/upload-proof', data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  
  getPaymentsByReservation(reservationId) {
    return axios.get(`/customer/payments/reservation/${reservationId}`)
  }
}

// Servicios de administrador
export const adminApi = {
  // Dashboard
  getDashboardStats() {
    return axios.get('/admin/dashboard/stats')
  },
  
  // Usuarios
  getUsers(params = {}) {
    return axios.get('/admin/users', { params })
  },
  
  createUser(userData) {
    return axios.post('/admin/users', userData)
  },
  
  getUser(id) {
    return axios.get(`/admin/users/${id}`)
  },
  
  updateUser(id, userData) {
    return axios.put(`/admin/users/${id}`, userData)
  },
  
  deleteUser(id) {
    return axios.delete(`/admin/users/${id}`)
  },
  
  changeUserPassword(id, passwordData) {
    return axios.put(`/admin/users/${id}/change-password`, passwordData)
  },
  
  toggleUserStatus(id) {
    return axios.put(`/admin/users/${id}/toggle-status`)
  },
  
  getUserStats() {
    return axios.get('/admin/users/stats/summary')
  },
  
  getRoles() {
    return axios.get('/admin/roles')
  },
  
  // Habitaciones - CORREGIDO
  getRooms(params = {}) {
    return axios.get('/admin/rooms', { params })
  },
  
  createRoom(roomData) {
    return axios.post('/admin/rooms', roomData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  
  getRoom(id) {
    return axios.get(`/admin/rooms/${id}`)
  },
  
  updateRoom(id, roomData) {
    return axios.put(`/admin/rooms/${id}`, roomData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  
  deleteRoom(id) {
    return axios.delete(`/admin/rooms/${id}`)
  },
  
  toggleRoomStatus(id) {
    return axios.put(`/admin/rooms/${id}/toggle-status`)
  },
  
  updateRoomStatus(id, statusData) {
    return axios.put(`/admin/rooms/${id}/status`, statusData)
  },
  
  getRoomStats() {
    return axios.get('/admin/rooms/stats/summary')
  },
  
  // Datos de apoyo para habitaciones - CORREGIDO
  getRoomTypes() {
    return axios.get('/admin/room-types')
  },
  
  getBranches() {
    return axios.get('/admin/branches')
  },
  
  // Servicios
  getServices(params = {}) {
    return axios.get('/admin/services', { params })
  },
  
  createService(serviceData) {
    return axios.post('/admin/services', serviceData)
  },
  
  getService(id) {
    return axios.get(`/admin/services/${id}`)
  },
  
  updateService(id, serviceData) {
    return axios.put(`/admin/services/${id}`, serviceData)
  },
  
  deleteService(id) {
    return axios.delete(`/admin/services/${id}`)
  },
  
  toggleServiceStatus(id) {
    return axios.put(`/admin/services/${id}/toggle-status`)
  },
  
  // Reservas
  getReservations(params = {}) {    
    return axios.get('/admin/reservations', { params })
  },
  
  getReservation(id) {
    return axios.get(`/admin/reservations/${id}`)
  },
  
  updateReservation(id, reservationData) {
    return axios.put(`/admin/reservations/${id}`, reservationData)
  },
  
 // Método para check-in
  checkInReservation(id, checkInData) {
    return axios.post(`/admin/reservations/${id}/check-in`, checkInData)
  },

  // Método para check-out  
  checkOutReservation(id, checkOutData) {
    return axios.post(`/admin/reservations/${id}/check-out`, checkOutData)
  },

  // Método para cancelar reserva - ACTUALIZADO
  cancelReservation(id, cancellationData = {}) {
    return axios.put(`/admin/reservations/${id}/cancel`, cancellationData)
  },

  // Estadísticas de reservas
  getReservationStats(params = {}) {
    return axios.get('/admin/reservations/stats/summary', { params })
  },
    
  // Pagos
  getPayments(params = {}) {
    return axios.get('/admin/payments', { params })
  },
  
  getPendingPayments() {
    return axios.get('/admin/payments/pending')
  },
  
  verifyPayment(id, verificationData) {
    return axios.put(`/admin/payments/${id}/verify`, verificationData)
  },
  
  rejectPayment(id, rejectionData) {
    return axios.put(`/admin/payments/${id}/reject`, rejectionData)
  },
  
  getPaymentStats() {
    return axios.get('/admin/payments/stats/summary')
  },
  
  // Registros
  getRegistrations(params = {}) {
    return axios.get('/admin/registrations', { params })
  },

  getRegistration(id) {
    return axios.get(`/admin/registrations/${id}`)
  },

  createDirectRegistration(registrationData) {
    return axios.post('/admin/registrations/direct', registrationData)
  },

  updateRegistration(id, registrationData) {
    return axios.put(`/admin/registrations/${id}`, registrationData)
  },

  // Método para check-out desde registros
  checkOutRegistration(id, checkOutData) {
    return axios.post(`/admin/registrations/${id}/check-out`, checkOutData)
  },

  // Estadísticas de registros
  getRegistrationStats(params = {}) {
    return axios.get('/admin/registrations/stats/summary', { params })
  },
  
  // Reportes
  getReservationsReport(params = {}) {
    return axios.get('/admin/reports/reservations', { params })
  },
  
  getIncomeReport(params = {}) {
    return axios.get('/admin/reports/income', { params })
  },
  
  getOccupancyReport(params = {}) {
    return axios.get('/admin/reports/occupancy', { params })
  },
  
  getCheckinsReport(params = {}) {
    return axios.get('/admin/reports/checkins', { params })
  },
  
  getUsersReport(params = {}) {
    return axios.get('/admin/reports/users', { params })
  },
  
  getServicesReport(params = {}) {
    return axios.get('/admin/reports/services', { params })
  },
  
  getCancellationsReport(params = {}) {
    return axios.get('/admin/reports/cancellations', { params })
  },
  
  getPaymentsReport(params = {}) {
    return axios.get('/admin/reports/payments', { params })
  },
  
  exportReport(reportType, params = {}) {
    return axios.post('/admin/reports/export', { 
      type: reportType, 
      params 
    }, {
      responseType: 'blob'
    })
  }
}

// Servicios de empleado
export const employeeApi = {
  // Reservas (limitadas)
  getReservations(params = {}) {
    return axios.get('/employee/reservations', { params })
  },
  
  getReservation(id) {
    return axios.get(`/employee/reservations/${id}`)
  },
  
  checkInReservation(id, checkInData) {
    return axios.post(`/employee/reservations/${id}/check-in`, checkInData)
  },
  
  checkOutReservation(id, checkOutData) {
    return axios.post(`/employee/reservations/${id}/check-out`, checkOutData)
  },
  
  // Registros
  getRegistrations(params = {}) {
    return axios.get('/employee/registrations', { params })
  },
  
  createDirectRegistration(registrationData) {
    return axios.post('/employee/registrations/direct', registrationData)
  },
  
  getRegistration(id) {
    return axios.get(`/employee/registrations/${id}`)
  },
  
  updateRegistration(id, registrationData) {
    return axios.put(`/employee/registrations/${id}`, registrationData)
  },
  
  // Pagos (solo visualización)
  getPayments(params = {}) {
    return axios.get('/employee/payments', { params })
  },
  
  getPendingPayments() {
    return axios.get('/employee/payments/pending')
  }
}

// Servicios compartidos (admin y employee)
export const sharedApi = {
  // Consumo de servicios
  getServiceConsumptions(params = {}) {
    return axios.get('/shared/service-consumptions', { params })
  },
  
  createServiceConsumption(consumptionData) {
    return axios.post('/shared/service-consumptions', consumptionData)
  },
  
  updateServiceConsumption(id, consumptionData) {
    return axios.put(`/shared/service-consumptions/${id}`, consumptionData)
  },
  
  deleteServiceConsumption(id) {
    return axios.delete(`/shared/service-consumptions/${id}`)
  },
  
  // Datos básicos
  getBranches() {
    return axios.get('/shared/branches')
  },
  
  getRoomTypes() {
    return axios.get('/shared/room-types')
  },
  
  getServices(params = {}) {
    return axios.get('/shared/services', { params })
  }
}

// Manejo de errores global
const handleApiError = (error) => {
  if (error.response) {
    // Error del servidor
    const message = error.response.data.message || 'Error del servidor'
    throw new Error(message)
  } else if (error.request) {
    // Error de red
    throw new Error('Error de conexión')
  } else {
    // Error desconocido
    throw new Error('Error desconocido')
  }
}

// Interceptor para manejo de errores
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      // Token expirado o no válido
      localStorage.removeItem('auth_token')
      window.location.href = '/login'
    }
    handleApiError(error)
    return Promise.reject(error)
  }
)

// Funciones utilitarias
export const apiHelpers = {
  // Formatear parámetros de consulta
  formatQueryParams(params) {
    return Object.keys(params)
      .filter(key => params[key] !== null && params[key] !== undefined && params[key] !== '')
      .reduce((obj, key) => {
        obj[key] = params[key]
        return obj
      }, {})
  },
  
  // Descargar archivo blob
  downloadBlob(blob, filename) {
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  },
  
  // Construir URL con parámetros
  buildUrl(baseUrl, params) {
    const url = new URL(baseUrl, window.location.origin)
    Object.keys(params).forEach(key => {
      if (params[key] !== null && params[key] !== undefined) {
        url.searchParams.append(key, params[key])
      }
    })
    return url.toString()
  }
}

// Exportar instancia de axios configurada
export default axios