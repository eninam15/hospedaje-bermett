<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 class="page-title">
            <i class="fas fa-file-invoice me-2"></i>
            Detalles de Reserva
          </h2>
          <router-link to="/customer/reservations" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>
            Volver a Mis Reservas
          </router-link>
        </div>
      </div>
    </div>
    
    <div class="row">
      <div class="col-md-8">
        <!-- Información Principal -->
        <div class="card modern-card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
              <i class="fas fa-info-circle me-2"></i>
              Información de la Reserva
            </h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="info-item">
                  <label class="info-label">Código de Reserva:</label>
                  <span class="info-value">{{ reservation.code }}</span>
                </div>
                <div class="info-item">
                  <label class="info-label">Estado:</label>
                  <span class="badge" :class="getStatusClass(reservation.status)">
                    {{ getStatusText(reservation.status) }}
                  </span>
                </div>
                <div class="info-item">
                  <label class="info-label">Fecha de Reserva:</label>
                  <span class="info-value">{{ formatDate(reservation.created_at) }}</span>
                </div>
                <div class="info-item">
                  <label class="info-label">Check-in:</label>
                  <span class="info-value">{{ formatDate(reservation.check_in_date) }} - 14:00</span>
                </div>
                <div class="info-item">
                  <label class="info-label">Check-out:</label>
                  <span class="info-value">{{ formatDate(reservation.check_out_date) }} - 12:00</span>
                </div>
              </div>
              <div class="col-md-6">
                <div class="info-item">
                  <label class="info-label">Habitación:</label>
                  <span class="info-value">{{ reservation.room_type }} - {{ reservation.room_number }}</span>
                </div>
                <div class="info-item">
                  <label class="info-label">Sucursal:</label>
                  <span class="info-value">{{ reservation.branch_name }}</span>
                </div>
                <div class="info-item">
                  <label class="info-label">Huéspedes:</label>
                  <span class="info-value">{{ reservation.adults_count }} Adulto{{ reservation.adults_count > 1 ? 's' : '' }}{{ reservation.children_count > 0 ? `, ${reservation.children_count} Niño${reservation.children_count > 1 ? 's' : ''}` : '' }}</span>
                </div>
                <div class="info-item">
                  <label class="info-label">Estacionamiento:</label>
                  <span class="info-value">{{ reservation.needs_parking ? 'Sí (Gratuito)' : 'No' }}</span>
                </div>
                <div class="info-item">
                  <label class="info-label">Método de Pago:</label>
                  <span class="info-value">{{ getPaymentMethodText(reservation.payment_method) }}</span>
                </div>
              </div>
            </div>
            
            <!-- Solicitudes Especiales -->
            <div v-if="reservation.special_requests" class="mt-4">
              <h6 class="text-muted mb-2">
                <i class="fas fa-comment-alt me-1"></i>
                Solicitudes Especiales:
              </h6>
              <div class="special-requests-box">
                {{ reservation.special_requests }}
              </div>
            </div>
          </div>
        </div>
        
        <!-- Desglose de Servicios -->
        <div class="card modern-card mt-4">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0">
              <i class="fas fa-list me-2"></i>
              Desglose de Servicios
            </h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-sm table-striped">
                <thead class="table-dark">
                  <tr>
                    <th>Servicio</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-end">Precio Unit.</th>
                    <th class="text-end">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <strong>Habitación {{ reservation.room_type }}</strong>
                      <small class="text-muted d-block">{{ reservation.nights }} noche{{ reservation.nights > 1 ? 's' : '' }}</small>
                    </td>
                    <td class="text-center">{{ reservation.nights }}</td>
                    <td class="text-end">Bs. {{ (reservation.total_amount / reservation.nights).toFixed(2) }}</td>
                    <td class="text-end">Bs. {{ reservation.total_amount.toFixed(2) }}</td>
                  </tr>
                  <tr v-if="reservation.needs_parking">
                    <td>
                      <strong>Estacionamiento</strong>
                      <small class="text-muted d-block">{{ reservation.nights }} noche{{ reservation.nights > 1 ? 's' : '' }}</small>
                    </td>
                    <td class="text-center">{{ reservation.nights }}</td>
                    <td class="text-end text-success">Bs. 0.00 (Gratuito)</td>
                    <td class="text-end text-success">Bs. 0.00</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Sidebar -->
      <div class="col-md-4">
        <!-- Resumen de Pago -->
        <div class="card modern-card">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0">
              <i class="fas fa-calculator me-2"></i>
              Resumen de Pago
            </h5>
          </div>
          <div class="card-body">
            <div class="payment-summary">
              <div class="summary-item">
                <span>Habitación ({{ reservation.nights }} noche{{ reservation.nights > 1 ? 's' : '' }}):</span>
                <span>Bs. {{ reservation.total_amount.toFixed(2) }}</span>
              </div>
              <div class="summary-item">
                <span>Estacionamiento:</span>
                <span class="text-success">Bs. 0.00</span>
              </div>
              <div class="summary-item">
                <span>Servicios adicionales:</span>
                <span>Bs. 0.00</span>
              </div>
              <hr class="my-3">
              <div class="summary-total">
                <span class="fw-bold">Total:</span>
                <span class="fw-bold text-success fs-5">Bs. {{ reservation.total_amount.toFixed(2) }}</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Acciones -->
        <div class="card modern-card mt-3">
          <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
              <i class="fas fa-tools me-2"></i>
              Acciones
            </h5>
          </div>
          <div class="card-body">
            <button 
              @click="downloadPDF" 
              class="btn btn-primary btn-action w-100 mb-3"
              :disabled="loading"
            >
              <span v-if="loading">
                <span class="spinner-border spinner-border-sm me-2"></span>
                Generando PDF...
              </span>
              <span v-else>
                <i class="fas fa-download me-2"></i>
                Descargar Boleta PDF
              </span>
            </button>
            
            <!--<button class="btn btn-outline-warning btn-action w-100 mb-3">
              <i class="fas fa-edit me-2"></i>
              Modificar Reserva
            </button>
            
            <button 
              v-if="reservation.status === 'confirmed'"
              class="btn btn-outline-danger btn-action w-100"
            >
              <i class="fas fa-times me-2"></i>
              Cancelar Reserva
            </button>-->
          </div>
        </div>
        
        <!-- Información Adicional -->
        <div class="card modern-card mt-3">
          <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">
              <i class="fas fa-info me-2"></i>
              Información Importante
            </h5>
          </div>
          <div class="card-body">
            <div class="info-box">
              <h6 class="text-primary">
                <i class="fas fa-clock me-1"></i>
                Horarios
              </h6>
              <p class="mb-2"><strong>Check-in:</strong> A partir de las 14:00</p>
              <p class="mb-3"><strong>Check-out:</strong> Hasta las 12:00</p>
              
              <h6 class="text-primary">
                <i class="fas fa-exclamation-triangle me-1"></i>
                Políticas
              </h6>
              <p class="mb-2"><strong>Cancelación:</strong> 24 horas antes</p>
              <p class="mb-0"><strong>Modificación:</strong> Sujeto a disponibilidad</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import jsPDF from 'jspdf'
