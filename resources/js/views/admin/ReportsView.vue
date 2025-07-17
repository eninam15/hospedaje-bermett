<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h4>Reportes y Estadísticas</h4>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <i class="fas fa-info-circle"></i>
              Página en construcción - Sistema de Reportes
            </div>
            
            <!-- Tipos de reportes -->
            <div class="row">
              <div class="col-md-6 col-lg-4 mb-3">
                <div class="card">
                  <div class="card-body">
                    <h5 class="card-title">
                      <i class="fas fa-calendar-alt"></i> Reporte de Reservas
                    </h5>
                    <p class="card-text">Análisis de reservas por período</p>
                    <button class="btn btn-primary btn-sm">Generar</button>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6 col-lg-4 mb-3">
                <div class="card">
                  <div class="card-body">
                    <h5 class="card-title">
                      <i class="fas fa-money-bill"></i> Reporte de Ingresos
                    </h5>
                    <p class="card-text">Análisis de ingresos por período</p>
                    <button class="btn btn-primary btn-sm">Generar</button>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6 col-lg-4 mb-3">
                <div class="card">
                  <div class="card-body">
                    <h5 class="card-title">
                      <i class="fas fa-chart-bar"></i> Reporte de Ocupación
                    </h5>
                    <p class="card-text">Análisis de ocupación por habitación</p>
                    <button class="btn btn-primary btn-sm">Generar</button>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6 col-lg-4 mb-3">
                <div class="card">
                  <div class="card-body">
                    <h5 class="card-title">
                      <i class="fas fa-users"></i> Reporte de Usuarios
                    </h5>
                    <p class="card-text">Análisis de usuarios registrados</p>
                    <button class="btn btn-primary btn-sm">Generar</button>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6 col-lg-4 mb-3">
                <div class="card">
                  <div class="card-body">
                    <h5 class="card-title">
                      <i class="fas fa-concierge-bell"></i> Reporte de Servicios
                    </h5>
                    <p class="card-text">Análisis de consumo de servicios</p>
                    <button class="btn btn-primary btn-sm">Generar</button>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6 col-lg-4 mb-3">
                <div class="card">
                  <div class="card-body">
                    <h5 class="card-title">
                      <i class="fas fa-times-circle"></i> Reporte de Cancelaciones
                    </h5>
                    <p class="card-text">Análisis de cancelaciones</p>
                    <button class="btn btn-primary btn-sm">Generar</button>
                  </div>
                </div>
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
  name: 'ReportsView',
  data() {
    return {
      // Datos temporales
    }
  },
  mounted() {
    console.log('ReportsView mounted')
  }
}
</script>