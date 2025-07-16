<template>
  <div id="app">
    <AppHeader />
    <main class="main-content">
      <router-view />
    </main>
    <AppFooter />
  </div>
</template>

<script>
import AppHeader from './components/common/AppHeader.vue'
import AppFooter from './components/common/AppFooter.vue'
import { useAuthStore } from './stores/auth.js'

export default {
  name: 'App',
  components: {
    AppHeader,
    AppFooter
  },
  async created() {
    const authStore = useAuthStore()
    await authStore.checkAuth()
  }
}
</script>

<style>
body {
  margin: 0;
  padding: 0;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background-color: #f8f9fa;
}

.main-content {
  min-height: calc(100vh - 200px);
}

/* Utilidades globales */
.text-primary {
  color: #007bff !important;
}

.bg-primary {
  background-color: #007bff !important;
}

.btn-primary {
  background-color: #007bff;
  border-color: #007bff;
}

.btn-primary:hover {
  background-color: #0056b3;
  border-color: #0056b3;
}

/* Estilos para Bolivia */
.text-bolivia {
  color: #28a745 !important;
}

.bg-bolivia {
  background-color: #28a745 !important;
}

/* Loading spinner */
.loading-spinner {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem;
}

/* Responsive utilities */
@media (max-width: 768px) {
  .main-content {
    min-height: calc(100vh - 160px);
  }
}
</style>