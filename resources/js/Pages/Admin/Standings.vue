<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ConfirmActionModal from "@/Components/Admin/ConfirmActionModal.vue";
import InfoTooltip from "@/Components/Admin/InfoTooltip.vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    seasons: Array,
    seasonId: Number,
    matchday: Number,
    standings: Array,
    adjustments: Array,
    logs: Array,
    clubs: Array,
    can: Object,
});
const page = usePage();
const confirmOpen = ref(false);
const hasSeason = computed(() => Number.isInteger(props.seasonId));
const adjustment = useForm({
    club_id: "",
    points: "",
    reason: "",
    effective_matchday: props.matchday || 1,
});
const selectedClub = computed(() =>
    props.clubs.find((club) => Number(club.id) === Number(adjustment.club_id)),
);
const adjustmentSummary = computed(() => {
    const points = Number(adjustment.points);
    if (!selectedClub.value || !points) return "";
    return `${selectedClub.value.name} · ${points > 0 ? "+" : ""}${points} poin mulai pekan ${adjustment.effective_matchday}`;
});

function selectSeason(event) {
    router.get(
        route("admin.standings.index"),
        { season_id: event.target.value },
        { preserveState: true, replace: true },
    );
}
function recalculate(publish) {
    if (!hasSeason.value) return;

    router.post(
        route("admin.standings.recalculate", props.seasonId),
        { publish },
        { preserveScroll: true },
    );
}
function requestAdjustment() {
    if (!hasSeason.value) return;

    confirmOpen.value = true;
}
function saveAdjustment() {
    if (!hasSeason.value) return;

    adjustment.post(
        route("admin.standings.adjustments.store", props.seasonId),
        {
            preserveScroll: true,
            onSuccess: () => {
                confirmOpen.value = false;
                adjustment.reset();
                adjustment.effective_matchday = props.matchday || 1;
            },
            onError: () => (confirmOpen.value = false),
        },
    );
}
const dateTime = (value) =>
    value
        ? new Intl.DateTimeFormat("id-ID", {
              dateStyle: "medium",
              timeStyle: "short",
          }).format(new Date(value))
        : "—";
const triggerLabel = (value) =>
    ({
        manual: "Dihitung ulang secara manual",
        publish: "Dihitung dan dipublikasikan",
        adjustment_created: "Penyesuaian poin diterapkan",
        adjustment_approved: "Penyesuaian poin diterapkan",
        match_created: "Pertandingan ditambahkan",
        match_updated: "Data pertandingan diperbarui",
        match_event_updated: "Kejadian pertandingan diperbarui",
        season_rules_updated: "Aturan musim diperbarui",
        test: "Pengujian sistem",
    })[value] || value?.replaceAll("_", " ");
const statusLabel = (value) =>
    ({ completed: "Berhasil", running: "Sedang diproses", failed: "Gagal" })[
        value
    ] || value;
</script>

