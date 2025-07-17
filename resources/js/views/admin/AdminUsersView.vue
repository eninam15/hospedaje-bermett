<template>
  <div class="users-management">
    <div class="container-xl">
      <!-- Header profesional -->
      <div class="users-header">
        <div class="header-content">
          <div class="header-text">
            <h2 class="header-title">👥 Gestión de Usuarios</h2>
            <p class="header-subtitle">Administra usuarios, roles y permisos del sistema</p>
          </div>
          <div class="header-actions">
            <button @click="exportUsers" class="btn btn-outline-secondary">
              <i class="pi pi-download"></i>
              Exportar
            </button>
            <button @click="openCreateModal" class="btn btn-success">
              <i class="pi pi-plus"></i>
              Nuevo Usuario
            </button>
          </div>
        </div>
      </div>

      <!-- Estadísticas -->
      <div class="stats-section">
        <div class="stats-grid">
          <div class="stat-card total">
            <div class="stat-icon">
              <i class="pi pi-users"></i>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.total_users }}</div>
              <div class="stat-label">Total Usuarios</div>
            </div>
          </div>

          <div class="stat-card active">
            <div class="stat-icon">
              <i class="pi pi-check-circle"></i>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.active_users }}</div>
              <div class="stat-label">Activos</div>
              <div class="stat-percentage">
                {{ getPercentage(stats.active_users, stats.total_users) }}%
              </div>
            </div>
          </div>

          <div class="stat-card customers">
            <div class="stat-icon">
              <i class="pi pi-user"></i>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.customers }}</div>
              <div class="stat-label">Clientes</div>
              <div class="stat-percentage">
                {{ getPercentage(stats.customers, stats.total_users) }}%
              </div>
            </div>
          </div>

          <div class="stat-card employees">
            <div class="stat-icon">
              <i class="pi pi-briefcase"></i>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.employees }}</div>
              <div class="stat-label">Empleados</div>
              <div class="stat-percentage">
                {{ getPercentage(stats.employees, stats.total_users) }}%
              </div>
            </div>
          </div>

          <div class="stat-card admins">
            <div class="stat-icon">
              <i class="pi pi-shield"></i>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.admins }}</div>
              <div class="stat-label">Administradores</div>
              <div class="stat-percentage">
                {{ getPercentage(stats.admins, stats.total_users) }}%
              </div>
            </div>
          </div>

          <div class="stat-card recent">
            <div class="stat-icon">
              <i class="pi pi-clock"></i>
            </div>
            <div class="stat-content">
              <div class="stat-value">{{ stats.recent_users }}</div>
              <div class="stat-label">Recientes</div>
              <div class="stat-note">Últimos 30 días</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="card filters-card">
        <div class="card-header">
          <h5 class="card-title">
            <i class="pi pi-filter"></i>
            Filtros y Búsqueda
          </h5>
          <button @click="clearFilters" class="btn btn-sm btn-outline-secondary">
            <i class="pi pi-refresh"></i>
            Limpiar
          </button>
        </div>
        <div class="card-body">
          <div class="filters-grid">
            <div class="filter-group">
              <label class="filter-label">Buscar Usuario</label>
              <div class="search-input">
                <i class="pi pi-search"></i>
                <input 
                  v-model="filters.search" 
                  type="text" 
                  class="form-control" 
                  placeholder="Nombre, email o teléfono..."
                  @input="debounceSearch"
                >
              </div>
            </div>
            
            <div class="filter-group">
              <label class="filter-label">Rol</label>
              <select v-model="filters.role" class="form-select" @change="loadUsers">
                <option value="">Todos los roles</option>
                <option value="admin">Administrador</option>
                <option value="employee">Empleado</option>
                <option value="customer">Cliente</option>
              </select>
            </div>

            <div class="filter-group">
              <label class="filter-label">Estado</label>
              <select v-model="filters.is_active" class="form-select" @change="loadUsers">
                <option value="">Todos los estados</option>
                <option value="true">Activo</option>
                <option value="false">Inactivo</option>
              </select>
            </div>

            <div class="filter-group">
              <label class="filter-label">Elementos por página</label>
              <select v-model="filters.per_page" class="form-select" @change="loadUsers">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla de usuarios -->
      <div class="card users-table-card">
        <div class="card-header">
          <h5 class="card-title">
            <i class="pi pi-list"></i>
            Lista de Usuarios
          </h5>
          <div class="table-info">
            <span class="results-count">
              Mostrando {{ users.length }} de {{ pagination.total }} usuarios
            </span>
          </div>
        </div>

        <div class="card-body">
          <!-- Loading State -->
          <div v-if="loading" class="loading-container">
            <div class="loading-content">
              <div class="loading-spinner">
                <i class="pi pi-spin pi-spinner"></i>
              </div>
              <p>Cargando usuarios...</p>
            </div>
          </div>

          <!-- Users Table -->
          <div v-else-if="users.length > 0" class="table-container">
            <table class="users-table">
              <thead>
                <tr>
                  <th class="table-header">Usuario</th>
                  <th class="table-header">Contacto</th>
                  <th class="table-header">Rol</th>
                  <th class="table-header">Estado</th>
                  <th class="table-header">Registro</th>
                  <th class="table-header">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in users" :key="user.id" class="table-row">
                  <td class="table-cell">
                    <div class="user-info">
                      <div class="user-avatar" :class="getRoleAvatarClass(user.roles[0])">
                        {{ user.name.charAt(0).toUpperCase() }}
                      </div>
                      <div class="user-details">
                        <div class="user-name">{{ user.name }}</div>
                        <div class="user-document">
                          {{ user.document_type?.toUpperCase() }}: {{ user.document_number }}
                        </div>
                      </div>
                    </div>
                  </td>
                  
                  <td class="table-cell">
                    <div class="contact-info">
                      <div class="contact-email">
                        <i class="pi pi-envelope"></i>
                        {{ user.email }}
                      </div>
                      <div class="contact-phone">
                        <i class="pi pi-phone"></i>
                        {{ user.phone || 'No especificado' }}
                      </div>
                    </div>
                  </td>

                  <td class="table-cell">
                    <div class="role-badge" :class="getRoleClass(user.roles[0])">
                      <i :class="getRoleIcon(user.roles[0])"></i>
                      {{ getRoleLabel(user.roles[0]) }}
                    </div>
                  </td>

                  <td class="table-cell">
                    <div class="status-container">
                      <span class="status-badge" :class="user.is_active ? 'active' : 'inactive'">
                        <i :class="user.is_active ? 'pi pi-check-circle' : 'pi pi-times-circle'"></i>
                        {{ user.is_active ? 'Activo' : 'Inactivo' }}
                      </span>
                    </div>
                  </td>

                  <td class="table-cell">
                    <div class="date-info">
                      <div class="date-primary">{{ formatDate(user.created_at) }}</div>
                      <div class="date-relative">{{ getRelativeDate(user.created_at) }}</div>
                    </div>
                  </td>

                  <td class="table-cell">
                    <div class="actions-group">
                      <button 
                        @click="viewUser(user)" 
                        class="action-btn view"
                        title="Ver detalles"
                      >
                        <i class="pi pi-eye"></i>
                      </button>
                      
                      <button 
                        @click="editUser(user)" 
                        class="action-btn edit"
                        title="Editar usuario"
                      >
                        <i class="pi pi-pencil"></i>
                      </button>
                      
                      <button 
                        @click="toggleUserStatus(user)" 
                        class="action-btn"
                        :class="user.is_active ? 'deactivate' : 'activate'"
                        :title="user.is_active ? 'Desactivar' : 'Activar'"
                      >
                        <i :class="user.is_active ? 'pi pi-ban' : 'pi pi-check'"></i>
                      </button>
                      
                      <button 
                        @click="changePassword(user)" 
                        class="action-btn password"
                        title="Cambiar contraseña"
                      >
                        <i class="pi pi-key"></i>
                      </button>
                      
                      <button 
                        @click="deleteUser(user)" 
                        class="action-btn delete"
                        title="Eliminar usuario"
                        :disabled="user.id === authStore.user.id"
                      >
                        <i class="pi pi-trash"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Paginación -->
            <div v-if="pagination.last_page > 1" class="pagination-container">
              <div class="pagination-info">
                Página {{ pagination.current_page }} de {{ pagination.last_page }}
              </div>
              <nav class="pagination-nav">
                <button 
                  class="pagination-btn" 
                  :class="{ disabled: pagination.current_page === 1 }"
                  @click="changePage(pagination.current_page - 1)"
                  :disabled="pagination.current_page === 1"
                >
                  <i class="pi pi-chevron-left"></i>
                  Anterior
                </button>
                
                <div class="pagination-pages">
                  <button 
                    v-for="page in getVisiblePages()" 
                    :key="page"
                    class="pagination-page" 
                    :class="{ active: page === pagination.current_page }"
                    @click="changePage(page)"
                  >
                    {{ page }}
                  </button>
                </div>
                
                <button 
                  class="pagination-btn" 
                  :class="{ disabled: pagination.current_page === pagination.last_page }"
                  @click="changePage(pagination.current_page + 1)"
                  :disabled="pagination.current_page === pagination.last_page"
                >
                  Siguiente
                  <i class="pi pi-chevron-right"></i>
                </button>
              </nav>
            </div>
          </div>

          <!-- Empty State -->
          <div v-else class="empty-state">
            <div class="empty-icon">
              <i class="pi pi-users"></i>
            </div>
            <h5>No hay usuarios</h5>
            <p>No se encontraron usuarios con los filtros aplicados</p>
            <button @click="clearFilters" class="btn btn-primary">
              <i class="pi pi-refresh"></i>
              Limpiar filtros
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <UserFormModal 
      v-if="showModal"
      :user="selectedUser"
      :show="showModal"
      @close="closeModal"
      @success="onUserSaved"
    />

    <UserDetailsModal 
      v-if="showDetailsModal"
      :user="selectedUser"
      :show="showDetailsModal"
      @close="closeDetailsModal"
    />

    <PasswordChangeModal 
      v-if="showPasswordModal"
      :user="selectedUser"
      :show="showPasswordModal"
      @close="closePasswordModal"
      @success="onPasswordChanged"
    />
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '../../stores/auth.js'
import UserFormModal from '../../components/admin/UserFormModal.vue'
import UserDetailsModal from '../../components/admin/UserDetailsModal.vue'
import PasswordChangeModal from '../../components/admin/PasswordChangeModal.vue'

