<template>
  <div>
    <!-- Hero Section -->
    <section class="hero-section bg-primary text-white py-5">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <h1 class="display-4 fw-bold mb-4">
              Hospedaje Bermett
            </h1>
            <p class="lead mb-4">
              Tu hogar en El Alto, Bolivia. Disfruta de habitaciones cómodas 
              y servicios de calidad en nuestras dos sucursales.
            </p>
            <router-link to="/search" class="btn btn-light btn-lg">
              Buscar Habitaciones
            </router-link>
          </div>
          <div class="col-lg-6">
            <div class="text-center">
              <i class="pi pi-building" style="font-size: 8rem; opacity: 0.8;"></i>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Quick Search -->
    <section class="py-5">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-lg-10">
            <div class="card shadow">
              <div class="card-body p-4">
                <h4 class="text-center mb-4">Búsqueda Rápida</h4>
                <form @submit.prevent="searchRooms">
                  <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                      <label class="form-label">Sucursal</label>
                      <select v-model="searchForm.branch_id" class="form-select">
                        <option value="">Todas</option>
                        <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                          {{ branch.name }}
                        </option>
                      </select>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Check-in</label>
                      <input v-model="searchForm.check_in_date" type="date" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Check-out</label>
                      <input v-model="searchForm.check_out_date" type="date" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                      <button type="submit" class="btn btn-primary w-100">
                        <i class="pi pi-search"></i> Buscar
                      </button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Sucursales -->
    <section class="py-5 bg-light">
      <div class="container">
        <h2 class="text-center mb-5">Nuestras Sucursales</h2>
        <div class="row">
          <div v-for="branch in branches" :key="branch.id" class="col-lg-6 mb-4">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <h5 class="card-title">{{ branch.name }}</h5>
                  <span class="badge bg-primary">{{ branch.category }}</span>
                </div>
                <p class="card-text">{{ branch.description }}</p>
                <div class="mb-3">
                  <p class="mb-1"><i class="pi pi-map-marker text-primary"></i> {{ branch.address }}</p>
                  <p class="mb-1"><i class="pi pi-phone text-primary"></i> {{ branch.phone }}</p>
                  <p class="mb-0"><i class="pi pi-home text-primary"></i> {{ branch.available_rooms }} habitaciones disponibles</p>
                </div>
                <router-link :to="`/search?branch_id=${branch.id}`" class="btn btn-outline-primary">
                  Ver Habitaciones
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Características -->
    <section class="py-5">
      <div class="container">
        <h2 class="text-center mb-5">¿Por qué elegirnos?</h2>
        <div class="row">
          <div class="col-md-4 mb-4">
            <div class="text-center">
              <i class="pi pi-shield text-primary mb-3" style="font-size: 3rem;"></i>
              <h5>Seguridad</h5>
              <p class="text-muted">Instalaciones seguras con personal las 24 horas</p>
            </div>
          </div>
          <div class="col-md-4 mb-4">
            <div class="text-center">
              <i class="pi pi-heart text-primary mb-3" style="font-size: 3rem;"></i>
              <h5>Comodidad</h5>
              <p class="text-muted">Habitaciones cómodas con todas las comodidades</p>
            </div>
          </div>
          <div class="col-md-4 mb-4">
            <div class="text-center">
              <i class="pi pi-dollar text-primary mb-3" style="font-size: 3rem;"></i>
              <h5>Precios Justos</h5>
              <p class="text-muted">Tarifas competitivas y transparentes</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { publicApi } from '../../services/api.js'

export default {
  name: 'HomeView',
  setup() {
    const router = useRouter()
    const branches = ref([])
    const loading = ref(false)
    
    const searchForm = ref({
      branch_id: '',
      check_in_date: '',
      check_out_date: ''
    })

    const loadBranches = async () => {
      try {
        loading.value = true
        const response = await publicApi.getBranches()
        branches.value = response.data.branches
      } catch (error) {
        console.error('Error loading branches:', error)
      } finally {
        loading.value = false
      }
    }

    const searchRooms = () => {
      const queryParams = new URLSearchParams()
      
      if (searchForm.value.branch_id) {
        queryParams.append('branch_id', searchForm.value.branch_id)
      }
      if (searchForm.value.check_in_date) {
        queryParams.append('check_in_date', searchForm.value.check_in_date)
      }
      if (searchForm.value.check_out_date) {
        queryParams.append('check_out_date', searchForm.value.check_out_date)
      }
      
      router.push(`/search?${queryParams.toString()}`)
    }

    // Configurar fechas mínimas
    const today = new Date().toISOString().split('T')[0]
    const tomorrow = new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString().split('T')[0]
    
    searchForm.value.check_in_date = today
    searchForm.value.check_out_date = tomorrow

    onMounted(() => {
      loadBranches()
    })

    return {
      branches,
      searchForm,
      searchRooms,
      loading
    }
  }
}
</script>

<style scoped>
.hero-section {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.card {
  transition: transform 0.2s;
}

.card:hover {
  transform: translateY(-5px);
}

.pi {
  font-size: 1.2rem;
}

.text-primary {
  color: #007bff !important;
}
</style>