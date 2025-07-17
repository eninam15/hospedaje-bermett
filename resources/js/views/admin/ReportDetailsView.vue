<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2>Detalles del Reporte</h2>
          <router-link to="/admin/reports" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Reportes
          </router-link>
        </div>
        
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i>
          Página en construcción - Detalles del Reporte
        </div>
        
        <div class="card">
          <div class="card-header">
            <h5>Reporte: {{ reportType }}</h5>
          </div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Fecha Inicio:</label>
                <input type="date" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">Fecha Fin:</label>
                <input type="date" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">Sucursal:</label>
                <select class="form-select">
                  <option>Todas</option>
                  <option>Villa Caluyo</option>
                  <option>Cruce Villa Adela</option>
                </select>
              </div>
            </div>
            
            <button class="btn btn-primary">
              <i class="fas fa-chart-bar"></i> Generar Reporte
            </button>
            <button class="btn btn-success ms-2">
              <i class="fas fa-file-excel"></i> Exportar Excel
            </button>
            <button class="btn btn-danger ms-2">
              <i class="fas fa-file-pdf"></i> Exportar PDF
            </button>
            
            <div class="mt-4">
              <h6>Resultados:</h6>
              <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Ejecuta el reporte para ver los resultados
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
  name: 'ReportDetailsView',
  data() {
    return {
      reportType: this.$route.params.type || 'General'
    }
  },
  mounted() {
    console.log('ReportDetailsView mounted, Type:', this.reportType)
  }
}
</script>