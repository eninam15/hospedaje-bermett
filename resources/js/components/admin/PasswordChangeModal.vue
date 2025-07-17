<template>
  <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Cambiar Contraseña</h5>
          <button type="button" class="btn-close" @click="$emit('close')"></button>
        </div>
        
        <div class="modal-body">
          <div class="mb-3">
            <div class="d-flex align-items-center mb-3">
              <div class="avatar-circle me-3" :style="{ backgroundColor: getAvatarColor(user.name) }">
                {{ getAvatarInitial(user.name) }}
              </div>
              <div>
                <h6 class="mb-0">{{ user.name }}</h6>
                <small class="text-muted">{{ user.email }}</small>
              </div>
            </div>
          </div>
          
          <form @submit.prevent="handleSubmit">
            <div class="mb-3">
              <label class="form-label">Nueva Contraseña *</label>
              <div class="input-group">
                <input 
                  v-model="form.password" 
                  :type="showPassword ? 'text' : 'password'" 
                  class="form-control" 
                  :class="{ 'is-invalid': errors.password }"
                  placeholder="Ingrese la nueva contraseña"
                  required
                  @input="validatePassword"
                >
                <button 
                  type="button" 
                  class="btn btn-outline-secondary"
                  @click="showPassword = !showPassword"
                >
                  <i :class="showPassword ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                </button>
              </div>
              <div v-if="errors.password" class="invalid-feedback">
                {{ errors.password[0] }}
              </div>
              
              <!-- Indicador de fortaleza de contraseña -->
              <div v-if="form.password" class="mt-2">
                <small class="text-muted">Fortaleza de la contraseña:</small>
                <div class="progress" style="height: 6px;">
                  <div 
                    class="progress-bar" 
                    :class="passwordStrength.class"
                    :style="{ width: passwordStrength.percentage + '%' }"
                  ></div>
                </div>
                <small :class="passwordStrength.textClass">
                  {{ passwordStrength.text }}
                </small>
              </div>
            </div>
            
            <div class="mb-3">
              <label class="form-label">Confirmar Contraseña *</label>
              <div class="input-group">
                <input 
                  v-model="form.password_confirmation" 
                  :type="showPasswordConfirmation ? 'text' : 'password'" 
                  class="form-control" 
                  :class="{ 'is-invalid': errors.password_confirmation }"
                  placeholder="Confirme la nueva contraseña"
                  required
                  @input="validatePasswordConfirmation"
                >
                <button 
                  type="button" 
                  class="btn btn-outline-secondary"
                  @click="showPasswordConfirmation = !showPasswordConfirmation"
                >
                  <i :class="showPasswordConfirmation ? 'pi pi-eye-slash' : 'pi pi-eye'"></i>
                </button>
              </div>
              <div v-if="errors.password_confirmation" class="invalid-feedback">
                {{ errors.password_confirmation[0] }}
              </div>
            </div>
            
            <!-- Opciones adicionales -->
            <div class="mb-3">
              <div class="form-check">
                <input 
                  v-model="form.notify_user" 
                  class="form-check-input" 
                  type="checkbox" 
                  id="notify_user"
                >
                <label class="form-check-label" for="notify_user">
                  Notificar al usuario por email sobre el cambio de contraseña
                </label>
              </div>
              <div class="form-check">
                <input 
                  v-model="form.force_password_change" 
                  class="form-check-input" 
                  type="checkbox" 
                  id="force_password_change"
                >
                <label class="form-check-label" for="force_password_change">
                  Forzar cambio de contraseña en el próximo inicio de sesión
                </label>
              </div>
            </div>
            
            <!-- Botón de generar contraseña -->
            <div class="mb-3">
              <button 
                type="button" 
                class="btn btn-outline-secondary btn-sm"
                @click="generateRandomPassword"
              >
                <i class="pi pi-refresh"></i> Generar Contraseña Aleatoria
              </button>
            </div>
            
            <!-- Alertas de seguridad -->
            <div class="alert alert-info">
              <i class="pi pi-info-circle"></i>
              <strong>Recomendaciones de seguridad:</strong>
              <ul class="mb-0 mt-2">
                <li>Use al menos 8 caracteres</li>
                <li>Incluya letras mayúsculas y minúsculas</li>
                <li>Agregue números y símbolos</li>
                <li>Evite información personal</li>
              </ul>
            </div>
            
            <div v-if="user.id === currentUserId" class="alert alert-warning">
              <i class="pi pi-exclamation-triangle"></i>
              <strong>Atención:</strong> Está cambiando su propia contraseña. 
              Asegúrese de recordar la nueva contraseña.
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
            :disabled="loading || !isFormValid"
          >
            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
            Cambiar Contraseña
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue'
import { useAuthStore } from '../../stores/auth.js'
import { userHelpers } from '../../services/userService.js'

