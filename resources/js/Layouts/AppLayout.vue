<script setup>
import { Link, useForm, usePage, router } from "@inertiajs/vue3";
import { computed, nextTick, onMounted, ref, watch } from "vue";
import ControlSidebar from "../Components/ControlSidebar.vue";

const page = usePage();
const user = computed(() => page.props.auth.user);
const num = computed(() => page.props.num || 0);
const contadorModulo = computed(
    () => page.props.contadorModulo || "página actual",
);
const menu = computed(() => page.props.menu || []);
const empresa = computed(() => page.props.empresa || {});
const fallbackLogo = computed(
    () => `${page.props.assetUrl}/img/empacar-brand.svg`,
);
const logoSrc = computed(() => empresa.value?.logo_url || fallbackLogo.value);
const nombreSistema = computed(() => empresa.value?.nombre || "EMPACAR S.A.");
const homeRoute = computed(() => page.props.homeRoute || "dashboard");
const puedeBuscarGlobal = computed(() => {
    const privilegios = page.props.auth?.privilegios || {};
    return (
        user.value?.rol?.nombre === "Administrador" ||
        Boolean(privilegios.Reportes?.leer)
    );
});

const flashSuccess = computed(() => page.props.flash?.success);
const errors = computed(() => page.props.errors);
const hasErrors = computed(() => Object.keys(page.props.errors).length > 0);
const userEstilo = computed(() => (user.value ? user.value.estilo : null));

const fontScale = ref(1);
const altoContraste = ref(false);

const form = useForm({
    buscar: "",
});

const temaAutomatico = () => {
    const hora = new Date().getHours();
    return hora >= 7 && hora < 19 ? 4 : 5;
};

