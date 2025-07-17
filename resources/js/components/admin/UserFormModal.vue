<template>
  <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            {{ isEditing ? 'Editar Usuario' : 'Crear Usuario' }}
          </h5>
          <button type="button" class="btn-close" @click="$emit('close')"></button>
        </div>
        
        <div class="modal-body">
          <form @submit.prevent="handleSubmit">
            <div class="row">
              <!-- Información Personal -->
              <div class="col-12 mb-3">
                <h6 class="text-primary">Información Personal</h6>
                <hr>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Nombre Completo *</label>
                <input 
                  v-model="form.name" 
                  type="text" 
                  class="form-control" 
                  :class="{ 'is-invalid': errors.name }"
                  required
                >
                <div v-if="errors.name" class="invalid-feedback">
                  {{ errors.name[0] }}
                </div>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Email *</label>
                <input 
                  v-model="form.email" 
                  type="email" 
                  class="form-control" 
                  :class="{ 'is-invalid': errors.email }"
                  required
                >
                <div v-if="errors.email" class="invalid-feedback">
                  {{ errors.email[0] }}
                </div>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Teléfono *</label>
                <input 
                  v-model="form.phone" 
                  type="tel" 
                  class="form-control" 
                  :class="{ 'is-invalid': errors.phone }"
                  placeholder="+591 70000000"
                  required
                >
                <div v-if="errors.phone" class="invalid-feedback">
                  {{ errors.phone[0] }}
                </div>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Fecha de Nacimiento</label>
                <input 
                  v-model="form.birth_date" 
                  type="date" 
                  class="form-control" 
                  :class="{ 'is-invalid': errors.birth_date }"
                >
                <div v-if="errors.birth_date" class="invalid-feedback">
                  {{ errors.birth_date[0] }}
                </div>
              </div>
              
              <!-- Documentos -->
              <div class="col-12 mb-3">
                <h6 class="text-primary">Documentos</h6>
                <hr>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Tipo de Documento *</label>
                <select 
                  v-model="form.document_type" 
                  class="form-select" 
                  :class="{ 'is-invalid': errors.document_type }"
                  required
                >
                  <option value="">Seleccionar</option>
                  <option value="ci">Cédula de Identidad</option>
                  <option value="passport">Pasaporte</option>
                  <option value="other">Otro</option>
                </select>
                <div v-if="errors.document_type" class="invalid-feedback">
                  {{ errors.document_type[0] }}
                </div>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Número de Documento *</label>
                <input 
                  v-model="form.document_number" 
                  type="text" 
                  class="form-control" 
                  :class="{ 'is-invalid': errors.document_number }"
                  required
                >
                <div v-if="errors.document_number" class="invalid-feedback">
                  {{ errors.document_number[0] }}
                </div>
              </div>
              
              <div class="col-12 mb-3">
                <label class="form-label">Dirección</label>
                <textarea 
                  v-model="form.address" 
                  class="form-control" 
                  rows="2"
                  :class="{ 'is-invalid': errors.address }"
                  placeholder="Dirección completa"
                ></textarea>
                <div v-if="errors.address" class="invalid-feedback">
                  {{ errors.address[0] }}
                </div>
              </div>
              
              <!-- Configuración de Cuenta -->
              <div class="col-12 mb-3">
                <h6 class="text-primary">Configuración de Cuenta</h6>
                <hr>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Rol *</label>
                <select 
                  v-model="form.role" 
                  class="form-select" 
                  :class="{ 'is-invalid': errors.role }"
                  required
                >
                  <option value="">Seleccionar</option>
                  <option value="admin">Administrador</option>
                  <option value="employee">Empleado</option>
                  <option value="customer">Cliente</option>
                </select>
                <div v-if="errors.role" class="invalid-feedback">
                  {{ errors.role[0] }}
                </div>
              </div>
              
              <div class="col-md-6 mb-3">
                <label class="form-label">Estado</label>
                <select v-model="form.is_active" class="form-select">
                  <option :value="true">Activo</option>
                  <option :value="false">Inactivo</option>
                </select>
              </div>
              
              <!-- Contraseña (solo al crear) -->
              <div v-if="!isEditing" class="col-12 mb-3">
                <h6 class="text-primary">Contraseña</h6>
                <hr>
              </div>
              
              <div v-if="!isEditing" class="col-md-6 mb-3">
                <label class="form-label">Contraseña *</label>
                <input 
                  v-model="form.password" 
                  type="password" 
                  class="form-control" 
                  :class="{ 'is-invalid': errors.password }"
                  required
                >
                <div v-if="errors.password" class="invalid-feedback">
                  {{ errors.password[0] }}
                </div>
              </div>
              
              <div v-if="!isEditing" class="col-md-6 mb-3">
                <label class="form-label">Confirmar Contraseña *</label>
                <input 
                  v-model="form.password_confirmation" 
                  type="password" 
                  class="form-control" 
                  required
                >
              </div>
              
              <!-- Información adicional para roles específicos -->
              <div v-if="form.role === 'admin'" class="col-12 mb-3">
                <div class="alert alert-warning">
                  <i class="pi pi-exclamation-triangle"></i>
                  <strong>Atención:</strong> Los administradores tienen acceso completo al sistema.
                </div>
              </div>
              
              <div v-if="form.role === 'employee'" class="col-12 mb-3">
                <div class="alert alert-info">
                  <i class="pi pi-info-circle"></i>
                  <strong>Información:</strong> Los empleados pueden gestionar reservas y registros.
                </div>
              </div>
            </div>
          </form>
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="$emit('close')">
            Cancelar
          </button>
          <button 
            type="button" 
            class="btn btn-primary" 
            @click="handleSubmit"
            :disabled="loading"
          >
            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
            {{ isEditing ? 'Actualizar' : 'Crear' }} Usuario
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'

