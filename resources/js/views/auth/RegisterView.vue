<template>
  <div class="min-vh-100 d-flex align-items-center bg-light py-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
          <div class="card shadow">
            <div class="card-body p-4">
              <div class="text-center mb-4">
                <h3 class="text-primary">🏨 Hospedaje Bermett</h3>
                <p class="text-muted">Crear Cuenta</p>
              </div>

              <!-- Mostrar mensaje de error general -->
              <div v-if="generalError" class="alert alert-danger" role="alert">
                {{ generalError }}
              </div>

              <form @submit.prevent="handleRegister">
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Nombre Completo</label>
                    <input 
                      v-model="form.name" 
                      type="text" 
                      class="form-control" 
                      id="name"
                      :class="{ 'is-invalid': errors.name }"
                      required
                    >
                    <div v-if="errors.name" class="invalid-feedback">
                      {{ Array.isArray(errors.name) ? errors.name[0] : errors.name }}
                    </div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Correo Electrónico</label>
                    <input 
                      v-model="form.email" 
                      type="email" 
                      class="form-control" 
                      id="email"
                      :class="{ 'is-invalid': errors.email }"
                      required
                    >
                    <div v-if="errors.email" class="invalid-feedback">
                      {{ Array.isArray(errors.email) ? errors.email[0] : errors.email }}
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input 
                      v-model="form.password" 
                      type="password" 
                      class="form-control" 
                      id="password"
                      :class="{ 'is-invalid': errors.password }"
                      required
                      minlength="6"
                    >
                    <div v-if="errors.password" class="invalid-feedback">
                      {{ Array.isArray(errors.password) ? errors.password[0] : errors.password }}
                    </div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                    <input 
                      v-model="form.password_confirmation" 
                      type="password" 
                      class="form-control" 
                      id="password_confirmation"
                      :class="{ 'is-invalid': passwordMismatch }"
                      required
                      minlength="6"
                    >
                    <div v-if="passwordMismatch" class="invalid-feedback">
                      Las contraseñas no coinciden
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Teléfono</label>
                    <input 
                      v-model="form.phone" 
                      type="tel" 
                      class="form-control" 
                      id="phone"
                      :class="{ 'is-invalid': errors.phone }"
                      placeholder="+591 70000000"
                      required
                    >
                    <div v-if="errors.phone" class="invalid-feedback">
                      {{ Array.isArray(errors.phone) ? errors.phone[0] : errors.phone }}
                    </div>
                  </div>

                  <div class="col-md-6 mb-3">
                    <label for="document_type" class="form-label">Tipo de Documento</label>
                    <select 
                      v-model="form.document_type" 
                      class="form-select" 
                      id="document_type"
                      :class="{ 'is-invalid': errors.document_type }"
                      required
                    >
                      <option value="">Seleccionar</option>
                      <option value="ci">Cédula de Identidad</option>
                      <option value="passport">Pasaporte</option>
                      <option value="other">Otro</option>
                    </select>
                    <div v-if="errors.document_type" class="invalid-feedback">
                      {{ Array.isArray(errors.document_type) ? errors.document_type[0] : errors.document_type }}
                    </div>
                  </div>
                </div>

                <div class="mb-3">
                  <label for="document_number" class="form-label">Número de Documento</label>
                  <input 
                    v-model="form.document_number" 
                    type="text" 
                    class="form-control" 
                    id="document_number"
                    :class="{ 'is-invalid': errors.document_number }"
                    required
                  >
                  <div v-if="errors.document_number" class="invalid-feedback">
                    {{ Array.isArray(errors.document_number) ? errors.document_number[0] : errors.document_number }}
                  </div>
                </div>

                <div class="mb-3">
                  <label for="address" class="form-label">Dirección (Opcional)</label>
                  <textarea 
                    v-model="form.address" 
                    class="form-control" 
                    id="address"
                    rows="2"
                    :class="{ 'is-invalid': errors.address }"
                    placeholder="Dirección completa"
                  ></textarea>
                  <div v-if="errors.address" class="invalid-feedback">
                    {{ Array.isArray(errors.address) ? errors.address[0] : errors.address }}
                  </div>
                </div>

                <div class="mb-3 form-check">
                  <input 
                    v-model="form.accept_terms" 
                    type="checkbox" 
                    class="form-check-input" 
                    id="accept_terms" 
                    required
                  >
                  <label class="form-check-label" for="accept_terms">
                    Acepto los términos y condiciones
                  </label>
                </div>

                <button 
                  type="submit" 
                  class="btn btn-primary w-100"
                  :disabled="loading || !isFormValid"
                >
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  {{ loading ? 'Registrando...' : 'Crear Cuenta' }}
                </button>
              </form>

              <div class="text-center mt-4">
                <p class="text-muted">¿Ya tienes cuenta?</p>
                <router-link to="/login" class="btn btn-outline-primary">
                  Iniciar Sesión
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth.js'
import { authApi } from '../../services/api.js'

