<template>
    <div class="stations-management">
        <!-- Header -->
        <div class="management-header">
            <div class="header-info">
                <h2 class="management-title">🏢 Gestión de Estaciones</h2>
                <p class="management-subtitle">Administra las estaciones de bicicletas del sistema</p>
            </div>
            <div class="header-actions" v-if="isAdmin">
                <button @click="openCreateModal" class="action-btn primary">
                    <span class="btn-icon">+</span>
                    Nueva Estación
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-card">
            <div class="filters-content">
                <div class="filter-group">
                    <label class="filter-label">Buscar</label>
                    <input
                        type="text"
                        class="filter-input"
                        v-model="filters.search"
                        placeholder="Nombre o código..."
                        @input="filterStations"
                    >
                </div>
                <div class="filter-group">
                    <label class="filter-label">Tipo</label>
                    <select class="filter-select" v-model="filters.type" @change="filterStations">
                        <option value="">Todos los tipos</option>
                        <option value="carga">Carga</option>
                        <option value="descanso">Descanso</option>
                        <option value="seleccion">Selección</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Estado</label>
                    <select class="filter-select" v-model="filters.status" @change="filterStations">
                        <option value="">Todos</option>
                        <option value="active">Activas</option>
                        <option value="inactive">Inactivas</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button @click="clearFilters" class="clear-btn">
                        <span class="btn-icon">✖</span>
                        Limpiar
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="loading-spinner"></div>
            <p>Cargando estaciones...</p>
        </div>

        <!-- Stations Grid -->
        <div v-else-if="filteredStations.length > 0" class="stations-grid">
            <div v-for="station in paginatedStations" :key="station.id" class="station-card">
                <div class="station-header">
                    <div class="station-info">
                        <div class="station-type-badge" :class="getTypeBadgeClass(station.type)">
                            {{ getTypeIcon(station.type) }} {{ getTypeText(station.type) }}
                        </div>
                        <h3 class="station-name">{{ station.name }}</h3>
                        <p class="station-code">Código: <strong>{{ station.code }}</strong></p>
                    </div>
                    <div class="station-actions" v-if="isAdmin">
                        <div class="dropdown-container">
                            <button @click="toggleDropdown(station.id)" class="dropdown-toggle">
                                <span class="dropdown-icon">⋮</span>
                            </button>
                            <div v-if="openDropdown === station.id" class="dropdown-menu">
                                <button @click="viewStation(station)" class="dropdown-item">
                                    <span class="item-icon">👁</span>
                                    Ver Detalles
                                </button>
                                <button @click="editStation(station)" class="dropdown-item">
                                    <span class="item-icon">✏</span>
                                    Editar
                                </button>
                                <button @click="toggleStationStatus(station)"
                                        class="dropdown-item"
                                        :class="{ 'text-warning': station.is_active, 'text-success': !station.is_active }">
                                    <span class="item-icon">{{ station.is_active ? '⏸' : '▶' }}</span>
                                    {{ station.is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                                <button v-if="canDeleteStation(station)"
                                        @click="deleteStation(station)"
                                        class="dropdown-item text-danger">
                                    <span class="item-icon">🗑</span>
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="station-body">
                    <!-- Location Info -->
                    <div class="location-info" v-if="station.address">
                        <span class="location-icon">📍</span>
                        <span class="location-text">{{ station.address }}</span>
                    </div>

                    <div class="coordinates-info">
                        <span class="coord-icon">🗺</span>
                        <span class="coord-text">{{ station.latitude }}, {{ station.longitude }}</span>
                    </div>

                    <div v-if="station.description" class="station-description">
                        {{ truncateText(station.description, 100) }}
                    </div>

                    <!-- Capacity Progress -->
                    <div class="capacity-section">
                        <div class="capacity-header">
                            <span class="capacity-label">Capacidad</span>
                            <span class="capacity-count">{{ getTotalBikes(station) }}/{{ station.capacity }}</span>
                        </div>
                        <div class="capacity-bar">
                            <div class="capacity-fill"
                                 :class="getCapacityClass(station)"
                                 :style="{ width: getOccupancyPercentage(station) + '%' }"></div>
                        </div>
                    </div>

                    <!-- Bike Stats -->
                    <div class="bike-stats">
                        <div class="stat-item">
                            <div class="stat-number available">{{ getAvailableBikes(station) }}</div>
                            <div class="stat-label">Disponibles</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number in-use">{{ getBikesInUse(station) }}</div>
                            <div class="stat-label">En Uso</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number maintenance">{{ getMaintenanceBikes(station) }}</div>
                            <div class="stat-label">Mantenimiento</div>
                        </div>
                    </div>

                    <!-- Bike Types -->
                    <div class="bike-types">
                        <div class="type-info">
                            <span class="type-icon">🚲</span>
                            <span class="type-text">Tradicionales: <strong>{{ getTraditionalBikes(station) }}</strong></span>
                        </div>
                        <div class="type-info">
                            <span class="type-icon">⚡</span>
                            <span class="type-text">Eléctricas: <strong>{{ getElectricBikes(station) }}</strong></span>
                        </div>
                    </div>
                </div>

                <div class="station-footer">
                    <div class="status-badge" :class="{ 'active': station.is_active, 'inactive': !station.is_active }">
                        {{ station.is_active ? 'Activa' : 'Inactiva' }}
                    </div>
                    <div class="station-buttons">
                        <button @click="viewStation(station)" class="view-btn">
                            <span class="btn-icon">👁</span>
                        </button>
                        <button v-if="isAdmin" @click="editStation(station)" class="edit-btn">
                            <span class="btn-icon">✏</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
            <div class="empty-icon">🏢</div>
            <h3 class="empty-title">No hay estaciones</h3>
            <p class="empty-text">
                {{ filters.search || filters.type || filters.status ? 'No se encontraron estaciones con los filtros aplicados.' : 'No hay estaciones registradas en el sistema.' }}
            </p>
            <button v-if="isAdmin && !hasFilters" @click="openCreateModal" class="empty-action-btn">
                <span class="btn-icon">+</span>
                Crear Primera Estación
            </button>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination-container">
            <div class="pagination">
                <button @click="changePage(currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="page-btn">
                    ←
                </button>
                <span class="page-info">
                    Página {{ currentPage }} de {{ totalPages }}
                </span>
                <button @click="changePage(currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        class="page-btn">
                    →
                </button>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="modal-overlay" @click="closeModal">
            <div class="modal-container" @click.stop>
                <div class="modal-header">
                    <h3 class="modal-title">
                        {{ editingStation ? 'Editar Estación' : 'Nueva Estación' }}
                    </h3>
                    <button @click="closeModal" class="modal-close">✖</button>
                </div>

                <form @submit.prevent="saveStation" class="modal-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Nombre *</label>
                            <input type="text" v-model="stationForm.name" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Código *</label>
                            <input type="text" v-model="stationForm.code" class="form-input" maxlength="10" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descripción</label>
                        <textarea v-model="stationForm.description" class="form-textarea" rows="3"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Latitud *</label>
                            <input type="number" v-model="stationForm.latitude" class="form-input" step="0.00000001" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Longitud *</label>
                            <input type="number" v-model="stationForm.longitude" class="form-input" step="0.00000001" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Dirección</label>
                        <input type="text" v-model="stationForm.address" class="form-input">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tipo *</label>
                            <select v-model="stationForm.type" class="form-select" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="carga">Carga</option>
                                <option value="descanso">Descanso</option>
                                <option value="seleccion">Selección</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Capacidad *</label>
                            <input type="number" v-model="stationForm.capacity" class="form-input" min="1" max="100" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" v-model="stationForm.is_active">
                            <span class="checkbox-text">Estación activa</span>
                        </label>
                    </div>

                    <div class="modal-actions">
                        <button type="button" @click="closeModal" class="cancel-btn">Cancelar</button>
                        <button type="submit" class="save-btn" :disabled="saving">
                            {{ saving ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- View Details Modal -->
        <div v-if="showDetailsModal" class="modal-overlay" @click="closeDetailsModal">
            <div class="modal-container large" @click.stop>
                <div class="modal-header">
                    <h3 class="modal-title">Detalles de la Estación</h3>
                    <button @click="closeDetailsModal" class="modal-close">✖</button>
                </div>

                <div v-if="selectedStation" class="station-details">
                    <div class="details-grid">
                        <div class="detail-item">
                            <label class="detail-label">Nombre</label>
                            <span class="detail-value">{{ selectedStation.name }}</span>
                        </div>
                        <div class="detail-item">
                            <label class="detail-label">Código</label>
                            <span class="detail-value">{{ selectedStation.code }}</span>
                        </div>
                        <div class="detail-item">
                            <label class="detail-label">Tipo</label>
                            <span class="detail-value type-badge" :class="getTypeBadgeClass(selectedStation.type)">
                                {{ getTypeIcon(selectedStation.type) }} {{ getTypeText(selectedStation.type) }}
                            </span>
                        </div>
                        <div class="detail-item">
                            <label class="detail-label">Estado</label>
                            <span class="detail-value status-badge" :class="{ 'active': selectedStation.is_active, 'inactive': !selectedStation.is_active }">
                                {{ selectedStation.is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </div>
                        <div class="detail-item">
                            <label class="detail-label">Capacidad</label>
                            <span class="detail-value">{{ selectedStation.capacity }} bicicletas</span>
                        </div>
                        <div class="detail-item">
                            <label class="detail-label">Ocupación</label>
                            <span class="detail-value">{{ getTotalBikes(selectedStation) }}/{{ selectedStation.capacity }} ({{ getOccupancyPercentage(selectedStation) }}%)</span>
                        </div>
                        <div class="detail-item full-width" v-if="selectedStation.address">
                            <label class="detail-label">Dirección</label>
                            <span class="detail-value">{{ selectedStation.address }}</span>
                        </div>
                        <div class="detail-item">
                            <label class="detail-label">Latitud</label>
                            <span class="detail-value">{{ selectedStation.latitude }}</span>
                        </div>
                        <div class="detail-item">
                            <label class="detail-label">Longitud</label>
                            <span class="detail-value">{{ selectedStation.longitude }}</span>
                        </div>
                        <div class="detail-item full-width" v-if="selectedStation.description">
                            <label class="detail-label">Descripción</label>
                            <span class="detail-value">{{ selectedStation.description }}</span>
                        </div>
                    </div>

                    <!-- Bike Statistics -->
                    <div class="bike-statistics">
                        <h4 class="section-title">Estadísticas de Bicicletas</h4>
                        <div class="stats-grid">
                            <div class="stats-card">
                                <div class="stats-icon">🚲</div>
                                <div class="stats-content">
                                    <div class="stats-number">{{ getTotalBikes(selectedStation) }}</div>
                                    <div class="stats-label">Total</div>
                                </div>
                            </div>
                            <div class="stats-card available">
                                <div class="stats-icon">✅</div>
                                <div class="stats-content">
                                    <div class="stats-number">{{ getAvailableBikes(selectedStation) }}</div>
                                    <div class="stats-label">Disponibles</div>
                                </div>
                            </div>
                            <div class="stats-card in-use">
                                <div class="stats-icon">🔄</div>
                                <div class="stats-content">
                                    <div class="stats-number">{{ getBikesInUse(selectedStation) }}</div>
                                    <div class="stats-label">En Uso</div>
                                </div>
                            </div>
                            <div class="stats-card maintenance">
                                <div class="stats-icon">🔧</div>
                                <div class="stats-content">
                                    <div class="stats-number">{{ getMaintenanceBikes(selectedStation) }}</div>
                                    <div class="stats-label">Mantenimiento</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {

    data() {
        return {
            stations: [],
            filteredStations: [],
            loading: true,
            showModal: false,
            showDetailsModal: false,
            editingStation: null,
            selectedStation: null,
            saving: false,
            openDropdown: null,

            // Filters
            filters: {
                search: '',
                type: '',
                status: ''
            },

            // Pagination
            currentPage: 1,
            itemsPerPage: 9,

            // Form data
            stationForm: {
                name: '',
                code: '',
                description: '',
                latitude: null,
                longitude: null,
                address: '',
                type: '',
                capacity: 10,
                is_active: true
            }
        }
    },

    computed: {
        isAdmin() {
            return this.$parent.isAdmin;
        },

        hasFilters() {
            return this.filters.search || this.filters.type || this.filters.status;
        },

        totalPages() {
            return Math.ceil(this.filteredStations.length / this.itemsPerPage);
        },

        paginatedStations() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.filteredStations.slice(start, end);
        }
    },

    mounted() {
        this.loadStations();
    },

    methods: {
        async loadStations() {
            try {
                this.loading = true;
                const response = await axios.get('/stations', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                this.stations = response.data.stations || [];
                this.filterStations();
            } catch (error) {
                console.error('Error cargando estaciones:', error);
                this.$emit('show-alert', 'Error al cargar las estaciones', 'error');
            } finally {
                this.loading = false;
            }
        },

        filterStations() {
            this.filteredStations = this.stations.filter(station => {
                const matchesSearch = !this.filters.search ||
                    station.name.toLowerCase().includes(this.filters.search.toLowerCase()) ||
                    station.code.toLowerCase().includes(this.filters.search.toLowerCase());

                const matchesType = !this.filters.type || station.type === this.filters.type;

                const matchesStatus = !this.filters.status ||
                    (this.filters.status === 'active' && station.is_active) ||
                    (this.filters.status === 'inactive' && !station.is_active);

                return matchesSearch && matchesType && matchesStatus;
            });

            this.currentPage = 1;
        },

        clearFilters() {
            this.filters = {
                search: '',
                type: '',
                status: ''
            };
            this.filterStations();
        },

        changePage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },

        openCreateModal() {
            this.editingStation = null;
            this.stationForm = {
                name: '',
                code: '',
                description: '',
                latitude: null,
                longitude: null,
                address: '',
                type: '',
                capacity: 10,
                is_active: true
            };
            this.showModal = true;
        },

        editStation(station) {
            this.editingStation = station;
            this.stationForm = {
                name: station.name,
                code: station.code,
                description: station.description || '',
                latitude: station.latitude,
                longitude: station.longitude,
                address: station.address || '',
                type: station.type,
                capacity: station.capacity,
                is_active: station.is_active
            };
            this.showModal = true;
            this.openDropdown = null;
        },

        viewStation(station) {
            this.selectedStation = station;
            this.showDetailsModal = true;
            this.openDropdown = null;
        },

        closeModal() {
            this.showModal = false;
            this.editingStation = null;
        },

        closeDetailsModal() {
            this.showDetailsModal = false;
            this.selectedStation = null;
        },

        async saveStation() {
            try {
                this.saving = true;

                const url = this.editingStation
                    ? `/stations/${this.editingStation.id}`
                    : '/stations';

                const method = this.editingStation ? 'PUT' : 'POST';

                const response = await axios({
                    method,
                    url,
                    data: this.stationForm,
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                this.$emit('show-alert',
                    this.editingStation ? 'Estación actualizada exitosamente' : 'Estación creada exitosamente',
                    'success'
                );

                this.closeModal();
                await this.loadStations();

            } catch (error) {
                console.error('Error guardando estación:', error);
                this.$emit('show-alert', 'Error al guardar la estación', 'error');
            } finally {
                this.saving = false;
            }
        },

        async toggleStationStatus(station) {
            try {
                await axios.patch(`/stations/${station.id}/toggle-status`, {}, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                const status = station.is_active ? 'desactivada' : 'activada';
                this.$emit('show-alert', `Estación ${status} exitosamente`, 'success');

                await this.loadStations();
            } catch (error) {
                console.error('Error cambiando estado:', error);
                this.$emit('show-alert', 'Error al cambiar el estado de la estación', 'error');
            }

            this.openDropdown = null;
        },

        async deleteStation(station) {
            if (!confirm(`¿Estás seguro de eliminar la estación "${station.name}"?`)) {
                return;
            }

            try {
                await axios.delete(`/stations/${station.id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                this.$emit('show-alert', 'Estación eliminada exitosamente', 'success');
                await this.loadStations();
            } catch (error) {
                console.error('Error eliminando estación:', error);
                this.$emit('show-alert', 'Error al eliminar la estación', 'error');
            }

            this.openDropdown = null;
        },

        toggleDropdown(stationId) {
            this.openDropdown = this.openDropdown === stationId ? null : stationId;
        },

        canDeleteStation(station) {
            return this.getTotalBikes(station) === 0;
        },

        exportData() {
            window.open('/stations/data', '_blank');
        },

        // Utility methods
        getTypeText(type) {
            const types = {
                'carga': 'Carga',
                'descanso': 'Descanso',
                'seleccion': 'Selección'
            };
            return types[type] || type;
        },

        getTypeIcon(type) {
            const icons = {
                'carga': '🔋',
                'descanso': '☕',
                'seleccion': '🎯'
            };
            return icons[type] || '🏢';
        },

        getTypeBadgeClass(type) {
            const classes = {
                'carga': 'type-carga',
                'descanso': 'type-descanso',
                'seleccion': 'type-seleccion'
            };
            return classes[type] || '';
        },

        getTotalBikes(station) {
            if (!station.bikes) return 0;
            return station.bikes.filter(bike => bike.is_active).length;
        },

        getAvailableBikes(station) {
            if (!station.bikes) return 0;
            return station.bikes.filter(bike => bike.is_active && bike.status === 'disponible').length;
        },

        getBikesInUse(station) {
            if (!station.bikes) return 0;
            return station.bikes.filter(bike => bike.is_active && bike.status === 'en_uso').length;
        },

        getMaintenanceBikes(station) {
            if (!station.bikes) return 0;
            return station.bikes.filter(bike => bike.is_active &&
                (bike.status === 'en_reparacion' || bike.status === 'mantenimiento')).length;
        },

        getTraditionalBikes(station) {
            if (!station.bikes) return 0;
            return station.bikes.filter(bike => bike.is_active && bike.type === 'tradicional').length;
        },

        getElectricBikes(station) {
            if (!station.bikes) return 0;
            return station.bikes.filter(bike => bike.is_active && bike.type === 'electrica').length;
        },

        getOccupancyPercentage(station) {
            if (station.capacity === 0) return 0;
            const totalBikes = this.getTotalBikes(station);
            return Math.round((totalBikes / station.capacity) * 100);
        },

        getCapacityClass(station) {
            const percentage = this.getOccupancyPercentage(station);
            if (percentage >= 90) return 'capacity-full';
            if (percentage >= 70) return 'capacity-high';
            return 'capacity-normal';
        },

        truncateText(text, length) {
            if (!text) return '';
            return text.length > length ? text.substring(0, length) + '...' : text;
        }
    }
}
</script>

<style>
/* Base Styles */
.stations-management {
    padding: 0;
    background: transparent;
}

/* Header */
.management-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    gap: 2rem;
}

.header-info {
    flex: 1;
}

.management-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
}

.management-subtitle {
    color: #6b7280;
    margin: 0;
    font-size: 1rem;
}

.header-actions {
    display: flex;
    gap: 1rem;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 0.9rem;
}

.action-btn.primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.action-btn.primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.action-btn.secondary {
    background: white;
    color: #374151;
    border: 1px solid #d1d5db;
}

.action-btn.secondary:hover {
    background: #f9fafb;
    border-color: #9ca3af;
    transform: translateY(-1px);
}

.btn-icon {
    font-size: 1.2rem;
}

/* Filters Card */
.filters-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
    border: 1px solid #e5e7eb;
}

.filters-content {
    padding: 1.5rem;
    display: flex;
    gap: 1.5rem;
    align-items: flex-end;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 200px;
}

.filter-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.filter-input, .filter-select {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.filter-input:focus, .filter-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-actions {
    display: flex;
    align-items: flex-end;
}

.clear-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #f3f4f6;
    color: #6b7280;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 0.9rem;
}

.clear-btn:hover {
    background: #e5e7eb;
    color: #374151;
}

/* Loading State */
.loading-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #e5e7eb;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Stations Grid */
.stations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.station-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    transition: all 0.3s ease;
}

.station-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* Station Card Header */
.station-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1.5rem 1.5rem 1rem;
    border-bottom: 1px solid #f3f4f6;
}

.station-info {
    flex: 1;
}

.station-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.station-type-badge.type-carga {
    background: rgba(16, 185, 129, 0.1);
    color: #065f46;
}

.station-type-badge.type-descanso {
    background: rgba(245, 158, 11, 0.1);
    color: #92400e;
}

.station-type-badge.type-seleccion {
    background: rgba(139, 92, 246, 0.1);
    color: #5b21b6;
}

.station-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
}

.station-code {
    font-size: 0.9rem;
    color: #6b7280;
    margin: 0;
}

/* Dropdown Menu */
.dropdown-container {
    position: relative;
}

.dropdown-toggle {
    padding: 0.5rem;
    border: none;
    background: transparent;
    color: #6b7280;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.dropdown-toggle:hover {
    background: #f3f4f6;
    color: #374151;
}

.dropdown-icon {
    font-size: 1.2rem;
    font-weight: bold;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    z-index: 10;
    min-width: 160px;
    overflow: hidden;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    width: 100%;
    border: none;
    background: transparent;
    text-align: left;
    cursor: pointer;
    transition: background-color 0.2s ease;
    font-size: 0.9rem;
}

.dropdown-item:hover {
    background: #f9fafb;
}

.dropdown-item.text-warning {
    color: #d97706;
}

.dropdown-item.text-success {
    color: #059669;
}

.dropdown-item.text-danger {
    color: #dc2626;
}

.item-icon {
    font-size: 1rem;
}

/* Station Card Body */
.station-body {
    padding: 1rem 1.5rem;
}

.location-info, .coordinates-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
    color: #6b7280;
}

.location-icon, .coord-icon {
    font-size: 1rem;
}

.station-description {
    font-size: 0.9rem;
    color: #6b7280;
    line-height: 1.5;
    margin-bottom: 1rem;
}

/* Capacity Section */
.capacity-section {
    margin-bottom: 1rem;
}

.capacity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.capacity-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.capacity-count {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
}

.capacity-bar {
    height: 6px;
    background: #e5e7eb;
    border-radius: 3px;
    overflow: hidden;
}

.capacity-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.capacity-fill.capacity-normal {
    background: linear-gradient(90deg, #10b981, #34d399);
}

.capacity-fill.capacity-high {
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
}

.capacity-fill.capacity-full {
    background: linear-gradient(90deg, #ef4444, #f87171);
}

/* Bike Stats */
.bike-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-item {
    flex: 1;
    text-align: center;
}

.stat-number {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-number.available {
    color: #10b981;
}

.stat-number.in-use {
    color: #3b82f6;
}

.stat-number.maintenance {
    color: #f59e0b;
}

.stat-label {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 500;
}

/* Bike Types */
.bike-types {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.type-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.type-icon {
    font-size: 1rem;
}

/* Station Card Footer */
.station-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-top: 1px solid #f3f4f6;
    background: #f9fafb;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.active {
    background: rgba(16, 185, 129, 0.1);
    color: #065f46;
}

.status-badge.inactive {
    background: rgba(239, 68, 68, 0.1);
    color: #991b1b;
}

.station-buttons {
    display: flex;
    gap: 0.5rem;
}

.view-btn, .edit-btn {
    padding: 0.5rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.view-btn {
    background: rgba(59, 130, 246, 0.1);
    color: #1d4ed8;
}

.view-btn:hover {
    background: rgba(59, 130, 246, 0.2);
}

.edit-btn {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
}

.edit-btn:hover {
    background: rgba(245, 158, 11, 0.2);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    margin: 0 0 1rem 0;
}

.empty-text {
    font-size: 1rem;
    margin: 0 0 2rem 0;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

.empty-action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.empty-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

/* Pagination */
.pagination-container {
    display: flex;
    justify-content: center;
    margin-top: 2rem;
}

.pagination {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.page-btn {
    padding: 0.5rem 1rem;
    border: 1px solid #d1d5db;
    background: white;
    color: #374151;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;
}

.page-btn:hover:not(:disabled) {
    background: #f3f4f6;
    border-color: #9ca3af;
}

.page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.page-info {
    font-size: 0.9rem;
    color: #6b7280;
    font-weight: 500;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
}

.modal-container {
    background: white;
    border-radius: 12px;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
}

.modal-container.large {
    max-width: 800px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid #e5e7eb;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.modal-close {
    padding: 0.5rem;
    border: none;
    background: transparent;
    color: #6b7280;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.2s ease;
    font-size: 1.2rem;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

/* Form Styles */
.modal-form {
    padding: 2rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.form-input, .form-select, .form-textarea {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
}

.form-checkbox {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    cursor: pointer;
    margin: 1rem 0;
}

.form-checkbox input[type="checkbox"] {
    width: 1rem;
    height: 1rem;
    accent-color: #667eea;
}

.checkbox-text {
    font-size: 0.9rem;
    color: #374151;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
}

.cancel-btn, .save-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.cancel-btn {
    background: white;
    color: #6b7280;
    border-color: #d1d5db;
}

.cancel-btn:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.save-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.save-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.save-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Details Modal Styles */
.station-details {
    padding: 2rem;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-item.full-width {
    grid-column: 1 / -1;
}

.detail-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.detail-value {
    font-size: 1rem;
    color: #1f2937;
    font-weight: 500;
}

.detail-value.type-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    width: fit-content;
}

.detail-value.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    width: fit-content;
}

/* Bike Statistics */
.bike-statistics {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e5e7eb;
}

.section-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 1.5rem 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.stats-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.stats-card.available {
    background: rgba(16, 185, 129, 0.05);
    border-color: rgba(16, 185, 129, 0.2);
}

.stats-card.in-use {
    background: rgba(59, 130, 246, 0.05);
    border-color: rgba(59, 130, 246, 0.2);
}

.stats-card.maintenance {
    background: rgba(245, 158, 11, 0.05);
    border-color: rgba(245, 158, 11, 0.2);
}

.stats-icon {
    font-size: 1.5rem;
}

.stats-content {
    flex: 1;
}

.stats-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 0.25rem 0;
}

.stats-label {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .stations-grid {
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    }

    .management-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }

    .header-actions {
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .filters-content {
        flex-direction: column;
        align-items: stretch;
    }

    .filter-group {
        min-width: auto;
    }

    .stations-grid {
        grid-template-columns: 1fr;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .bike-stats {
        flex-direction: column;
        gap: 0.5rem;
    }

    .bike-types {
        flex-direction: column;
        gap: 0.5rem;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .modal-container {
        margin: 0;
        border-radius: 0;
        height: 100vh;
        max-height: none;
    }

    .modal-form {
        padding: 1rem;
    }

    .modal-header {
        padding: 1rem;
    }

    .station-details {
        padding: 1rem;
    }

    .action-btn {
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
    }
}
</style>