export default {
  name: 'UserFormModal',
  props: {
    user: {
      type: Object,
      default: null
    },
    show: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close', 'success'],
  setup(props, { emit }) {
    const form = ref({
      name: '',
      email: '',
      phone: '',
      birth_date: '',
      document_type: '',
      document_number: '',
      address: '',
      role: '',
      is_active: true,
      password: '',
      password_confirmation: ''
    })
    
    const errors = ref({})
    const loading = ref(false)
    
    const isEditing = computed(() => !!props.user)
    
    const resetForm = () => {
      form.value = {
        name: '',
        email: '',
        phone: '',
        birth_date: '',
        document_type: '',
        document_number: '',
        address: '',
        role: '',
        is_active: true,
        password: '',
        password_confirmation: ''
      }
      errors.value = {}
    }
    
    const loadUserData = () => {
      if (props.user) {
        form.value = {
          name: props.user.name || '',
          email: props.user.email || '',
          phone: props.user.phone || '',
          birth_date: props.user.birth_date || '',
          document_type: props.user.document_type || '',
          document_number: props.user.document_number || '',
          address: props.user.address || '',
          role: props.user.roles?.[0] || '',
          is_active: props.user.is_active ?? true,
          password: '',
          password_confirmation: ''
        }
      }
    }
    
    const validateForm = () => {
      errors.value = {}
      
      if (!form.value.name.trim()) {
        errors.value.name = ['El nombre es requerido']
      }
      
      if (!form.value.email.trim()) {
        errors.value.email = ['El email es requerido']
      } else if (!/^\S+@\S+\.\S+$/.test(form.value.email)) {
        errors.value.email = ['El email no es válido']
      }
      
      if (!form.value.phone.trim()) {
        errors.value.phone = ['El teléfono es requerido']
      }
      
      if (!form.value.document_type) {
        errors.value.document_type = ['El tipo de documento es requerido']
      }
      
      if (!form.value.document_number.trim()) {
        errors.value.document_number = ['El número de documento es requerido']
      }
      
      if (!form.value.role) {
        errors.value.role = ['El rol es requerido']
      }
      
      if (!isEditing.value) {
        if (!form.value.password) {
          errors.value.password = ['La contraseña es requerida']
        } else if (form.value.password.length < 6) {
          errors.value.password = ['La contraseña debe tener al menos 6 caracteres']
        }
        
        if (form.value.password !== form.value.password_confirmation) {
          errors.value.password_confirmation = ['Las contraseñas no coinciden']
        }
      }
      
      return Object.keys(errors.value).length === 0
    }
    
    const handleSubmit = async () => {
      if (!validateForm()) {
        return
      }
      
      try {
        loading.value = true
        
        // Simular API call
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        // Aquí iría la llamada real a la API
        if (isEditing.value) {
          console.log('Actualizando usuario:', form.value)
        } else {
          console.log('Creando usuario:', form.value)
        }
        
        emit('success')
        
      } catch (error) {
        console.error('Error saving user:', error)
        
        // Manejar errores de validación del servidor
        if (error.response?.data?.errors) {
          errors.value = error.response.data.errors
        } else {
          alert('Error al guardar el usuario: ' + (error.message || 'Error desconocido'))
        }
      } finally {
        loading.value = false
      }
    }
    
    // Watchers
    watch(() => props.user, () => {
      if (props.show) {
        if (props.user) {
          loadUserData()
        } else {
          resetForm()
        }
      }
    }, { immediate: true })
    
    watch(() => props.show, (newVal) => {
      if (newVal) {
        if (props.user) {
          loadUserData()
        } else {
          resetForm()
        }
      }
    })
    
    return {
      form,
      errors,
      loading,
      isEditing,
      handleSubmit
    }
  }
}
</script>

<style scoped>
.modal.show {
  display: block !important;
}

.alert {
  border: none;
  border-radius: 0.5rem;
}

.form-control:focus,
.form-select:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.btn-primary {
  background-color: #007bff;
  border-color: #007bff;
}

.btn-primary:hover {
  background-color: #0056b3;
  border-color: #0056b3;
}

.text-primary {
  color: #007bff !important;
}

.invalid-feedback {
  display: block;
}

hr {
  margin: 0.5rem 0;
}
</style>