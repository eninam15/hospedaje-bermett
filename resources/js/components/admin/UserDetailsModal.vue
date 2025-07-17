<template>
  <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Detalles del Usuario</h5>
          <button type="button" class="btn-close" @click="$emit('close')"></button>
        </div>
        
        <div class="modal-body">
          <div v-if="loading" class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
          </div>
          
          <div v-else-if="userDetails" class="row">
            <!-- Información Principal -->
            <div class="col-md-4 mb-4">
              <div class="card">
                <div class="card-body text-center">
                  <div class="avatar-large mb-3" :style="{ backgroundColor: getAvatarColor(userDetails.name) }">
                    {{ getAvatarInitial(userDetails.name) }}
                  </div>
                  <h5 class="card-title">{{ userDetails.name }}</h5>
                  <p class="text-muted mb-2">{{ userDetails.email }}</p>
                  <span :class="getRoleClass(userDetails.current_role)">
                    {{ formatRole(userDetails.current_role) }}
                  </span>
                  <br>
                  <span class="mt-2" :class="userDetails.is_active ? 'badge bg-success' : 'badge bg-danger'">
                    {{ userDetails.is_active ? 'Activo' : 'Inactivo' }}
                  </span>
                </div>
              </div>
            </div>
            
            <!-- Información Personal -->
            <div class="col-md-8 mb-4">
              <div class="card">
                <div class="card-header">
                  <h6 class="mb-0">Información Personal</h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label text-muted">Teléfono</label>
                      <p class="mb-0">{{ userDetails.phone || 'No especificado' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label text-muted">Fecha de Nacimiento</label>
                      <p class="mb-0">{{ formatDate(userDetails.birth_date) || 'No especificada' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label text-muted">Tipo de Documento</label>
                      <p class="mb-0">{{ formatDocumentType(userDetails.document_type) }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label text-muted">Número de Documento</label>
                      <p class="mb-0">{{ userDetails.document_number }}</p>
                    </div>
                    <div class="col-12 mb-3">
                      <label class="form-label text-muted">Dirección</label>
                      <p class="mb-0">{{ userDetails.address || 'No especificada' }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Información de Cuenta -->
            <div class="col-md-6 mb-4">
              <div class="card">
                <div class="card-header">
                  <h6 class="mb-0">Información de Cuenta</h6>
                </div>
                <div class="card-body">
                  <div class="mb-3">
                    <label class="form-label text-muted">Fecha de Registro</label>
                    <p class="mb-0">{{ formatDateTime(userDetails.created_at) }}</p>
                  </div>
                  <div class="mb-3">
                    <label class="form-label text-muted">Email Verificado</label>
                    <p class="mb-0">
                      <span v-if="userDetails.email_verified_at" class="badge bg-success">
                        <i class="pi pi-check"></i> Verificado
                      </span>
                      <span v-else class="badge bg-warning">
                        <i class="pi pi-exclamation-triangle"></i> Sin verificar
                      </span>
                    </p>
                  </div>
                  <div class="mb-3">
                    <label class="form-label text-muted">Roles</label>
                    <div>
                      <span v-for="role in userDetails.roles" :key="role" :class="getRoleClass(role)" class="me-1">
                        {{ formatRole(role) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="col-md-6 mb-4">
              <div class="card">
                <div class="card-header">
                  <h6 class="mb-0">Estadísticas</h6>
                </div>
                <div class="card-body">
                  <div class="row text-center">
                    <div class="col-4 mb-3">
                      <h4 class="text-primary">{{ userDetails.stats.total_reservations }}</h4>
                      <small class="text-muted">Reservas</small>
                    </div>
                    <div class="col-4 mb-3">
                      <h4 class="text-success">{{ userDetails.stats.confirmed_reservations }}</h4>
                      <small class="text-muted">Confirmadas</small>
                    </div>
                    <div class="col-4 mb-3">
                      <h4 class="text-info">{{ userDetails.stats.completed_stays }}</h4>
                      <small class="text-muted">Estadías</small>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Actividad Reciente -->
            <div class="col-12 mb-4">
              <div class="card">
                <div class="card-header">
                  <h6 class="mb-0">Actividad Reciente</h6>
                </div>
                <div class="card-body">
                  <div v-if="recentActivity.length > 0">
                    <div v-for="activity in recentActivity" :key="activity.id" class="d-flex align-items-center mb-3">
                      <div class="activity-icon me-3" :class="getActivityIconClass(activity.type)">
                        <i :class="getActivityIcon(activity.type)"></i>
                      </div>
                      <div class="flex-grow-1">
                        <p class="mb-0">{{ activity.description }}</p>
                        <small class="text-muted">{{ formatDateTime(activity.created_at) }}</small>
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-center py-3">
                    <i class="pi pi-clock text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2 mb-0">No hay actividad reciente</p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Configuración de Seguridad -->
            <div class="col-12 mb-4">
              <div class="card">
                <div class="card-header">
                  <h6 class="mb-0">Configuración de Seguridad</h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <label class="form-label text-muted">Último Inicio de Sesión</label>
                      <p class="mb-0">{{ formatDateTime(userDetails.last_login_at) || 'Nunca' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label text-muted">IP del Último Acceso</label>
                      <p class="mb-0">{{ userDetails.last_login_ip || 'N/A' }}</p>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label text-muted">Intentos de Login Fallidos</label>
                      <p class="mb-0">
                        <span :class="userDetails.failed_login_attempts > 0 ? 'text-warning' : 'text-success'">
                          {{ userDetails.failed_login_attempts || 0 }}
                        </span>
                      </p>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="form-label text-muted">Autenticación 2FA</label>
                      <p class="mb-0">
                        <span class="badge bg-secondary">
                          <i class="pi pi-shield"></i> Deshabilitado
                        </span>
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="$emit('close')">
            Cerrar
          </button>
          <button type="button" class="btn btn-primary" @click="editUser">
            <i class="pi pi-pencil"></i> Editar Usuario
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { userHelpers } from '../../services/userService.js'

export default {
  name: 'UserDetailsModal',
  props: {
    user: {
      type: Object,
      required: true
    },
    show: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close', 'edit'],
  setup(props, { emit }) {
    const userDetails = ref(null)
    const loading = ref(false)
    const recentActivity = ref([])
    
    const loadUserDetails = async () => {
      try {
        loading.value = true
        
        // Simular carga de datos detallados
        await new Promise(resolve => setTimeout(resolve, 800))
        
        // Datos simulados extendidos
        userDetails.value = {
          ...props.user,
          birth_date: '1990-05-15',
          address: 'Av. 6 de Agosto #123, La Paz, Bolivia',
          email_verified_at: '2024-01-15T10:00:00Z',
          last_login_at: '2024-07-16T08:30:00Z',
          last_login_ip: '192.168.1.100',
          failed_login_attempts: 0,
          stats: {
            total_reservations: 12,
            confirmed_reservations: 10,
            completed_stays: 8
          }
        }
        
        // Actividad reciente simulada
        recentActivity.value = [
          {
            id: 1,
            type: 'login',
            description: 'Inicio de sesión exitoso',
            created_at: '2024-07-16T08:30:00Z'
          },
          {
            id: 2,
            type: 'profile_update',
            description: 'Actualizó información del perfil',
            created_at: '2024-07-15T14:20:00Z'
          },
          {
            id: 3,
            type: 'reservation',
            description: 'Creó una nueva reserva',
            created_at: '2024-07-14T16:45:00Z'
          }
        ]
        
      } catch (error) {
        console.error('Error loading user details:', error)
      } finally {
        loading.value = false
      }
    }
    
    const editUser = () => {
      emit('edit', userDetails.value)
    }
    
    const getAvatarInitial = (name) => {
      return userHelpers.getAvatarInitial(name)
    }
    
    const getAvatarColor = (name) => {
      return userHelpers.getAvatarColor(name)
    }
    
    const getRoleClass = (role) => {
      return userHelpers.getRoleClass(role)
    }
    
    const formatRole = (role) => {
      return userHelpers.formatRole(role)
    }
    
    const formatDocumentType = (type) => {
      return userHelpers.formatDocumentType(type)
    }
    
    const formatDate = (dateString) => {
      return userHelpers.formatDate(dateString)
    }
    
    const formatDateTime = (dateString) => {
      return userHelpers.formatDateTime(dateString)
    }
    
    const getActivityIcon = (type) => {
      const icons = {
        login: 'pi pi-sign-in',
        profile_update: 'pi pi-user-edit',
        reservation: 'pi pi-calendar-plus',
        payment: 'pi pi-credit-card',
        logout: 'pi pi-sign-out'
      }
      return icons[type] || 'pi pi-circle'
    }
    
    const getActivityIconClass = (type) => {
      const classes = {
        login: 'activity-icon-success',
        profile_update: 'activity-icon-info',
        reservation: 'activity-icon-primary',
        payment: 'activity-icon-warning',
        logout: 'activity-icon-secondary'
      }
      return classes[type] || 'activity-icon-default'
    }
    
    onMounted(() => {
      if (props.show) {
        loadUserDetails()
      }
    })
    
    return {
      userDetails,
      loading,
      recentActivity,
      editUser,
      getAvatarInitial,
      getAvatarColor,
      getRoleClass,
      formatRole,
      formatDocumentType,
      formatDate,
      formatDateTime,
      getActivityIcon,
      getActivityIconClass
    }
  }
}
</script>

<style scoped>
.modal.show {
  display: block !important;
}

.avatar-large {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  font-size: 2rem;
  margin: 0 auto;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
}

.activity-icon-success {
  background-color: #28a745;
}

.activity-icon-info {
  background-color: #17a2b8;
}

.activity-icon-primary {
  background-color: #007bff;
}

.activity-icon-warning {
  background-color: #ffc107;
  color: #212529;
}

.activity-icon-secondary {
  background-color: #6c757d;
}

.activity-icon-default {
  background-color: #e9ecef;
  color: #6c757d;
}

.card {
  border: none;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
  background-color: rgba(0, 0, 0, 0.03);
  border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.form-label {
  font-weight: 600;
  font-size: 0.875rem;
}

.badge {
  font-size: 0.75em;
}

.text-muted {
  color: #6c757d !important;
}

.text-primary {
  color: #007bff !important;
}

.text-success {
  color: #28a745 !important;
}

.text-info {
  color: #17a2b8 !important;
}

.text-warning {
  color: #ffc107 !important;
}
</style>