<template>
    <div class="bikes-container">
        <!-- Header -->
        <div class="header">
            <h2>Bicicletas</h2>
            <div class="header-actions">
                <button @click="refreshBikes" :disabled="loading" class="btn-secondary">
                    {{ loading ? 'Cargando...' : 'Actualizar' }}
                </button>
                <button v-if="isAdmin" @click="showCreateModal = true" class="btn-primary">
                    Nueva Bicicleta
                </button>
            </div>
        </div>

        <!-- Stats Cards (solo para admin) -->
        <div v-if="isAdmin" class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon">🚲</span>
                <div>
                    <p>Total</p>
                    <h3>{{ stats.total_bikes || 0 }}</h3>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">✓</span>
                <div>
                    <p>Disponibles</p>
                    <h3>{{ stats.by_status?.disponible || 0 }}</h3>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon">⚡</span>
                <div>
                    <p>Eléctricas</p>
                    <h3>{{ stats.by_type?.electrica || 0 }}</h3>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <input v-model="filters.search" type="text" placeholder="Buscar por código...">
            <select v-model="filters.type">
                <option value="">Todos los tipos</option>
                <option value="tradicional">Tradicional</option>
                <option value="electrica">Eléctrica</option>
            </select>
            <select v-model="filters.status">
                <option value="">Todos los estados</option>
                <option value="disponible">Disponible</option>
                <option value="en_uso">En Uso</option>
                <option value="en_reparacion">En Reparación</option>
                <option value="mantenimiento">Mantenimiento</option>
            </select>
            <button @click="clearFilters" class="btn-outline">Limpiar</button>
        </div>

        <!-- Lista de bicicletas -->
        <div class="bikes-list">
            <div v-if="loading" class="loading">Cargando bicicletas...</div>

            <div v-else-if="filteredBikes.length === 0" class="empty">
                No hay bicicletas disponibles
            </div>

            <div v-else>
                <div v-for="bike in filteredBikes" :key="bike.id" class="bike-item">
                    <div class="bike-info">
                        <div class="bike-header">
                            <h3>{{ bike.code }}</h3>
                            <span class="bike-type" :class="bike.type">
                {{ bike.type === 'electrica' ? '⚡ Eléctrica' : '🚲 Tradicional' }}
              </span>
                        </div>
                        <p class="bike-station">
                            {{ bike.station ? bike.station.name : 'Sin estación asignada' }}
                        </p>
                        <div class="bike-status-row">
                            <span class="status" :class="bike.status">{{ getStatusText(bike.status) }}</span>
                            <span v-if="bike.type === 'electrica'" class="battery">
                🔋 {{ bike.battery_level }}%
              </span>
                        </div>
                    </div>
                    <div class="bike-actions">
                        <button @click="viewBike(bike)" class="btn-secondary">Ver</button>
                        <button v-if="isAdmin" @click="editBike(bike)" class="btn-primary">
                            Editar
                        </button>
                        <button v-if="bike.can_be_rented && !isAdmin" @click="rentBike(bike)" class="btn-success">
                            Alquilar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Crear/Editar Bicicleta -->
        <div v-if="showCreateModal || showEditModal" class="modal" @click="closeModal">
            <div class="modal-content" @click.stop>
                <h3>{{ showEditModal ? 'Editar Bicicleta' : 'Nueva Bicicleta' }}</h3>
                <form @submit.prevent="showEditModal ? updateBike() : createBike()">
                    <div class="form-group">
                        <label>Código</label>
                        <input v-model="bikeForm.code" type="text" required maxlength="20" placeholder="Ej: BK001">
                    </div>

                    <div class="form-group">
                        <label>Tipo</label>
                        <select v-model="bikeForm.type" required>
                            <option value="">Seleccionar...</option>
                            <option value="tradicional">Tradicional</option>
                            <option value="electrica">Eléctrica</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Estación</label>
                        <select v-model="bikeForm.station_id" required>
                            <option value="">Seleccionar estación...</option>
                            <option v-for="station in stations" :key="station.id" :value="station.id">
                                {{ station.name }} - {{ station.code }}
                            </option>
                        </select>
                    </div>

                    <div v-if="bikeForm.type === 'electrica'" class="form-group">
                        <label>Nivel de batería (%)</label>
                        <input v-model.number="bikeForm.battery_level" type="number" min="0" max="100" placeholder="100">
                    </div>

                    <div class="form-group">
                        <label>Descripción (opcional)</label>
                        <textarea v-model="bikeForm.description" rows="3" placeholder="Descripción adicional..."></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" @click="closeModal" class="btn-secondary">Cancelar</button>
                        <button type="submit" class="btn-primary">
                            {{ showEditModal ? 'Actualizar' : 'Crear' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Ver Bicicleta -->
        <div v-if="selectedBike" class="modal" @click="closeModal">
            <div class="modal-content" @click.stop>
                <h3>{{ selectedBike.code }}</h3>
                <div class="bike-details">
                    <p><strong>Tipo:</strong> {{ selectedBike.type === 'electrica' ? 'Eléctrica' : 'Tradicional' }}</p>
                    <p><strong>Estado:</strong> {{ getStatusText(selectedBike.status) }}</p>
                    <p><strong>Estación:</strong> {{ selectedBike.station ? selectedBike.station.name : 'Sin asignar' }}</p>
                    <p v-if="selectedBike.type === 'electrica'"><strong>Batería:</strong> {{ selectedBike.battery_level }}%</p>
                    <p v-if="selectedBike.description"><strong>Descripción:</strong> {{ selectedBike.description }}</p>
                    <p><strong>Tiempo total de uso:</strong> {{ formatMinutes(selectedBike.total_usage_time) }}</p>
                </div>
                <div class="modal-actions">
                    <button @click="closeModal" class="btn-secondary">Cerrar</button>
                    <button v-if="selectedBike.can_be_rented && !isAdmin" @click="rentBike(selectedBike)" class="btn-primary">
                        Alquilar
                    </button>
                    <button v-if="isAdmin" @click="editBike(selectedBike)" class="btn-primary">
                        Editar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BikesList',
    data() {
        return {
            bikes: [],
            stations: [],
            selectedBike: null,
            stats: {},
            loading: false,
            currentUser: null,
            showCreateModal: false,
            showEditModal: false,
            editingBike: null,
            bikeForm: {
                code: '',
                type: '',
                station_id: '',
                battery_level: 100,
                description: ''
            },
            filters: {
                search: '',
                type: '',
                status: ''
            }
        }
    },
    computed: {
        isAdmin() {
            return this.currentUser && this.currentUser.role === 'admin'
        },
        filteredBikes() {
            return this.bikes.filter(bike => {
                const matchesSearch = !this.filters.search ||
                    bike.code.toLowerCase().includes(this.filters.search.toLowerCase())
                const matchesType = !this.filters.type || bike.type === this.filters.type
                const matchesStatus = !this.filters.status || bike.status === this.filters.status
                return matchesSearch && matchesType && matchesStatus
            })
        }
    },
    async mounted() {
        await this.loadCurrentUser()
        await this.loadBikes()
        await this.loadStations()
        if (this.isAdmin) {
            await this.loadStats()
        }
    },
    methods: {
        async loadCurrentUser() {
            try {
                const response = await axios.get('/api/user')
                this.currentUser = response.data.user || response.data
            } catch (error) {
                console.error('Error loading user:', error)
            }
        },

        async loadBikes() {
            this.loading = true
            try {
                const response = await axios.get('/api/bikes')
                this.bikes = response.data.data?.data || response.data.data || []
            } catch (error) {
                console.error('Error:', error)
                alert('Error al cargar bicicletas')
            } finally {
                this.loading = false
            }
        },

        async loadStations() {
            try {
                const response = await axios.get('/api/stations')
                this.stations = response.data.data?.data || response.data.data || []
            } catch (error) {
                console.error('Error:', error)
            }
        },

        async loadStats() {
            try {
                const response = await axios.get('/api/bikes/statistics')
                this.stats = response.data.data || {}
            } catch (error) {
                console.error('Error:', error)
            }
        },

        async createBike() {
            try {
                const response = await axios.post('/api/bikes', this.bikeForm)
                this.closeModal()
                this.loadBikes()
                alert('Bicicleta creada exitosamente')
            } catch (error) {
                console.error('Error:', error)
                alert('Error al crear bicicleta: ' + (error.response?.data?.message || 'Error desconocido'))
            }
        },

        async updateBike() {
            try {
                const response = await axios.put(`/api/bikes/${this.editingBike.id}`, this.bikeForm)
                this.closeModal()
                this.loadBikes()
                alert('Bicicleta actualizada exitosamente')
            } catch (error) {
                console.error('Error:', error)
                alert('Error al actualizar bicicleta')
            }
        },

        async refreshBikes() {
            await this.loadBikes()
            if (this.isAdmin) {
                await this.loadStats()
            }
        },

        viewBike(bike) {
            this.selectedBike = bike
        },

        editBike(bike) {
            this.editingBike = bike
            this.bikeForm = {
                code: bike.code,
                type: bike.type,
                station_id: bike.station_id,
                battery_level: bike.battery_level || 100,
                description: bike.description || ''
            }
            this.showEditModal = true
        },

        async rentBike(bike) {
            if (!confirm('¿Alquilar esta bicicleta?')) return

            try {
                await axios.post(`/api/bikes/${bike.id}/rent`, {
                    station_id: bike.station_id
                })
                this.loadBikes()
                alert('Bicicleta alquilada exitosamente')
            } catch (error) {
                alert('Error al alquilar bicicleta')
            }
        },

        closeModal() {
            this.showCreateModal = false
            this.showEditModal = false
            this.selectedBike = null
            this.editingBike = null
            this.bikeForm = { code: '', type: '', station_id: '', battery_level: 100, description: '' }
        },

        clearFilters() {
            this.filters = { search: '', type: '', status: '' }
        },

        getStatusText(status) {
            const statuses = {
                'disponible': 'Disponible',
                'en_uso': 'En Uso',
                'en_reparacion': 'En Reparación',
                'mantenimiento': 'Mantenimiento'
            }
            return statuses[status] || status
        },

        formatMinutes(minutes) {
            if (!minutes) return '0 min'
            const hours = Math.floor(minutes / 60)
            const mins = minutes % 60
            return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`
        }
    }
}
</script>

<style scoped>
.bikes-container { padding: 20px; }

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 10px;
}

.stat-icon {
    font-size: 24px;
}

.stat-card h3 {
    margin: 0;
    font-size: 24px;
    color: #333;
}

.stat-card p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.filters input, .filters select {
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    min-width: 150px;
}

.bikes-list {
    display: grid;
    gap: 15px;
}

.bike-item {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.bike-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 5px;
}

.bike-header h3 {
    margin: 0;
    color: #333;
}

.bike-type {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}

.bike-type.electrica { background: #fef3c7; color: #92400e; }
.bike-type.tradicional { background: #dbeafe; color: #1e40af; }

.bike-station {
    margin: 5px 0;
    color: #666;
    font-size: 14px;
}

.bike-status-row {
    display: flex;
    gap: 10px;
    align-items: center;
}

.status {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}

.status.disponible { background: #d1fae5; color: #065f46; }
.status.en_uso { background: #dbeafe; color: #1e40af; }
.status.en_reparacion { background: #fee2e2; color: #991b1b; }
.status.mantenimiento { background: #fef3c7; color: #92400e; }

.battery {
    font-size: 12px;
    color: #666;
}

.bike-actions {
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
    max-height: 90vh;
    overflow-y: auto;
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

.form-actions, .modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}

.bike-details p {
    margin: 10px 0;
}

.btn-primary { background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
.btn-secondary { background: #6b7280; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
.btn-success { background: #10b981; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; }
.btn-outline { background: white; color: #374151; border: 1px solid #d1d5db; padding: 8px 16px; border-radius: 4px; cursor: pointer; }

.loading, .empty {
    text-align: center;
    padding: 40px;
    color: #666;
}
</style>
