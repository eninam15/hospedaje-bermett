<template>
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <!-- Logo/Brand -->
      <router-link class="navbar-brand" to="/">
        <div class="brand-container">
          <div class="brand-icon">
            <div class="qr-code-container">
              <img src="/images/logo-bermett.png" alt="QR de Pago" style="width: 140px; height: 50px;" />
            </div>
          </div>
          
        </div>
      </router-link>

      <!-- Mobile Toggle -->
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <!-- Navigation -->
      <div class="collapse navbar-collapse" id="navbarNav">
        <template v-if="authInitialized">
          <!-- Left Menu -->
          <ul class="navbar-nav me-auto">
            <template v-if="!isAuthenticated">
              <li class="nav-item">
                <router-link to="/" class="nav-link">
                  <span>Inicio</span>
                </router-link>
              </li>
              <li class="nav-item">
                <router-link to="/search" class="nav-link">
                  <span>Habitaciones</span>
                </router-link>
              </li>
            </template>

            <template v-else-if="userRole === 'customer'">
              <li class="nav-item">
                <router-link to="/customer" class="nav-link">
                  <i class="bi bi-grid-3x3-gap"></i>
                  <span>Dashboard</span>
                </router-link>
              </li>
              <li class="nav-item">
                <router-link to="/customer/reservations" class="nav-link">
                  <i class="bi bi-calendar2-heart"></i>
                  <span>Mis Reservas</span>
                </router-link>
              </li>
            </template>

            <template v-else-if="userRole === 'admin'">
              <li class="nav-item">
                <router-link to="/admin" class="nav-link">
                  <i class="bi bi-speedometer2"></i>
                  <span>Admin Panel</span>
                </router-link>
              </li>
              <li class="nav-item">
                <router-link to="/admin/reservations" class="nav-link">
                  <i class="bi bi-calendar-week"></i>
                  <span>Reservas</span>
                </router-link>
              </li>
            </template>
          </ul>

          <!-- Right Menu -->
          <ul class="navbar-nav">
            <template v-if="!isAuthenticated">
              <li class="nav-item">
                <router-link to="/login" class="nav-link">
                  <span>Iniciar Sesión</span>
                </router-link>
              </li>
              <li class="nav-item">
                <router-link to="/register" class="btn btn-register">
                  <span>Registrarse</span>
                </router-link>
              </li>
            </template>

            <template v-else>
              <!-- User Menu -->
              <li class="nav-item dropdown user-dropdown">
                <a class="nav-link dropdown-toggle user-menu" href="#" data-bs-toggle="dropdown">
                  <div class="user-avatar">
                    <img v-if="userAvatar" :src="userAvatar" :alt="userName" />
                    <span v-else class="user-initials">{{ userInitials }}</span>
                  </div>
                  <div class="user-info">
                    <span class="user-name">{{ userName }}</span>
                    <span class="user-role">{{ userRoleText }}</span>
                  </div>
                  <i class="bi bi-chevron-down dropdown-arrow"></i>
                </a>

                <ul class="dropdown-menu dropdown-menu-end">
                  <!-- User Info Header -->
                  <li class="dropdown-header">
                    <div class="dropdown-user-info">
                      <div class="dropdown-avatar">
                        <img v-if="userAvatar" :src="userAvatar" :alt="userName" />
                        <span v-else class="user-initials">{{ userInitials }}</span>
                      </div>
                      <div class="dropdown-user-details">
                        <span class="dropdown-user-name">{{ userName }}</span>
                        <span class="dropdown-user-email">{{ userEmail }}</span>
                      </div>
                    </div>
                  </li>

                  <li><hr class="dropdown-divider" /></li>

                  <!-- Menu Items -->
                  <template v-if="userRole === 'customer'">
                    <li>
                      <router-link to="/customer" class="dropdown-item">
                        <i class="bi bi-grid-3x3-gap"></i>
                        <span>Dashboard</span>
                      </router-link>
                    </li>
                    <li>
                      <router-link to="/customer/reservations" class="dropdown-item">
                        <i class="bi bi-calendar2-heart"></i>
                        <span>Mis Reservas</span>
                      </router-link>
                    </li>
                    <li>
                      <router-link to="/customer/profile" class="dropdown-item">
                        <i class="bi bi-person-gear"></i>
                        <span>Mi Perfil</span>
                      </router-link>
                    </li>
                  </template>

                  <template v-if="userRole === 'admin'">
                    <li>
                      <router-link to="/admin" class="dropdown-item">
                        <i class="bi bi-speedometer2"></i>
                        <span>Admin Panel</span>
                      </router-link>
                    </li>
                    <li>
                      <router-link to="/admin/reservations" class="dropdown-item">
                        <i class="bi bi-calendar-week"></i>
                        <span>Reservas</span>
                      </router-link>
                    </li>
                    <li>
                      <router-link to="/admin/users" class="dropdown-item">
                        <i class="bi bi-people"></i>
                        <span>Usuarios</span>
                      </router-link>
                    </li>
                  </template>

                  <li><hr class="dropdown-divider" /></li>

                  <li>
                    <a href="#" @click.prevent="handleLogout" class="dropdown-item logout-item">
                      <i class="bi bi-box-arrow-right"></i>
                      <span>Cerrar Sesión</span>
                    </a>
                  </li>
                </ul>
              </li>
            </template>
          </ul>
        </template>

        <!-- Loading State -->
        <template v-else>
          <div class="loading-container">
            <div class="loading-spinner"></div>
            <span>Cargando...</span>
          </div>
        </template>
      </div>
    </div>
  </nav>
</template>

