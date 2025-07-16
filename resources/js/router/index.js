import { useAuthStore } from '../stores/auth.js'

// Importar vistas
import HomeView from '../views/auth/HomeView.vue'
import SearchView from '../views/public/SearchView.vue'
import LoginView from '../views/auth/LoginView.vue'
import RegisterView from '../views/auth/RegisterView.vue'
import CustomerDashboard from '../views/customer/DashboardView.vue'
import CustomerReservations from '../views/customer/ReservationsView.vue'
import AdminDashboard from '../views/admin/DashboardView.vue'

const routes = [
  // Rutas públicas
  {
    path: '/',
    name: 'home',
    component: HomeView
  },
  {
    path: '/search',
    name: 'search',
    component: SearchView
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { guest: true }
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { guest: true }
  },
  
  // Rutas de cliente
  {
    path: '/customer',
    name: 'customer.dashboard',
    component: CustomerDashboard,
    meta: { requiresAuth: true, role: 'customer' }
  },
  {
    path: '/customer/reservations',
    name: 'customer.reservations',
    component: CustomerReservations,
    meta: { requiresAuth: true, role: 'customer' }
  },
  
  // Rutas de administrador
  {
    path: '/admin',
    name: 'admin.dashboard',
    component: AdminDashboard,
    meta: { requiresAuth: true, role: 'admin' }
  },
  
  // Ruta 404
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('../views/NotFoundView.vue')
  }
]

// Guards de navegación
const router = {
  beforeEach: (to, from, next) => {
    const authStore = useAuthStore()
    
    // Verificar autenticación
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
      next('/login')
      return
    }
    
    // Verificar guests (solo usuarios no autenticados)
    if (to.meta.guest && authStore.isAuthenticated) {
      // Redirigir según rol
      if (authStore.isAdmin) {
        next('/admin')
      } else if (authStore.isCustomer) {
        next('/customer')
      } else {
        next('/')
      }
      return
    }
    
    // Verificar roles
    if (to.meta.role && authStore.userRole !== to.meta.role) {
      // Redirigir a dashboard apropiado
      if (authStore.isAdmin) {
        next('/admin')
      } else if (authStore.isCustomer) {
        next('/customer')
      } else {
        next('/')
      }
      return
    }
    
    next()
  }
}

export default routes