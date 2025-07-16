<template>
  <footer class="bg-dark text-light py-4 mt-5">
    <div class="container">
      <div class="row">
        <div class="col-lg-4 col-md-6 mb-4">
          <h5 class="text-primary">🏨 Hospedaje Bermett</h5>
          <p class="text-muted">
            Hospedaje cómodo y seguro en El Alto, Bolivia. 
            Dos sucursales para brindarte la mejor experiencia.
          </p>
        </div>
        
        <div class="col-lg-4 col-md-6 mb-4">
          <h6>Nuestras Sucursales</h6>
          <div class="mb-3">
            <strong>Villa Caluyo (3 ⭐)</strong>
            <p class="text-muted small mb-1">Villa Caluyo D #784 Entre Calle 5 y 6</p>
            <p class="text-muted small">📞 +591 71234567</p>
          </div>
          <div>
            <strong>Cruce Villa Adela (4 ⭐)</strong>
            <p class="text-muted small mb-1">Av. Ladislao Cabrera, calle 1 N.º 2094</p>
            <p class="text-muted small">📞 +591 72345678</p>
          </div>
        </div>
        
        <div class="col-lg-4 col-md-12 mb-4">
          <h6>Información</h6>
          <ul class="list-unstyled">
            <li><a href="#" class="text-muted text-decoration-none">Política de Privacidad</a></li>
            <li><a href="#" class="text-muted text-decoration-none">Términos y Condiciones</a></li>
            <li><a href="#" class="text-muted text-decoration-none">Políticas de Cancelación</a></li>
            <li><a href="#" class="text-muted text-decoration-none">Contacto</a></li>
          </ul>
        </div>
      </div>
      
      <hr class="border-secondary">
      
      <div class="row align-items-center">
        <div class="col-md-6">
          <p class="text-muted mb-0">
            © 2025 Hospedaje Bermett. Todos los derechos reservados.
          </p>
        </div>
        <div class="col-md-6 text-md-end">
          <p class="text-muted mb-0">
            🇧🇴 El Alto, Bolivia
          </p>
        </div>
      </div>
    </div>
  </footer>
</template>

<script>
export default {
  name: 'AppFooter'
}
</script>

<style scoped>
footer {
  margin-top: auto;
}

.text-primary {
  color: #007bff !important;
}

.list-unstyled a:hover {
  color: #fff !important;
}
</style>