<!-- src/components/common/AppHeader.vue -->
<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
      <!-- Logo/Brand -->
      <router-link class="navbar-brand fw-bold d-flex align-items-center" to="/">
        <i class="bi bi-building me-2"></i>
        Hostal Bernet
      </router-link>

      <!-- Mobile menu button -->
      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#navbarNav"
        aria-controls="navbarNav"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon"></span>
      </button>

      <!-- Navigation -->
      <div class="collapse navbar-collapse" id="navbarNav">
        <!-- Left Navigation -->
        <ul class="navbar-nav me-auto">
          <template v-if="!isAuthenticated">
            <!-- Navegación pública -->
            <li class="nav-item">
              <router-link to="/" class="nav-link">Inicio</router-link>
            </li>
            <li class="nav-item">
              <router-link to="/search" class="nav-link">Habitaciones</router-link>
            </li>
            <li class="nav-item">
              <router-link to="/about" class="nav-link">Nosotros</router-link>
            </li>
            <li class="nav-item">
              <router-link to="/contact" class="nav-link">Contacto</router-link>
            </li>
          </template>

          <template v-else-if="userRole === 'customer'">
            <!-- Navegación del cliente -->
            <li class="nav-item">
              <router-link to="/customer" class="nav-link">Dashboard</router-link>
            </li>
            <li class="nav-item">
              <router-link to="/customer/reservations" class="nav-link">Mis Reservas</router-link>
            </li>
            <li class="nav-item">
              <router-link to="/customer/registrations" class="nav-link">Mis Estadías</router-link>
            </li>
          </template>
        </ul>

        <!-- Right Navigation -->
        <ul class="navbar-nav">
          <template v-if="!isAuthenticated">
            <!-- Botones de autenticación -->
            <li class="nav-item">
              <router-link to="/login" class="nav-link">
                <i class="bi bi-box-arrow-in-right me-1"></i>
                Iniciar Sesión
              </router-link>
            </li>
            <li class="nav-item">
              <router-link to="/register" class="nav-link btn btn-outline-light rounded-pill ms-2 px-3">
                Registrarse
              </router-link>
            </li>
          </template>

          <template v-else>
            <!-- Menu de usuario autenticado -->
            <li class="nav-item dropdown">
              <a
                class="nav-link dropdown-toggle d-flex align-items-center"
                href="#"
                id="navbarDropdown"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                <div class="avatar me-2">
                  <i class="bi bi-person-circle fs-4"></i>
                </div>
                <div class="d-none d-md-block">
                  <div class="fw-semibold">{{ userName }}</div>
                  <div class="text-light opacity-75 small">{{ userRoleText }}</div>
                </div>
              </a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li>
                  <h6 class="dropdown-header">
                    <i class="bi bi-person me-2"></i>
                    {{ userName }}
                  </h6>
                </li>
                <li><hr class="dropdown-divider" /></li>

                <!-- Opciones por rol -->
                <template v-if="userRole === 'customer'">
                  <li>
                    <router-link to="/customer" class="dropdown-item">
                      <i class="bi bi-speedometer2 me-2"></i>
                      Dashboard
                    </router-link>
                  </li>
                  <li>
                    <router-link to="/customer/profile" class="dropdown-item">
                      <i class="bi bi-person-gear me-2"></i>
                      Mi Perfil
                    </router-link>
                  </li>
                  <li>
                    <router-link to="/customer/reservations" class="dropdown-item">
                      <i class="bi bi-calendar-check me-2"></i>
                      Mis Reservas
                    </router-link>
                  </li>
                </template>

                <template v-if="userRole === 'admin'">
                  <li>
                    <router-link to="/admin" class="dropdown-item">
                      <i class="bi bi-speedometer2 me-2"></i>
                      Panel Admin
                    </router-link>
                  </li>
                  <li>
                    <router-link to="/admin/reservations" class="dropdown-item">
                      <i class="bi bi-calendar-check me-2"></i>
                      Reservas
                    </router-link>
                  </li>
                  <li>
                    <router-link to="/admin/payments" class="dropdown-item">
                      <i class="bi bi-credit-card me-2"></i>
                      Pagos
                    </router-link>
                  </li>
                </template>

                <li><hr class="dropdown-divider" /></li>
                <li>
                  <a href="#" @click.prevent="handleLogout" class="dropdown-item text-danger">
                    <i class="bi bi-box-arrow-right me-2"></i>
                    Cerrar Sesión
                  </a>
                </li>
              </ul>
            </li>
          </template>
        </ul>
      </div>
    </div>
  </nav>
</template>

<script>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'AppHeader',
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()

    const isAuthenticated = computed(() => authStore.isAuthenticated)
    const userName = computed(() => authStore.userName)
    const userRole = computed(() => authStore.userRole)

    const userRoleText = computed(() => {
      switch (userRole.value) {
        case 'admin':
          return 'Administrador'
        case 'customer':
          return 'Cliente'
        default:
          return 'Usuario'
      }
    })

    const handleLogout = async () => {
      try {
        await authStore.logout()
        router.push('/')
      } catch (error) {
        console.error('Error al cerrar sesión:', error)
      }
    }

    return {
      isAuthenticated,
      userName,
      userRole,
      userRoleText,
      handleLogout,
    }
  },
}
</script>

<style scoped>
.navbar {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.navbar-brand {
  font-size: 1.5rem;
  font-weight: 700;
  text-decoration: none;
  color: white !important;
}

.nav-link {
  font-weight: 500;
  transition: all 0.3s ease;
  color: rgba(255, 255, 255, 0.85) !important;
}

.nav-link:hover {
  color: white !important;
  transform: translateY(-1px);
}

.nav-link.router-link-active {
  color: white !important;
  font-weight: 600;
}

.btn-outline-light {
  border-color: rgba(255, 255, 255, 0.5);
  color: white !important;
  transition: all 0.3s ease;
}

.btn-outline-light:hover {
  background-color: white;
  color: var(--bs-primary) !important;
  border-color: white;
  transform: translateY(-1px);
}

.dropdown-menu {
  border: none;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  border-radius: 0.75rem;
  min-width: 250px;
  margin-top: 0.5rem;
}

.dropdown-header {
  color: var(--bs-primary);
  font-weight: 600;
}

.dropdown-item {
  padding: 0.75rem 1rem;
  transition: all 0.3s ease;
  border-radius: 0.5rem;
  margin: 0.25rem 0.5rem;
}

.dropdown-item:hover {
  background-color: var(--bs-light);
  transform: translateX(5px);
}

.dropdown-item.text-danger:hover {
  background-color: rgba(220, 53, 69, 0.1);
  color: var(--bs-danger) !important;
}

.avatar {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.dropdown-toggle::after {
  margin-left: 0.5rem;
}

@media (max-width: 768px) {
  .navbar-nav .nav-item {
    text-align: center;
    margin: 0.25rem 0;
  }

  .dropdown-menu {
    position: static !important;
    transform: none !important;
    box-shadow: none;
    border: none;
    background-color: rgba(255, 255, 255, 0.95);
    margin-top: 0.5rem;
    border-radius: 0.5rem;
  }

  .btn-outline-light {
    margin-top: 0.5rem;
    margin-left: 0 !important;
  }
}
</style>