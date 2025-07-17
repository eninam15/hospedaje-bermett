<template>
  <div class="login-view min-vh-100 d-flex align-items-center">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
          <div class="auth-container">
            <div class="row g-0">
              <!-- Left Side - Branding -->
              <div class="col-lg-6 d-none d-lg-flex">
                <div class="auth-branding">
                  <div class="branding-content">
                    <div class="brand-logo mb-4">
                      <i class="bi bi-building display-1 text-white"></i>
                    </div>
                    <h2 class="brand-title text-white mb-3">Bienvenido a Hostal Bermett</h2>
                    <p class="brand-subtitle text-white-50 mb-4">
                      Tu hogar lejos de casa. Inicia sesión para acceder a tu cuenta y gestionar tus
                      reservas de manera fácil y segura.
                    </p>
                    <div class="brand-features">
                      <div class="feature-item d-flex align-items-center mb-2">
                        <i class="bi bi-check-circle-fill text-secondary me-2"></i>
                        <span class="text-white-50">Reservas online 24/7</span>
                      </div>
                      <div class="feature-item d-flex align-items-center mb-2">
                        <i class="bi bi-check-circle-fill text-secondary me-2"></i>
                        <span class="text-white-50">Gestión completa de estadías</span>
                      </div>
                      <div class="feature-item d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-secondary me-2"></i>
                        <span class="text-white-50">Historial de consumos</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Right Side - Login Form -->
              <div class="col-lg-6">
                <div class="auth-form">
                  <div class="form-header text-center mb-4">
                    <h3 class="form-title mb-2">Iniciar Sesión</h3>
                    <p class="form-subtitle text-muted">
                      Accede a tu cuenta para gestionar tus reservas
                    </p>
                  </div>

                  <!-- Alert Messages -->
                  <div v-if="error" class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ error }}
                  </div>

                  <div v-if="successMessage" class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    {{ successMessage }}
                  </div>

                  <!-- Login Form -->
                  <form @submit.prevent="handleLogin" class="login-form">
                    <div class="mb-3">
                      <label for="email" class="form-label">
                        <i class="bi bi-envelope me-1"></i>
                        Correo Electrónico
                      </label>
                      <input
                        id="email"
                        v-model="loginForm.email"
                        type="email"
                        class="form-control form-control-lg"
                        :class="{ 'is-invalid': errors.email }"
                        placeholder="tu@email.com"
                        required
                        autocomplete="email"
                      />
                      <div v-if="errors.email" class="invalid-feedback">
                        {{ errors.email }}
                      </div>
                    </div>

                    <div class="mb-3">
                      <label for="password" class="form-label">
                        <i class="bi bi-lock me-1"></i>
                        Contraseña
                      </label>
                      <div class="input-group">
                        <input
                          id="password"
                          v-model="loginForm.password"
                          :type="showPassword ? 'text' : 'password'"
                          class="form-control form-control-lg"
                          :class="{ 'is-invalid': errors.password }"
                          placeholder="Tu contraseña"
                          required
                          autocomplete="current-password"
                        />
                        <button
                          type="button"
                          class="btn btn-outline-secondary"
                          @click="togglePassword"
                        >
                          <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                        </button>
                      </div>
                      <div v-if="errors.password" class="invalid-feedback d-block">
                        {{ errors.password }}
                      </div>
                    </div>

                    <div class="mb-3 d-flex justify-content-between align-items-center">
                      <div class="form-check">
                        <input
                          id="remember"
                          v-model="loginForm.remember"
                          type="checkbox"
                          class="form-check-input"
                        />
                        <label for="remember" class="form-check-label"> Recordarme </label>
                      </div>
                      <router-link
                        to="/forgot-password"
                        class="text-primary text-decoration-none"
                      >
                        ¿Olvidaste tu contraseña?
                      </router-link>
                    </div>

                    <button
                      type="submit"
                      class="btn btn-primary btn-lg w-100 mb-3"
                      :disabled="loading"
                    >
                      <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                      <i v-else class="bi bi-box-arrow-in-right me-2"></i>
                      {{ loading ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
                    </button>
                  </form>

                  <!-- Demo Accounts -->
                  <div class="demo-accounts mb-4">
                    <p class="text-center text-muted mb-3">
                      <small>Cuentas de demostración:</small>
                    </p>
                    <div class="row g-2">
                      <div class="col-6">
                        <button
                          type="button"
                          class="btn btn-outline-primary btn-sm w-100"
                          @click="fillDemoAdmin"
                          :disabled="loading"
                        >
                          <i class="bi bi-person-gear me-1"></i>
                          Admin
                        </button>
                      </div>
                      <div class="col-6">
                        <button
                          type="button"
                          class="btn btn-outline-success btn-sm w-100"
                          @click="fillDemoCustomer"
                          :disabled="loading"
                        >
                          <i class="bi bi-person me-1"></i>
                          Cliente
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Register Link -->
                  <div class="text-center">
                    <p class="mb-0">
                      ¿No tienes una cuenta?
                      <router-link
                        to="/register"
                        class="text-primary text-decoration-none fw-semibold"
                      >
                        Regístrate aquí
                      </router-link>
                    </p>
                  </div>
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
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'LoginView',
  setup() {
    const router = useRouter()
    const route = useRoute()
    const authStore = useAuthStore()

    const loading = ref(false)
    const error = ref('')
    const successMessage = ref('')
    const showPassword = ref(false)

    const loginForm = reactive({
      email: '',
      password: '',
      remember: false,
    })

    const errors = reactive({
      email: '',
      password: '',
    })

    const validateForm = () => {
      errors.email = ''
      errors.password = ''

      if (!loginForm.email) {
        errors.email = 'El correo electrónico es requerido'
        return false
      }

      if (!/\S+@\S+\.\S+/.test(loginForm.email)) {
        errors.email = 'El correo electrónico no es válido'
        return false
      }

      if (!loginForm.password) {
        errors.password = 'La contraseña es requerida'
        return false
      }

      if (loginForm.password.length < 6) {
        errors.password = 'La contraseña debe tener al menos 6 caracteres'
        return false
      }

      return true
    }

    const handleLogin = async () => {
      error.value = ''

      if (!validateForm()) {
        return
      }

      try {
        loading.value = true

        // Usar nuestro authStore.login directamente
        await authStore.login({
          email: loginForm.email,
          password: loginForm.password,
        })

        // Redirigir según el rol usando nuestros getters
        if (authStore.isAdmin) {
          router.push('/admin')
        } else if (authStore.isCustomer) {
          router.push('/customer')
        } else {
          router.push('/')
        }
      } catch (err) {
        // Manejar errores de nuestro sistema
        if (err.response?.data?.errors) {
          Object.assign(errors, err.response.data.errors)
        } else {
          error.value = err.response?.data?.message || err.message || 'Error al iniciar sesión'
        }
      } finally {
        loading.value = false
      }
    }

    const togglePassword = () => {
      showPassword.value = !showPassword.value
    }

    const fillDemoAdmin = () => {
      loginForm.email = 'admin@bermett.com'
      loginForm.password = 'admin123'
    }

    const fillDemoCustomer = () => {
      loginForm.email = 'cliente@bermett.com'
      loginForm.password = 'cliente123'
    }

    onMounted(() => {
      // Si hay un mensaje de success en la query (ej: después de registro)
      if (route.query.message) {
        successMessage.value = route.query.message
      }

      // Limpiar mensajes después de 5 segundos
      setTimeout(() => {
        successMessage.value = ''
      }, 5000)
    })

    return {
      loginForm,
      errors,
      loading,
      error,
      successMessage,
      showPassword,
      handleLogin,
      togglePassword,
      fillDemoAdmin,
      fillDemoCustomer,
    }
  },
}
</script>

<style scoped>
:root {
  --primary-color: #2c5aa0;
  --primary-dark: #1e3a5f;
  --gray-800: #1a1a1a;
  --gray-100: #f3f4f6;
  --border-radius-xl: 1rem;
  --border-radius-md: 0.5rem;
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --transition-base: all 0.2s ease;
}

.login-view {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.auth-container {
  background: white;
  border-radius: var(--border-radius-xl);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
  max-width: 900px;
  margin: 0 auto;
}

.auth-branding {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3rem 2rem;
  position: relative;
  overflow: hidden;
}

.auth-branding::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="dots" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23dots)"/></svg>');
  animation: float 20s ease-in-out infinite;
}

