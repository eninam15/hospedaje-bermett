<template>
  <div class="modal-overlay" @click="handleOverlayClick">
    <div class="modal-container" @click.stop>
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-sign-in-alt me-2"></i>
          Check-in de Reserva
        </h5>
        <button @click="$emit('close')" class="btn-close" type="button">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="modal-body">
        <!-- Información de la Reserva -->
        <div class="reservation-summary mb-4">
          <h6 class="section-title">
            <i class="fas fa-info-circle me-2"></i>
            Información de la Reserva
          </h6>
          <div class="row">
            <div class="col-md-6">
              <div class="info-item">
                <strong>Código:</strong> {{ reservation.reservation_code }}
              </div>
              <div class="info-item">
                <strong>Cliente:</strong> {{ reservation.user?.name }}
              </div>
              <div class="info-item">
                <strong>Email:</strong> {{ reservation.user?.email }}
              </div>
              <div class="info-item" v-if="reservation.user?.phone">
                <strong>Teléfono:</strong> {{ reservation.user?.phone }}
              </div>
            </div>
            <div class="col-md-6">
              <div class="info-item">
                <strong>Habitación:</strong> {{ reservation.room?.room_number }} - {{ reservation.room?.room_type?.name }}
              </div>
              <div class="info-item">
                <strong>Check-in:</strong> {{ formatDate(reservation.check_in_date) }}
              </div>
              <div class="info-item">
                <strong>Check-out:</strong> {{ formatDate(reservation.check_out_date) }}
              </div>
              <div class="info-item">
                <strong>Huéspedes:</strong> {{ reservation.adults_count + reservation.children_count }}
              </div>
            </div>
          </div>
        </div>

        <!-- Formulario de Check-in -->
        <form @submit.prevent="performCheckIn">
          <!-- Notas del Check-in -->
          <div class="form-section mb-4">
            <h6 class="section-title required">
              <i class="fas fa-sticky-note me-2"></i>
              Notas del Check-in
            </h6>
            <textarea
              v-model="form.notes"
              @blur="showValidationErrors = true"
              @input="validateNotes"
              class="form-control"
              :class="{ 
                'is-invalid': (!form.notes || !form.notes.trim()) && showValidationErrors,
                'is-valid': form.notes && form.notes.trim() && showValidationErrors
              }"
              rows="4"
              placeholder="Escriba observaciones sobre el check-in (ej: 'Habitación limpia y lista', 'Cliente llegó a tiempo', etc.) - OBLIGATORIO"
              required
            ></textarea>
            <div v-if="(!form.notes || !form.notes.trim()) && showValidationErrors" class="invalid-feedback">
              <i class="fas fa-exclamation-circle me-1"></i>
              Las notas del check-in son obligatorias. Debe escribir al menos una observación.
            </div>
            <div v-else-if="form.notes && form.notes.trim() && showValidationErrors" class="valid-feedback">
              <i class="fas fa-check-circle me-1"></i>
              ✓ Notas completadas correctamente
            </div>
            <small class="form-text text-muted mt-2">
              <i class="fas fa-info-circle me-1"></i>
              <strong>Campo obligatorio:</strong> Escriba observaciones sobre el estado de la habitación, la llegada del cliente, o cualquier detalle relevante del check-in.
            </small>
          </div>

          <!-- Verificaciones previas al check-in -->
          <div class="form-section mb-4">
            <h6 class="section-title">
              <i class="fas fa-clipboard-check me-2"></i>
              Verificaciones
            </h6>
            <div class="verification-checks">
              <div class="form-check mb-2">
                <input
                  v-model="verifications.payment_verified"
                  class="form-check-input"
                  type="checkbox"
                  id="payment_verified"
                />
                <label class="form-check-label" for="payment_verified">
                  Pago verificado y confirmado
                </label>
              </div>
              <div class="form-check mb-2">
                <input
                  v-model="verifications.room_ready"
                  class="form-check-input"
                  type="checkbox"
                  id="room_ready"
                />
                <label class="form-check-label" for="room_ready">
                  Habitación limpia y lista para ocupar
                </label>
              </div>
              <div class="form-check mb-2">
                <input
                  v-model="verifications.documents_reviewed"
                  class="form-check-input"
                  type="checkbox"
                  id="documents_reviewed"
                />
                <label class="form-check-label" for="documents_reviewed">
                  Documentos de identidad revisados
                </label>
              </div>
              <div class="form-check mb-2">
                <input
                  v-model="verifications.keys_delivered"
                  class="form-check-input"
                  type="checkbox"
                  id="keys_delivered"
                />
                <label class="form-check-label" for="keys_delivered">
                  Llaves o tarjetas de acceso entregadas
                </label>
              </div>
            </div>
          </div>

          <!-- Error Display -->
          <div v-if="error" class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ error }}
          </div>

          <!-- Success Display -->
          <div v-if="success" class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            {{ success }}
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button @click="$emit('close')" type="button" class="btn btn-secondary" :disabled="processing">
          <i class="fas fa-times me-1"></i>
          Cancelar
        </button>
        <button @click="performCheckIn" type="button" class="btn btn-primary" :disabled="processing || !canPerformCheckIn">
          <i class="fas fa-spinner fa-spin me-1" v-if="processing"></i>
          <i class="fas fa-sign-in-alt me-1" v-else></i>
          {{ processing ? 'Procesando...' : 'Realizar Check-in' }}
        </button>
        
        <!-- Mensaje de ayuda si el botón está deshabilitado -->
        <div v-if="!canPerformCheckIn && !processing" class="mt-2">
          <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Para continuar complete: 
            <span v-if="!verifications.payment_verified" class="text-danger">✗ Verificación de pago</span>
            <span v-if="!verifications.room_ready" class="text-danger">✗ Habitación lista</span>
            <span v-if="!verifications.documents_reviewed" class="text-danger">✗ Documentos revisados</span>
            <span v-if="!notesValid" class="text-danger">✗ Notas obligatorias</span>
          </small>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { adminApi } from '../../../services/api'

