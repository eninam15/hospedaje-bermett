import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('auth_token'),
    isAuthenticated: false,
    loading: false
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
    async login(credentials) {
      this.loading = true
      try {
        const response = await axios.post('/auth/login', credentials)
        
        console.log('Login response:', response.data);
    
        
        this.token = response.data.token
        this.user = response.data.user
        this.isAuthenticated = true
        
        localStorage.setItem('auth_token', this.token)
        
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async register(userData) {
      this.loading = true
      try {
        const response = await axios.post('/auth/register', userData)
        
        this.token = response.data.token
        this.user = response.data.user
        this.isAuthenticated = true
        
        localStorage.setItem('auth_token', this.token)
        
        return response.data
      } catch (error) {
        throw error
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await axios.post('/auth/logout')
      } catch (error) {
        // Continuar con logout local aunque falle el servidor
      }
      
      this.user = null
      this.token = null
      this.isAuthenticated = false
      
      localStorage.removeItem('auth_token')
    },

    async checkAuth() {
      if (!this.token) {
        return false
      }

      try {
        const response = await axios.get('/auth/user')
        this.user = response.data.user
        this.isAuthenticated = true
        return true
      } catch (error) {
        this.logout()
        return false
      }
    },

    async updateProfile(profileData) {
      try {
        const response = await axios.put('/auth/profile', profileData)
        this.user = response.data.user
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