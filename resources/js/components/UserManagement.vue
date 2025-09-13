<template>
    <div class="users-management">
        <div class="header-section">
            <h2 class="page-title">Gestión de Usuarios</h2>
            <button
                @click="showCreateAdminModal = true"
                class="btn btn-primary"
                :disabled="loading"
            >
                Crear Administrador
            </button>
        </div>

        <!-- Buscador -->
        <div class="search-section">
            <div class="search-container">
                <input
                    type="text"
                    v-model="searchQuery"
                    @input="searchUsers"
                    placeholder="Buscar por nombre, apellidos, email o DPI..."
                    class="search-input"
                    :disabled="loading"
                />
                <button
                    @click="clearSearch"
                    class="btn btn-secondary"
                    v-if="searchQuery"
                    :disabled="loading"
                >
                    Limpiar
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="loading-container">
            <div class="spinner"></div>
            <p>Cargando usuarios...</p>
        </div>

        <!-- Tabla de usuarios -->
        <div v-else class="users-table-container">
            <div v-if="users.length === 0" class="no-users">
                <p>No se encontraron usuarios</p>
            </div>

            <div v-else class="table-responsive">
                <table class="users-table">
                    <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nombre Completo</th>
                        <th>Email</th>
                        <th>DPI</th>
                        <th>Teléfono</th>
                        <th>F. Nacimiento</th>
                        <th>Rol</th>
                        <th>F. Registro</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="user in users" :key="user.id" class="user-row">
                        <td class="photo-cell">
                            <div class="user-photo">
                                <img
                                    v-if="user.foto_url"
                                    :src="user.foto_url"
                                    :alt="`Foto de ${user.name}`"
                                    class="photo-img"
                                />
                                <div v-else class="photo-placeholder">
                                    {{ getInitials(user.name, user.apellidos) }}
                                </div>
                            </div>
                        </td>
                        <td class="name-cell">
                            <div class="user-name">
                                <strong>{{ user.name }} {{ user.apellidos }}</strong>
                            </div>
                        </td>
                        <td class="email-cell">{{ user.email }}</td>
                        <td class="dpi-cell">{{ formatDPI(user.dpi) }}</td>
                        <td class="phone-cell">{{ user.telefono || 'N/A' }}</td>
                        <td class="birth-cell">{{ formatDate(user.fecha_nacimiento) }}</td>
                        <td class="role-cell">
                                <span :class="['role-badge', `role-${user.role}`]">
                                    {{ user.role === 'admin' ? 'Administrador' : 'Usuario' }}
                                </span>
                        </td>
                        <td class="date-cell">{{ formatDateTime(user.created_at) }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal para crear administrador -->
        <div v-if="showCreateAdminModal" class="modal-overlay" @click="closeModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <h3>Crear Nuevo Administrador</h3>
                    <button @click="closeModal" class="close-btn">&times;</button>
                </div>

                <form @submit.prevent="createAdmin" class="admin-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="admin-name">Nombre *</label>
                            <input
                                type="text"
                                id="admin-name"
                                v-model="adminData.name"
                                class="form-control"
                                placeholder="Nombre del administrador"
                                :disabled="creatingAdmin"
                                required
                            />
                        </div>
                        <div class="form-group">
                            <label for="admin-apellidos">Apellidos *</label>
                            <input
                                type="text"
                                id="admin-apellidos"
                                v-model="adminData.apellidos"
                                class="form-control"
                                placeholder="Apellidos del administrador"
                                :disabled="creatingAdmin"
                                required
                            />
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="admin-email">Correo *</label>
                        <input
                            type="email"
                            id="admin-email"
                            v-model="adminData.email"
                            class="form-control"
                            placeholder="correo@ejemplo.com"
                            :disabled="creatingAdmin"
                            required
                        />
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="admin-dpi">DPI *</label>
                            <input
                                type="text"
                                id="admin-dpi"
                                v-model="adminData.dpi"
                                class="form-control"
                                placeholder="1234567890123"
                                maxlength="13"
                                pattern="[0-9]{13}"
                                :disabled="creatingAdmin"
                                required
                            />
                        </div>
                        <div class="form-group">
                            <label for="admin-fecha-nacimiento">Fecha de Nacimiento *</label>
                            <input
                                type="date"
                                id="admin-fecha-nacimiento"
                                v-model="adminData.fecha_nacimiento"
                                class="form-control"
                                :disabled="creatingAdmin"
                                required
                            />
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="admin-telefono">Teléfono</label>
                        <input
                            type="tel"
                            id="admin-telefono"
                            v-model="adminData.telefono"
                            class="form-control"
                            placeholder="+502 1234-5678"
                            :disabled="creatingAdmin"
                        />
                    </div>

                    <div class="form-group">
                        <label for="admin-password">Contraseña *</label>
                        <input
                            type="password"
                            id="admin-password"
                            v-model="adminData.password"
                            class="form-control"
                            placeholder="Mínimo 6 caracteres"
                            :disabled="creatingAdmin"
                            required
                        />
                    </div>

                    <div class="form-group">
                        <label for="admin-foto">Foto de Perfil</label>
                        <input
                            type="file"
                            id="admin-foto"
                            @change="handleAdminFileUpload"
                            class="form-control file-input"
                            accept="image/jpeg,image/png,image/jpg,image/gif"
                            :disabled="creatingAdmin"
                        />
                        <small class="form-text">Opcional. Máximo 2MB. Formatos: JPG, PNG, GIF</small>
                    </div>

                    <div class="modal-footer">
                        <button
                            type="button"
                            @click="closeModal"
                            class="btn btn-secondary"
                            :disabled="creatingAdmin"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="btn btn-primary"
                            :disabled="creatingAdmin"
                        >
                            <span v-if="creatingAdmin">Creando...</span>
                            <span v-else>Crear Administrador</span>
                        </button>
                    </div>
                </form>

                <p v-if="modalError" class="text-danger">{{ modalError }}</p>
                <p v-if="modalSuccess" class="text-success">{{ modalSuccess }}</p>
            </div>
        </div>

        <!-- Mensajes -->
        <div v-if="error" class="alert alert-danger">{{ error }}</div>
        <div v-if="successMessage" class="alert alert-success">{{ successMessage }}</div>
    </div>
</template>

<script>
export default {
    name: 'UsersManagement',
    data() {
        return {
            users: [],
            searchQuery: '',
            loading: false,
            error: null,
            successMessage: null,

            // Modal para crear admin
            showCreateAdminModal: false,
            creatingAdmin: false,
            modalError: null,
            modalSuccess: null,
            adminData: {
                name: '',
                apellidos: '',
                email: '',
                dpi: '',
                fecha_nacimiento: '',
                telefono: '',
                password: '',
                foto: null
            }
        };
    },
    mounted() {
        this.loadUsers();
    },
    methods: {
        // Cargar usuarios
        async loadUsers() {
            this.loading = true;
            this.error = null;

            try {
                const response = await fetch('/users-catalog', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const result = await response.json();

                if (response.ok) {
                    this.users = result.users || [];
                } else {
                    this.error = result.error || 'Error al cargar los usuarios';
                }
            } catch (err) {
                console.error('Error loading users:', err);
                this.error = 'Error de conexión al cargar los usuarios';
            } finally {
                this.loading = false;
            }
        },

        // Buscar usuarios
        async searchUsers() {
            this.loading = true;
            this.error = null;

            try {
                const url = this.searchQuery
                    ? `/users-catalog?search=${encodeURIComponent(this.searchQuery)}`
                    : '/users-catalog';

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const result = await response.json();

                if (response.ok) {
                    this.users = result.users || [];
                } else {
                    this.error = result.error || 'Error en la búsqueda';
                }
            } catch (err) {
                console.error('Error searching users:', err);
                this.error = 'Error de conexión en la búsqueda';
            } finally {
                this.loading = false;
            }
        },

        // Limpiar búsqueda
        clearSearch() {
            this.searchQuery = '';
            this.loadUsers();
        },

        // Manejar archivo del admin
        handleAdminFileUpload(event) {
            const file = event.target.files[0];
            if (file) {
                // Validar tamaño (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    this.modalError = 'La imagen no debe superar los 2MB';
                    event.target.value = '';
                    return;
                }

                // Validar tipo
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    this.modalError = 'Formato no válido. Use JPG, PNG o GIF';
                    event.target.value = '';
                    return;
                }

                this.adminData.foto = file;
                this.modalError = null;
            }
        },

        // Crear administrador
        async createAdmin() {
            this.creatingAdmin = true;
            this.modalError = null;
            this.modalSuccess = null;

            try {
                const formData = new FormData();
                formData.append('name', this.adminData.name);
                formData.append('apellidos', this.adminData.apellidos);
                formData.append('email', this.adminData.email);
                formData.append('dpi', this.adminData.dpi);
                formData.append('fecha_nacimiento', this.adminData.fecha_nacimiento);
                formData.append('telefono', this.adminData.telefono || '');
                formData.append('password', this.adminData.password);

                if (this.adminData.foto) {
                    formData.append('foto', this.adminData.foto);
                }

                const response = await fetch('/create-admin', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    this.modalSuccess = 'Administrador creado exitosamente';

                    // Limpiar formulario
                    this.adminData = {
                        name: '',
                        apellidos: '',
                        email: '',
                        dpi: '',
                        fecha_nacimiento: '',
                        telefono: '',
                        password: '',
                        foto: null
                    };

                    // Recargar lista de usuarios
                    setTimeout(() => {
                        this.closeModal();
                        this.loadUsers();
                        this.successMessage = 'Administrador creado exitosamente';
                        setTimeout(() => this.successMessage = null, 5000);
                    }, 2000);
                } else {
                    // Mostrar errores de validación
                    if (result.errors) {
                        const errorMessages = Object.values(result.errors).flat();
                        this.modalError = errorMessages.join('. ');
                    } else {
                        this.modalError = result.error || 'Error al crear el administrador';
                    }
                }
            } catch (err) {
                console.error('Error creating admin:', err);
                this.modalError = 'Error de conexión al crear el administrador';
            } finally {
                this.creatingAdmin = false;
            }
        },

        // Cerrar modal
        closeModal() {
            this.showCreateAdminModal = false;
            this.modalError = null;
            this.modalSuccess = null;

            // Resetear formulario
            this.adminData = {
                name: '',
                apellidos: '',
                email: '',
                dpi: '',
                fecha_nacimiento: '',
                telefono: '',
                password: '',
                foto: null
            };
        },

        // Utilidades
        getInitials(name, apellidos) {
            const firstName = name ? name.charAt(0).toUpperCase() : '';
            const lastName = apellidos ? apellidos.charAt(0).toUpperCase() : '';
            return firstName + lastName;
        },

        formatDPI(dpi) {
            if (!dpi) return 'N/A';
            // Formato: 1234 56789 0123
            return dpi.replace(/(\d{4})(\d{5})(\d{4})/, '$1 $2 $3');
        },

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-GT');
        },

        formatDateTime(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-GT') + ' ' + date.toLocaleTimeString('es-GT', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
}
</script>

<style scoped>
.users-management {
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

.header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    flex-wrap: wrap;
    gap: 15px;
}

.page-title {
    color: #333;
    margin: 0;
    font-size: 2rem;
    font-weight: bold;
}

.search-section {
    margin-bottom: 25px;
}

.search-container {
    display: flex;
    gap: 10px;
    max-width: 500px;
}

.search-input {
    flex: 1;
    padding: 12px;
    border: 2px solid #764ba2;
    border-radius: 8px;
    font-size: 16px;
    outline: none;
    transition: 0.3s;
}

.search-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 5px rgba(102, 126, 234, 0.5);
}

