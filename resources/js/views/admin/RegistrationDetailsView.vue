<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2>Detalles de Registro</h2>
          <router-link to="/admin/registrations" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Registros
          </router-link>
        </div>
        
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i>
          Página en construcción - Detalles de Registro
        </div>
        
        <div class="card">
          <div class="card-header">
            <h5>Información del Registro</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <p><strong>ID:</strong> {{ registrationId }}</p>
                <p><strong>Código:</strong> REG123456</p>
                <p><strong>Reserva:</strong> RES123456</p>
                <p><strong>Cliente:</strong> Usuario Test</p>
              </div>
              <div class="col-md-6">
                <p><strong>Habitación:</strong> 101</p>
                <p><strong>Check-in:</strong> 20/07/2025 14:30</p>
                <p><strong>Check-out:</strong> 22/07/2025 11:45</p>
                <p><strong>Estado:</strong> <span class="badge bg-success">Activo</span></p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'RegistrationDetailsView',
  data() {
    return {
      registrationId: this.$route.params.id
    }
  },
  mounted() {
    console.log('RegistrationDetailsView mounted, ID:', this.registrationId)
  }
}
</script>