const reglasTema = (estilo) => {
    const tema = estilo == 6 ? temaAutomatico() : Number(estilo || 6);

    const temas = {
        1: `
      .main-sidebar,.sidebar{background:#F7B267!important;}
      .brand-link,.main-header{background:#F79D65!important;color:#202124!important;}
      .nav-item p,.nav-sidebar .nav-link,.main-header a{color:#202124!important;}
      .content-wrapper{background:#FFF7E6!important;}
      .card,.small-box{border-radius:18px!important;box-shadow:0 8px 20px rgba(0,0,0,.08)!important;}
      .card-header{background:#7BDFF2!important;color:#17202A!important;}
      .btn-primary{background:#00A6A6!important;border-color:#00A6A6!important;}
    `,
        2: `
      .main-sidebar,.sidebar{background:#073B2A!important;}
      .brand-link{background:#FFFFFF!important;color:#006B3F!important;border-right:1px solid #D9E9DF!important;}
      .main-header{background:#FFFFFF!important;color:#073B2A!important;border-bottom:1px solid #D9E9DF!important;}
      .main-header a{color:#073B2A!important;}
      .nav-item p,.nav-sidebar .nav-link{color:#ECFDF5!important;}
      .nav-sidebar .nav-link:hover{background:#00A859!important;color:#FFFFFF!important;}
      .content-wrapper{background:#F3FAF5!important;}
      .card{border-radius:18px!important;border:1px solid #D9E9DF!important;box-shadow:0 12px 26px rgba(0,107,63,.10)!important;}
      .card-header{background:#006B3F!important;color:#FFFFFF!important;border-radius:18px 18px 0 0!important;}
      .btn-primary{background:#00A859!important;border-color:#00A859!important;border-radius:999px!important;}
    `,
        3: `
      .main-sidebar,.sidebar{background:#374151!important;}
      .brand-link,.main-header{background:#FFFFFF!important;color:#374151!important;border-bottom:1px solid #E5E7EB!important;}
      .main-header a{color:#374151!important;}
      .nav-item p,.nav-sidebar .nav-link{color:#F3F4F6!important;}
      .content-wrapper{background:#FAFAFA!important;}
      .card{border-radius:8px!important;border:1px solid #E5E7EB!important;box-shadow:none!important;}
      .card-header{background:#F9FAFB!important;color:#111827!important;}
      .btn-primary{background:#374151!important;border-color:#374151!important;}
    `,
        4: `
      .main-sidebar,.sidebar{background:#063B2B!important;}
      .brand-link{background:#FFFFFF!important;color:#006B3F!important;border-right:1px solid #E5EFE8!important;}
      .main-header{background:#FFFFFF!important;color:#063B2B!important;border-bottom:1px solid #E5EFE8!important;}
      .main-header a{color:#063B2B!important;}
      .nav-item p,.nav-sidebar .nav-link{color:#F0FDF4!important;}
      .nav-sidebar .nav-link:hover{background:#00A859!important;color:#FFFFFF!important;}
      .content-wrapper{background:#F7FBF8!important;}
      .card{background:#FFFFFF!important;color:#102A1F!important;border:1px solid #E1EEE5!important;border-radius:16px!important;box-shadow:0 10px 24px rgba(6,59,43,.08)!important;}
      .card-body,.table{color:#102A1F!important;}
      .btn-primary{background:#006B3F!important;border-color:#006B3F!important;border-radius:999px!important;}
    `,
        5: `
      .main-sidebar,.sidebar{background:#021F18!important;}
      .brand-link,.main-header{background:#031B16!important;color:#F0FDF4!important;border-color:#123C31!important;}
      .main-header a,.nav-item p,.nav-sidebar .nav-link{color:#F0FDF4!important;}
      .nav-sidebar .nav-link:hover{background:#006B3F!important;color:#FFFFFF!important;}
      .content-wrapper{background:#071B16!important;}
      .card,.modal-content{background:#0B2A21!important;color:#F0FDF4!important;border-color:#145943!important;border-radius:16px!important;box-shadow:0 12px 26px rgba(0,0,0,.25)!important;}
      .card-body,.table{color:#F0FDF4!important;}
      .form-control{background:#05241C!important;color:#F0FDF4!important;border-color:#1E6E52!important;}
      .btn-primary{background:#00A859!important;border-color:#00A859!important;border-radius:999px!important;}
    `,
    };

    const reglasDropdown = {
        1: `
      .main-header .dropdown-menu{background:#FFF7E6!important;border:1px solid #F7B267!important;box-shadow:0 12px 30px rgba(0,0,0,.18)!important;opacity:1!important;}
      .main-header .dropdown-menu .dropdown-item,.main-header .dropdown-menu button.dropdown-item{color:#202124!important;background:transparent!important;}
      .main-header .dropdown-menu .dropdown-item:hover,.main-header .dropdown-menu .dropdown-item:focus,.main-header .dropdown-menu .dropdown-item.active,.main-header .dropdown-menu .dropdown-item:active{background:#F7B267!important;color:#202124!important;}
      .main-header .dropdown-menu .dropdown-divider{border-top-color:#F79D65!important;}
    `,
        2: `
      .main-header .dropdown-menu{background:#FFFFFF!important;border:1px solid #B7DEC6!important;box-shadow:0 14px 35px rgba(0,107,63,.20)!important;opacity:1!important;}
      .main-header .dropdown-menu .dropdown-item,.main-header .dropdown-menu button.dropdown-item{color:#063B2B!important;background:transparent!important;}
      .main-header .dropdown-menu .dropdown-item:hover,.main-header .dropdown-menu .dropdown-item:focus,.main-header .dropdown-menu .dropdown-item.active,.main-header .dropdown-menu .dropdown-item:active{background:#00A859!important;color:#FFFFFF!important;}
      .main-header .dropdown-menu .dropdown-divider{border-top-color:#D9E9DF!important;}
    `,
        3: `
      .main-header .dropdown-menu{background:#FFFFFF!important;border:1px solid #D1D5DB!important;box-shadow:0 12px 30px rgba(0,0,0,.15)!important;opacity:1!important;}
      .main-header .dropdown-menu .dropdown-item,.main-header .dropdown-menu button.dropdown-item{color:#374151!important;background:transparent!important;}
      .main-header .dropdown-menu .dropdown-item:hover,.main-header .dropdown-menu .dropdown-item:focus,.main-header .dropdown-menu .dropdown-item.active,.main-header .dropdown-menu .dropdown-item:active{background:#E5E7EB!important;color:#111827!important;}
      .main-header .dropdown-menu .dropdown-divider{border-top-color:#E5E7EB!important;}
    `,
        4: `
      .main-header .dropdown-menu{background:#FFFFFF!important;border:1px solid #B7DEC6!important;box-shadow:0 14px 35px rgba(0,107,63,.18)!important;opacity:1!important;}
      .main-header .dropdown-menu .dropdown-item,.main-header .dropdown-menu button.dropdown-item{color:#063B2B!important;background:transparent!important;}
      .main-header .dropdown-menu .dropdown-item:hover,.main-header .dropdown-menu .dropdown-item:focus,.main-header .dropdown-menu .dropdown-item.active,.main-header .dropdown-menu .dropdown-item:active{background:#006B3F!important;color:#FFFFFF!important;}
      .main-header .dropdown-menu .dropdown-divider{border-top-color:#D9E9DF!important;}
    `,
        5: `
      .main-header .dropdown-menu{background:#031B16!important;border:1px solid #1E6E52!important;box-shadow:0 14px 35px rgba(0,0,0,.50)!important;opacity:1!important;}
      .main-header .dropdown-menu .dropdown-item,.main-header .dropdown-menu button.dropdown-item{color:#F0FDF4!important;background:transparent!important;}
      .main-header .dropdown-menu .dropdown-item:hover,.main-header .dropdown-menu .dropdown-item:focus,.main-header .dropdown-menu .dropdown-item.active,.main-header .dropdown-menu .dropdown-item:active{background:#00A859!important;color:#FFFFFF!important;}
      .main-header .dropdown-menu .dropdown-divider{border-top-color:#1E6E52!important;}
    `,
    };

    return `${temas[tema] || temas[4]} ${reglasDropdown[tema] || reglasDropdown[4]}`;
};

