<template>
    <div class="damage-reports">
        <!-- Header Section -->
        <div class="reports-header">
            <div class="header-left">
                <h2 class="section-title">
                    {{ isAdmin ? 'Gestión de Reportes de Daños' : 'Mis Reportes de Daños' }}
                </h2>
                <p class="section-subtitle">
                    {{ isAdmin ? 'Administrar todos los reportes del sistema' : 'Gestiona los reportes de daños de tus bicicletas' }}
                </p>
            </div>
            <div class="header-right">
                <button @click="showCreateForm = true" class="btn-primary">
                    <span class="btn-icon">📝</span>
                    Nuevo Reporte
                </button>
            </div>
        </div>

        <!-- Stats Cards (solo para admin) -->
        <div v-if="isAdmin && statistics" class="stats-section">
            <div class="stats-grid">
                <div class="stat-card stat-blue">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <h3>{{ statistics.total_reports }}</h3>
                        <p>Total de Reportes</p>
                    </div>
                </div>
                <div class="stat-card stat-yellow">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <h3>{{ statistics.pending_reports }}</h3>
                        <p>Pendientes</p>
                    </div>
                </div>
                <div class="stat-card stat-green">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3>{{ statistics.resolved_reports }}</h3>
                        <p>Resueltos</p>
                    </div>
                </div>
                <div class="stat-card stat-red">
                    <div class="stat-icon">🚨</div>
                    <div class="stat-content">
                        <h3>{{ statistics.by_severity.grave }}</h3>
                        <p>Críticos</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <div class="filters-grid">
                <div class="filter-group">
                    <label>Estado:</label>
                    <select v-model="filters.status" @change="loadReports">
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="en_revision">En Revisión</option>
                        <option value="en_reparacion">En Reparación</option>
                        <option value="resuelto">Resuelto</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Severidad:</label>
                    <select v-model="filters.severity" @change="loadReports">
                        <option value="">Todas las severidades</option>
                        <option value="leve">Leve</option>
                        <option value="moderado">Moderado</option>
                        <option value="grave">Grave</option>
                    </select>
                </div>
                <div class="filter-group" v-if="isAdmin && availableBikes.length">
                    <label>Bicicleta:</label>
                    <select v-model="filters.bike_id" @change="loadReports">
                        <option value="">Todas las bicicletas</option>
                        <option v-for="bike in availableBikes" :key="bike.id" :value="bike.id">
                            {{ bike.brand }} {{ bike.model }} - {{ bike.user ? bike.user.name : 'Sin usuario' }}
                        </option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button @click="clearFilters" class="btn-secondary">
                        Limpiar Filtros
                    </button>
                    <button @click="loadReports" class="btn-refresh">
                        🔄
                    </button>
                </div>
            </div>
        </div>

        <!-- Reports List -->
        <div class="reports-section">
            <div v-if="loading" class="loading-state">
                <div class="loading-spinner"></div>
                <p>Cargando reportes...</p>
            </div>

            <div v-else-if="reports.length === 0" class="empty-state">
                <div class="empty-icon">📝</div>
                <h3>No hay reportes</h3>
                <p>{{ filters.status || filters.severity ? 'No se encontraron reportes con los filtros aplicados' : 'Aún no hay reportes de daños registrados' }}</p>
                <button @click="showCreateForm = true" class="btn-primary">
                    Crear Primer Reporte
                </button>
            </div>

            <div v-else class="reports-grid">
                <div v-for="report in reports" :key="report.id" class="report-card">
                    <div class="report-header">
                        <div class="report-meta">
                            <span class="report-id">#{{ report.id }}</span>
                            <span class="report-date">{{ formatDate(report.created_at) }}</span>
                        </div>
                        <div class="report-badges">
                            <span :class="'severity-badge severity-' + report.severity">
                                {{ getSeverityText(report.severity) }}
                            </span>
                            <span :class="'status-badge status-' + report.status">
                                {{ getStatusText(report.status) }}
                            </span>
                        </div>
                    </div>

                    <div class="report-content">
                        <h4 class="report-bike">
                            🚲 {{ report.bike.brand }} {{ report.bike.model }}
                            <span v-if="isAdmin" class="bike-owner">
                                ({{ report.user.name }})
                            </span>
                        </h4>
                        <p class="report-description">{{ report.description }}</p>

                        <div v-if="report.photos && report.photos.length" class="report-photos">
                            <div class="photos-grid">
                                <img
                                    v-for="(photo, index) in report.photos.slice(0, 3)"
                                    :key="index"
                                    :src="getPhotoUrl(photo)"
                                    :alt="'Foto ' + (index + 1)"
                                    class="photo-thumbnail"
                                    @click="showPhotoModal(report.photos, index)"
                                >
                                <div v-if="report.photos.length > 3" class="photo-count">
                                    +{{ report.photos.length - 3 }} más
                                </div>
                            </div>
                        </div>

                        <div v-if="report.resolution_notes" class="resolution-notes">
                            <strong>Notas de resolución:</strong>
                            <p>{{ report.resolution_notes }}</p>
                            <p class="resolved-by">
                                Resuelto por: {{ report.resolver.name }} el {{ formatDate(report.resolved_at) }}
                            </p>
                        </div>
                    </div>

                    <div class="report-actions">
                        <button @click="viewReport(report)" class="btn-view">
                            Ver Detalles
                        </button>
                        <button
                            v-if="isAdmin && report.status !== 'resuelto'"
                            @click="updateReportStatus(report)"
                            class="btn-edit"
                        >
                            Actualizar Estado
                        </button>
                        <button
                            v-if="canDelete(report)"
                            @click="deleteReport(report)"
                            class="btn-delete"
                        >
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="pagination">
            <button
                @click="changePage(pagination.current_page - 1)"
                :disabled="pagination.current_page <= 1"
                class="page-btn"
            >
                ‹ Anterior
            </button>
            <span class="page-info">
                Página {{ pagination.current_page }} de {{ pagination.last_page }}
            </span>
            <button
                @click="changePage(pagination.current_page + 1)"
                :disabled="pagination.current_page >= pagination.last_page"
                class="page-btn"
            >
                Siguiente ›
            </button>
        </div>

        <!-- Create Report Modal -->
        <div v-if="showCreateForm" class="modal-overlay" @click="closeCreateForm">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>Nuevo Reporte de Daños</h3>
                    <button @click="closeCreateForm" class="modal-close">✕</button>
                </div>
                <form @submit.prevent="createReport" class="report-form">
                    <div class="form-group">
                        <label>Bicicleta *</label>
                        <select v-model="form.bike_id" required>
                            <option value="">Selecciona una bicicleta</option>
                            <option v-for="bike in availableBikes" :key="bike.id" :value="bike.id">
                                {{ bike.brand }} {{ bike.model }}
                                <span v-if="isAdmin && bike.user"> - {{ bike.user.name }}</span>
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Descripción del Daño *</label>
                        <textarea
                            v-model="form.description"
                            placeholder="Describe detalladamente el daño encontrado..."
                            rows="4"
                            maxlength="1000"
                            required
                        ></textarea>
                        <span class="char-count">{{ form.description.length }}/1000</span>
                    </div>

                    <div class="form-group">
                        <label>Severidad *</label>
                        <select v-model="form.severity" required>
                            <option value="leve">Leve - Daño menor que no afecta el uso</option>
                            <option value="moderado">Moderado - Daño que afecta parcialmente el uso</option>
                            <option value="grave">Grave - Daño que impide el uso seguro</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Fotos (Opcional)</label>
                        <input
                            ref="photoInput"
                            type="file"
                            @change="handlePhotoUpload"
                            multiple
                            accept="image/*"
                            class="photo-input"
                        >
                        <div class="photo-help">
                            Máximo 5 fotos, 2MB por foto
                        </div>
                        <div v-if="form.photos.length" class="uploaded-photos">
                            <div v-for="(photo, index) in form.photos" :key="index" class="photo-preview">
                                <img :src="photo.preview" :alt="'Preview ' + (index + 1)">
                                <button type="button" @click="removePhoto(index)" class="remove-photo">✕</button>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" @click="closeCreateForm" class="btn-secondary">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="creatingReport" class="btn-primary">
                            <span v-if="creatingReport">Creando...</span>
                            <span v-else>Crear Reporte</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Status Update Modal -->
        <div v-if="showStatusModal" class="modal-overlay" @click="closeStatusModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>Actualizar Estado del Reporte #{{ selectedReport.id }}</h3>
                    <button @click="closeStatusModal" class="modal-close">✕</button>
                </div>
                <form @submit.prevent="updateStatus" class="status-form">
                    <div class="form-group">
                        <label>Estado *</label>
                        <select v-model="statusForm.status" required>
                            <option value="pendiente">Pendiente</option>
                            <option value="en_revision">En Revisión</option>
                            <option value="en_reparacion">En Reparación</option>
                            <option value="resuelto">Resuelto</option>
                        </select>
                    </div>

                    <div v-if="statusForm.status === 'resuelto'" class="form-group">
                        <label>Notas de Resolución</label>
                        <textarea
                            v-model="statusForm.resolution_notes"
                            placeholder="Describe cómo se resolvió el problema..."
                            rows="3"
                            maxlength="500"
                        ></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" @click="closeStatusModal" class="btn-secondary">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="updatingStatus" class="btn-primary">
                            <span v-if="updatingStatus">Actualizando...</span>
                            <span v-else>Actualizar</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Photo Modal -->
        <div v-if="showPhotoModalFlag" class="modal-overlay" @click="closePhotoModal">
            <div class="photo-modal-content" @click.stop>
                <div class="photo-modal-header">
                    <button @click="closePhotoModal" class="modal-close">✕</button>
                </div>
                <div class="photo-carousel">
                    <button
                        v-if="modalPhotos.length > 1"
                        @click="previousPhoto"
                        class="photo-nav photo-prev"
                        :disabled="currentPhotoIndex === 0"
                    >
                        ‹
                    </button>
                    <img
                        :src="getPhotoUrl(modalPhotos[currentPhotoIndex])"
                        :alt="'Foto ' + (currentPhotoIndex + 1)"
                        class="modal-photo"
                    >
                    <button
                        v-if="modalPhotos.length > 1"
                        @click="nextPhoto"
                        class="photo-nav photo-next"
                        :disabled="currentPhotoIndex === modalPhotos.length - 1"
                    >
                        ›
                    </button>
                </div>
                <div v-if="modalPhotos.length > 1" class="photo-indicators">
                    <span
                        v-for="(photo, index) in modalPhotos"
                        :key="index"
                        :class="['photo-indicator', { active: index === currentPhotoIndex }]"
                        @click="currentPhotoIndex = index"
                    ></span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'DamageReports',

    data() {
        return {
            reports: [],
            availableBikes: [],
            statistics: null,
            loading: false,
            creatingReport: false,
            updatingStatus: false,
            isAdmin: false,

            // Pagination
            pagination: null,
            currentPage: 1,

            // Filters
            filters: {
                status: '',
                severity: '',
                bike_id: ''
            },

            // Modals
            showCreateForm: false,
            showStatusModal: false,
            showPhotoModalFlag: false,
            selectedReport: null,
            modalPhotos: [],
            currentPhotoIndex: 0,

            // Forms
            form: {
                bike_id: '',
                description: '',
                severity: 'leve',
                photos: []
            },

            statusForm: {
                status: '',
                resolution_notes: ''
            }
        }
    },

    async mounted() {
        await this.initialize();
    },

    methods: {
        async initialize() {
            try {
                // Verificar si el usuario es admin
                const userResponse = await axios.get('/user');
                this.isAdmin = userResponse.data.role === 'admin' ||
                    (userResponse.data.user && userResponse.data.user.role === 'admin');

                await Promise.all([
                    this.loadReports(),
                    this.loadAvailableBikes(),
                    this.isAdmin ? this.loadStatistics() : Promise.resolve()
                ]);

            } catch (error) {
                console.error('Error inicializando:', error);
                this.showError('Error al cargar los datos');
            }
        },

        async loadReports(page = 1) {
            this.loading = true;
            try {
                const params = {
                    page,
                    ...this.filters
                };

                const response = await axios.get('/damage-reports', { params });

                if (response.data.success) {
                    this.reports = response.data.reports.data;
                    this.pagination = {
                        current_page: response.data.reports.current_page,
                        last_page: response.data.reports.last_page,
                        total: response.data.reports.total
                    };
                    this.currentPage = page;
                }
            } catch (error) {
                console.error('Error cargando reportes:', error);
                this.showError('Error al cargar los reportes');
            } finally {
                this.loading = false;
            }
        },

        async loadAvailableBikes() {
            try {
                const response = await axios.get('/damage-reports/bikes');
                if (response.data.success) {
                    this.availableBikes = response.data.bikes;
                }
            } catch (error) {
                console.error('Error cargando bicicletas:', error);
            }
        },

        async loadStatistics() {
            if (!this.isAdmin) return;

            try {
                const response = await axios.get('/damage-reports/statistics');
                if (response.data.success) {
                    this.statistics = response.data.statistics;
                }
            } catch (error) {
                console.error('Error cargando estadísticas:', error);
            }
        },

        async createReport() {
            if (!this.form.bike_id || !this.form.description.trim()) {
                this.showError('Por favor completa todos los campos requeridos');
                return;
            }

            this.creatingReport = true;
            try {
                const formData = new FormData();
                formData.append('bike_id', this.form.bike_id);
                formData.append('description', this.form.description);
                formData.append('severity', this.form.severity);

                // Agregar fotos
                this.form.photos.forEach((photo, index) => {
                    formData.append(`photos[${index}]`, photo.file);
                });

                const response = await axios.post('/damage-reports', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                });

                if (response.data.success) {
                    this.showSuccess('Reporte creado exitosamente');
                    this.closeCreateForm();
                    await this.loadReports();
                    if (this.isAdmin) await this.loadStatistics();
                }
            } catch (error) {
                console.error('Error creando reporte:', error);
                this.showError(error.response?.data?.message || 'Error al crear el reporte');
            } finally {
                this.creatingReport = false;
            }
        },

        async updateReportStatus(report) {
            this.selectedReport = report;
            this.statusForm.status = report.status;
            this.statusForm.resolution_notes = report.resolution_notes || '';
            this.showStatusModal = true;
        },

        async updateStatus() {
            this.updatingStatus = true;
            try {
                const response = await axios.put(
                    `/damage-reports/${this.selectedReport.id}/status`,
                    this.statusForm
                );

                if (response.data.success) {
                    this.showSuccess('Estado actualizado exitosamente');
                    this.closeStatusModal();
                    await this.loadReports();
                    if (this.isAdmin) await this.loadStatistics();
                }
            } catch (error) {
                console.error('Error actualizando estado:', error);
                this.showError(error.response?.data?.message || 'Error al actualizar el estado');
            } finally {
                this.updatingStatus = false;
            }
        },

        async deleteReport(report) {
            if (!confirm(`¿Estás seguro de que quieres eliminar el reporte #${report.id}?`)) {
                return;
            }

            try {
                const response = await axios.delete(`/damage-reports/${report.id}`);

                if (response.data.success) {
                    this.showSuccess('Reporte eliminado exitosamente');
                    await this.loadReports();
                    if (this.isAdmin) await this.loadStatistics();
                }
            } catch (error) {
                console.error('Error eliminando reporte:', error);
                this.showError(error.response?.data?.message || 'Error al eliminar el reporte');
            }
        },

        handlePhotoUpload(event) {
            const files = Array.from(event.target.files);

            if (this.form.photos.length + files.length > 5) {
                this.showError('Máximo 5 fotos permitidas');
                return;
            }

            files.forEach(file => {
                if (file.size > 2 * 1024 * 1024) {
                    this.showError(`${file.name} es muy grande. Máximo 2MB por foto.`);
                    return;
                }

                if (!file.type.startsWith('image/')) {
                    this.showError(`${file.name} no es una imagen válida.`);
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    this.form.photos.push({
                        file: file,
                        preview: e.target.result
                    });
                };
                reader.readAsDataURL(file);
            });

            // Limpiar input
            this.$refs.photoInput.value = '';
        },

        removePhoto(index) {
            this.form.photos.splice(index, 1);
        },

        showPhotoModal(photos, startIndex = 0) {
            this.modalPhotos = photos;
            this.currentPhotoIndex = startIndex;
            this.showPhotoModalFlag = true;
        },

        closePhotoModal() {
            this.showPhotoModalFlag = false;
            this.modalPhotos = [];
            this.currentPhotoIndex = 0;
        },

        previousPhoto() {
            if (this.currentPhotoIndex > 0) {
                this.currentPhotoIndex--;
            }
        },

        nextPhoto() {
            if (this.currentPhotoIndex < this.modalPhotos.length - 1) {
                this.currentPhotoIndex++;
            }
        },

        closeCreateForm() {
            this.showCreateForm = false;
            this.form = {
                bike_id: '',
                description: '',
                severity: 'leve',
                photos: []
            };
        },

        closeStatusModal() {
            this.showStatusModal = false;
            this.selectedReport = null;
            this.statusForm = {
                status: '',
                resolution_notes: ''
            };
        },

        clearFilters() {
            this.filters = {
                status: '',
                severity: '',
                bike_id: ''
            };
            this.loadReports();
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.loadReports(page);
            }
        },

        viewReport(report) {
            // Implementar vista detallada si es necesario
            console.log('Ver reporte:', report);
        },

        canDelete(report) {
            return this.isAdmin || (report.user_id === this.getCurrentUserId() && report.status === 'pendiente');
        },

        getCurrentUserId() {
            // Implementar método para obtener ID del usuario actual
            return window.Laravel?.user?.id || null;
        },

        getPhotoUrl(photoPath) {
            return `/storage/${photoPath}`;
        },

        getSeverityText(severity) {
            const severities = {
                'leve': 'Leve',
                'moderado': 'Moderado',
                'grave': 'Grave'
            };
            return severities[severity] || severity;
        },

        getStatusText(status) {
            const statuses = {
                'pendiente': 'Pendiente',
                'en_revision': 'En Revisión',
                'en_reparacion': 'En Reparación',
                'resuelto': 'Resuelto'
            };
            return statuses[status] || status;
        },

        formatDate(dateString) {
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('es-GT', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch {
                return dateString;
            }
        },

        showSuccess(message) {
            // Implementar sistema de notificaciones
            console.log('Success:', message);
            alert(message); // Temporal
        },

        showError(message) {
            // Implementar sistema de notificaciones
            console.error('Error:', message);
            alert(message); // Temporal
        }
    }
}
</script>

