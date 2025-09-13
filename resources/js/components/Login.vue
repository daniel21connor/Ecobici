<template>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 px-4 py-8">
        <!-- Partículas de fondo decorativas -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 left-1/4 w-72 h-72 bg-blue-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-pulse"></div>
            <div class="absolute top-1/3 right-1/4 w-72 h-72 bg-purple-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-pulse animation-delay-2000"></div>
            <div class="absolute bottom-1/4 left-1/3 w-72 h-72 bg-indigo-200 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-pulse animation-delay-4000"></div>
        </div>

        <div class="max-w-md w-full relative">
            <!-- Contenedor principal con glassmorphism -->
            <div class="bg-white/70 backdrop-blur-lg p-8 rounded-2xl shadow-xl border border-white/20 transition-all duration-500 hover:shadow-2xl">
                <!-- Header con icono -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full mb-4 shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="!isRegistering" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">
                        {{ isRegistering ? 'Registro' : 'Iniciar Sesión' }}
                    </h2>
                </div>

                <!-- Formulario con transiciones -->
                <form @submit.prevent="isRegistering ? register() : login()" class="space-y-6">
                    <!-- Campo nombre con transición -->
                    <transition
                        enter-active-class="transition-all duration-500 ease-out"
                        enter-from-class="opacity-0 transform -translate-y-4"
                        enter-to-class="opacity-100 transform translate-y-0"
                        leave-active-class="transition-all duration-300 ease-in"
                        leave-from-class="opacity-100 transform translate-y-0"
                        leave-to-class="opacity-0 transform -translate-y-4"
                    >
                        <div v-if="isRegistering">
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Nombre completo"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 outline-none transition-all duration-300 bg-white/80 backdrop-blur-sm"
                                required
                            >
                        </div>
                    </transition>

                    <!-- Campo email -->
                    <div>
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="Email"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 outline-none transition-all duration-300 bg-white/80 backdrop-blur-sm"
                            required
                        >
                    </div>

                    <!-- Campo contraseña -->
                    <div>
                        <input
                            v-model="form.password"
                            type="password"
                            placeholder="Contraseña"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 outline-none transition-all duration-300 bg-white/80 backdrop-blur-sm"
                            required
                        >
                    </div>

                    <!-- Campo rol con transición -->
                    <transition
                        enter-active-class="transition-all duration-500 ease-out"
                        enter-from-class="opacity-0 transform -translate-y-4"
                        enter-to-class="opacity-100 transform translate-y-0"
                        leave-active-class="transition-all duration-300 ease-in"
                        leave-from-class="opacity-100 transform translate-y-0"
                        leave-to-class="opacity-0 transform -translate-y-4"
                    >
                        <div v-if="isRegistering">
                            <select
                                v-model="form.role"
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20 outline-none transition-all duration-300 bg-white/80 backdrop-blur-sm appearance-none cursor-pointer"
                            >
                                <option value="user">Usuario</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </transition>

                    <!-- CAPTCHA Matemático (solo para login) -->
                    <transition
                        enter-active-class="transition-all duration-500 ease-out"
                        enter-from-class="opacity-0 transform -translate-y-4"
                        enter-to-class="opacity-100 transform translate-y-0"
                        leave-active-class="transition-all duration-300 ease-in"
                        leave-from-class="opacity-100 transform translate-y-0"
                        leave-to-class="opacity-0 transform -translate-y-4"
                    >
                        <div v-if="!isRegistering" class="space-y-2">
                            <label class="text-sm font-medium text-gray-700">Verificación de seguridad</label>
                            <div class="flex items-center space-x-3 p-4 bg-gray-50/80 rounded-xl border-2 border-gray-200">
                                <span class="text-lg font-bold text-gray-800 min-w-[80px]">{{ mathQuestion }}</span>
                                <input
                                    v-model="captchaAnswer"
                                    type="number"
                                    placeholder="?"
                                    class="flex-1 px-3 py-2 text-center border-2 border-gray-200 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition-all duration-300"
                                    required
                                >
                                <button
                                    type="button"
                                    @click="generateMathCaptcha"
                                    class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors duration-200"
                                    title="Generar nueva pregunta"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </transition>

                    <!-- Botón principal -->
                    <button
                        type="submit"
                        class="w-full relative overflow-hidden bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-3 px-6 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 group"
                    >
                        <span class="relative z-10">
                            {{ isRegistering ? 'Registrarse' : 'Iniciar Sesión' }}
                        </span>
                        <!-- Efecto hover -->
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-600 to-indigo-700 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left"></div>
                    </button>
                </form>

                <!-- Toggle entre login/registro -->
                <div class="text-center mt-6 pt-4 border-t border-gray-200">
                    <button
                        @click="isRegistering = !isRegistering; generateMathCaptcha();"
                        class="group relative text-gray-600 hover:text-blue-600 transition-colors duration-300 font-medium"
                    >
                        <span class="relative z-10">
                            {{ isRegistering ? '¿Ya tienes cuenta? Iniciar Sesión' : '¿No tienes cuenta? Registrarse' }}
                        </span>
                        <span class="absolute inset-x-0 bottom-0 h-0.5 bg-blue-600 transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300"></span>
                    </button>
                </div>

                <!-- Mensaje de error con animación -->
                <transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 transform translate-y-2"
                    enter-to-class="opacity-100 transform translate-y-0"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 transform translate-y-0"
                    leave-to-class="opacity-0 transform translate-y-2"
                >
                    <div v-if="error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-xl">
                        <div class="flex items-center">
                            <svg class="h-4 w-4 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-red-700 text-sm">{{ error }}</p>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            isRegistering: false,
            form: {
                name: '',
                email: '',
                password: '',
                role: 'user'
            },
            error: '',
            // CAPTCHA
            mathQuestion: '',
            captchaAnswer: '',
            correctAnswer: 0
        }
    },
    mounted() {
        this.generateMathCaptcha();
    },
    methods: {
        // Generar CAPTCHA matemático
        generateMathCaptcha() {
            const num1 = Math.floor(Math.random() * 10) + 1;
            const num2 = Math.floor(Math.random() * 10) + 1;
            const operations = ['+', '-', '*'];
            const operation = operations[Math.floor(Math.random() * operations.length)];

            this.mathQuestion = `${num1} ${operation} ${num2} = ?`;

            switch(operation) {
                case '+':
                    this.correctAnswer = num1 + num2;
                    break;
                case '-':
                    this.correctAnswer = num1 - num2;
                    break;
                case '*':
                    this.correctAnswer = num1 * num2;
                    break;
            }

            this.captchaAnswer = '';
        },

        // Validar CAPTCHA
        validateCaptcha() {
            return parseInt(this.captchaAnswer) === this.correctAnswer;
        },

        async login() {
            // Validar CAPTCHA solo en login
            if (!this.validateCaptcha()) {
                this.error = 'Respuesta del CAPTCHA incorrecta.';
                this.generateMathCaptcha();
                return;
            }

            try {
                const response = await axios.post('/login', {
                    email: this.form.email,
                    password: this.form.password
                });

                if (response.data.success) {
                    window.location.href = '/dashboard';
                }
            } catch (error) {
                this.error = 'Credenciales incorrectas';
                this.generateMathCaptcha();
            }
        },

        async register() {
            try {
                const response = await axios.post('/register', this.form);

                if (response.data.success) {
                    window.location.href = '/dashboard';
                }
            } catch (error) {
                this.error = 'Error al registrar usuario';
            }
        }
    }
}
</script>

<style scoped>
.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}

/* Custom scrollbar para select */
select::-webkit-scrollbar {
    width: 8px;
}

select::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

select::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

select::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Estilos responsive */
@media (max-width: 480px) {
    .bg-gradient-to-br {
        padding: 1rem;
    }

    .bg-white\/70 {
        padding: 1.5rem;
    }

    .text-2xl {
        font-size: 1.5rem;
    }
}
</style>