const cambiarFondo = (estilo) => {
    if (typeof document === "undefined") return;

    const tema = estilo || 6;
    document.body.className = `tema-${tema} hold-transition sidebar-mini layout-fixed`;

    let hoja = document.getElementById("app-theme-overrides");
    if (!hoja) {
        hoja = document.createElement("style");
        hoja.id = "app-theme-overrides";
        document.head.appendChild(hoja);
    }

    hoja.innerHTML = reglasTema(tema);
};

const aplicarAccesibilidad = () => {
    if (typeof document === "undefined") return;
    document.documentElement.style.fontSize = `${Math.round(fontScale.value * 100)}%`;
    document.body.classList.toggle("alto-contraste", altoContraste.value);
    localStorage.setItem("app_font_scale", String(fontScale.value));
    localStorage.setItem("app_alto_contraste", altoContraste.value ? "1" : "0");
};

const aumentarFuente = () => {
    fontScale.value = Math.min(1.3, Number((fontScale.value + 0.1).toFixed(1)));
    aplicarAccesibilidad();
};

const disminuirFuente = () => {
    fontScale.value = Math.max(0.9, Number((fontScale.value - 0.1).toFixed(1)));
    aplicarAccesibilidad();
};

const alternarContraste = () => {
    altoContraste.value = !altoContraste.value;
    aplicarAccesibilidad();
};

const realizarBusqueda = () => {
    form.buscar = String(form.buscar || "").trim();
    if (form.buscar.length < 2) {
        return;
    }

    form.get(route("reportes.buscar"), {
        preserveState: true,
        replace: true,
    });
};

const onLogoError = (event) => {
    event.target.src = fallbackLogo.value;
};

const logout = () => {
    router.post(route("logout"));
};

watch(
    userEstilo,
    (nuevoEstilo) => {
        cambiarFondo(nuevoEstilo || 6);
    },
    { immediate: true },
);

onMounted(() => {
    fontScale.value = Number(localStorage.getItem("app_font_scale") || 1);
    altoContraste.value = localStorage.getItem("app_alto_contraste") === "1";
    aplicarAccesibilidad();
    cambiarFondo(userEstilo.value || 6);

    nextTick(() => {
        if (window.$) {
            const treeview = window.$('[data-widget="treeview"]');
            treeview.Treeview("init");
            window.$('[data-widget="pushmenu"]').PushMenu("init");
        }
    });
});
</script>