export default {
  name: 'RegisterView',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    
    const form = ref({
      name: '',
      email: '',
      password: '',
      password_confirmation: '',
      phone: '',
      document_type: '',
      document_number: '',
      address: '',
      accept_terms: false
    })
    
    const errors = ref({})
    const generalError = ref('')
    const loading = ref(false)

    // Validaciones computadas
    const passwordMismatch = computed(() => {
      return form.value.password && form.value.password_confirmation && 
             form.value.password !== form.value.password_confirmation
    })

    const isFormValid = computed(() => {
      return form.value.name && 
             form.value.email && 
             form.value.password && 
             form.value.password_confirmation &&
             form.value.phone &&
             form.value.document_type &&
             form.value.document_number &&
             form.value.accept_terms &&
             !passwordMismatch.value
    })

    const handleRegister = async () => {
      try {
        loading.value = true
        errors.value = {}
        generalError.value = ''
        
        console.log('Enviando datos de registro:', form.value)
        
        // Opción 1: Usar el store
        if (authStore && typeof authStore.register === 'function') {
          console.log('Usando authStore.register')
          await authStore.register(form.value)
        } else {
          // Opción 2: Usar directamente la API
          console.log('Usando authApi.register directamente')
          const response = await authApi.register(form.value)
          
          if (response.data.token) {
            // Guardar token manualmente si el store no funciona
            localStorage.setItem('auth_token', response.data.token)
            localStorage.setItem('auth_user', JSON.stringify(response.data.user))
          }
        }
        
        console.log('Registro exitoso, redirigiendo...')
        
        // Redirigir al dashboard del cliente o home
        router.push('/customer').catch(() => {
          // Si la ruta no existe, ir a home
          router.push('/')
        })
        
      } catch (error) {
        console.error('Error en registro:', error)
        
        if (error.response?.data?.errors) {
          // Errores de validación del servidor
          errors.value = error.response.data.errors
          console.log('Errores de validación:', errors.value)
        } else if (error.response?.data?.message) {
          // Mensaje de error del servidor
          generalError.value = error.response.data.message
        } else if (error.message) {
          // Error de red o JavaScript
          generalError.value = error.message
        } else {
          // Error desconocido
          generalError.value = 'Error desconocido al crear la cuenta'
        }
      } finally {
        loading.value = false
      }
    }

    return {
      form,
      errors,
      generalError,
      loading,
      passwordMismatch,
      isFormValid,
      handleRegister
    }
  }
}
</script>

<style scoped>
.min-vh-100 {
  min-height: 100vh;
}

.card {
  border: none;
  border-radius: 10px;
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
  opacity: 0.6;
  cursor: not-allowed;
}

.text-primary {
  color: #007bff !important;
}

.form-control:focus,
.form-select:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.form-check-input:checked {
  background-color: #007bff;
  border-color: #007bff;
}

.alert {
  border-radius: 0.375rem;
}
</style>