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