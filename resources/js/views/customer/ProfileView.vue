<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <h2>Mi Perfil</h2>
        <div class="alert alert-info">
          <i class="fas fa-info-circle"></i>
          Página en construcción - Perfil de Usuario
        </div>
      </div>
    </div>
    
    <div class="row">
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <h5>Información Personal</h5>
          </div>
          <div class="card-body">
            <form>
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" class="form-control" value="Usuario Test">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" value="usuario@test.com">
                  </div>
                </div>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" class="form-control" value="+591 12345678">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Documento de Identidad</label>
                    <input type="text" class="form-control" value="12345678">
                  </div>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Dirección</label>
                <textarea class="form-control" rows="3">Dirección de ejemplo</textarea>
              </div>
              
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Guardar Cambios
              </button>
            </form>
          </div>
        </div>
        
        <div class="card mt-4">
          <div class="card-header">
            <h5>Cambiar Contraseña</h5>
          </div>
          <div class="card-body">
            <form>
              <div class="mb-3">
                <label class="form-label">Contraseña Actual</label>
                <input type="password" class="form-control">
              </div>
              <div class="mb-3">
                <label class="form-label">Nueva Contraseña</label>
                <input type="password" class="form-control">
              </div>
              <div class="mb-3">
                <label class="form-label">Confirmar Nueva Contraseña</label>
                <input type="password" class="form-control">
              </div>
              <button type="submit" class="btn btn-warning">
                <i class="fas fa-key"></i> Cambiar Contraseña
              </button>
            </form>
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            <h5>Estadísticas</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <div class="d-flex justify-content-between">
                <span>Reservas Totales:</span>
                <span class="badge bg-primary">0</span>
              </div>
            </div>
            <div class="mb-3">
              <div class="d-flex justify-content-between">
                <span>Estadías Completadas:</span>
                <span class="badge bg-success">0</span>
              </div>
            </div>
            <div class="mb-3">
              <div class="d-flex justify-content-between">
                <span>Total Gastado:</span>
                <span class="badge bg-info">S/ 0.00</span>
              </div>
            </div>
            <div class="mb-3">
              <div class="d-flex justify-content-between">
                <span>Miembro desde:</span>
                <span class="badge bg-secondary">2025</span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="card mt-3">
          <div class="card-header">
            <h5>Acciones</h5>
          </div>
          <div class="card-body">
            <button class="btn btn-info btn-sm w-100 mb-2">
              <i class="fas fa-download"></i> Descargar Historial
            </button>
            <button class="btn btn-secondary btn-sm w-100 mb-2">
              <i class="fas fa-bell"></i> Configurar Notificaciones
            </button>
            <button class="btn btn-danger btn-sm w-100">
              <i class="fas fa-user-times"></i> Eliminar Cuenta
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ProfileView',
  mounted() {
    console.log('ProfileView mounted')
  }
}
</script>