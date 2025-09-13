<template>
    <div class="rankings-container">
        <!-- Header con estadísticas generales -->
        <div class="card mb-6">
            <div class="card-header">
                <div class="header-content">
                    <div class="header-left">
                        <h2 class="section-title">🏆 Ranking de Usuarios</h2>
                        <p class="section-subtitle">Tabla de posiciones por kilómetros recorridos</p>
                    </div>
                    <div class="header-right">
                        <button @click="refreshRankings"
                                :disabled="loading"
                                class="refresh-btn">
                            <span v-if="loading" class="loader">⟳</span>
                            <span v-else>🔄</span>
                            Actualizar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Estadísticas Generales -->
            <div v-if="generalStats" class="stats-overview">
                <div class="stat-item">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-number">{{ generalStats.total_users }}</div>
                        <div class="stat-label">Usuarios Activos</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">🚴</div>
                    <div class="stat-content">
                        <div class="stat-number">{{ formatDistance(generalStats.total_distance) }}</div>
                        <div class="stat-label">Km Totales</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">🌱</div>
                    <div class="stat-content">
                        <div class="stat-number">{{ formatCO2(generalStats.total_co2_saved) }}</div>
                        <div class="stat-label">CO₂ Ahorrado</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <div class="stat-number">{{ formatDistance(generalStats.average_distance_per_user) }}</div>
                        <div class="stat-label">Promedio por Usuario</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tu Posición (si el usuario está logueado) -->
        <div v-if="userStats" class="card mb-6">
            <div class="user-position-card">
                <div class="position-header">
                    <div class="position-badge" :class="getPositionClass(userPosition)">
                        <span class="position-icon">{{ getPositionIcon(userPosition) }}</span>
                        <span class="position-text">
                            {{ userPosition ? `#${userPosition}` : 'Sin posición' }}
                        </span>
                    </div>
                    <div class="position-info">
                        <h3>Tu Posición en el Ranking</h3>
                        <p>{{ userPosition ? 'Sigue pedaleando para subir posiciones!' : 'Completa tu primera ruta para aparecer en el ranking' }}</p>
                    </div>
                </div>

                <div class="user-stats-grid">
                    <div class="user-stat">
                        <div class="user-stat-icon">🚴</div>
                        <div class="user-stat-content">
                            <div class="user-stat-number">{{ formatDistance(userStats.total_distance) }}</div>
                            <div class="user-stat-label">Kilómetros</div>
                        </div>
                    </div>
                    <div class="user-stat">
                        <div class="user-stat-icon">🌍</div>
                        <div class="user-stat-content">
                            <div class="user-stat-number">{{ formatCO2(userStats.total_co2_saved) }}</div>
                            <div class="user-stat-label">CO₂ Ahorrado</div>
                        </div>
                    </div>
                    <div class="user-stat">
                        <div class="user-stat-icon">✅</div>
                        <div class="user-stat-content">
                            <div class="user-stat-number">{{ userStats.completed_routes }}</div>
                            <div class="user-stat-label">Rutas Completadas</div>
                        </div>
                    </div>
                    <div class="user-stat">
                        <div class="user-stat-icon">⭐</div>
                        <div class="user-stat-content">
                            <div class="user-stat-number">{{ userStats.total_green_points }}</div>
                            <div class="user-stat-label">Puntos Verdes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros de Período -->
        <div class="card mb-6">
            <div class="filters-container">
                <div class="period-filters">
                    <button v-for="period in periods"
                            :key="period.value"
                            @click="selectedPeriod = period.value; loadRankings()"
                            :class="['period-btn', { active: selectedPeriod === period.value }]">
                        {{ period.label }}
                    </button>
                </div>
                <div class="limit-selector">
                    <label>Mostrar:</label>
                    <select v-model="selectedLimit" @change="loadRankings()" class="limit-select">
                        <option value="20">Top 20</option>
                        <option value="50">Top 50</option>
                        <option value="100">Top 100</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Tabla de Rankings -->
        <div class="card">
            <div class="rankings-table-container">
                <div v-if="loading" class="loading-state">
                    <div class="loading-spinner"></div>
                    <p>Cargando rankings...</p>
                </div>

                <div v-else-if="rankings.length === 0" class="empty-state">
                    <div class="empty-icon">🏆</div>
                    <h3>No hay datos de ranking</h3>
                    <p>Aún no hay usuarios con rutas completadas</p>
                </div>

                <div v-else class="rankings-table">
                    <!-- Header de la tabla -->
                    <div class="table-header">
                        <div class="header-cell position-col">#</div>
                        <div class="header-cell user-col">Usuario</div>
                        <div class="header-cell distance-col">Distancia</div>
                        <div class="header-cell co2-col">CO₂ Ahorrado</div>
                        <div class="header-cell routes-col">Rutas</div>
                        <div class="header-cell points-col">Puntos</div>
                    </div>

                    <!-- Filas de datos -->
                    <div class="table-body">
                        <div v-for="(ranking, index) in rankings"
                             :key="ranking.id"
                             :class="['table-row', { 'current-user': isCurrentUser(ranking.user_id), 'top-3': index < 3 }]">

                            <!-- Posición -->
                            <div class="table-cell position-col">
                                <div class="position-display" :class="getPositionClass(index + 1)">
                                    <span class="position-medal">{{ getPositionIcon(index + 1) }}</span>
                                    <span class="position-number">{{ index + 1 }}</span>
                                </div>
                            </div>

                            <!-- Usuario -->
                            <div class="table-cell user-col">
                                <div class="user-display">
                                    <div class="user-avatar">
                                        <span>{{ getUserInitials(ranking.user) }}</span>
                                    </div>
                                    <div class="user-info">
                                        <div class="user-name">{{ getUserFullName(ranking.user) }}</div>
                                        <div v-if="isCurrentUser(ranking.user_id)" class="current-user-badge">Tú</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Distancia -->
                            <div class="table-cell distance-col">
                                <div class="metric-display">
                                    <span class="metric-number">{{ formatDistance(ranking.total_distance) }}</span>
                                    <span class="metric-unit">km</span>
                                </div>
                            </div>

                            <!-- CO2 Ahorrado -->
                            <div class="table-cell co2-col">
                                <div class="metric-display co2">
                                    <span class="metric-number">{{ formatCO2(ranking.total_co2_saved) }}</span>
                                    <span class="metric-unit">kg CO₂</span>
                                </div>
                            </div>

                            <!-- Rutas Completadas -->
                            <div class="table-cell routes-col">
                                <div class="metric-display">
                                    <span class="metric-number">{{ ranking.completed_routes }}</span>
                                    <span class="metric-unit">rutas</span>
                                </div>
                            </div>

                            <!-- Puntos Verdes -->
                            <div class="table-cell points-col">
                                <div class="metric-display points">
                                    <span class="metric-number">{{ ranking.total_green_points }}</span>
                                    <span class="metric-unit">pts</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de usuarios cercanos (si el usuario actual no está en el top) -->
        <div v-if="nearbyRankings.length > 0" class="card mt-6">
            <div class="nearby-section">
                <h3 class="nearby-title">🎯 Tu Zona en el Ranking</h3>
                <div class="nearby-rankings">
                    <div v-for="ranking in nearbyRankings"
                         :key="ranking.id"
                         :class="['nearby-row', { 'current-user': isCurrentUser(ranking.user_id) }]">
                        <div class="nearby-position">{{ ranking.ranking_position }}</div>
                        <div class="nearby-user">{{ getUserFullName(ranking.user) }}</div>
                        <div class="nearby-distance">{{ formatDistance(ranking.total_distance) }} km</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'UserRankings',

    data() {
        return {
            rankings: [],
            userStats: null,
            userPosition: null,
            nearbyRankings: [],
            generalStats: null,
            loading: false,
            selectedPeriod: 'all_time',
            selectedLimit: 20,
            currentUserId: null,
            periods: [
                { value: 'all_time', label: 'Todo el Tiempo' },
                { value: 'year', label: 'Este Año' },
                { value: 'month', label: 'Este Mes' }
            ]
        }
    },

    async mounted() {
        await this.getCurrentUser();
        await this.loadUserRankings();
    },

    methods: {
        async getCurrentUser() {
            try {
                const response = await axios.get('/user');
                if (response.data.success && response.data.user) {
                    this.currentUserId = response.data.user.id;
                } else if (response.data.id) {
                    this.currentUserId = response.data.id;
                }
            } catch (error) {
                console.error('Error obteniendo usuario actual:', error);
            }
        },

        async loadUserRankings() {
            this.loading = true;
            try {
                const response = await axios.get('/rankings/user', {
                    params: {
                        limit: this.selectedLimit
                    }
                });

                if (response.data.success) {
                    const data = response.data.data;
                    this.userPosition = data.user_position;
                    this.userStats = data.user_stats;
                    this.rankings = data.top_rankings || [];
                    this.nearbyRankings = data.nearby_rankings || [];
                    this.generalStats = data.general_stats;
                } else {
                    console.error('Error en la respuesta:', response.data.message);
                }
            } catch (error) {
                console.error('Error cargando rankings:', error);
                this.$toast?.error?.('Error al cargar los rankings');
            } finally {
                this.loading = false;
            }
        },

        async loadRankings() {
            this.loading = true;
            try {
                const response = await axios.get('/rankings', {
                    params: {
                        limit: this.selectedLimit,
                        period: this.selectedPeriod
                    }
                });

                if (response.data.success) {
                    this.rankings = response.data.data.rankings || [];
                    this.generalStats = response.data.data.general_stats;
                }
            } catch (error) {
                console.error('Error cargando rankings por período:', error);
                this.$toast?.error?.('Error al cargar los rankings');
            } finally {
                this.loading = false;
            }
        },

        async refreshRankings() {
            this.loading = true;
            try {
                await axios.post('/rankings/update');
                await this.loadUserRankings();
                this.$toast?.success?.('Rankings actualizados correctamente');
            } catch (error) {
                console.error('Error actualizando rankings:', error);
                this.$toast?.error?.('Error al actualizar los rankings');
            } finally {
                this.loading = false;
            }
        },

        formatDistance(distance) {
            if (!distance) return '0';
            const num = parseFloat(distance);
            return num.toFixed(1);
        },

        formatCO2(co2) {
            if (!co2) return '0';
            const num = parseFloat(co2);
            return num.toFixed(2);
        },

        getUserFullName(user) {
            if (!user) return 'Usuario Desconocido';
            return `${user.name} ${user.apellidos || ''}`.trim();
        },

        getUserInitials(user) {
            if (!user || !user.name) return 'U';
            const names = user.name.split(' ');
            const apellidos = user.apellidos ? user.apellidos.split(' ') : [];

            const firstInitial = names[0]?.[0] || '';
            const lastInitial = apellidos[0]?.[0] || names[1]?.[0] || '';

            return (firstInitial + lastInitial).toUpperCase();
        },

        getPositionIcon(position) {
            if (position === 1) return '🥇';
            if (position === 2) return '🥈';
            if (position === 3) return '🥉';
            if (position <= 10) return '🏆';
            return '📍';
        },

        getPositionClass(position) {
            if (position === 1) return 'gold';
            if (position === 2) return 'silver';
            if (position === 3) return 'bronze';
            if (position <= 10) return 'top-10';
            return '';
        },

        isCurrentUser(userId) {
            return this.currentUserId && userId === this.currentUserId;
        }
    }
}
</script>

