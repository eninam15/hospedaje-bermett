<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2>Detalles de Habitación</h2>
          <router-link to="/admin/rooms" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Habitaciones
          </router-link>
        </div>
        
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i>
          Página en construcción - Detalles de Habitación Admin
        </div>
        
        <div class="card">
          <div class="card-header">
            <h5>Información de la Habitación</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <p><strong>ID:</strong> {{ roomId }}</p>
                <p><strong>Número:</strong> 101</p>
                <p><strong>Tipo:</strong> Individual</p>
                <p><strong>Piso:</strong> 1</p>
              </div>
              <div class="col-md-6">
                <p><strong>Precio por noche:</strong> S/ 80.00</p>
                <p><strong>Estado:</strong> <span class="badge bg-success">Disponible</span></p>
                <p><strong>Sucursal:</strong> Villa Caluyo</p>
                <p><strong>Capacidad:</strong> 1 Adulto</p>
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
  name: 'RoomDetailsView',
  data() {
    return {
      roomId: this.$route.params.id
    }
  },
  mounted() {
    console.log('RoomDetailsView mounted, ID:', this.roomId)
  }
}
</script>