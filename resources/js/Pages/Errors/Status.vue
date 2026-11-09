<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted } from "vue";

const props = defineProps({
    status: { type: Number, required: true },
    isAuthenticated: { type: Boolean, default: false },
    canAccessDashboard: { type: Boolean, default: false },
});

const content = computed(() => {
    const pages = {
        403: {
            eyebrow: "Akses dibatasi",
            title: "Anda tidak memiliki akses",
            description:
                "Akun Anda aktif, tetapi belum memiliki izin untuk membuka halaman ini. Hubungi Super Admin jika akses tersebut diperlukan.",
            action: props.canAccessDashboard
                ? "Kembali ke dashboard"
                : "Kembali ke beranda",
            icon: "lock",
        },
        404: {
            eyebrow: "Halaman tidak ditemukan",
            title: "Halaman yang dicari tidak tersedia",
            description:
                "Alamat mungkin salah, data telah dipindahkan, atau halaman sudah tidak tersedia.",
            action: props.canAccessDashboard
                ? "Kembali ke dashboard"
                : "Kembali ke beranda",
            icon: "search",
        },
        419: {
            eyebrow: "Sesi berakhir",
            title: "Silakan masuk kembali",
            description:
                "Sesi Anda telah berakhir untuk menjaga keamanan akun. Masuk kembali sebelum melanjutkan pekerjaan.",
            action: "Masuk kembali",
            icon: "clock",
        },
        429: {
            eyebrow: "Permintaan dibatasi",
            title: "Terlalu banyak percobaan",
            description:
                "Sistem menerima terlalu banyak permintaan dalam waktu singkat. Tunggu sebentar, kemudian coba kembali.",
            action: "Coba kembali",
            icon: "pause",
        },
        500: {
            eyebrow: "Gangguan sistem",
            title: "Terjadi kendala saat memproses halaman",
            description:
                "Data Anda tetap aman. Silakan coba kembali atau hubungi pengelola sistem apabila kendala terus terjadi.",
            action: "Coba kembali",
            icon: "alert",
        },
        503: {
            eyebrow: "Pemeliharaan sistem",
            title: "Kami sedang melakukan pembaruan",
            description:
                "Website sementara tidak dapat digunakan. Silakan kembali beberapa saat lagi.",
            action: "Periksa kembali",
            icon: "tools",
        },
    };

    return pages[props.status] ?? pages[500];
});

const primaryHref = computed(() => {
    if (props.status === 419) return "/login";
    if (props.status === 403) {
        return props.canAccessDashboard ? "/dashboard" : "/";
    }
    if (props.status === 404) {
        return props.canAccessDashboard ? "/dashboard" : "/";
    }

    return null;
});

const retry = () => window.location.reload();
const goBack = () => {
    if (window.history.length > 1) window.history.back();
    else window.location.assign(props.canAccessDashboard ? "/dashboard" : "/");
};

onMounted(() => document.body.classList.add("status-page-body"));
onBeforeUnmount(() => document.body.classList.remove("status-page-body"));
</script>

<template>
    <Head :title="`${status} | COSMIC`" />

    <main class="status-page">
        <div class="status-grid" aria-hidden="true"></div>
        <header class="status-header">
            <Link class="brand" href="/">
                <img src="/Assets/Logo/Logo.jpeg" alt="" />
                <span>COSMIC<b>.</b></span>
            </Link>
            <span class="system-label">Cosmic Control Center</span>
        </header>

        <section class="status-content" aria-labelledby="status-title">
            <div class="status-code" aria-hidden="true">{{ status }}</div>
            <div :class="['status-symbol', `is-${content.icon}`]" aria-hidden="true">
                <svg viewBox="0 0 64 64" fill="none" stroke="currentColor">
                    <path v-if="content.icon === 'lock'" d="M19 29v-8a13 13 0 0 1 26 0v8M15 29h34v27H15zM32 39v8" />
                    <path v-else-if="content.icon === 'search'" d="m44 44 13 13M29 49a20 20 0 1 1 0-40 20 20 0 0 1 0 40Z" />
                    <path v-else-if="content.icon === 'clock'" d="M32 8a24 24 0 1 1-17 7M32 18v16l11 7M7 8v12h12" />
                    <path v-else-if="content.icon === 'pause'" d="M21 15v34M43 15v34" />
                    <path v-else-if="content.icon === 'tools'" d="m39 13 12 12M11 53l28-28M42 9l13 13-9 9-13-13zM11 53l-2-11 9 9z" />
                    <path v-else d="M32 7 59 55H5L32 7Zm0 17v15m0 8v2" />
                </svg>
            </div>

            <div class="status-copy">
                <span class="eyebrow">{{ content.eyebrow }}</span>
                <h1 id="status-title">{{ content.title }}</h1>
                <p>{{ content.description }}</p>

                <div class="status-actions">
                    <Link v-if="primaryHref" class="primary-action" :href="primaryHref">
                        {{ content.action }}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
                    </Link>
                    <button v-else class="primary-action" type="button" @click="retry">
                        {{ content.action }}
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 7v5h-5M4 17v-5h5M6 9a7 7 0 0 1 12-2l2 5M18 15a7 7 0 0 1-12 2l-2-5" /></svg>
                    </button>
                    <button v-if="status !== 503" class="secondary-action" type="button" @click="goBack">
                        Kembali
                    </button>
                </div>
            </div>
        </section>

        <footer class="status-footer">
            <span>COSMIC Mini Soccer Collective</span>
            <span>Pekanbaru · Indonesia</span>
        </footer>
    </main>