.btn {
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: linear-gradient(135deg, #764ba2, #667eea);
    transform: translateY(-2px);
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover:not(:disabled) {
    background: #5a6268;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.loading-container {
    text-align: center;
    padding: 40px;
}

.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #764ba2;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.no-users {
    text-align: center;
    padding: 40px;
    color: #666;
    font-size: 18px;
}

.table-responsive {
    overflow-x: auto;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.users-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    min-width: 800px;
}

.users-table th {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 15px 10px;
    text-align: left;
    font-weight: bold;
    border-bottom: 2px solid #5a67d8;
}

.users-table td {
    padding: 12px 10px;
    border-bottom: 1px solid #eee;
    vertical-align: middle;
}

.user-row:hover {
    background-color: #f8f9fa;
}

.photo-cell {
    width: 60px;
}

.user-photo {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto;
}

.photo-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 18px;
}

.name-cell {
    min-width: 200px;
}

.user-name strong {
    color: #333;
}

.email-cell {
    color: #666;
    min-width: 200px;
}

.dpi-cell {
    font-family: monospace;
    color: #333;
    min-width: 150px;
}

.phone-cell {
    min-width: 120px;
}

.birth-cell {
    min-width: 100px;
}

.role-cell {
    min-width: 120px;
}

.role-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.role-admin {
    background: #dc3545;
    color: white;
}