export default {
  name: 'PasswordChangeModal',
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
  emits: ['close', 'success'],
  setup(props, { emit }) {
    const authStore = useAuthStore()
    
    const form = ref({
      password: '',
      password_confirmation: '',
      notify_user: true,
      force_password_change: false
    })
    
    const errors = ref({})
    const loading = ref(false)
    const showPassword = ref(false)
    const showPasswordConfirmation = ref(false)
    
    const currentUserId = computed(() => authStore.user?.id)
    
    const passwordStrength = computed(() => {
      const password = form.value.password
      if (!password) return { percentage: 0, class: '', textClass: '', text: '' }
      
      let score = 0
      let feedback = []
      
      // Longitud
      if (password.length >= 8) score += 25
      else feedback.push('al menos 8 caracteres')
      
      // Mayúsculas
      if (/[A-Z]/.test(password)) score += 25
      else feedback.push('letras mayúsculas')
      
      // Minúsculas
      if (/[a-z]/.test(password)) score += 25
      else feedback.push('letras minúsculas')
      
      // Números y símbolos
      if (/[0-9]/.test(password) && /[^A-Za-z0-9]/.test(password)) score += 25
      else feedback.push('números y símbolos')
      
      let strengthClass, textClass, text
      
      if (score < 50) {
        strengthClass = 'bg-danger'
        textClass = 'text-danger'
        text = 'Débil'
      } else if (score < 75) {
        strengthClass = 'bg-warning'
        textClass = 'text-warning'
        text = 'Regular'
      } else if (score < 100) {
        strengthClass = 'bg-info'
        textClass = 'text-info'
        text = 'Buena'
      } else {
        strengthClass = 'bg-success'
        textClass = 'text-success'
        text = 'Excelente'
      }
      
      return {
        percentage: score,
        class: strengthClass,
        textClass,
        text
      }
    })
    
    const isFormValid = computed(() => {
      return form.value.password.length >= 6 && 
             form.value.password === form.value.password_confirmation &&
             Object.keys(errors.value).length === 0
    })
    
    const validatePassword = () => {
      errors.value.password = []
      
      if (!form.value.password) {
        errors.value.password = ['La contraseña es requerida']
        return
      }
      
      if (form.value.password.length < 6) {
        errors.value.password = ['La contraseña debe tener al menos 6 caracteres']
        return
      }
      
      delete errors.value.password
      
      // Revalidar confirmación si existe
      if (form.value.password_confirmation) {
        validatePasswordConfirmation()
      }
    }
    
    const validatePasswordConfirmation = () => {
      errors.value.password_confirmation = []
      
      if (!form.value.password_confirmation) {
        errors.value.password_confirmation = ['La confirmación de contraseña es requerida']
        return
      }
      
      if (form.value.password !== form.value.password_confirmation) {
        errors.value.password_confirmation = ['Las contraseñas no coinciden']
        return
      }
      
      delete errors.value.password_confirmation
    }
    
    const generateRandomPassword = () => {
      const password = userHelpers.generatePassword(12)
      form.value.password = password
      form.value.password_confirmation = password
      
      validatePassword()
      validatePasswordConfirmation()
    }
    
    const handleSubmit = async () => {
      validatePassword()
      validatePasswordConfirmation()
      
      if (!isFormValid.value) {
        return
      }
      
      try {
        loading.value = true
        
        // Simular API call
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        console.log('Changing password for user:', props.user.id, form.value)
        
        emit('success')
        
      } catch (error) {
        console.error('Error changing password:', error)
        
        if (error.response?.data?.errors) {
          errors.value = error.response.data.errors
        } else {
          alert('Error al cambiar la contraseña: ' + (error.message || 'Error desconocido'))
        }
      } finally {
        loading.value = false
      }
    }
    
    const getAvatarInitial = (name) => {
      return userHelpers.getAvatarInitial(name)
    }
    
    const getAvatarColor = (name) => {
      return userHelpers.getAvatarColor(name)
    }
    
    // Reset form cuando se abre el modal
    watch(() => props.show, (newVal) => {
      if (newVal) {
        form.value = {
          password: '',
          password_confirmation: '',
          notify_user: true,
          force_password_change: false
        }
        errors.value = {}
        showPassword.value = false
        showPasswordConfirmation.value = false
      }
    })
    
    return {
      form,
      errors,
      loading,
      showPassword,
      showPasswordConfirmation,
      currentUserId,
      passwordStrength,
      isFormValid,
      validatePassword,
      validatePasswordConfirmation,
      generateRandomPassword,
      handleSubmit,
      getAvatarInitial,
      getAvatarColor
    }
  }
}
</script>

<style scoped>
.modal.show {
  display: block !important;
}

.avatar-circle {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

.progress {
  border-radius: 10px;
  overflow: hidden;
}

.progress-bar {
  transition: width 0.3s ease;
}

.alert {
  border: none;
  border-radius: 0.5rem;
}

.alert ul {
  padding-left: 1.2rem;
}

.input-group .btn {
  border-left: none;
}

.form-control:focus {
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

.btn-primary:disabled {
  background-color: #6c757d;
  border-color: #6c757d;
}

.invalid-feedback {
  display: block;
}

.form-check-input:checked {
  background-color: #007bff;
  border-color: #007bff;
}

.text-danger {
  color: #dc3545 !important;
}

.text-warning {
  color: #ffc107 !important;
}

.text-info {
  color: #17a2b8 !important;
}

.text-success {
  color: #28a745 !important;
}

.bg-danger {
  background-color: #dc3545 !important;
}

.bg-warning {
  background-color: #ffc107 !important;
}

.bg-info {
  background-color: #17a2b8 !important;
}

.bg-success {
  background-color: #28a745 !important;
}
</style>