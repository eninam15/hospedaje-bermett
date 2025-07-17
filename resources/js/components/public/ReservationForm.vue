<template>
  <div class="reservation-form">
    <!-- Room Information Card -->
    <div class="room-info-card mb-4">
      <div class="card border-0">
        <div class="card-body p-0">
          <div class="row align-items-center">
            <div class="col-md-4">
              <div class="room-image-preview">
                <img 
                  :src="getRoomImage(room)" 
                  :alt="'Habitación ' + room.room_number"
                  class="img-fluid rounded"
                />
              </div>
            </div>
            <div class="col-md-8">
              <div class="room-details">
                <h5 class="room-title mb-2">
                  <i class="fas fa-door-open text-primary me-2"></i>
                  {{ room.room_type.name }} - Habitación {{ room.room_number }}
                </h5>
                <p class="room-branch text-muted mb-2">
                  <i class="fas fa-map-marker-alt me-1"></i>
                  {{ room.branch.name }}
                </p>
                <p class="room-description mb-2">{{ room.description }}</p>
                <div class="room-capacity">
                  <span class="badge bg-light text-dark me-2">
                    <i class="fas fa-users me-1"></i>
                    Hasta {{ room.room_type.max_guests }} huéspedes
                  </span>
                  <span class="badge bg-light text-dark">
                    <i class="fas fa-star text-warning me-1"></i>
                    {{ room.branch.category }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reservation Form -->
    <form @submit.prevent="handleReservation" class="reservation-form-content">
      <!-- Dates Section -->
      <div class="form-section mb-4">
        <h6 class="section-title">
          <i class="fas fa-calendar-alt text-primary me-2"></i>
          Fechas de Estadía
        </h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">
              <i class="fas fa-calendar-check me-1"></i>
              Check-in
            </label>
            <input 
              v-model="form.check_in_date" 
              type="date" 
              class="form-control"
              :class="{ 'is-invalid': errors.check_in_date }"
              :min="minDate"
              @change="updateStayDuration"
              required
            >
            <div v-if="errors.check_in_date" class="invalid-feedback">
              {{ errors.check_in_date }}
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">
              <i class="fas fa-calendar-times me-1"></i>
              Check-out
            </label>
            <input 
              v-model="form.check_out_date" 
              type="date" 
              class="form-control"
              :class="{ 'is-invalid': errors.check_out_date }"
              :min="form.check_in_date || minDate"
              @change="updateStayDuration"
              required
            >
            <div v-if="errors.check_out_date" class="invalid-feedback">
              {{ errors.check_out_date }}
            </div>
          </div>
        </div>
        <div class="stay-duration mt-2">
          <small class="text-muted">
            <i class="fas fa-moon me-1"></i>
            Duración de estadía: <strong>{{ calculatedNights }} noche{{ calculatedNights > 1 ? 's' : '' }}</strong>
          </small>
        </div>
      </div>

      <!-- Guests Section -->
      <div class="form-section mb-4">
        <h6 class="section-title">
          <i class="fas fa-users text-primary me-2"></i>
          Huéspedes
        </h6>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">
              <i class="fas fa-user me-1"></i>
              Adultos
            </label>
            <select 
              v-model="form.adults_count" 
              class="form-select"
              :class="{ 'is-invalid': errors.adults_count }"
              required
            >
              <option v-for="n in room.room_type.max_adults" :key="n" :value="n">
                {{ n }} adulto{{ n > 1 ? 's' : '' }}
              </option>
            </select>
            <div v-if="errors.adults_count" class="invalid-feedback">
              {{ errors.adults_count }}
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label">
              <i class="fas fa-child me-1"></i>
              Niños
            </label>
            <select 
              v-model="form.children_count" 
              class="form-select"
              :class="{ 'is-invalid': errors.children_count }"
            >
              <option v-for="n in (room.room_type.max_children || 0) + 1" :key="n-1" :value="n-1">
                {{ n-1 }} niño{{ (n-1) !== 1 ? 's' : '' }}
              </option>
            </select>
            <div v-if="errors.children_count" class="invalid-feedback">
              {{ errors.children_count }}
            </div>
          </div>
        </div>
        <div class="guest-capacity-info mt-2">
          <div class="alert alert-info py-2">
            <small>
              <i class="fas fa-info-circle me-1"></i>
              Esta habitación puede alojar hasta {{ room.room_type.max_guests }} huéspedes
              ({{ room.room_type.max_adults }} adultos{{ room.room_type.max_children > 0 ? `, ${room.room_type.max_children} niños` : '' }})
            </small>
          </div>
        </div>
      </div>

      <!-- Services Section -->
      <div class="form-section mb-4">
        <h6 class="section-title">
          <i class="fas fa-concierge-bell text-primary me-2"></i>
          Servicios Adicionales
        </h6>
        <div class="services-options">
          <div class="service-option">
            <div class="form-check form-check-card">
              <input 
                v-model="form.needs_parking" 
                class="form-check-input" 
                type="checkbox" 
                id="needs_parking"
                @change="onParkingChange"
              >
              <label class="form-check-label" for="needs_parking">
                <div class="service-card">
                  <div class="service-icon">
                    <i class="fas fa-parking text-primary"></i>
                  </div>
                  <div class="service-details">
                    <div class="service-name">Estacionamiento</div>
                    <div class="service-description">Espacio de estacionamiento seguro</div>
                  </div>
                  <div class="service-price">
                    <span class="price text-success">GRATUITO</span>
                  </div>
                </div>
              </label>
            </div>
          </div>
        </div>

        <!-- Car Details Section (shown when parking is selected) -->
        <div v-if="form.needs_parking" class="car-details-section mt-3">
          <div class="card border-primary">
            <div class="card-header bg-primary text-white">
              <h6 class="mb-0">
                <i class="fas fa-car me-2"></i>
                Datos del Vehículo
              </h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">
                    <i class="fas fa-car me-1"></i>
                    Modelo del Vehículo
                  </label>
                  <input 
                    v-model="form.car_model" 
                    type="text" 
                    class="form-control"
                    :class="{ 'is-invalid': errors.car_model }"
                    placeholder="Ej: Toyota Corolla, Honda Civic, etc."
                    maxlength="50"
                  >
                  <div v-if="errors.car_model" class="invalid-feedback">
                    {{ errors.car_model }}
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">
                    <i class="fas fa-hashtag me-1"></i>
                    Placa del Vehículo
                  </label>
                  <input 
                    v-model="form.car_plate" 
                    type="text" 
                    class="form-control text-uppercase"
                    :class="{ 'is-invalid': errors.car_plate }"
                    placeholder="Ej: ABC-1234"
                    maxlength="10"
                    @input="formatCarPlate"
                  >
                  <div v-if="errors.car_plate" class="invalid-feedback">
                    {{ errors.car_plate }}
                  </div>
                </div>
              </div>
              <div class="mt-2">
                <small class="text-muted">
                  <i class="fas fa-info-circle me-1"></i>
                  Estos datos aparecerán en tu boleta de reserva para identificación del vehículo
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Method Section -->
      <div class="form-section mb-4">
        <h6 class="section-title">
          <i class="fas fa-credit-card text-primary me-2"></i>
          Método de Pago
        </h6>
        <div class="payment-methods">
          <div class="payment-option">
            <div class="form-check form-check-card">
              <input 
                v-model="form.payment_method" 
                class="form-check-input" 
                type="radio" 
                id="payment_qr" 
                value="qr"
              >
              <label class="form-check-label" for="payment_qr">
                <div class="payment-card">
                  <div class="payment-icon">
                    <i class="fas fa-qrcode text-success"></i>
                  </div>
                  <div class="payment-details">
                    <div class="payment-name">QR / Transferencia</div>
                    <div class="payment-description">Pago inmediato con QR o transferencia bancaria</div>
                  </div>
                  <div class="payment-badge">
                    <span class="badge bg-success">Recomendado</span>
                  </div>
                </div>
              </label>
            </div>
          </div>
          <div class="payment-option">
            <div class="form-check form-check-card">
              <input 
                v-model="form.payment_method" 
                class="form-check-input" 
                type="radio" 
                id="payment_cash" 
                value="cash"
              >
              <label class="form-check-label" for="payment_cash">
                <div class="payment-card">
                  <div class="payment-icon">
                    <i class="fas fa-money-bill-wave text-warning"></i>
                  </div>
                  <div class="payment-details">
                    <div class="payment-name">Efectivo</div>
                    <div class="payment-description">Pago en efectivo al llegar al hospedaje</div>
                  </div>
                  <div class="payment-badge">
                    <span class="badge bg-outline-secondary">Al llegar</span>
                  </div>
                </div>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Special Requests Section -->
      <div class="form-section mb-4">
        <h6 class="section-title">
          <i class="fas fa-comment-alt text-primary me-2"></i>
          Solicitudes Especiales <span class="text-muted">(opcional)</span>
        </h6>
        <div class="special-requests">
          <textarea 
            v-model="form.special_requests" 
            class="form-control"
            :class="{ 'is-invalid': errors.special_requests }"
            rows="4"
            placeholder="¿Tienes alguna solicitud especial? Por ejemplo: habitación en piso alto, cama extra, etc."
            maxlength="500"
          ></textarea>
          <div v-if="errors.special_requests" class="invalid-feedback">
            {{ errors.special_requests }}
          </div>
          <div class="form-text">
            <small class="text-muted">{{ form.special_requests.length }}/500 caracteres</small>
          </div>
        </div>
      </div>

      <!-- Cost Summary Section -->
      <div class="cost-summary-section mb-4">
        <div class="card border-0 bg-light">
          <div class="card-body">
            <h6 class="card-title mb-3">
              <i class="fas fa-calculator text-primary me-2"></i>
              Resumen de Costos
            </h6>
            <div class="cost-breakdown">
              <div class="cost-item d-flex justify-content-between align-items-center">
                <div class="cost-label">
                  <i class="fas fa-bed me-2 text-muted"></i>
                  Habitación {{ room.room_type.name }}
                  <small class="text-muted d-block">
                    {{ calculatedNights }} noche{{ calculatedNights > 1 ? 's' : '' }} × {{ room.formatted_price || `Bs. ${room.price_per_night}` }}
                  </small>
                </div>
                <div class="cost-value">
                  Bs. {{ roomTotal.toFixed(2) }}
                </div>
              </div>
              
              <div v-if="form.needs_parking" class="cost-item d-flex justify-content-between align-items-center">
                <div class="cost-label">
                  <i class="fas fa-parking me-2 text-muted"></i>
                  Estacionamiento
                  <small class="text-muted d-block">
                    {{ calculatedNights }} noche{{ calculatedNights > 1 ? 's' : '' }} × Gratuito
                  </small>
                </div>
                <div class="cost-value text-success">
                  Bs. 0.00
                </div>
              </div>
              
              <hr class="my-3">
              
              <div class="cost-total d-flex justify-content-between align-items-center">
                <div class="total-label">
                  <strong>
                    <i class="fas fa-receipt me-2 text-success"></i>
                    Total a Pagar
                  </strong>
                </div>
                <div class="total-value">
                  <strong class="text-success fs-5">Bs. {{ grandTotal.toFixed(2) }}</strong>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Information -->
      <div v-if="form.payment_method === 'qr'" class="payment-info-section mb-4">
        <div class="alert alert-info border-0">
          <h6 class="alert-heading">
            <i class="fas fa-info-circle me-2"></i>
            Información de Pago QR
          </h6>
          <div class="qr-payment-info">
            <p class="mb-2"><strong>{{ room.branch.qr_payment_info || 'Información de pago QR no disponible' }}</strong></p>
            <div class="payment-instructions">
              <small class="text-muted">
                <strong>Instrucciones:</strong><br>
                1. Realiza la transferencia por el monto total<br>
                2. Confirma tu reserva<br>
                3. Escanea el código QR que aparecerá después<br>
                4. Sube el comprobante de pago en "Mis Reservas"
              </small>
            </div>
          </div>
        </div>
      </div>

      <!-- Authentication Warning -->
      <div v-if="!isAuthenticated" class="auth-warning mb-4">
        <div class="alert alert-warning border-0">
          <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle text-warning me-3 fs-4"></i>
            <div>
              <h6 class="alert-heading mb-1">Inicia Sesión para Continuar</h6>
              <p class="mb-2">Necesitas una cuenta para realizar la reserva.</p>
              <div class="auth-actions">
                <router-link to="/login" class="btn btn-warning btn-sm me-2">
                  <i class="fas fa-sign-in-alt me-1"></i>
                  Iniciar Sesión
                </router-link>
                <router-link to="/register" class="btn btn-outline-warning btn-sm">
                  <i class="fas fa-user-plus me-1"></i>
                  Crear Cuenta
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="form-actions">
        <div class="row">
          <div class="col-md-6">
            <button 
              type="button" 
              class="btn btn-outline-secondary w-100" 
              @click="$emit('cancel')"
            >
              <i class="fas fa-arrow-left me-2"></i>
              Volver a Buscar
            </button>
          </div>
          <div class="col-md-6">
            <button 
              type="submit" 
              class="btn btn-success w-100" 
              :disabled="loading || !isAuthenticated || !isFormValid"
            >
              <span v-if="loading">
                <span class="spinner-border spinner-border-sm me-2"></span>
                Procesando Reserva...
              </span>
              <span v-else>
                <i class="fas fa-calendar-check me-2"></i>
                Confirmar Reserva
              </span>
            </button>
          </div>
        </div>
      </div>
    </form>

    <!-- QR Payment Modal -->
    <div v-if="showQRModal" class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">
              <i class="fas fa-qrcode me-2"></i>
              Pago con QR
            </h5>
          </div>
          <div class="modal-body text-center">
            <div class="qr-section mb-4">
              <h6 class="mb-3">Escanea el código QR para realizar el pago</h6>
              <div class="qr-code-container">
                <img src="/images/qrpago.png" alt="QR de Pago" style="width: 250px; height: 250px;" />
              </div>

              <div class="payment-amount mt-3">
                <h4 class="text-primary">Monto a pagar: <strong>Bs. {{ grandTotal.toFixed(2) }}</strong></h4>
              </div>
            </div>
            <div class="payment-info">
              <div class="alert alert-info">
                <p class="mb-2"><strong>Información de pago:</strong></p>
                <p class="mb-1">Banco: {{ paymentInfo.bank || 'Banco Nacional' }}</p>
                <p class="mb-1">Cuenta: {{ paymentInfo.account || '1234567890' }}</p>
                <p class="mb-0">Titular: {{ paymentInfo.owner || room.branch.name }}</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button @click="closeQRModal" class="btn btn-secondary">
              <i class="fas fa-times me-2"></i>
              Cancelar
            </button>
            <button @click="confirmPayment" class="btn btn-success">
              <i class="fas fa-check me-2"></i>
              He realizado el pago
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Modal -->
    <div v-if="showSuccessModal" class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">
              <i class="fas fa-check-circle me-2"></i>
              ¡Reserva Exitosa!
            </h5>
          </div>
          <div class="modal-body text-center">
            <div class="success-icon mb-3">
              <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
            </div>
            <h4 class="text-success mb-3">¡Reserva Confirmada!</h4>
            <div class="reservation-details bg-light p-3 rounded mb-3">
              <h6>Detalles de tu Reserva:</h6>
              <p class="mb-1"><strong>Código:</strong> {{ reservationData.reservation_code }}</p>
              <p class="mb-1"><strong>Habitación:</strong> {{ room.room_type.name }} - {{ room.room_number }}</p>
              <p class="mb-1"><strong>Check-in:</strong> {{ formatDate(form.check_in_date) }}</p>
              <p class="mb-1"><strong>Check-out:</strong> {{ formatDate(form.check_out_date) }}</p>
              <p class="mb-0"><strong>Total:</strong> Bs. {{ grandTotal.toFixed(2) }}</p>
            </div>
            <div class="next-steps">
              <h6>Próximos Pasos:</h6>
              <div v-if="form.payment_method === 'qr'" class="alert alert-info">
                <p class="mb-0">
                  <i class="fas fa-upload me-2"></i>
                  No olvides subir tu comprobante de pago en "Mis Reservas"
                </p>
              </div>
              <div v-else class="alert alert-warning">
                <p class="mb-0">
                  <i class="fas fa-money-bill-wave me-2"></i>
                  Presenta este código al llegar al hospedaje para pagar en efectivo
                </p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button @click="downloadPDF" class="btn btn-primary me-2">
              <i class="fas fa-download me-2"></i>
              Descargar Boleta PDF
            </button>
            <button @click="closeSuccessModal" class="btn btn-success">
              <i class="fas fa-check me-2"></i>
              Entendido
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, nextTick } from 'vue'
import { customerApi } from '@/services/api'
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

