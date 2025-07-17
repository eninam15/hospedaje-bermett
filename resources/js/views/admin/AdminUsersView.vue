<template>
  <div class="container-fluid py-4">
    <div class="row">
      <div class="col-12">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2>Gestión de Usuarios</h2>
          <button class="btn btn-success" @click="openCreateModal">
            <i class="pi pi-plus"></i> Nuevo Usuario
          </button>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <input 
                  v-model="filters.search" 
                  type="text" 
                  class="form-control" 
                  placeholder="Nombre, email o teléfono..."
                  @input="debounceSearch"
                >
              </div>
              <div class="col-md-2">
                <label class="form-label">Rol</label>
                <select v-model="filters.role" class="form-select" @change="loadUsers">
                  <option value="">Todos</option>
                  <option value="admin">Admin</option>
                  <option value="employee">Empleado</option>
                  <option value="customer">Cliente</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label">Estado</label>
                <select v-model="filters.is_active" class="form-select" @change="loadUsers">
                  <option value="">Todos</option>
                  <option value="true">Activo</option>
                  <option value="false">Inactivo</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label">Por página</label>
                <select v-model="filters.per_page" class="form-select" @change="loadUsers">
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                  <button @click="clearFilters" class="btn btn-outline-secondary">
                    <i class="pi pi-refresh"></i> Limpiar
                  </button>
                  <button @click="exportUsers" class="btn btn-outline-primary">
                    <i class="pi pi-download"></i> Exportar
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Estadísticas -->
        <div class="row mb-4">
          <div class="col-md-2">
            <div class="card text-center">
              <div class="card-body">
                <h4 class="text-primary">{{ stats.total_users }}</h4>
                <small class="text-muted">Total</small>
              </div>
            </div>
          </div>
          <div class="col-md-2">
            <div class="card text-center">
              <div class="card-body">
                <h4 class="text-success">{{ stats.active_users }}</h4>
                <small class="text-muted">Activos</small>
              </div>
            </div>
          </div>
          <div class="col-md-2">
            <div class="card text-center">
              <div class="card-body">
                <h4 class="text-info">{{ stats.customers }}</h4>
                <small class="text-muted">Clientes</small>
              </div>
            </div>
          </div>
          <div class="col-md-2">
            <div class="card text-center">
              <div class="card-body">
                <h4 class="text-warning">{{ stats.employees }}</h4>
                <small class="text-muted">Empleados</small>
              </div>
            </div>
          </div>
          <div class="col-md-2">
            <div class="card text-center">
              <div class="card-body">
                <h4 class="text-danger">{{ stats.admins }}</h4>
                <small class="text-muted">Admins</small>
              </div>
            </div>
          </div>
          <div class="col-md-2">
            <div class="card text-center">
              <div class="card-body">
                <h4 class="text-secondary">{{ stats.recent_users }}</h4>
                <small class="text-muted">Recientes</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla de usuarios -->
        <div class="card">
          <div class="card-body">
            <div v-if="loading" class="text-center py-4">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
              </div>
            </div>

            <div v-else-if="users.length > 0" class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Registro</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="user in users" :key="user.id">
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar-circle me-3">
                          {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                          <strong>{{ user.name }}</strong>
                          <br>
                          <small class="text-muted">{{ user.document_type?.toUpperCase() }}: {{ user.document_number }}</small>
                        </div>
                      </div>
                    </td>
                    <td>{{ user.email }}</td>
                    <td>{{ user.phone }}</td>
                    <td>
                      <span :class="getRoleClass(user.roles[0])">
                        {{ getRoleLabel(user.roles[0]) }}
                      </span>
                    </td>
                    <td>
                      <span :class="user.is_active ? 'badge bg-success' : 'badge bg-danger'">
                        {{ user.is_active ? 'Activo' : 'Inactivo' }}
                      </span>
                    </td>
                    <td>{{ formatDate(user.created_at) }}</td>
                    <td>
                      <div class="btn-group" role="group">
                        <button 
                          @click="viewUser(user)" 
                          class="btn btn-sm btn-outline-primary"
                          title="Ver detalles"
                        >
                          <i class="pi pi-eye"></i>
                        </button>
                        <button 
                          @click="editUser(user)" 
                          class="btn btn-sm btn-outline-warning"
                          title="Editar"
                        >
                          <i class="pi pi-pencil"></i>
                        </button>
                        <button 
                          @click="toggleUserStatus(user)" 
                          :class="user.is_active ? 'btn btn-sm btn-outline-danger' : 'btn btn-sm btn-outline-success'"
                          :title="user.is_active ? 'Desactivar' : 'Activar'"
                        >
                          <i :class="user.is_active ? 'pi pi-ban' : 'pi pi-check'"></i>
                        </button>
                        <button 
                          @click="changePassword(user)" 
                          class="btn btn-sm btn-outline-secondary"
                          title="Cambiar contraseña"
                        >
                          <i class="pi pi-key"></i>
                        </button>
                        <button 
                          @click="deleteUser(user)" 
                          class="btn btn-sm btn-outline-danger"
                          title="Eliminar"
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
              <nav v-if="pagination.last_page > 1" class="mt-4">
                <ul class="pagination justify-content-center">
                  <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                    <button class="page-link" @click="changePage(pagination.current_page - 1)">
                      Anterior
                    </button>
                  </li>
                  <li 
                    v-for="page in getVisiblePages()" 
                    :key="page"
                    class="page-item" 
                    :class="{ active: page === pagination.current_page }"
                  >
                    <button class="page-link" @click="changePage(page)">
                      {{ page }}
                    </button>
                  </li>
                  <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                    <button class="page-link" @click="changePage(pagination.current_page + 1)">
                      Siguiente
                    </button>
                  </li>
                </ul>
              </nav>
            </div>

            <div v-else class="text-center py-5">
              <i class="pi pi-users" style="font-size: 3rem; color: #6c757d;"></i>
              <h5 class="mt-3 text-muted">No hay usuarios</h5>
              <p class="text-muted">No se encontraron usuarios con los filtros aplicados</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de crear/editar usuario -->
    <UserFormModal 
      v-if="showModal"
      :user="selectedUser"
      :show="showModal"
      @close="closeModal"
      @success="onUserSaved"
    />

    <!-- Modal de ver usuario -->
    <UserDetailsModal 
      v-if="showDetailsModal"
      :user="selectedUser"
      :show="showDetailsModal"
      @close="closeDetailsModal"
    />

    <!-- Modal de cambiar contraseña -->
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
        await new Promise(resolve => setTimeout(resolve, 1000))
        
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
          }
        ]
        
        pagination.value = {
          current_page: 1,
          last_page: 1,
          per_page: 15,
          total: 3
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
          total_users: 25,
          active_users: 23,
          customers: 18,
          employees: 5,
          admins: 2,
          recent_users: 4
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

    const getRoleClass = (role) => {
      const classes = {
        'admin': 'badge bg-danger',
        'employee': 'badge bg-warning',
        'customer': 'badge bg-info'
      }
      return classes[role] || 'badge bg-secondary'
    }

    const getRoleLabel = (role) => {
      const labels = {
        'admin': 'Admin',
        'employee': 'Empleado',
        'customer': 'Cliente'
      }
      return labels[role] || 'Desconocido'
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
      getRoleClass,
      getRoleLabel
    }
  }
}
</script>

<style scoped>
.avatar-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background-color: #007bff;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

.table-responsive {
  border-radius: 0.5rem;
}

.btn-group .btn {
  padding: 0.25rem 0.5rem;
}

.card {
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card:hover {
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.pagination .page-link {
  color: #007bff;
  border-color: #dee2e6;
}

.pagination .page-item.active .page-link {
  background-color: #007bff;
  border-color: #007bff;
}

.badge {
  font-size: 0.75em;
}
</style>