</template>

<style scoped>
:global(body.status-page-body) { padding-bottom: 0; background: #05090f; }
.status-page { position: relative; min-height: 100svh; overflow: hidden; isolation: isolate; display: grid; grid-template-rows:auto 1fr auto; padding: 0 clamp(24px,5vw,72px); color:#f7f9fc; background:radial-gradient(circle at 78% 28%,rgba(69,183,255,.12),transparent 31%),linear-gradient(135deg,#05090f 0%,#07111e 62%,#06101b 100%); }
.status-grid { position:absolute; z-index:-1; inset:0; opacity:.11; background-image:linear-gradient(rgba(175,218,255,.18) 1px,transparent 1px),linear-gradient(90deg,rgba(175,218,255,.18) 1px,transparent 1px); background-size:72px 72px; mask-image:linear-gradient(90deg,black,transparent 80%); }
.status-header { min-height:104px; display:flex; align-items:center; justify-content:space-between; border-bottom:1px solid rgba(175,218,255,.14); }
.brand { display:flex; align-items:center; gap:12px; color:white; text-decoration:none; font:900 1.65rem var(--font-brand); letter-spacing:-.08em; }
.brand img { width:40px; height:40px; border-radius:50%; object-fit:cover; }
.brand b,.eyebrow { color:var(--color-accent); }
.system-label,.status-footer { color:#6f8190; font:700 .58rem var(--font-display); letter-spacing:.16em; text-transform:uppercase; }
.status-content { width:min(1120px,100%); display:grid; grid-template-columns:210px minmax(0,1fr); align-items:center; gap:clamp(38px,7vw,105px); margin:auto; padding:70px 0; }
.status-code { position:absolute; right:-.03em; bottom:-.18em; z-index:-1; color:rgba(255,255,255,.025); font:900 clamp(18rem,38vw,42rem)/.7 var(--font-display); letter-spacing:-.08em; }
.status-symbol { width:210px; height:210px; display:grid; place-items:center; border:1px solid rgba(69,183,255,.38); border-radius:50%; color:var(--color-accent); background:rgba(69,183,255,.035); box-shadow:0 0 80px rgba(69,183,255,.06),inset 0 0 35px rgba(69,183,255,.035); }
.status-symbol svg { width:82px; stroke-width:2; stroke-linecap:round; stroke-linejoin:round; }
.eyebrow { display:block; margin-bottom:18px; font:800 .72rem var(--font-display); letter-spacing:.2em; text-transform:uppercase; }
.status-copy h1 { max-width:790px; margin:0; font:900 clamp(3.1rem,6.5vw,7.2rem)/.85 var(--font-display); letter-spacing:-.045em; text-transform:uppercase; }
.status-copy p { max-width:620px; margin:28px 0 0; color:#9fb1bf; font-size:1rem; line-height:1.7; }
.status-actions { display:flex; flex-wrap:wrap; gap:12px; margin-top:38px; }
.primary-action,.secondary-action { min-height:52px; display:inline-flex; align-items:center; justify-content:center; gap:22px; padding:0 25px; border:1px solid var(--color-accent); border-radius:3px; font:800 .68rem var(--font-display); letter-spacing:.09em; text-decoration:none; text-transform:uppercase; cursor:pointer; }
.primary-action { color:#03101a; background:var(--color-accent); }
.primary-action svg { width:18px; stroke-width:2; }
.secondary-action { color:#c0ced8; border-color:rgba(175,218,255,.24); background:transparent; }
.primary-action:hover { filter:brightness(1.08); }
.secondary-action:hover { color:white; border-color:rgba(175,218,255,.5); }
.status-footer { min-height:68px; display:flex; align-items:center; justify-content:space-between; border-top:1px solid rgba(175,218,255,.12); }
@media(max-width:720px){.status-header{min-height:82px}.system-label{display:none}.status-content{grid-template-columns:1fr;gap:34px;padding:52px 0}.status-symbol{width:130px;height:130px}.status-symbol svg{width:54px}.status-copy h1{font-size:clamp(3rem,15vw,5.2rem)}.status-copy p{font-size:.92rem}.status-footer{align-items:flex-start;flex-direction:column;justify-content:center;gap:6px}.status-code{font-size:18rem}.primary-action,.secondary-action{width:100%}}
</style>
