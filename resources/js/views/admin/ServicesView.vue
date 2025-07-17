<!-- UserDetailsView.vue -->
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

<!-- RoomDetailsView.vue -->
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

<!-- RoomTypesView.vue -->
<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Tipos de Habitación</h4>
            <button class="btn btn-primary">
              <i class="fas fa-plus"></i> Nuevo Tipo
            </button>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <i class="fas fa-info-circle"></i>
              Página en construcción - Tipos de Habitación
            </div>
            
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Max Adultos</th>
                    <th>Max Niños</th>
                    <th>Precio Base</th>
                    <th>Acciones</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>1</td>
                    <td>Individual</td>
                    <td>Habitación para 1 persona</td>
                    <td>1</td>
                    <td>0</td>
                    <td>S/ 80.00</td>
                    <td>
                      <button class="btn btn-sm btn-primary">Editar</button>
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
</template>

<script>
export default {
  name: 'RoomTypesView',
  mounted() {
    console.log('RoomTypesView mounted')
  }
}
</script>

<!-- ServicesView.vue -->
<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Servicios</h4>
            <button class="btn btn-primary">
              <i class="fas fa-plus"></i> Nuevo Servicio
            </button>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <i class="fas fa-info-circle"></i>
              Página en construcción - Gestión de Servicios
            </div>
            
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Sucursal</th>
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
  name: 'ServicesView',
  mounted() {
    console.log('ServicesView mounted')
  }
}
</script>