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

                        <!-- Estaciones -->
                        <li>
                            <a
                                @click="setActiveSection('stations')"
                                class="nav-item"
                                :class="{ 'nav-active': activeSection === 'stations' }"
                            >
                                <span class="nav-icon">🏢</span>
                                <span v-if="sidebarOpen" class="nav-text">Estaciones</span>
                            </a>
                        </li>

                        <!-- Bicicletas -->
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
                        <li v-if="isAdmin" class="nav-divider">
                            <hr v-if="sidebarOpen">
                            <span v-if="sidebarOpen" class="divider-text">Administración</span>
                        </li>

                        <!-- Gestión de Usuarios (solo admin) -->
                        <li v-if="isAdmin">
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
                        <li v-if="isAdmin">
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
                            <span class="membership-info">
                                {{ currentMembership.days_remaining }} días restantes
                            </span>
                        </div>
                        <div v-if="isAdmin" class="admin-badge">
                            <span class="admin-icon">⭐</span>
                            <span class="admin-text">Administrador</span>
                        </div>
                    </div>
                    <div class="header-right">
                        <div class="header-actions">
                            <!-- Notificación de membresía -->
                            <div v-if="!hasActiveMembership && !isAdmin" class="membership-alert">
                                <span class="alert-icon">⚠️</span>
                                <span class="alert-text">Sin membresía activa</span>
                                <button @click="setActiveSection('memberships')" class="alert-btn">
                                    Activar
                                </button>
                            </div>
                            <!-- Estado de membresía activa -->
                            <div v-else-if="hasActiveMembership" class="membership-active">
                                <span class="active-icon">✅</span>
                                <span class="active-text">Membresía Activa</span>
                            </div>
                            <!-- Estado de admin -->
                            <div v-else-if="isAdmin" class="admin-active">
                                <span class="admin-active-icon">🛡️</span>
                                <span class="admin-active-text">Acceso Total</span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <main class="content">
                <!-- Dashboard Section -->
                <div v-if="activeSection === 'dashboard'" class="section">
                    <!-- Admin Dashboard -->
                    <div v-if="isAdmin" class="admin-dashboard">
                        <!-- Welcome Card para Admin -->
                        <div class="card mb-6">
                            <div class="card-content">
                                <div class="admin-welcome">
                                    <div class="admin-welcome-header">
                                        <div class="admin-welcome-icon">
                                            <span class="admin-emoji">👑</span>
                                        </div>
                                        <div class="admin-welcome-content">
                                            <h2 class="admin-welcome-title">
                                                Bienvenido, Administrador {{ user.name }}
                                            </h2>
                                            <p class="admin-welcome-subtitle">
                                                Panel de control administrativo - Acceso completo al sistema
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Cards para Admin -->
                        <div class="card mb-6">
                            <div class="card-content">
                                <h2 class="section-title">Panel de Administrador</h2>
                                <div class="stats-grid">
                                    <div class="stat-card stat-blue">
                                        <div class="stat-icon">👥</div>
                                        <div class="stat-content">
                                            <h3>Gestión de Usuarios</h3>
                                            <p>Administrar usuarios del sistema</p>
                                            <button @click="setActiveSection('users')" class="stat-btn">Ver Usuarios</button>
                                        </div>
                                    </div>
                                    <div class="stat-card stat-green">
                                        <div class="stat-icon">⚙️</div>
                                        <div class="stat-content">
                                            <h3>Configuración</h3>
                                            <p>Configurar el sistema</p>
                                            <button @click="setActiveSection('settings')" class="stat-btn">Configurar</button>
                                        </div>
                                    </div>
                                    <div class="stat-card stat-purple">
                                        <div class="stat-icon">📈</div>
                                        <div class="stat-content">
                                            <h3>Reportes</h3>
                                            <p>Ver reportes del sistema</p>
                                            <button @click="setActiveSection('reports')" class="stat-btn">Ver Reportes</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Dashboard -->
                    <div v-else>
                        <!-- Membership Status Card -->
                        <div class="card mb-6">
                            <div class="card-content">
                                <div v-if="hasActiveMembership" class="membership-welcome">
                                    <div class="welcome-header">
                                        <div class="welcome-icon">
                                            <span class="plan-emoji">{{ getPlanEmoji(currentMembership.plan_type) }}</span>
                                        </div>
                                        <div class="welcome-content">
                                            <h2 class="welcome-title">
                                                ¡Bienvenido a tu plan {{ currentMembership.plan_info.name }}!
                                            </h2>
                                            <p class="welcome-subtitle">
                                                Tu membresía está activa hasta el {{ formatDate(currentMembership.end_date) }}
                                            </p>
                                        </div>
                                        <div class="welcome-badge">
                                            <span class="days-remaining">{{ currentMembership.days_remaining }}</span>
                                            <span class="days-text">días</span>
                                        </div>
                                    </div>

                                    <div class="membership-features">
                                        <h3 class="features-title">Características de tu plan:</h3>
                                        <div class="features-grid">
                                            <div v-for="(feature, index) in currentMembership.features"
                                                 :key="index"
                                                 class="feature-item">
                                                <span class="feature-icon">✓</span>
                                                <span class="feature-text">{{ feature }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="membership-actions">
                                        <button @click="setActiveSection('memberships')" class="action-btn primary">
                                            Gestionar Membresía
                                        </button>
                                        <button @click="setActiveSection('profile')" class="action-btn secondary">
                                            Ver Perfil
                                        </button>
                                    </div>
                                </div>

                                <!-- Sin membresía -->
                                <div v-else class="no-membership">
                                    <div class="no-membership-icon">🚫</div>
                                    <h2 class="no-membership-title">No tienes una membresía activa</h2>
                                    <p class="no-membership-text">
                                        Activa una membresía para acceder a todas las funcionalidades de la plataforma
                                    </p>
                                    <button @click="setActiveSection('memberships')" class="activation-btn">
                                        Activar Membresía
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Services Section para todos -->
                    <div class="card">
                        <div class="card-content">
                            <h2 class="section-title">
                                {{ isAdmin ? 'Acceso Rápido' : 'Mis Servicios' }}
                            </h2>
                            <div class="services-grid">
                                <div class="service-card" :class="{ disabled: !hasActiveMembership && !isAdmin }">
                                    <div class="service-icon">👤</div>
                                    <h3>Mi Perfil</h3>
                                    <p>Ver y editar información personal</p>
                                    <button @click="setActiveSection('profile')"
                                            class="service-btn"
                                            :disabled="!hasActiveMembership && !isAdmin">
                                        Ver Perfil
                                    </button>
                                </div>

                                <div class="service-card" :class="{ disabled: !hasActiveMembership && !isAdmin }">
                                    <div class="service-icon">🏢</div>
                                    <h3>Estaciones</h3>
                                    <p>Gestionar estaciones</p>
                                    <button @click="setActiveSection('stations')"
                                            class="service-btn"
                                            :disabled="!hasActiveMembership && !isAdmin">
                                        Ver Estaciones
                                    </button>
                                    <span v-if="!hasActiveMembership && !isAdmin" class="premium-badge">Premium</span>
                                </div>

                                <div class="service-card" :class="{ disabled: !hasActiveMembership && !isAdmin }">
                                    <div class="service-icon">🚲</div>
                                    <h3>Mis Bicicletas</h3>
                                    <p>Gestionar mis bicicletas</p>
                                    <button @click="setActiveSection('bikes')"
                                            class="service-btn"
                                            :disabled="!hasActiveMembership && !isAdmin">
                                        Ver Bicicletas
                                    </button>
                                    <span v-if="!hasActiveMembership && !isAdmin" class="premium-badge">Premium</span>
                                </div>

                                <div class="service-card" :class="{ disabled: !canAccessReports }">
                                    <div class="service-icon">📈</div>
                                    <h3>Reportes</h3>
                                    <p>Ver reportes y estadísticas</p>
                                    <button @click="setActiveSection('reports')"
                                            class="service-btn"
                                            :disabled="!canAccessReports">
                                        Ver Reportes
                                    </button>
                                    <span v-if="!canAccessReports" class="premium-badge">Premium</span>
                                </div>

                                <div class="service-card">
                                    <div class="service-icon">💳</div>
                                    <h3>Membresías</h3>
                                    <p>Gestionar mi membresía</p>
                                    <button @click="setActiveSection('memberships')" class="service-btn">
                                        {{ hasActiveMembership ? 'Gestionar' : 'Activar' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Memberships Section -->
                <div v-else-if="activeSection === 'memberships'" class="section">
                    <div class="membership-container">
                        <!-- Aquí se carga el componente de membresías -->
                        <membership-payment
                            ref="membershipComponent"
                            @membership-updated="onMembershipUpdated"
                        />
                    </div>
                </div>

                <!-- Stations Section -->
                <div v-else-if="activeSection === 'stations'" class="section">
                    <div class="station-container">
                        <estacion ref="stationComponent" />
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
                                    <span class="role-badge" :class="{'role-admin': isAdmin, 'role-user': !isAdmin}">
                                        {{ getRoleText() }}
                                    </span>
                                </div>
                                <div v-if="currentMembership" class="profile-field">
                                    <label>Membresía:</label>
                                    <span class="membership-badge" :class="'badge-' + currentMembership.plan_type">
                                        {{ currentMembership.plan_info.name }}
                                    </span>
                                </div>
                                <div v-if="isAdmin" class="profile-field">
                                    <label>Permisos:</label>
                                    <span class="permissions-text">Acceso total al sistema</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bikes Section -->
                <div v-else-if="activeSection === 'bikes'" class="section">
                    <div class="card">
                        <div class="card-content">
                            <h2 class="section-title">Mis Bicicletas</h2>
                            <div class="coming-soon">
                                <p>🚧 Esta sección está en desarrollo</p>
                                <p class="text-sm">Pronto estará disponible</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reports Section -->
                <div v-else-if="activeSection === 'reports'" class="section">
                    <div class="card">
                        <div class="card-content">
                            <h2 class="section-title">Reportes</h2>
                            <div class="coming-soon">
                                <p>🚧 Esta sección está en desarrollo</p>
                                <p class="text-sm">Pronto estará disponible</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Section (Admin only) -->
                <div v-else-if="activeSection === 'users'" class="section">
                    <div class="user-management-container">
                        <user-management ref="userManagementComponent" />
                    </div>
                </div>

                <!-- Settings Section (Admin only) -->
                <div v-else-if="activeSection === 'settings'" class="section">
                    <div class="card">
                        <div class="card-content">
                            <h2 class="section-title">Configuración</h2>
                            <div class="coming-soon">
                                <p>🚧 Esta sección está en desarrollo</p>
                                <p class="text-sm">Pronto estará disponible</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fallback for any other section -->
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
// Importar el componente de membresías y estaciones
import MembershipPayment from './MembershipPayment.vue';
import Estacion from './Estacion.vue';
<<<<<<< HEAD
import BikesList from './BikesList.vue';          // Nuevo componente
import UsageHistory from './UsageHistory.vue';    // Nuevo componente
import DamageReports from './DamageReports.vue';  // Nuevo componente
=======
import UserManagement from "@/components/UserManagement.vue";
>>>>>>> origin/connor-dev

export default {
    components: {
        MembershipPayment,
        Estacion,
<<<<<<< HEAD
        BikesList,
        UsageHistory,
        DamageReports,
=======
        UserManagement,
>>>>>>> origin/connor-dev
    },

    data() {
        return {
            user: {},
            activeSection: 'dashboard',
            sidebarOpen: true,
            currentMembership: null,
            hasActiveMembership: false,
            loading: false,
            userLoaded: false
        }
    },

    computed: {
        isAdmin() {
            return this.user && this.user.role === 'admin';
        },

        canAccessReports() {
            return this.hasActiveMembership || this.isAdmin;
        }
    },

    async mounted() {
        await this.initializeUser();
    },

    methods: {
        async initializeUser() {
            try {
                await this.getCurrentUser();
                if (this.userLoaded && !this.isAdmin) {
                    await this.loadCurrentMembership();
                }
            } catch (error) {
                console.error('Error inicializando usuario:', error);
            }
        },

        async getCurrentUser() {
            try {
                // Intentar diferentes endpoints
                let response;

                try {
                    response = await axios.get('/user', {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        }
                    });
                } catch (firstError) {
                    console.log('Primer endpoint falló, probando alternativo...');
                    response = await axios.get('/user', {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        }
                    });
                }

                console.log('Respuesta del usuario:', response.data);

                // Manejar diferentes formatos de respuesta
                if (response.data.success && response.data.user) {
                    this.user = response.data.user;
                } else if (response.data.user) {
                    this.user = response.data.user;
                } else if (response.data.name) {
                    this.user = response.data;
                } else {
                    throw new Error('Formato de respuesta inesperado');
                }

                this.userLoaded = true;
                console.log('Usuario cargado exitosamente:', this.user);
                console.log('Es admin:', this.isAdmin);

            } catch (error) {
                console.error('Error al obtener usuario:', error);

                // Si hay error de autenticación, redirigir al login
                if (error.response && [401, 403].includes(error.response.status)) {
                    console.log('Redirigiendo al login por error de autenticación');
                    window.location.href = '/login';
                    return;
                }

                // Si no es error de auth, mostrar error pero no redirigir
                console.error('Error no crítico obteniendo usuario, continuando...');

                // Establecer usuario por defecto para evitar errores
                this.user = {
                    name: 'Usuario',
                    email: 'No disponible',
                    role: 'user'
                };
                this.userLoaded = true;
            }
        },

        async loadCurrentMembership() {
            if (this.isAdmin) {
                console.log('Usuario admin, saltando carga de membresía');
                return;
            }

            this.loading = true;
            try {
                const response = await axios.get('/api/memberships/current', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                console.log('Respuesta de membresía:', response.data);

                if (response.data.success && response.data.membership) {
                    this.currentMembership = response.data.membership;
                    this.hasActiveMembership = response.data.membership.is_active || false;
                    console.log('Membresía cargada:', this.currentMembership);
                } else {
                    this.currentMembership = null;
                    this.hasActiveMembership = false;
                    console.log('No hay membresía activa:', response.data.message);
                }
            } catch (error) {
                console.error('Error cargando membresía:', error);
                this.hasActiveMembership = false;
                this.currentMembership = null;
            } finally {
                this.loading = false;
            }
        },

        async onMembershipUpdated(membershipData) {
            console.log('Membresía actualizada:', membershipData);
            await this.loadCurrentMembership();
            this.setActiveSection('dashboard');
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        setActiveSection(section) {
            this.activeSection = section;
            console.log('Cambiando a sección:', section);
        },

        getPageTitle() {
            const titles = {
                'dashboard': 'Dashboard',
                'memberships': 'Membresías',
<<<<<<< HEAD
                'stations': 'Estacion',
=======
                'stations': 'Estaciones',
>>>>>>> origin/connor-dev
                'profile': 'Mi Perfil',
                'bikes': 'Mis Bicicletas',
                'reports': 'Reportes',
                'users': 'Gestión de Usuarios',
                'settings': 'Configuración'
            };
            return titles[this.activeSection] || 'Dashboard';
        },

        getUserInitials() {
            if (!this.user || !this.user.name) return 'U';

            const name = this.user.name.toString();
            const names = name.split(' ');

            if (names.length > 1) {
                return names[0][0].toUpperCase() + names[1][0].toUpperCase();
            }

            const firstChar = names[0][0] ? names[0][0].toUpperCase() : 'U';
            const secondChar = this.user.apellidos && this.user.apellidos[0]
                ? this.user.apellidos[0].toUpperCase()
                : (names[0][1] ? names[0][1].toUpperCase() : '');

            return firstChar + secondChar;
        },

        getRoleText() {
            return this.isAdmin ? 'Administrador' : 'Usuario';
        },

        getPlanEmoji(planType) {
            const emojis = {
                'basic': '🥉',
                'premium': '🥈',
                'vip': '🥇'
            };
            return emojis[planType] || '💳';
        },

        formatDate(dateString) {
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('es-GT', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            } catch (error) {
                return dateString;
            }
        },

        async logout() {
            try {
                await axios.post('/logout', {}, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                window.location.href = '/login';
            } catch (error) {
                console.error('Error al cerrar sesión:', error);
                // Forzar redirección incluso si hay error
                window.location.href = '/login';
            }
        }
    }
}
</script>

<style scoped>
/* Estilos base existentes... */
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

/* Sidebar styles... */
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

/* Navigation styles... */
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

/* Header styles... */
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
    gap: 12px;
}

.membership-info {
    font-size: 0.9rem;
    color: #6b7280;
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

.membership-active {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #d1fae5;
    color: #065f46;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.9rem;
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

/* Content styles... */
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

.mb-6 {
    margin-bottom: 1.5rem;
}

/* Membership Welcome Styles */
.membership-welcome {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin: -30px -30px 30px -30px;
}

.welcome-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
}

.welcome-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.plan-emoji {
    font-size: 2rem;
}

.welcome-content {
    flex: 1;
}

.welcome-title {
    font-size: 1.4rem;
    font-weight: 700;
    margin: 0 0 8px 0;
}

.welcome-subtitle {
    font-size: 0.95rem;
    opacity: 0.9;
    margin: 0;
}

.welcome-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 15px 20px;
    border-radius: 12px;
    text-align: center;
    min-width: 80px;
}

.days-remaining {
    font-size: 1.8rem;
    font-weight: 700;
    display: block;
}

.days-text {
    font-size: 0.8rem;
    opacity: 0.8;
}

.membership-features {
    margin-bottom: 25px;
}

.features-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin: 0 0 15px 0;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.1);
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 0.9rem;
}

.feature-icon {
    color: #10b981;
    font-weight: bold;
}

.membership-actions {
    display: flex;
    gap: 15px;
}

.action-btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.action-btn.primary {
    background: white;
    color: #667eea;
}

.action-btn.secondary {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

/* No Membership Styles */
.no-membership {
    text-align: center;
    padding: 40px 20px;
}

.no-membership-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.7;
}

.no-membership-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 15px 0;
}