<style scoped>
.rankings-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

.card-header {
    padding: 25px 30px;
    border-bottom: 1px solid #e5e7eb;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-left {
    flex: 1;
}

.section-title {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 5px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-subtitle {
    color: #6b7280;
    font-size: 14px;
    margin: 0;
}

.refresh-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: #059669;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s ease;
}

.refresh-btn:hover:not(:disabled) {
    background: #047857;
    transform: translateY(-1px);
}

.refresh-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.loader {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Estadísticas Generales */
.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    padding: 25px 30px;
    background: #fafbfc;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    font-size: 32px;
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 50%;
    color: white;
    filter: drop-shadow(0 2px 4px rgba(16, 185, 129, 0.3));
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
}

.stat-label {
    font-size: 12px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 4px;
}

/* Tu Posición */
.user-position-card {
    padding: 25px 30px;
}

.position-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
}

.position-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 25px;
    font-weight: 600;
    border: 2px solid;
}

.position-badge.gold {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
    border-color: #f59e0b;
}

.position-badge.silver {
    background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
    color: white;
    border-color: #6b7280;
}

.position-badge.bronze {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    color: white;
    border-color: #b45309;
}

.position-badge.top-10 {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border-color: #059669;
}

.position-badge:not(.gold):not(.silver):not(.bronze):not(.top-10) {
    background: #f3f4f6;
    color: #6b7280;
    border-color: #d1d5db;
}