export default {
  name: 'CheckInModal',
  props: {
    reservation: {
      type: Object,
      required: true
    }
  },
  emits: ['close', 'success'],
  data() {
    return {
      processing: false,
      error: null,
      success: null,
      errors: {},
      showValidationErrors: false,
      form: {
        notes: '' // Solo mantenemos las notas, eliminamos additional_guests
      },
      verifications: {
        payment_verified: false,
        room_ready: false,
        documents_reviewed: false,
        keys_delivered: false
      }
    }
  },
  computed: {
    canPerformCheckIn() {
      // Verificar que las verificaciones críticas estén marcadas Y que las notas no estén vacías
      return this.verifications.payment_verified && 
             this.verifications.room_ready &&
             this.verifications.documents_reviewed &&
             this.form.notes && 
             this.form.notes.trim().length > 0
    },
    
    notesValid() {
      return this.form.notes && this.form.notes.trim().length > 0
    }
  },
  mounted() {
    // Validar en tiempo real después de que el usuario empiece a escribir
    this.$nextTick(() => {
      const notesTextarea = this.$el.querySelector('textarea')
      if (notesTextarea) {
        notesTextarea.addEventListener('blur', () => {
          this.showValidationErrors = true
        })
      }
    })
  },
  methods: {
    handleOverlayClick() {
      if (!this.processing) {
        this.$emit('close')
      }
    },
    
    validateNotes() {
      // Método para validar las notas en tiempo real
      if (this.form.notes && this.form.notes.trim().length > 0) {
        this.showValidationErrors = true
      }
    },
    
    async performCheckIn() {
      // Mostrar errores de validación
      this.showValidationErrors = true
      
      // Validar que las notas no estén vacías
      if (!this.form.notes || !this.form.notes.trim()) {
        this.error = 'Las notas del check-in son obligatorias. Por favor, escriba al menos una observación.'
        return
      }
      
      if (!this.canPerformCheckIn) {
        this.error = 'Debe completar todas las verificaciones obligatorias y escribir las notas antes de proceder.'
        return
      }
      
      this.processing = true
      this.error = null
      this.success = null
      this.errors = {}
      
      try {
        const checkInData = {
          notes: this.form.notes.trim() // Solo enviamos las notas
        }
        
        console.log('Datos enviados:', checkInData)
        
        const response = await adminApi.checkInReservation(this.reservation.id, checkInData)
        
        this.success = 'Check-in realizado exitosamente'
        
        setTimeout(() => {
          this.$emit('success', response.data)
        }, 1500)
        
      } catch (error) {
        console.error('Error en check-in:', error)
        console.error('Response data:', error.response?.data)
        
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {}
          this.error = error.response.data.message || 'Por favor, corrija los errores en el formulario.'
        } else {
          this.error = error.response?.data?.message || 'Error al realizar el check-in. Por favor, inténtalo nuevamente.'
        }
      } finally {
        this.processing = false
      }
    },
    
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    }
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
}

