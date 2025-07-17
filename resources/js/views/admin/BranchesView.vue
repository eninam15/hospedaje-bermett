<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Sucursales</h4>
            <button class="btn btn-primary">
              <i class="fas fa-plus"></i> Nueva Sucursal
            </button>
          </div>
          <div class="card-body">
            <div class="alert alert-info">
              <i class="fas fa-info-circle"></i>
              Página en construcción - Gestión de Sucursales
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="card">
                  <div class="card-body">
                    <h5>Villa Caluyo</h5>
                    <p><strong>Dirección:</strong> Villa Caluyo D #784 Entre Calle 5 y 6, El Alto</p>
                    <p><strong>Teléfono:</strong> +591 71234567</p>
                    <p><strong>Estrellas:</strong> 3</p>
                    <p><strong>Check-in:</strong> 14:00 | <strong>Check-out:</strong> 12:00</p>
                    <button class="btn btn-sm btn-primary">Editar</button>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="card">
                  <div class="card-body">
                    <h5>Cruce Villa Adela</h5>
                    <p><strong>Dirección:</strong> Zona Cruce Villa Adela, Av. Ladislao Cabrera, calle 1 N.º 2094, El Alto</p>
                    <p><strong>Teléfono:</strong> +591 72345678</p>
                    <p><strong>Estrellas:</strong> 4</p>
                    <p><strong>Check-in:</strong> 14:00 | <strong>Check-out:</strong> 12:00</p>
                    <button class="btn btn-sm btn-primary">Editar</button>
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
  name: 'BranchesView',
  mounted() {
    console.log('BranchesView mounted')
  }
}
</script>