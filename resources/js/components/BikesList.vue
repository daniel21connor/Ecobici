<template>
    <div class="bikes-container">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Bicicletas</h2>
            <div class="flex space-x-3">
                <button
                    @click="refreshBikes"
                    :disabled="loading"
                    class="btn btn-secondary"
                >
                    <svg class="h-4 w-4 mr-2" :class="{'animate-spin': loading}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Actualizar
                </button>
                <button
                    v-if="user && user.role === 'admin'"
                    @click="showCreateModal = true"
                    class="btn btn-primary"
                >
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva Bicicleta
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <span class="text-blue-600 text-sm font-bold">🚲</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Total</p>
                        <p class="text-lg font-semibold text-gray-900">{{ stats.total_bikes || 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                            <span class="text-green-600 text-sm font-bold">✓</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Disponibles</p>
                        <p class="text-lg font-semibold text-gray-900">{{ stats.by_status?.disponible || 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <span class="text-yellow-600 text-sm font-bold">⚡</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Eléctricas</p>
                        <p class="text-lg font-semibold text-gray-900">{{ stats.by_type?.electrica || 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                            <span class="text-red-600 text-sm font-bold">🔧</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">En Reparación</p>
                        <p class="text-lg font-semibold text-gray-900">{{ stats.by_status?.en_reparacion || 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Código o descripción..."
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
                        <option value="tradicional">Tradicional</option>
                        <option value="electrica">Eléctrica</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select
                        v-model="filters.status"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">Todos los estados</option>
                        <option value="disponible">Disponible</option>
                        <option value="en_uso">En Uso</option>
                        <option value="en_reparacion">En Reparación</option>
                        <option value="mantenimiento">Mantenimiento</option>
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

        <!-- Bikes List -->
        <div v-if="loading && bikes.length === 0" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-500">Cargando bicicletas...</p>
        </div>

        <div v-else-if="filteredBikes.length === 0" class="text-center py-12">
            <svg class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0h2M7 7h10M7 11h4m6 0h4M7 15h10" />
            </svg>
            <p class="text-gray-500 text-lg mb-2">No hay bicicletas disponibles</p>
            <p class="text-gray-400">Ajusta los filtros o agrega una nueva bicicleta</p>
        </div>

        <div v-else class="bg-white rounded-lg shadow overflow-hidden">
            <!-- Table Header -->
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                <div class="grid grid-cols-12 gap-4 text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <div class="col-span-2">Código</div>
                    <div class="col-span-2">Tipo</div>
                    <div class="col-span-2">Estado</div>
                    <div class="col-span-2">Estación</div>
                    <div class="col-span-2">Batería</div>
                    <div class="col-span-2">Acciones</div>
                </div>
            </div>

            <!-- Table Body -->
            <div class="divide-y divide-gray-200">
                <div
                    v-for="bike in paginatedBikes"
                    :key="bike.id"
                    class="px-6 py-4 hover:bg-gray-50 transition-colors"
                >
                    <div class="grid grid-cols-12 gap-4 items-center">
                        <!-- Código -->
                        <div class="col-span-2">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8">
                                    <div class="h-8 w-8 rounded-full flex items-center justify-center"
                                         :class="bike.type === 'electrica' ? 'bg-yellow-100 text-yellow-600' : 'bg-blue-100 text-blue-600'">
                                        <span class="text-xs font-bold">{{ bike.type === 'electrica' ? '⚡' : '🚲' }}</span>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">{{ bike.code }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Tipo -->
                        <div class="col-span-2">
                            <span class="px-2 py-1 text-xs font-medium rounded-full"
                                  :class="bike.type === 'electrica' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'">
                                {{ bike.type === 'electrica' ? 'Eléctrica' : 'Tradicional' }}
                            </span>
                        </div>

                        <!-- Estado -->
                        <div class="col-span-2">
                            <span class="px-2 py-1 text-xs font-medium rounded-full"
                                  :class="getStatusClass(bike.status)">
                                {{ getStatusText(bike.status) }}
                            </span>
                        </div>

                        <!-- Estación -->
                        <div class="text-sm text-gray-900">
                            {{ bike.estacion ? bike.estacion.nombre : 'Sin asignar' }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ bike.estacion ? bike.estacion.codigo || bike.estacion.id : '' }}
                        </div>

                        <!-- Batería -->
                        <div class="col-span-2">
                            <div v-if="bike.type === 'electrica'" class="flex items-center">
                                <div class="w-full bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="h-2 rounded-full transition-all"
                                         :class="getBatteryColor(bike.battery_level)"
                                         :style="{ width: bike.battery_level + '%' }">
                                    </div>
                                </div>
                                <span class="text-xs text-gray-600 min-w-8">{{ bike.battery_level }}%</span>
                            </div>
                            <span v-else class="text-xs text-gray-400">N/A</span>
                        </div>

                        <!-- Acciones -->
                        <div class="col-span-2">
                            <div class="flex space-x-2">
                                <button
                                    @click="viewBike(bike)"
                                    class="text-blue-600 hover:text-blue-900 text-sm"
                                >
                                    Ver
                                </button>
                                <button
                                    v-if="bike.can_be_rented && (!user || user.role !== 'admin')"
                                    @click="rentBike(bike)"
                                    class="text-green-600 hover:text-green-900 text-sm"
                                >
                                    Alquilar
                                </button>
                                <button
                                    v-if="user && user.role === 'admin'"
                                    @click="editBike(bike)"
                                    class="text-indigo-600 hover:text-indigo-900 text-sm"
                                >
                                    Editar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="px-6 py-3 bg-gray-50 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Mostrando {{ (currentPage - 1) * itemsPerPage + 1 }} a
                        {{ Math.min(currentPage * itemsPerPage, filteredBikes.length) }}
                        de {{ filteredBikes.length }} resultados
                    </div>
                    <div class="flex space-x-2">
                        <button
                            @click="currentPage--"
                            :disabled="currentPage === 1"
                            class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Anterior
                        </button>
                        <span class="px-3 py-1 text-sm">
                            {{ currentPage }} de {{ totalPages }}
                        </span>
                        <button
                            @click="currentPage++"
                            :disabled="currentPage === totalPages"
                            class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Siguiente
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bike Detail Modal -->
        <div v-if="selectedBike" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75" @click="closeModal"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                    <!-- Modal Header -->
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">{{ selectedBike.code }}</h3>
                            <p class="text-sm text-gray-500">{{ selectedBike.type === 'electrica' ? 'Bicicleta Eléctrica' : 'Bicicleta Tradicional' }}</p>
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

                    <!-- Bike Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Información General</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Estado:</span>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full"
                                          :class="getStatusClass(selectedBike.status)">
                                        {{ getStatusText(selectedBike.status) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Disponible para alquiler:</span>
                                    <span :class="selectedBike.can_be_rented ? 'text-green-600' : 'text-red-600'" class="font-medium">
                                        {{ selectedBike.can_be_rented ? 'Sí' : 'No' }}
                                    </span>
                                </div>
                                <div v-if="selectedBike.type === 'electrica'" class="flex justify-between">
                                    <span class="text-gray-500">Nivel de batería:</span>
                                    <div class="flex items-center space-x-2">
                                        <div class="w-16 bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full transition-all"
                                                 :class="getBatteryColor(selectedBike.battery_level)"
                                                 :style="{ width: selectedBike.battery_level + '%' }">
                                            </div>
                                        </div>
                                        <span class="font-medium">{{ selectedBike.battery_level }}%</span>
                                    </div>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Tiempo total de uso:</span>
                                    <span class="font-medium">{{ formatMinutes(selectedBike.total_usage_time) }}</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Ubicación</h4>
                            <div v-if="selectedBike.estacion" class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Estación:</span>
                                    <span class="font-medium">{{ selectedBike.estacion.nombre }}</span>                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Código:</span>
                                    <span class="font-medium">{{ selectedBike.estacion.code }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Tipo:</span>
                                    <span class="font-medium">{{ getStationType(selectedBike.estacion.type) }}</span>
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-500">
                                Sin estación asignada
                            </div>
                        </div>
                    </div>

                    <!-- Damage Reports -->
                    <div v-if="selectedBike.active_damage_reports && selectedBike.active_damage_reports.length > 0">
                        <h4 class="font-medium text-gray-900 mb-3">Reportes de Daño Activos</h4>
                        <div class="space-y-2 mb-6">
                            <div
                                v-for="report in selectedBike.active_damage_reports"
                                :key="report.id"
                                class="p-3 bg-red-50 border border-red-200 rounded-lg"
                            >
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-medium text-red-800">{{ report.description }}</p>
                                        <p class="text-xs text-red-600">Severidad: {{ report.severity }}</p>
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                        {{ report.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end space-x-3">
                        <button @click="closeModal" class="btn btn-secondary">
                            Cerrar
                        </button>
                        <button
                            v-if="selectedBike.can_be_rented && (!user || user.role !== 'admin')"
                            @click="rentBike(selectedBike)"
                            class="btn btn-primary"
                        >
                            Alquilar
                        </button>
                        <button
                            v-if="!user || user.role !== 'admin'"
                            @click="reportDamage(selectedBike)"
                            class="btn btn-outline"
                        >
                            Reportar Daño
                        </button>
                        <button
                            v-if="user && user.role === 'admin'"
                            @click="editBike(selectedBike)"
                            class="btn btn-primary"
                        >
                            Editar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BikesList',

    props: {
        user: {
            type: Object,
            default: () => null
        }
    },

    data() {
        return {
            bikes: [],
            selectedBike: null,
            stats: {},
            loading: false,
            showCreateModal: false,
            filters: {
                search: '',
                type: '',
                status: ''
            },
            currentPage: 1,
            itemsPerPage: 10
        }
    },

    computed: {
        filteredBikes() {
            return this.bikes.filter(bike => {
                const matchesSearch = !this.filters.search ||
                    bike.code.toLowerCase().includes(this.filters.search.toLowerCase()) ||
                    (bike.description && bike.description.toLowerCase().includes(this.filters.search.toLowerCase()));

                const matchesType = !this.filters.type || bike.type === this.filters.type;
                const matchesStatus = !this.filters.status || bike.status === this.filters.status;

                return matchesSearch && matchesType && matchesStatus;
            });
        },

        paginatedBikes() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.filteredBikes.slice(start, end);
        },

        totalPages() {
            return Math.ceil(this.filteredBikes.length / this.itemsPerPage);
        }
    },

    async mounted() {
        await this.loadBikes();
        await this.loadStats();
    },

    methods: {
        async loadBikes() {
            this.loading = true;
            try {
                const response = await axios.get('/bikes', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.data.success) {
                    this.bikes = response.data.data;
                }
            } catch (error) {
                console.error('Error loading bikes:', error);
                alert('Error al cargar las bicicletas');
            } finally {
                this.loading = false;
            }
        },

        async loadStats() {
            try {
                const response = await axios.get('/bikes/statistics', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.data.success) {
                    this.stats = response.data.data;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },

        async refreshBikes() {
            await this.loadBikes();
            await this.loadStats();
        },

        viewBike(bike) {
            this.selectedBike = bike;
        },

        closeModal() {
            this.selectedBike = null;
        },

        async rentBike(bike) {
            if (!bike.estacion) {
                alert('Esta bicicleta no tiene una estación asignada');
                return;
            }

            try {
                const response = await axios.post(`/api/bikes/${bike.id}/rent`, {
                    estacion_id: bike.estacion.id
                }, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.data.success) {
                    this.closeModal();
                    await this.refreshBikes();
                    alert('Bicicleta alquilada exitosamente');
                }
            } catch (error) {
                console.error('Error renting bike:', error);
                alert(error.response?.data?.message || 'Error al alquilar la bicicleta');
            }
        },

        reportDamage(bike) {
            alert('Funcionalidad de reportar daño será implementada próximamente');
            this.closeModal();
        },

        editBike(bike) {
            alert('Funcionalidad de editar bicicleta será implementada próximamente');
            this.closeModal();
        },

        clearFilters() {
            this.filters.search = '';
            this.filters.type = '';
            this.filters.status = '';
            this.currentPage = 1;
        },

        // Utility methods
        getStatusClass(status) {
            const classes = {
                'disponible': 'bg-green-100 text-green-800',
                'en_uso': 'bg-blue-100 text-blue-800',
                'en_reparacion': 'bg-red-100 text-red-800',
                'mantenimiento': 'bg-yellow-100 text-yellow-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },

        getStatusText(status) {
            const texts = {
                'disponible': 'Disponible',
                'en_uso': 'En Uso',
                'en_reparacion': 'En Reparación',
                'mantenimiento': 'Mantenimiento'
            };
            return texts[status] || status;
        },

        getBatteryColor(level) {
            if (level >= 60) return 'bg-green-500';
            if (level >= 30) return 'bg-yellow-500';
            return 'bg-red-500';
        },

        getStationType(type) {
            const types = {
                'carga': 'Estación de Carga',
                'descanso': 'Estación de Descanso',
                'seleccion': 'Estación de Selección'
            };
            return types[type] || type;
        },

        formatMinutes(minutes) {
            if (!minutes) return '0 min';
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            if (hours > 0) {
                return `${hours}h ${mins}m`;
            }
            return `${mins}m`;
        }
    }
}
</script>

<style scoped>
.btn {
    @apply inline-flex items-center px-4 py-2 border font-medium rounded-md text-sm transition-colors;
}

.btn-primary {
    @apply border-transparent text-white bg-blue-600 hover:bg-blue-700;
}

.btn-secondary {
    @apply border-gray-300 text-gray-700 bg-white hover:bg-gray-50;
}

.btn-outline {
    @apply border-gray-300 text-gray-700 bg-white hover:bg-gray-50;
}
</style>
