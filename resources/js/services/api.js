import axios from 'axios'

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
  getUsers() {
    return axios.get('/admin/users')
  },
  
  // Reservas
  getReservations() {
    return axios.get('/admin/reservations')
  },
  
  // Pagos
  getPendingPayments() {
    return axios.get('/admin/payments/pending')
  },
  
  // Reportes
  getReport(type, params = {}) {
    return axios.get(`/admin/reports/${type}`, { params })
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
    handleApiError(error)
    return Promise.reject(error)
  }
)