import autoTable from 'jspdf-autotable'

export default {
  name: 'ReservationDetailsView',
  data() {
    return {
      loading: false,
      reservationId: this.$route.params.id,
      reservation: {
        code: 'RES456789',
        status: 'confirmed',
        created_at: '2025-07-16T10:30:00Z',
        check_in_date: '2025-07-20',
        check_out_date: '2025-07-22',
        room_type: 'Suite Premium',
        room_number: '205',
        branch_name: 'Villa Caluyo - Zona Sur',
        branch_category: '5 Estrellas',
        branch_address: 'Av. Ballivián 1234, Villa Caluyo, La Paz',
        branch_phone: '+591 2 2345678',
        adults_count: 2,
        children_count: 1,
        needs_parking: true,
        payment_method: 'qr',
        special_requests: 'Habitación en piso alto con vista al jardín. Cama extra para niño.',
        total_amount: 450.00,
        nights: 2,
        guest_name: 'Juan Carlos Pérez',
        guest_email: 'juan.perez@email.com',
        guest_phone: '+591 70123456'
      }
    }
  },
  methods: {
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('es-BO', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      })
    },
    
    getStatusClass(status) {
      const classes = {
        'confirmed': 'bg-success',
        'pending': 'bg-warning',
        'cancelled': 'bg-danger',
        'completed': 'bg-info'
      }
      return classes[status] || 'bg-secondary'
    },
    
    getStatusText(status) {
      const texts = {
        'confirmed': 'Confirmada',
        'pending': 'Pendiente',
        'cancelled': 'Cancelada',
        'completed': 'Completada'
      }
      return texts[status] || 'Desconocido'
    },
    
    getPaymentMethodText(method) {
      const texts = {
        'qr': 'QR / Transferencia',
        'cash': 'Efectivo al llegar',
        'card': 'Tarjeta de Crédito'
      }
      return texts[method] || 'Desconocido'
    },
    
    async downloadPDF() {
      try {
        this.loading = true
        
        const doc = new jsPDF()
        const pageWidth = doc.internal.pageSize.width
        const pageHeight = doc.internal.pageSize.height
        
        // Configuración de colores corporativos
        const primaryColor = [52, 120, 186] // Azul corporativo
        const secondaryColor = [108, 117, 125] // Gris
        const successColor = [40, 167, 69] // Verde
        const darkColor = [52, 58, 64] // Negro suave
        
        // Header corporativo con fondo
        doc.setFillColor(...primaryColor)
        doc.rect(0, 0, pageWidth, 35, 'F')
        
        // Logo/Título principal
        doc.setTextColor(255, 255, 255)
        doc.setFontSize(24)
        doc.setFont('helvetica', 'bold')
        doc.text('BOLETA DE RESERVA', pageWidth / 2, 20, { align: 'center' })
        
        // Subtítulo
        doc.setFontSize(12)
        doc.setFont('helvetica', 'normal')
        doc.text('Comprobante de Reserva Hotelera', pageWidth / 2, 28, { align: 'center' })
        
        // Información de la empresa
        let yPos = 45
        doc.setTextColor(...darkColor)
        doc.setFontSize(16)
        doc.setFont('helvetica', 'bold')
        doc.text(this.reservation.branch_name, 20, yPos)
        
        yPos += 8
        doc.setFontSize(10)
        doc.setFont('helvetica', 'normal')
        doc.setTextColor(...secondaryColor)
        doc.text(`Categoría: ${this.reservation.branch_category}`, 20, yPos)
        
        yPos += 5
        doc.text(`Dirección: ${this.reservation.branch_address}`, 20, yPos)
        
        yPos += 5
        doc.text(`Teléfono: ${this.reservation.branch_phone}`, 20, yPos)
        
        // Fecha y hora de emisión (esquina superior derecha)
        doc.setFontSize(9)
        doc.setTextColor(...secondaryColor)
        const currentDate = new Date().toLocaleDateString('es-BO', {
          year: 'numeric',
          month: 'long',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        })
        doc.text(`Fecha de emisión: ${currentDate}`, pageWidth - 20, 45, { align: 'right' })
        
        // Línea separadora elegante
        yPos += 15
        doc.setLineWidth(0.8)
        doc.setDrawColor(...primaryColor)
        doc.line(20, yPos, pageWidth - 20, yPos)
        
        // Información de la reserva en tabla elegante
        yPos += 10
        
        const reservationData = [
          ['Código de Reserva', this.reservation.code],
          ['Estado', this.getStatusText(this.reservation.status)],
          ['Fecha de Reserva', this.formatDate(this.reservation.created_at)],
          ['Huésped Principal', this.reservation.guest_name],
          ['Email', this.reservation.guest_email],
          ['Teléfono', this.reservation.guest_phone],
          ['Habitación', `${this.reservation.room_type} - ${this.reservation.room_number}`],
          ['Check-in', `${this.formatDate(this.reservation.check_in_date)} - 14:00 hrs`],
          ['Check-out', `${this.formatDate(this.reservation.check_out_date)} - 12:00 hrs`],
          ['Duración', `${this.reservation.nights} noche${this.reservation.nights > 1 ? 's' : ''}`],
          ['Huéspedes', `${this.reservation.adults_count} adulto${this.reservation.adults_count > 1 ? 's' : ''}${this.reservation.children_count > 0 ? `, ${this.reservation.children_count} niño${this.reservation.children_count > 1 ? 's' : ''}` : ''}`],
          ['Estacionamiento', this.reservation.needs_parking ? 'Incluido (Gratuito)' : 'No solicitado'],
          ['Método de Pago', this.getPaymentMethodText(this.reservation.payment_method)]
        ]
        
        autoTable(doc, {
          startY: yPos,
          body: reservationData,
          theme: 'striped',
          styles: {
            fontSize: 10,
            cellPadding: 4,
            lineColor: [224, 224, 224],
            lineWidth: 0.1
          },
          columnStyles: {
            0: { 
              cellWidth: 50,
              fontStyle: 'bold',
              textColor: darkColor,
              fillColor: [248, 249, 250]
            },
            1: { 
              cellWidth: 120,
              textColor: darkColor
            }
          },
          alternateRowStyles: {
            fillColor: [248, 249, 250]
          }
        })
        
        // Solicitudes especiales
        yPos = doc.lastAutoTable.finalY + 15
        if (this.reservation.special_requests) {
          doc.setFontSize(12)
          doc.setFont('helvetica', 'bold')
          doc.setTextColor(...darkColor)
          doc.text('SOLICITUDES ESPECIALES:', 20, yPos)
          
          yPos += 8
          doc.setFontSize(10)
          doc.setFont('helvetica', 'normal')
          doc.setTextColor(...secondaryColor)
          
          const splitRequests = doc.splitTextToSize(this.reservation.special_requests, pageWidth - 40)
          splitRequests.forEach(line => {
            doc.text(line, 20, yPos)
            yPos += 5
          })
          
          yPos += 5
        }
        
        // Desglose de costos con diseño profesional
        yPos += 10
        doc.setFontSize(14)
        doc.setFont('helvetica', 'bold')
        doc.setTextColor(...darkColor)
        doc.text('DESGLOSE DE COSTOS', 20, yPos)
        
        yPos += 10
        
        const costData = [
          ['Concepto', 'Cantidad', 'Precio Unitario', 'Subtotal'],
          [
            `Habitación ${this.reservation.room_type}`,
            `${this.reservation.nights} noche${this.reservation.nights > 1 ? 's' : ''}`,
            `Bs. ${(this.reservation.total_amount / this.reservation.nights).toFixed(2)}`,
            `Bs. ${this.reservation.total_amount.toFixed(2)}`
          ]
        ]
        
        if (this.reservation.needs_parking) {
          costData.push([
            'Estacionamiento',
            `${this.reservation.nights} noche${this.reservation.nights > 1 ? 's' : ''}`,
            'Bs. 0.00 (Gratuito)',
            'Bs. 0.00'
          ])
        }
        
        autoTable(doc, {
          startY: yPos,
          head: [costData[0]],
          body: costData.slice(1),
          theme: 'grid',
          styles: {
            fontSize: 10,
            cellPadding: 6,
            lineColor: [224, 224, 224],
            lineWidth: 0.5
          },
          headStyles: {
            fillColor: primaryColor,
            textColor: [255, 255, 255],
            fontStyle: 'bold',
            fontSize: 11
          },
          columnStyles: {
            0: { cellWidth: 70 },
            1: { cellWidth: 40, halign: 'center' },
            2: { cellWidth: 40, halign: 'right' },
            3: { cellWidth: 40, halign: 'right' }
          }
        })
        
        // Total destacado
        yPos = doc.lastAutoTable.finalY + 10
        doc.setFillColor(...successColor)
        doc.rect(pageWidth - 90, yPos - 8, 70, 20, 'F')
        
        doc.setFontSize(16)
        doc.setFont('helvetica', 'bold')
        doc.setTextColor(255, 255, 255)
        doc.text(`TOTAL: Bs. ${this.reservation.total_amount.toFixed(2)}`, pageWidth - 20, yPos + 2, { align: 'right' })
        
        // Información importante
        yPos += 35
        doc.setFontSize(12)
        doc.setFont('helvetica', 'bold')
        doc.setTextColor(...darkColor)
        doc.text('INFORMACIÓN IMPORTANTE:', 20, yPos)
        
        yPos += 8
        doc.setFontSize(10)
        doc.setFont('helvetica', 'normal')
        doc.setTextColor(...secondaryColor)
        
        const importantInfo = [
          '• Presentar esta boleta al momento del check-in',
          '• Check-in: A partir de las 14:00 hrs',
          '• Check-out: Hasta las 12:00 hrs',
          '• Cancelaciones: Contactar con 24 hrs de anticipación',
          '• Modificaciones: Sujetas a disponibilidad',
          this.reservation.payment_method === 'qr' ? '• Para pago QR: Subir comprobante en "Mis Reservas"' : '• Para pago en efectivo: Presentar esta boleta al llegar',
          this.reservation.needs_parking ? '• Estacionamiento gratuito incluido' : null
        ].filter(Boolean)
        
        importantInfo.forEach(info => {
          doc.text(info, 20, yPos)
          yPos += 6
        })
        
        // Footer elegante
        yPos = pageHeight - 30
        doc.setLineWidth(0.5)
        doc.setDrawColor(...primaryColor)
        doc.line(20, yPos, pageWidth - 20, yPos)
        
        yPos += 10
        doc.setFontSize(11)
        doc.setFont('helvetica', 'bold')
        doc.setTextColor(...primaryColor)
        doc.text('¡Gracias por elegirnos!', pageWidth / 2, yPos, { align: 'center' })
        
        yPos += 6
        doc.setFontSize(9)
        doc.setFont('helvetica', 'normal')
        doc.setTextColor(...secondaryColor)
        doc.text('Esperamos que disfrute su estadía', pageWidth / 2, yPos, { align: 'center' })
        
        // Guardar el PDF
        doc.save(`Boleta_Reserva_${this.reservation.code}.pdf`)
        
      } catch (error) {
        console.error('Error generating PDF:', error)
        alert('Error al generar el PDF. Inténtalo de nuevo.')
      } finally {
        this.loading = false
      }
    }
  },
  
  mounted() {
    console.log('ReservationDetailsView mounted, ID:', this.reservationId)
    // Aquí normalmente cargarías los datos de la reserva desde la API
    // await this.loadReservationData()
  }
}
</script>

