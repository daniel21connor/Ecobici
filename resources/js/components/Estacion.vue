<template>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">
                    {{ isAdmin ? 'Administrar Estaciones' : 'Catálogo de Estaciones' }}
                </h1>
                <p class="mt-2 text-gray-600">
                    {{ isAdmin ? 'Gestiona las estaciones del sistema EcoBici' : 'Explora las estaciones disponibles' }}
                </p>
            </div>

            <!-- Stats Cards (solo para admin) -->
            <div v-if="isAdmin && estadisticas" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Estaciones</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ estadisticas.total_estaciones }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Estaciones Activas</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ estadisticas.estaciones_activas }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">En Mantenimiento</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ estadisticas.estaciones_mantenimiento }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Capacidad Total</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ estadisticas.capacidad_total }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros y Acciones -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <!-- Filtros -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <!-- Búsqueda -->
                            <div class="relative">
                                <input
                                    v-model="filtros.search"
                                    type="text"
                                    placeholder="Buscar estaciones..."
                                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 w-64"
                                >
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                            </div>

                            <!-- Filtro por tipo -->
                            <select
                                v-model="filtros.tipo"
                                class="border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Todos los tipos</option>
                                <option value="carga">Estación de Carga</option>
                                <option value="descanso">Estación de Descanso</option>
                                <option value="seleccion">Estación de Selección</option>
                            </select>

                            <!-- Filtro por estado (solo admin) -->
                            <select
                                v-if="isAdmin"
                                v-model="filtros.estado"
                                class="border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="">Todos los estados</option>
                                <option value="activa">Activa</option>
                                <option value="inactiva">Inactiva</option>
                                <option value="mantenimiento">En Mantenimiento</option>
                            </select>
                        </div>

                        <!-- Botón Crear (solo admin) -->
                        <button
                            v-if="isAdmin"
                            @click="abrirModalCrear"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Nueva Estación
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex justify-center py-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <!-- Grid de Estaciones -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div
                    v-for="estacion in estaciones.data"
                    :key="estacion.id"
                    class="bg-white rounded-lg shadow hover:shadow-md transition-shadow duration-200"
                >
                    <div class="p-6">
                        <!-- Header de la tarjeta -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ estacion.nombre }}</h3>
                                <p class="text-sm text-gray-600">{{ estacion.ubicacion }}</p>
                            </div>
                            <span
                                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  getEstadoClass(estacion.estado)
                ]"
                            >
                {{ getEstadoTexto(estacion.estado) }}
              </span>
                        </div>

                        <!-- Tipo de estación -->
                        <div class="mb-4">
              <span
                  :class="[
                  'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                  getTipoClass(estacion.tipo_estacion)
                ]"
              >
                {{ getTipoTexto(estacion.tipo_estacion) }}
              </span>
                        </div>

                        <!-- Capacidad -->
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>Capacidad</span>
                                <span>{{ estacion.capacidad_disponible }}/{{ estacion.capacidad_total }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    class="bg-blue-600 h-2 rounded-full"
                                    :style="{ width: `${(estacion.capacidad_disponible / estacion.capacidad_total) * 100}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <p v-if="estacion.descripcion" class="text-sm text-gray-600 mb-4">
                            {{ estacion.descripcion }}
                        </p>

                        <!-- Dirección -->
                        <p v-if="estacion.direccion" class="text-sm text-gray-500 mb-4">
                            📍 {{ estacion.direccion }}
                        </p>

                        <!-- Acciones -->
                        <div class="flex gap-2">
                            <button
                                @click="verDetalles(estacion)"
                                class="flex-1 bg-blue-50 text-blue-700 hover:bg-blue-100 px-3 py-2 rounded-md text-sm font-medium transition-colors"
                            >
                                Ver Detalles
                            </button>

                            <template v-if="isAdmin">
                                <button
                                    @click="editarEstacion(estacion)"
                                    class="bg-yellow-50 text-yellow-700 hover:bg-yellow-100 px-3 py-2 rounded-md text-sm font-medium transition-colors"
                                >
                                    Editar
                                </button>
                                <button
                                    @click="eliminarEstacion(estacion)"
                                    class="bg-red-50 text-red-700 hover:bg-red-100 px-3 py-2 rounded-md text-sm font-medium transition-colors"
                                >
                                    Eliminar
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="estaciones.last_page > 1" class="flex justify-center">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <button
                        v-for="page in estaciones.last_page"
                        :key="page"
                        @click="cambiarPagina(page)"
                        :class="[
              'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
              page === estaciones.current_page
                ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
            ]"
                    >
                        {{ page }}
                    </button>
                </nav>
            </div>

            <!-- Modal Crear/Editar -->
            <div v-if="modalVisible" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            {{ modoEdicion ? 'Editar Estación' : 'Nueva Estación' }}
                        </h3>

                        <form @submit.prevent="guardarEstacion">
                            <!-- Nombre -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre *</label>
                                <input
                                    v-model="formulario.nombre"
                                    type="text"
                                    required
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                            </div>

                            <!-- Ubicación -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Ubicación *</label>
                                <input
                                    v-model="formulario.ubicacion"
                                    type="text"
                                    required
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                            </div>

                            <!-- Coordenadas -->
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Latitud *</label>
                                    <input
                                        v-model="formulario.latitud"
                                        type="number"
                                        step="any"
                                        required
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                    >
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Longitud *</label>
                                    <input
                                        v-model="formulario.longitud"
                                        type="number"
                                        step="any"
                                        required
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                    >
                                </div>
                            </div>

                            <!-- Tipo -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                                <select
                                    v-model="formulario.tipo_estacion"
                                    required
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="">Seleccionar tipo</option>
                                    <option value="carga">Estación de Carga</option>
                                    <option value="descanso">Estación de Descanso</option>
                                    <option value="seleccion">Estación de Selección</option>
                                </select>
                            </div>

                            <!-- Capacidad -->
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Capacidad Total *</label>
                                    <input
                                        v-model="formulario.capacidad_total"
                                        type="number"
                                        min="1"
                                        required
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                    >
                                </div>
                                <div v-if="modoEdicion">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Capacidad Disponible</label>
                                    <input
                                        v-model="formulario.capacidad_disponible"
                                        type="number"
                                        min="0"
                                        class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                    >
                                </div>
                            </div>

                            <!-- Estado -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Estado *</label>
                                <select
                                    v-model="formulario.estado"
                                    required
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                                    <option value="activa">Activa</option>
                                    <option value="inactiva">Inactiva</option>
                                    <option value="mantenimiento">En Mantenimiento</option>
                                </select>
                            </div>

                            <!-- Descripción -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Descripción</label>
                                <textarea
                                    v-model="formulario.descripcion"
                                    rows="3"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                ></textarea>
                            </div>

                            <!-- Dirección -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                                <input
                                    v-model="formulario.direccion"
                                    type="text"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                >
                            </div>

                            <!-- Botones -->
                            <div class="flex gap-3 pt-4">
                                <button
                                    type="submit"
                                    :disabled="enviando"
                                    class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {{ enviando ? 'Guardando...' : (modoEdicion ? 'Actualizar' : 'Crear') }}
                                </button>
                                <button
                                    type="button"
                                    @click="cerrarModal"
                                    class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<script>