<template>
    <div class="wrapper">
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a
                        class="nav-link"
                        data-widget="pushmenu"
                        href="#"
                        role="button"
                        aria-label="Abrir menú"
                    >
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <Link :href="route(homeRoute)" class="nav-link"
                        >Panel principal</Link
                    >
                </li>
            </ul>

            <ul class="navbar-nav ml-auto">
                <li v-if="puedeBuscarGlobal" class="nav-item">
                    <a
                        class="nav-link"
                        data-widget="navbar-search"
                        href="#"
                        role="button"
                        aria-label="Buscar información del negocio"
                    >
                        <i class="fas fa-search"></i>
                    </a>
                    <div class="navbar-search-block">
                        <form
                            class="form-inline"
                            @submit.prevent="realizarBusqueda"
                        >
                            <input
                                type="hidden"
                                name="_token"
                                :value="page.props.csrf_token"
                            />
                            <div class="input-group input-group-sm">
                                <input
                                    class="form-control form-control-navbar"
                                    type="search"
                                    name="buscar"
                                    placeholder="Buscar productos, clientes, ventas o pagos EMPACAR..."
                                    v-model="form.buscar"
                                    aria-label="Buscar información del negocio"
                                    maxlength="60"
                                    :disabled="form.processing"
                                />
                                <div class="input-group-append">
                                    <button
                                        class="btn btn-navbar"
                                        type="submit"
                                        title="Buscar"
                                    >
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <button
                                        class="btn btn-navbar"
                                        type="button"
                                        data-widget="navbar-search"
                                        title="Cerrar búsqueda"
                                    >
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </li>

                <li class="nav-item dropdown">
                    <a
                        class="nav-link dropdown-toggle"
                        data-toggle="dropdown"
                        href="#"
                    >
                        Temas
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <Link
                            :href="route('cargarEstilo', { estilo: '1' })"
                            class="dropdown-item"
                            >Tema Niños</Link
                        >
                        <Link
                            :href="route('cargarEstilo', { estilo: '2' })"
                            class="dropdown-item"
                            >Tema Jóvenes</Link
                        >
                        <Link
                            :href="route('cargarEstilo', { estilo: '3' })"
                            class="dropdown-item"
                            >Tema Adultos</Link
                        >
                        <div class="dropdown-divider"></div>
                        <Link
                            :href="route('cargarEstilo', { estilo: '4' })"
                            class="dropdown-item"
                            >Modo Día</Link
                        >
                        <Link
                            :href="route('cargarEstilo', { estilo: '5' })"
                            class="dropdown-item"
                            >Modo Noche</Link
                        >
                        <Link
                            :href="route('cargarEstilo', { estilo: '6' })"
                            class="dropdown-item"
                            >Automático Día/Noche</Link
                        >
                    </div>
                </li>

                <li class="nav-item dropdown">
                    <a
                        class="nav-link dropdown-toggle"
                        data-toggle="dropdown"
                        href="#"
                    >
                        Accesibilidad
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <button
                            class="dropdown-item"
                            type="button"
                            @click="aumentarFuente"
                        >
                            Aumentar letra A+
                        </button>
                        <button
                            class="dropdown-item"
                            type="button"
                            @click="disminuirFuente"
                        >
                            Disminuir letra A-
                        </button>
                        <button
                            class="dropdown-item"
                            type="button"
                            @click="alternarContraste"
                        >
                            {{
                                altoContraste
                                    ? "Contraste normal"
                                    : "Alto contraste"
                            }}
                        </button>
                    </div>
                </li>

                <li class="nav-item dropdown">
                    <a
                        class="nav-link dropdown-toggle"
                        data-toggle="dropdown"
                        href="#"
                    >
                        {{ user ? user.email : "Invitado" }}
                    </a>
                    <div
                        class="dropdown-menu dropdown-menu-lg dropdown-menu-right"
                    >
                        <Link
                            :href="route('profile.edit')"
                            class="dropdown-item"
                            >Perfil</Link
                        >
                        <div class="dropdown-divider"></div>
                        <button @click="logout" class="dropdown-item">
                            Cerrar sesión
                        </button>
                    </div>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link"
                        data-widget="fullscreen"
                        href="#"
                        role="button"
                        aria-label="Pantalla completa"
                    >
                        <i class="fas fa-expand-arrows-alt"></i>
                    </a>
                </li>
            </ul>
        </nav>

        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <Link
                :href="route(homeRoute)"
                class="brand-link d-flex align-items-center"
                style="text-decoration: none"
            >
                <img
                    :src="logoSrc"
                    alt="Logo de EMPACAR S.A."
                    class="brand-image elevation-1 logo-empresa"
                    @error="onLogoError"
                />
                <span class="brand-text font-weight-bold text-truncate">{{
                    nombreSistema
                }}</span>
            </Link>

            <div class="sidebar">
                <nav class="mt-2">
                    <ul
                        class="nav nav-pills nav-sidebar flex-column"
                        data-widget="treeview"
                        role="menu"
                        data-accordion="false"
                    >
                        <li
                            v-for="grupo in menu"
                            :key="grupo.label"
                            class="nav-item"
                        >
                            <a href="#" class="nav-link">
                                <i :class="['nav-icon', grupo.icon]"></i>
                                <p>
                                    {{ grupo.label }}
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li
                                    v-for="item in grupo.items"
                                    :key="`${grupo.label}-${item.route}`"
                                    class="nav-item"
                                >
                                    <Link
                                        :href="route(item.route)"
                                        class="nav-link"
                                    >
                                        <i :class="['nav-icon', item.icon]"></i>
                                        <p>{{ item.label }}</p>
                                    </Link>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <div class="content-wrapper">
            <br />
            <section class="content">
                <div class="container-fluid">
                    <div
                        v-if="flashSuccess"
                        class="alert alert-success alert-dismissible fade show"
                        role="alert"
                    >
                        <i class="icon fas fa-check"></i> {{ flashSuccess }}
                        <button
                            type="button"
                            class="close"
                            data-dismiss="alert"
                            aria-label="Cerrar"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div
                        v-if="hasErrors"
                        class="alert alert-danger alert-dismissible fade show"
                        role="alert"
                    >
                        <h5><i class="icon fas fa-ban"></i> ¡Atención!</h5>
                        <ul class="mb-0 pl-3">
                            <li v-for="(error, key) in errors" :key="key">
                                {{ error }}
                            </li>
                        </ul>
                        <button
                            type="button"
                            class="close"
                            data-dismiss="alert"
                            aria-label="Cerrar"
                        >
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div v-if="form.processing">
                        <div class="loading-content">
                            <i
                                class="fas fa-circle-notch fa-spin fa-3x text-primary"
                            ></i>
                            <h4 class="text-primary mt-3 font-weight-light">
                                Buscando...
                            </h4>
                        </div>
                    </div>

                    <div class="app">
                        <slot />
                    </div>
                </div>
            </section>
        </div>

        <footer class="main-footer">
            <strong>&copy; 2026 EMPACAR S.A.</strong>
            <div class="float-right d-none d-sm-inline-block">
                Visitas de {{ contadorModulo }}: <strong>{{ num }}</strong>
            </div>
        </footer>

        <ControlSidebar />
    </div>
