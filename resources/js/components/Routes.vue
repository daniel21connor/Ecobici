<template>
    <div class="routes-container">
        <!-- Header con estadísticas -->
        <div class="card mb-6">
            <div class="card-content">
                <div class="stats-header">
                    <h2 class="section-title">Mis Rutas y Estadísticas</h2>
                    <div class="stats-grid">
                        <div class="stat-item stat-blue">
                            <div class="stat-icon">🚴‍♂️</div>
                            <div class="stat-info">
                                <span class="stat-number">{{ stats.completed_routes }}</span>
                                <span class="stat-label">Rutas Completadas</span>
                            </div>
                        </div>
                        <div class="stat-item stat-green">
                            <div class="stat-icon">📏</div>
                            <div class="stat-info">
                                <span class="stat-number">{{ stats.total_distance }} km</span>
                                <span class="stat-label">Distancia Total</span>
                            </div>
                        </div>
                        <div class="stat-item stat-eco">
                            <div class="stat-icon">🌱</div>
                            <div class="stat-info">
                                <span class="stat-number">{{ stats.total_co2_saved }} kg</span>
                                <span class="stat-label">CO₂ Reducido</span>
                            </div>
                        </div>
                        <div class="stat-item stat-purple">
                            <div class="stat-icon">⭐</div>
                            <div class="stat-info">
                                <span class="stat-number">{{ stats.total_green_points }}</span>
                                <span class="stat-label">Puntos Verdes</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Insignias -->
        <div class="card mb-6">
            <div class="card-content">
                <div class="badges-section">
                    <h3 class="section-subtitle">Mis Insignias</h3>
                    <div v-if="badges.length > 0" class="badges-grid">
                        <div v-for="badge in badges" :key="badge.name" class="badge-card">
                            <div class="badge-icon">{{ badge.icon }}</div>
                            <h4 class="badge-name">{{ badge.name }}</h4>
                            <p class="badge-description">{{ badge.description }}</p>
                        </div>
                    </div>
                    <div v-else class="no-badges">
                        <p>🎯 Completa rutas para ganar insignias</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Selector de método de creación -->
        <div class="card mb-6">
            <div class="card-content">
                <div class="creation-selector">
                    <h3 class="section-subtitle">Crear Nueva Ruta</h3>
                    <div class="method-tabs">
                        <button
                            @click="creationMethod = 'map'"
                            :class="['method-tab', { active: creationMethod === 'map' }]"
                        >
                            🗺️ Con Mapa Interactivo
                        </button>
                        <button
                            @click="creationMethod = 'form'"
                            :class="['method-tab', { active: creationMethod === 'form' }]"
                        >
                            📝 Formulario Manual
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Componente de Mapa (nuevo) -->
        <div v-if="creationMethod === 'map'" class="card mb-6">
            <div class="card-content">
                <RouteMapper @route-created="handleRouteCreated" />
            </div>
        </div>

        <!-- Formulario manual (existente) -->
        <div v-if="creationMethod === 'form'" class="card mb-6">
            <div class="card-content">
                <h3 class="section-subtitle">Crear Nueva Ruta - Formulario Manual</h3>
                <form @submit.prevent="createRoute" class="route-form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">Nombre de la Ruta</label>
                            <input
                                v-model="newRoute.name"
                                type="text"
                                id="name"
                                placeholder="Ej: Casa al trabajo"
                                required
                            />
                        </div>
                        <div class="form-group">
                            <label for="distance">Distancia (km)</label>
                            <input
                                v-model="newRoute.distance"
                                type="number"
                                id="distance"
                                step="0.1"
                                min="0.1"
                                placeholder="5.5"
                                required
                            />
                        </div>
                        <div class="form-group">
                            <label for="start_point">Punto de Inicio</label>
                            <input
                                v-model="newRoute.start_point"
                                type="text"
                                id="start_point"
                                placeholder="Ej: Casa, Oficina, Universidad"
                                required
                            />
                        </div>
                        <div class="form-group">
                            <label for="end_point">Punto de Destino</label>
                            <input
                                v-model="newRoute.end_point"
                                type="text"
                                id="end_point"
                                placeholder="Ej: Trabajo, Centro comercial"
                                required
                            />
                        </div>
                    </div>

                    <!-- Preview de impacto -->
                    <div v-if="newRoute.distance" class="impact-preview">
                        <h4>Impacto Estimado:</h4>
                        <div class="impact-items">
                            <span class="impact-item">
                                🌱 {{ (newRoute.distance * 0.2).toFixed(2) }} kg de CO₂ reducido
                            </span>
                            <span class="impact-item">
                                ⭐ {{ Math.round(newRoute.distance * 10) }} puntos verdes
                            </span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary" :disabled="loading">
                            {{ loading ? 'Creando...' : 'Crear Ruta' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de rutas -->
        <div class="card">
            <div class="card-content">
                <h3 class="section-subtitle">Mis Rutas</h3>

                <div v-if="routes.length > 0" class="routes-grid">
                    <div v-for="route in routes" :key="route.id" class="route-card" :class="{ 'completed': route.completed }">
                        <div class="route-header">
                            <h4 class="route-name">{{ route.name }}</h4>
                            <div class="route-status">
                                <span v-if="route.completed" class="status-badge completed">Completada ✅</span>
                                <span v-else class="status-badge pending">Pendiente ⏳</span>
                            </div>
                        </div>

                        <div class="route-details">
                            <div class="route-info">
                                <span class="info-item">
                                    📍 {{ route.start_point }} → {{ route.end_point }}
                                </span>
                                <span class="info-item">
                                    📏 {{ route.distance }} km
                                </span>
                            </div>
                        </div>

                        <div v-if="route.completed" class="route-rewards">
                            <div class="reward-item">
                                🌱 {{ route.co2_saved }} kg CO₂ ahorrados
                            </div>
                            <div class="reward-item">
                                ⭐ {{ route.green_points }} puntos verdes
                            </div>
                        </div>

                        <div class="route-actions">
                            <button
                                v-if="!route.completed"
                                @click="completeRoute(route)"
                                class="btn-complete"
                                :disabled="loading"
                            >
                                Marcar como Completada
                            </button>
                            <button
                                @click="deleteRoute(route)"
                                class="btn-delete"
                                :disabled="loading"
                            >
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="no-routes">
                    <div class="no-routes-icon">🗺️</div>
                    <h4>No tienes rutas creadas</h4>
                    <p>Crea tu primera ruta para empezar a reducir tu huella de carbono</p>
                </div>
            </div>
        </div>

        <!-- Modal de éxito -->
        <div v-if="showSuccessModal" class="modal-overlay" @click="closeSuccessModal">
            <div class="success-modal" @click.stop>
                <div class="success-content">
                    <div class="success-icon">🎉</div>
                    <h3>¡Ruta Completada!</h3>
                    <div class="success-rewards">
                        <p>Has ganado:</p>
                        <div class="reward-list">
                            <span class="reward">🌱 {{ lastRewards.co2_saved }} kg de CO₂ ahorrados</span>
                            <span class="reward">⭐ {{ lastRewards.green_points }} puntos verdes</span>
                        </div>
                    </div>
                    <button @click="closeSuccessModal" class="btn-primary">¡Genial!</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import RouteMapper from './RouteMapper.vue'

export default {
    name: 'Routes',
    components: {
        RouteMapper
    },
    data() {
        return {
            routes: [],
            badges: [],
            stats: {
                total_routes: 0,
                completed_routes: 0,
                total_distance: 0,
                total_co2_saved: 0,
                total_green_points: 0
            },
            newRoute: {
                name: '',
                start_point: '',
                end_point: '',
                distance: null
            },
            loading: false,
            showSuccessModal: false,
            lastRewards: {
                co2_saved: 0,
                green_points: 0
            },
            creationMethod: 'map' // 'map' o 'form'
        }
    },

    async mounted() {
        await this.loadRoutes();
        await this.loadBadges();
    },

    methods: {
        async loadRoutes() {
            this.loading = true;
            try {
                const response = await axios.get('/routes', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.data.success) {
                    this.routes = response.data.routes;
                    this.stats = response.data.stats;
                }
            } catch (error) {
                console.error('Error cargando rutas:', error);
                alert('Error al cargar las rutas');
            } finally {
                this.loading = false;
            }
        },

        async loadBadges() {
            try {
                const response = await axios.get('/routes/badges', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.data.success) {
                    this.badges = response.data.badges;
                }
            } catch (error) {
                console.error('Error cargando insignias:', error);
            }
        },

        async createRoute() {
            this.loading = true;
            try {
                const response = await axios.post('/routes', this.newRoute, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.data.success) {
                    alert('Ruta creada exitosamente');
                    this.resetForm();
                    await this.loadRoutes();
                }
            } catch (error) {
                console.error('Error creando ruta:', error);
                if (error.response?.data?.errors) {
                    const errors = Object.values(error.response.data.errors).flat();
                    alert('Errores: ' + errors.join(', '));
                } else {
                    alert('Error al crear la ruta');
                }
            } finally {
                this.loading = false;
            }
        },

        async completeRoute(route) {
            this.loading = true;
            try {
                const response = await axios.patch(`/routes/${route.id}/complete`, {}, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.data.success) {
                    this.lastRewards = response.data.rewards;
                    this.showSuccessModal = true;
                    await this.loadRoutes();
                    await this.loadBadges();
                }
            } catch (error) {
                console.error('Error completando ruta:', error);
                alert('Error al completar la ruta');
            } finally {
                this.loading = false;
            }
        },

        async deleteRoute(route) {
            if (!confirm('¿Estás seguro de que quieres eliminar esta ruta?')) {
                return;
            }

            this.loading = true;
            try {
                const response = await axios.delete(`/routes/${route.id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (response.data.success) {
                    alert('Ruta eliminada exitosamente');
                    await this.loadRoutes();
                }
            } catch (error) {
                console.error('Error eliminando ruta:', error);
                alert('Error al eliminar la ruta');
            } finally {
                this.loading = false;
            }
        },

        handleRouteCreated(newRoute) {
            // Callback cuando se crea una ruta desde el mapa
            this.loadRoutes();
            this.loadBadges();
        },

        resetForm() {
            this.newRoute = {
                name: '',
                start_point: '',
                end_point: '',
                distance: null
            };
        },

        closeSuccessModal() {
            this.showSuccessModal = false;
        }
    }
}
</script>

<style scoped>
.routes-container {
    max-width: 1200px;
    margin: 0 auto;
}

.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-content {
    padding: 25px;
}

.mb-6 {
    margin-bottom: 1.5rem;
}

.section-title {
    font-size: 1.4rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 20px 0;
}

.section-subtitle {
    font-size: 1.2rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 20px 0;
}

/* Creation Method Selector */
.creation-selector {
    text-align: center;
}

.method-tabs {
    display: flex;
    gap: 10px;
    justify-content: center;
    margin-top: 15px;
}

.method-tab {
    background: #f3f4f6;
    color: #6b7280;
    border: 2px solid #e5e7eb;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.method-tab:hover {
    border-color: #d1d5db;
    color: #374151;
}

.method-tab.active {
    background: #dbeafe;
    color: #1e40af;
    border-color: #3b82f6;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    border-radius: 10px;
    background: white;
    border: 1px solid #e5e7eb;
    transition: transform 0.2s;
}

.stat-item:hover {
    transform: translateY(-2px);
}

.stat-blue { border-left: 4px solid #3b82f6; }
.stat-green { border-left: 4px solid #10b981; }
.stat-eco { border-left: 4px solid #22c55e; }
.stat-purple { border-left: 4px solid #8b5cf6; }

.stat-icon {
    font-size: 2rem;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    display: block;
}

.stat-label {
    font-size: 0.9rem;
    color: #6b7280;
}

/* Badges */
.badges-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.badge-card {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s;
}

.badge-card:hover {
    border-color: #667eea;
    transform: translateY(-2px);
}

.badge-icon {
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.badge-name {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 5px 0;
}

.badge-description {
    font-size: 0.8rem;
    color: #6b7280;
    margin: 0;
}

.no-badges {
    text-align: center;
    padding: 40px;
    color: #6b7280;
}

/* Form */
.route-form {
    background: #f8fafc;
    padding: 25px;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}

.form-group input {
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-group input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.impact-preview {
    background: #ecfdf5;
    border: 1px solid #d1fae5;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.impact-preview h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #065f46;
    margin: 0 0 10px 0;
}

.impact-items {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.impact-item {
    font-size: 0.9rem;
    color: #059669;
    font-weight: 500;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
}

.btn-primary {
    background: #667eea;
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
}

.btn-primary:hover:not(:disabled) {
    background: #5a67d8;
}

.btn-primary:disabled {
    background: #cbd5e0;
    cursor: not-allowed;
}

/* Routes Grid */
.routes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
}

.route-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 20px;
    transition: all 0.3s;
}

.route-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.route-card.completed {
    background: #f0fdf4;
    border-color: #bbf7d0;
}

.route-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.route-name {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-badge.completed {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.pending {
    background: #fef3c7;
    color: #92400e;
}

.route-details {
    margin-bottom: 15px;
}

.route-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.info-item {
    font-size: 0.9rem;
    color: #6b7280;
}

.route-rewards {
    background: #ecfdf5;
    border: 1px solid #d1fae5;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 15px;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.reward-item {
    font-size: 0.9rem;
    color: #059669;
    font-weight: 500;
}

.route-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn-complete {
    background: #10b981;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.3s;
    flex: 1;
}

.btn-complete:hover:not(:disabled) {
    background: #059669;
}

.btn-delete {
    background: #ef4444;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.3s;
}

.btn-delete:hover:not(:disabled) {
    background: #dc2626;
}

.btn-complete:disabled,
.btn-delete:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.no-routes {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.no-routes-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.7;
}

.no-routes h4 {
    font-size: 1.2rem;
    font-weight: 600;
    color: #374151;
    margin: 0 0 10px 0;
}

.no-routes p {
    margin: 0;
}

/* Success Modal */
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
}

.success-modal {
    background: white;
    border-radius: 12px;
    padding: 30px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.success-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
}

.success-icon {
    font-size: 4rem;
}

.success-content h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.success-rewards p {
    color: #6b7280;
    margin: 0 0 15px 0;
}

.reward-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.reward {
    background: #f0fdf4;
    color: #059669;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 500;
}

/* Responsive Design */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .routes-grid {
        grid-template-columns: 1fr;
    }

    .badges-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }

    .impact-items {
        flex-direction: column;
        gap: 8px;
    }

    .route-actions {
        flex-direction: column;
    }

    .btn-complete {
        flex: none;
    }

    .method-tabs {
        flex-direction: column;
        gap: 8px;
    }
}
</style>
