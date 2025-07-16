<template>
  <div>
    <!-- Información de la habitación -->
    <div class="card mb-4">
      <div class="card-body">
        <h6 class="card-title">{{ room.room_type.name }} - Habitación {{ room.room_number }}</h6>
        <p class="text-muted mb-2">{{ room.branch.name }}</p>
        <p class="mb-0">{{ room.description }}</p>
      </div>
    </div>

    <!-- Formulario de reserva -->
    <form @submit.prevent="handleReservation">
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Check-in</label>
          <input v-model="form.check_in_date" type="date" class="form-control" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Check-out</label>
          <input v-model="form.check_out_date" type="date" class="form-control" readonly>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Adultos</label>
          <select v-model="form.adults_count" class="form-select" required>
            <option v-for="n in room.room_type.max_adults" :key="n" :value="n">{{ n }}</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Niños</label>
          <select v-model="form.children_count" class="form-select">
            <option v-for="n in room.room_type.max_children + 1" :key="n-1" :value="n-1">{{ n-1 }}</option>
          </select>
        </div>
      </div>

      <div class="mb-3">
        <div class="form-check">
          <input 
            v-model="form.needs_parking" 
            class="form-check-input" 
            type="checkbox" 
            id="needs_parking"
          >
          <label class="form-check-label" for="needs_parking">
            Necesito estacionamiento (+Bs. 15 por noche)
          </label>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Método de Pago</label>
        <div class="form-check">
          <input 
            v-model="form.payment_method" 
            class="form-check-input" 
            type="radio" 
            id="payment_qr" 
            value="qr"
          >
          <label class="form-check-label" for="payment_qr">
            QR (Yape/Transferencia) - Pago inmediato
          </label>
        </div>
        <div class="form-check">
          <input 
            v-model="form.payment_method" 
            class="form-check-input" 
            type="radio" 
            id="payment_cash" 
            value="cash"
          >
          <label class="form-check-label" for="payment_cash">
            Efectivo - Pago en el hospedaje
          </label>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Solicitudes especiales (opcional)</label>
        <textarea 
          v-model="form.special_requests" 
          class="form-control" 
          rows="3"
          placeholder="Cualquier solicitud especial..."
        ></textarea>
      </div>

      <!-- Resumen de costos -->
      <div class="card mb-4">
        <div class="card-body">
          <h6 class="card-title">Resumen de Costos</h6>
          <div class="d-flex justify-content-between">
            <span>Habitación ({{ searchParams.total_nights }} noche{{ searchParams.total_nights > 1 ? 's' : '' }}):</span>
            <span>{{ room.formatted_room_total }}</span>
          </div>
          <div v-if="form.needs_parking" class="d-flex justify-content-between">
            <span>Estacionamiento:</span>
            <span>Bs. {{ parkingTotal }}</span>
          </div>
          <hr>
          <div class="d-flex justify-content-between fw-bold">
            <span>Total:</span>
            <span class="text-success">Bs. {{ grandTotal }}</span>
          </div>
        </div>
      </div>

      <!-- Información de pago QR -->
      <div v-if="form.payment_method === 'qr'" class="alert alert-info">
        <h6>Información de Pago QR</h6>
        <p class="mb-0">{{ room.branch.qr_payment_info }}</p>
        <small class="text-muted">
          Después de realizar la reserva, podrás subir el comprobante de pago.
        </small>
      </div>

      <!-- Botones -->
      <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-secondary" @click="$emit('cancel')">
          Cancelar
        </button>
        <button type="submit" class="btn btn-success" :disabled="loading || !isAuthenticated">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
          {{ loading ? 'Reservando...' : 'Confirmar Reserva' }}
        </button>
      </div>

      <!-- Mensaje de login -->
      <div v-if="!isAuthenticated" class="alert alert-warning mt-3">
        <i class="pi pi-info-circle"></i>
        Debes <router-link to="/login">iniciar sesión</router-link> para realizar una reserva.
      </div>
    </form>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth.js'
import { customerApi } from '../../services/api.js'

export default {
  name: 'ReservationForm',
  props: {
    room: {
      type: Object,
      required: true
    },
    searchParams: {
      type: Object,
      required: true
    }
  },
  emits: ['success', 'cancel'],
  setup(props, { emit }) {
    const authStore = useAuthStore()
    const loading = ref(false)
    
    const form = ref({
      room_id: props.room.id,
      check_in_date: props.searchParams.check_in_date,
      check_out_date: props.searchParams.check_out_date,
      adults_count: props.searchParams.adults,
      children_count: props.searchParams.children,
      needs_parking: false,
      payment_method: 'qr',
      special_requests: ''
    })

    const parkingTotal = computed(() => {
      return form.value.needs_parking ? (15 * props.searchParams.total_nights) : 0
    })

    const grandTotal = computed(() => {
      return props.room.room_total + parkingTotal.value
    })

    const isAuthenticated = computed(() => authStore.isAuthenticated)

    const handleReservation = async () => {
      if (!isAuthenticated.value) {
        return
      }

      try {
        loading.value = true
        
        const response = await customerApi.createReservation(form.value)
        
        // Mostrar mensaje de éxito
        alert('Reserva creada exitosamente. Código: ' + response.data.reservation.reservation_code)
        
        emit('success')
      } catch (error) {
        console.error('Error creating reservation:', error)
        alert('Error al crear la reserva: ' + (error.response?.data?.message || error.message))
      } finally {
        loading.value = false
      }
    }

    return {
      form,
      loading,
      parkingTotal,
      grandTotal,
      isAuthenticated,
      handleReservation
    }
  }
}
</script>

<style scoped>
.form-check-input:checked {
  background-color: #007bff;
  border-color: #007bff;
}

.btn-success {
  background-color: #28a745;
  border-color: #28a745;
}

.btn-success:hover {
  background-color: #218838;
  border-color: #1e7e34;
}

.text-success {
  color: #28a745 !important;
}
</style>