.no-membership-text {
    font-size: 1rem;
    color: #6b7280;
    margin: 0 0 25px 0;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.activation-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.activation-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

/* Stats Grid Styles */
.section-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 25px 0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.stat-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 25px;
    display: flex;
    gap: 20px;
    align-items: center;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.stat-blue {
    border-left: 4px solid #3b82f6;
}

.stat-green {
    border-left: 4px solid #10b981;
}

.stat-purple {
    border-left: 4px solid #8b5cf6;
}

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
}

.stat-content h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 8px 0;
}

.stat-content p {
    color: #6b7280;
    margin: 0 0 15px 0;
    font-size: 0.9rem;
}

.stat-btn {
    background: #f3f4f6;
    color: #374151;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.stat-btn:hover {
    background: #e5e7eb;
}

/* Services Grid Styles */
.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
}

.service-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
}

.service-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    border-color: #d1d5db;
}

.service-card.disabled {
    opacity: 0.6;
    background: #f3f4f6;
}

.service-card.disabled:hover {
    transform: none;
    box-shadow: none;
}

.service-icon {
    font-size: 2.5rem;
    margin-bottom: 15px;
    display: block;
}

.service-card h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 10px 0;
}

.service-card p {
    color: #6b7280;
    margin: 0 0 20px 0;
    font-size: 0.9rem;
}

