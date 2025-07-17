import axios from 'axios'

export const userService = {
  // Obtener lista de usuarios con filtros
  getUsers(params = {}) {
    return axios.get('/admin/users', { params })
  },
  
  // Crear nuevo usuario
  createUser(userData) {
    return axios.post('/admin/users', userData)
  },
  
  // Obtener usuario por ID
  getUser(id) {
    return axios.get(`/admin/users/${id}`)
  },
  
  // Actualizar usuario
  updateUser(id, userData) {
    return axios.put(`/admin/users/${id}`, userData)
  },
  
  // Eliminar usuario
  deleteUser(id) {
    return axios.delete(`/admin/users/${id}`)
  },
  
  // Cambiar contraseña de usuario
  changePassword(id, passwordData) {
    return axios.put(`/admin/users/${id}/change-password`, passwordData)
  },
  
  // Alternar estado activo/inactivo
  toggleStatus(id) {
    return axios.put(`/admin/users/${id}/toggle-status`)
  },
  
  // Obtener estadísticas de usuarios
  getStats() {
    return axios.get('/admin/users/stats/summary')
  },
  
  // Obtener roles disponibles
  getRoles() {
    return axios.get('/admin/roles')
  },
  
  // Exportar usuarios
  exportUsers(params = {}) {
    return axios.get('/admin/users/export', { 
      params,
      responseType: 'blob' 
    })
  },
  
  // Búsqueda avanzada de usuarios
  searchUsers(searchTerm) {
    return axios.get('/admin/users', {
      params: { search: searchTerm }
    })
  },
  
  // Obtener usuarios por rol
  getUsersByRole(role) {
    return axios.get('/admin/users', {
      params: { role }
    })
  },
  
  // Obtener usuarios activos
  getActiveUsers() {
    return axios.get('/admin/users', {
      params: { is_active: true }
    })
  },
  
  // Obtener usuarios inactivos
  getInactiveUsers() {
    return axios.get('/admin/users', {
      params: { is_active: false }
    })
  },
  
  // Validar email único
  validateEmail(email, userId = null) {
    return axios.post('/admin/users/validate-email', { 
      email, 
      user_id: userId 
    })
  },
  
  // Validar documento único
  validateDocument(documentType, documentNumber, userId = null) {
    return axios.post('/admin/users/validate-document', {
      document_type: documentType,
      document_number: documentNumber,
      user_id: userId
    })
  }
}

