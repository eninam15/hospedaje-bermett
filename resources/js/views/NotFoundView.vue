<template>
  <div class="min-vh-100 d-flex align-items-center justify-content-center">
    <div class="text-center">
      <div class="mb-4">
        <i class="pi pi-exclamation-triangle text-warning" style="font-size: 5rem;"></i>
      </div>
      <h1 class="display-1 fw-bold text-primary">404</h1>
      <h3 class="mb-3">Página no encontrada</h3>
      <p class="lead text-muted mb-4">
        La página que buscas no existe o ha sido movida.
      </p>
      <div class="d-flex gap-3 justify-content-center">
        <router-link to="/" class="btn btn-primary">
          <i class="pi pi-home"></i> Ir al Inicio
        </router-link>
        <button @click="$router.go(-1)" class="btn btn-outline-secondary">
          <i class="pi pi-arrow-left"></i> Volver
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'NotFoundView'
}
</script>

<style scoped>
.min-vh-100 {
  min-height: 100vh;
}

.text-primary {
  color: #007bff !important;
}

.text-warning {
  color: #ffc107 !important;
}
</style>