<template>
    <div class="bikes-container">
        <!-- Header -->
        <div class="header">
            <h2>Bicicletas</h2>
            <div class="header-actions">
                <button @click="refreshBikes" :disabled="loading" class="btn-secondary">
                    {{ loading ? 'Cargando...' : 'Actualizar' }}
                </button>
<<<<<<< HEAD
                <button v-if="isAdmin" @click="showCreateModal = true" class="btn-primary">
                    Nueva Bicicleta
=======
                <button
                    v-if="isAdmin"
                    @click="showCreateModal = true"
                    class="btn btn-primary"
                >
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva Estación
>>>>>>> origin/connor-dev
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

<<<<<<< HEAD
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
=======
        <!-- Rest of the template remains the same... -->
        <!-- Stations Grid -->
        <div v-if="loading && stations.length === 0" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-500">Cargando estaciones...</p>
>>>>>>> origin/connor-dev
        </div>

        <!-- Lista de bicicletas -->
        <div class="bikes-list">
            <div v-if="loading" class="loading">Cargando bicicletas...</div>

<<<<<<< HEAD
            <div v-else-if="filteredBikes.length === 0" class="empty">
                No hay bicicletas disponibles
            </div>
=======
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
                v-for="station in filteredStations"
                :key="station.id"
                class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer"
                @click="selectStation(station)"
            >
                <div class="p-6">
                    <!-- Header -->
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ station.name }}</h3>
                            <p class="text-sm text-gray-500">{{ station.code }}</p>
                        </div>
                        <span
                            class="px-3 py-1 rounded-full text-xs font-medium"
                            :class="getTypeClass(station.type)"
                        >
                            {{ getTypeText(station.type) }}
                        </span>
                    </div>
>>>>>>> origin/connor-dev

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

<<<<<<< HEAD
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
=======
        <!-- Modal content remains the same but using currentUser instead of user prop -->
>>>>>>> origin/connor-dev
    </div>
</template>

<script>
export default {
<<<<<<< HEAD
    name: 'BikesList',
=======
    name: 'StationsComponent',

>>>>>>> origin/connor-dev
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
<<<<<<< HEAD
                type: '',
                status: ''
            }
=======
                type: ''
            },
            // OPCIÓN 1: Obtener usuario internamente
            currentUser: null,
            currentUsage: null
>>>>>>> origin/connor-dev
        }
    },
    computed: {
<<<<<<< HEAD
        isAdmin() {
            return this.currentUser && this.currentUser.role === 'admin'
        },
        filteredBikes() {
            return this.bikes.filter(bike => {
=======
        // Computed property para verificar si es admin
        isAdmin() {
            return this.currentUser?.role === 'admin';
        },

        filteredStations() {
            return this.stations.filter(station => {
>>>>>>> origin/connor-dev
                const matchesSearch = !this.filters.search ||
                    bike.code.toLowerCase().includes(this.filters.search.toLowerCase())
                const matchesType = !this.filters.type || bike.type === this.filters.type
                const matchesStatus = !this.filters.status || bike.status === this.filters.status
                return matchesSearch && matchesType && matchesStatus
            })
        }
    },
    async mounted() {
<<<<<<< HEAD
        await this.loadCurrentUser()
        await this.loadBikes()
        await this.loadStations()
        if (this.isAdmin) {
            await this.loadStats()
        }
=======
        // OPCIÓN 1: Cargar usuario internamente
        await this.getCurrentUser();
        await this.loadStations();
>>>>>>> origin/connor-dev
    },
    methods: {
<<<<<<< HEAD
        async loadCurrentUser() {
=======
        // OPCIÓN 1: Método para obtener usuario actual
        async getCurrentUser() {
            try {
                const response = await axios.get('/user');

                if (response.data.success && response.data.user) {
                    this.currentUser = response.data.user;
                } else if (response.data.user) {
                    this.currentUser = response.data.user;
                } else if (response.data.name) {
                    this.currentUser = response.data;
                }

                console.log('Usuario cargado en Estacion:', this.currentUser);
            } catch (error) {
                console.error('Error obteniendo usuario:', error);
                // Usuario por defecto
                this.currentUser = { role: 'user' };
            }
        },

        async loadStations() {
            this.loading = true;
>>>>>>> origin/connor-dev
            try {
                const response = await axios.get('/api/user')
                this.currentUser = response.data.user || response.data
            } catch (error) {
<<<<<<< HEAD
                console.error('Error loading user:', error)
=======
                console.error('Error loading stations:', error);
                this.showError('Error al cargar las estaciones');
            } finally {
                this.loading = false;
>>>>>>> origin/connor-dev
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
<<<<<<< HEAD
            this.showCreateModal = false
            this.showEditModal = false
            this.selectedBike = null
            this.editingBike = null
            this.bikeForm = { code: '', type: '', station_id: '', battery_level: 100, description: '' }
=======
            this.selectedStation = null;
            this.selectedStationBikes = [];
        },

        canRentFromStation(station) {
            return station.available_bikes_count > 0 && !this.currentUsage;
        },

        async rentBike(bike) {
            try {
                const response = await axios.post(`/api/bikes/${bike.id}/rent`, {
                    station_id: this.selectedStation.id
                });

                if (response.data.success) {
                    this.rentedBike = bike;
                    this.showRentSuccess = true;
                    this.closeModal();
                    this.$emit('bike-rented', response.data.data);
                    await this.refreshStations();
                }
            } catch (error) {
                console.error('Error renting bike:', error);
                this.showError(error.response?.data?.message || 'Error al alquilar la bicicleta');
            }
        },

        async rentFromStation(station) {
            await this.selectStation(station);
        },

        closeRentSuccess() {
            this.showRentSuccess = false;
            this.rentedBike = null;
>>>>>>> origin/connor-dev
        },

        clearFilters() {
            this.filters = { search: '', type: '', status: '' }
        },

<<<<<<< HEAD
        getStatusText(status) {
            const statuses = {
                'disponible': 'Disponible',
                'en_uso': 'En Uso',
                'en_reparacion': 'En Reparación',
                'mantenimiento': 'Mantenimiento'
            }
            return statuses[status] || status
=======
        showError(message) {
            // Método interno para manejar errores
            console.error('Error:', message);
            // Puedes reemplazar esto con tu sistema de notificaciones preferido
            alert('❌ ' + message);
        },

        showSuccess(message) {
            // Método para mostrar mensajes de éxito
            console.log('Éxito:', message);
            // Puedes reemplazar esto con tu sistema de notificaciones preferido
            alert('✅ ' + message);
        },

        // Utility methods remain the same...
        getTypeClass(type) {
            const classes = {
                'carga': 'bg-yellow-100 text-yellow-800',
                'descanso': 'bg-blue-100 text-blue-800',
                'seleccion': 'bg-green-100 text-green-800'
            };
            return classes[type] || 'bg-gray-100 text-gray-800';
>>>>>>> origin/connor-dev
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
