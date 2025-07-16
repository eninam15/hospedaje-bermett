<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
      <router-link class="navbar-brand fw-bold" to="/">
        🏨 Hospedaje Bermett
      </router-link>
      
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <router-link class="nav-link" to="/">Inicio</router-link>
          </li>
          <li class="nav-item">
            <router-link class="nav-link" to="/search">Buscar Habitaciones</router-link>
          </li>
        </ul>
        
        <ul class="navbar-nav">
          <li v-if="!isAuthenticated" class="nav-item">
            <router-link class="nav-link" to="/login">Iniciar Sesión</router-link>
          </li>
          <li v-if="!isAuthenticated" class="nav-item">
            <router-link class="nav-link" to="/register">Registrarse</router-link>
          </li>
          
          <li v-if="isAuthenticated" class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
              {{ userName }}
            </a>
            <ul class="dropdown-menu">
              <li v-if="isAdmin">
                <router-link class="dropdown-item" to="/admin">Panel Admin</router-link>
              </li>
              <li v-if="isCustomer">
                <router-link class="dropdown-item" to="/customer">Mi Dashboard</router-link>
              </li>
              <li v-if="isCustomer">
                <router-link class="dropdown-item" to="/customer/reservations">Mis Reservas</router-link>
              </li>
              <li><hr class="dropdown-divider"></li>
              <li>
                <a class="dropdown-item" href="#" @click="handleLogout">Cerrar Sesión</a>
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</template>

<script>
import { useAuthStore } from '../../stores/auth.js'
import { useRouter } from 'vue-router'

export default {
  name: 'AppHeader',
  setup() {
    const authStore = useAuthStore()
    const router = useRouter()
    
    const handleLogout = async () => {
      try {
        await authStore.logout()
        router.push('/')
      } catch (error) {
        console.error('Error al cerrar sesión:', error)
      }
    }
    
    return {
      isAuthenticated: authStore.isAuthenticated,
      isAdmin: authStore.isAdmin,
      isCustomer: authStore.isCustomer,
      userName: authStore.userName,
      handleLogout
    }
  }
}
</script>

<style scoped>
.navbar-brand {
  font-size: 1.5rem;
}

.nav-link {
  font-weight: 500;
}

.nav-link:hover {
  color: #fff !important;
}

.dropdown-menu {
  border: none;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.dropdown-item:hover {
  background-color: #f8f9fa;
}
</style>