<template>
    <div class="bikes-management">
        <!-- Header -->
        <div class="management-header">
            <div class="header-info">
                <h2 class="management-title">🚲 Gestión de Bicicletas</h2>
                <p class="management-subtitle">Administra las bicicletas del sistema</p>
            </div>
            <div class="header-actions" v-if="isAdmin">
                <button @click="openCreateModal" class="action-btn primary">
                    <span class="btn-icon">+</span>
                    Nueva Bicicleta
                </button>
                <button @click="exportData" class="action-btn secondary">
                    <span class="btn-icon">📊</span>
                    Exportar
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
                        placeholder="Código de bicicleta..."
                        @input="filterBikes"
                    >
                </div>
                <div class="filter-group">
                    <label class="filter-label">Tipo</label>
                    <select class="filter-select" v-model="filters.type" @change="filterBikes">
                        <option value="">Todos los tipos</option>
                        <option value="tradicional">Tradicional</option>
                        <option value="electrica">Eléctrica</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Estado</label>
                    <select class="filter-select" v-model="filters.status" @change="filterBikes">
                        <option value="">Todos</option>
                        <option value="disponible">Disponible</option>
                        <option value="en_uso">En Uso</option>
                        <option value="en_reparacion">En Reparación</option>
                        <option value="mantenimiento">Mantenimiento</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">Estación</label>
                    <select class="filter-select" v-model="filters.station_id" @change="filterBikes">
                        <option value="">Todas las estaciones</option>
                        <option value="unassigned">Sin asignar</option>
                        <option v-for="station in stations" :key="station.id" :value="station.id">
                            {{ station.name }}
                        </option>
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

        <!-- Summary Stats -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon">🚲</div>
                <div class="stat-content">
                    <div class="stat-number">{{ totalBikes }}</div>
                    <div class="stat-label">Total</div>
                </div>
            </div>
            <div class="stat-card available">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <div class="stat-number">{{ availableBikes }}</div>
                    <div class="stat-label">Disponibles</div>
                </div>
            </div>
            <div class="stat-card in-use">
                <div class="stat-icon">🔄</div>
                <div class="stat-content">
                    <div class="stat-number">{{ bikesInUse }}</div>
                    <div class="stat-label">En Uso</div>
                </div>
            </div>
            <div class="stat-card maintenance">
                <div class="stat-icon">🔧</div>
                <div class="stat-content">
                    <div class="stat-number">{{ bikesInMaintenance }}</div>
                    <div class="stat-label">Mantenimiento</div>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
            <div class="loading-spinner"></div>
            <p>Cargando bicicletas...</p>
        </div>

        <!-- Bikes Grid -->
        <div v-else-if="filteredBikes.length > 0" class="bikes-grid">
            <div v-for="bike in paginatedBikes" :key="bike.id" class="bike-card">
                <div class="bike-header">
                    <div class="bike-info">
                        <div class="bike-type-badge" :class="getTypeBadgeClass(bike.type)">
                            {{ getTypeIcon(bike.type) }} {{ getTypeText(bike.type) }}
                        </div>
                        <h3 class="bike-code">{{ bike.code }}</h3>
                        <div class="bike-status-badge" :class="getStatusBadgeClass(bike.status)">
                            {{ getStatusIcon(bike.status) }} {{ getStatusText(bike.status) }}
                        </div>
                    </div>
                    <div class="bike-actions" v-if="isAdmin">
                        <div class="dropdown-container">
                            <button @click="toggleDropdown(bike.id)" class="dropdown-toggle">
                                <span class="dropdown-icon">⋮</span>
                            </button>
                            <div v-if="openDropdown === bike.id" class="dropdown-menu">
                                <button @click="viewBike(bike)" class="dropdown-item">
                                    <span class="item-icon">👁</span>
                                    Ver Detalles
                                </button>
                                <button @click="editBike(bike)" class="dropdown-item">
                                    <span class="item-icon">✏</span>
                                    Editar
                                </button>
                                <button v-if="bike.type === 'electrica'"
                                        @click="updateBattery(bike)"
                                        class="dropdown-item">
                                    <span class="item-icon">🔋</span>
                                    Actualizar Batería
                                </button>
                                <button @click="moveToStation(bike)" class="dropdown-item">
                                    <span class="item-icon">📍</span>
                                    Mover Estación
                                </button>
                                <button @click="toggleBikeStatus(bike)"
                                        class="dropdown-item"
                                        :class="{ 'text-warning': bike.is_active, 'text-success': !bike.is_active }">
                                    <span class="item-icon">{{ bike.is_active ? '⏸' : '▶' }}</span>
                                    {{ bike.is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                                <button v-if="bike.status !== 'en_uso'"
                                        @click="deleteBike(bike)"
                                        class="dropdown-item text-danger">
                                    <span class="item-icon">🗑</span>
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bike-body">
                    <!-- Station Info -->
                    <div class="station-info">
                        <span class="station-icon">🏢</span>
                        <span class="station-text">
                            {{ bike.station ? bike.station.name : 'Sin asignar' }}
                        </span>
                    </div>

                    <!-- Battery Level (for electric bikes) -->
                    <div v-if="bike.type === 'electrica'" class="battery-info">
                        <span class="battery-icon">🔋</span>
                        <span class="battery-text">Batería: {{ bike.battery_level || 0 }}%</span>
                        <div class="battery-bar">
                            <div class="battery-fill"
                                 :class="getBatteryClass(bike.battery_level)"
                                 :style="{ width: (bike.battery_level || 0) + '%' }"></div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div v-if="bike.description" class="bike-description">
                        {{ truncateText(bike.description, 80) }}
                    </div>

                    <!-- Bike Details -->
                    <div class="bike-details">
                        <div class="detail-row">
                            <span class="detail-label">Compra:</span>
                            <span class="detail-value">
                                {{ bike.purchase_date ? formatDate(bike.purchase_date) : 'N/A' }}
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Último Mant.:</span>
                            <span class="detail-value">
                                {{ bike.last_maintenance ? formatDate(bike.last_maintenance) : 'N/A' }}
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Precio:</span>
                            <span class="detail-value">
                                {{ bike.purchase_price ? formatDate(bike.purchase_price) : 'N/A' }}
                            </span>
                        </div>
                    </div>

                    <!-- Maintenance Warning -->
                    <div v-if="needsMaintenance(bike)" class="maintenance-warning">
                        <span class="warning-icon">⚠️</span>
                        <span class="warning-text">Requiere mantenimiento</span>
                    </div>
                </div>

                <div class="bike-footer">
                    <div class="status-indicators">
                        <span class="activity-badge" :class="{ 'active': bike.is_active, 'inactive': !bike.is_active }">
                            {{ bike.is_active ? 'Activa' : 'Inactiva' }}
                        </span>
                        <span v-if="bike.type === 'electrica' && bike.battery_level < 20" class="low-battery-badge">
                            Batería Baja
                        </span>
                    </div>
                    <div class="bike-buttons">
                        <button @click="viewBike(bike)" class="view-btn">
                            <span class="btn-icon">👁</span>
                        </button>
                        <button v-if="isAdmin" @click="editBike(bike)" class="edit-btn">
                            <span class="btn-icon">✏</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
            <div class="empty-icon">🚲</div>
            <h3 class="empty-title">No hay bicicletas</h3>
            <p class="empty-text">
                {{ hasFilters ? 'No se encontraron bicicletas con los filtros aplicados.' : 'No hay bicicletas registradas en el sistema.' }}
            </p>
            <button v-if="isAdmin && !hasFilters" @click="openCreateModal" class="empty-action-btn">
                <span class="btn-icon">+</span>
                Crear Primera Bicicleta
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
                        {{ editingBike ? 'Editar Bicicleta' : 'Nueva Bicicleta' }}
                    </h3>
                    <button @click="closeModal" class="modal-close">✖</button>
                </div>

                <form @submit.prevent="saveBike" class="modal-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Código *</label>
                            <input type="text" v-model="bikeForm.code" class="form-input" maxlength="20" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tipo *</label>
                            <select v-model="bikeForm.type" class="form-select" required @change="onTypeChange">
                                <option value="">Seleccionar tipo</option>
                                <option value="tradicional">Tradicional</option>
                                <option value="electrica">Eléctrica</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Estado *</label>
                            <select v-model="bikeForm.status" class="form-select" required>
                                <option value="">Seleccionar estado</option>
                                <option value="disponible">Disponible</option>
                                <option value="en_uso">En Uso</option>
                                <option value="en_reparacion">En Reparación</option>
                                <option value="mantenimiento">Mantenimiento</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Estación</label>
                            <select v-model="bikeForm.station_id" class="form-select">
                                <option value="">Sin asignar</option>
                                <option v-for="station in availableStations" :key="station.id" :value="station.id">
                                    {{ station.name }} ({{ station.available_slots }} espacios)
                                </option>
                            </select>
                        </div>
                    </div>

                    <div v-if="bikeForm.type === 'electrica'" class="form-group">
                        <label class="form-label">Nivel de Batería *</label>
                        <input type="number" v-model="bikeForm.battery_level" class="form-input"
                               min="0" max="100" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Descripción</label>
                        <textarea v-model="bikeForm.description" class="form-textarea" rows="3"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Precio de Compra</label>
                            <input type="number" v-model="bikeForm.purchase_price" class="form-input"
                                   step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Fecha de Compra</label>
                            <input type="date" v-model="bikeForm.purchase_date" class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Último Mantenimiento</label>
                        <input type="date" v-model="bikeForm.last_maintenance" class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-checkbox">
                            <input type="checkbox" v-model="bikeForm.is_active">
                            <span class="checkbox-text">Bicicleta activa</span>
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

        <!-- Battery Update Modal -->
        <div v-if="showBatteryModal" class="modal-overlay" @click="closeBatteryModal">
            <div class="modal-container small" @click.stop>
                <div class="modal-header">
                    <h3 class="modal-title">Actualizar Batería</h3>
                    <button @click="closeBatteryModal" class="modal-close">✖</button>
                </div>

                <form @submit.prevent="saveBatteryLevel" class="modal-form">
                    <div class="form-group">
                        <label class="form-label">Nivel de Batería</label>
                        <input type="number" v-model="batteryForm.level" class="form-input"
                               min="0" max="100" required>
                        <small class="form-help">Ingresa el nivel actual de batería (0-100%)</small>
                    </div>

                    <div class="modal-actions">
                        <button type="button" @click="closeBatteryModal" class="cancel-btn">Cancelar</button>
                        <button type="submit" class="save-btn" :disabled="saving">
                            {{ saving ? 'Actualizando...' : 'Actualizar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Move to Station Modal -->
        <div v-if="showMoveModal" class="modal-overlay" @click="closeMoveModal">
            <div class="modal-container small" @click.stop>
                <div class="modal-header">
                    <h3 class="modal-title">Mover a Estación</h3>
                    <button @click="closeMoveModal" class="modal-close">✖</button>
                </div>

                <form @submit.prevent="saveStationMove" class="modal-form">
                    <div class="form-group">
                        <label class="form-label">Estación Destino *</label>
                        <select v-model="moveForm.station_id" class="form-select" required>
                            <option value="">Seleccionar estación</option>
                            <option v-for="station in availableStations" :key="station.id" :value="station.id">
                                {{ station.name }} ({{ station.available_slots }} espacios disponibles)
                            </option>
                        </select>
                        <small class="form-help">La bicicleta cambiará su estado a "Disponible"</small>
                    </div>

                    <div class="modal-actions">
                        <button type="button" @click="closeMoveModal" class="cancel-btn">Cancelar</button>
                        <button type="submit" class="save-btn" :disabled="saving">
                            {{ saving ? 'Moviendo...' : 'Mover' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- View Details Modal -->
        <div v-if="showDetailsModal" class="modal-overlay" @click="closeDetailsModal">
            <div class="modal-container large" @click.stop>
                <div class="modal-header">
                    <h3 class="modal-title">Detalles de la Bicicleta</h3>
                    <button @click="closeDetailsModal" class="modal-close">✖</button>
                </div>

                <div v-if="selectedBike" class="bike-details-view">
                    <div class="details-grid">
                        <div class="detail-item">
                            <label class="detail-label">Código</label>
                            <span class="detail-value">{{ selectedBike.code }}</span>
                        </div>
                        <div class="detail-item">
                            <label class="detail-label">Tipo</label>
                            <span class="detail-value type-badge" :class="getTypeBadgeClass(selectedBike.type)">
                                {{ getTypeIcon(selectedBike.type) }} {{ getTypeText(selectedBike.type) }}
                            </span>
                        </div>
                        <div class="detail-item">
                            <label class="detail-label">Estado</label>
                            <span class="detail-value status-badge" :class="getStatusBadgeClass(selectedBike.status)">
                                {{ getStatusIcon(selectedBike.status) }} {{ getStatusText(selectedBike.status) }}
                            </span>
                        </div>
                        <div class="detail-item">
                            <label class="detail-label">Actividad</label>
                            <span class="detail-value activity-badge" :class="{ 'active': selectedBike.is_active, 'inactive': !selectedBike.is_active }">
                                {{ selectedBike.is_active ? 'Activa' : 'Inactiva' }}
                            </span>
                        </div>
                        <div class="detail-item">
                            <label class="detail-label">Estación</label>
                            <span class="detail-value">
                                {{ selectedBike.station ? selectedBike.station.name : 'Sin asignar' }}
                            </span>
                        </div>
                        <div v-if="selectedBike.type === 'electrica'" class="detail-item">
                            <label class="detail-label">Batería</label>
                            <span class="detail-value">
                                {{ selectedBike.battery_level || 0 }}%
                                <span class="battery-indicator" :class="getBatteryClass(selectedBike.battery_level)"></span>
                            </span>
                        </div>
                        <div class="detail-item">
                            <label class="detail-label">Precio de Compra</label>
                            <span class="detail-value">{{ selectedBike.purchase_price ?  formatDate(selectedBike.purchase_price) : 'N/A' }}</span>
                        </div>
                        <div class="detail-item">
                            <label class="detail-label">Fecha de Compra</label>
                            <span class="detail-value">{{ selectedBike.purchase_date ? formatDate(selectedBike.purchase_date) : 'N/A' }}</span>
                        </div>
                        <div class="detail-item">
                            <label class="detail-label">Último Mantenimiento</label>
                            <span class="detail-value">{{ selectedBike.last_maintenance ? formatDate(selectedBike.last_maintenance) : 'N/A' }}</span>
                        </div>
                        <div v-if="selectedBike.description" class="detail-item full-width">
                            <label class="detail-label">Descripción</label>
                            <span class="detail-value">{{ selectedBike.description }}</span>
                        </div>
                    </div>

                    <!-- Maintenance Status -->
                    <div v-if="needsMaintenance(selectedBike)" class="maintenance-alert">
                        <div class="alert-icon">⚠️</div>
                        <div class="alert-content">
                            <h4 class="alert-title">Mantenimiento Requerido</h4>
                            <p class="alert-text">
                                Esta bicicleta requiere mantenimiento.
                                {{ selectedBike.last_maintenance
                                ? 'Último mantenimiento hace ' + getDaysAgo(selectedBike.last_maintenance) + ' días.'
                                : 'No hay registro de mantenimiento previo.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BikesManagement',
    data() {
        return {
            bikes: [],
            stations: [],
            filteredBikes: [],
            loading: true,
            showModal: false,
            showDetailsModal: false,
            showBatteryModal: false,
            showMoveModal: false,
            editingBike: null,
            selectedBike: null,
            selectedBikeForBattery: null,
            selectedBikeForMove: null,
            saving: false,
            openDropdown: null,

            // Filters
            filters: {
                search: '',
                type: '',
                status: '',
                station_id: ''
            },

            // Pagination
            currentPage: 1,
            itemsPerPage: 12,

            // Form data
            bikeForm: {
                code: '',
                type: '',
                status: 'disponible',
                station_id: '',
                battery_level: null,
                description: '',
                purchase_price: null,
                purchase_date: '',
                last_maintenance: '',
                is_active: true
            },

            batteryForm: {
                level: 100
            },

            moveForm: {
                station_id: ''
            }
        }
    },

    computed: {
        isAdmin() {
            return this.$parent.isAdmin;
        },

        hasFilters() {
            return this.filters.search || this.filters.type || this.filters.status || this.filters.station_id;
        },

        totalPages() {
            return Math.ceil(this.filteredBikes.length / this.itemsPerPage);
        },

        paginatedBikes() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.filteredBikes.slice(start, end);
        },

        totalBikes() {
            return this.bikes.length;
        },

        availableBikes() {
            return this.bikes.filter(bike => bike.status === 'disponible' && bike.is_active).length;
        },

        bikesInUse() {
            return this.bikes.filter(bike => bike.status === 'en_uso' && bike.is_active).length;
        },

        bikesInMaintenance() {
            return this.bikes.filter(bike =>
                (bike.status === 'en_reparacion' || bike.status === 'mantenimiento') && bike.is_active
            ).length;
        },

        availableStations() {
            return this.stations.filter(station => station.is_active && station.available_slots > 0);
        }
    },

    mounted() {
        this.loadData();
    },

    methods: {
        async loadData() {
            await Promise.all([
                this.loadBikes(),
                this.loadStations()
            ]);
        },

        async loadBikes() {
            try {
                this.loading = true;
                const response = await axios.get('/bikes', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                this.bikes = response.data.bikes || [];
                this.filterBikes();
            } catch (error) {
                console.error('Error cargando bicicletas:', error);
                this.$emit('show-alert', 'Error al cargar las bicicletas', 'error');
            } finally {
                this.loading = false;
            }
        },

        async loadStations() {
            try {
                const response = await axios.get('/stations/data', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.data.success) {
                    this.stations = response.data.stations || [];
                    console.log('Estaciones cargadas:', this.stations); // Para debugging
                } else {
                    console.error('Error en respuesta de estaciones:', response.data);
                }
            } catch (error) {
                console.error('Error cargando estaciones:', error);
                this.$emit('show-alert', 'Error al cargar las estaciones', 'error');
            }
        },

        filterBikes() {
            this.filteredBikes = this.bikes.filter(bike => {
                const matchesSearch = !this.filters.search ||
                    bike.code.toLowerCase().includes(this.filters.search.toLowerCase());

                const matchesType = !this.filters.type || bike.type === this.filters.type;

                const matchesStatus = !this.filters.status || bike.status === this.filters.status;

                const matchesStation = !this.filters.station_id ||
                    (this.filters.station_id === 'unassigned' && !bike.station_id) ||
                    (bike.station_id && bike.station_id.toString() === this.filters.station_id);

                return matchesSearch && matchesType && matchesStatus && matchesStation;
            });

            this.currentPage = 1;
        },

        clearFilters() {
            this.filters = {
                search: '',
                type: '',
                status: '',
                station_id: ''
            };
            this.filterBikes();
        },

        changePage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },

        openCreateModal() {
            this.editingBike = null;
            this.bikeForm = {
                code: '',
                type: '',
                status: 'disponible',
                station_id: '',
                battery_level: null,
                description: '',
                purchase_price: null,
                purchase_date: '',
                last_maintenance: '',
                is_active: true
            };
            this.showModal = true;
        },

        editBike(bike) {
            this.editingBike = bike;
            this.bikeForm = {
                code: bike.code,
                type: bike.type,
                status: bike.status,
                station_id: bike.station_id || '',
                battery_level: bike.battery_level,
                description: bike.description || '',
                purchase_price: bike.purchase_price,
                purchase_date: bike.purchase_date || '',
                last_maintenance: bike.last_maintenance || '',
                is_active: bike.is_active
            };
            this.showModal = true;
            this.openDropdown = null;
        },

        viewBike(bike) {
            this.selectedBike = bike;
            this.showDetailsModal = true;
            this.openDropdown = null;
        },

        updateBattery(bike) {
            this.selectedBikeForBattery = bike;
            this.batteryForm.level = bike.battery_level || 0;
            this.showBatteryModal = true;
            this.openDropdown = null;
        },

        moveToStation(bike) {
            this.selectedBikeForMove = bike;
            this.moveForm.station_id = '';
            this.showMoveModal = true;
            this.openDropdown = null;
        },

        closeModal() {
            this.showModal = false;
            this.editingBike = null;
        },

        closeDetailsModal() {
            this.showDetailsModal = false;
            this.selectedBike = null;
        },

        closeBatteryModal() {
            this.showBatteryModal = false;
            this.selectedBikeForBattery = null;
        },

        closeMoveModal() {
            this.showMoveModal = false;
            this.selectedBikeForMove = null;
        },

        onTypeChange() {
            if (this.bikeForm.type !== 'electrica') {
                this.bikeForm.battery_level = null;
            } else if (this.bikeForm.battery_level === null) {
                this.bikeForm.battery_level = 100;
            }
        },

        async saveBike() {
            try {
                this.saving = true;

                const url = this.editingBike
                    ? `/bikes/${this.editingBike.id}`
                    : '/bikes';

                const method = this.editingBike ? 'PUT' : 'POST';

                const response = await axios({
                    method,
                    url,
                    data: this.bikeForm,
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                this.$emit('show-alert',
                    this.editingBike ? 'Bicicleta actualizada exitosamente' : 'Bicicleta creada exitosamente',
                    'success'
                );

                this.closeModal();
                await this.loadBikes();

            } catch (error) {
                console.error('Error guardando bicicleta:', error);
                this.$emit('show-alert', 'Error al guardar la bicicleta', 'error');
            } finally {
                this.saving = false;
            }
        },

        async saveBatteryLevel() {
            try {
                this.saving = true;

                await axios.patch(`/bikes/${this.selectedBikeForBattery.id}/update-battery`, {
                    battery_level: this.batteryForm.level
                }, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                this.$emit('show-alert', 'Nivel de batería actualizado exitosamente', 'success');
                this.closeBatteryModal();
                await this.loadBikes();

            } catch (error) {
                console.error('Error actualizando batería:', error);
                this.$emit('show-alert', 'Error al actualizar el nivel de batería', 'error');
            } finally {
                this.saving = false;
            }
        },

        async saveStationMove() {
            try {
                this.saving = true;

                await axios.patch(`/bikes/${this.selectedBikeForMove.id}/move-to-station`, {
                    station_id: this.moveForm.station_id
                }, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                this.$emit('show-alert', 'Bicicleta movida exitosamente', 'success');
                this.closeMoveModal();
                await this.loadBikes();

            } catch (error) {
                console.error('Error moviendo bicicleta:', error);
                this.$emit('show-alert', 'Error al mover la bicicleta', 'error');
            } finally {
                this.saving = false;
            }
        },

        async toggleBikeStatus(bike) {
            try {
                await axios.patch(`/bikes/${bike.id}/toggle-status`, {}, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                const status = bike.is_active ? 'desactivada' : 'activada';
                this.$emit('show-alert', `Bicicleta ${status} exitosamente`, 'success');

                await this.loadBikes();
            } catch (error) {
                console.error('Error cambiando estado:', error);
                this.$emit('show-alert', 'Error al cambiar el estado de la bicicleta', 'error');
            }

            this.openDropdown = null;
        },

        async deleteBike(bike) {
            if (!confirm(`¿Estás seguro de eliminar la bicicleta "${bike.code}"?`)) {
                return;
            }

            try {
                await axios.delete(`/bikes/${bike.id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                this.$emit('show-alert', 'Bicicleta eliminada exitosamente', 'success');
                await this.loadBikes();
            } catch (error) {
                console.error('Error eliminando bicicleta:', error);
                this.$emit('show-alert', 'Error al eliminar la bicicleta', 'error');
            }

            this.openDropdown = null;
        },

        toggleDropdown(bikeId) {
            this.openDropdown = this.openDropdown === bikeId ? null : bikeId;
        },

        exportData() {
            window.open('/bikes/data', '_blank');
        },

        // Utility methods
        getTypeText(type) {
            const types = {
                'tradicional': 'Tradicional',
                'electrica': 'Eléctrica'
            };
            return types[type] || type;
        },

        getTypeIcon(type) {
            const icons = {
                'tradicional': '🚲',
                'electrica': '⚡'
            };
            return icons[type] || '🚲';
        },

        getTypeBadgeClass(type) {
            const classes = {
                'tradicional': 'type-traditional',
                'electrica': 'type-electric'
            };
            return classes[type] || '';
        },

        getStatusText(status) {
            const statuses = {
                'disponible': 'Disponible',
                'en_uso': 'En Uso',
                'en_reparacion': 'En Reparación',
                'mantenimiento': 'Mantenimiento'
            };
            return statuses[status] || status;
        },

        getStatusIcon(status) {
            const icons = {
                'disponible': '✅',
                'en_uso': '🔄',
                'en_reparacion': '🔧',
                'mantenimiento': '⚙️'
            };
            return icons[status] || '❓';
        },

        getStatusBadgeClass(status) {
            const classes = {
                'disponible': 'status-available',
                'en_uso': 'status-in-use',
                'en_reparacion': 'status-repair',
                'mantenimiento': 'status-maintenance'
            };
            return classes[status] || '';
        },

        getBatteryClass(level) {
            if (level >= 80) return 'battery-high';
            if (level >= 50) return 'battery-medium';
            if (level >= 20) return 'battery-low';
            return 'battery-critical';
        },

        needsMaintenance(bike) {
            if (!bike.last_maintenance) return true;
            const lastMaintenance = new Date(bike.last_maintenance);
            const monthsAgo = (new Date() - lastMaintenance) / (1000 * 60 * 60 * 24 * 30);
            return monthsAgo >= 3;
        },

        formatDate(dateString) {
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('es-GT', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            } catch (error) {
                return dateString;
            }
        },

        getDaysAgo(dateString) {
            try {
                const date = new Date(dateString);
                const now = new Date();
                const diffTime = Math.abs(now - date);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                return diffDays;
            } catch (error) {
                return 0;
            }
        },

        truncateText(text, length) {
            if (!text) return '';
            return text.length > length ? text.substring(0, length) + '...' : text;
        }
    }
}
</script>

<style scoped>
/* Base Styles */
.bikes-management {
    padding: 0;
    background: transparent;
}

/* Header - Reusing styles from StationsManagement */
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
}

.action-btn.primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.action-btn.primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.action-btn.secondary {
    background: white;
    color: #374151;
    border: 1px solid #d1d5db;
}

.action-btn.secondary:hover {
    background: #f9fafb;
    transform: translateY(-1px);
}

.btn-icon {
    font-size: 1rem;
}

/* Filters Card */
.filters-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filters-content {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-label {
    font-weight: 500;
    color: #374151;
    font-size: 0.9rem;
}

.filter-input, .filter-select {
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
}

.filter-input:focus, .filter-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.clear-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #f3f4f6;
    color: #374151;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.clear-btn:hover {
    background: #e5e7eb;
}

/* Stats Overview */
.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.stat-card.available {
    border-left: 4px solid #10b981;
}

.stat-card.in-use {
    border-left: 4px solid #f59e0b;
}

.stat-card.maintenance {
    border-left: 4px solid #6b7280;
}

.stat-icon {
    font-size: 2rem;
    opacity: 0.8;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.9rem;
    color: #6b7280;
    font-weight: 500;
}

/* Loading - Reusing styles */
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
    border: 3px solid #f3f4f6;
    border-top: 3px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Bikes Grid */
.bikes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.bike-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    overflow: hidden;
}

.bike-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.bike-header {
    padding: 1.5rem 1.5rem 1rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.bike-info {
    flex: 1;
}

.bike-type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: inline-block;
}

.type-traditional {
    background: #f3f4f6;
    color: #374151;
}

.type-electric {
    background: #fef3c7;
    color: #92400e;
}

.bike-code {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
}

.bike-status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
}

.status-available {
    background: #d1fae5;
    color: #065f46;
}

.status-in-use {
    background: #fef3c7;
    color: #92400e;
}

.status-repair {
    background: #fee2e2;
    color: #991b1b;
}

.status-maintenance {
    background: #e0e7ff;
    color: #3730a3;
}

/* Dropdown styles - Reusing from StationsManagement */
.bike-actions {
    position: relative;
}

.dropdown-container {
    position: relative;
}

.dropdown-toggle {
    background: none;
    border: none;
    padding: 0.5rem;
    cursor: pointer;
    border-radius: 6px;
    transition: background-color 0.3s ease;
}

.dropdown-toggle:hover {
    background: #f3f4f6;
}

.dropdown-icon {
    font-size: 1.2rem;
    color: #6b7280;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    min-width: 180px;
    z-index: 100;
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.75rem 1rem;
    background: none;
    border: none;
    text-align: left;
    cursor: pointer;
    transition: background-color 0.3s ease;
    font-size: 0.9rem;
}

.dropdown-item:hover {
    background: #f9fafb;
}

.text-warning {
    color: #f59e0b;
}

.text-success {
    color: #10b981;
}

.text-danger {
    color: #ef4444;
}

.item-icon {
    font-size: 0.9rem;
}

.bike-body {
    padding: 0 1.5rem 1rem 1.5rem;
}

.station-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
    color: #6b7280;
}

.station-icon {
    font-size: 0.8rem;
}

.battery-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    font-size: 0.9rem;
    color: #6b7280;
}

.battery-icon {
    font-size: 0.8rem;
}

.battery-bar {
    width: 60px;
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
    margin-left: 0.5rem;
}

.battery-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.battery-high {
    background: #10b981;
}

.battery-medium {
    background: #f59e0b;
}

.battery-low {
    background: #ef4444;
}

.battery-critical {
    background: #dc2626;
}

.bike-description {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    line-height: 1.4;
}

.bike-details {
    margin-bottom: 1rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
}

.detail-label {
    color: #6b7280;
    font-weight: 500;
}

.detail-value {
    color: #374151;
    font-weight: 500;
}

.maintenance-warning {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #fef3c7;
    color: #92400e;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-size: 0.85rem;
    margin-bottom: 1rem;
}

.warning-icon {
    font-size: 0.9rem;
}

.bike-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f9fafb;
}

.status-indicators {
    display: flex;
    gap: 0.5rem;
}

.activity-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.activity-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.activity-badge.inactive {
    background: #f3f4f6;
    color: #6b7280;
}

.low-battery-badge {
    background: #fee2e2;
    color: #991b1b;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.bike-buttons {
    display: flex;
    gap: 0.5rem;
}

.view-btn, .edit-btn {
    padding: 0.5rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.view-btn {
    background: #e0e7ff;
    color: #3730a3;
}

.view-btn:hover {
    background: #c7d2fe;
}

.edit-btn {
    background: #f3f4f6;
    color: #374151;
}

.edit-btn:hover {
    background: #e5e7eb;
}

/* Empty State - Reusing styles */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.7;
}

.empty-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 1rem;
}

.empty-text {
    margin-bottom: 2rem;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.5;
}

.empty-action-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.empty-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

/* Pagination - Reusing styles */
.pagination-container {
    display: flex;
    justify-content: center;
    margin-top: 2rem;
}

.pagination {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: white;
    padding: 1rem 2rem;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.page-btn {
    background: #f3f4f6;
    color: #374151;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.page-btn:hover:not(:disabled) {
    background: #e5e7eb;
}

.page-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.page-info {
    color: #6b7280;
    font-weight: 500;
}

/* Modal Styles - Reusing and extending */
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
    padding: 2rem;
}

.modal-container {
    background: white;
    border-radius: 12px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.modal-container.large {
    max-width: 800px;
}

.modal-container.small {
    max-width: 400px;
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
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #6b7280;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.modal-form {
    padding: 2rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}

.form-input, .form-select, .form-textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
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
    gap: 0.5rem;
    cursor: pointer;
}

.checkbox-text {
    color: #374151;
    font-weight: 500;
}

.form-help {
    color: #6b7280;
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
}

.cancel-btn {
    padding: 0.75rem 1.5rem;
    background: white;
    color: #374151;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.cancel-btn:hover {
    background: #f9fafb;
}

.save-btn {
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.save-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.save-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

/* Bike Details View */
.bike-details-view {
    padding: 2rem;
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
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
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}

.detail-value {
    color: #1f2937;
    font-size: 1rem;
}

.detail-value.type-badge {
    align-self: flex-start;
}

.battery-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-left: 0.5rem;
}

.maintenance-alert {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 8px;
    padding: 1rem;
    display: flex;
    gap: 0.75rem;
    margin-top: 1rem;
}

.alert-icon {
    font-size: 1.2rem;
    color: #f59e0b;
}

.alert-content {
    flex: 1;
}

.alert-title {
    font-size: 1rem;
    font-weight: 600;
    color: #92400e;
    margin: 0 0 0.5rem 0;
}

.alert-text {
    color: #92400e;
    margin: 0;
    font-size: 0.9rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .management-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .header-actions {
        width: 100%;
        justify-content: flex-start;
    }

    .filters-content {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .stats-overview {
        grid-template-columns: repeat(2, 1fr);
    }

    .bikes-grid {
        grid-template-columns: 1fr;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .details-grid {
        grid-template-columns: 1fr;
    }

    .modal-container {
        margin: 1rem;
        max-width: none;
        width: calc(100% - 2rem);
    }
}

@media (max-width: 480px) {
    .stats-overview {
        grid-template-columns: 1fr;
    }

    .action-btn {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }

    .bike-header {
        padding: 1rem;
    }

    .bike-body {
        padding: 0 1rem 1rem 1rem;
    }

    .bike-footer {
        padding: 1rem;
    }
}
</style>
