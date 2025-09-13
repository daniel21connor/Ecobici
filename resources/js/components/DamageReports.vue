<template>
    <div class="damage-reports">
        <!-- Header -->
        <div class="header">
            <h2>Reportes de Daños</h2>
            <button @click="showCreateModal = true" class="btn-primary">
                Nuevo Reporte
            </button>
        </div>

        <!-- Lista de reportes -->
        <div class="reports-list">
            <div v-if="loading" class="loading">Cargando...</div>

            <div v-else-if="reports.length === 0" class="empty">
                No hay reportes
            </div>

            <div v-else>
                <div v-for="report in reports" :key="report.id" class="report-item">
                    <div class="report-info">
                        <h3>{{ report.bike.code }} - {{ report.bike.type }}</h3>
                        <p>{{ report.description }}</p>
                        <span class="status" :class="report.status">{{ report.status }}</span>
                        <span class="severity" :class="report.severity">{{ report.severity }}</span>
                    </div>
                    <div class="report-actions">
                        <button @click="viewReport(report)" class="btn-secondary">Ver</button>
                        <button v-if="user.role === 'admin'" @click="resolveReport(report)" class="btn-success">
                            Resolver
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Crear Reporte -->
        <div v-if="showCreateModal" class="modal" @click="closeModal">
            <div class="modal-content" @click.stop>
                <h3>Nuevo Reporte</h3>
                <form @submit.prevent="createReport">
                    <div class="form-group">
                        <label>Bicicleta</label>
                        <select v-model="newReport.bike_id" required>
                            <option value="">Seleccionar...</option>
                            <option v-for="bike in bikes" :key="bike.id" :value="bike.id">
                                {{ bike.code }} - {{ bike.type }}
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea v-model="newReport.description" required rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Severidad</label>
                        <select v-model="newReport.severity">
                            <option value="leve">Leve</option>
                            <option value="moderado">Moderado</option>
                            <option value="grave">Grave</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="button" @click="closeModal" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary">Crear</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Ver Reporte -->
        <div v-if="selectedReport" class="modal" @click="closeModal">
            <div class="modal-content" @click.stop>
                <h3>Reporte #{{ selectedReport.id }}</h3>
                <div class="report-details">
                    <p><strong>Bicicleta:</strong> {{ selectedReport.bike.code }}</p>
                    <p><strong>Descripción:</strong> {{ selectedReport.description }}</p>
                    <p><strong>Estado:</strong> {{ selectedReport.status }}</p>
                    <p><strong>Severidad:</strong> {{ selectedReport.severity }}</p>
                    <p><strong>Fecha:</strong> {{ formatDate(selectedReport.created_at) }}</p>
                </div>
                <button @click="closeModal" class="btn-secondary">Cerrar</button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'DamageReports',
    props: {
        user: { type: Object, required: true }
    },
    data() {
        return {
            reports: [],
            bikes: [],
            loading: false,
            showCreateModal: false,
            selectedReport: null,
            newReport: {
                bike_id: '',
                description: '',
                severity: 'leve'
            }
        }
    },
    async mounted() {
        await this.loadReports()
        await this.loadBikes()
    },
    methods: {
        async loadReports() {
            this.loading = true
            try {
                const response = await axios.get('/damage-reports')
                this.reports = response.data.data || []
            } catch (error) {
                console.error('Error:', error)
            } finally {
                this.loading = false
            }
        },

        async loadBikes() {
            try {
                const response = await axios.get('/bikes')
                this.bikes = response.data.data || []
            } catch (error) {
                console.error('Error:', error)
            }
        },

        async createReport() {
            try {
                await axios.post('/damage-reports', this.newReport)
                this.closeModal()
                this.loadReports()
                alert('Reporte creado')
            } catch (error) {
                alert('Error al crear reporte')
            }
        },

        async resolveReport(report) {
            if (!confirm('¿Resolver este reporte?')) return

            try {
                await axios.post(`/damage-reports/${report.id}/resolve`)
                this.loadReports()
                alert('Reporte resuelto')
            } catch (error) {
                alert('Error al resolver reporte')
            }
        },

        viewReport(report) {
            this.selectedReport = report
        },

        closeModal() {
            this.showCreateModal = false
            this.selectedReport = null
            this.newReport = { bike_id: '', description: '', severity: 'leve' }
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString()
        }
    }
}
</script>

<style scoped>
.damage-reports {
    padding: 20px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.reports-list {
    margin-bottom: 20px;
}

.report-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.report-info h3 {
    margin: 0 0 5px 0;
    color: #333;
}

.report-info p {
    margin: 5px 0;
    color: #666;
}

.status, .severity {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    margin-right: 5px;
}

.status.pendiente { background: #fef3c7; color: #92400e; }
.status.resuelto { background: #d1fae5; color: #065f46; }

.severity.leve { background: #d1fae5; color: #065f46; }
.severity.moderado { background: #fef3c7; color: #92400e; }
.severity.grave { background: #fee2e2; color: #991b1b; }

.report-actions {
    display: flex;
    gap: 10px;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    min-width: 400px;
    max-width: 90vw;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}

.btn-primary {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-secondary {
    background: #6b7280;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.btn-success {
    background: #10b981;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
}

.loading, .empty {
    text-align: center;
    padding: 40px;
    color: #666;
}

.report-details p {
    margin: 10px 0;
}
</style>
