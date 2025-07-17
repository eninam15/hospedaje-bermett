<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Gestión de Reservas - Empleado</h4>
            <button class="btn btn-primary">
              <i class="fas fa-plus"></i> Registro Directo (Walk-in)
            </button>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <i class="fas fa-info-circle"></i>
              Página en construcción - Reservas Empleado
            </div>
            
            <!-- Filtros -->
            <div class="row mb-3">
              <div class="col-md-3">
                <label class="form-label">Estado</label>
                <select class="form-select">
                  <option>Todos los estados</option>
                  <option>Pendiente pago</option>
                  <option>Confirmada</option>
                  <option>Check-in realizado</option>
                  <option>Completada</option>
                  <option>Cancelada</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Fecha</label>
                <input type="date" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">Buscar</label>
                <input type="text" class="form-control" placeholder="Código de reserva o cliente...">
              </div>
              <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-secondary w-100">
                  <i class="fas fa-search"></i> Buscar
                </button>
              </div>
            </div>
            
            <!-- Tabs por acción -->
            <ul class="nav nav-tabs" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#todas">
                  <i class="fas fa-list"></i> Todas las Reservas
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#checkin">
                  <i class="fas fa-sign-in-alt"></i> Check-in Hoy
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#checkout">
                  <i class="fas fa-sign-out-alt"></i> Check-out Hoy
                </a>
              </li>
            </ul>
            
            <div class="tab-content mt-3">
              <!-- Todas las reservas -->
              <div class="tab-pane fade show active" id="todas">
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Habitación</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Huéspedes</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td colspan="9" class="text-center py-4">
                          <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                          <p class="text-muted">No hay reservas disponibles</p>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              
              <!-- Check-in programados -->
              <div class="tab-pane fade" id="checkin">
                <div class="alert alert-success">
                  <i class="fas fa-info-circle"></i>
                  Reservas programadas para check-in hoy
                </div>
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Reserva</th>
                        <th>Cliente</th>
                        <th>Habitación</th>
                        <th>Hora Programada</th>
                        <th>Teléfono</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td colspan="6" class="text-center py-4">
                          <i class="fas fa-user-check fa-3x text-muted mb-3"></i>
                          <p class="text-muted">No hay check-ins programados para hoy</p>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              
              <!-- Check-out programados -->
              <div class="tab-pane fade" id="checkout">
                <div class="alert alert-warning">
                  <i class="fas fa-info-circle"></i>
                  Registros programados para check-out hoy
                </div>
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Registro</th>
                        <th>Cliente</th>
                        <th>Habitación</th>
                        <th>Hora Programada</th>
                        <th>Servicios Consumidos</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td colspan="6" class="text-center py-4">
                          <i class="fas fa-user-minus fa-3x text-muted mb-3"></i>
                          <p class="text-muted">No hay check-outs programados para hoy</p>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Estadísticas rápidas -->
    <div class="row mt-4">
      <div class="col-md-3">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h6>Reservas Hoy</h6>
                <h4>0</h4>
              </div>
              <div class="align-self-center">
                <i class="fas fa-calendar-day fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="card bg-success text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h6>Check-ins Realizados</h6>
                <h4>0</h4>
              </div>
              <div class="align-self-center">
                <i class="fas fa-sign-in-alt fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="card bg-warning text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h6>Check-outs Realizados</h6>
                <h4>0</h4>
              </div>
              <div class="align-self-center">
                <i class="fas fa-sign-out-alt fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-3">
        <div class="card bg-info text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h6>Habitaciones Ocupadas</h6>
                <h4>0</h4>
              </div>
              <div class="align-self-center">
                <i class="fas fa-bed fa-2x"></i>
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
  name: 'EmployeeReservationsView',
  data() {
    return {
      activeTab: 'todas'
    }
  },
  mounted() {
    console.log('EmployeeReservationsView mounted')
  }
}
</script>

<style scoped>
.nav-tabs .nav-link {
  color: #495057;
}

.nav-tabs .nav-link.active {
  color: #007bff;
  border-bottom-color: #007bff;
}

.card {
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.table th {
  background-color: #f8f9fa;
  font-weight: 600;
}
</style>