import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: null,
    isAuthenticated: false,
    loading: false,
    initialized: false
  }),

  getters: {
    isAdmin() {
      return this.user?.roles?.some(role => role.name === 'admin') || false
    },
    isEmployee() {
      return this.user?.roles?.some(role => role.name === 'employee') || false
    },
    isCustomer() {
      return this.user?.roles?.some(role => role.name === 'customer') || false
    },
    userRole() {
      return this.user?.roles?.[0]?.name || null
    },
    userName() {
      return this.user?.name || ''
    }
  },

  actions: {
    async register(userData) {
      this.loading = true
      try {
        const response = await axios.post('/auth/register', userData)
        
        // Configurar autenticación inmediatamente después del registro
        this.token = response.data.token
        this.user = response.data.user
        this.isAuthenticated = true

        // Guardar en localStorage
        localStorage.setItem('auth_token', this.token)
        localStorage.setItem('auth_user', JSON.stringify(this.user))
        
        // Configurar axios header
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`

        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async login(credentials) {
      this.loading = true
      try {
        const response = await axios.post('/auth/login', credentials)
        
        this.token = response.data.token
        this.user = response.data.user
        this.isAuthenticated = true

        localStorage.setItem('auth_token', this.token)
        localStorage.setItem('auth_user', JSON.stringify(this.user))
        axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`

        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        if (this.token) {
          await axios.post('/auth/logout')
        }
      } catch (error) {
        // Ignorar errores del servidor en logout
      }

      // Limpiar estado local
      this.user = null
      this.token = null
      this.isAuthenticated = false

      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      delete axios.defaults.headers.common['Authorization']
    },

    async checkAuth() {
      if (this.initialized) {
        return this.isAuthenticated
      }

      const token = localStorage.getItem('auth_token')
      const savedUser = localStorage.getItem('auth_user')

      if (!token) {
        this.isAuthenticated = false
        this.initialized = true
        return false
      }

      // Configurar token
      this.token = token
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

      // Cargar usuario guardado como respaldo
      if (savedUser) {
        try {
          this.user = JSON.parse(savedUser)
        } catch (error) {
          // Ignorar error de parsing
        }
      }

      try {
        // Verificar con el servidor
        const response = await axios.get('/auth/user')
        
        this.user = response.data.user
        this.isAuthenticated = true
        this.initialized = true
        
        // Actualizar localStorage
        localStorage.setItem('auth_user', JSON.stringify(this.user))
        
        return true
      } catch (error) {
        // Si hay error 500, mantener sesión local si existe usuario
        if (error.response?.status === 500 && this.user) {
          this.isAuthenticated = true
          this.initialized = true
          return true
        }
        
        // Para otros errores, limpiar sesión
        if (error.response?.status === 401 || !this.user) {
          await this.logout()
        }
        
        this.initialized = true
        return false
      }
    },

    async updateProfile(profileData) {
      try {
        const response = await axios.put('/auth/profile', profileData)
        this.user = response.data.user
        localStorage.setItem('auth_user', JSON.stringify(this.user))
        return response.data
      } catch (error) {
        throw error
      }
    },

    async changePassword(passwordData) {
      try {
        const response = await axios.put('/auth/change-password', passwordData)
        return response.data
      } catch (error) {
        throw error
      }
    }
  }
})