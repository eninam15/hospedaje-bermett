<!-- Employee DashboardView.vue -->
<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 mb-4">
        <h2>Dashboard Empleado</h2>
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i>
          Página en construcción - Dashboard Empleado
        </div>
      </div>
    </div>
    
    <!-- Stats Cards Empleado -->
    <div class="row mb-4">
      <div class="col-md-3">
        <div class="card bg-primary text-white">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div>
                <h5>Check-ins Hoy</h5>
                <h3>0</h3>
              </div>
              <div class="align-self-center">
                <i class="fas fa-sign-in-alt fa-2x"></i>
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
                <h5>Check-outs Hoy</h5>
                <h3>0</h3>
              </div>
              <div class="align-self-center">
                <i class="fas fa-sign-out-alt fa-2x"></i>
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
                <h5>Reservas Activas</h5>
                <h3>0</h3>
              </div>
              <div class="align-self-center">
                <i class="fas fa-calendar-check fa-2x"></i>
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
                <h5>Servicios Hoy</h5>
                <h3>0</h3>
              </div>
              <div class="align-self-center">
                <i class="fas fa-concierge-bell fa-2x"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Acciones rápidas -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5>Acciones Rápidas</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-4 mb-3">
                <button class="btn btn-primary btn-lg w-100">
                  <i class="fas fa-user-plus"></i><br>
                  Nuevo Check-in
                </button>
              </div>
              <div class="col-md-4 mb-3">
                <button class="btn btn-success btn-lg w-100">
                  <i class="fas fa-user-minus"></i><br>
                  Procesar Check-out
                </button>
              </div>
              <div class="col-md-4 mb-3">
                <router-link to="/employee/reservations" class="btn btn-info btn-lg w-100">
                  <i class="fas fa-list"></i><br>
                  Ver Reservas
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Reservas de hoy -->
    <div class="row">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5>Check-ins Programados Hoy</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Reserva</th>
                    <th>Cliente</th>
                    <th>Habitación</th>
                    <th>Hora</th>
                    <th>Acción</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td colspan="5" class="text-center">Sin check-ins programados</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h5>Check-outs Programados Hoy</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm">
                <thead>
                  <tr>
                    <th>Registro</th>
                    <th>Cliente</th>
                    <th>Habitación</th>
                    <th>Hora</th>
                    <th>Acción</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td colspan="5" class="text-center">Sin check-outs programados</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'EmployeeDashboard',
  mounted() {
    console.log('EmployeeDashboard mounted')
  }
}
</script>

<!-- Employee ReservationsView.vue -->
<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Gestión de Reservas</h4>
            <button class="btn btn-primary">
              <i class="fas fa-plus"></i> Registro Directo
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
                <select class="form-select">
                  <option>Todos los estados</option>
                  <option>Pendiente</option>
                  <option>Confirmada</option>
                  <option>Check-in</option>
                  <option>Completada</option>
                </select>
              </div>
              <div class="col-md-3">
                <input type="date" class="form-control">
              </div>
              <div class="col-md-4">
                <input type="text" class="form-control" placeholder="Buscar por código o cliente...">
              </div>
              <div class="col-md-2">
                <button class="btn btn-secondary w-100">Buscar</button>
              </div>
            </div>
            
            <!-- Tabla de reservas -->
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>Habitación</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td colspan="7" class="text-center">Sin datos disponibles</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'EmployeeReservations',
  mounted() {
    console.log('EmployeeReservations mounted')
  }
}
</script>