<script>
import { computed, onMounted } from 'vue'
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
    const authInitialized = computed(() => authStore.initialized)
    const userEmail = computed(() => authStore.user?.email || '')
    const userAvatar = computed(() => authStore.user?.profile_photo || null)

    const userInitials = computed(() => {
      const name = userName.value
      if (!name) return 'U'
      const words = name.split(' ')
      if (words.length >= 2) {
        return `${words[0][0]}${words[1][0]}`.toUpperCase()
      }
      return name.substring(0, 2).toUpperCase()
    })

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

    onMounted(async () => {
      if (!authStore.initialized) {
        await authStore.checkAuth()
      }
    })

    return {
      isAuthenticated,
      userName,
      userRole,
      userRoleText,
      userInitials,
      userEmail,
      userAvatar,
      authInitialized,
      handleLogout,
    }
  },
}
</script>

<style scoped>
/* Navbar Base */
.navbar {
  background: #fff;
  box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
  padding: 1rem 0;
  position: sticky;
  top: 0;
  z-index: 1000;
  backdrop-filter: blur(10px);
}

/* Brand */
.navbar-brand {
  text-decoration: none;
  margin-right: 3rem;
}

.brand-container {
  display: flex;
  align-items: center;
  gap: 12px;
}

.brand-icon {
  width: 44px;
  height: 44px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.4rem;
}

.brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1;
}

.brand-name {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a1a1a;
  letter-spacing: -0.02em;
}

.brand-subtitle {
  font-size: 0.75rem;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-weight: 500;
}

/* Navigation */
.navbar-nav .nav-item {
  margin: 0 0.25rem;
}

.nav-link {
  color: #555 !important;
  font-weight: 500;
  padding: 0.6rem 1rem !important;
  border-radius: 8px;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}

.nav-link:hover {
  color: #667eea !important;
  background: rgba(102, 126, 234, 0.08);
}

.nav-link.router-link-active {
  color: #667eea !important;
  background: rgba(102, 126, 234, 0.12);
  font-weight: 600;
}

/* Register Button */
.btn-register {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white !important;
  padding: 0.6rem 1.5rem;
  border-radius: 25px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
  border: none;
  margin-left: 0.5rem;
}

.btn-register:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
  color: white !important;
}

/* User Menu */
.user-menu {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0.5rem 1rem !important;
  border-radius: 25px;
  background: rgba(0, 0, 0, 0.02);
  border: 1px solid rgba(0, 0, 0, 0.06);
  transition: all 0.2s ease;
}

.user-menu:hover {
  background: rgba(102, 126, 234, 0.05);
  border-color: rgba(102, 126, 234, 0.2);
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.user-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-initials {
  color: white;
  font-weight: 600;
  font-size: 0.9rem;
}

.user-info {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
  text-align: left;
}

.user-name {
  font-weight: 600;
  color: #1a1a1a;
  font-size: 0.9rem;
}

.user-role {
  font-size: 0.75rem;
  color: #666;
}

.dropdown-arrow {
  font-size: 0.8rem;
  color: #666;
  transition: transform 0.2s ease;
}

.dropdown.show .dropdown-arrow {
  transform: rotate(180deg);
}

/* Dropdown Menu */
.dropdown-menu {
  border: none;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
  padding: 0.5rem;
  margin-top: 0.5rem;
  min-width: 260px;
  background: white;
}

.dropdown-header {
  padding: 1rem;
  background: linear-gradient(135deg, #f8f9ff 0%, #f1f4ff 100%);
  border-radius: 12px;
  margin-bottom: 0.5rem;
  border: none;
}

.dropdown-user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.dropdown-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.dropdown-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.dropdown-user-details {
  display: flex;
  flex-direction: column;
  line-height: 1.3;
}

.dropdown-user-name {
  font-weight: 600;
  color: #1a1a1a;
}

.dropdown-user-email {
  font-size: 0.85rem;
  color: #666;
}

.dropdown-item {
  padding: 0.75rem 1rem;
  border-radius: 8px;
  margin: 0.125rem 0;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 12px;
  color: #555;
  text-decoration: none;
}

.dropdown-item:hover {
  background: rgba(102, 126, 234, 0.08);
  color: #667eea;
  transform: translateX(4px);
}

.dropdown-item i {
  width: 18px;
  font-size: 1.1rem;
}

.logout-item:hover {
  background: rgba(239, 68, 68, 0.08) !important;
  color: #ef4444 !important;
}

.dropdown-divider {
  margin: 0.5rem 0;
  border-color: rgba(0, 0, 0, 0.08);
}

/* Mobile Toggle */
.navbar-toggler {
  border: none;
  padding: 0.25rem;
  width: 32px;
  height: 32px;
  position: relative;
  background: none;
}

.navbar-toggler span {
  display: block;
  width: 22px;
  height: 2px;
  background: #1a1a1a;
  margin: 4px 0;
  transition: 0.3s;
  border-radius: 1px;
}

.navbar-toggler:focus {
  box-shadow: none;
}

/* Loading */
.loading-container {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #666;
  font-size: 0.9rem;
}

.loading-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid #e5e5e5;
  border-top: 2px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 991.98px) {
  .brand-text {
    display: none;
  }
  
  .brand-icon {
    width: 40px;
    height: 40px;
  }
  
  .navbar-collapse {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
  }
  
  .nav-link {
    text-align: center;
    margin: 0.25rem 0;
  }
  
  .btn-register {
    margin: 1rem 0;
    text-align: center;
    display: block;
  }
  
  .user-menu {
    justify-content: center;
    margin: 0.5rem 0;
  }
  
  .dropdown-menu {
    position: static !important;
    transform: none !important;
    box-shadow: none;
    border: 1px solid rgba(0, 0, 0, 0.1);
    margin-top: 0.5rem;
  }
}

@media (max-width: 575.98px) {
  .user-info {
    display: none;
  }
  
  .dropdown-arrow {
    display: none;
  }
}
</style>