<template>
    <Head title="Klasemen & Statistik" />
    <AuthenticatedLayout>
        <template #page-title>Klasemen & Statistik</template>
        <div class="page">
            <div v-if="page.props.flash?.success" class="flash">
                {{ page.props.flash.success }}
            </div>

            <header class="hero">
                <div>
                    <span>Manajemen Klasemen</span>
                    <h1>Klasemen & <b>Statistik.</b></h1>
                    <p>
                        Kelola perhitungan poin dan publikasi klasemen
                        kompetisi.
                    </p>
                </div>
                <div class="hero-actions">
                    <label>
                        <span>Musim kompetisi</span>
                        <select :value="seasonId" @change="selectSeason">
                            <option v-if="!hasSeason" value="">
                                Belum ada musim
                            </option>
                            <option
                                v-for="season in seasons"
                                :key="season.id"
                                :value="season.id"
                            >
                                {{ season.name }}
                            </option>
                        </select>
                    </label>
                    <button
                        v-if="can.recalculate && hasSeason"
                        type="button"
                        @click="recalculate(false)"
                    >
                        Hitung Ulang
                    </button>
                    <button
                        v-if="can.publish && hasSeason"
                        type="button"
                        class="primary"
                        @click="recalculate(true)"
                    >
                        Hitung & Publikasikan
                    </button>
                </div>
            </header>

            <section class="panel standings-panel">
                <header>
                    <div>
                        <span>Klasemen Liga</span>
                        <h2>Klasemen Terkini</h2>
                    </div>
                    <a
                        v-if="can.export && hasSeason"
                        :href="route('admin.standings.export', seasonId)"
                        >Unduh CSV</a
                    >
                </header>
                <div v-if="!hasSeason" class="season-empty">
                    <strong>Belum ada musim kompetisi</strong>
                    <p>
                        Tambahkan kompetisi dan musim terlebih dahulu melalui
                        menu Kompetisi &amp; Musim. Klasemen akan tersedia
                        setelah musim memiliki peserta dan pertandingan.
                    </p>
                </div>
                <div class="scroll">
                    <div class="table">
                        <div class="row head">
                            <span>Pos</span><span>Klub</span><span>Mn</span
                            ><span>M</span><span>S</span><span>K</span
                            ><span>GM</span><span>GK</span><span>SG</span
                            ><span>Pen.</span><span>Poin</span>
                        </div>
                        <div
                            v-for="club in standings"
                            :key="club.clubId"
                            :class="['row', { cosmic: club.cosmic }]"
                        >
                            <b>{{ club.position }}</b
                            ><strong>{{ club.name }}</strong
                            ><span>{{ club.played }}</span
                            ><span>{{ club.won }}</span
                            ><span>{{ club.drawn }}</span
                            ><span>{{ club.lost }}</span
                            ><span>{{ club.goalsFor }}</span
                            ><span>{{ club.goalsAgainst }}</span
                            ><span>{{ club.difference }}</span
                            ><span>{{ club.adjustment }}</span
                            ><b>{{ club.points }}</b>
                        </div>
                    </div>
                </div>
            </section>

            <div class="lower">
                <section class="panel adjustment-panel">
                    <header>
                        <div>
                            <span>Administrasi Poin</span>
                            <h2>Penyesuaian Poin Klub</h2>
                            <p>
                                Tambahkan atau kurangi poin klub berdasarkan
                                keputusan administratif.
                            </p>
                        </div>
                    </header>
                    <form
                        v-if="can.adjust && hasSeason"
                        class="adjust-form"
                        @submit.prevent="requestAdjustment"
                    >
                        <label class="wide">
                            <span>Klub</span>
                            <select v-model="adjustment.club_id" required>
                                <option value="">Pilih klub peserta</option>
                                <option
                                    v-for="club in clubs"
                                    :key="club.id"
                                    :value="club.id"
                                >
                                    {{ club.name }}
                                </option>
                            </select>
                            <small>Klub yang poinnya akan disesuaikan.</small>
                        </label>
                        <label>
                            <span
                                >Perubahan poin
                                <InfoTooltip label="Lihat cara perhitungan poin"
                                    ><strong>Cara perhitungan poin</strong
                                    ><br />Poin akhir = poin pertandingan +
                                    penyesuaian poin.<br />Gunakan nilai positif
                                    untuk menambah, misalnya +2, dan nilai
                                    negatif untuk mengurangi, misalnya
                                    -3.</InfoTooltip
                                ></span
                            >
                            <input
                                v-model="adjustment.points"
                                type="number"
                                min="-100"
                                max="100"
                                placeholder="Contoh: -3"
                                required
                            />
                            <small>Nilai tidak boleh 0.</small>
                        </label>
                        <label>
                            <span>Berlaku mulai pekan</span>
                            <input
                                v-model="adjustment.effective_matchday"
                                type="number"
                                min="1"
                                placeholder="Contoh: 4"
                                required
                            />
                            <small>Pekan mulai berlakunya perubahan.</small>
                        </label>
                        <label class="wide">
                            <span>Alasan penyesuaian</span>
                            <textarea
                                v-model="adjustment.reason"
                                rows="3"
                                maxlength="255"
                                placeholder="Contoh: Pengurangan poin karena pelanggaran regulasi"
                                required
                            ></textarea>
                            <small
                                >Jelaskan dasar penambahan atau pengurangan
                                poin.</small
                            >
                        </label>
                        <div class="form-note">
                            Setelah dikonfirmasi, klasemen akan dihitung ulang
                            dan langsung diperbarui pada website.
                        </div>
                        <button type="submit" :disabled="adjustment.processing">
                            Terapkan Penyesuaian
                        </button>
                        <div
                            v-if="Object.keys(adjustment.errors).length"
                            class="errors"
                        >
                            <p
                                v-for="(error, key) in adjustment.errors"
                                :key="key"
                            >
                                {{ error }}
                            </p>
                        </div>
                    </form>
                    <div v-else-if="!hasSeason" class="section-empty">
                        Penyesuaian poin tersedia setelah musim kompetisi
                        dibuat.
                    </div>

                    <div class="history-title">
                        <strong>Riwayat Penyesuaian Poin</strong
                        ><span>{{ adjustments.length }} catatan</span>
                    </div>
                    <div class="adjust-list">
                        <article v-for="item in adjustments" :key="item.id">
                            <div
                                class="point"
                                :class="{ minus: item.points < 0 }"
                            >
                                {{ item.points > 0 ? "+" : ""
                                }}{{ item.points }}
                            </div>
                            <div class="adjust-copy">
                                <strong>{{ item.club.display_name }}</strong>
                                <p>{{ item.reason }}</p>
                                <small
                                    >Pekan {{ item.effective_matchday }}
                                    · Dicatat oleh
                                    {{ item.created_by?.name || "Sistem" }} ·
                                    {{ dateTime(item.created_at) }}</small
                                >
                            </div>
                            <span
                                :class="[
                                    'record-status',
                                    { inactive: !item.is_active },
                                ]"
                                >{{
                                    item.is_active
                                        ? "Diterapkan"
                                        : "Tidak diterapkan"
                                }}</span
                            >
                        </article>
                        <p v-if="!adjustments.length" class="empty">
                            Belum ada penyesuaian poin pada musim ini.
                        </p>
                    </div>
                </section>

                <section class="panel log-panel">
                    <header>
                        <div>
                            <span>Riwayat Sistem</span>
                            <h2>Riwayat Perhitungan Klasemen</h2>
                            <p>
                                Catatan otomatis setiap kali data klasemen
                                dihitung ulang.
                            </p>
                        </div>
                    </header>
                    <div class="logs">
                        <article v-for="log in logs" :key="log.id">
                            <i :class="log.status"></i>
                            <div>
                                <div class="log-heading">
                                    <strong>{{
                                        triggerLabel(log.trigger)
                                    }}</strong
                                    ><span :class="log.status">{{
                                        statusLabel(log.status)
                                    }}</span>
                                </div>
                                <p v-if="log.status === 'failed'">
                                    {{
                                        log.errors?.message ||
                                        "Perhitungan tidak dapat diselesaikan."
                                    }}
                                </p>
                                <small v-else
                                    >{{ log.summary?.matches ?? 0 }}
                                    pertandingan ·
                                    {{ log.summary?.clubs ?? 0 }} klub ·
                                    {{
                                        log.summary?.published
                                            ? "Tampil di website"
                                            : "Belum dipublikasikan"
                                    }}</small
                                >
                                <time
                                    >{{ log.requested_by?.name || "Sistem" }} ·
                                    {{ dateTime(log.created_at) }}</time
                                >
                            </div>
                        </article>
                        <p v-if="!logs.length" class="empty">
                            Belum ada aktivitas perhitungan klasemen.
                        </p>
                    </div>
                </section>
            </div>
        </div>

        <ConfirmActionModal
            :open="confirmOpen"
            title="Terapkan penyesuaian poin?"
            :item-name="adjustmentSummary"
            description="Perubahan ini akan langsung memengaruhi klasemen dan dipublikasikan ke website."
            note="Pastikan klub, jumlah poin, pekan berlaku, dan alasan sudah benar. Aktivitas akan disimpan dalam riwayat internal."
            confirm-label="Terapkan Poin"
            processing-label="Menerapkan..."
            variant="warning"
            :processing="adjustment.processing"
            @confirm="saveAdjustment"
            @cancel="confirmOpen = false"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.page {
    max-width: 1450px;
    margin: auto;
}
.flash {
    margin-bottom: 12px;
    padding: 12px 14px;
    border: 1px solid #45b7ff55;
    color: #a9daf7;
    background: #45b7ff0a;
}
.hero {
    min-height: 220px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 30px;
    padding: 34px;
    border: 1px solid #ffffff18;
    background: radial-gradient(circle at 80% 20%, #45b7ff22, transparent 28%),
        #07111e;
}
.hero > div:first-child > span,
.panel header > div > span {
    color: var(--color-accent);
    font: 800 0.55rem var(--font-display);
    letter-spacing: 0.14em;
    text-transform: uppercase;
}
.hero h1 {
    margin: 8px 0;
    font: 900 clamp(3rem, 5vw, 5rem) / 0.85 var(--font-display);
    text-transform: uppercase;
}
.hero h1 b {
    color: var(--color-accent);
}
.hero p,
.panel header p {
    margin: 0;
    color: #83939f;
    line-height: 1.5;
}
.hero-actions {
    display: flex;
    align-items: flex-end;
    justify-content: flex-end;
    gap: 8px;
    flex-wrap: wrap;
}
.hero-actions label {
    display: grid;
    gap: 6px;
}
.hero-actions label span {
    color: #8194a0;
    font: 700 0.48rem var(--font-display);
    text-transform: uppercase;
}
.hero button,
.hero select {
    min-height: 42px;
    padding: 10px 13px;
    border: 1px solid #ffffff20;
    color: white;
    background: #07111e;
}
.hero button {
    font: 800 0.55rem var(--font-display);
    text-transform: uppercase;
}
.hero button.primary {
    border-color: var(--color-accent);
    color: #03101b;
    background: var(--color-accent);
}
.panel {
    border: 1px solid #ffffff18;
    background: #07111e;
}
.standings-panel {
    margin-top: 14px;
}
.season-empty,
.section-empty {
    margin: 18px;
    padding: 18px;
    border: 1px dashed #45b7ff4d;
    color: #8296a5;
    background: #45b7ff08;
}
.season-empty strong {
    color: #fff;
    font: 800 0.9rem var(--font-display);
    text-transform: uppercase;
}
.season-empty p {
    max-width: 720px;
    margin: 8px 0 0;
    line-height: 1.6;
}
.panel > header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 20px;
    border-bottom: 1px solid #ffffff18;
}
.panel h2 {
    margin: 4px 0;
    font: 900 1.5rem var(--font-display);
    text-transform: uppercase;
}
.panel header a {
    color: var(--color-accent);
    font: 800 0.6rem var(--font-display);
    text-decoration: none;
    text-transform: uppercase;
}
.scroll {
    overflow: auto;
}
.table {
    min-width: 900px;
}
.row {
    display: grid;
    grid-template-columns: 45px 1.5fr repeat(9, 0.5fr);
    align-items: center;
    gap: 5px;
    padding: 12px 16px;
    border-bottom: 1px solid #ffffff0e;
    text-align: center;
}
.row > :nth-child(2) {
    text-align: left;
}
.row.head {
    color: #71828e;
    font: 700 0.5rem var(--font-display);
    text-transform: uppercase;
}
.row.cosmic {
    border-left: 3px solid var(--color-accent);
    color: var(--color-accent);
    background: #45b7ff10;
}
.lower {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 14px;
    margin-top: 14px;
}
.adjust-form {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    padding: 18px;
}
.adjust-form label {
    display: flex;
    min-width: 0;
    flex-direction: column;
}
.adjust-form label.wide {
    grid-column: 1/-1;
}
.adjust-form label > span {
    margin-bottom: 7px;
    color: #c6d2da;
    font: 750 0.55rem var(--font-display);
    text-transform: uppercase;
}
.adjust-form label em {
    margin-left: 5px;
    color: #70828f;
    font-family: Arial, sans-serif;
    font-size: 0.58rem;
    font-style: normal;
    text-transform: none;
}
.adjust-form input,
.adjust-form select,
.adjust-form textarea {
    box-sizing: border-box;
    width: 100%;
    min-height: 42px;
    padding: 11px 12px;
    border: 1px solid #ffffff20;
    color: white;
    background: #050d16;
    font: inherit;
}
.adjust-form textarea {
    resize: vertical;
}
.adjust-form label > small {
    margin-top: 6px;
    color: #718590;
    font-size: 0.67rem;
    line-height: 1.4;
}
.form-note {
    grid-column: 1/-1;
    padding: 11px 13px;
    border-left: 3px solid var(--color-accent);
    color: #9db4c1;
    background: #45b7ff09;
    font-size: 0.72rem;
    line-height: 1.5;
}
.adjust-form > button {
    grid-column: 1/-1;
    min-height: 44px;
    border: 1px solid var(--color-accent);
    color: #03101b;
    background: var(--color-accent);
    font: 800 0.57rem var(--font-display);
    text-transform: uppercase;
}
.adjust-form > button:disabled {
    opacity: 0.55;
}
.errors {
    grid-column: 1/-1;
}
.errors p {
    margin: 4px 0;
    color: #ff9da4;
    font-size: 0.72rem;
}
.history-title {
    display: flex;
    justify-content: space-between;
    padding: 15px 18px 10px;
    border-top: 1px solid #ffffff12;
    font: 750 0.56rem var(--font-display);
    text-transform: uppercase;
}
.history-title span {
    color: #718590;
}
.adjust-list,
.logs {
    padding: 0 18px 18px;
}
.adjust-list article {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr) auto;
    align-items: center;
    gap: 12px;
    padding: 14px 0;
    border-bottom: 1px solid #ffffff10;
}
.point {
    display: grid;
    width: 44px;
    height: 44px;
    place-items: center;
    border: 1px solid #45b7ff55;
    color: var(--color-accent);
    font: 900 0.9rem var(--font-display);
}
.point.minus {
    border-color: #ff8c9455;
    color: #ff8c94;
}
.adjust-copy {
    min-width: 0;
}
.adjust-copy strong,
.adjust-copy small {
    display: block;
}
.adjust-copy p {
    margin: 4px 0;
    color: #b0c0c9;
    font-size: 0.76rem;
    line-height: 1.45;
}
.adjust-copy small {
    color: #718590;
    font-size: 0.65rem;
    line-height: 1.5;
}
.record-status {
    padding: 6px 8px;
    border: 1px solid #45b7ff55;
    color: var(--color-accent);
    font: 750 0.48rem var(--font-display);
    text-transform: uppercase;
}
.record-status.inactive {
    border-color: #ffffff22;
    color: #7c8b94;
}
.logs article {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px 0;
    border-bottom: 1px solid #ffffff10;
}
.logs > article > i {
    flex: 0 0 auto;
    width: 9px;
    height: 9px;
    margin-top: 5px;
    border-radius: 50%;
    background: #77848d;
}
.logs > article > i.completed {
    background: var(--color-accent);
}
.logs > article > i.failed {
    background: #ff626d;
}
.logs article > div {
    min-width: 0;
    flex: 1;
}
.log-heading {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 8px;
}
.log-heading strong {
    font-size: 0.78rem;
    text-transform: none;
}
.log-heading span {
    color: #91a2ad;
    font: 750 0.47rem var(--font-display);
    text-transform: uppercase;
}
.log-heading span.completed {
    color: var(--color-accent);
}
.log-heading span.failed {
    color: #ff8b93;
}
.logs small,
.logs time {
    display: block;
    margin-top: 5px;
    color: #788996;
    font-size: 0.67rem;
    line-height: 1.45;
}
.logs p {
    margin: 5px 0 0;
    color: #ff9da4;
    font-size: 0.7rem;
}
.logs time {
    color: #5f7480;
}
.empty {
    margin: 0;
    padding: 18px 0;
    color: #788996;
    font-size: 0.75rem;
}
@media (max-width: 1000px) {
    .lower {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 760px) {
    .hero {
        align-items: flex-start;
        flex-direction: column;
        padding: 24px;
    }
    .hero-actions {
        width: 100%;
        justify-content: flex-start;
    }
    .hero-actions label,
    .hero-actions select,
    .hero-actions button {
        width: 100%;
    }
}
@media (max-width: 600px) {
    .adjust-form {
        grid-template-columns: 1fr;
    }
    .adjust-form label.wide {
        grid-column: auto;
    }
    .adjust-form > button,
    .form-note,
    .errors {
        grid-column: auto;
    }
    .adjust-list article {
        grid-template-columns: 44px minmax(0, 1fr);
    }
    .record-status {
        grid-column: 2;
        justify-self: start;
    }
    .log-heading {
        flex-direction: column;
    }
}
</style>
