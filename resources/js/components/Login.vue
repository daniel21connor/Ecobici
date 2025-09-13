<template>
    <div class="container">
        <div class="auth-wrapper">
            <transition name="slide" mode="out-in">
                <!-- PANTALLA DE LOGIN -->
                <div v-if="currentView === 'login'" key="login" class="auth-card">
                    <div class="logo-container">
                        <h1 class="text-primary">Auroracicleta</h1>
                    </div>
                    <h3 class="text-center">Login</h3>

                    <!-- Mensaje de bloqueo -->
                    <div v-if="isBlocked" class="alert alert-danger">
                        <div class="blocked-message">
                            <i class="block-icon">🚫</i>
                            <h4>Cuenta temporalmente bloqueada</h4>
                            <p>Demasiados intentos fallidos.</p>
                            <div class="countdown">
                                Podrá intentar nuevamente en: <strong>{{ formatTime(timeLeft) }}</strong>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="login" :class="{ 'form-disabled': isBlocked }">
                        <div class="form-group">
                            <label for="email">Correo</label>
                            <input
                                type="email"
                                id="email"
                                v-model="email"
                                class="form-control custom-input"
                                placeholder="Ingrese su correo"
                                :disabled="isBlocked || loading"
                                required
                            />
                        </div>
                        <div class="form-group">
                            <label for="password">Contraseña</label>
                            <input
                                type="password"
                                id="password"
                                v-model="password"
                                class="form-control custom-input"
                                placeholder="Ingrese su contraseña"
                                :disabled="isBlocked || loading"
                                required
                            />
                        </div>

                        <!-- CAPTCHA Matemático Simple -->
                        <div class="form-group captcha-container" v-if="!isBlocked">
                            <label>Verificación de seguridad:</label>
                            <div class="math-captcha">
                                <span class="captcha-question">{{ mathQuestion }}</span>
                                <input
                                    type="number"
                                    v-model="captchaAnswer"
                                    class="form-control custom-input captcha-input"
                                    placeholder="Resultado"
                                    :disabled="loading"
                                    required
                                />
                                <button type="button" @click="generateMathCaptcha" class="refresh-captcha" :disabled="loading">
                                    🔄
                                </button>
                            </div>
                        </div>

                        <!-- Contador de intentos -->
                        <div v-if="attemptsLeft < 3 && attemptsLeft > 0" class="attempts-warning">
                            ⚠️ Le quedan <strong>{{ attemptsLeft }}</strong> intento(s)
                        </div>

                        <button type="submit" class="btn custom-btn my-2" :disabled="loading || isBlocked">
                            <span v-if="loading">Cargando...</span>
                            <span v-else-if="isBlocked">Bloqueado</span>
                            <span v-else>Login</span>
                        </button>

                        <!-- Enlace para registro -->
                        <div class="text-center mt-3">
                            <button type="button" @click="showRegister" class="link-button" :disabled="loading">
                                ¿No tienes cuenta? Regístrate aquí
                            </button>
                        </div>
                    </form>
                    <p v-if="error" class="text-danger mt-2">{{ error }}</p>
                </div>

                <!-- PANTALLA DE REGISTRO -->
                <div v-else-if="currentView === 'register'" key="register" class="auth-card">
                    <div class="logo-container">
                        <h1 class="text-primary">Auroracicleta</h1>
                    </div>
                    <h3 class="text-center">Crear Cuenta</h3>

                    <form @submit.prevent="register" class="register-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Nombre *</label>
                                <input
                                    type="text"
                                    id="name"
                                    v-model="registerData.name"
                                    class="form-control custom-input"
                                    placeholder="Ingrese su nombre"
                                    :disabled="loading"
                                    required
                                />
                            </div>
                            <div class="form-group">
                                <label for="apellidos">Apellidos *</label>
                                <input
                                    type="text"
                                    id="apellidos"
                                    v-model="registerData.apellidos"
                                    class="form-control custom-input"
                                    placeholder="Ingrese sus apellidos"
                                    :disabled="loading"
                                    required
                                />
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="register-email">Correo *</label>
                            <input
                                type="email"
                                id="register-email"
                                v-model="registerData.email"
                                class="form-control custom-input"
                                placeholder="correo@ejemplo.com"
                                :disabled="loading"
                                required
                            />
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="dpi">DPI *</label>
                                <input
                                    type="text"
                                    id="dpi"
                                    v-model="registerData.dpi"
                                    class="form-control custom-input"
                                    placeholder="1234567890123"
                                    maxlength="13"
                                    pattern="[0-9]{13}"
                                    :disabled="loading"
                                    required
                                />
                            </div>
                            <div class="form-group">
                                <label for="fecha_nacimiento">Fecha de Nacimiento *</label>
                                <input
                                    type="date"
                                    id="fecha_nacimiento"
                                    v-model="registerData.fecha_nacimiento"
                                    class="form-control custom-input"
                                    :disabled="loading"
                                    required
                                />
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="telefono">Teléfono</label>
                            <input
                                type="tel"
                                id="telefono"
                                v-model="registerData.telefono"
                                class="form-control custom-input"
                                placeholder="+502 1234-5678"
                                :disabled="loading"
                            />
                        </div>

                        <div class="form-group">
                            <label for="register-password">Contraseña *</label>
                            <input
                                type="password"
                                id="register-password"
                                v-model="registerData.password"
                                class="form-control custom-input"
                                placeholder="Mínimo 6 caracteres"
                                :disabled="loading"
                                required
                            />
                        </div>

                        <div class="form-group">
                            <label for="foto">Foto de Perfil</label>
                            <input
                                type="file"
                                id="foto"
                                @change="handleFileUpload"
                                class="form-control custom-input file-input"
                                accept="image/jpeg,image/png,image/jpg,image/gif"
                                :disabled="loading"
                            />
                            <small class="form-text">Opcional. Máximo 2MB. Formatos: JPG, PNG, GIF</small>
                        </div>

                        <button type="submit" class="btn custom-btn my-2" :disabled="loading">
                            <span v-if="loading">Creando cuenta...</span>
                            <span v-else>Crear Cuenta</span>
                        </button>

                        <div class="text-center mt-3">
                            <button type="button" @click="showLogin" class="link-button" :disabled="loading">
                                ← Ya tengo cuenta
                            </button>
                        </div>
                    </form>

                    <p v-if="error" class="text-danger mt-2">{{ error }}</p>
                    <p v-if="successMessage" class="text-success mt-2">{{ successMessage }}</p>
                </div>
            </transition>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            currentView: 'login', // 'login' o 'register'

            // Login data
            email: "",
            password: "",
            mathQuestion: "",
            captchaAnswer: "",
            correctAnswer: 0,
            attemptsLeft: 3,
            isBlocked: false,
            timeLeft: 0,
            countdownInterval: null,

            // Register data
            registerData: {
                name: '',
                apellidos: '',
                email: '',
                dpi: '',
                fecha_nacimiento: '',
                telefono: '',
                password: '',
                foto: null
            },

            // Common data
            error: null,
            successMessage: null,
            loading: false
        };
    },
    mounted() {
        this.generateMathCaptcha();
        this.checkLoginStatus();
    },
    beforeUnmount() {
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }
    },
    methods: {
        // ===== NAVEGACIÓN =====
        showRegister() {
            this.currentView = 'register';
            this.clearMessages();
        },

        showLogin() {
            this.currentView = 'login';
            this.clearMessages();
        },

        clearMessages() {
            this.error = null;
            this.successMessage = null;
        },

        // ===== GESTIÓN DE ARCHIVOS =====
        handleFileUpload(event) {
            const file = event.target.files[0];
            if (file) {
                // Validar tamaño (2MB max)
                if (file.size > 2 * 1024 * 1024) {
                    this.error = 'La imagen no debe superar los 2MB';
                    event.target.value = '';
                    return;
                }

                // Validar tipo
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    this.error = 'Formato no válido. Use JPG, PNG o GIF';
                    event.target.value = '';
                    return;
                }

                this.registerData.foto = file;
                this.error = null;
            }
        },

        // ===== CSRF TOKEN =====
        async getCSRFToken() {
            try {
                const response = await fetch('/sanctum/csrf-cookie');
                return response.ok;
            } catch (error) {
                console.error('Error obteniendo CSRF token:', error);
                return false;
            }
        },

        // ===== MÉTODOS DE LOGIN =====
        async checkLoginStatus() {
            // Simular estado de intentos (en una app real esto vendría del servidor)
            const storedAttempts = localStorage.getItem('login_attempts');
            const lastAttempt = localStorage.getItem('last_failed_attempt');

            if (storedAttempts && lastAttempt) {
                const attempts = parseInt(storedAttempts);
                const timeDiff = Date.now() - parseInt(lastAttempt);
                const cooldownTime = 2 * 60 * 1000; // 2 minutos

                if (attempts >= 3 && timeDiff < cooldownTime) {
                    this.attemptsLeft = 0;
                    this.startCountdown(Math.ceil((cooldownTime - timeDiff) / 1000));
                } else if (timeDiff >= cooldownTime) {
                    // Reset attempts after cooldown
                    localStorage.removeItem('login_attempts');
                    localStorage.removeItem('last_failed_attempt');
                    this.attemptsLeft = 3;
                } else {
                    this.attemptsLeft = Math.max(0, 3 - attempts);
                }
            }
        },

        startCountdown(seconds) {
            this.timeLeft = seconds;
            this.isBlocked = true;

            if (this.countdownInterval) {
                clearInterval(this.countdownInterval);
            }

            this.countdownInterval = setInterval(() => {
                this.timeLeft--;

                if (this.timeLeft <= 0) {
                    clearInterval(this.countdownInterval);
                    this.isBlocked = false;
                    this.attemptsLeft = 3;
                    this.error = null;
                    localStorage.removeItem('login_attempts');
                    localStorage.removeItem('last_failed_attempt');
                    this.generateMathCaptcha();
                }
            }, 1000);
        },

        formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        },

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

            this.captchaAnswer = "";
        },

        validateCaptcha() {
            return parseInt(this.captchaAnswer) === this.correctAnswer;
        },
        async login() {
            if (this.isBlocked) {
                return;
            }

            // Validar CAPTCHA antes de enviar
            if (!this.validateCaptcha()) {
                this.error = 'Respuesta del CAPTCHA incorrecta.';
                this.generateMathCaptcha();
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                // Obtener CSRF token
                await this.getCSRFToken();

                const response = await fetch('/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        email: this.email,
                        password: this.password
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    // Login exitoso
                    localStorage.removeItem('login_attempts');
                    localStorage.removeItem('last_failed_attempt');

                    // Todos los usuarios van al mismo dashboard
                    // La diferenciación de roles se maneja en el dashboard
                    window.location.href = '/dashboard';
                } else {
                    // Login fallido
                    this.error = result.message || 'Credenciales incorrectas';

                    // Actualizar contador de intentos
                    const currentAttempts = parseInt(localStorage.getItem('login_attempts') || '0') + 1;
                    localStorage.setItem('login_attempts', currentAttempts.toString());
                    localStorage.setItem('last_failed_attempt', Date.now().toString());

                    this.attemptsLeft = Math.max(0, 3 - currentAttempts);

                    if (currentAttempts >= 3) {
                        this.startCountdown(120); // 2 minutos
                    }

                    this.generateMathCaptcha();
                }
            } catch (err) {
                console.error("Error de conexión:", err);
                this.error = "Error al intentar iniciar sesión. Intente nuevamente.";
                this.generateMathCaptcha();
            } finally {
                this.loading = false;
            }
        },

        // ===== REGISTRO =====
        async register() {
            this.loading = true;
            this.error = null;
            this.successMessage = null;

            try {
                // Obtener CSRF token
                await this.getCSRFToken();

                // Crear FormData para manejar archivos
                const formData = new FormData();
                formData.append('name', this.registerData.name);
                formData.append('apellidos', this.registerData.apellidos);
                formData.append('email', this.registerData.email);
                formData.append('dpi', this.registerData.dpi);
                formData.append('fecha_nacimiento', this.registerData.fecha_nacimiento);
                formData.append('telefono', this.registerData.telefono || '');
                formData.append('password', this.registerData.password);

                if (this.registerData.foto) {
                    formData.append('foto', this.registerData.foto);
                }

                const response = await fetch('/register', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    this.successMessage = 'Cuenta creada exitosamente. Redirigiendo...';

                    // Limpiar formulario
                    this.registerData = {
                        name: '',
                        apellidos: '',
                        email: '',
                        dpi: '',
                        fecha_nacimiento: '',
                        telefono: '',
                        password: '',
                        foto: null
                    };

                    // Redirigir después de 2 segundos
                    setTimeout(() => {
                        if (result.role === 'admin') {
                            window.location.href = '/admin/dashboard';
                        } else {
                            window.location.href = '/dashboard';
                        }
                    }, 2000);
                } else {
                    // Mostrar errores de validación
                    if (result.errors) {
                        const errorMessages = Object.values(result.errors).flat();
                        this.error = errorMessages.join('. ');
                    } else {
                        this.error = result.message || 'Error al crear la cuenta';
                    }
                }
            } catch (err) {
                console.error("Error al registrar:", err);
                this.error = "Error al crear la cuenta. Intente nuevamente.";
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>

<style scoped>
/* Reset global para eliminar márgenes y paddings por defecto */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

/* Asegurar que html y body ocupen toda la pantalla */
html, body {
    height: 100%;
    width: 100%;
    overflow-x: hidden;
}

/* Contenedor padre que asegura cobertura completa */
#app {
    height: 100%;
    width: 100%;
}

.container {
    background: linear-gradient(to right, #667eea, #764ba2);
    min-height: 100vh;
    min-width: 100vw;
    height: 100%;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    position: fixed;
    top: 0;
    left: 0;
    box-sizing: border-box;
}

.auth-wrapper {
    width: 450px;
    max-width: 100%;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.auth-card {
    width: 100%;
    padding: 30px;
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
    border-radius: 10px;
    position: relative;
    max-height: 90vh;
    overflow-y: auto;
}

.logo-container {
    text-align: center;
    margin-bottom: 15px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.text-primary {
    text-align: center;
    font-size: 2.5rem;
    font-weight: bold;
    color: #764ba2;
    margin: 0;
}

/* Formularios */
.register-form {
    max-height: 60vh;
    overflow-y: auto;
    padding-right: 10px;
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

.form-text {
    color: #666;
    font-size: 12px;
    margin-top: 5px;
    display: block;
}

/* Formulario deshabilitado */
.form-disabled {
    opacity: 0.6;
    pointer-events: none;
}

/* Alerta de bloqueo */
.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.blocked-message {
    text-align: center;
}

.block-icon {
    font-size: 2rem;
    display: block;
    margin-bottom: 10px;
}

.blocked-message h4 {
    margin: 10px 0;
    color: #721c24;
}

.countdown {
    margin-top: 15px;
    font-size: 16px;
}

.countdown strong {
    color: #dc3545;
    font-size: 18px;
}

/* Advertencia de intentos */
.attempts-warning {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
    padding: 10px;
    border-radius: 6px;
    text-align: center;
    margin-bottom: 15px;
    font-size: 14px;
}

.custom-input {
    width: 100%;
    padding: 12px;
    border: 2px solid #764ba2;
    border-radius: 8px;
    font-size: 16px;
    outline: none;
    transition: 0.3s;
}

.custom-input:focus:not(:disabled) {
    border-color: #667eea;
    box-shadow: 0 0 5px rgba(102, 126, 234, 0.5);
}

.custom-input:disabled {
    background-color: #f8f9fa;
    cursor: not-allowed;
}

.file-input {
    padding: 8px !important;
}

/* Estilos para CAPTCHA matemático */
.captcha-container {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border: 2px solid #764ba2;
}

.math-captcha {
    display: flex;
    align-items: center;
    gap: 10px;
    justify-content: center;
}

.captcha-question {
    font-size: 18px;
    font-weight: bold;
    color: #333;
    min-width: 100px;
    text-align: center;
}

.captcha-input {
    max-width: 100px;
    text-align: center;
    margin: 0;
}

.refresh-captcha {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.refresh-captcha:hover:not(:disabled) {
    background-color: rgba(118, 75, 162, 0.1);
}

.refresh-captcha:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.custom-btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    font-size: 16px;
    font-weight: bold;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
}

.custom-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #764ba2, #667eea);
    transform: scale(1.05);
}

.custom-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
    background: #cccccc !important;
}

/* Botón tipo enlace */
.link-button {
    background: none;
    border: none;
    color: #764ba2;
    font-size: 14px;
    cursor: pointer;
    text-decoration: underline;
    transition: color 0.3s;
    padding: 0;
}

.link-button:hover:not(:disabled) {
    color: #667eea;
}

.link-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.text-danger {
    color: #dc3545;
    font-size: 14px;
    text-align: center;
}

.text-success {
    color: #28a745;
    font-size: 14px;
    text-align: center;
}

.text-center {
    text-align: center;
    color: #333;
    margin-bottom: 20px;
}

.my-2 {
    margin-top: 15px;
    margin-bottom: 15px;
}

.mt-2 {
    margin-top: 10px;
}

.mt-3 {
    margin-top: 15px;
}

/* Transiciones */
.slide-enter-active, .slide-leave-active {
    transition: transform 0.6s ease-in-out, opacity 0.5s;
}
.slide-enter-from {
    transform: translateX(100%);
    opacity: 0;
}
.slide-leave-to {
    transform: translateX(-100%);
    opacity: 0;
}

/* Responsive */
@media (max-width: 480px) {
    .container {
        padding: 10px;
    }

    .auth-wrapper {
        width: 100%;
    }

    .auth-card {
        padding: 20px;
    }

    .text-primary {
        font-size: 2rem;
    }

    .form-row {
        flex-direction: column;
        gap: 0;
    }

    .math-captcha {
        flex-direction: column;
        gap: 10px;
    }

    .captcha-input {
        max-width: 150px;
    }

    .blocked-message h4 {
        font-size: 1.2rem;
    }

    .countdown {
        font-size: 14px;
    }
}
</style>
