<template>
  <div class="modal-overlay" @click="handleOverlayClick">
    <div class="modal-container" @click.stop>
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-times-circle me-2 text-danger"></i>
          Cancelar Reserva
        </h5>
        <button @click="$emit('close')" class="btn-close" type="button">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div class="modal-body">
        <!-- Advertencia -->
        <div class="alert alert-warning">
          <i class="fas fa-exclamation-triangle me-2"></i>
          <strong>¡Atención!</strong> Esta acción no se puede deshacer. La reserva será cancelada permanentemente.
        </div>

        <!-- Información de la Reserva -->
        <div class="reservation-summary mb-4">
          <h6 class="section-title">
            <i class="fas fa-info-circle me-2"></i>
            Información de la Reserva a Cancelar
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
                <strong>Total:</strong> <span class="amount">${{ formatCurrency(reservation.total_amount) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Estado Actual -->
        <div class="current-status mb-4">
          <h6 class="section-title">
            <i class="fas fa-flag me-2"></i>
            Estado Actual
          </h6>
          <div class="status-card">
            <div class="status-badge">
              <span :class="getStatusBadgeClass(reservation.status)">
                <i :class="getStatusIcon(reservation.status)" class="me-1"></i>
                {{ getStatusText(reservation.status) }}
              </span>
            </div>
            <div class="status-info">
              <div class="info-item">
                <strong>Creada:</strong> {{ formatDateTime(reservation.created_at) }}
              </div>
              <div class="info-item" v-if="reservation.registration?.actual_check_in">
                <strong>Check-in realizado:</strong> {{ formatDateTime(reservation.registration.actual_check_in) }}
              </div>
            </div>
          </div>
        </div>

        <!-- Análisis de Impacto -->
        <div class="impact-analysis mb-4">
          <h6 class="section-title">
            <i class="fas fa-chart-line me-2"></i>
            Análisis de Impacto
          </h6>
          <div class="impact-cards">
            <div class="impact-card" :class="{ 'warning': hasFinancialImpact() }">
              <div class="impact-icon">
                <i class="fas fa-dollar-sign"></i>
              </div>
              <div class="impact-content">
                <h6>Impacto Financiero</h6>
                <p class="mb-1">
                  <strong v-if="hasFinancialImpact()">
                    Posible reembolso: ${{ formatCurrency(getRefundAmount()) }}
                  </strong>
                  <span v-else>Sin impacto financiero</span>
                </p>
                <small class="text-muted">{{ getFinancialMessage() }}</small>
              </div>
            </div>
            
            <div class="impact-card" :class="{ 'warning': hasOperationalImpact() }">
              <div class="impact-icon">
                <i class="fas fa-bed"></i>
              </div>
              <div class="impact-content">
                <h6>Impacto Operacional</h6>
                <p class="mb-1">
                  <strong v-if="hasOperationalImpact()">
                    Habitación {{ reservation.room?.room_number }} se liberará
                  </strong>
                  <span v-else>Sin impacto operacional</span>
                </p>
                <small class="text-muted">{{ getOperationalMessage() }}</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Formulario de Cancelación -->
        <form @submit.prevent="performCancellation">
          <!-- Motivo de Cancelación -->
          <div class="form-section mb-4">
            <h6 class="section-title required">
              <i class="fas fa-comment-alt me-2"></i>
              Motivo de Cancelación
            </h6>
            
            <!-- Motivos predefinidos -->
            <div class="predefined-reasons mb-3">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-check mb-2">
                    <input
                      v-model="selectedReason"
                      class="form-check-input"
                      type="radio"
                      name="cancellation_reason"
                      id="client_request"
                      value="Solicitud del cliente"
                    />
                    <label class="form-check-label" for="client_request">
                      Solicitud del cliente
                    </label>
                  </div>
                  <div class="form-check mb-2">
                    <input
                      v-model="selectedReason"
                      class="form-check-input"
                      type="radio"
                      name="cancellation_reason"
                      id="payment_issues"
                      value="Problemas de pago"
                    />
                    <label class="form-check-label" for="payment_issues">
                      Problemas de pago
                    </label>
                  </div>
                  <div class="form-check mb-2">
                    <input
                      v-model="selectedReason"
                      class="form-check-input"
                      type="radio"
                      name="cancellation_reason"
                      id="overbooking"
                      value="Sobrereserva del hotel"
                    />
                    <label class="form-check-label" for="overbooking">
                      Sobrereserva del hotel
                    </label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-check mb-2">
                    <input
                      v-model="selectedReason"
                      class="form-check-input"
                      type="radio"
                      name="cancellation_reason"
                      id="maintenance_issues"
                      value="Problemas de mantenimiento"
                    />
                    <label class="form-check-label" for="maintenance_issues">
                      Problemas de mantenimiento
                    </label>
                  </div>
                  <div class="form-check mb-2">
                    <input
                      v-model="selectedReason"
                      class="form-check-input"
                      type="radio"
                      name="cancellation_reason"
                      id="force_majeure"
                      value="Fuerza mayor"
                    />
                    <label class="form-check-label" for="force_majeure">
                      Fuerza mayor
                    </label>
                  </div>
                  <div class="form-check mb-2">
                    <input
                      v-model="selectedReason"
                      class="form-check-input"
                      type="radio"
                      name="cancellation_reason"
                      id="other"
                      value="Otro"
                    />
                    <label class="form-check-label" for="other">
                      Otro motivo
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Detalles adicionales -->
            <div class="additional-details">
              <label class="form-label">
                <i class="fas fa-edit me-1"></i>
                Detalles Adicionales *
              </label>
              <textarea
                v-model="form.cancellation_reason"
                class="form-control"
                :class="{ 'is-invalid': errors.cancellation_reason }"
                rows="4"
                placeholder="Proporcione detalles específicos sobre el motivo de la cancelación..."
                required
              ></textarea>
              <div v-if="errors.cancellation_reason" class="invalid-feedback">
                {{ errors.cancellation_reason[0] }}
              </div>
              <small class="form-text text-muted">
                Explique con detalle las circunstancias que llevaron a esta cancelación.
              </small>
            </div>
          </div>

          <!-- Acciones post-cancelación -->
          <div class="form-section mb-4">
            <h6 class="section-title">
              <i class="fas fa-tasks me-2"></i>
              Acciones Post-Cancelación
            </h6>
            
            <div class="post-actions">
              <div class="form-check mb-2">
                <input
                  v-model="postActions.notify_client"
                  class="form-check-input"
                  type="checkbox"
                  id="notify_client"
                />
                <label class="form-check-label" for="notify_client">
                  Notificar al cliente por email
                </label>
              </div>
              
              <div class="form-check mb-2" v-if="hasFinancialImpact()">
                <input
                  v-model="postActions.process_refund"
                  class="form-check-input"
                  type="checkbox"
                  id="process_refund"
                />
                <label class="form-check-label" for="process_refund">
                  Iniciar proceso de reembolso
                </label>
              </div>
              
              <div class="form-check mb-2">
                <input
                  v-model="postActions.block_dates"
                  class="form-check-input"
                  type="checkbox"
                  id="block_dates"
                />
                <label class="form-check-label" for="block_dates">
                  Bloquear fechas temporalmente para mantenimiento
                </label>
              </div>
              
              <div class="form-check mb-2">
                <input
                  v-model="postActions.update_pricing"
                  class="form-check-input"
                  type="checkbox"
                  id="update_pricing"
                />
                <label class="form-check-label" for="update_pricing">
                  Revisar precios para fechas liberadas
                </label>
              </div>
            </div>
          </div>

          <!-- Confirmación final -->
          <div class="final-confirmation mb-4">
            <div class="confirmation-box">
              <div class="form-check">
                <input
                  v-model="finalConfirmation"
                  class="form-check-input"
                  type="checkbox"
                  id="final_confirmation"
                  required
                />
                <label class="form-check-label" for="final_confirmation">
                  <strong>Confirmo que entiendo las consecuencias de esta cancelación y autorizo proceder</strong>
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
          <i class="fas fa-arrow-left me-1"></i>
          Mantener Reserva
        </button>
        <button @click="performCancellation" type="button" class="btn btn-danger" :disabled="processing || !canProceed">
          <i class="fas fa-spinner fa-spin me-1" v-if="processing"></i>
          <i class="fas fa-times-circle me-1" v-else></i>
          {{ processing ? 'Cancelando...' : 'Cancelar Reserva' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { adminApi } from '../../../services/api'

export default {
  name: 'CancelReservationModal',
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
      selectedReason: '',
      finalConfirmation: false,
      form: {
        cancellation_reason: ''
      },
      postActions: {
        notify_client: true,
        process_refund: false,
        block_dates: false,
        update_pricing: false
      }
    }
  },
  computed: {
    canProceed() {
      return this.form.cancellation_reason.trim().length > 10 && this.finalConfirmation
    }
  },
  watch: {
    selectedReason(newReason) {
      if (newReason && newReason !== 'Otro') {
        this.form.cancellation_reason = newReason + ': '
      } else if (newReason === 'Otro') {
        this.form.cancellation_reason = ''
      }
    }
  },
  methods: {
    handleOverlayClick() {
      if (!this.processing) {
        this.$emit('close')
      }
    },
    
    async performCancellation() {
      if (!this.canProceed) {
        this.error = 'Debe completar todos los campos requeridos y confirmar la acción.'
        return
      }
      
      this.processing = true
      this.error = null
      this.success = null
      this.errors = {}
      
      try {
        const cancellationData = {
          cancellation_reason: this.form.cancellation_reason,
          post_actions: this.postActions
        }
        
        const response = await adminApi.cancelReservation(this.reservation.id, cancellationData)
        
        this.success = 'Reserva cancelada exitosamente'
        
        // Esperar un momento para mostrar el mensaje de éxito
        setTimeout(() => {
          this.$emit('success', response.data)
        }, 2000)
        
      } catch (error) {
        console.error('Error al cancelar reserva:', error)
        
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors || {}
          this.error = 'Por favor, corrija los errores en el formulario.'
        } else {
          this.error = error.response?.data?.message || 'Error al cancelar la reserva. Por favor, inténtalo nuevamente.'
        }
      } finally {
        this.processing = false
      }
    },
    
    hasFinancialImpact() {
      // Hay impacto financiero si ya se procesó algún pago
      return this.reservation.payments && this.reservation.payments.some(p => p.status === 'verified')
    },
    
    hasOperationalImpact() {
      // Hay impacto operacional si está confirmada o con check-in
      return ['confirmed', 'checked_in'].includes(this.reservation.status)
    },
    
    getRefundAmount() {
      if (!this.hasFinancialImpact()) return 0
      
      // Calcular reembolso basado en políticas (simplificado)
      const totalPaid = this.reservation.payments
        ?.filter(p => p.status === 'verified')
        .reduce((sum, p) => sum + p.amount, 0) || 0
      
      // Política simple: reembolso del 80% si se cancela con más de 24h de anticipación
      const checkInDate = new Date(this.reservation.check_in_date)
      const now = new Date()
      const hoursUntilCheckIn = (checkInDate - now) / (1000 * 60 * 60)
      
      if (hoursUntilCheckIn > 24) {
        return totalPaid * 0.8
      } else {
        return totalPaid * 0.5
      }
    },
    
    getFinancialMessage() {
      if (!this.hasFinancialImpact()) {
        return 'No se han procesado pagos para esta reserva'
      }
      
      const checkInDate = new Date(this.reservation.check_in_date)
      const now = new Date()
      const hoursUntilCheckIn = (checkInDate - now) / (1000 * 60 * 60)
      
      if (hoursUntilCheckIn > 24) {
        return 'Reembolso del 80% según política de cancelación'
      } else {
        return 'Reembolso del 50% por cancelación tardía'
      }
    },
    
    getOperationalMessage() {
      if (this.reservation.status === 'checked_in') {
        return 'Se requiere check-out inmediato'
      } else if (this.reservation.status === 'confirmed') {
        return 'Habitación se liberará para nuevas reservas'
      } else {
        return 'Sin impacto en operaciones actuales'
      }
    },
    
    formatDate(dateString) {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      })
    },
    
    formatDateTime(dateTimeString) {
      if (!dateTimeString) return 'N/A'
      return new Date(dateTimeString).toLocaleString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    
    formatCurrency(amount) {
      if (amount === null || amount === undefined) return '0.00'
      return new Intl.NumberFormat('es-ES', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(parseFloat(amount))
    },
    
    getStatusBadgeClass(status) {
      const classes = {
        'pending_payment': 'badge bg-warning text-dark',
        'confirmed': 'badge bg-success',
        'checked_in': 'badge bg-primary',
        'completed': 'badge bg-info',
        'cancelled': 'badge bg-danger'
      }
      return classes[status] || 'badge bg-secondary'
    },
    
    getStatusIcon(status) {
      const icons = {
        'pending_payment': 'fas fa-hourglass-half',
        'confirmed': 'fas fa-check-circle',
        'checked_in': 'fas fa-sign-in-alt',
        'completed': 'fas fa-check-double',
        'cancelled': 'fas fa-times-circle'
      }
      return icons[status] || 'fas fa-question-circle'
    },
    
    getStatusText(status) {
      const texts = {
        'pending_payment': 'Pago Pendiente',
        'confirmed': 'Confirmada',
        'checked_in': 'Check-in',
        'completed': 'Completada',
        'cancelled': 'Cancelada'
      }
      return texts[status] || 'Desconocido'
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
  max-width: 900px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-header {
  padding: 1.5rem 1.5rem 1rem 1.5rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
  border-radius: 12px 12px 0 0;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  flex: 1;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.25rem;
  color: rgba(255, 255, 255, 0.8);
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.15s ease-in-out;
}

.btn-close:hover {
  color: white;
  background-color: rgba(255, 255, 255, 0.1);
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  padding: 1rem 1.5rem 1.5rem 1.5rem;
  border-top: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
}

.reservation-summary {
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 1.25rem;
  border: 1px solid #e9ecef;
}

.current-status {
  background-color: #fff3cd;
  border-radius: 8px;
  padding: 1.25rem;
  border: 1px solid #ffeaa7;
}

.section-title {
  color: #495057;
  font-weight: 600;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e9ecef;
}

.section-title.required::after {
  content: " *";
  color: #dc3545;
  font-weight: 700;
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

.amount {
  font-weight: 700;
  color: #dc3545;
  font-size: 1.1rem;
}

.status-card {
  background: white;
  border-radius: 8px;
  padding: 1.25rem;
  border: 1px solid #e9ecef;
}

.status-badge {
  margin-bottom: 1rem;
}

.impact-analysis {
  background-color: #f0f8ff;
  border-radius: 8px;
  padding: 1.25rem;
  border: 1px solid #b3d9ff;
}

.impact-cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.impact-card {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  border: 1px solid #e9ecef;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.impact-card.warning {
  border-color: #ffc107;
  background-color: #fff8e1;
}

.impact-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background-color: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6c757d;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.impact-card.warning .impact-icon {
  background-color: #ffc107;
  color: white;
}

.impact-content h6 {
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #495057;
}

.form-section {
  background-color: #fdfdfd;
  border: 1px solid #f1f3f4;
  border-radius: 8px;
  padding: 1.25rem;
}

.predefined-reasons .form-check {
  background-color: white;
  border: 1px solid #e9ecef;
  border-radius: 6px;
  padding: 0.75rem;
  margin-bottom: 0.5rem;
  transition: all 0.15s ease-in-out;
}

.predefined-reasons .form-check:hover {
  border-color: #007bff;
  background-color: #f8f9fa;
}

.predefined-reasons .form-check-input:checked + .form-check-label {
  font-weight: 600;
  color: #007bff;
}

.additional-details {
  background: #fff;
  border: 1px solid #e9ecef;
  border-radius: 6px;
  padding: 1rem;
  margin-top: 1rem;
}

.post-actions {
  background: white;
  border: 1px solid #e9ecef;
  border-radius: 6px;
  padding: 1rem;
}

.post-actions .form-check {
  background-color: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 4px;
  padding: 0.75rem;
  margin-bottom: 0.5rem;
  transition: all 0.15s ease-in-out;
}

.post-actions .form-check:hover {
  background-color: #e9ecef;
}

.post-actions .form-check-input:checked + .form-check-label {
  font-weight: 500;
  color: #495057;
}

.final-confirmation {
  background-color: #fff5f5;
  border-radius: 8px;
  padding: 1.25rem;
  border: 1px solid #f8d7da;
}

.confirmation-box {
  background: white;
  border: 2px solid #dc3545;
  border-radius: 6px;
  padding: 1rem;
}

.confirmation-box .form-check-input:checked {
  background-color: #dc3545;
  border-color: #dc3545;
}

.form-control:focus,
.form-select:focus {
  border-color: #dc3545;
  box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.btn {
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.15s ease-in-out;
}

.btn-danger {
  background-color: #dc3545;
  border-color: #dc3545;
}

.btn-danger:hover {
  background-color: #c82333;
  border-color: #c82333;
  transform: translateY(-1px);
}

.btn-danger:disabled {
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

.badge {
  font-size: 0.75rem;
  font-weight: 500;
  padding: 0.375rem 0.75rem;
}

.alert {
  border-radius: 6px;
  border: none;
  font-weight: 500;
}

.alert-warning {
  background-color: #fff3cd;
  color: #856404;
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
  
  .impact-cards {
    grid-template-columns: 1fr;
  }
  
  .impact-card {
    flex-direction: column;
    text-align: center;
  }
  
  .reservation-summary .row .col-md-6 {
    margin-bottom: 1rem;
  }
  
  .predefined-reasons .row .col-md-6 {
    margin-bottom: 1rem;
  }
}
</style>