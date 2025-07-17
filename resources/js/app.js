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


// Crear instancias
const app = createApp(App)
const pinia = createPinia()
const router = createRouter({
    history: createWebHistory(),
    routes
})

// Configurar
app.use(pinia)
app.use(router)
app.use(PrimeVue)
app.use(ToastPlugin)


// Montar aplicación
app.mount('#app')