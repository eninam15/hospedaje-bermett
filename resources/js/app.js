import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.bundle.min.js'
import 'primeicons/primeicons.css'
import './bootstrap'

import App from './App.vue'
import routes from './router/index.js'
import ToastPlugin from './plugins/toast.js'
import { useAuthStore } from './stores/auth'

// Crear app y configurar plugins
const app = createApp(App)
const pinia = createPinia()
app.use(pinia)

const router = createRouter({
  history: createWebHistory(),
  routes
})

app.use(router)
app.use(PrimeVue)
app.use(ToastPlugin)

// Inicializar autenticación antes de montar
const initApp = async () => {
  const auth = useAuthStore()
  
  try {
    await auth.checkAuth()
  } catch (error) {
    console.error('Error al inicializar autenticación:', error)
  } finally {
    app.mount('#app')
  }
}

initApp()