.position-icon {
    font-size: 18px;
}

.position-info h3 {
    margin: 0 0 5px 0;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.position-info p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
}

.user-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.user-stat {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.user-stat-icon {
    font-size: 24px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 8px;
    color: white;
}

.user-stat-number {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
}

.user-stat-label {
    font-size: 11px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Filtros */
.filters-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.period-filters {
    display: flex;
    gap: 8px;
    background: #f3f4f6;
    padding: 4px;
    border-radius: 8px;
}

.period-btn {
    padding: 10px 16px;
    border: none;
    background: transparent;
    color: #6b7280;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s ease;
}

.period-btn:hover {
    background: #e5e7eb;
    color: #374151;
}

.period-btn.active {
    background: #10b981;
    color: white;
    box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
}

.limit-selector {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #6b7280;
}

.limit-select {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    color: #374151;
    cursor: pointer;
}

/* Estados de carga y vacío */
.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: #6b7280;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #e5e7eb;
    border-top: 3px solid #10b981;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 15px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.empty-state h3 {
    margin: 0 0 10px 0;
    color: #374151;
    font-weight: 600;
}

.empty-state p {
    margin: 0;
    font-size: 14px;
}

/* Tabla de Rankings */
.rankings-table-container {
    overflow-x: auto;
}

.rankings-table {
    min-width: 700px;
}

.table-header {
    display: grid;
    grid-template-columns: 80px 1fr 120px 120px 100px 100px;
    gap: 15px;
    padding: 20px 30px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 600;
    color: #374151;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-body {
    max-height: 600px;
    overflow-y: auto;
}

.table-row {
    display: grid;
    grid-template-columns: 80px 1fr 120px 120px 100px 100px;
    gap: 15px;
    padding: 20px 30px;
    border-bottom: 1px solid #f3f4f6;
    transition: all 0.2s ease;
    align-items: center;
}

.table-row:hover {
    background: #fafbfc;
}

.table-row.current-user {
    background: linear-gradient(90deg, #ecfdf5 0%, #f0fdf4 100%);
    border-left: 4px solid #10b981;
}

.table-row.top-3 {
    background: linear-gradient(90deg, #fef7cd 0%, #fef3c7 100%);
}

.table-cell {
    display: flex;
    align-items: center;
}

/* Posición */
.position-display {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 20px;
    font-weight: 600;
}

.position-display.gold {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: white;
}

.position-display.silver {
    background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
    color: white;
}

.position-display.bronze {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
    color: white;
}

.position-display.top-10 {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.position-medal {
    font-size: 16px;
}

.position-number {
    font-size: 14px;
    font-weight: 700;
}

/* Usuario */
.user-display {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
}

.user-info {
    flex: 1;
}

.user-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 14px;
}

.current-user-badge {
    background: #10b981;
    color: white;
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 2px;
    display: inline-block;
}

/* Métricas */
.metric-display {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.metric-number {
    font-weight: 700;
    color: #1f2937;
    font-size: 16px;
}

.metric-unit {
    font-size: 11px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.metric-display.co2 .metric-number {
    color: #059669;
}

.metric-display.points .metric-number {
    color: #7c3aed;
}

/* Sección cercana */
.nearby-section {
    padding: 25px 30px;
}

.nearby-title {
    margin: 0 0 20px 0;
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
}

.nearby-rankings {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.nearby-row {
    display: grid;
    grid-template-columns: 60px 1fr 120px;
    gap: 15px;
    padding: 15px;
    background: #f8fafc;
    border-radius: 8px;
    align-items: center;
}

.nearby-row.current-user {
    background: linear-gradient(90deg, #ecfdf5 0%, #f0fdf4 100%);
    border: 2px solid #10b981;
}

.nearby-position {
    font-weight: 700;
    color: #6b7280;
    text-align: center;
}

.nearby-user {
    font-weight: 500;
    color: #1f2937;
}

.nearby-distance {
    font-weight: 600;
    color: #059669;
    text-align: right;
}

/* Responsive */
@media (max-width: 768px) {
    .rankings-container {
        padding: 10px;
    }

    .card-header {
        padding: 20px;
    }

    .header-content {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }

    .stats-overview {
        grid-template-columns: 1fr;
        padding: 20px;
        gap: 15px;
    }

    .stat-item {
        padding: 15px;
    }

    .user-stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .filters-container {
        padding: 15px 20px;
        flex-direction: column;
        align-items: stretch;
    }

    .period-filters {
        justify-content: center;
    }

    .table-header,
    .table-row {
        grid-template-columns: 60px 1fr 80px 80px 60px 60px;
        gap: 8px;
        padding: 15px 20px;
    }

    .metric-display {
        align-items: center;
    }

    .nearby-row {
        grid-template-columns: 50px 1fr 100px;
        gap: 10px;
        padding: 12px;
    }
}

@media (max-width: 480px) {
    .section-title {
        font-size: 20px;
    }

    .user-stats-grid {
        grid-template-columns: 1fr;
    }

    .table-header,
    .table-row {
        grid-template-columns: 50px 1fr 70px 60px;
        gap: 5px;
        padding: 10px 15px;
    }

    .routes-col,
    .points-col {
        display: none;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 24px;
    }

    .stat-number {
        font-size: 20px;
    }
}
</style>