export default {
  name: 'ReservationForm',
  props: {
    room: {
      type: Object,
      required: true
    },
    searchParams: {
      type: Object,
      default: () => ({})
    }
  },
  emits: ['success', 'cancel'],
  setup(props, { emit }) {
    const loading = ref(false)
    const showSuccessModal = ref(false)
    const showQRModal = ref(false)
    const reservationData = ref({})
    const errors = ref({})
    
    const form = ref({
      room_id: props.room.id,
      check_in_date: props.searchParams.check_in_date || '',
      check_out_date: props.searchParams.check_out_date || '',
      adults_count: props.searchParams.adults || 1,
      children_count: props.searchParams.children || 0,
      needs_parking: false,
      car_model: '',
      car_plate: '',
      payment_method: 'qr',
      special_requests: ''
    })

    // Información de pago (esto debería venir del backend)
    const paymentInfo = ref({
      bank: 'Banco Nacional de Bolivia',
      account: '1000123456789',
      owner: props.room.branch?.name || 'Hotel'
    })

    // Fecha mínima (hoy)
    const minDate = computed(() => {
      const today = new Date()
      return today.toISOString().split('T')[0]
    })

    // Calcular noches de estadía
    const calculatedNights = computed(() => {
      if (!form.value.check_in_date || !form.value.check_out_date) return 1
      
      const checkIn = new Date(form.value.check_in_date)
      const checkOut = new Date(form.value.check_out_date)
      const diffTime = checkOut - checkIn
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      
      return diffDays > 0 ? diffDays : 1
    })

    // Computed properties para costos
    const roomTotal = computed(() => {
      const pricePerNight = parseFloat(props.room.price_per_night) || 0
      return pricePerNight * calculatedNights.value
    })

    const parkingTotal = computed(() => {
      // Estacionamiento gratuito
      return 0
    })

    const grandTotal = computed(() => {
      return roomTotal.value + parkingTotal.value
    })

    // Autenticación
    const isAuthenticated = computed(() => {
      const token = localStorage.getItem('auth_token')
      return !!token
    })

    const isFormValid = computed(() => {
      return form.value.check_in_date && 
             form.value.check_out_date && 
             form.value.adults_count && 
             form.value.payment_method &&
             calculatedNights.value > 0 &&
             (!form.value.needs_parking || (form.value.car_model && form.value.car_plate)) &&
             Object.keys(errors.value).length === 0
    })

    // Métodos
    const updateStayDuration = () => {
      // Forzar recálculo
      if (form.value.check_in_date && form.value.check_out_date) {
        const checkIn = new Date(form.value.check_in_date)
        const checkOut = new Date(form.value.check_out_date)
        
        if (checkOut <= checkIn) {
          // Si checkout es menor o igual que checkin, ajustar checkout
          const newCheckOut = new Date(checkIn)
          newCheckOut.setDate(newCheckOut.getDate() + 1)
          form.value.check_out_date = newCheckOut.toISOString().split('T')[0]
        }
      }
    }

    const onParkingChange = () => {
      if (!form.value.needs_parking) {
        // Limpiar datos del auto si se desmarca el estacionamiento
        form.value.car_model = ''
        form.value.car_plate = ''
        // Limpiar errores relacionados
        delete errors.value.car_model
        delete errors.value.car_plate
      }
    }

    const formatCarPlate = (event) => {
      // Convertir a mayúsculas automáticamente
      event.target.value = event.target.value.toUpperCase()
      form.value.car_plate = event.target.value.toUpperCase()
    }

    const validateForm = () => {
      errors.value = {}
      
      if (!form.value.check_in_date) {
        errors.value.check_in_date = 'La fecha de check-in es requerida'
      }
      
      if (!form.value.check_out_date) {
        errors.value.check_out_date = 'La fecha de check-out es requerida'
      }
      
      if (form.value.check_in_date && form.value.check_out_date) {
        const checkIn = new Date(form.value.check_in_date)
        const checkOut = new Date(form.value.check_out_date)
        
        if (checkOut <= checkIn) {
          errors.value.check_out_date = 'La fecha de check-out debe ser posterior al check-in'
        }
        
        if (checkIn < new Date(minDate.value)) {
          errors.value.check_in_date = 'La fecha de check-in no puede ser anterior a hoy'
        }
      }
      
      if (!form.value.adults_count || form.value.adults_count < 1) {
        errors.value.adults_count = 'Debe haber al menos 1 adulto'
      }
      
      if (form.value.adults_count > props.room.room_type.max_adults) {
        errors.value.adults_count = `Máximo ${props.room.room_type.max_adults} adultos para esta habitación`
      }
      
      if (form.value.children_count > (props.room.room_type.max_children || 0)) {
        errors.value.children_count = `Máximo ${props.room.room_type.max_children || 0} niños para esta habitación`
      }
      
      const totalGuests = parseInt(form.value.adults_count) + parseInt(form.value.children_count)
      if (totalGuests > props.room.room_type.max_guests) {
        errors.value.children_count = `Total de huéspedes no puede exceder ${props.room.room_type.max_guests}`
      }

      // Validar datos del auto si se requiere estacionamiento
      if (form.value.needs_parking) {
        if (!form.value.car_model || form.value.car_model.trim() === '') {
          errors.value.car_model = 'El modelo del vehículo es requerido'
        }
        
        if (!form.value.car_plate || form.value.car_plate.trim() === '') {
          errors.value.car_plate = 'La placa del vehículo es requerida'
        } else if (form.value.car_plate.length < 6) {
          errors.value.car_plate = 'La placa debe tener al menos 6 caracteres'
        }
      }
      
      if (form.value.special_requests && form.value.special_requests.length > 500) {
        errors.value.special_requests = 'Las solicitudes especiales no pueden exceder 500 caracteres'
      }
      
      return Object.keys(errors.value).length === 0
    }

    const handleReservation = async () => {
      if (!isAuthenticated.value) {
        alert('Debes iniciar sesión para realizar una reserva')
        return
      }

      if (!validateForm()) {
        alert('Por favor, corrige los errores en el formulario')
        return
      }

      try {
        loading.value = true
        
        const reservationData = {
          ...form.value,
          total_amount: grandTotal.value,
          parking_cost: parkingTotal.value,
          nights: calculatedNights.value
        }
        
        console.log('Creating reservation with data:', reservationData)
        
        const response = await customerApi.createReservation(reservationData)
        console.log('Reservation response:', response.data)
        
        if (response.data.success) {
          reservationData.value = response.data.data || response.data.reservation
        } else {
          reservationData.value = response.data.reservation || response.data
        }
        
        // Si es pago QR, mostrar modal QR primero
        if (form.value.payment_method === 'qr') {
          showQRModal.value = true
        } else {
          showSuccessModal.value = true
        }
        
      } catch (error) {
        console.error('Error creating reservation:', error)
        
        let errorMessage = 'Error al crear la reserva'
        if (error.response?.data?.message) {
          errorMessage = error.response.data.message
        } else if (error.response?.data?.errors) {
          const errorMessages = Object.values(error.response.data.errors).flat()
          errorMessage = errorMessages.join(', ')
        } else if (error.message) {
          errorMessage = error.message
        }
        
        alert('❌ ' + errorMessage)
      } finally {
        loading.value = false
      }
    }

    const confirmPayment = () => {
      showQRModal.value = false
      showSuccessModal.value = true
    }

    const closeQRModal = () => {
      showQRModal.value = false
    }

    const closeSuccessModal = () => {
      showSuccessModal.value = false
      emit('success', reservationData.value)
    }

    const downloadPDF = () => {
      try {
        const doc = new jsPDF()
        const pageWidth = doc.internal.pageSize.width
        const pageHeight = doc.internal.pageSize.height
        
        // Configuración del documento
        doc.setFontSize(20)
        doc.setTextColor(40, 40, 40)
        doc.text('BOLETA DE RESERVA', pageWidth / 2, 25, { align: 'center' })
        
        // Información del hospedaje
        doc.setFontSize(14)
        doc.setTextColor(100, 100, 100)
        doc.text(props.room.branch.name, pageWidth / 2, 35, { align: 'center' })
        doc.text(`Categoría: ${props.room.branch.category}`, pageWidth / 2, 42, { align: 'center' })
        
        // Línea separadora
        doc.setLineWidth(0.5)
        doc.setDrawColor(200, 200, 200)
        doc.line(20, 50, pageWidth - 20, 50)
        
        // Información de la reserva
        doc.setFontSize(12)
        doc.setTextColor(0, 0, 0)
        
        let yPosition = 60
        
        // Datos básicos de la reserva
        const reservationInfo = [
          ['Código de Reserva:', reservationData.value.reservation_code || 'N/A'],
          ['Fecha de Reserva:', new Date().toLocaleDateString('es-BO')],
          ['Habitación:', `${props.room.room_type.name} - Habitación ${props.room.room_number}`],
          ['Huéspedes:', `${form.value.adults_count} adulto(s), ${form.value.children_count} niño(s)`],
          ['Check-in:', formatDate(form.value.check_in_date)],
          ['Check-out:', formatDate(form.value.check_out_date)],
          ['Noches:', `${calculatedNights.value} noche(s)`],
          ['Método de Pago:', form.value.payment_method === 'qr' ? 'QR/Transferencia' : 'Efectivo al llegar']
        ]
        
        // Agregar información del vehículo si aplica
        if (form.value.needs_parking) {
          reservationInfo.push(['Estacionamiento:', 'Incluido (Gratuito)'])
          reservationInfo.push(['Modelo del Vehículo:', form.value.car_model])
          reservationInfo.push(['Placa del Vehículo:', form.value.car_plate])
        }
        
        reservationInfo.forEach(([label, value]) => {
          doc.setFont(undefined, 'bold')
          doc.text(label, 20, yPosition)
          doc.setFont(undefined, 'normal')
          doc.text(value, 80, yPosition)
          yPosition += 8
        })
        
        // Solicitudes especiales
        if (form.value.special_requests) {
          yPosition += 5
          doc.setFont(undefined, 'bold')
          doc.text('Solicitudes Especiales:', 20, yPosition)
          yPosition += 8
          doc.setFont(undefined, 'normal')
          
          // Dividir el texto en líneas
          const splitText = doc.splitTextToSize(form.value.special_requests, pageWidth - 40)
          splitText.forEach(line => {
            doc.text(line, 20, yPosition)
            yPosition += 6
          })
        }
        
        // Desglose de costos
        yPosition += 15
        doc.setFontSize(14)
        doc.setFont(undefined, 'bold')
        doc.text('DESGLOSE DE COSTOS:', 20, yPosition)
        yPosition += 10
        
        const costBreakdown = [
          ['Concepto', 'Cantidad', 'Precio Unitario', 'Subtotal'],
          [
            `Habitación ${props.room.room_type.name}`, 
            `${calculatedNights.value} noche(s)`, 
            `Bs. ${(roomTotal.value / calculatedNights.value).toFixed(2)}`, 
            `Bs. ${roomTotal.value.toFixed(2)}`
          ]
        ]
        
        if (form.value.needs_parking) {
          costBreakdown.push([
            'Estacionamiento', 
            `${calculatedNights.value} noche(s)`, 
            'Bs. 0.00 (Gratuito)', 
            'Bs. 0.00'
          ])
        }
        
        autoTable(doc, {
          startY: yPosition,
          head: [costBreakdown[0]],
          body: costBreakdown.slice(1),
          theme: 'grid',
          styles: { fontSize: 10 },
          headStyles: { fillColor: [41, 128, 185], textColor: [255, 255, 255] },
          columnStyles: {
            0: { cellWidth: 60 },
            1: { cellWidth: 30, halign: 'center' },
            2: { cellWidth: 40, halign: 'right' },
            3: { cellWidth: 40, halign: 'right' }
          }
        })
        
        // Total
        const finalY = doc.lastAutoTable ? doc.lastAutoTable.finalY + 10 : yPosition + 50
        doc.setFontSize(16)
        doc.setFont(undefined, 'bold')
        doc.setTextColor(40, 167, 69)
        doc.text(`TOTAL A PAGAR: Bs. ${grandTotal.value.toFixed(2)}`, pageWidth - 20, finalY, { align: 'right' })
        
        // Información importante
        let footerY = finalY + 20
        doc.setFontSize(11)
        doc.setTextColor(0, 0, 0)
        doc.setFont(undefined, 'bold')
        doc.text('INFORMACIÓN IMPORTANTE:', 20, footerY)
        footerY += 8
        
        doc.setFont(undefined, 'normal')
        const importantInfo = [
          '• Presentar esta boleta al momento del check-in',
          '• El check-in es a partir de las 14:00 hrs',
          '• El check-out es hasta las 12:00 hrs',
          '• Cancelaciones: contactar con 24 hrs de anticipación'
        ]
        
        if (form.value.payment_method === 'qr') {
          importantInfo.push('• No olvide subir el comprobante de pago en "Mis Reservas"')
        }
        
        if (form.value.needs_parking) {
          importantInfo.push(`• Su vehículo ${form.value.car_model} (${form.value.car_plate}) tiene estacionamiento asignado`)
        }
        
        importantInfo.forEach(info => {
          doc.text(info, 20, footerY)
          footerY += 6
        })
        
        // Pie de página
        footerY += 10
        doc.setFontSize(10)
        doc.setTextColor(100, 100, 100)
        doc.text('Gracias por elegirnos. ¡Esperamos que disfrute su estadía!', pageWidth / 2, footerY, { align: 'center' })
        
        // Fecha de emisión
        doc.text(`Fecha de emisión: ${new Date().toLocaleDateString('es-BO', { 
          year: 'numeric', 
          month: 'long', 
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        })}`, pageWidth / 2, footerY + 8, { align: 'center' })
        
        // Descargar el PDF
        doc.save(`Boleta_Reserva_${reservationData.value.reservation_code || 'temp'}.pdf`)
        
      } catch (error) {
        console.error('Error generating PDF:', error)
        alert('Error al generar el PDF. Inténtalo de nuevo.')
      }
    }

    const getRoomImage = (room) => {
      if (room.main_photo) {
        if (room.main_photo.startsWith('/') || room.main_photo.startsWith('http')) {
          return room.main_photo
        }
        return `/storage/${room.main_photo}`
      } else if (room.photos && room.photos.length > 0) {
        const firstPhoto = room.photos[0]
        if (firstPhoto.startsWith('/') || firstPhoto.startsWith('http')) {
          return firstPhoto
        }
        return `/storage/${firstPhoto}`
      }
      return '/images/room-placeholder.jpg'
    }

    const formatDate = (dateString) => {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('es-BO', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      })
    }

    // Initialize form with search params
    onMounted(() => {
      console.log('ReservationForm mounted with:', {
        room: props.room,
        searchParams: props.searchParams
      })
      
      // Si no hay fechas en searchParams, usar valores por defecto
      if (!form.value.check_in_date) {
        const today = new Date()
        form.value.check_in_date = today.toISOString().split('T')[0]
      }
      
      if (!form.value.check_out_date) {
        const tomorrow = new Date()
        tomorrow.setDate(tomorrow.getDate() + 1)
        form.value.check_out_date = tomorrow.toISOString().split('T')[0]
      }
    })

    return {
      form,
      loading,
      showSuccessModal,
      showQRModal,
      reservationData,
      errors,
      paymentInfo,
      minDate,
      calculatedNights,
      roomTotal,
      parkingTotal,
      grandTotal,
      isAuthenticated,
      isFormValid,
      updateStayDuration,
      onParkingChange,
      formatCarPlate,
      handleReservation,
      confirmPayment,
      closeQRModal,
      closeSuccessModal,
      downloadPDF,
      getRoomImage,
      formatDate,
      validateForm
    }
  }
}
</script>

