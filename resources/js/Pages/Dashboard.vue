<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { gsap } from "gsap";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
const props = defineProps({
    stats: { type: Array, default: () => [] },
    activities: { type: Array, default: () => [] },
    shortcuts: { type: Array, default: () => [] },
    nextMatch: { type: Object, default: null },
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
                <article
                    v-for="stat in props.stats"
                    :key="stat.label"
                    class="stat-card"
                >
                    <div class="stat-icon">
                        {{
                            stat.icon === "calendar"
                                ? "□"
                                : stat.icon === "trophy"
                                  ? "◇"
                                  : stat.icon === "image"
                                    ? "▣"
                                    : "○"
                        }}
                    </div>
                    <div class="stat-value">{{ stat.value }}</div>
                    <div class="stat-label">{{ stat.label }}</div>
                    <div class="stat-note">{{ stat.note }}</div>
                </article>
            </section>
            <section v-if="nextMatch" class="next-operation">
                <div>
                    <span>Pertandingan Terdekat</span
                    ><strong>{{ nextMatch.teams }}</strong
                    ><small
                        >{{ nextMatch.date }} · {{ nextMatch.time }} ·
                        {{ nextMatch.venue }}</small
                    >
                </div>
                <Link :href="nextMatch.href">Kelola Pertandingan →</Link>
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
    grid-template-columns: repeat(4, 1fr);
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
        grid-template-columns: repeat(2, 1fr);
    }
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
    .next-operation {
        align-items: flex-start;
        flex-direction: column;
    }
    .next-operation > a {
        width: 100%;
        text-align: center;
    }
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
