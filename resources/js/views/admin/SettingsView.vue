<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h4>Configuraciones del Sistema</h4>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <i class="fas fa-info-circle"></i>
              Página en construcción - Configuraciones
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="card">
                  <div class="card-header">
                    <h6>Configuración General</h6>
                  </div>
                  <div class="card-body">
                    <div class="mb-3">
                      <label class="form-label">Nombre del Sistema</label>
                      <input type="text" class="form-control" value="Hostal Bernet">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Moneda</label>
                      <select class="form-select">
                        <option>Bolivianos (Bs.)</option>
                        <option>Soles (S/)</option>
                        <option>Dólares ($)</option>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Zona Horaria</label>
                      <select class="form-select">
                        <option>America/La_Paz</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="card">
                  <div class="card-header">
                    <h6>Configuración de Reservas</h6>
                  </div>
                  <div class="card-body">
                    <div class="mb-3">
                      <label class="form-label">Tiempo límite para check-in (horas)</label>
                      <input type="number" class="form-control" value="24">
                    </div>
                    <div class="mb-3">
                      <label class="form-label">Anticipo mínimo (%)</label>
                      <input type="number" class="form-control" value="50">
                    </div>
                    <div class="mb-3">
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" checked>
                        <label class="form-check-label">
                          Permitir cancelaciones
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="mt-3">
              <button class="btn btn-primary">
                <i class="fas fa-save"></i> Guardar Configuración
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SettingsView',
  mounted() {
    console.log('SettingsView mounted')
  }
}
</script>