<template>
    <div class="container">
        <div class="membership-wrapper">
            <transition name="slide" mode="out-in">
                <!-- VISTA DE PLANES -->
                <div v-if="currentView === 'plans'" key="plans" class="membership-card">
                    <div class="header-section">
                        <h1 class="text-primary">🚴‍♂️ Auroracicleta</h1>
                        <h2 class="section-title">Elige tu Plan de Membresía</h2>
                        <p class="subtitle">Selecciona el plan que mejor se adapte a tus necesidades</p>
                    </div>

                    <!-- Membresía Actual -->
                    <div v-if="currentMembership" class="current-membership">
                        <div class="membership-status">
                            <div class="status-badge" :class="'status-' + currentMembership.status">
                                {{ getStatusText(currentMembership.status) }}
                            </div>
                            <h3>{{ currentMembership.plan_info.name }}</h3>
                            <p>Expira: {{ formatDate(currentMembership.end_date) }}</p>
                            <p class="days-remaining">
                                {{ currentMembership.days_remaining }} días restantes
                            </p>
                        </div>
                        <button @click="showHistory" class="btn-secondary">
                            Ver Historial
                        </button>
                    </div>

                    <!-- Planes Disponibles -->
                    <div class="plans-grid">
                        <div
                            v-for="(plan, key) in plans"
                            :key="key"
                            class="plan-card"
                            :class="{ 'plan-popular': key === 'premium' }"
                            @click="selectPlan(key)"
                        >
                            <div v-if="key === 'premium'" class="popular-badge">
                                ⭐ Popular
                            </div>

                            <div class="plan-header" :style="{ backgroundColor: plan.color }">
                                <h3>{{ plan.name }}</h3>
                                <div class="price">
                                    <span class="currency">Q</span>
                                    <span class="amount">{{ plan.price }}</span>
                                    <span class="period">/mes</span>
                                </div>
                            </div>

                            <div class="plan-features">
                                <ul>
                                    <li v-for="feature in plan.features" :key="feature">
                                        ✅ {{ feature }}
                                    </li>
                                </ul>
                            </div>

                            <button class="btn-plan">
                                {{ currentMembership && currentMembership.plan_type === key ? 'Plan Actual' : 'Seleccionar' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- VISTA DE PAGO -->
                <div v-else-if="currentView === 'payment'" key="payment" class="membership-card payment-card">
                    <div class="payment-header">
                        <button @click="goBack" class="back-btn">← Volver</button>
                        <h2>Procesar Pago</h2>
                    </div>

                    <!-- Resumen del Plan -->
                    <div class="plan-summary">
                        <div class="summary-content">
                            <h3>{{ selectedPlan.name }}</h3>
                            <div class="summary-price">
                                <span class="total">Total: Q{{ selectedPlan.price }}</span>
                            </div>
                            <div class="summary-features">
                                <p><strong>Incluye:</strong></p>
                                <ul>
                                    <li v-for="feature in selectedPlan.features.slice(0, 3)" :key="feature">
                                        {{ feature }}
                                    </li>
                                    <li v-if="selectedPlan.features.length > 3">
                                        + {{ selectedPlan.features.length - 3 }} más...
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de Pago -->
                    <form @submit.prevent="processPayment" class="payment-form">
                        <!-- Método de Pago -->
                        <div class="form-group">
                            <label>Método de Pago</label>
                            <div class="payment-methods">
                                <label class="payment-method" :class="{ active: paymentData.payment_method === 'credit_card' }">
                                    <input type="radio" v-model="paymentData.payment_method" value="credit_card" />
                                    <div class="method-content">
                                        <span class="method-icon">💳</span>
                                        <span>Tarjeta de Crédito</span>
                                    </div>
                                </label>

                                <label class="payment-method" :class="{ active: paymentData.payment_method === 'debit_card' }">
                                    <input type="radio" v-model="paymentData.payment_method" value="debit_card" />
                                    <div class="method-content">
                                        <span class="method-icon">💳</span>
                                        <span>Tarjeta de Débito</span>
                                    </div>
                                </label>

                                <label class="payment-method" :class="{ active: paymentData.payment_method === 'paypal' }">
                                    <input type="radio" v-model="paymentData.payment_method" value="paypal" />
                                    <div class="method-content">
                                        <span class="method-icon">📧</span>
                                        <span>PayPal</span>
                                    </div>
                                </label>

                                <label class="payment-method" :class="{ active: paymentData.payment_method === 'bank_transfer' }">
                                    <input type="radio" v-model="paymentData.payment_method" value="bank_transfer" />
                                    <div class="method-content">
                                        <span class="method-icon">🏦</span>
                                        <span>Transferencia</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Datos de Tarjeta -->
                        <div v-if="['credit_card', 'debit_card'].includes(paymentData.payment_method)" class="card-section">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Número de Tarjeta</label>
                                    <input
                                        type="text"
                                        v-model="paymentData.card_number"
                                        class="form-control"
                                        placeholder="1234 5678 9012 3456"
                                        maxlength="16"
                                        @input="formatCardNumber"
                                        :disabled="loading"
                                        required
                                    />
                                    <small class="card-test-info">
                                        💡 Usa 4000000000000002 para simular rechazo
                                    </small>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Nombre en la Tarjeta</label>
                                    <input
                                        type="text"
                                        v-model="paymentData.card_name"
                                        class="form-control"
                                        placeholder="Juan Pérez"
                                        :disabled="loading"
                                        required
                                    />
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Fecha de Expiración</label>
                                    <input
                                        type="text"
                                        v-model="paymentData.card_expiry"
                                        class="form-control"
                                        placeholder="MM/AA"
                                        maxlength="5"
                                        @input="formatExpiry"
                                        :disabled="loading"
                                        required
                                    />
                                </div>
                                <div class="form-group">
                                    <label>CVV</label>
                                    <input
                                        type="text"
                                        v-model="paymentData.card_cvv"
                                        class="form-control"
                                        placeholder="123"
                                        maxlength="4"
                                        :disabled="loading"
                                        required
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- PayPal -->
                        <div v-else-if="paymentData.payment_method === 'paypal'" class="paypal-section">
                            <div class="form-group">
                                <label>Email de PayPal</label>
                                <input
                                    type="email"
                                    v-model="paymentData.paypal_email"
                                    class="form-control"
                                    placeholder="tu@paypal.com"
                                    :disabled="loading"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Transferencia Bancaria -->
                        <div v-else-if="paymentData.payment_method === 'bank_transfer'" class="bank-section">
                            <div class="form-group">
                                <label>Número de Cuenta</label>
                                <input
                                    type="text"
                                    v-model="paymentData.bank_account"
                                    class="form-control"
                                    placeholder="1234567890"
                                    :disabled="loading"
                                    required
                                />
                            </div>
                        </div>

                        <!-- Captcha de Seguridad -->
                        <div class="security-section">
                            <label>Verificación de Seguridad</label>
                            <div class="security-captcha">
                                <span class="captcha-question">{{ securityQuestion }}</span>
                                <input
                                    type="number"
                                    v-model="securityAnswer"
                                    class="form-control captcha-input"
                                    placeholder="Resultado"
                                    :disabled="loading"
                                    required
                                />
                                <button type="button" @click="generateSecurityQuestion" class="refresh-btn" :disabled="loading">
                                    🔄
                                </button>
                            </div>
                        </div>

                        <div v-if="error" class="error-message">
                            ❌ {{ error }}
                        </div>

                        <button type="submit" class="btn-pay" :disabled="loading || !isFormValid">
                            <span v-if="loading">Procesando...</span>
                            <span v-else>💳 Pagar Q{{ selectedPlan.price }}</span>
                        </button>
                    </form>
                </div>

                <!-- VISTA DE CONFIRMACIÓN -->
                <div v-else-if="currentView === 'success'" key="success" class="membership-card success-card">
                    <div class="success-content">
                        <div class="success-icon">🎉</div>
                        <h2>¡Pago Exitoso!</h2>
                        <p>Tu membresía ha sido activada correctamente</p>

                        <div class="success-details">
                            <div class="detail-item">
                                <strong>Plan:</strong> {{ paymentResult.plan_info.name }}
                            </div>
                            <div class="detail-item">
                                <strong>Monto:</strong> Q{{ paymentResult.amount }}
                            </div>
                            <div class="detail-item">
                                <strong>ID de Transacción:</strong> {{ paymentResult.transaction_id }}
                            </div>
                            <div class="detail-item">
                                <strong>Válido hasta:</strong> {{ formatDate(paymentResult.end_date) }}
                            </div>
                        </div>

                        <button @click="goToPlans" class="btn-continue">
                            Continuar
                        </button>
                    </div>
                </div>

                <!-- VISTA DE HISTORIAL -->
                <div v-else-if="currentView === 'history'" key="history" class="membership-card history-card">
                    <div class="history-header">
                        <button @click="goToPlans" class="back-btn">← Volver</button>
                        <h2>Historial de Membresías</h2>
                    </div>

                    <div v-if="membershipHistory.length === 0" class="empty-history">
                        <p>No hay historial de membresías</p>
                    </div>

                    <div v-else class="history-list">
                        <div v-for="membership in membershipHistory" :key="membership.id" class="history-item">
                            <div class="history-info">
                                <h4>{{ membership.plan_info.name }}</h4>
                                <p>Q{{ membership.amount }} - {{ formatDate(membership.created_at) }}</p>
                                <p class="transaction-id">ID: {{ membership.transaction_id }}</p>
                            </div>
                            <div class="history-status">
                                <span class="status-badge" :class="'status-' + membership.status">
                                    {{ getStatusText(membership.status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>

<script>
export default {
    name: 'MembershipPayment',
    data() {
        return {
            currentView: 'plans', // 'plans', 'payment', 'success', 'history'
            loading: false,
            error: null,

            // Datos de membresía
            currentMembership: null,
            membershipHistory: [],
            plans: {},
            selectedPlanKey: null,

            // Datos de pago
            paymentData: {
                payment_method: 'credit_card',
                card_number: '',
                card_name: '',
                card_expiry: '',
                card_cvv: '',
                paypal_email: '',
                bank_account: ''
            },

            // Resultado de pago
            paymentResult: null,

            // Seguridad
            securityQuestion: '',
            securityAnswer: '',
            correctSecurityAnswer: 0
        };
    },

    computed: {
        selectedPlan() {
            return this.selectedPlanKey ? this.plans[this.selectedPlanKey] : null;
        },

        isFormValid() {
            if (!this.validateSecurity()) return false;

            switch (this.paymentData.payment_method) {
                case 'credit_card':
                case 'debit_card':
                    return this.paymentData.card_number.length === 16 &&
                        this.paymentData.card_name.length > 0 &&
                        this.paymentData.card_expiry.length === 5 &&
                        this.paymentData.card_cvv.length >= 3;
                case 'paypal':
                    return this.paymentData.paypal_email.length > 0;
                case 'bank_transfer':
                    return this.paymentData.bank_account.length > 0;
                default:
                    return false;
            }
        }
    },

    mounted() {
        this.loadPlans();
        this.loadCurrentMembership();
        this.generateSecurityQuestion();
    },

    methods: {
        // ===== NAVEGACIÓN =====
        selectPlan(planKey) {
            if (this.currentMembership && this.currentMembership.plan_type === planKey) {
                return; // No permitir seleccionar el plan actual
            }

            this.selectedPlanKey = planKey;
            this.currentView = 'payment';
            this.error = null;
        },

        goBack() {
            this.currentView = 'plans';
            this.error = null;
        },

        goToPlans() {
            this.currentView = 'plans';
            this.loadCurrentMembership(); // Recargar membresía actual
            this.error = null;
        },

        showHistory() {
            this.currentView = 'history';
            this.loadMembershipHistory();
        },

        // ===== CARGA DE DATOS =====
        async loadPlans() {
            try {
                const response = await fetch('/memberships/plans', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                const result = await response.json();

                if (result.success) {
                    this.plans = result.plans;
                }
            } catch (error) {
                console.error('Error cargando planes:', error);
            }
        },

        async loadCurrentMembership() {
            try {
                const response = await fetch('/memberships/current', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                const result = await response.json();

                if (result.success) {
                    this.currentMembership = result.membership;
                } else {
                    this.currentMembership = null;
                }
            } catch (error) {
                console.error('Error cargando membresía actual:', error);
                this.currentMembership = null;
            }
        },

        async loadMembershipHistory() {
            try {
                this.loading = true;
                const response = await fetch('/memberships/history', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });
                const result = await response.json();

                if (result.success) {
                    this.membershipHistory = result.memberships;
                }
            } catch (error) {
                console.error('Error cargando historial:', error);
            } finally {
                this.loading = false;
            }
        },

        // ===== PROCESAMIENTO DE PAGO =====
        async processPayment() {
            if (!this.isFormValid) {
                this.error = 'Por favor complete todos los campos requeridos';
                return;
            }

            if (!this.validateSecurity()) {
                this.error = 'Respuesta de seguridad incorrecta';
                this.generateSecurityQuestion();
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const response = await fetch('/memberships/payment', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        plan_type: this.selectedPlanKey,
                        ...this.paymentData
                    })
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    this.paymentResult = result.membership;
                    this.currentView = 'success';
                    this.resetPaymentForm();
                } else {
                    this.error = result.message || 'Error al procesar el pago';
                }
            } catch (error) {
                console.error('Error procesando pago:', error);
                this.error = 'Error de conexión. Intente nuevamente';
            } finally {
                this.loading = false;
            }
        },

        // ===== VALIDACIONES =====
        validateSecurity() {
            return parseInt(this.securityAnswer) === this.correctSecurityAnswer;
        },

        async getCSRFToken() {
            try {
                const response = await fetch('/sanctum/csrf-cookie');
                return response.ok;
            } catch (error) {
                console.error('Error obteniendo CSRF token:', error);
                return false;
            }
        },

        // ===== FORMATEO DE CAMPOS =====
        formatCardNumber(event) {
            let value = event.target.value.replace(/\D/g, '');
            this.paymentData.card_number = value;
        },

        formatExpiry(event) {
            let value = event.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            this.paymentData.card_expiry = value;
        },

        // ===== SEGURIDAD =====
        generateSecurityQuestion() {
            const num1 = Math.floor(Math.random() * 20) + 1;
            const num2 = Math.floor(Math.random() * 20) + 1;
            const operations = ['+', '-', '*'];
            const operation = operations[Math.floor(Math.random() * operations.length)];

            this.securityQuestion = `${num1} ${operation} ${num2} = ?`;

            switch(operation) {
                case '+':
                    this.correctSecurityAnswer = num1 + num2;
                    break;
                case '-':
                    this.correctSecurityAnswer = num1 - num2;
                    break;
                case '*':
                    this.correctSecurityAnswer = num1 * num2;
                    break;
            }

            this.securityAnswer = '';
        },

        // ===== UTILIDADES =====
        resetPaymentForm() {
            this.paymentData = {
                payment_method: 'credit_card',
                card_number: '',
                card_name: '',
                card_expiry: '',
                card_cvv: '',
                paypal_email: '',
                bank_account: ''
            };
            this.securityAnswer = '';
            this.generateSecurityQuestion();
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-GT', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },

        getStatusText(status) {
            const statusTexts = {
                'pending': 'Pendiente',
                'active': 'Activa',
                'expired': 'Expirada',
                'cancelled': 'Cancelada'
            };
            return statusTexts[status] || status;
        }
    }
}
</script>

<style scoped>
/* Reset global */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html, body {
    height: 100%;
    width: 100%;
    overflow-x: hidden;
}

.container {
    background: linear-gradient(135deg, #667eea, #764ba2);
    min-height: 100vh;
    min-width: 100vw;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 20px;
    position: fixed;
    top: 0;
    left: 0;
    overflow-y: auto;
}

.membership-wrapper {
    width: 100%;
    max-width: 1200px;
    position: relative;
}

.membership-card {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    padding: 30px;
    backdrop-filter: blur(10px);
}

.payment-card {
    max-width: 600px;
    margin: 0 auto;
}

.success-card, .history-card {
    max-width: 700px;
    margin: 0 auto;
}

/* Header */
.header-section {
    text-align: center;
    margin-bottom: 30px;
}

.text-primary {
    font-size: 2.5rem;
    font-weight: bold;
    background: linear-gradient(135deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 10px;
}

.section-title {
    font-size: 1.8rem;
    color: #333;
    margin-bottom: 10px;
}

.subtitle {
    color: #666;
    font-size: 1.1rem;
}

/* Membresía Actual */
.current-membership {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.membership-status h3 {
    font-size: 1.3rem;
    margin-bottom: 5px;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.status-active {
    background-color: #28a745;
    color: white;
}

.status-pending {
    background-color: #ffc107;
    color: #000;
}

.status-expired {
    background-color: #dc3545;
    color: white;
}

.status-cancelled {
    background-color: #6c757d;
    color: white;
}

.days-remaining {
    font-weight: bold;
    font-size: 1.1rem;
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 2px solid white;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-secondary:hover {
    background: white;
    color: #28a745;
}

/* Planes Grid */
.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.plan-card {
    border: 3px solid transparent;
    border-radius: 15px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    position: relative;
}

.plan-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.plan-popular {
    border-color: #ffc107;
    transform: scale(1.05);
}

.popular-badge {
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    background: #ffc107;
    color: #000;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: bold;
    z-index: 10;
}

.plan-header {
    color: white;
    padding: 25px 20px;
    text-align: center;
}

.plan-header h3 {
    font-size: 1.4rem;
    margin-bottom: 15px;
}

.price {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 5px;
}

.currency {
    font-size: 1.2rem;
}

.amount {
    font-size: 3rem;
    font-weight: bold;
}

.period {
    font-size: 1rem;
    opacity: 0.9;
}

.plan-features {
    padding: 25px 20px;
}

.plan-features ul {
    list-style: none;
    space-y: 10px;
}

.plan-features li {
    padding: 8px 0;
    font-size: 0.95rem;
    color: #333;
}

.btn-plan {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    font-weight: bold;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-plan:hover {
    background: linear-gradient(135deg, #764ba2, #667eea);
}

/* Payment Form */
.payment-header, .history-header {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.back-btn {
    background: none;
    border: none;
    font-size: 1.1rem;
    color: #667eea;
    cursor: pointer;
    transition: color 0.3s;
}

.back-btn:hover {
    color: #764ba2;
}

.plan-summary {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
}

.summary-content h3 {
    color: #333;
    margin-bottom: 10px;
}

.summary-price {
    font-size: 1.3rem;
    font-weight: bold;
    color: #28a745;
    margin-bottom: 15px;
}

.summary-features {
    font-size: 0.9rem;
    color: #666;
}

.summary-features ul {
    list-style: none;
    margin-top: 5px;
}

.summary-features li {
    padding: 2px 0;
}

/* Payment Methods */
.payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.payment-method {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 15px 10px;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
    background: white;
}

.payment-method:hover {
    border-color: #667eea;
}

.payment-method.active {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
}

.payment-method input {
    display: none;
}

.method-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.method-icon {
    font-size: 1.5rem;
}

/* Form Elements */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 500;
}

.form-row {
    display: flex;
    gap: 15px;
}

.form-row .form-group {
    flex: 1;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
}

.form-control:disabled {
    background-color: #f8f9fa;
    cursor: not-allowed;
}

.card-test-info {
    display: block;
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 8px;
    padding: 8px;
    background: #f8f9fa;
    border-left: 3px solid #17a2b8;
    border-radius: 4px;
    line-height: 1.4;
}

/* Security Section */
.security-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.security-captcha {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-top: 10px;
}

.captcha-question {
    font-size: 1.2rem;
    font-weight: bold;
    color: #333;
    min-width: 120px;
}

.captcha-input {
    max-width: 100px;
    text-align: center;
}

.refresh-btn {
    background: none;
    border: 2px solid #667eea;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
}

.refresh-btn:hover:not(:disabled) {
    background: #667eea;
}

.refresh-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Buttons */
.btn-pay {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 10px;
}

.btn-pay:hover:not(:disabled) {
    background: linear-gradient(135deg, #20c997, #28a745);
    transform: translateY(-2px);
}

.btn-pay:disabled {
    background: #cccccc;
    cursor: not-allowed;
    transform: none;
}

.btn-continue {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-continue:hover {
    background: linear-gradient(135deg, #764ba2, #667eea);
    transform: translateY(-2px);
}

/* Success Page */
.success-content {
    text-align: center;
    padding: 20px;
}

.success-icon {
    font-size: 4rem;
    margin-bottom: 20px;
}

.success-content h2 {
    color: #28a745;
    margin-bottom: 15px;
    font-size: 2rem;
}

.success-content p {
    color: #666;
    font-size: 1.1rem;
    margin-bottom: 25px;
}

.success-details {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 25px;
    text-align: left;
}

.detail-item {
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
}

.detail-item:last-child {
    border-bottom: none;
}

/* History */
.empty-history {
    text-align: center;
    padding: 40px;
    color: #666;
}

.history-list {
    space-y: 15px;
}

.history-item {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.history-info h4 {
    color: #333;
    margin-bottom: 5px;
}

.history-info p {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 3px;
}

.transaction-id {
    font-family: monospace;
    font-size: 0.8rem !important;
    color: #999 !important;
}

/* Error Messages */
.error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 15px;
    border: 1px solid #f5c6cb;
}

/* Transitions */
.slide-enter-active, .slide-leave-active {
    transition: all 0.5s ease-in-out;
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
@media (max-width: 768px) {
    .container {
        padding: 10px;
    }

    .membership-card {
        padding: 20px;
    }

    .plans-grid {
        grid-template-columns: 1fr;
    }

    .payment-methods {
        grid-template-columns: repeat(2, 1fr);
    }

    .form-row {
        flex-direction: column;
        gap: 0;
    }

    .security-captcha {
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }

    .text-primary {
        font-size: 2rem;
    }

    .history-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .current-membership {
        flex-direction: column;
        gap: 15px;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .payment-methods {
        grid-template-columns: 1fr;
    }

    .method-content {
        flex-direction: row;
        justify-content: center;
    }
}
</style>