<style scoped>
.reservation-form {
  max-width: 100%;
}

.room-info-card {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 12px;
  padding: 1.5rem;
}

.room-image-preview {
  height: 120px;
  overflow: hidden;
  border-radius: 8px;
}

.room-image-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.room-title {
  color: #495057;
  font-weight: 600;
}

.room-branch {
  font-size: 0.9rem;
}

.room-description {
  color: #6c757d;
  font-size: 0.9rem;
}

.form-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #e9ecef;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.section-title {
  color: #495057;
  font-weight: 600;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #f8f9fa;
}

.form-check-card {
  margin-bottom: 1rem;
}

.form-check-card .form-check-input {
  display: none;
}

.service-card,
.payment-card {
  padding: 1rem;
  border: 2px solid #e9ecef;
  border-radius: 8px;
  transition: all 0.3s ease;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.form-check-input:checked + .form-check-label .service-card,
.form-check-input:checked + .form-check-label .payment-card {
  border-color: #28a745;
  background-color: #f8fff9;
}

.service-card:hover,
.payment-card:hover {
  border-color: #28a745;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
}

.service-icon,
.payment-icon {
  font-size: 1.5rem;
  width: 50px;
  text-align: center;
}

.service-details,
.payment-details {
  flex: 1;
}

.service-name,
.payment-name {
  font-weight: 600;
  color: #495057;
}

.service-description,
.payment-description {
  font-size: 0.85rem;
  color: #6c757d;
}

.service-price {
  text-align: right;
}

.service-price .price {
  font-weight: 600;
  font-size: 1.1rem;
}

.car-details-section {
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.car-details-section .card-header {
  border-radius: 8px 8px 0 0;
}

.car-details-section .form-control {
  border-radius: 6px;
}

.cost-summary-section .card {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.cost-item {
  padding: 0.75rem 0;
}

.cost-item:not(:last-child) {
  border-bottom: 1px solid #f8f9fa;
}

.cost-label {
  flex: 1;
}

.cost-value {
  font-weight: 600;
  color: #495057;
}

.cost-total {
  padding: 1rem 0 0;
}

.total-value {
  font-size: 1.25rem;
}

.payment-info-section .alert {
  border-left: 4px solid #17a2b8;
}

.auth-warning .alert {
  border-left: 4px solid #ffc107;
}

.form-actions {
  margin-top: 2rem;
}

.btn-success {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  border: none;
  font-weight: 600;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.btn-success:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

.btn-outline-secondary {
  border-color: #6c757d;
  color: #6c757d;
  font-weight: 600;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.btn-outline-secondary:hover {
  background-color: #6c757d;
  border-color: #6c757d;
  transform: translateY(-2px);
}

.success-icon {
  animation: checkScale 0.6s ease-in-out;
}

@keyframes checkScale {
  0% { transform: scale(0); }
  50% { transform: scale(1.2); }
  100% { transform: scale(1); }
}

.modal.show {
  display: block !important;
}

.modal-content {
  border-radius: 12px;
  border: none;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

.reservation-details {
  text-align: left;
}

.next-steps {
  text-align: left;
}

.form-control:focus,
.form-select:focus {
  border-color: #28a745;
  box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.is-invalid {
  border-color: #dc3545;
}

.invalid-feedback {
  display: block;
}

.stay-duration,
.guest-capacity-info,
.form-text {
  margin-top: 0.5rem;
}

.guest-capacity-info .alert {
  margin-bottom: 0;
}

.qr-code-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 1rem;
  background: white;
  border-radius: 8px;
  border: 2px solid #e9ecef;
  margin: 0 auto;
  width: fit-content;
}

.text-uppercase {
  text-transform: uppercase;
}

@media (max-width: 768px) {
  .form-section {
    padding: 1rem;
  }
  
  .room-info-card {
    padding: 1rem;
  }
  
  .service-card,
  .payment-card {
    flex-direction: column;
    text-align: center;
  }
  
  .service-price {
    text-align: center;
  }
}
</style>