@keyframes float {
  0%,
  100% {
    transform: translateY(0px) rotate(0deg);
  }
  50% {
    transform: translateY(-20px) rotate(1deg);
  }
}

.branding-content {
  position: relative;
  z-index: 1;
  text-align: center;
}

.brand-title {
  font-weight: 700;
  font-size: 1.75rem;
}

.brand-subtitle {
  line-height: 1.6;
}

.feature-item {
  font-size: 0.9rem;
}

.auth-form {
  padding: 3rem 2rem;
}

.form-title {
  font-weight: 700;
  color: var(--gray-800);
}

.form-subtitle {
  font-size: 0.95rem;
}

.form-control-lg {
  padding: 0.75rem 1rem;
  font-size: 1rem;
}

.login-form .form-control:focus {
  border-color: var(--primary-color);
  box-shadow: 0 0 0 0.2rem rgba(44, 90, 160, 0.25);
}

.btn-primary {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
  border: none;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  transition: var(--transition-base);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.btn-primary:disabled {
  opacity: 0.7;
  transform: none;
}

.demo-accounts {
  background-color: var(--gray-100);
  border-radius: var(--border-radius-md);
  padding: 1rem;
}

.demo-accounts .btn {
  font-size: 0.8rem;
  padding: 0.4rem 0.8rem;
}

.input-group .btn {
  border-left: 0;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

.alert {
  border-radius: var(--border-radius-md);
  border: none;
  font-size: 0.9rem;
}

.alert-danger {
  background-color: #fef2f2;
  color: #dc2626;
}

.alert-success {
  background-color: #f0fdf4;
  color: #16a34a;
}

@media (max-width: 992px) {
  .auth-form {
    padding: 2rem 1.5rem;
  }

  .form-title {
    font-size: 1.5rem;
  }
}

@media (max-width: 768px) {
  .auth-container {
    margin: 1rem;
  }

  .auth-form {
    padding: 1.5rem 1rem;
  }
}
</style>