<style scoped>
.page-title {
  color: #2c3e50;
  font-weight: 600;
  margin-bottom: 0;
}

.modern-card {
  border: none;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.modern-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
}

.card-header {
  border-radius: 12px 12px 0 0 !important;
  border-bottom: none;
  padding: 1rem 1.5rem;
}

.card-body {
  padding: 1.5rem;
}

.info-item {
  margin-bottom: 1rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f8f9fa;
}

.info-item:last-child {
  border-bottom: none;
}

.info-label {
  font-weight: 600;
  color: #6c757d;
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.9rem;
}

.info-value {
  color: #2c3e50;
  font-weight: 500;
}

.special-requests-box {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  border-left: 4px solid #007bff;
  color: #495057;
  font-style: italic;
}

.payment-summary {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 8px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  padding: 0.25rem 0;
}

.summary-total {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 0;
  margin-top: 0.5rem;
  border-top: 2px solid #28a745;
}

.btn-action {
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  transition: all 0.3s ease;
}

.btn-action:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.info-box {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 8px;
  border-left: 4px solid #6c757d;
}

.info-box h6 {
  margin-bottom: 0.75rem;
  font-weight: 600;
}

.info-box p {
  margin-bottom: 0.5rem;
  color: #6c757d;
}

.table-striped > tbody > tr:nth-child(odd) > td {
  background-color: #f8f9fa;
}

.table th {
  border-top: none;
  font-weight: 600;
  color: #495057;
}

.badge {
  font-size: 0.8rem;
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
}

@media (max-width: 768px) {
  .card-body {
    padding: 1rem;
  }
  
  .info-item {
    margin-bottom: 0.75rem;
  }
  
  .btn-action {
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
  }
}
</style>