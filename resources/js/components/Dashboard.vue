<template>
    <div class="min-h-screen bg-gray-50 flex">
        <!-- Sidebar -->
        <div class="sidebar-container" :class="{ 'sidebar-open': sidebarOpen, 'sidebar-closed': !sidebarOpen }">
            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Logo/Header -->
                <div class="sidebar-header">
                    <div class="logo-container">
                        <span class="logo-icon">🚴‍♂️</span>
                        <span v-if="sidebarOpen" class="logo-text">Auroracicleta</span>
                    </div>

                    <!-- Toggle Button -->
                    <button
                        @click="toggleSidebar"
                        class="toggle-btn"
                        :class="{ 'toggle-rotated': !sidebarOpen }"
                    >
                        ←
                    </button>
                </div>

                <!-- Navigation Menu -->
                <nav class="sidebar-nav">
                    <ul class="nav-list">
                        <!-- Dashboard -->
                        <li>
                            <a
                                @click="setActiveSection('dashboard')"
                                class="nav-item"
                                :class="{ 'nav-active': activeSection === 'dashboard' }"
                            >
                                <span class="nav-icon">📊</span>
                                <span v-if="sidebarOpen" class="nav-text">Dashboard</span>
                            </a>
                        </li>

                        <!-- Membresías -->
                        <li>
                            <a
                                @click="setActiveSection('memberships')"
                                class="nav-item"
                                :class="{ 'nav-active': activeSection === 'memberships' }"
                            >
                                <span class="nav-icon">💳</span>
                                <span v-if="sidebarOpen" class="nav-text">Membresías</span>
                                <span v-if="sidebarOpen && !hasActiveMembership" class="nav-badge">!</span>
                            </a>
                        </li>

                        <!-- Mi Perfil -->
                        <li>
                            <a
                                @click="setActiveSection('profile')"
                                class="nav-item"
                                :class="{ 'nav-active': activeSection === 'profile' }"
                            >
                                <span class="nav-icon">👤</span>
                                <span v-if="sidebarOpen" class="nav-text">Mi Perfil</span>
                            </a>
                        </li>

                        <!-- Bicicletas (futuro módulo) -->
                        <li>
                            <a
                                @click="setActiveSection('bikes')"
                                class="nav-item"
                                :class="{ 'nav-active': activeSection === 'bikes' }"
                            >
                                <span class="nav-icon">🚲</span>
                                <span v-if="sidebarOpen" class="nav-text">Mis Bicicletas</span>
                            </a>
                        </li>

                        <!-- Reportes (solo si tiene acceso) -->
                        <li v-if="canAccessReports">
                            <a
                                @click="setActiveSection('reports')"
                                class="nav-item"
                                :class="{ 'nav-active': activeSection === 'reports' }"
                            >
                                <span class="nav-icon">📈</span>
                                <span v-if="sidebarOpen" class="nav-text">Reportes</span>
                            </a>
                        </li>

                        <!-- Separador para admin -->
                        <li v-if="user.role === 'admin'" class="nav-divider">
                            <hr v-if="sidebarOpen">
                            <span v-if="sidebarOpen" class="divider-text">Administración</span>
                        </li>

                        <!-- Gestión de Usuarios (solo admin) -->
                        <li v-if="user.role === 'admin'">
                            <a
                                @click="setActiveSection('users')"
                                class="nav-item"
                                :class="{ 'nav-active': activeSection === 'users' }"
                            >
                                <span class="nav-icon">👥</span>
                                <span v-if="sidebarOpen" class="nav-text">Usuarios</span>
                            </a>
                        </li>

                        <!-- Configuración (solo admin) -->
                        <li v-if="user.role === 'admin'">
                            <a
                                @click="setActiveSection('settings')"
                                class="nav-item"
                                :class="{ 'nav-active': activeSection === 'settings' }"
                            >
                                <span class="nav-icon">⚙️</span>
                                <span v-if="sidebarOpen" class="nav-text">Configuración</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- User Info at Bottom -->
                <div class="sidebar-footer">
                    <div class="user-info">
                        <div class="user-avatar">
                            <span>{{ getUserInitials() }}</span>
                        </div>
                        <div v-if="sidebarOpen" class="user-details">
                            <p class="user-name">{{ user.name }}</p>
                            <p class="user-role">{{ getRoleText() }}</p>
                        </div>
                    </div>

                    <button
                        @click="logout"
                        class="logout-btn"
                        :title="sidebarOpen ? '' : 'Cerrar Sesión'"
                    >
                        <span class="logout-icon">🚪</span>
                        <span v-if="sidebarOpen" class="logout-text">Salir</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-content">
                    <div class="header-left">
                        <h1 class="page-title">{{ getPageTitle() }}</h1>
                        <div v-if="currentMembership" class="membership-status">
                            <span class="membership-badge" :class="'badge-' + currentMembership.plan_type">
                                {{ currentMembership.plan_info.name }}
                            </span>
                        </div>
                    </div>
                    <div class="header-right">
                        <div class="header-actions">
                            <!-- Notificación de membresía -->
                            <div v-if="!hasActiveMembership" class="membership-alert">
                                <span class="alert-icon">⚠️</span>
                                <span class="alert-text">Sin membresía activa</span>
                                <button @click="setActiveSection('memberships')" class="alert-btn">
                                    Activar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="content">
                <!-- Dashboard Section -->
                <div v-if="activeSection === 'dashboard'" class="section">
                    <!-- Admin Section -->
                    <div v-if="user.role === 'admin'" class="card mb-6">
                        <div class="card-content">
                            <h2 class="section-title">Panel de Administrador</h2>
                            <div class="stats-grid">
                                <div class="stat-card stat-blue">
                                    <h3>Gestión de Usuarios</h3>
                                    <p>Administrar usuarios del sistema</p>
                                    <button @click="setActiveSection('users')" class="stat-btn">Ver Usuarios</button>
                                </div>
                                <div class="stat-card stat-green">
                                    <h3>Configuración</h3>
                                    <p>Configurar el sistema</p>
                                    <button @click="setActiveSection('settings')" class="stat-btn">Configurar</button>
                                </div>
                                <div class="stat-card stat-purple">
                                    <h3>Reportes</h3>
                                    <p>Ver reportes del sistema</p>
                                    <button @click="setActiveSection('reports')" class="stat-btn">Ver Reportes</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Section -->
                    <div class="card">
                        <div class="card-content">
                            <h2 class="section-title">
                                {{ user.role === 'admin' ? 'Panel General' : 'Panel de Usuario' }}
                            </h2>
                            <div class="grid grid-2">
                                <div class="info-card">
                                    <h3>Mi Perfil</h3>
                                    <p>Ver y editar información personal</p>
                                    <button @click="setActiveSection('profile')" class="info-btn">Ver Perfil</button>
                                </div>
                                <div class="info-card">
                                    <h3>Actividad</h3>
                                    <p>Ver actividad reciente</p>
                                    <button class="info-btn">Ver Actividad</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Memberships Section -->
                <div v-else-if="activeSection === 'memberships'" class="section">
                    <div class="membership-container">
                        <!-- Aquí se carga el componente de membresías -->
                        <membership-payment ref="membershipComponent" />
                    </div>
                </div>

                <!-- Profile Section -->
                <div v-else-if="activeSection === 'profile'" class="section">
                    <div class="card">
                        <div class="card-content">
                            <h2 class="section-title">Mi Perfil</h2>
                            <div class="profile-info">
                                <div class="profile-field">
                                    <label>Nombre:</label>
                                    <span>{{ user.name }} {{ user.apellidos }}</span>
                                </div>
                                <div class="profile-field">
                                    <label>Email:</label>
                                    <span>{{ user.email }}</span>
                                </div>
                                <div class="profile-field">
                                    <label>DPI:</label>
                                    <span>{{ user.dpi }}</span>
                                </div>
                                <div class="profile-field">
                                    <label>Teléfono:</label>
                                    <span>{{ user.telefono || 'No registrado' }}</span>
                                </div>
                                <div class="profile-field">
                                    <label>Rol:</label>
                                    <span>{{ getRoleText() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Other Sections -->
                <div v-else class="section">
                    <div class="card">
                        <div class="card-content">
                            <h2 class="section-title">{{ getPageTitle() }}</h2>
                            <div class="coming-soon">
                                <p>🚧 Esta sección está en desarrollo</p>
                                <p class="text-sm">Pronto estará disponible</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<script>
// Importar el componente de membresías
import MembershipPayment from './MembershipPayment.vue';

export default {
    components: {
        MembershipPayment
    },

    data() {
        return {
            user: {},
            activeSection: 'dashboard',
            sidebarOpen: true,
            currentMembership: null,
            hasActiveMembership: false
        }
    },

    computed: {
        canAccessReports() {
            // Verificar si puede acceder a reportes según su membresía
            return this.hasActiveMembership || this.user.role === 'admin';
        }
    },

    async mounted() {
        await this.getCurrentUser();
        await this.loadCurrentMembership();
    },

    methods: {
        async getCurrentUser() {
            try {
                const response = await axios.get('/user');
                this.user = response.data.user;
            } catch (error) {
                console.error('Error al obtener usuario:', error);
                window.location.href = '/login';
            }
        },

        async loadCurrentMembership() {
            try {
                const response = await fetch('/api/memberships/current', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                const result = await response.json();

                if (result.success) {
                    this.currentMembership = result.membership;
                    this.hasActiveMembership = true;
                } else {
                    this.currentMembership = null;
                    this.hasActiveMembership = false;
                }
            } catch (error) {
                console.error('Error cargando membresía:', error);
                this.hasActiveMembership = false;
            }
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        setActiveSection(section) {
            this.activeSection = section;
        },

        getPageTitle() {
            const titles = {
                'dashboard': 'Dashboard',
                'memberships': 'Membresías',
                'profile': 'Mi Perfil',
                'bikes': 'Mis Bicicletas',
                'reports': 'Reportes',
                'users': 'Gestión de Usuarios',
                'settings': 'Configuración'
            };
            return titles[this.activeSection] || 'Dashboard';
        },

        getUserInitials() {
            if (!this.user.name) return 'U';
            const names = this.user.name.split(' ');
            return names.length > 1 ?
                names[0][0] + names[1][0] :
                names[0][0] + (this.user.apellidos ? this.user.apellidos[0] : '');
        },

        getRoleText() {
            return this.user.role === 'admin' ? 'Administrador' : 'Usuario';
        },

        async logout() {
            try {
                await axios.post('/logout');
                window.location.href = '/login';
            } catch (error) {
                console.error('Error al cerrar sesión:', error);
            }
        }
    }
}
</script>

<style scoped>
/* Layout Base */
.sidebar-container {
    transition: all 0.3s ease;
    position: relative;
    z-index: 10;
}

.sidebar-open {
    width: 280px;
}

.sidebar-closed {
    width: 70px;
}

.sidebar {
    height: 100vh;
    background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
    color: white;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    transition: all 0.3s ease;
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
}

.sidebar-open .sidebar {
    width: 280px;
}

.sidebar-closed .sidebar {
    width: 70px;
}

.main-content {
    flex: 1;
    margin-left: 280px;
    transition: margin-left 0.3s ease;
}

.sidebar-closed ~ .main-content {
    margin-left: 70px;
}

/* Sidebar Header */
.sidebar-header {
    padding: 20px;
    border-bottom: 1px solid #374151;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo-container {
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo-icon {
    font-size: 1.8rem;
}

.logo-text {
    font-size: 1.2rem;
    font-weight: bold;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.toggle-btn {
    background: none;
    border: none;
    color: #9ca3af;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.toggle-btn:hover {
    background: #374151;
    color: white;
}

.toggle-rotated {
    transform: rotate(180deg);
}

/* Navigation */
.sidebar-nav {
    flex: 1;
    padding: 20px 0;
    overflow-y: auto;
}

.nav-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: #d1d5db;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.nav-item:hover {
    background: #374151;
    color: white;
}

.nav-active {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}

.nav-active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: white;
}

.nav-icon {
    font-size: 1.2rem;
    min-width: 20px;
    text-align: center;
}

.nav-text {
    font-weight: 500;
}

.nav-badge {
    background: #ef4444;
    color: white;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: bold;
    margin-left: auto;
}

.nav-divider {
    margin: 20px 0;
    padding: 0 20px;
}

.nav-divider hr {
    border: none;
    border-top: 1px solid #374151;
    margin-bottom: 10px;
}

.divider-text {
    font-size: 0.8rem;
    color: #6b7280;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Sidebar Footer */
.sidebar-footer {
    border-top: 1px solid #374151;
    padding: 20px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
}

.user-details {
    flex: 1;
}

.user-name {
    font-weight: 600;
    font-size: 0.9rem;
    margin: 0 0 2px 0;
}

.user-role {
    font-size: 0.8rem;
    color: #9ca3af;
    margin: 0;
}

.logout-btn {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 12px;
    background: #374151;
    color: #d1d5db;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.logout-btn:hover {
    background: #ef4444;
    color: white;
}

/* Header */
.header {
    background: white;
    border-bottom: 1px solid #e5e7eb;
    padding: 0 30px;
    height: 70px;
    display: flex;
    align-items: center;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 20px;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.membership-status {
    display: flex;
    align-items: center;
}

.membership-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.badge-basic {
    background: #d1fae5;
    color: #065f46;
}

.badge-premium {
    background: #dbeafe;
    color: #1e40af;
}

.badge-vip {
    background: #fef3c7;
    color: #92400e;
}

.header-right {
    display: flex;
    align-items: center;
}

.membership-alert {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #fef3c7;
    color: #92400e;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.9rem;
}

.alert-icon {
    font-size: 1.1rem;
}

.alert-btn {
    background: #f59e0b;
    color: white;
    border: none;
    padding: 4px 12px;
    border-radius: 4px;
    font-size: 0.8rem;
    cursor: pointer;
    font-weight: 600;
}

.alert-btn:hover {
    background: #d97706;
}

/* Content */
.content {
    padding: 30px;
    min-height: calc(100vh - 70px);
}

.section {
    width: 100%;
}

.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.card-content {
    padding: 30px;
}

.section-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 20px;
}

.mb-6 {
    margin-bottom: 1.5rem;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.stat-card {
    padding: 24px;
    border-radius: 10px;
    position: relative;
    overflow: hidden;
}

.stat-blue {
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    color: #1e40af;
}

.stat-green {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #065f46;
}

.stat-purple {
    background: linear-gradient(135deg, #e9d5ff, #d8b4fe);
    color: #7c2d12;
}

.stat-card h3 {
    font-weight: 700;
    margin-bottom: 8px;
}

.stat-card p {
    font-size: 0.9rem;
    margin-bottom: 15px;
    opacity: 0.8;
}

.stat-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: inherit;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.9rem;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.stat-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
}

/* Grid */
.grid {
    display: grid;
    gap: 20px;
}

.grid-2 {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

.info-card {
    background: #f9fafb;
    padding: 20px;
    border-radius: 10px;
    border: 1px solid #e5e7eb;
}

.info-card h3 {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
}

.info-card p {
    color: #6b7280;
    font-size: 0.9rem;
    margin-bottom: 15px;
}

.info-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.9rem;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.info-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Profile */
.profile-info {
    display: grid;
    gap: 20px;
    max-width: 500px;
}

.profile-field {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #e5e7eb;
}

.profile-field:last-child {
    border-bottom: none;
}

.profile-field label {
    font-weight: 600;
    color: #374151;
}

.profile-field span {
    color: #6b7280;
}

/* Coming Soon */
.coming-soon {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.coming-soon p {
    margin-bottom: 10px;
}

.text-sm {
    font-size: 0.9rem;
}

/* Membership Container */
.membership-container {
    margin: -30px;
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar-container {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 20;
        height: 100vh;
    }

    .sidebar-closed {
        transform: translateX(-100%);
        width: 280px;
    }

    .main-content {
        margin-left: 0;
    }

    .sidebar-closed ~ .main-content {
        margin-left: 0;
    }

    .header {
        padding: 0 15px;
    }

    .content {
        padding: 15px;
    }

    .stats-grid, .grid-2 {
        grid-template-columns: 1fr;
    }

    .header-content {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }

    .membership-alert {
        font-size: 0.8rem;
        padding: 6px 12px;
    }
}
</style>
