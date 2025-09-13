<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <h1 class="text-xl font-semibold">Dashboard</h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">{{ user.name }} ({{ user.role }})</span>
                        <button
                            @click="logout"
                            class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
                        >
                            Cerrar Sesión
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Admin Section -->
            <div v-if="user.role === 'admin'" class="bg-white overflow-hidden shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Panel de Administrador</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h3 class="font-medium text-blue-900">Gestión de Usuarios</h3>
                            <p class="text-blue-700 text-sm">Administrar usuarios del sistema</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h3 class="font-medium text-green-900">Configuración</h3>
                            <p class="text-green-700 text-sm">Configurar el sistema</p>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg">
                            <h3 class="font-medium text-purple-900">Reportes</h3>
                            <p class="text-purple-700 text-sm">Ver reportes del sistema</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Section -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        {{ user.role === 'admin' ? 'Panel General' : 'Panel de Usuario' }}
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-medium text-gray-900">Mi Perfil</h3>
                            <p class="text-gray-600 text-sm">Ver y editar información personal</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="font-medium text-gray-900">Actividad</h3>
                            <p class="text-gray-600 text-sm">Ver actividad reciente</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script>
export default {
    data() {
        return {
            user: {}
        }
    },

    async mounted() {
        await this.getCurrentUser();
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
