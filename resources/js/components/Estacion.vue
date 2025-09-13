<template>
    <div class="stations-container">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Estaciones</h2>
            <div class="flex space-x-3">
                <button
                    @click="refreshStations"
                    :disabled="loading"
                    class="btn btn-secondary"
                >
                    <svg class="h-4 w-4 mr-2" :class="{'animate-spin': loading}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Actualizar
                </button>
                <button
                    v-if="user.is_admin"
                    @click="showCreateModal = true"
                    class="btn btn-primary"
                >
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva Estación
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Nombre o código..."
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                    <select
                        v-model="filters.type"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">Todos los tipos</option>
                        <option value="carga">Estación de Carga</option>
                        <option value="descanso">Estación de Descanso</option>
                        <option value="seleccion">Estación de Selección</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button
                        @click="clearFilters"
                        class="btn btn-outline w-full"
                    >
                        Limpiar Filtros
                    </button>
                </div>
            </div>
        </div>

        <!-- Stations Grid -->
        <div v-if="loading && stations.length === 0" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-500">Cargando estaciones...</p>
        </div>

        <div v-else-if="filteredStations.length === 0" class="text-center py-12">
            <svg class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0h2M7 7h10M7 11h4m6 0h4M7 15h10" />
            </svg>
            <p class="text-gray-500 text-lg mb-2">No hay estaciones disponibles</p>
            <p class="text-gray-400">Ajusta los filtros o agrega una nueva estación</p>
        </div>

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

                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">
                                {{ station.available_bikes_count }}
                            </div>
                            <div class="text-xs text-gray-500">Disponibles</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-600">
                                {{ station.total_bikes }}
                            </div>
                            <div class="text-xs text-gray-500">Total</div>
                        </div>
                    </div>

                    <!-- Occupancy Bar -->
                    <div class="mb-4">
                        <div class="flex justify-between text-sm text-gray-500 mb-1">
                            <span>Ocupación</span>
                            <span>{{ station.occupancy_percentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div
                                class="h-2 rounded-full transition-all"
                                :class="getOccupancyColor(station.occupancy_percentage)"
                                :style="{ width: station.occupancy_percentage + '%' }"
                            ></div>
                        </div>
                    </div>

                    <!-- Address -->
                    <p v-if="station.address" class="text-sm text-gray-600 mb-4">
                        {{ station.address }}
                    </p>

                    <!-- Actions -->
                    <div class="flex space-x-2">
                        <button
                            @click.stop="viewBikes(station)"
                            class="flex-1 btn btn-outline btn-sm"
                        >
                            Ver Bicicletas
                        </button>
                        <button
                            v-if="canRentFromStation(station)"
                            @click.stop="rentFromStation(station)"
                            class="flex-1 btn btn-primary btn-sm"
                        >
                            Alquilar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Station Detail Modal -->
        <div v-if="selectedStation" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75" @click="closeModal"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                    <!-- Modal Header -->
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">{{ selectedStation.name }}</h3>
                            <p class="text-sm text-gray-500">{{ selectedStation.code }}</p>
                        </div>
                        <button
                            @click="closeModal"
                            class="text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Station Info -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Información</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Tipo:</span>
                                    <span class="font-medium">{{ getTypeText(selectedStation.type) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Capacidad:</span>
                                    <span class="font-medium">{{ selectedStation.capacity }} bicicletas</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Ocupación:</span>
                                    <span class="font-medium">{{ selectedStation.occupancy_percentage }}%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Estado:</span>
                                    <span :class="selectedStation.is_active ? 'text-green-600' : 'text-red-600'" class="font-medium">
                    {{ selectedStation.is_active ? 'Activa' : 'Inactiva' }}
                  </span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Ubicación</h4>
                            <p class="text-sm text-gray-600 mb-2">{{ selectedStation.address }}</p>
                            <div class="text-xs text-gray-500">
                                <p>Lat: {{ selectedStation.latitude }}</p>
                                <p>Lng: {{ selectedStation.longitude }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Available Bikes -->
                    <div v-if="selectedStationBikes.length > 0">
                        <h4 class="font-medium text-gray-900 mb-3">Bicicletas Disponibles</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                            <div
                                v-for="bike in selectedStationBikes"
                                :key="bike.id"
                                class="border border-gray-200 rounded-lg p-3"
                            >
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ bike.code }}</p>
                                        <p class="text-sm text-gray-500">{{ bike.type }}</p>
                                    </div>
                                    <span
                                        class="px-2 py-1 rounded text-xs font-medium"
                                        :class="bike.can_be_rented ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                    >
                    {{ bike.can_be_rented ? 'Disponible' : 'No disponible' }}
                  </span>
                                </div>
                                <div v-if="bike.type === 'electrica'" class="mb-2">
                                    <div class="flex justify-between text-sm text-gray-500 mb-1">
                                        <span>Batería</span>
                                        <span>{{ bike.battery_level }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                                        <div
                                            class="h-1.5 rounded-full transition-all"
                                            :class="getBatteryColor(bike.battery_level)"
                                            :style="{ width: bike.battery_level + '%' }"
                                        ></div>
                                    </div>
                                </div>
                                <button
                                    v-if="bike.can_be_rented && !currentUsage"
                                    @click="rentBike(bike)"
                                    class="w-full btn btn-primary btn-sm mt-2"
                                >
                                    Alquilar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end space-x-3">
                        <button @click="closeModal" class="btn btn-secondary">
                            Cerrar
                        </button>
                        <button
                            v-if="user.is_admin"
                            @click="editStation(selectedStation)"
                            class="btn btn-primary"
                        >
                            Editar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rent Success Modal -->
        <div v-if="showRentSuccess" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-6">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">¡Alquiler Exitoso!</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Has alquilado la bicicleta {{ rentedBike?.code }}. Disfruta tu viaje.
                        </p>
                        <button
                            @click="closeRentSuccess"
                            class="w-full btn btn-primary"
                        >
                            Entendido
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'StationsComponent',

    props: {
        user: {
            type: Object,
            required: true
        },
        currentUsage: {
            type: Object,
            default: null
        }
    },

    data() {
        return {
            stations: [],
            selectedStation: null,
            selectedStationBikes: [],
            loading: false,
            showCreateModal: false,
            showRentSuccess: false,
            rentedBike: null,
            filters: {
                search: '',
                type: ''
            }
        }
    },

    computed: {
        filteredStations() {
            return this.stations.filter(station => {
                const matchesSearch = !this.filters.search ||
                    station.name.toLowerCase().includes(this.filters.search.toLowerCase()) ||
                    station.code.toLowerCase().includes(this.filters.search.toLowerCase());

                const matchesType = !this.filters.type || station.type === this.filters.type;

                return matchesSearch && matchesType;
            });
        }
    },

    async mounted() {
        await this.loadStations();
    },

    methods: {
        async loadStations() {
            this.loading = true;
            try {
                const response = await axios.get('/api/stations');
                if (response.data.success) {
                    this.stations = response.data.data;
                }
            } catch (error) {
                console.error('Error loading stations:', error);
                this.$emit('show-error', 'Error al cargar las estaciones');
            } finally {
                this.loading = false;
            }
        },

        async refreshStations() {
            await this.loadStations();
        },

        async selectStation(station) {
            this.selectedStation = station;
            await this.loadStationBikes(station.id);
        },

        async loadStationBikes(stationId) {
            try {
                const response = await axios.get(`/api/stations/${stationId}/available-bikes`);
                if (response.data.success) {
                    this.selectedStationBikes = response.data.data;
                }
            } catch (error) {
                console.error('Error loading station bikes:', error);
                this.selectedStationBikes = [];
            }
        },

        closeModal() {
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
                this.$emit('show-error', error.response?.data?.message || 'Error al alquilar la bicicleta');
            }
        },

        async rentFromStation(station) {
            await this.selectStation(station);
        },

        closeRentSuccess() {
            this.showRentSuccess = false;
            this.rentedBike = null;
        },

        clearFilters() {
            this.filters.search = '';
            this.filters.type = '';
        },

        // Utility methods
        getTypeClass(type) {
            const classes = {
                'carga': 'bg-yellow-100 text-yellow-800',
                'descanso': 'bg-blue-100 text-blue-800',
                'seleccion': 'bg-green-100 text-green-800'
            };
            return classes[type] || 'bg-gray-100 text-gray-800';
        },

        getTypeText(type) {
            const texts = {
                'carga': 'Carga',
                'descanso': 'Descanso',
                'seleccion': 'Selección'
            };
            return texts[type] || type;
        },

        getOccupancyColor(percentage) {
            if (percentage >= 80) return 'bg-red-500';
            if (percentage >= 60) return 'bg-yellow-500';
            return 'bg-green-500';
        },

        getBatteryColor(level) {
            if (level >= 60) return 'bg-green-500';
            if (level >= 30) return 'bg-yellow-500';
            return 'bg-red-500';
        },

        viewBikes(station) {
            this.$emit('view-station-bikes', station);
        },

        editStation(station) {
            this.$emit('edit-station', station);
        }
    }
}
</script>
