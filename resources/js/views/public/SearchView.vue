<template>
  <div class="search-rooms py-4">
    <div class="container-xl">
      <!-- Header Section -->
      <div class="page-header mb-4">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h2 class="mb-2">
              <i class="fas fa-search me-2"></i>
              Buscar Habitaciones
            </h2>
            <p class="text-muted mb-0">Encuentra la habitación perfecta para tu estadía</p>
          </div>
          <div class="col-md-4 text-md-end">
            <div class="search-stats" v-if="searchExecuted">
              <span class="badge bg-primary fs-6">
                {{ availableRooms.length }} habitaciones encontradas
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Filtros de búsqueda -->
      <div class="search-filters mb-4">
        <div class="card">
          <div class="card-body">
            <form @submit.prevent="searchRooms">
              <div class="row g-3">
                <div class="col-lg-2 col-md-6">
                  <label class="form-label">
                    <i class="fas fa-building me-1"></i>
                    Sucursal
                  </label>
                  <select v-model="filters.branch_id" class="form-select">
                    <option value="">Todas las sucursales</option>
                    <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                      {{ branch.name }}
                    </option>
                  </select>
                </div>
                
                <div class="col-lg-2 col-md-6">
                  <label class="form-label">
                    <i class="fas fa-calendar-check me-1"></i>
                    Check-in
                  </label>
                  <input 
                    v-model="filters.check_in_date" 
                    type="date" 
                    class="form-control" 
                    required
                    :min="today"
                  >
                </div>
                
                <div class="col-lg-2 col-md-6">
                  <label class="form-label">
                    <i class="fas fa-calendar-times me-1"></i>
                    Check-out
                  </label>
                  <input 
                    v-model="filters.check_out_date" 
                    type="date" 
                    class="form-control" 
                    required
                    :min="filters.check_in_date"
                  >
                </div>
                
                <div class="col-lg-2 col-md-6">
                  <label class="form-label">
                    <i class="fas fa-user me-1"></i>
                    Adultos
                  </label>
                  <select v-model="filters.adults" class="form-select">
                    <option v-for="n in 6" :key="n" :value="n">{{ n }} adulto{{ n > 1 ? 's' : '' }}</option>
                  </select>
                </div>
                
                <div class="col-lg-2 col-md-6">
                  <label class="form-label">
                    <i class="fas fa-child me-1"></i>
                    Niños
                  </label>
                  <select v-model="filters.children" class="form-select">
                    <option v-for="n in 5" :key="n-1" :value="n-1">{{ n-1 }} niño{{ (n-1) !== 1 ? 's' : '' }}</option>
                  </select>
                </div>
                
                <div class="col-lg-2 col-md-12">
                  <label class="form-label d-none d-lg-block">&nbsp;</label>
                  <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                    <span v-if="loading">
                      <i class="fas fa-spinner fa-spin me-2"></i>
                      Buscando...
                    </span>
                    <span v-else>
                      <i class="fas fa-search me-2"></i>
                      Buscar
                    </span>
                  </button>
                </div>
              </div>
              
              <!-- Search Summary -->
              <div v-if="searchParams.total_nights" class="search-summary mt-3 p-3 bg-light rounded">
                <div class="row align-items-center">
                  <div class="col-md-8">
                    <div class="d-flex flex-wrap gap-3">
                      <div class="summary-item">
                        <i class="fas fa-moon text-primary me-1"></i>
                        <strong>{{ searchParams.total_nights }}</strong> noche{{ searchParams.total_nights > 1 ? 's' : '' }}
                      </div>
                      <div class="summary-item">
                        <i class="fas fa-users text-primary me-1"></i>
                        <strong>{{ filters.adults }}</strong> adulto{{ filters.adults > 1 ? 's' : '' }}
                        {{ filters.children > 0 ? `, ${filters.children} niño${filters.children > 1 ? 's' : ''}` : '' }}
                      </div>
                      <div v-if="selectedBranch" class="summary-item">
                        <i class="fas fa-map-marker-alt text-primary me-1"></i>
                        {{ selectedBranch.name }}
                      </div>
                    </div>
                  </div>
                  <div class="col-md-4 text-md-end">
                    <button @click="clearSearch" class="btn btn-outline-secondary btn-sm">
                      <i class="fas fa-times me-1"></i>
                      Limpiar búsqueda
                    </button>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Resultados -->
      <div class="search-results">
        <!-- Loading State -->
        <div v-if="loading" class="loading-state text-center py-5">
          <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Cargando...</span>
          </div>
          <h5 class="text-muted">Buscando habitaciones disponibles...</h5>
          <p class="text-muted">Esto puede tomar unos segundos</p>
        </div>

        <!-- Results Header -->
        <div v-else-if="availableRooms.length > 0" class="results-section">
          <div class="results-header mb-4">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h4 class="mb-2">
                  <i class="fas fa-home me-2 text-success"></i>
                  {{ availableRooms.length }} habitación{{ availableRooms.length > 1 ? 'es' : '' }} 
                  {{ searchParams.total_nights ? 'disponible' + (availableRooms.length > 1 ? 's' : '') : 'en total' }}
                </h4>
                <p class="text-muted mb-0" v-if="searchParams.total_nights">
                  Para tu estadía de {{ searchParams.total_nights }} noche{{ searchParams.total_nights > 1 ? 's' : '' }}
                </p>
                <p class="text-muted mb-0" v-else>
                  Mostrando todas las habitaciones disponibles
                </p>
              </div>
              <div class="col-md-4 text-md-end">
                <div class="sort-controls">
                  <select v-model="sortBy" @change="sortRooms" class="form-select form-select-sm">
                    <option value="price_asc">Precio: Menor a mayor</option>
                    <option value="price_desc">Precio: Mayor a menor</option>
                    <option value="capacity">Capacidad</option>
                    <option value="name">Nombre</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Rooms Grid -->
          <div class="rooms-grid">
            <div class="row g-4">
              <div v-for="room in sortedRooms" :key="room.id" class="col-lg-6 col-xl-4">
                <div class="room-card">
                  <div class="card h-100">
                    <!-- Room Image -->
                    <div class="room-image-container">
                      <img 
                        :src="getRoomImage(room)" 
                        class="card-img-top room-image" 
                        :alt="'Habitación ' + room.room_number"
                      >
                      <div class="room-overlay">
                        <div class="room-number-badge">
                          {{ room.room_number }}
                        </div>
                        <div class="room-category-badge">
                          <span class="badge bg-primary">{{ room.branch.category }}</span>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Room Info -->
                    <div class="card-body">
                      <!-- Header -->
                      <div class="room-header mb-3">
                        <h6 class="room-title mb-1">
                          {{ room.room_type.name }}
                        </h6>
                        <p class="room-location text-muted mb-0">
                          <i class="fas fa-map-marker-alt me-1"></i>
                          {{ room.branch.name }}
                        </p>
                      </div>
                      
                      <!-- Description -->
                      <p class="room-description text-muted">
                        {{ room.description || room.room_type.description }}
                      </p>
                      
                      <!-- Capacity -->
                      <div class="room-capacity mb-3">
                        <div class="capacity-info">
                          <i class="fas fa-users text-primary me-2"></i>
                          <span class="fw-medium">
                            Hasta {{ room.room_type.max_guests }} huésped{{ room.room_type.max_guests > 1 ? 'es' : '' }}
                          </span>
                          <small class="text-muted">
                            ({{ room.room_type.max_adults }} adulto{{ room.room_type.max_adults > 1 ? 's' : '' }}{{ room.room_type.max_children > 0 ? `, ${room.room_type.max_children} niño${room.room_type.max_children > 1 ? 's' : ''}` : '' }})
                          </small>
                        </div>
                      </div>
                      
                      <!-- Amenities -->
                      <div class="room-amenities mb-3">
                        <div class="amenities-title mb-2">
                          <small class="text-muted fw-medium">
                            <i class="fas fa-star text-warning me-1"></i>
                            Comodidades incluidas:
                          </small>
                        </div>
                        <div class="amenities-list">
                          <!-- Room specific amenities -->
                          <span 
                            v-for="amenity in room.amenities.slice(0, 3)" 
                            :key="'room-' + amenity" 
                            class="badge bg-light text-dark me-1 mb-1"
                          >
                            {{ amenity }}
                          </span>
                          <!-- Room type amenities -->
                          <span 
                            v-for="amenity in room.room_type.amenities.slice(0, 2)" 
                            :key="'type-' + amenity" 
                            class="badge bg-light text-dark me-1 mb-1"
                          >
                            {{ amenity }}
                          </span>
                          <span 
                            v-if="getTotalAmenities(room) > 5" 
                            class="badge bg-secondary"
                          >
                            +{{ getTotalAmenities(room) - 5 }} más
                          </span>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Room Footer -->
                    <div class="card-footer bg-white border-0 pt-0">
                      <div class="d-flex justify-content-between align-items-center">
                        <!-- Price -->
                        <div class="price-section">
                          <div class="price-per-night">
                            <small class="text-muted">Por noche:</small>
                            <div class="price-amount">{{ room.formatted_price }}</div>
                          </div>
                          <div v-if="room.room_total && searchParams.total_nights > 1" class="total-price">
                            <small class="text-success fw-medium">
                              Total: {{ room.formatted_room_total }}
                            </small>
                          </div>
                        </div>
                        
                        <!-- Action Button -->
                        <div class="action-section">
                          <button 
                            @click="selectRoom(room)" 
                            class="btn btn-success btn-reserve"
                          >
                            <i class="fas fa-calendar-check me-1"></i>
                            Reservar
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- No Results State -->
        <div v-else-if="!loading && searchExecuted" class="no-results-state text-center py-5">
          <div class="no-results-icon mb-4">
            <i class="fas fa-search text-muted" style="font-size: 4rem; opacity: 0.5;"></i>
          </div>
          <h4 class="text-muted mb-3">No se encontraron habitaciones disponibles</h4>
          <p class="text-muted mb-4">
            No hay habitaciones que coincidan con tus criterios de búsqueda para las fechas seleccionadas.
          </p>
          <div class="suggestions">
            <h6 class="text-muted mb-3">Sugerencias:</h6>
            <ul class="list-unstyled text-muted">
              <li class="mb-2">
                <i class="fas fa-lightbulb text-warning me-2"></i>
                Intenta con fechas diferentes
              </li>
              <li class="mb-2">
                <i class="fas fa-lightbulb text-warning me-2"></i>
                Prueba con otra sucursal
              </li>
              <li class="mb-2">
                <i class="fas fa-lightbulb text-warning me-2"></i>
                Reduce el número de huéspedes
              </li>
            </ul>
          </div>
          <button @click="clearSearch" class="btn btn-outline-primary">
            <i class="fas fa-redo me-2"></i>
            Nueva búsqueda
          </button>
        </div>

        <!-- Initial State -->
        <div v-else-if="!loading && availableRooms.length === 0" class="initial-state text-center py-5">
          <div class="initial-icon mb-4">
            <i class="fas fa-bed text-primary" style="font-size: 4rem; opacity: 0.7;"></i>
          </div>
          <h4 class="text-muted mb-3">No hay habitaciones disponibles</h4>
          <p class="text-muted mb-4">
            Actualmente no hay habitaciones disponibles en el sistema.
          </p>
          <button @click="loadAllRooms" class="btn btn-outline-primary">
            <i class="fas fa-redo me-2"></i>
            Recargar habitaciones
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de reserva -->
    <div v-if="selectedRoom" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-calendar-check me-2"></i>
              Reservar Habitación {{ selectedRoom.room_number }}
            </h5>
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
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { publicApi } from '@/services/api' // Ajusta la ruta según tu estructura
import ReservationForm from '@/components/public/ReservationForm.vue' // Ajusta la ruta

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
    const sortBy = ref('price_asc')

    const filters = ref({
      branch_id: '',
      check_in_date: '',
      check_out_date: '',
      adults: 1,
      children: 0
    })

    // Computed properties
    const today = computed(() => {
      return new Date().toISOString().split('T')[0]
    })

    const selectedBranch = computed(() => {
      if (!filters.value.branch_id) return null
      return branches.value.find(branch => branch.id == filters.value.branch_id)
    })

    const sortedRooms = computed(() => {
      const rooms = [...availableRooms.value]
      
      switch (sortBy.value) {
        case 'price_asc':
          return rooms.sort((a, b) => parseFloat(a.price_per_night) - parseFloat(b.price_per_night))
        case 'price_desc':
          return rooms.sort((a, b) => parseFloat(b.price_per_night) - parseFloat(a.price_per_night))
        case 'capacity':
          return rooms.sort((a, b) => b.room_type.max_guests - a.room_type.max_guests)
        case 'name':
          return rooms.sort((a, b) => a.room_type.name.localeCompare(b.room_type.name))
        default:
          return rooms
      }
    })

    // Methods
    const loadBranches = async () => {
      try {
        const response = await publicApi.getBranches()
        
        // Manejar diferentes formatos de respuesta
        if (response.data.success && response.data.data) {
          branches.value = response.data.data
        } else if (response.data.branches) {
          branches.value = response.data.branches
        } else if (Array.isArray(response.data)) {
          branches.value = response.data
        }
        
        console.log('Branches loaded:', branches.value)
      } catch (error) {
        console.error('Error loading branches:', error)
        branches.value = []
      }
    }

    const searchRooms = async () => {
      try {
        loading.value = true
        searchExecuted.value = true
        
        console.log('Searching with filters:', filters.value)
        
        const response = await publicApi.checkAvailability(filters.value)
        console.log('Search response:', response.data)
        
        // Manejar diferentes formatos de respuesta
        if (response.data.success) {
          availableRooms.value = response.data.data?.available_rooms || response.data.available_rooms || []
          searchParams.value = response.data.data?.search_params || response.data.search_params || {}
        } else {
          availableRooms.value = response.data.available_rooms || []
          searchParams.value = response.data.search_params || {}
        }
        
        console.log('Available rooms:', availableRooms.value)
        console.log('Search params:', searchParams.value)
        
      } catch (error) {
        console.error('Error searching rooms:', error)
        availableRooms.value = []
        searchParams.value = {}
        
        // Mostrar error al usuario
        alert('Error al buscar habitaciones. Por favor, intenta nuevamente.')
      } finally {
        loading.value = false
      }
    }

    const selectRoom = (room) => {
      selectedRoom.value = room
      console.log('Room selected:', room)
    }

    const onReservationSuccess = () => {
      selectedRoom.value = null
      alert('¡Reserva realizada exitosamente!')
      // Redirigir a mis reservas si el usuario está logueado
      // router.push('/customer/reservations')
    }

    const clearSearch = () => {
      // Resetear parámetros de búsqueda pero mantener habitaciones visibles
      searchParams.value = {}
      
      // Resetear fechas a valores por defecto
      const today = new Date().toISOString().split('T')[0]
      const tomorrow = new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      
      filters.value = {
        branch_id: '',
        check_in_date: today,
        check_out_date: tomorrow,
        adults: 1,
        children: 0
      }
      
      // Recargar todas las habitaciones
      loadAllRooms()
    }

    const sortRooms = () => {
      // La lógica de ordenamiento está en el computed sortedRooms
      console.log('Sorting by:', sortBy.value)
    }

    const getRoomImage = (room) => {
      // Primero verificar si tiene main_photo
      if (room.main_photo) {
        // Si ya viene con la ruta completa (/images/... o http://...)
        if (room.main_photo.startsWith('/') || room.main_photo.startsWith('http')) {
          return room.main_photo
        }
        // Si es solo el nombre del archivo (rooms/xxx.jpg)
        return `/storage/${room.main_photo}`
      } 
      // Si no, verificar photos array
      else if (room.photos && room.photos.length > 0) {
        const firstPhoto = room.photos[0]
        // Si ya viene con la ruta completa
        if (firstPhoto.startsWith('/') || firstPhoto.startsWith('http')) {
          return firstPhoto
        }
        // Si es solo el nombre del archivo
        return `/storage/${firstPhoto}`
      }
      // Imagen por defecto
      return '/images/room-placeholder.jpg'
    }

    const getTotalAmenities = (room) => {
      const roomAmenities = room.amenities || []
      const typeAmenities = room.room_type?.amenities || []
      return roomAmenities.length + typeAmenities.length
    }

    // Configurar fechas por defecto
    const initializeDates = () => {
      const today = new Date().toISOString().split('T')[0]
      const tomorrow = new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString().split('T')[0]
      
      filters.value.check_in_date = today
      filters.value.check_out_date = tomorrow
    }

    // Load all available rooms without filters
    const loadAllRooms = async () => {
      try {
        loading.value = true
        console.log('Loading all available rooms...')
        
        const response = await publicApi.getRooms()
        console.log('All rooms response:', response.data)
        
        // Manejar diferentes formatos de respuesta
        let roomsData = []
        if (response.data.success && response.data.data) {
          roomsData = response.data.data
        } else if (response.data.rooms) {
          roomsData = response.data.rooms
        } else if (Array.isArray(response.data)) {
          roomsData = response.data
        }
        
        console.log('Rooms data extracted:', roomsData)
        console.log('Number of rooms found:', roomsData.length)
        
        // No filtrar por is_active o status ya que no existen en estos datos
        // Solo usar todas las habitaciones que vengan
        const allRooms = roomsData || []
        
        // Formatear datos para mostrar - los precios ya vienen formateados
        availableRooms.value = allRooms.map(room => ({
          ...room,
          // Los precios ya vienen formateados, pero por si acaso:
          formatted_price: room.formatted_price || `Bs. ${Number(room.price_per_night || 0).toFixed(2)}`,
          formatted_room_total: room.formatted_room_total || `Bs. ${Number(room.price_per_night || 0).toFixed(2)}`,
          room_total: Number(room.price_per_night || 0),
          // Las fotos ya vienen con la ruta correcta
          main_photo: room.main_photo || (room.photos && room.photos.length > 0 ? room.photos[0] : null)
        }))
        
        // Marcar que ya tenemos datos para mostrar
        searchExecuted.value = true
        
        console.log('Available rooms loaded:', availableRooms.value.length)
        console.log('Sample room data:', availableRooms.value[0])
        
      } catch (error) {
        console.error('Error loading all rooms:', error)
        console.error('Error details:', error.response?.data)
        availableRooms.value = []
        searchExecuted.value = true // Marcar como ejecutado para mostrar el estado sin resultados
        
        // Mostrar error al usuario
        alert('Error al cargar las habitaciones. Por favor, recarga la página.')
      } finally {
        loading.value = false
      }
    }

    // Lifecycle
    onMounted(async () => {
      initializeDates()
      
      // Cargar datos en paralelo
      await Promise.all([
        loadBranches(),
        loadAllRooms()
      ])
      
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
      
      // Si hay parámetros específicos, hacer búsqueda filtrada
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
      sortBy,
      today,
      selectedBranch,
      sortedRooms,
      searchRooms,
      selectRoom,
      onReservationSuccess,
      clearSearch,
      sortRooms,
      getRoomImage,
      getTotalAmenities
    }
  }
}
</script>

