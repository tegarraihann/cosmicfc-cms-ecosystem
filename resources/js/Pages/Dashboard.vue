<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { gsap } from "gsap";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
const props = defineProps({
    stats: { type: Array, default: () => [] },
    activities: { type: Array, default: () => [] },
    shortcuts: { type: Array, default: () => [] },
    attentionItems: { type: Array, default: () => [] },
    systemStatus: { type: Array, default: () => [] },
    nextMatch: { type: Object, default: null },
    generatedAt: { type: String, default: "" },
    visitorAnalytics: { type: Object, default: null },
});
const page = usePage(),
    root = ref(null),
    user = computed(() => page.props.auth.user),
    firstName = computed(() => user.value?.name?.split(" ")[0] || "Admin"),
    roleLabel = computed(() =>
        user.value?.role === "superadmin"
            ? "Super Administrator"
            : "Administrator",
    );
let context;
onMounted(() => {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    context = gsap.context(
        () =>
            gsap
                .timeline({ defaults: { ease: "power3.out" } })
                .from(".welcome-copy>*", {
                    y: 24,
                    opacity: 0,
                    stagger: 0.08,
                    duration: 0.5,
                }),
        root.value,
    );
});
onBeforeUnmount(() => context?.revert());
</script>
<template>
    <Head title="Dashboard" /><AuthenticatedLayout
        ><template #page-title>Dashboard</template>
        <div ref="root" class="dashboard-page">
            <section class="welcome">
                <div class="welcome-copy">
                    <div class="eyebrow">{{ roleLabel }} Workspace</div>
                    <h1>
                        Selamat Datang,<br /><span>{{ firstName }}.</span>
                    </h1>
                    <p>
                        Pusat kendali untuk mengelola ekosistem digital COSMIC.
                    </p>
                    <small class="generated-at">Data diperbarui {{ generatedAt }}</small>
                </div>
                <div class="welcome-meta">
                    <i></i>
                    <div>
                        <strong>Sistem Aktif</strong
                        ><small>Data terhubung ke CMS</small>
                    </div>
                </div>
            </section>
            <section class="stats-grid">
                <component
                    v-for="stat in props.stats"
                    :key="stat.label"
                    :is="stat.href ? Link : 'article'"
                    :href="stat.href"
                    class="stat-card"
                >
                    <div class="stat-icon" aria-hidden="true">
                        <svg v-if="stat.icon === 'calendar'" viewBox="0 0 24 24">
                            <rect x="3" y="5" width="18" height="16" rx="2" />
                            <path d="M8 3v4M16 3v4M3 10h18" />
                            <path d="M8 14h3M14 14h2M8 17h2M13 17h3" />
                        </svg>
                        <svg v-else-if="stat.icon === 'trophy'" viewBox="0 0 24 24">
                            <path d="M8 4h8v4a4 4 0 0 1-8 0V4Z" />
                            <path d="M8 6H5v1a4 4 0 0 0 4 4M16 6h3v1a4 4 0 0 1-4 4M12 12v5M8 21h8M9 17h6" />
                        </svg>
                        <svg v-else-if="stat.icon === 'news'" viewBox="0 0 24 24">
                            <path d="M5 3h14v18H5z" />
                            <path d="M8 7h8M8 11h8M8 15h5" />
                        </svg>
                        <svg v-else-if="stat.icon === 'image'" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="16" rx="2" />
                            <circle cx="8.5" cy="9" r="1.5" />
                            <path d="m5 18 5-5 3 3 2-2 4 4" />
                        </svg>
                        <svg v-else viewBox="0 0 24 24">
                            <circle cx="9" cy="8" r="4" />
                            <path d="M3 21v-2a6 6 0 0 1 12 0v2M16 7a4 4 0 0 1 0 7M18 17a5 5 0 0 1 3 4" />
                        </svg>
                    </div>
                    <div class="stat-value">{{ stat.value }}</div>
                    <div class="stat-label">{{ stat.label }}</div>
                    <div class="stat-note">{{ stat.note }}</div>
                </component>
            </section>
            <Link v-if="visitorAnalytics" :href="route('admin.analytics.index')" class="visitor-card">
                <div class="visitor-title"><span>Google Analytics 4</span><h2>Statistik Pengunjung Website</h2><small v-if="visitorAnalytics.status.lastSyncedAt">Diperbarui {{ visitorAnalytics.status.lastSyncedAt }}</small></div>
                <template v-if="visitorAnalytics.data">
                    <div class="visitor-metric"><strong>{{ visitorAnalytics.data.todayUsers }}</strong><span>Pengunjung hari ini</span></div>
                    <div class="visitor-metric"><strong>{{ visitorAnalytics.data.users30Days }}</strong><span>30 hari terakhir</span></div>
                    <div class="visitor-metric"><strong>{{ visitorAnalytics.data.pageViews30Days }}</strong><span>Tampilan halaman</span></div>
                    <div class="visitor-change" :class="{ down: visitorAnalytics.data.changePercent < 0 }"><strong>{{ visitorAnalytics.data.changePercent === null ? '—' : `${visitorAnalytics.data.changePercent > 0 ? '+' : ''}${visitorAnalytics.data.changePercent}%` }}</strong><span>Dari periode sebelumnya</span></div>
                </template>
                <div v-else class="visitor-empty"><strong>GA4 belum terhubung</strong><span>Atur integrasi untuk melihat statistik pengunjung.</span></div>
                <b class="visitor-link">Lihat statistik lengkap →</b>
            </Link>
            <div class="operations-grid">
                <section class="dashboard-panel attention-panel">
                    <header>
                        <div><span>Prioritas</span><h2>Perlu Ditindaklanjuti</h2></div>
                        <small>{{ attentionItems.length }} pekerjaan</small>
                    </header>
                    <div v-if="attentionItems.length" class="attention-list">
                        <Link v-for="item in attentionItems" :key="item.label" :href="item.href">
                            <i :class="item.level"></i><span>{{ item.label }}</span><strong>{{ item.count }}</strong>
                        </Link>
                    </div>
                    <div v-else class="empty-state compact">
                        <strong>Tidak ada pekerjaan mendesak</strong>
                        <p>Data operasional utama sudah lengkap.</p>
                    </div>
                </section>
                <section class="dashboard-panel system-panel">
                    <header><div><span>Monitoring</span><h2>Status Sistem</h2></div></header>
                    <div class="system-list">
                        <div v-for="item in systemStatus" :key="item.label">
                            <i :class="item.level"></i><span><strong>{{ item.label }}</strong><small>{{ item.detail }}</small></span>
                        </div>
                    </div>
                </section>
            </div>
            <section v-if="nextMatch" class="next-operation">
                <div>
                    <span>Pertandingan Terdekat</span
                    ><strong>{{ nextMatch.teams }}</strong
                    ><small
                        >{{ nextMatch.date }} · {{ nextMatch.time }} ·
                        {{ nextMatch.venue }}</small
                    >
                </div>
                <Link :href="nextMatch.href">{{ nextMatch.action }} →</Link>
            </section>
            <section v-else class="next-operation is-empty">
                <div>
                    <span>Pertandingan Terdekat</span>
                    <strong>Belum ada pertandingan aktif</strong>
                    <small>Tambahkan pertandingan baru untuk memulai alur operasional.</small>
                </div>
                <Link v-if="shortcuts.some((item) => item.label === 'Tambah Pertandingan')" :href="route('admin.matches.index', { action: 'create' })">Tambah Pertandingan →</Link>
            </section>
            <div class="dashboard-grid">
                <section class="dashboard-panel">
                    <header>
                        <div>
                            <span>Riwayat Sistem</span>
                            <h2>Aktivitas Terbaru</h2>
                        </div>
                        <small
                            >{{ activities.length }} aktivitas terakhir</small
                        >
                    </header>
                    <div v-if="activities.length" class="activity-list">
                        <Link v-for="a in activities" :key="a.id" :href="a.href"
                            ><i :class="a.type"></i>
                            <div>
                                <strong>{{ a.title }}</strong>
                                <p>{{ a.detail }}</p>
                            </div>
                            <time>{{ a.time }}</time></Link
                        >
                    </div>
                    <div v-else class="empty-state">
                        <strong>Belum ada aktivitas</strong>
                        <p>
                            Aktivitas akan muncul setelah admin memperbarui data
                            atau konten website.
                        </p>
                    </div>
                </section>
                <section class="dashboard-panel">
                    <header>
                        <div>
                            <span>Shortcut</span>
                            <h2>Akses Cepat</h2>
                        </div>
                    </header>
                    <div v-if="shortcuts.length" class="quick-list">
                        <Link
                            v-for="s in shortcuts"
                            :key="s.label"
                            :href="s.href"
                            ><b>{{ s.icon === "upload" ? "↑" : "+" }}</b>
                            <div>
                                <strong>{{ s.label }}</strong
                                ><small>{{ s.description }}</small>
                            </div></Link
                        >
                    </div>
                    <div v-else class="empty-state">
                        <strong>Tidak ada akses cepat</strong>
                        <p>Shortcut mengikuti hak akses akun Anda.</p>
                    </div>
                    <Link class="site-preview" href="/" target="_blank"
                        >Buka Website Publik <span>→</span></Link
                    >
                </section>
            </div>
        </div></AuthenticatedLayout
    >