</template>

<style scoped>
.loading-content {
    text-align: center;
}

.logo-empresa {
    width: 46px !important;
    height: 36px !important;
    object-fit: contain;
    background: #ffffff;
    padding: 3px;
    border-radius: 10px;
}

.brand-text {
    max-width: 185px;
    color: #006b3f !important;
    letter-spacing: 0.2px;
}
</style>

<style>
:root {
    --empacar-green: #006b3f;
    --empacar-green-light: #00a859;
    --empacar-blue: #006ba6;
    --empacar-lime: #8dc63f;
    --empacar-dark: #063b2b;
    --empacar-soft: #f3faf5;
}

body {
    font-family: "Montserrat", "Open Sans", Arial, sans-serif;
}

.main-header {
    box-shadow: 0 4px 18px rgba(0, 107, 63, 0.08) !important;
}

.main-sidebar {
    box-shadow: 8px 0 26px rgba(6, 59, 43, 0.18) !important;
}

.brand-link {
    min-height: 62px;
    display: flex !important;
    align-items: center !important;
    gap: 0.45rem;
}

.nav-sidebar > .nav-item > .nav-link {
    border-radius: 14px !important;
    margin: 2px 10px !important;
    transition: all 0.2s ease-in-out;
}

.nav-sidebar .nav-treeview .nav-link {
    border-radius: 12px !important;
    margin-left: 16px !important;
}

