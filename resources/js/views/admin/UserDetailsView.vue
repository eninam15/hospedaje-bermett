<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2>Detalles de Usuario</h2>
          <router-link to="/admin/users" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Usuarios
          </router-link>
        </div>
        
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i>
          Página en construcción - Detalles de Usuario Admin
        </div>
        
        <div class="card">
          <div class="card-header">
            <h5>Información del Usuario</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <p><strong>ID:</strong> {{ userId }}</p>
                <p><strong>Nombre:</strong> Usuario Test</p>
                <p><strong>Email:</strong> usuario@test.com</p>
                <p><strong>Rol:</strong> <span class="badge bg-primary">Customer</span></p>
              </div>
              <div class="col-md-6">
                <p><strong>Teléfono:</strong> +591 12345678</p>
                <p><strong>Estado:</strong> <span class="badge bg-success">Activo</span></p>
                <p><strong>Registrado:</strong> 16/07/2025</p>
                <p><strong>Última conexión:</strong> 16/07/2025</p>
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
  name: 'UserDetailsView',
  data() {
    return {
      userId: this.$route.params.id
    }
  },
  mounted() {
    console.log('UserDetailsView mounted, ID:', this.userId)
  }
}
</script>