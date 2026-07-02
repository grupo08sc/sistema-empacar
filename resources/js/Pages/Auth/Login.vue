<script setup lang="ts">
import { ref } from "vue";
import { Head, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthLayout from "../../Layouts/AuthLayout.vue";

const isLoading = ref(false);

const loginForm = useForm({
    email: "",
    password: ""
});

const handleLogin = () => {
    isLoading.value = true;
    loginForm.clearErrors();

    setTimeout(() => {
        loginForm.post(route('login'), {
            onFinish: () => {
                isLoading.value = false;
                loginForm.reset('password');
            },
        });
    }, 500);
};
const empacarLogo = '/img/empacar-brand.svg';
</script>

<template>
    <Head title="Iniciar sesión - EMPACAR S.A." />
    <AuthLayout>
        <div class="empacar-login d-flex justify-content-center align-items-center">
            <div class="login-shell row no-gutters">
                <div class="col-lg-6 login-brand-panel d-none d-lg-flex flex-column justify-content-between">
                    <div>
                        <img :src="empacarLogo" alt="EMPACAR S.A." class="login-logo mb-4">
                        <span class="login-kicker">Sistema comercial administrativo</span>
                        <h2 class="font-weight-bold mt-3 mb-3">Gestión de ventas, inventario y pagos para EMPACAR.</h2>
                        <p>
                            Prototipo alineado a una operación industrial sostenible: productos, divisiones, clientes, cuotas y pagos electrónicos.
                        </p>
                    </div>
                    <div class="login-pill-row">
                        <span>PET</span>
                        <span>Corrugado</span>
                        <span>Kraft</span>
                        <span>Reciclado</span>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card login-card p-4">
                        <div class="text-center mb-3">
                            <img :src="empacarLogo" alt="EMPACAR S.A." class="login-logo-mobile mb-2">
                            <h4 class="fw-bold mb-1">Iniciar sesión</h4>
                            <small class="text-muted">Acceso exclusivo para usuarios autorizados.</small>
                        </div>

                        <form @submit.prevent="handleLogin">
                            <div v-if="loginForm.errors.email" class="alert alert-danger py-2 text-center">
                                {{ loginForm.errors.email }}
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small">Correo electrónico</label>
                                <input
                                    type="email"
                                    class="form-control"
                                    v-model="loginForm.email"
                                    placeholder="admin@empacar.local"
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted small">Contraseña</label>
                                <input type="password" class="form-control" v-model="loginForm.password" required>
                                <div v-if="loginForm.errors.password" class="text-danger text-sm mt-1">
                                    {{ loginForm.errors.password }}
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 my-4" :disabled="isLoading">
                                <i class="fas fa-sign-in-alt mr-1"></i>
                                {{ isLoading ? 'Entrando...' : 'Entrar al sistema' }}
                            </button>

                            <div class="demo-access text-center">
                                <small>
                                    Bienvenido al Sistema Web de Gestión Comercial de EMPACAR S.A.
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>

<style>
:root {
    --empacar-green: #006B3F;
    --empacar-light-green: #00A859;
    --empacar-blue: #006BA6;
    --empacar-dark: #063B2B;
    --empacar-soft: #F3FAF5;
}

body {
    font-family: 'Montserrat', 'Open Sans', Arial, sans-serif;
    color: var(--empacar-dark);
    overflow-x: hidden;
}

.empacar-login {
    min-height: 100vh;
    padding: 2rem;
    background:
        radial-gradient(circle at 20% 20%, rgba(141,198,63,.25), transparent 32%),
        radial-gradient(circle at 80% 15%, rgba(0,107,166,.20), transparent 28%),
        linear-gradient(135deg, #F3FAF5 0%, #FFFFFF 60%, #EAF6EF 100%);
}

.login-shell {
    width: min(980px, 100%);
    border-radius: 30px;
    overflow: hidden;
    background: #FFFFFF;
    box-shadow: 0 28px 70px rgba(6,59,43,.18);
    border: 1px solid #DDEFE4;
}

.login-brand-panel {
    background:
        linear-gradient(145deg, rgba(0,107,63,.94), rgba(0,107,166,.88)),
        repeating-linear-gradient(45deg, rgba(255,255,255,.06) 0 12px, transparent 12px 24px);
    color: #FFFFFF;
    padding: 2.2rem;
    min-height: 560px;
}

.login-logo {
    width: 210px;
    background: #FFFFFF;
    border-radius: 18px;
    padding: .55rem;
}

.login-logo-mobile {
    width: 170px;
}

.login-kicker {
    display: inline-flex;
    border: 1px solid rgba(255,255,255,.45);
    border-radius: 999px;
    padding: .35rem .75rem;
    font-weight: 800;
    text-transform: uppercase;
    font-size: .75rem;
    letter-spacing: .05rem;
}

.login-brand-panel p {
    color: rgba(255,255,255,.86);
    font-size: 1.02rem;
}

.login-pill-row {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
}

.login-pill-row span {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 999px;
    padding: .42rem .8rem;
    font-weight: 700;
}

.login-card {
    border: 0;
    box-shadow: none;
    min-height: 560px;
    display: flex;
    justify-content: center;
    color: var(--empacar-dark);
}

.form-control {
    border-radius: 14px;
    border-color: #CFE5D7;
    min-height: 46px;
}

.form-control:focus {
    border-color: var(--empacar-light-green);
    box-shadow: 0 0 0 .2rem rgba(0,168,89,.12);
}

.btn-primary {
    background: linear-gradient(135deg, var(--empacar-green), var(--empacar-light-green));
    border: 0;
    border-radius: 999px;
    padding: 12px 30px;
    transition: all 0.2s;
    font-weight: 800;
}

.btn-primary:hover {
    transform: translateY(-1px);
    filter: brightness(.96);
}

.demo-access {
    background: var(--empacar-soft);
    border-radius: 16px;
    padding: .75rem;
    color: #4A665A;
}
</style>