// Funciones helper para el frontend
export const userHelpers = {
  // Formatear nombre de rol
  formatRole(role) {
    const roleNames = {
      'admin': 'Administrador',
      'employee': 'Empleado',
      'customer': 'Cliente'
    }
    return roleNames[role] || role
  },
  
  // Obtener clase CSS para rol
  getRoleClass(role) {
    const classes = {
      'admin': 'badge bg-danger',
      'employee': 'badge bg-warning',
      'customer': 'badge bg-info'
    }
    return classes[role] || 'badge bg-secondary'
  },
  
  // Formatear tipo de documento
  formatDocumentType(type) {
    const types = {
      'ci': 'C.I.',
      'passport': 'Pasaporte',
      'other': 'Otro'
    }
    return types[type] || type
  },
  
  // Validar formulario de usuario
  validateUserForm(userData, isEditing = false) {
    const errors = {}
    
    // Validaciones básicas
    if (!userData.name?.trim()) {
      errors.name = ['El nombre es requerido']
    }
    
    if (!userData.email?.trim()) {
      errors.email = ['El email es requerido']
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(userData.email)) {
      errors.email = ['El email no es válido']
    }
    
    if (!userData.phone?.trim()) {
      errors.phone = ['El teléfono es requerido']
    }
    
    if (!userData.document_type) {
      errors.document_type = ['El tipo de documento es requerido']
    }
    
    if (!userData.document_number?.trim()) {
      errors.document_number = ['El número de documento es requerido']
    }
    
    if (!userData.role) {
      errors.role = ['El rol es requerido']
    }
    
    // Validaciones de contraseña (solo al crear)
    if (!isEditing) {
      if (!userData.password) {
        errors.password = ['La contraseña es requerida']
      } else if (userData.password.length < 6) {
        errors.password = ['La contraseña debe tener al menos 6 caracteres']
      }
      
      if (userData.password !== userData.password_confirmation) {
        errors.password_confirmation = ['Las contraseñas no coinciden']
      }
    }
    
    // Validación de fecha de nacimiento
    if (userData.birth_date) {
      const birthDate = new Date(userData.birth_date)
      const today = new Date()
      const age = today.getFullYear() - birthDate.getFullYear()
      
      if (age < 16) {
        errors.birth_date = ['El usuario debe tener al menos 16 años']
      } else if (age > 100) {
        errors.birth_date = ['Fecha de nacimiento inválida']
      }
    }
    
    // Validación de teléfono (formato básico)
    if (userData.phone && !/^\+?[0-9\s\-()]{8,15}$/.test(userData.phone)) {
      errors.phone = ['El formato del teléfono no es válido']
    }
    
    return {
      isValid: Object.keys(errors).length === 0,
      errors
    }
  },
  
  // Generar contraseña aleatoria
  generatePassword(length = 8) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*'
    let password = ''
    for (let i = 0; i < length; i++) {
      password += chars.charAt(Math.floor(Math.random() * chars.length))
    }
    return password
  },
  
  // Formatear fecha
  formatDate(dateString) {
    if (!dateString) return ''
    
    const date = new Date(dateString)
    return date.toLocaleDateString('es-BO', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
  },
  
  // Formatear fecha y hora
  formatDateTime(dateString) {
    if (!dateString) return ''
    
    const date = new Date(dateString)
    return date.toLocaleString('es-BO', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  },
  
  // Obtener inicial del avatar
  getAvatarInitial(name) {
    return name ? name.charAt(0).toUpperCase() : '?'
  },
  
  // Obtener color del avatar basado en el nombre
  getAvatarColor(name) {
    const colors = [
      '#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8',
      '#6f42c1', '#e83e8c', '#fd7e14', '#20c997', '#6c757d'
    ]
    
    if (!name) return colors[0]
    
    let hash = 0
    for (let i = 0; i < name.length; i++) {
      hash = name.charCodeAt(i) + ((hash << 5) - hash)
    }
    
    return colors[Math.abs(hash) % colors.length]
  },
  
  // Verificar permisos de usuario
  canEdit(user, currentUser) {
    // No puede editar su propio usuario si no es admin
    if (user.id === currentUser.id && !currentUser.roles.includes('admin')) {
      return false
    }
    
    // Solo admin puede editar otros admins
    if (user.roles.includes('admin') && !currentUser.roles.includes('admin')) {
      return false
    }
    
    return true
  },
  
  canDelete(user, currentUser) {
    // No puede eliminar su propio usuario
    if (user.id === currentUser.id) {
      return false
    }
    
    // Solo admin puede eliminar
    if (!currentUser.roles.includes('admin')) {
      return false
    }
    
    return true
  },
  
  canChangeStatus(user, currentUser) {
    // No puede cambiar su propio estado
    if (user.id === currentUser.id) {
      return false
    }
    
    // Solo admin puede cambiar estado
    if (!currentUser.roles.includes('admin')) {
      return false
    }
    
    return true
  },
  
  // Formatear estadísticas
  formatStats(stats) {
    return {
      total_users: stats.total_users || 0,
      active_users: stats.active_users || 0,
      inactive_users: (stats.total_users || 0) - (stats.active_users || 0),
      customers: stats.customers || 0,
      employees: stats.employees || 0,
      admins: stats.admins || 0,
      recent_users: stats.recent_users || 0,
      percentage_active: stats.total_users > 0 ? 
        Math.round((stats.active_users / stats.total_users) * 100) : 0
    }
  },
  
  // Filtrar usuarios por texto
  filterUsers(users, searchText) {
    if (!searchText) return users
    
    const search = searchText.toLowerCase()
    return users.filter(user => 
      user.name.toLowerCase().includes(search) ||
      user.email.toLowerCase().includes(search) ||
      user.phone.toLowerCase().includes(search) ||
      user.document_number.toLowerCase().includes(search)
    )
  },
  
  // Ordenar usuarios
  sortUsers(users, sortBy = 'name', sortOrder = 'asc') {
    return [...users].sort((a, b) => {
      let aValue = a[sortBy]
      let bValue = b[sortBy]
      
      // Manejar fechas
      if (sortBy === 'created_at' || sortBy === 'updated_at') {
        aValue = new Date(aValue)
        bValue = new Date(bValue)
      }
      
      // Manejar strings
      if (typeof aValue === 'string') {
        aValue = aValue.toLowerCase()
        bValue = bValue.toLowerCase()
      }
      
      if (sortOrder === 'asc') {
        return aValue > bValue ? 1 : -1
      } else {
        return aValue < bValue ? 1 : -1
      }
    })
  },
  
  // Exportar a CSV
  exportToCSV(users, filename = 'usuarios.csv') {
    const headers = [
      'ID', 'Nombre', 'Email', 'Teléfono', 'Tipo Doc', 'Número Doc',
      'Rol', 'Estado', 'Fecha Registro'
    ]
    
    const csvContent = [
      headers.join(','),
      ...users.map(user => [
        user.id,
        `"${user.name}"`,
        user.email,
        user.phone,
        user.document_type,
        user.document_number,
        user.roles[0] || '',
        user.is_active ? 'Activo' : 'Inactivo',
        this.formatDate(user.created_at)
      ].join(','))
    ].join('\n')
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const link = document.createElement('a')
    
    if (link.download !== undefined) {
      const url = URL.createObjectURL(blob)
      link.setAttribute('href', url)
      link.setAttribute('download', filename)
      link.style.visibility = 'hidden'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    }
  }
}