<style scoped>
.search-rooms {
  background-color: #f8f9fa;
  min-height: calc(100vh - 80px);
}

.page-header {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  color: white;
  padding: 2rem;
  border-radius: 12px;
}

.search-filters .card {
  border: 1px solid #e9ecef;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  border-radius: 12px;
}

.search-summary {
  border: 1px solid #e9ecef;
}

.summary-item {
  display: inline-flex;
  align-items: center;
  color: #495057;
}

.results-header {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  border: 1px solid #e9ecef;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.room-card {
  transition: all 0.3s ease;
}

.room-card:hover {
  transform: translateY(-8px);
}

.room-card:hover .card {
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
  border-color: #28a745;
}

.room-image-container {
  position: relative;
  height: 220px;
  overflow: hidden;
}

.room-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.room-card:hover .room-image {
  transform: scale(1.05);
}

.room-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(to bottom, rgba(0,0,0,0.1), transparent, rgba(0,0,0,0.1));
}

.room-number-badge {
  position: absolute;
  top: 12px;
  left: 12px;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 0.5rem 0.75rem;
  border-radius: 20px;
  font-weight: 700;
  font-size: 0.9rem;
}

.room-category-badge {
  position: absolute;
  top: 12px;
  right: 12px;
}

.room-title {
  font-weight: 700;
  color: #495057;
  font-size: 1.1rem;
}