export default {
  name: 'AdminUsersView',
  components: {
    UserFormModal,
    UserDetailsModal,
    PasswordChangeModal
  },
  setup() {
    const authStore = useAuthStore()
    
    const users = ref([])
    const stats = ref({
      total_users: 0,
      active_users: 0,
      customers: 0,
      employees: 0,
      admins: 0,
      recent_users: 0
    })
    const loading = ref(false)
    const selectedUser = ref(null)
    const showModal = ref(false)
    const showDetailsModal = ref(false)
    const showPasswordModal = ref(false)
    
    const filters = ref({
      search: '',
      role: '',
      is_active: '',
      per_page: 15,
      page: 1
    })
    
    const pagination = ref({
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0
    })
    
    let searchTimeout = null

    const loadUsers = async () => {
      try {
        loading.value = true
        
        // Simular API call - reemplazar con llamada real
        await new Promise(resolve => setTimeout(resolve, 800))
        
        // Datos simulados
        users.value = [
          {
            id: 1,
            name: 'Admin Principal',
            email: 'admin@bermett.com',
            phone: '+591 70000000',
            document_type: 'ci',
            document_number: '12345678',
            roles: ['admin'],
            is_active: true,
            created_at: '2024-01-15T10:00:00Z'
          },
          {
            id: 2,
            name: 'María Empleada',
            email: 'empleado@bermett.com',
            phone: '+591 70000001',
            document_type: 'ci',
            document_number: '87654321',
            roles: ['employee'],
            is_active: true,
            created_at: '2024-01-20T10:00:00Z'
          },
          {
            id: 3,
            name: 'Juan Cliente',
            email: 'cliente@bermett.com',
            phone: '+591 70000002',
            document_type: 'ci',
            document_number: '11223344',
            roles: ['customer'],
            is_active: true,
            created_at: '2024-01-25T10:00:00Z'
          },
          {
            id: 4,
            name: 'Ana Recepcionista',
            email: 'recepcion@bermett.com',
            phone: '+591 70000003',
            document_type: 'ci',
            document_number: '55667788',
            roles: ['employee'],
            is_active: false,
            created_at: '2024-02-01T10:00:00Z'
          },
          {
            id: 5,
            name: 'Carlos Huésped',
            email: 'carlos@email.com',
            phone: '+591 70000004',
            document_type: 'ci',
            document_number: '99887766',
            roles: ['customer'],
            is_active: true,
            created_at: '2024-02-10T10:00:00Z'
          }
        ]
        
        pagination.value = {
          current_page: 1,
          last_page: 1,
          per_page: 15,
          total: 5
        }
        
      } catch (error) {
        console.error('Error loading users:', error)
      } finally {
        loading.value = false
      }
    }

    const loadStats = async () => {
      try {
        // Simular API call - reemplazar con llamada real
        stats.value = {
          total_users: 47,
          active_users: 43,
          customers: 35,
          employees: 8,
          admins: 4,
          recent_users: 12
        }
      } catch (error) {
        console.error('Error loading stats:', error)
      }
    }

    const debounceSearch = () => {
      clearTimeout(searchTimeout)
      searchTimeout = setTimeout(() => {
        loadUsers()
      }, 500)
    }

    const clearFilters = () => {
      filters.value = {
        search: '',
        role: '',
        is_active: '',
        per_page: 15,
        page: 1
      }
      loadUsers()
    }

    const changePage = (page) => {
      if (page >= 1 && page <= pagination.value.last_page) {
        filters.value.page = page
        loadUsers()
      }
    }

    const getVisiblePages = () => {
      const current = pagination.value.current_page
      const last = pagination.value.last_page
      const pages = []
      
      const start = Math.max(1, current - 2)
      const end = Math.min(last, current + 2)
      
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
      
      return pages
    }

    const openCreateModal = () => {
      selectedUser.value = null
      showModal.value = true
    }

    const editUser = (user) => {
      selectedUser.value = user
      showModal.value = true
    }

    const viewUser = (user) => {
      selectedUser.value = user
      showDetailsModal.value = true
    }

    const changePassword = (user) => {
      selectedUser.value = user
      showPasswordModal.value = true
    }

    const toggleUserStatus = async (user) => {
      if (user.id === authStore.user.id) {
        alert('No puedes cambiar tu propio estado')
        return
      }
      
      if (confirm(`¿Estás seguro de ${user.is_active ? 'desactivar' : 'activar'} este usuario?`)) {
        try {
          // Simular API call
          user.is_active = !user.is_active
          alert(`Usuario ${user.is_active ? 'activado' : 'desactivado'} exitosamente`)
          loadStats() // Actualizar estadísticas
        } catch (error) {
          console.error('Error toggling user status:', error)
        }
      }
    }

    const deleteUser = async (user) => {
      if (user.id === authStore.user.id) {
        alert('No puedes eliminar tu propio usuario')
        return
      }
      
      if (confirm(`¿Estás seguro de eliminar al usuario ${user.name}?`)) {
        try {
          // Simular API call
          const index = users.value.findIndex(u => u.id === user.id)
          if (index !== -1) {
            users.value.splice(index, 1)
          }
          alert('Usuario eliminado exitosamente')
          loadStats() // Actualizar estadísticas
        } catch (error) {
          console.error('Error deleting user:', error)
        }
      }
    }

    const exportUsers = () => {
      alert('Función de exportar usuarios - Por implementar')
    }

    const closeModal = () => {
      showModal.value = false
      selectedUser.value = null
    }

    const closeDetailsModal = () => {
      showDetailsModal.value = false
      selectedUser.value = null
    }

    const closePasswordModal = () => {
      showPasswordModal.value = false
      selectedUser.value = null
    }

    const onUserSaved = () => {
      closeModal()
      loadUsers()
      loadStats()
    }

    const onPasswordChanged = () => {
      closePasswordModal()
      alert('Contraseña actualizada exitosamente')
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('es-BO', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }

    const getRelativeDate = (dateString) => {
      const now = new Date()
      const date = new Date(dateString)
      const diffTime = Math.abs(now - date)
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      
      if (diffDays === 1) return 'Ayer'
      if (diffDays < 7) return `Hace ${diffDays} días`
      if (diffDays < 30) return `Hace ${Math.ceil(diffDays / 7)} semanas`
      return `Hace ${Math.ceil(diffDays / 30)} meses`
    }

    const getRoleClass = (role) => {
      const classes = {
        'admin': 'role-admin',
        'employee': 'role-employee',
        'customer': 'role-customer'
      }
      return classes[role] || 'role-default'
    }

    const getRoleIcon = (role) => {
      const icons = {
        'admin': 'pi pi-shield',
        'employee': 'pi pi-briefcase',
        'customer': 'pi pi-user'
      }
      return icons[role] || 'pi pi-user'
    }

    const getRoleLabel = (role) => {
      const labels = {
        'admin': 'Administrador',
        'employee': 'Empleado',
        'customer': 'Cliente'
      }
      return labels[role] || 'Desconocido'
    }

    const getRoleAvatarClass = (role) => {
      const classes = {
        'admin': 'avatar-admin',
        'employee': 'avatar-employee',
        'customer': 'avatar-customer'
      }
      return classes[role] || 'avatar-default'
    }

    const getPercentage = (value, total) => {
      return total > 0 ? Math.round((value / total) * 100) : 0
    }

    onMounted(() => {
      loadUsers()
      loadStats()
    })

    return {
      authStore,
      users,
      stats,
      loading,
      selectedUser,
      showModal,
      showDetailsModal,
      showPasswordModal,
      filters,
      pagination,
      loadUsers,
      debounceSearch,
      clearFilters,
      changePage,
      getVisiblePages,
      openCreateModal,
      editUser,
      viewUser,
      changePassword,
      toggleUserStatus,
      deleteUser,
      exportUsers,
      closeModal,
      closeDetailsModal,
      closePasswordModal,
      onUserSaved,
      onPasswordChanged,
      formatDate,
      getRelativeDate,
      getRoleClass,
      getRoleIcon,
      getRoleLabel,
      getRoleAvatarClass,
      getPercentage
    }
  }
}
</script>

<style scoped>
.users-management {
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
.users-header {
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

.btn-sm {
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
}

/* Estadísticas */
.stats-section {
  margin-bottom: 2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.stat-card {
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

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
}

.stat-card.total::before {
  background: linear-gradient(90deg, #6b7280, #4b5563);
}
.stat-card.active::before {
  background: linear-gradient(90deg, #10b981, #059669);
}
.stat-card.customers::before {
  background: linear-gradient(90deg, #3b82f6, #1d4ed8);
}
.stat-card.employees::before {
  background: linear-gradient(90deg, #f59e0b, #d97706);
}
.stat-card.admins::before {
  background: linear-gradient(90deg, #ef4444, #dc2626);
}
.stat-card.recent::before {
  background: linear-gradient(90deg, #8b5cf6, #7c3aed);
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.stat-icon {
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

.stat-card.total .stat-icon {
  background: linear-gradient(135deg, #6b7280, #4b5563);
}
.stat-card.active .stat-icon {
  background: linear-gradient(135deg, #10b981, #059669);
}
.stat-card.customers .stat-icon {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}
.stat-card.employees .stat-icon {
  background: linear-gradient(135deg, #f59e0b, #d97706);
}
.stat-card.admins .stat-icon {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}
.stat-card.recent .stat-icon {
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  line-height: 1;
}

.stat-label {
  color: #6b7280;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

.stat-percentage {
  color: #10b981;
  font-size: 0.8rem;
  font-weight: 600;
  margin-top: 0.25rem;
}

.stat-note {
  color: #9ca3af;
  font-size: 0.75rem;
  margin-top: 0.25rem;
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
  grid-template-columns: 2fr 1fr 1fr 1fr;
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

.search-input {
  position: relative;
}

.search-input i {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  font-size: 0.875rem;
}

.search-input .form-control {
  padding-left: 2.5rem;
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

/* Información de la tabla */
.table-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.results-count {
  color: #6b7280;
  font-size: 0.875rem;
}

/* Loading */
.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

.loading-content {
  text-align: center;
  color: #6b7280;
}

.loading-spinner {
  font-size: 2rem;
  color: #3b82f6;
  margin-bottom: 1rem;
}

/* Tabla */
.table-container {
  overflow-x: auto;
}

.users-table {
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
  padding: 1rem 0.75rem;
  vertical-align: top;
}

/* User info */
.user-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  color: white;
  flex-shrink: 0;
}

.user-avatar.avatar-admin {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

.user-avatar.avatar-employee {
  background: linear-gradient(135deg, #f59e0b, #d97706);
}

.user-avatar.avatar-customer {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.user-avatar.avatar-default {
  background: linear-gradient(135deg, #6b7280, #4b5563);
}

.user-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.user-name {
  font-weight: 600;
  color: #1f2937;
}

.user-document {
  color: #6b7280;
  font-size: 0.8rem;
}

/* Contact info */
.contact-info {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.contact-email, .contact-phone {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #6b7280;
  font-size: 0.875rem;
}

.contact-email i, .contact-phone i {
  color: #9ca3af;
  font-size: 0.8rem;
}

/* Role badge */
.role-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  border-radius: 0.5rem;
  font-size: 0.8rem;
  font-weight: 500;
}

.role-badge.role-admin {
  background: #fef2f2;
  color: #991b1b;
}

.role-badge.role-employee {
  background: #fef3c7;
  color: #92400e;
}

.role-badge.role-customer {
  background: #dbeafe;
  color: #1e40af;
}

.role-badge.role-default {
  background: #f3f4f6;
  color: #374151;
}

/* Status */
.status-container {
  display: flex;
  align-items: center;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  border-radius: 0.5rem;
  font-size: 0.8rem;
  font-weight: 500;
}

.status-badge.active {
  background: #dcfce7;
  color: #166534;
}

.status-badge.inactive {
  background: #fef2f2;
  color: #991b1b;
}

/* Date info */
.date-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.date-primary {
  color: #1f2937;
  font-weight: 500;
}

.date-relative {
  color: #6b7280;
  font-size: 0.8rem;
}

/* Actions */
.actions-group {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  width: 32px;
  height: 32px;
  border-radius: 0.5rem;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.action-btn.view {
  background: #dbeafe;
  color: #1e40af;
}

.action-btn.view:hover {
  background: #bfdbfe;
}

.action-btn.edit {
  background: #fef3c7;
  color: #92400e;
}

.action-btn.edit:hover {
  background: #fde68a;
}

.action-btn.activate {
  background: #dcfce7;
  color: #166534;
}

.action-btn.activate:hover {
  background: #bbf7d0;
}

.action-btn.deactivate {
  background: #fef2f2;
  color: #991b1b;
}

.action-btn.deactivate:hover {
  background: #fecaca;
}

.action-btn.password {
  background: #f3f4f6;
  color: #374151;
}

.action-btn.password:hover {
  background: #e5e7eb;
}

.action-btn.delete {
  background: #fef2f2;
  color: #991b1b;
}

.action-btn.delete:hover {
  background: #fecaca;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Paginación */
.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.pagination-info {
  color: #6b7280;
  font-size: 0.875rem;
}

.pagination-nav {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.pagination-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  background: white;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.875rem;
}

.pagination-btn:hover:not(.disabled) {
  background: #f9fafb;
  border-color: #3b82f6;
}

.pagination-btn.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-pages {
  display: flex;
  gap: 0.25rem;
}

.pagination-page {
  width: 32px;
  height: 32px;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  background: white;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
}

.pagination-page:hover {
  background: #f9fafb;
  border-color: #3b82f6;
}

.pagination-page.active {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
  color: #6b7280;
}

.empty-icon {
  font-size: 4rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.empty-state h5 {
  color: #374151;
  margin-bottom: 0.5rem;
}

.empty-state p {
  margin-bottom: 1.5rem;
}

/* Responsive */
@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .header-actions {
    justify-content: center;
  }

  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }

  .stat-card {
    flex-direction: column;
    text-align: center;
    gap: 0.75rem;
  }

  .filters-grid {
    grid-template-columns: 1fr;
  }

  .table-container {
    font-size: 0.8rem;
  }

  .user-info {
    flex-direction: column;
    text-align: center;
    gap: 0.75rem;
  }

  .contact-info {
    align-items: center;
  }

  .actions-group {
    flex-wrap: wrap;
    justify-content: center;
  }

  .pagination-container {
    flex-direction: column;
    gap: 1rem;
  }
}

@media (max-width: 480px) {
  .container-xl {
    padding: 0 0.5rem;
  }

  .users-header {
    padding: 1.5rem;
  }

  .card-body {
    padding: 1rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .stat-card {
    padding: 1rem;
  }

  .table-container {
    font-size: 0.75rem;
  }

  .table-header,
  .table-cell {
    padding: 0.75rem 0.5rem;
  }

  .user-info {
    gap: 0.5rem;
  }

  .user-avatar {
    width: 32px;
    height: 32px;
  }

  .actions-group {
    gap: 0.25rem;
  }

  .action-btn {
    width: 28px;
    height: 28px;
    font-size: 0.8rem;
  }
}
</style>