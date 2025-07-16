<template>
  <div class="container py-4">
    <div class="row">
      <div class="col-12">
        <h2 class="mb-4">Buscar Habitaciones</h2>
        
        <!-- Filtros de búsqueda -->
        <div class="card mb-4">
          <div class="card-body">
            <form @submit.prevent="searchRooms">
              <div class="row g-3">
                <div class="col-md-2">
                  <label class="form-label">Sucursal</label>
                  <select v-model="filters.branch_id" class="form-select">
                    <option value="">Todas</option>
                    <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                      {{ branch.name }}
                    </option>
                  </select>
                </div>
                
                <div class="col-md-2">
                  <label class="form-label">Check-in</label>
                  <input v-model="filters.check_in_date" type="date" class="form-control" required>
                </div>
                
                <div class="col-md-2">
                  <label class="form-label">Check-out</label>
                  <input v-model="filters.check_out_date" type="date" class="form-control" required>
                </div>
                
                <div class="col-md-2">
                  <label class="form-label">Adultos</label>
                  <select v-model="filters.adults" class="form-select">
                    <option v-for="n in 6" :key="n" :value="n">{{ n }}</option>
                  </select>
                </div>
                
                <div class="col-md-2">
                  <label class="form-label">Niños</label>
                  <select v-model="filters.children" class="form-select">
                    <option v-for="n in 4" :key="n-1" :value="n-1">{{ n-1 }}</option>
                  </select>
                </div>
                
                <div class="col-md-2">
                  <label class="form-label">&nbsp;</label>
                  <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                    <i class="pi pi-search"></i> Buscar
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Resultados -->
        <div v-if="loading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
        </div>

        <div v-else-if="availableRooms.length > 0">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>{{ availableRooms.length }} habitaciones disponibles</h5>
            <small class="text-muted" v-if="searchParams.total_nights">
              Para {{ searchParams.total_nights }} noche{{ searchParams.total_nights > 1 ? 's' : '' }}
            </small>
          </div>

          <div class="row">
            <div v-for="room in availableRooms" :key="room.id" class="col-lg-6 col-xl-4 mb-4">
              <div class="card h-100 room-card">
                <img :src="room.main_photo" class="card-img-top" alt="Habitación" style="height: 200px; object-fit: cover;">
                
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="card-title mb-0">
                      {{ room.room_type.name }} - {{ room.room_number }}
                    </h6>
                    <span class="badge bg-primary">{{ room.branch.category }}</span>
                  </div>
                  
                  <p class="text-muted small mb-2">
                    <i class="pi pi-map-marker"></i> {{ room.branch.name }}
                  </p>
                  
                  <p class="card-text">{{ room.description }}</p>
                  
                  <div class="mb-3">
                    <small class="text-muted">Comodidades:</small>
                    <div class="mt-1">
                      <span v-for="amenity in room.amenities" :key="amenity" class="badge bg-light text-dark me-1">
                        {{ amenity }}
                      </span>
                    </div>
                  </div>
                  
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <small class="text-muted">Por noche:</small>
                      <div class="fw-bold text-primary">{{ room.formatted_price }}</div>
                      <div v-if="room.room_total" class="fw-bold text-success">
                        Total: {{ room.formatted_room_total }}
                      </div>
                    </div>
                    <button @click="selectRoom(room)" class="btn btn-success">
                      Reservar
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else-if="!loading && searchExecuted" class="text-center py-5">
          <i class="pi pi-search" style="font-size: 3rem; color: #6c757d;"></i>
          <h5 class="mt-3 text-muted">No se encontraron habitaciones disponibles</h5>
          <p class="text-muted">Intenta con otras fechas o sucursal</p>
        </div>

        <div v-else class="text-center py-5">
          <i class="pi pi-search" style="font-size: 3rem; color: #6c757d;"></i>
          <h5 class="mt-3 text-muted">Busca habitaciones disponibles</h5>
          <p class="text-muted">Selecciona tus fechas y realiza una búsqueda</p>
        </div>
      </div>
    </div>

    <!-- Modal de reserva -->
    <div v-if="selectedRoom" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Reservar Habitación</h5>
            <button type="button" class="btn-close" @click="selectedRoom = null"></button>
          </div>
          <div class="modal-body">
            <ReservationForm 
              :room="selectedRoom" 
              :search-params="searchParams"
              @success="onReservationSuccess"
              @cancel="selectedRoom = null"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { publicApi } from '../../services/api.js'
import ReservationForm from '../../components/public/ReservationForm.vue'

export default {
  name: 'SearchView',
  components: {
    ReservationForm
  },
  setup() {
    const route = useRoute()
    const router = useRouter()
    
    const branches = ref([])
    const availableRooms = ref([])
    const selectedRoom = ref(null)
    const loading = ref(false)
    const searchExecuted = ref(false)
    const searchParams = ref({})

    const filters = ref({
      branch_id: '',
      check_in_date: '',
      check_out_date: '',
      adults: 1,
      children: 0
    })

    const loadBranches = async () => {
      try {
        const response = await publicApi.getBranches()
        branches.value = response.data.branches
      } catch (error) {
        console.error('Error loading branches:', error)
      }
    }

    const searchRooms = async () => {
      try {
        loading.value = true
        searchExecuted.value = true
        
        const response = await publicApi.checkAvailability(filters.value)
        availableRooms.value = response.data.available_rooms
        searchParams.value = response.data.search_params
      } catch (error) {
        console.error('Error searching rooms:', error)
        availableRooms.value = []
      } finally {
        loading.value = false
      }
    }

    const selectRoom = (room) => {
      selectedRoom.value = room
    }

    const onReservationSuccess = () => {
      selectedRoom.value = null
      router.push('/customer/reservations')
    }

    // Configurar fechas por defecto
    const today = new Date().toISOString().split('T')[0]
    const tomorrow = new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString().split('T')[0]
    
    filters.value.check_in_date = today
    filters.value.check_out_date = tomorrow

    // Cargar parámetros desde query
    onMounted(() => {
      loadBranches()
      
      // Cargar filtros desde query params
      if (route.query.branch_id) {
        filters.value.branch_id = route.query.branch_id
      }
      if (route.query.check_in_date) {
        filters.value.check_in_date = route.query.check_in_date
      }
      if (route.query.check_out_date) {
        filters.value.check_out_date = route.query.check_out_date
      }
      if (route.query.adults) {
        filters.value.adults = parseInt(route.query.adults)
      }
      if (route.query.children) {
        filters.value.children = parseInt(route.query.children)
      }
      
      // Buscar automáticamente si hay parámetros
      if (route.query.check_in_date && route.query.check_out_date) {
        searchRooms()
      }
    })

    return {
      branches,
      availableRooms,
      selectedRoom,
      loading,
      searchExecuted,
      searchParams,
      filters,
      searchRooms,
      selectRoom,
      onReservationSuccess
    }
  }
}
</script>

<style scoped>
.room-card {
  transition: transform 0.2s, box-shadow 0.2s;
}

.room-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.modal.show {
  display: block !important;
}

.btn-success {
  background-color: #28a745;
  border-color: #28a745;
}

.btn-success:hover {
  background-color: #218838;
  border-color: #1e7e34;
}

.text-primary {
  color: #007bff !important;
}
</style>