export default {
    name: 'Estacion',
    data() {
        return {
            estaciones: {
                data: [],
                current_page: 1,
                last_page: 1
            },
            estadisticas: null,
            loading: true,
            modalVisible: false,
            modoEdicion: false,
            enviando: false,
            filtros: {
                search: '',
                tipo: '',
                estado: '',
                page: 1
            },
            formulario: {
                nombre: '',
                descripcion: '',
                ubicacion: '',
                latitud: '',
                longitud: '',
                tipo_estacion: '',
                capacidad_total: '',
                capacidad_disponible: '',
                estado: 'activa',
                direccion: '',
                telefono: '',
                observaciones: ''
            },
            estacionEditando: null,
            isAdmin: false
        }
    },
    mounted() {
        this.verificarUsuario();
        this.cargarEstaciones();
        if (this.isAdmin) {
            this.cargarEstadisticas();
        }
    },
    watch: {
        'filtros.search': {
            handler() {
                this.debounceCargar();
            }
        },
        'filtros.tipo'() {
            this.cargarEstaciones();
        },
        'filtros.estado'() {
            this.cargarEstaciones();
        }
    },
    methods: {
        async verificarUsuario() {
            try {
                const response = await fetch('/user');
                const data = await response.json();
                this.isAdmin = data.is_admin || false;
            } catch (error) {
                console.error('Error verificando usuario:', error);
            }
        },

        async cargarEstaciones() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                Object.keys(this.filtros).forEach(key => {
                    if (this.filtros[key] !== '' && this.filtros[key] !== null) {
                        params.append(key, this.filtros[key]);
                    }
                });

                const response = await fetch(`/estaciones/data?${params}`);

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Error response:', errorText);
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    this.estaciones = data.data;
                } else {
                    console.error('API error:', data);
                    this.mostrarError(data.message || 'Error al cargar las estaciones');
                }
            } catch (error) {
                console.error('Error completo:', error);
                this.mostrarError(`Error de conexión: ${error.message}`);
            } finally {
                this.loading = false;
            }
        },

        async cargarEstadisticas() {
            try {
                const response = await fetch('/estaciones/estadisticas');
                const data = await response.json();
                if (data.success) {
                    this.estadisticas = data.data;
                }
            } catch (error) {
                console.error('Error cargando estadísticas:', error);
            }
        },

        debounceCargar: (() => {
            let timeout;
            return function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.cargarEstaciones();
                }, 500);
            };
        })(),

        cambiarPagina(page) {
            this.filtros.page = page;
            this.cargarEstaciones();
        },

        abrirModalCrear() {
            this.modoEdicion = false;
            this.estacionEditando = null;
            this.resetFormulario();
            this.modalVisible = true;
        },

        editarEstacion(estacion) {
            this.modoEdicion = true;
            this.estacionEditando = estacion;
            this.formulario = { ...estacion };
            this.modalVisible = true;
        },

        async guardarEstacion() {
            this.enviando = true;
            try {
                const url = this.modoEdicion
                    ? `/estaciones/${this.estacionEditando.id}`
                    : '/estaciones';

                const method = this.modoEdicion ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(this.formulario)
                });

                const data = await response.json();

                if (data.success) {
                    this.mostrarExito(data.message);
                    this.cerrarModal();
                    this.cargarEstaciones();
                    if (this.isAdmin) {
                        this.cargarEstadisticas();
                    }
                } else {
                    this.mostrarError(data.message || 'Error al guardar');
                }
            } catch (error) {
                console.error('Error:', error);
                this.mostrarError('Error de conexión');
            } finally {
                this.enviando = false;
            }
        },

        async eliminarEstacion(estacion) {
            if (!confirm(`¿Está seguro de eliminar la estación "${estacion.nombre}"?`)) {
                return;
            }

            try {
                const response = await fetch(`/estaciones/${estacion.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.mostrarExito('Estación eliminada exitosamente');
                    this.cargarEstaciones();
                    if (this.isAdmin) {
                        this.cargarEstadisticas();
                    }
                } else {
                    this.mostrarError(data.message || 'Error al eliminar');
                }
            } catch (error) {
                console.error('Error:', error);
                this.mostrarError('Error de conexión');
            }
        },

        verDetalles(estacion) {
            // Implementar modal de detalles o navegación
            console.log('Ver detalles de:', estacion);
        },

        cerrarModal() {
            this.modalVisible = false;
            this.resetFormulario();
        },

        resetFormulario() {
            this.formulario = {
                nombre: '',
                descripcion: '',
                ubicacion: '',
                latitud: '',
                longitud: '',
                tipo_estacion: '',
                capacidad_total: '',
                capacidad_disponible: '',
                estado: 'activa',
                direccion: '',
                telefono: '',
                observaciones: ''
            };
        },

        getEstadoClass(estado) {
            const classes = {
                'activa': 'bg-green-100 text-green-800',
                'inactiva': 'bg-red-100 text-red-800',
                'mantenimiento': 'bg-yellow-100 text-yellow-800'
            };
            return classes[estado] || 'bg-gray-100 text-gray-800';
        },

        getEstadoTexto(estado) {
            const textos = {
                'activa': 'Activa',
                'inactiva': 'Inactiva',
                'mantenimiento': 'Mantenimiento'
            };
            return textos[estado] || estado;
        },

        getTipoClass(tipo) {
            const classes = {
                'carga': 'bg-blue-100 text-blue-800',
                'descanso': 'bg-purple-100 text-purple-800',
                'seleccion': 'bg-indigo-100 text-indigo-800'
            };
            return classes[tipo] || 'bg-gray-100 text-gray-800';
        },

        getTipoTexto(tipo) {
            const textos = {
                'carga': 'Carga',
                'descanso': 'Descanso',
                'seleccion': 'Selección'
            };
            return textos[tipo] || tipo;
        },

        mostrarExito(mensaje) {
            // Implementar notificación de éxito
            alert(mensaje);
        },

        mostrarError(mensaje) {
            // Implementar notificación de error
            alert(mensaje);
        }
    }
}
</script>
