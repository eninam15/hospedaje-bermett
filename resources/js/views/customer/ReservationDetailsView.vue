<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2>Detalles de Reserva</h2>
          <router-link to="/customer/reservations" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Mis Reservas
          </router-link>
        </div>
        
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i>
          Página en construcción - Detalles de Reserva Cliente
        </div>
      </div>
    </div>
    
    <div class="row">
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <h5>Información de la Reserva</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <p><strong>Código de Reserva:</strong> RES123456</p>
                <p><strong>Estado:</strong> <span class="badge bg-success">Confirmada</span></p>
                <p><strong>Fecha de Reserva:</strong> 16/07/2025</p>
                <p><strong>Check-in:</strong> 20/07/2025 - 14:00</p>
                <p><strong>Check-out:</strong> 22/07/2025 - 12:00</p>
              </div>
              <div class="col-md-6">
                <p><strong>Habitación:</strong> 101 - Individual</p>
                <p><strong>Sucursal:</strong> Villa Caluyo</p>
                <p><strong>Huéspedes:</strong> 1 Adulto</p>
                <p><strong>Parqueo:</strong> No</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="card mt-4">
          <div class="card-header">
            <h5>Servicios Adicionales</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Servicio</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td colspan="4" class="text-center">Sin servicios adicionales</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            <h5>Resumen de Pago</h5>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <span>Habitación (2 noches):</span>
              <span>S/ 0.00</span>
            </div>
            <div class="d-flex justify-content-between">
              <span>Servicios:</span>
              <span>S/ 0.00</span>
            </div>
            <div class="d-flex justify-content-between">
              <span>Parqueo:</span>
              <span>S/ 0.00</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between fw-bold">
              <span>Total:</span>
              <span>S/ 0.00</span>
            </div>
          </div>
        </div>
        
        <div class="card mt-3">
          <div class="card-header">
            <h5>Acciones</h5>
          </div>
          <div class="card-body">
            <button class="btn btn-primary btn-sm w-100 mb-2">
              <i class="fas fa-download"></i> Descargar Comprobante
            </button>
            <button class="btn btn-warning btn-sm w-100 mb-2">
              <i class="fas fa-edit"></i> Modificar Reserva
            </button>
            <button class="btn btn-danger btn-sm w-100">
              <i class="fas fa-times"></i> Cancelar Reserva
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ReservationDetailsView',
  data() {
    return {
      reservationId: this.$route.params.id
    }
  },
  mounted() {
    console.log('ReservationDetailsView mounted, ID:', this.reservationId)
  }
}
</script>