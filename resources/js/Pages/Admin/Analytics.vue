<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";

const props = defineProps({ analytics: Object, filters: Object, canRefresh: Boolean });
const page = usePage();
const filter = useForm({ ...props.filters });
const applyFilter = () => filter.get(route("admin.analytics.index"), { preserveState: true });
const refresh = () => router.post(route("admin.analytics.refresh"), { start_date: props.filters.start_date, end_date: props.filters.end_date }, { preserveScroll: true });
const number = (value) => new Intl.NumberFormat("id-ID").format(value || 0);
const duration = (seconds) => `${Math.floor((seconds || 0) / 60)}m ${Math.round((seconds || 0) % 60)}d`;
</script>

<template>
    <Head title="Statistik Pengunjung" />
    <AuthenticatedLayout>
        <template #page-title>Statistik Pengunjung</template>
        <div class="analytics-page">
            <header class="analytics-hero">
                <div><span>Google Analytics 4</span><h1>Statistik Pengunjung<b>.</b></h1><p>Pahami cara pengunjung menemukan dan menggunakan website COSMIC.</p></div>
                <div class="connection"><i :class="analytics.status.connectionStatus"></i><strong>{{ analytics.status.connectionStatus === 'connected' ? 'GA4 Terhubung' : 'GA4 Belum Terhubung' }}</strong><small>{{ analytics.status.lastSyncedAt || 'Belum pernah disinkronkan' }}</small></div>
            </header>

            <section class="filter-panel">
                <label>Periode<select v-model="filter.period"><option value="7">7 hari</option><option value="30">30 hari</option><option value="90">90 hari</option><option value="custom">Tanggal khusus</option></select></label>
                <template v-if="filter.period === 'custom'"><label>Dari<input v-model="filter.start_date" type="date" /></label><label>Sampai<input v-model="filter.end_date" type="date" /></label></template>
                <button type="button" @click="applyFilter">Terapkan</button>
                <button v-if="canRefresh" type="button" class="secondary" @click="refresh">Perbarui Data</button>
            </section>
            <div v-if="page.props.errors?.refresh" class="refresh-notice">{{ page.props.errors.refresh }}</div>

            <section v-if="!analytics.available && !analytics.data" class="empty-analytics">
                <strong>Statistik belum dapat ditampilkan</strong>
                <p>{{ analytics.status.lastError || 'Hubungkan Google Analytics 4 melalui Pengaturan Website → Integrasi.' }}</p>
                <a :href="route('admin.club-profile.edit')">Atur Integrasi GA4</a>
            </section>
            <template v-else>
                <section class="metric-grid">
                    <article><span>Pengguna</span><strong>{{ number(analytics.data.overview.activeUsers) }}</strong></article>
                    <article><span>Pengguna Baru</span><strong>{{ number(analytics.data.overview.newUsers) }}</strong></article>
                    <article><span>Sesi</span><strong>{{ number(analytics.data.overview.sessions) }}</strong></article>
                    <article><span>Tampilan Halaman</span><strong>{{ number(analytics.data.overview.pageViews) }}</strong></article>
                    <article><span>Engagement Rate</span><strong>{{ analytics.data.overview.engagementRate }}%</strong></article>
                    <article><span>Durasi Rata-rata</span><strong>{{ duration(analytics.data.overview.averageSessionDuration) }}</strong></article>
                </section>
                <section class="report-grid">
                    <article class="wide"><header><span>Tren Harian</span><h2>Aktivitas Pengunjung</h2></header><div class="trend-list"><div v-for="row in analytics.data.trend" :key="row.date"><time>{{ row.date }}</time><span><i :style="{ width: Math.max(3, Math.min(100, row.users)) + '%' }"></i></span><strong>{{ number(row.users) }}</strong></div></div></article>
                    <article><header><span>Konten</span><h2>Halaman Terpopuler</h2></header><ol><li v-for="row in analytics.data.pages" :key="row.path"><div><strong>{{ row.title || row.path }}</strong><small>{{ row.path }}</small></div><b>{{ number(row.views) }}</b></li></ol></article>
                    <article><header><span>Akuisisi</span><h2>Sumber Kunjungan</h2></header><ol><li v-for="row in analytics.data.sources" :key="row.source"><strong>{{ row.source }}</strong><b>{{ number(row.sessions) }}</b></li></ol></article>
                    <article><header><span>Teknologi</span><h2>Perangkat</h2></header><ol><li v-for="row in analytics.data.devices" :key="row.device"><strong>{{ row.device }}</strong><b>{{ number(row.users) }}</b></li></ol></article>
                    <article><header><span>Lokasi</span><h2>Wilayah Pengunjung</h2></header><ol><li v-for="row in analytics.data.locations" :key="`${row.country}-${row.city}`"><strong>{{ row.city || 'Tidak diketahui' }}, {{ row.country }}</strong><b>{{ number(row.users) }}</b></li></ol></article>
                    <article class="wide"><header><span>Interaksi</span><h2>Event Terpopuler</h2></header><ol class="event-grid"><li v-for="row in analytics.data.events" :key="row.event"><strong>{{ row.event }}</strong><b>{{ number(row.count) }}</b></li></ol></article>
                </section>
            </template>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.refresh-notice{margin:-5px 0 15px;padding:12px 15px;border:1px solid #ffb54a55;color:#ffd18c;background:#241b0d;font-size:.68rem}
.analytics-page{max-width:1440px;margin:auto}.analytics-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:28px;padding:40px;border:1px solid #afdafa24;background:linear-gradient(120deg,#07111e,#0a1c2c)}.analytics-hero span,.report-grid header span{color:var(--color-accent);font:800 .58rem var(--font-display);letter-spacing:.17em;text-transform:uppercase}.analytics-hero h1{margin:9px 0 0;font:900 clamp(2.7rem,5vw,5rem)/.85 var(--font-display);text-transform:uppercase}.analytics-hero h1 b{color:var(--color-accent)}.analytics-hero p{color:#8fa1ae}.connection{min-width:220px;padding:16px;border:1px solid #ffffff1c}.connection>*{display:block}.connection i{width:8px;height:8px;margin-bottom:8px;border-radius:50%;background:#71808a}.connection i.connected{background:#42d393}.connection small{margin-top:5px;color:#758794}.filter-panel{display:flex;align-items:end;gap:10px;margin:15px 0;padding:15px;border:1px solid #ffffff15;background:#07111e}.filter-panel label{display:grid;gap:6px;color:#8496a3;font-size:.64rem}.filter-panel input,.filter-panel select{min-height:42px;padding:0 12px;border:1px solid #ffffff1f;color:white;background:#06101a}.filter-panel button,.empty-analytics a{min-height:42px;padding:0 16px;border:1px solid var(--color-accent);color:#04101a;background:var(--color-accent);font:800 .57rem var(--font-display);text-transform:uppercase}.filter-panel .secondary{color:#d6e2ea;border-color:#ffffff25;background:transparent}.metric-grid{display:grid;grid-template-columns:repeat(6,1fr);gap:10px}.metric-grid article,.report-grid>article{padding:20px;border:1px solid #ffffff16;background:#07111e}.metric-grid span{color:#718593;font-size:.62rem}.metric-grid strong{display:block;margin-top:9px;font:900 2rem var(--font-display)}.report-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px}.report-grid .wide{grid-column:1/-1}.report-grid h2{margin:5px 0 18px;font:850 1.45rem var(--font-display);text-transform:uppercase}.report-grid ol{display:grid;gap:0;margin:0;padding:0;list-style:none}.report-grid li{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:11px 0;border-top:1px solid #ffffff0f}.report-grid li small{display:block;color:#687b89}.report-grid li b{color:var(--color-accent)}.event-grid{grid-template-columns:repeat(3,1fr);column-gap:24px}.trend-list{display:grid;gap:9px}.trend-list div{display:grid;grid-template-columns:80px 1fr 60px;align-items:center;gap:10px}.trend-list time{color:#8093a0;font-size:.61rem}.trend-list span{height:6px;background:#ffffff0b}.trend-list i{display:block;height:100%;background:var(--color-accent)}.trend-list strong{text-align:right}.empty-analytics{padding:55px;margin-top:15px;border:1px dashed #ffffff26;text-align:center}.empty-analytics strong{font:850 1.6rem var(--font-display);text-transform:uppercase}.empty-analytics p{margin-bottom:25px;color:#8598a5}.empty-analytics a{display:inline-flex;align-items:center;text-decoration:none}@media(max-width:1000px){.metric-grid{grid-template-columns:repeat(3,1fr)}}@media(max-width:700px){.analytics-hero,.filter-panel{align-items:stretch;flex-direction:column}.metric-grid,.report-grid{grid-template-columns:1fr 1fr}.report-grid>article,.report-grid .wide{grid-column:1/-1}.event-grid{grid-template-columns:1fr}.connection{min-width:0}}@media(max-width:480px){.metric-grid{grid-template-columns:1fr 1fr}.analytics-hero{padding:25px 20px}}
</style>
