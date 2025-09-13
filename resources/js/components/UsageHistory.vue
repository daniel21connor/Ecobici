<template>
    <div class="usage-history-container">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Mi Historial de Uso</h2>
            <div class="flex space-x-3">
                <button
                    @click="refreshHistory"
                    :disabled="loading"
                    class="btn btn-secondary"
                >
                    <svg class="h-4 w-4 mr-2" :class="{'animate-spin': loading}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Actualizar
                </button>
                <button
                    @click="exportHistory"
                    class="btn btn-outline"
                >
                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exportar
                </button>
            </div>
        </div>

        <!-- Current Usage Card -->
        <div v-if="currentUsage" class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow p-6 mb-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold mb-2">Uso Activo</h3>
                    <div class="space-y-1">
                        <p class="text-sm opacity-90">Bicicleta: {{ currentUsage.bike.code }}</p>
                        <p class="text-sm opacity-90">Estación de inicio: {{ currentUsage.start_station.name }}</p>
                        <p class="text-sm opacity-90">Inicio: {{ formatDateTime(currentUsage.start_time) }}</p>
                        <p class="text-sm font-medium">Duración actual: {{ getCurrentDuration() }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold mb-2">{{ currentUsage.current_duration }}</div>
                    <div class="text-sm opacity-90">minutos</div>
                    <button
                        @click="returnCurrentBike"
                        class="mt-3 bg-white text-blue-600 px-4 py-2 rounded-lg font-medium hover:bg-gray-100 transition-colors"
                    >
                        Devolver Bicicleta
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <span class="text-blue-600 text-sm font-bold">#</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Total Viajes</p>
                        <p class="text-lg font-semibold text-gray-900">{{ stats.total_trips || 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                            <span class="text-green-600 text-sm font-bold">⏱</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Tiempo Total</p>
                        <p class="text-lg font-semibold text-gray-900">{{ formatMinutes(stats.total_duration) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <span class="text-yellow-600 text-sm font-bold">📏</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Distancia Total</p>
                        <p class="text-lg font-semibold text-gray-900">{{ (stats.total_distance || 0).toFixed(1) }} km</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                            <span class="text-purple-600 text-sm font-bold">⏳</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Promedio</p>
                        <p class="text-lg font-semibold text-gray-900">{{ formatMinutes(stats.average_duration) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado</label>
                    <select
                        v-model="filters.status"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">Todos los estados</option>
                        <option value="activo">Activo</option>
                        <option value="completado">Completado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Desde</label>
                    <input
                        v-model="filters.startDate"
                        type="date"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Hasta</label>
                    <input
                        v-model="filters.endDate"
                        type="date"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Período</label>
                    <select
                        v-model="statsPeriod"
                        @change="loadStats"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="week">Esta semana</option>
                        <option value="month">Este mes</option>
                        <option value="year">Este año</option>
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

        <!-- Usage History Table -->
        <div v-if="loading && usageHistory.length === 0" class="text-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-500">Cargando historial...</p>
        </div>

        <div v-else-if="filteredHistory.length === 0" class="text-center py-12">
            <svg class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="text-gray-500 text-lg mb-2">No hay historial de uso</p>
            <p class="text-gray-400">Comienza a usar bicicletas para ver tu historial aquí</p>
        </div>

        <div v-else class="bg-white rounded-lg shadow overflow-hidden">
            <!-- Table Header -->
            <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                <div class="grid grid-cols-12 gap-4 text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <div class="col-span-2">Bicicleta</div>
                    <div class="col-span-2">Inicio</div>
                    <div class="col-span-2">Fin</div>
                    <div class="col-span-2">Duración</div>
                    <div class="col-span-2">Estado</div>
                    <div class="col-span-2">Acciones</div>
                </div>
            </div>

            <!-- Table Body -->
            <div class="divide-y divide-gray-200">
                <div
                    v-for="usage in paginatedHistory"
                    :key="usage.id"
                    class="px-6 py-4 hover:bg-gray-50 transition-colors"
                >
                    <div class="grid grid-cols-12 gap-4 items-center">
                        <!-- Bicicleta -->
                        <div class="col-span-2">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8">
                                    <div class="h-8 w-8 rounded-full flex items-center justify-center"
                                         :class="usage.bike.type === 'electrica' ? 'bg-yellow-100 text-yellow-600' : 'bg-blue-100 text-blue-600'">
                                        <span class="text-xs font-bold">{{ usage.bike.type === 'electrica' ? '⚡' : '🚲' }}</span>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">{{ usage.bike.code }}</div>
                                    <div class="text-xs text-gray-500">{{ usage.bike.type }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Inicio -->
                        <div class="col-span-2">
                            <div class="text-sm text-gray-900">{{ usage.start_station.name }}</div>
                            <div class="text-xs text-gray-500">{{ formatDateTime(usage.start_time) }}</div>
                        </div>

                        <!-- Fin -->
                        <div class="col-span-2">
                            <div v-if="usage.end_station" class="text-sm text-gray-900">{{ usage.end_station.name }}</div>
                            <div v-else class="text-sm text-gray-400">En curso</div>
                            <div v-if="usage.end_time" class="text-xs text-gray-500">{{ formatDateTime(usage.end_time) }}</div>
                        </div>

                        <!-- Duración -->
                        <div class="col-span-2">
                            <div class="text-sm font-medium text-gray-900">
                                {{ usage.duration_formatted || (usage.is_active ? getCurrentDurationForUsage(usage) : 'N/A') }}
                            </div>
                            <div v-if="usage.distance_km" class="text-xs text-gray-500">{{ usage.distance_km }} km</div>
                        </div>

                        <!-- Estado -->
                        <div class="col-span-2">
                            <span class="px-2 py-1 text-xs font-medium rounded-full"
                                  :class="getStatusClass(usage.status)">
                                {{ getStatusText(usage.status) }}
                            </span>
                        </div>

                        <!-- Acciones -->
                        <div class="col-span-2">
                            <div class="flex space-x-2">
                                <button
                                    @click="viewUsage(usage)"
                                    class="text-blue-600 hover:text-blue-900 text-sm"
                                >
                                    Ver
                                </button>
                                <button
                                    v-if="usage.is_active"
                                    @click="completeUsage(usage)"
                                    class="text-green-600 hover:text-green-900 text-sm"
                                >
                                    Completar
                                </button>
                                <button
                                    v-if="usage.is_active"
                                    @click="cancelUsage(usage)"
                                    class="text-red-600 hover:text-red-900 text-sm"
                                >
                                    Cancelar
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
                        {{ Math.min(currentPage * itemsPerPage, filteredHistory.length) }}
                        de {{ filteredHistory.length }} resultados
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

        <!-- Usage Detail Modal -->
        <div v-if="selectedUsage" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75" @click="closeModal"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                    <!-- Modal Header -->
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Detalle del Viaje</h3>
                            <p class="text-sm text-gray-500">ID: {{ selectedUsage.id }}</p>
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

                    <!-- Usage Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Información del Viaje</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Bicicleta:</span>
                                    <span class="font-medium">{{ selectedUsage.bike.code }} ({{ selectedUsage.bike.type }})</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Estado:</span>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full"
                                          :class="getStatusClass(selectedUsage.status)">
                                        {{ getStatusText(selectedUsage.status) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Duración:</span>
                                    <span class="font-medium">{{ selectedUsage.duration_formatted || 'En curso' }}</span>
                                </div>
                                <div v-if="selectedUsage.distance_km" class="flex justify-between">
                                    <span class="text-gray-500">Distancia:</span>
                                    <span class="font-medium">{{ selectedUsage.distance_km }} km</span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Ubicaciones</h4>
                            <div class="space-y-4 text-sm">
                                <div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Estación de inicio:</span>
                                        <span class="font-medium">{{ selectedUsage.start_station.name }}</span>
                                    </div>
                                    <div class="text-xs text-gray-400">{{ formatDateTime(selectedUsage.start_time) }}</div>
                                </div>
                                <div v-if="selectedUsage.end_station">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">Estación de fin:</span>
                                        <span class="font-medium">{{ selectedUsage.end_station.name }}</span>
                                    </div>
                                    <div class="text-xs text-gray-400">{{ formatDateTime(selectedUsage.end_time) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Route Map Placeholder -->
                    <div v-if="selectedUsage.route" class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-3">Ruta</h4>
                        <div class="bg-gray-100 rounded-lg p-4 text-center">
                            <p class="text-gray-500 text-sm">Mapa de ruta disponible próximamente</p>
                            <p class="text-xs text-gray-400 mt-1">
                                Desde {{ selectedUsage.route.start.station }} hasta {{ selectedUsage.route.end.station }}
                            </p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="selectedUsage.notes" class="mb-6">
                        <h4 class="font-medium text-gray-900 mb-3">Notas</h4>
                        <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-lg">{{ selectedUsage.notes }}</p>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end space-x-3">
                        <button @click="closeModal" class="btn btn-secondary">
                            Cerrar
                        </button>
                        <button
                            v-if="selectedUsage.is_active"
                            @click="completeUsage(selectedUsage)"
                            class="btn btn-primary"
                        >
                            Completar Viaje
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Complete Usage Modal -->
        <div v-if="showCompleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-6">
                    <div class="text-center">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Completar Viaje</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Selecciona la estación donde devuelves la bicicleta:
                        </p>
                        <select
                            v-model="selectedEndStation"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 mb-4 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Seleccionar estación...</option>
                            <option v-for="station in availableStations" :key="station.id" :value="station.id">
                                {{ station.name }} ({{ station.code }})
                            </option>
                        </select>
                        <textarea
                            v-model="completionNotes"
                            placeholder="Notas adicionales (opcional)"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 mb-4 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            rows="3"
                        ></textarea>
                        <div class="flex space-x-3">
                            <button
                                @click="showCompleteModal = false"
                                class="flex-1 btn btn-secondary"
                            >
                                Cancelar
                            </button>
                            <button
                                @click="confirmComplete"
                                :disabled="!selectedEndStation"
                                class="flex-1 btn btn-primary"
                            >
                                Completar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'UsageHistory',

    props: {
        user: {
            type: Object,
            required: true
        }
    },

    data() {
        return {
            usageHistory: [],
            currentUsage: null,
            selectedUsage: null,
            stats: {},
            loading: false,
            showCompleteModal: false,
            selectedEndStation: '',
            completionNotes: '',
            availableStations: [],
            usageToComplete: null,
            statsPeriod: 'month',
            filters: {
                status: '',
                startDate: '',
                endDate: ''
            },
            currentPage: 1,
            itemsPerPage: 10
        }
    },

    computed: {
        filteredHistory() {
            return this.usageHistory.filter(usage => {
                const matchesStatus = !this.filters.status || usage.status === this.filters.status;

                let matchesDateRange = true;
                if (this.filters.startDate || this.filters.endDate) {
                    const usageDate = new Date(usage.start_time).toISOString().split('T')[0];
                    if (this.filters.startDate && usageDate < this.filters.startDate) {
                        matchesDateRange = false;
                    }
                    if (this.filters.endDate && usageDate > this.filters.endDate) {
                        matchesDateRange = false;
                    }
                }

                return matchesStatus && matchesDateRange;
            });
        },

        paginatedHistory() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.filteredHistory.slice(start, end);
        },

        totalPages() {
            return Math.ceil(this.filteredHistory.length / this.itemsPerPage);
        }
    },

    async mounted() {
        await this.loadHistory();
        await this.loadCurrentUsage();
        await this.loadStats();
        await this.loadStations();
    },

    methods: {
        async loadHistory() {
            this.loading = true;
            try {
                const response = await axios.get('/usage-history', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.data.success) {
                    this.usageHistory = response.data.data.data || response.data.data;
                }
            } catch (error) {
                console.error('Error loading usage history:', error);
                this.$emit('show-error', 'Error al cargar el historial de uso');
            } finally {
                this.loading = false;
            }
        },

        async loadCurrentUsage() {
            try {
                const response = await axios.get('/usage-history/current', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.data.success) {
                    this.currentUsage = response.data.data;
                }
            } catch (error) {
                console.error('Error loading current usage:', error);
            }
        },

        async loadStats() {
            try {
                const response = await axios.get('/usage-history/stats', {
                    params: { period: this.statsPeriod },
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

        async loadStations() {
            try {
                const response = await axios.get('/stations', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.data.success) {
                    this.availableStations = response.data.data;
                }
            } catch (error) {
                console.error('Error loading stations:', error);
            }
        },

        async refreshHistory() {
            await this.loadHistory();
            await this.loadCurrentUsage();
            await this.loadStats();
        },

        viewUsage(usage) {
            this.selectedUsage = usage;
        },

        closeModal() {
            this.selectedUsage = null;
        },

        completeUsage(usage) {
            this.usageToComplete = usage;
            this.showCompleteModal = true;
            this.selectedEndStation = '';
            this.completionNotes = '';
        },

        async confirmComplete() {
            if (!this.selectedEndStation) return;

            try {
                const response = await axios.post(`/usage-history/${this.usageToComplete.id}/complete`, {
                    end_station_id: this.selectedEndStation,
                    notes: this.completionNotes
                }, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.data.success) {
                    this.showCompleteModal = false;
                    this.currentUsage = null;
                    this.$emit('usage-completed', response.data.data);
                    await this.refreshHistory();
                    alert('Viaje completado exitosamente');
                }
            } catch (error) {
                console.error('Error completing usage:', error);
                this.$emit('show-error', error.response?.data?.message || 'Error al completar el viaje');
            }
        },

        async cancelUsage(usage) {
            if (!confirm('¿Estás seguro de que quieres cancelar este viaje?')) return;

            try {
                const reason = prompt('Motivo de cancelación (opcional):');

                const response = await axios.post(`/usage-history/${usage.id}/cancel`, {
                    reason: reason
                }, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.data.success) {
                    if (usage.is_active) {
                        this.currentUsage = null;
                    }
                    await this.refreshHistory();
                    alert('Viaje cancelado exitosamente');
                }
            } catch (error) {
                console.error('Error canceling usage:', error);
                this.$emit('show-error', error.response?.data?.message || 'Error al cancelar el viaje');
            }
        },

        async returnCurrentBike() {
            if (this.currentUsage) {
                this.completeUsage(this.currentUsage);
            }
        },

        async exportHistory() {
            try {
                // Crear CSV con el historial filtrado
                const csvContent = this.generateCSV(this.filteredHistory);
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', `historial_uso_${new Date().toISOString().split('T')[0]}.csv`);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (error) {
                console.error('Error exporting history:', error);
                this.$emit('show-error', 'Error al exportar el historial');
            }
        },

        generateCSV(data) {
            const headers = [
                'ID',
                'Bicicleta',
                'Tipo',
                'Estación Inicio',
                'Estación Fin',
                'Fecha Inicio',
                'Fecha Fin',
                'Duración (min)',
                'Distancia (km)',
                'Estado',
                'Notas'
            ].join(',');

            const rows = data.map(usage => [
                usage.id,
                usage.bike.code,
                usage.bike.type,
                usage.start_station.name,
                usage.end_station ? usage.end_station.name : '',
                usage.start_time,
                usage.end_time || '',
                usage.duration_minutes || '',
                usage.distance_km || '',
                usage.status,
                (usage.notes || '').replace(/,/g, ';')
            ].join(','));

            return [headers, ...rows].join('\n');
        },

        clearFilters() {
            this.filters.status = '';
            this.filters.startDate = '';
            this.filters.endDate = '';
            this.currentPage = 1;
        },

        getCurrentDuration() {
            if (!this.currentUsage || !this.currentUsage.start_time) return '0 min';

            const start = new Date(this.currentUsage.start_time);
            const now = new Date();
            const diffMs = now - start;
            const diffMins = Math.floor(diffMs / 60000);

            const hours = Math.floor(diffMins / 60);
            const minutes = diffMins % 60;

            if (hours > 0) {
                return `${hours}h ${minutes}m`;
            }
            return `${minutes}m`;
        },

        getCurrentDurationForUsage(usage) {
            if (!usage.start_time) return '0 min';

            const start = new Date(usage.start_time);
            const now = new Date();
            const diffMs = now - start;
            const diffMins = Math.floor(diffMs / 60000);

            const hours = Math.floor(diffMins / 60);
            const minutes = diffMins % 60;

            if (hours > 0) {
                return `${hours}h ${minutes}m`;
            }
            return `${minutes}m`;
        },

        // Utility methods
        getStatusClass(status) {
            const classes = {
                'activo': 'bg-blue-100 text-blue-800',
                'completado': 'bg-green-100 text-green-800',
                'cancelado': 'bg-red-100 text-red-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },

        getStatusText(status) {
            const texts = {
                'activo': 'En Uso',
                'completado': 'Completado',
                'cancelado': 'Cancelado'
            };
            return texts[status] || status;
        },

        formatDateTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('es-GT', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
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