.service-btn {
    background: #667eea;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.service-btn:hover:not(:disabled) {
    background: #5b6fe8;
}

.service-btn:disabled {
    background: #d1d5db;
    cursor: not-allowed;
}

.premium-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #f59e0b;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
}

/* Profile Styles */
.profile-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.profile-field {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.profile-field label {
    font-weight: 600;
    color: #374151;
    font-size: 0.9rem;
}

.profile-field span {
    color: #1f2937;
    font-size: 1rem;
}

/* Sidebar Footer */
.sidebar-footer {
    padding: 20px;
    border-top: 1px solid #374151;
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
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
}

.user-details {
    flex: 1;
    overflow: hidden;
}

.user-name {
    font-size: 0.9rem;
    font-weight: 600;
    color: white;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-role {
    font-size: 0.8rem;
    color: #9ca3af;
    margin: 0;
}

.logout-btn {
    width: 100%;
    background: #dc2626;
    color: white;
    border: none;
    padding: 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.logout-btn:hover {
    background: #b91c1c;
}

.logout-text {
    font-size: 0.9rem;
    font-weight: 500;
}

/* Coming Soon Styles */
.coming-soon {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.coming-soon p:first-child {
    font-size: 2rem;
    margin-bottom: 10px;
}

.text-sm {
    font-size: 0.9rem;
}

/* Divider Styles */
.nav-divider {
    margin: 20px 0;
}

.nav-divider hr {
    border: none;
    border-top: 1px solid #374151;
    margin: 0 20px 10px 20px;
}

.divider-text {
    font-size: 0.8rem;
    color: #9ca3af;
    font-weight: 500;
    padding: 0 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Membership Container */
.membership-container {
    width: 100%;
}

/* Responsive Design */
@media (max-width: 768px) {
    .sidebar-container {
        position: fixed;
        z-index: 1000;
    }

    .main-content {
        margin-left: 0;
    }

    .sidebar-closed ~ .main-content {
        margin-left: 0;
    }

    .header-content {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
    }

    .services-grid {
        grid-template-columns: 1fr;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .features-grid {
        grid-template-columns: 1fr;
    }

    .membership-actions {
        flex-direction: column;
    }

    .welcome-header {
        flex-direction: column;
        text-align: center;
    }

}
</style>