.nav-sidebar .nav-link:hover {
    transform: translateX(2px);
}

.small-box {
    border-radius: 20px !important;
    overflow: hidden;
    box-shadow: 0 12px 30px rgba(6, 59, 43, 0.12) !important;
}

.small-box .inner p {
    font-weight: 700;
    letter-spacing: 0.2px;
}

.small-box.bg-info {
    background: linear-gradient(
        135deg,
        var(--empacar-blue),
        #02a9b5
    ) !important;
}

.small-box.bg-success {
    background: linear-gradient(
        135deg,
        var(--empacar-green),
        var(--empacar-green-light)
    ) !important;
}

.small-box.bg-warning {
    background: linear-gradient(135deg, #f2b705, #f4d35e) !important;
    color: #153528 !important;
}

.small-box.bg-danger {
    background: linear-gradient(
        135deg,
        #0b7352,
        var(--empacar-lime)
    ) !important;
}

.card {
    border-radius: 18px !important;
}

.btn-primary {
    background: linear-gradient(
        135deg,
        var(--empacar-green),
        var(--empacar-green-light)
    ) !important;
    border-color: var(--empacar-green) !important;
}

.btn-primary:hover {
    filter: brightness(0.95);
    transform: translateY(-1px);
}

.main-footer {
    border-top: 1px solid #d9e9df !important;
    color: #49695b;
}

body.alto-contraste,
body.alto-contraste .content-wrapper,
body.alto-contraste .card,
body.alto-contraste .modal-content,
body.alto-contraste .main-header,
body.alto-contraste .main-sidebar,
body.alto-contraste .sidebar {
    background: #000000 !important;
    color: #ffffff !important;
}

body.alto-contraste a,
body.alto-contraste .nav-link,
body.alto-contraste .nav-item p,
body.alto-contraste .table,
body.alto-contraste .card-body,
body.alto-contraste label,
body.alto-contraste h1,
body.alto-contraste h2,
body.alto-contraste h3,
body.alto-contraste h4,
body.alto-contraste h5,
body.alto-contraste h6 {
    color: #ffffff !important;
}

body.alto-contraste .btn,
body.alto-contraste .badge {
    border: 2px solid #ffffff !important;
}

.main-header .dropdown-menu {
    opacity: 1 !important;
    background-clip: padding-box !important;
}

.main-header .dropdown-menu .dropdown-item,
.main-header .dropdown-menu button.dropdown-item {
    font-weight: 500;
}

body.alto-contraste .main-header .dropdown-menu {
    background: #000000 !important;
    border: 2px solid #ffffff !important;
}

body.alto-contraste .main-header .dropdown-menu .dropdown-item,
body.alto-contraste .main-header .dropdown-menu button.dropdown-item {
    color: #ffffff !important;
    background: #000000 !important;
}

body.alto-contraste .main-header .dropdown-menu .dropdown-item:hover,
body.alto-contraste .main-header .dropdown-menu .dropdown-item:focus,
body.alto-contraste .main-header .dropdown-menu .dropdown-item:active {
    color: #000000 !important;
    background: #ffff00 !important;
}

.logo-empresa {
    width: 46px !important;
    height: 36px !important;
    object-fit: contain;
    background: #ffffff;
    padding: 3px;
    border-radius: 10px;
}

.brand-text {
    max-width: 185px;
    color: #006b3f !important;
    letter-spacing: 0.2px;
}
</style>
