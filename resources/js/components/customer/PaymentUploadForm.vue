<template>
  <div>
    <!-- Información de la reserva -->
    <div class="alert alert-info mb-4">
      <h6>Información de Pago</h6>
      <p class="mb-2">
        <strong>Reserva:</strong> {{ reservation.reservation_code }}<br>
        <strong>Total:</strong> {{ reservation.formatted_total }}
      </p>
      <p class="mb-0">
        <strong>Datos de pago:</strong><br>
        {{ reservation.branch.qr_payment_info }}
      </p>
    </div>

    <!-- Formulario -->
    <form @submit.prevent="uploadPayment">
      <div class="mb-3">
        <label for="payment_reference" class="form-label">Número de Operación *</label>
        <input 
          v-model="form.payment_reference" 
          type="text" 
          class="form-control" 
          id="payment_reference"
          :class="{ 'is-invalid': errors.payment_reference }"
          placeholder="Ingresa el número de operación"
          required
        >
        <div v-if="errors.payment_reference" class="invalid-feedback">
          {{ errors.payment_reference[0] }}
        </div>
      </div>

      <div class="mb-3">
        <label for="payment_proof" class="form-label">Comprobante de Pago *</label>
        <input 
          @change="onFileChange" 
          type="file" 
          class="form-control" 
          id="payment_proof"
          accept="image/*"
          :class="{ 'is-invalid': errors.payment_proof }"
          required
        >
        <div class="form-text">
          Sube una foto clara del comprobante de pago (JPG, PNG, máximo 2MB)
        </div>
        <div v-if="errors.payment_proof" class="invalid-feedback">
          {{ errors.payment_proof[0] }}
        </div>
      </div>

      <!-- Preview de la imagen -->
      <div v-if="imagePreview" class="mb-3">
        <label class="form-label">Vista previa:</label>
        <div class="text-center">
          <img :src="imagePreview" alt="Vista previa" class="img-fluid" style="max-height: 200px;">
        </div>
      </div>

      <!-- Botones -->
      <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-secondary" @click="$emit('cancel')">
          Cancelar
        </button>
        <button type="submit" class="btn btn-success" :disabled="loading">
          <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
          {{ loading ? 'Subiendo...' : 'Subir Comprobante' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import { ref } from 'vue'
import { customerApi } from '../../services/api.js'

export default {
  name: 'PaymentUploadForm',
  props: {
    reservation: {
      type: Object,
      required: true
    }
  },
  emits: ['success', 'cancel'],
  setup(props, { emit }) {
    const form = ref({
      reservation_id: props.reservation.id,
      payment_reference: '',
      payment_proof: null
    })
    
    const errors = ref({})
    const loading = ref(false)
    const imagePreview = ref(null)

    const onFileChange = (event) => {
      const file = event.target.files[0]
      if (file) {
        form.value.payment_proof = file
        
        // Crear preview
        const reader = new FileReader()
        reader.onload = (e) => {
          imagePreview.value = e.target.result
        }
        reader.readAsDataURL(file)
      }
    }

    const uploadPayment = async () => {
      try {
        loading.value = true
        errors.value = {}
        
        const formData = new FormData()
        formData.append('reservation_id', form.value.reservation_id)
        formData.append('payment_reference', form.value.payment_reference)
        formData.append('payment_proof', form.value.payment_proof)
        
        await customerApi.uploadPaymentProof(formData)
        
        alert('Comprobante subido exitosamente. Será validado por nuestro equipo.')
        emit('success')
      } catch (error) {
        console.error('Error uploading payment proof:', error)
        
        if (error.response?.data?.errors) {
          errors.value = error.response.data.errors
        } else {
          alert('Error al subir el comprobante: ' + (error.response?.data?.message || error.message))
        }
      } finally {
        loading.value = false
      }
    }

    return {
      form,
      errors,
      loading,
      imagePreview,
      onFileChange,
      uploadPayment
    }
  }
}
</script>

<style scoped>
.btn-success {
  background-color: #28a745;
  border-color: #28a745;
}

.btn-success:hover {
  background-color: #218838;
  border-color: #1e7e34;
}

.form-control:focus {
  border-color: #28a745;
  box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}
</style>