.room-location {
  font-size: 0.9rem;
}

.room-description {
  font-size: 0.9rem;
  line-height: 1.4;
  height: 2.8rem;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}

.capacity-info {
  background-color: #f8f9fa;
  padding: 0.75rem;
  border-radius: 8px;
  border-left: 4px solid #28a745;
}

.amenities-list {
  max-height: 80px;
  overflow: hidden;
}

.price-amount {
  font-size: 1.4rem;
  font-weight: 700;
  color: #28a745;
}

.total-price {
  font-size: 0.9rem;
}

.btn-reserve {
  background-color: #28a745;
  border-color: #28a745;
  font-weight: 600;
  padding: 0.5rem 1.25rem;
  border-radius: 25px;
  transition: all 0.3s ease;
}

.btn-reserve:hover {
  background-color: #218838;
  border-color: #1e7e34;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.loading-state,
.no-results-state,
.initial-state {
  background: white;
  border-radius: 12px;
  padding: 3rem 2rem;
  margin: 2rem 0;
  border: 1px solid #e9ecef;
}

.tip-card {
  text-align: center;
  transition: all 0.3s ease;
}

.tip-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.tip-card i {
  font-size: 1.5rem;
  display: block;
}

.suggestions ul li {
  padding: 0.5rem 0;
}

.modal.show {
  display: block !important;
}

.modal-content {
  border-radius: 12px;
  border: none;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.sort-controls .form-select {
  border-radius: 8px;
}

@media (max-width: 768px) {
  .page-header {
    text-align: center;
  }
  
  .room-card {
    margin-bottom: 1rem;
  }
  
  .search-summary {
    text-align: center;
  }
  
  .summary-item {
    display: block;
    margin-bottom: 0.5rem;
  }
  
  .results-header {
    text-align: center;
  }
  
  .price-section,
  .action-section {
    text-align: center;
  }
  
  .d-flex.justify-content-between {
    flex-direction: column;
    gap: 1rem;
  }
}

/* Animaciones */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.room-card {
  animation: fadeInUp 0.6s ease forwards;
}

.room-card:nth-child(1) { animation-delay: 0.1s; }
.room-card:nth-child(2) { animation-delay: 0.2s; }
.room-card:nth-child(3) { animation-delay: 0.3s; }
.room-card:nth-child(4) { animation-delay: 0.4s; }
.room-card:nth-child(5) { animation-delay: 0.5s; }
.room-card:nth-child(6) { animation-delay: 0.6s; }
</style>