</template>
<style scoped>
.dashboard-page {
    max-width: 1440px;
    margin: auto;
}
.welcome {
    min-height: 250px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 40px;
    padding: 37px 40px;
    margin-bottom: 16px;
    border: 1px solid #afdafa24;
    background:
        linear-gradient(90deg, #07111efa, #07111eb3),
        url("/Assets/Dokumentasi/Docs-wahyu.jpeg") 65% 46% / cover;
}
.eyebrow,
.dashboard-panel header span,
.next-operation span {
    color: var(--color-accent);
    font: 800 0.58rem var(--font-display);
    letter-spacing: 0.18em;
    text-transform: uppercase;
}
.welcome h1 {
    margin: 12px 0 0;
    font: 900 clamp(3.3rem, 5vw, 5.6rem) / 0.79 var(--font-display);
    letter-spacing: -0.045em;
    text-transform: uppercase;
}
.welcome h1 span {
    color: var(--color-accent);
}
.welcome p {
    margin: 18px 0 0;
    color: #9eacb8;
}
.generated-at {
    display: block;
    margin-top: 10px;
    color: #627582;
    font-size: 0.62rem;
}
.welcome-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border: 1px solid #afdafa29;
    background: #05090f9e;
}
.welcome-meta i,
.activity-list i {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--color-accent);
    box-shadow: 0 0 12px #45b7ffb3;
}
.visitor-card{display:grid;grid-template-columns:minmax(240px,1.4fr) repeat(3,minmax(100px,.55fr)) minmax(120px,.7fr) auto;align-items:center;gap:20px;margin-bottom:16px;padding:22px 25px;border:1px solid #45b7ff38;color:white;background:linear-gradient(100deg,#07111e,#091827);text-decoration:none;transition:border-color .2s,transform .2s}.visitor-card:hover{border-color:#45b7ff88;transform:translateY(-1px)}.visitor-title span{color:var(--color-accent);font:800 .56rem var(--font-display);letter-spacing:.16em;text-transform:uppercase}.visitor-title h2{margin:5px 0 2px;font:850 1.25rem var(--font-display);text-transform:uppercase}.visitor-title small,.visitor-metric span,.visitor-change span,.visitor-empty span{display:block;color:#718692;font-size:.57rem}.visitor-metric,.visitor-change{padding-left:18px;border-left:1px solid #ffffff15}.visitor-metric strong,.visitor-change strong{font:900 1.65rem var(--font-display)}.visitor-change strong{color:#43d394}.visitor-change.down strong{color:#ff8790}.visitor-empty{grid-column:2/6}.visitor-empty strong{display:block;margin-bottom:4px}.visitor-link{color:var(--color-accent);font:800 .56rem var(--font-display);white-space:nowrap;text-transform:uppercase}
.welcome-meta strong,
.welcome-meta small {
    display: block;
}
.welcome-meta strong {
    font: 800 0.65rem var(--font-display);
    text-transform: uppercase;
}
.welcome-meta small {
    margin-top: 4px;
    color: #748490;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}
.stat-card {
    position: relative;
    display: block;
    visibility: visible;
    opacity: 1;
    min-height: 175px;
    padding: 21px;
    border: 1px solid #afdafa21;
    background: #07111eb8;
    color: inherit;
    text-decoration: none;
    transition: 0.22s;
}
.stat-card:hover {
    border-color: #45b7ff61;
    transform: translateY(-3px);
}
.stat-icon {
    position: absolute;
    top: 20px;
    right: 20px;
    display: grid;
    width: 35px;
    height: 35px;
    place-items: center;
    border: 1px solid #45b7ff33;
    border-radius: 50%;
    color: var(--color-accent);
}
.stat-icon svg {
    width: 19px;
    height: 19px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.7;
    stroke-linecap: round;
    stroke-linejoin: round;
}
.stat-value {
    margin-top: 20px;
    font: 900 3.8rem/0.8 var(--font-display);
}
.stat-label {
    margin-top: 18px;
    font: 800 0.65rem var(--font-display);
    letter-spacing: 0.13em;
    text-transform: uppercase;
}
.stat-note {
    margin-top: 6px;
    color: #687985;
    font-size: 0.6rem;
}
.next-operation {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 24px;
    margin-bottom: 16px;
    padding: 20px 22px;
    border: 1px solid #45b7ff47;
    background: linear-gradient(90deg, #45b7ff17, #07111eb8);
}
.next-operation strong,
.next-operation small {
    display: block;
}
.next-operation strong {
    margin-top: 7px;
    font: 850 1.25rem var(--font-display);
    text-transform: uppercase;
}
.next-operation small {
    margin-top: 5px;
    color: #8395a1;
}
.next-operation > a {
    padding: 12px 15px;
    color: #06111e;
    background: var(--color-accent);
    font: 800 0.58rem var(--font-display);
    text-decoration: none;
    text-transform: uppercase;
}
.next-operation.is-empty {
    border-color: #afdafa21;
}
.operations-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(300px, 0.65fr);
    gap: 16px;
    margin-bottom: 16px;
}
.attention-list > a {
    min-height: 54px;
    display: grid;
    grid-template-columns: 9px minmax(0, 1fr) auto;
    align-items: center;
    gap: 13px;
    padding: 10px 20px;
    border-bottom: 1px solid #ffffff12;
    color: inherit;
    text-decoration: none;
}
.attention-list > a:hover {
    background: #45b7ff0b;
}
.attention-list i,
.system-list i {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #7b8994;
}
.attention-list i.danger,
.system-list i.danger {
    background: #ff5b67;
    box-shadow: 0 0 10px #ff5b6766;
}
.attention-list i.warning,
.system-list i.warning {
    background: #f4ae48;
    box-shadow: 0 0 10px #f4ae4866;
}
.attention-list i.info {
    background: var(--color-accent);
}
.attention-list span {
    font: 750 0.67rem var(--font-display);
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.attention-list strong {
    color: var(--color-accent);
    font: 900 1rem var(--font-display);
}
.system-list > div {
    min-height: 65px;
    display: grid;
    grid-template-columns: 9px minmax(0, 1fr);
    align-items: center;
    gap: 13px;
    padding: 10px 20px;
    border-bottom: 1px solid #ffffff12;
}
.system-list i.success {
    background: #4ed18b;
    box-shadow: 0 0 10px #4ed18b66;
}
.system-list i.unknown {
    background: #70808c;
}
.system-list strong,
.system-list small {
    display: block;
}
.system-list strong {
    font: 750 0.67rem var(--font-display);
    letter-spacing: 0.05em;
    text-transform: uppercase;
}
.system-list small {
    margin-top: 5px;
    color: #687985;
    font-size: 0.6rem;
}
.empty-state.compact {
    padding: 27px 20px;
}
.dashboard-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(300px, 0.65fr);
    gap: 16px;
}
.dashboard-panel {
    border: 1px solid #afdafa21;
    background: #07111e9e;
}
.dashboard-panel header {
    min-height: 72px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 21px;
    border-bottom: 1px solid #ffffff17;
}
.dashboard-panel header h2 {
    margin: 5px 0 0;
    font: 850 1.25rem var(--font-display);
    text-transform: uppercase;
}
.dashboard-panel header > small {
    color: #60717d;
}
.activity-list > a {
    min-height: 78px;
    display: grid;
    grid-template-columns: 10px 1fr auto;
    align-items: center;
    gap: 15px;
    padding: 12px 21px;
    border-bottom: 1px solid #ffffff13;
    color: inherit;
    text-decoration: none;
}
.activity-list > a:hover,
.quick-list > a:hover {
    background: #45b7ff0b;
}
.activity-list strong,
.quick-list strong {
    font: 750 0.7rem var(--font-display);
    letter-spacing: 0.05em;
    text-transform: uppercase;
}
.activity-list p {
    margin: 5px 0 0;
    color: #758691;
}
.activity-list time {
    color: #596a76;
    font-size: 0.6rem;
}
.quick-list {
    padding: 12px;
}
.quick-list > a {
    min-height: 62px;
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 10px;
    border-bottom: 1px solid #ffffff12;
    color: #fff;
    text-decoration: none;
}
.quick-list b {
    display: grid;
    width: 34px;
    height: 34px;
    place-items: center;
    border: 1px solid #45b7ff33;
    color: var(--color-accent);
}
.quick-list small {
    display: block;
    margin-top: 4px;
    color: #60717d;
}
.empty-state {
    padding: 35px 22px;
    text-align: center;
}
.empty-state strong {
    font: 800 0.72rem var(--font-display);
    text-transform: uppercase;
}
.empty-state p {
    margin: 8px auto 0;
    color: #687985;
}
.site-preview {
    min-height: 49px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 0 12px 12px;
    padding: 0 15px;
    color: #04101c;
    background: var(--color-accent);
    font: 800 0.63rem var(--font-display);
    text-decoration: none;
    text-transform: uppercase;
}
@media (max-width: 1100px) {
    .stats-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .visitor-card { grid-template-columns:1.2fr repeat(3,.55fr); }
    .visitor-change { display:none; }
    .visitor-link { grid-column:1/-1; }
}
@media (max-width: 760px) {
    .welcome {
        min-height: 290px;
        flex-direction: column;
        align-items: flex-start;
        justify-content: flex-end;
        padding: 28px 24px;
    }
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    .operations-grid {
        grid-template-columns: 1fr;
    }
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .next-operation {
        align-items: flex-start;
        flex-direction: column;
    }
    .next-operation > a {
        width: 100%;
        text-align: center;
    }
    .visitor-card { grid-template-columns:1fr 1fr; }
    .visitor-title,.visitor-link { grid-column:1/-1; }
    .visitor-metric { padding:10px 0 0;border-left:0;border-top:1px solid #ffffff15; }
    .visitor-metric:nth-of-type(3) { display:none; }
    .visitor-empty { grid-column:1/-1; }
}
@media (max-width: 520px) {
    .welcome h1 {
        font-size: 3.5rem;
    }
    .stats-grid {
        gap: 8px;
    }
    .stat-card {
        min-height: 155px;
        padding: 16px;
    }
    .stat-value {
        font-size: 3rem;
    }
    .activity-list > a {
        grid-template-columns: 8px 1fr;
    }
    .activity-list time {
        grid-column: 2;
    }
    .dashboard-panel header > small {
        display: none;
    }
}
</style>
