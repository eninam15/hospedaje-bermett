import { useAuthStore } from '../stores/auth.js'

// Importar vistas
import HomeView from '../views/auth/HomeView.vue'
import SearchView from '../views/public/SearchView.vue'
import LoginView from '../views/auth/LoginView.vue'
import RegisterView from '../views/auth/RegisterView.vue'
import CustomerDashboard from '../views/customer/DashboardView.vue'
import CustomerReservations from '../views/customer/ReservationsView.vue'
import AdminDashboard from '../views/admin/DashboardView.vue'
import AdminUsers from '../views/admin/AdminUsersView.vue'
import AdminRooms from '../views/admin/RoomsView.vue'
import AdminReservations from '../views/admin/ReservationsView.vue'
import AdminPayments from '../views/admin/PaymentsView.vue'
import AdminReports from '../views/admin/ReportsView.vue'

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
  {
    path: '/customer/reservations/:id',
    name: 'customer.reservation.details',
    component: () => import('../views/customer/ReservationDetailsView.vue'),
    meta: { requiresAuth: true, role: 'customer' }
  },
  {
    path: '/customer/profile',
    name: 'customer.profile',
    component: () => import('../views/customer/ProfileView.vue'),
    meta: { requiresAuth: true, role: 'customer' }
  },
  
  // Rutas de administrador
  {
    path: '/admin',
    name: 'admin.dashboard',
    component: AdminDashboard,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/users',
    name: 'admin.users',
    component: AdminUsers,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/users/:id',
    name: 'admin.user.details',
    component: () => import('../views/admin/UserDetailsView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/rooms',
    name: 'admin.rooms',
    component: AdminRooms,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/rooms/:id',
    name: 'admin.room.details',
    component: () => import('../views/admin/RoomDetailsView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/room-types',
    name: 'admin.room-types',
    component: () => import('../views/admin/RoomTypesView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/services',
    name: 'admin.services',
    component: () => import('../views/admin/ServicesView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/branches',
    name: 'admin.branches',
    component: () => import('../views/admin/BranchesView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/reservations',
    name: 'admin.reservations',
    component: AdminReservations,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/reservations/:id',
    name: 'admin.reservation.details',
    component: () => import('../views/admin/ReservationDetailsView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/payments',
    name: 'admin.payments',
    component: AdminPayments,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/payments/pending',
    name: 'admin.payments.pending',
    component: () => import('../views/admin/PendingPaymentsView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/registrations',
    name: 'admin.registrations',
    component: () => import('../views/admin/RegistrationsView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/registrations/:id',
    name: 'admin.registration.details',
    component: () => import('../views/admin/RegistrationDetailsView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/reports',
    name: 'admin.reports',
    component: AdminReports,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/reports/:type',
    name: 'admin.report.details',
    component: () => import('../views/admin/ReportDetailsView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/settings',
    name: 'admin.settings',
    component: () => import('../views/admin/SettingsView.vue'),
    meta: { requiresAuth: true, role: 'admin' }
  },
  
  // Rutas de empleado
  {
    path: '/employee',
    name: 'employee.dashboard',
    component: () => import('../views/employee/DashboardView.vue'),
    meta: { requiresAuth: true, role: 'employee' }
  },
  {
    path: '/employee/reservations',
    name: 'employee.reservations',
    component: () => import('../views/employee/ReservationsView.vue'),
    meta: { requiresAuth: true, role: 'employee' }
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