.role-user {
    background: #28a745;
    color: white;
}

.date-cell {
    min-width: 140px;
    font-size: 14px;
    color: #666;
}

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
}

.modal-content {
    background: white;
    border-radius: 10px;
    padding: 0;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 10px 10px 0 0;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.5rem;
}

.close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.3s;
}

.close-btn:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

.admin-form {
    padding: 20px;
}

.form-row {
    display: flex;
    gap: 15px;
}

.form-row .form-group {
    flex: 1;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    color: #333;
    font-weight: 500;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid #764ba2;
    border-radius: 8px;
    font-size: 16px;
    outline: none;
    transition: 0.3s;
}

.form-control:focus:not(:disabled) {
    border-color: #667eea;
    box-shadow: 0 0 5px rgba(102, 126, 234, 0.5);
}

.form-control:disabled {
    background-color: #f8f9fa;
    cursor: not-allowed;
}

.file-input {
    padding: 8px !important;
}

.form-text {
    color: #666;
    font-size: 12px;
    margin-top: 5px;
    display: block;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
}

.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.alert-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.text-danger {
    color: #dc3545;
    font-size: 14px;
    margin-top: 10px;
}

.text-success {
    color: #28a745;
    font-size: 14px;
    margin-top: 10px;
}

/* Responsive */
@media (max-width: 768px) {
    .users-management {
        padding: 10px;
    }

    .header-section {
        flex-direction: column;
        align-items: stretch;
    }

    .page-title {
        font-size: 1.5rem;
        text-align: center;
    }

    .search-container {
        max-width: 100%;
    }

    .form-row {
        flex-direction: column;
        gap: 0;
    }

    .modal-content {
        margin: 10px;
        max-width: calc(100vw - 20px);
    }

    .modal-footer {
        flex-direction: column;
    }

    .modal-footer .btn {
        width: 100%;
    }
}</style>
