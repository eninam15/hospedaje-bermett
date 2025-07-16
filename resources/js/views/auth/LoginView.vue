<template>
  <div class="min-vh-100 d-flex align-items-center bg-light">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
          <div class="card shadow">
            <div class="card-body p-4">
              <div class="text-center mb-4">
                <h3 class="text-primary">🏨 Hospedaje Bermett</h3>
                <p class="text-muted">Iniciar Sesión</p>
              </div>

              <form @submit.prevent="handleLogin">
                <div class="mb-3">
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
                    {{ errors.email[0] }}
                  </div>
                </div>

                <div class="mb-3">
                  <label for="password" class="form-label">Contraseña</label>
                  <input 
                    v-model="form.password" 
                    type="password" 
                    class="form-control" 
                    id="password"
                    :class="{ 'is-invalid': errors.password }"
                    required
                  >
                  <div v-if="errors.password" class="invalid-feedback">
                    {{ errors.password[0] }}
                  </div>
                </div>

                <div class="mb-3 form-check">
                  <input v-model="form.remember" type="checkbox" class="form-check-input" id="remember">
                  <label class="form-check-label" for="remember">
                    Recordarme
                  </label>
                </div>

                <button 
                  type="submit" 
                  class="btn btn-primary w-100"
                  :disabled="loading"
                >
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  {{ loading ? 'Iniciando...' : 'Iniciar Sesión' }}
                </button>
              </form>

              <div class="text-center mt-4">
                <p class="text-muted">¿No tienes cuenta?</p>
                <router-link to="/register" class="btn btn-outline-primary">
                  Registrarse
                </router-link>
              </div>

              <!-- Usuarios de prueba -->
              <div class="mt-4">
                <small class="text-muted">Usuarios de prueba:</small>
                <div class="d-grid gap-2 mt-2">
                  <button 
                    @click="loginAsAdmin" 
                    class="btn btn-sm btn-outline-success"
                    type="button"
                  >
                    Admin: admin@bermett.com
                  </button>
                  <button 
                    @click="loginAsCustomer" 
                    class="btn btn-sm btn-outline-info"
                    type="button"
                  >
                    Cliente: cliente@bermett.com
                  </button>
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
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth.js'

export default {
  name: 'LoginView',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    
    const form = ref({
      email: '',
      password: '',
      remember: false
    })
    
    const errors = ref({})
    const loading = ref(false)

    const handleLogin = async () => {
      try {
        loading.value = true
        errors.value = {}
        
        await authStore.login(form.value)
        
        // Redirigir según rol
        if (authStore.isAdmin) {
          router.push('/admin')
        } else if (authStore.isCustomer) {
          router.push('/customer')
        } else {
          router.push('/')
        }
      } catch (error) {
        if (error.response?.data?.errors) {
          errors.value = error.response.data.errors
        } else {
          errors.value = { 
            email: [error.response?.data?.message || 'Error al iniciar sesión'] 
          }
        }
      } finally {
        loading.value = false
      }
    }

    const loginAsAdmin = () => {
      form.value.email = 'admin@bermett.com'
      form.value.password = 'admin123'
      handleLogin()
    }

    const loginAsCustomer = () => {
      form.value.email = 'cliente@bermett.com'
      form.value.password = 'cliente123'
      handleLogin()
    }

    return {
      form,
      errors,
      loading,
      handleLogin,
      loginAsAdmin,
      loginAsCustomer
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

.text-primary {
  color: #007bff !important;
}

.form-control:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
</style>