.modal-container {
  background: white;
  border-radius: 12px;
  max-width: 800px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-header {
  padding: 1.5rem 1.5rem 1rem 1.5rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: between;
  align-items: center;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #495057;
  flex: 1;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.25rem;
  color: #6c757d;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.15s ease-in-out;
}

.btn-close:hover {
  color: #495057;
  background-color: #f8f9fa;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  padding: 1rem 1.5rem 1.5rem 1.5rem;
  border-top: 1px solid #e9ecef;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.reservation-summary {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 1.25rem;
  border: 1px solid #e9ecef;
}

.section-title {
  color: #495057;
  font-weight: 600;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e9ecef;
}

.info-item {
  margin-bottom: 0.75rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.info-item strong {
  color: #495057;
  margin-right: 0.5rem;
}

.form-section {
  background-color: #fdfdfd;
  border: 1px solid #f1f3f4;
  border-radius: 8px;
  padding: 1.25rem;
}

.verification-checks .form-check {
  background-color: white;
  border: 1px solid #e9ecef;
  border-radius: 6px;
  padding: 0.75rem;
  margin-bottom: 0.5rem;
}

.verification-checks .form-check:last-child {
  margin-bottom: 0;
}

.verification-checks .form-check-input:checked {
  background-color: #198754;
  border-color: #198754;
}

.form-control:focus,
.form-select:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.btn {
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.15s ease-in-out;
}

.btn-primary {
  background-color: #007bff;
  border-color: #007bff;
}

.btn-primary:hover {
  background-color: #0056b3;
  border-color: #0056b3;
  transform: translateY(-1px);
}

.btn-primary:disabled {
  background-color: #6c757d;
  border-color: #6c757d;
  transform: none;
}

.btn-secondary {
  background-color: #6c757d;
  border-color: #6c757d;
}

.btn-secondary:hover {
  background-color: #545862;
  border-color: #545862;
  transform: translateY(-1px);
}

.btn-outline-primary {
  color: #007bff;
  border-color: #007bff;
}

.btn-outline-primary:hover {
  background-color: #007bff;
  border-color: #007bff;
  color: white;
}

.btn-outline-danger {
  color: #dc3545;
  border-color: #dc3545;
}

.btn-outline-danger:hover {
  background-color: #dc3545;
  border-color: #dc3545;
  color: white;
}

.alert {
  border-radius: 6px;
  border: none;
  font-weight: 500;
}

.alert-danger {
  background-color: #f8d7da;
  color: #721c24;
}

.alert-success {
  background-color: #d1edff;
  color: #0c5460;
}

.invalid-feedback {
  display: block;
  font-size: 0.875rem;
  color: #dc3545;
  margin-top: 0.25rem;
}

.is-invalid {
  border-color: #dc3545;
}

.section-title.required::after {
  content: " *";
  color: #dc3545;
  font-weight: 700;
}

.form-control.is-valid {
  border-color: #28a745;
}

.form-control.is-valid:focus {
  border-color: #28a745;
  box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.valid-feedback {
  display: block;
  width: 100%;
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: #28a745;
}

.invalid-feedback {
  display: block;
  font-size: 0.875rem;
  color: #dc3545;
  margin-top: 0.25rem;
}

.is-invalid {
  border-color: #dc3545;
}

.is-invalid:focus {
  border-color: #dc3545;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.form-text {
  margin-top: 0.5rem;
  font-size: 0.875rem;
}

.form-text.text-muted {
  color: #6c757d;
}

.form-text strong {
  color: #495057;
}

@media (max-width: 768px) {
  .modal-container {
    margin: 0.5rem;
    max-width: calc(100% - 1rem);
  }
  
  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 1rem;
  }
  
  .modal-footer {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .modal-footer .btn {
    width: 100%;
  }
  
  .reservation-summary .row .col-md-6 {
    margin-bottom: 1rem;
  }
}
</style>