<style scoped>
/* Estilos principales */
.damage-reports {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

/* Header */
.reports-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    gap: 20px;
}

.header-left h2 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 8px 0;
}

.section-subtitle {
    color: #6b7280;
    margin: 0;
    font-size: 1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.btn-primary:disabled {
    opacity: 0.6;
    transform: none;
    box-shadow: none;
    cursor: not-allowed;
}

/* Stats Section */
.stats-section {
    margin-bottom: 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-blue { border-left: 4px solid #3b82f6; }
.stat-yellow { border-left: 4px solid #f59e0b; }
.stat-green { border-left: 4px solid #10b981; }
.stat-red { border-left: 4px solid #ef4444; }

.stat-icon {
    font-size: 2rem;
}

.stat-content h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.stat-content p {
    color: #6b7280;
    margin: 0;
    font-size: 0.9rem;
}

/* Filters */
.filters-section {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.filter-group label {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}

.filter-group select {
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
    background: white;
    transition: border-color 0.3s ease;
}

.filter-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-actions {
    display: flex;
    gap: 10px;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
    padding: 10px 16px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

.btn-refresh {
    background: #10b981;
    color: white;
    border: none;
    padding: 10px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-refresh:hover {
    background: #059669;
}

/* Loading and Empty States */
.loading-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid #f3f4f6;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.7;
}

.empty-state h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 15px 0;
}

.empty-state p {
    color: #6b7280;
    margin: 0 0 25px 0;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

/* Reports Grid */
.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.report-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.report-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.report-header {
    background: #f9fafb;
    padding: 15px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.report-meta {
    display: flex;
    align-items: center;
    gap: 12px;
}

.report-id {
    font-weight: 700;
    color: #1f2937;
}

.report-date {
    color: #6b7280;
    font-size: 0.9rem;
}

.report-badges {
    display: flex;
    gap: 8px;
}

.severity-badge, .status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.severity-leve { background: #d1fae5; color: #065f46; }
.severity-moderado { background: #fef3c7; color: #92400e; }
.severity-grave { background: #fee2e2; color: #991b1b; }

.status-pendiente { background: #fef3c7; color: #92400e; }
.status-en_revision { background: #dbeafe; color: #1e40af; }
.status-en_reparacion { background: #fed7aa; color: #9a3412; }
.status-resuelto { background: #d1fae5; color: #065f46; }

.report-content {
    padding: 20px;
}

.report-bike {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 10px 0;
}

.bike-owner {
    font-weight: 400;
    color: #6b7280;
    font-size: 0.9rem;
}

.report-description {
    color: #4b5563;
    margin: 0 0 15px 0;
    line-height: 1.5;
}

.report-photos {
    margin: 15px 0;
}

.photos-grid {
    display: flex;
    gap: 8px;
    align-items: center;
}

.photo-thumbnail {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.photo-thumbnail:hover {
    transform: scale(1.05);
}

.photo-count {
    background: #f3f4f6;
    color: #6b7280;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.resolution-notes {
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 6px;
    padding: 12px;
    margin-top: 15px;
}

.resolution-notes strong {
    color: #166534;
}

.resolution-notes p {
    margin: 8px 0 0 0;
    color: #166534;
}

.resolved-by {
    font-size: 0.8rem;
    color: #059669 !important;
    font-style: italic;
}

.report-actions {
    padding: 15px 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 10px;
    background: #f9fafb;
}

.btn-view, .btn-edit, .btn-delete {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
}

.btn-view {
    background: #e5e7eb;
    color: #374151;
}

.btn-view:hover {
    background: #d1d5db;
}

.btn-edit {
    background: #dbeafe;
    color: #1e40af;
}

.btn-edit:hover {
    background: #bfdbfe;
}

.btn-delete {
    background: #fee2e2;
    color: #991b1b;
}

.btn-delete:hover {
    background: #fecaca;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-top: 30px;
    padding: 20px;
}

.page-btn {
    background: #667eea;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
}

.page-btn:hover:not(:disabled) {
    background: #5b6fe8;
}

.page-btn:disabled {
    background: #d1d5db;
    color: #6b7280;
    cursor: not-allowed;
}

.page-info {
    font-weight: 500;
    color: #374151;
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
    padding: 20px;
}

.modal-content {
    background: white;
    border-radius: 12px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.modal-header {
    padding: 20px 20px 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #6b7280;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

/* Form Styles */
.report-form, .status-form {
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.char-count {
    display: block;
    text-align: right;
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 4px;
}

.photo-input {
    margin-bottom: 8px;
}

.photo-help {
    font-size: 0.8rem;
    color: #6b7280;
    margin-bottom: 12px;
}

.uploaded-photos {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.photo-preview {
    position: relative;
    width: 80px;
    height: 80px;
}

.photo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 6px;
}

.remove-photo {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ef4444;
    color: white;
    border: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    font-size: 0.8rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    padding-top: 15px;
    border-top: 1px solid #e5e7eb;
}

/* Photo Modal */
.photo-modal-content {
    background: white;
    border-radius: 12px;
    max-width: 90vw;
    max-height: 90vh;
    position: relative;
}

.photo-modal-header {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 10;
}

.photo-modal-header .modal-close {
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.photo-carousel {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 300px;
}

.modal-photo {
    max-width: 100%;
    max-height: 80vh;
    object-fit: contain;
    border-radius: 8px;
}

.photo-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    font-size: 1.5rem;
    cursor: pointer;
    transition: background 0.3s ease;
}

.photo-nav:hover:not(:disabled) {
    background: rgba(0, 0, 0, 0.9);
}

.photo-nav:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.photo-prev {
    left: 20px;
}

.photo-next {
    right: 20px;
}

.photo-indicators {
    display: flex;
    justify-content: center;
    gap: 8px;
    padding: 15px;
    background: #f9fafb;
    border-radius: 0 0 12px 12px;
}

.photo-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #d1d5db;
    cursor: pointer;
    transition: background 0.3s ease;
}

.photo-indicator.active {
    background: #667eea;
}

.photo-indicator:hover {
    background: #9ca3af;
}

/* Responsive Design */
@media (max-width: 768px) {
    .reports-header {
        flex-direction: column;
        align-items: stretch;
    }

    .filters-grid {
        grid-template-columns: 1fr;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .reports-grid {
        grid-template-columns: 1fr;
    }

    .report-header {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
    }

    .report-badges {
        justify-content: flex-start;
    }

    .report-actions {
        flex-direction: column;
    }

    .modal-content {
        margin: 10px;
        max-width: calc(100vw - 20px);
    }

    .photo-modal-content {
        margin: 20px;
        max-width: calc(100vw - 40px);
        max-height: calc(100vh - 40px);
    }

    .photo-nav {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
    }

    .photo-prev {
        left: 10px;
    }

    .photo-next {
        right: 10px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .stat-card {
        flex-direction: column;
        text-align: center;
    }

    .filters-grid {
        gap: 15px;
    }

    .filter-actions {
        flex-direction: column;
    }

    .form-actions {
        flex-direction: column-reverse;
    }

    .uploaded-photos {
        justify-content: center;
    }
}
</style>
