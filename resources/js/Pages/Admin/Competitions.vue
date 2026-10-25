<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import MediaPicker from "@/Components/Admin/MediaPicker.vue";
import SeasonEditor from "@/Components/Admin/SeasonEditor.vue";
import ParticipantManager from "@/Components/Admin/ParticipantManager.vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
const p = defineProps({
        competitions: Object,
        filters: Object,
        clubs: Array,
        media: Array,
        can: Object,
    }),
    page = usePage(),
    modal = ref(""),
    current = ref(null),
    season = ref(null),
    seasonTab = ref("info"),
    selectedLogo = ref(null),
    selectedBanner = ref(null),
    filter = ref({ ...p.filters });
const emptyCompetition = () => ({
    name: "",
    short_name: "",
    type: "league",
    organizer: "COSMIC",
    description: "",
    logo_media_id: null,
    banner_media_id: null,
    website_url: "",
    status: "active",
});
const cf = useForm(emptyCompetition());
const emptySeason = () => ({
    name: "",
    starts_at: "",
    ends_at: "",
    format: "league",
    current_matchday: 0,
    total_matchdays: 0,
    max_participants: 8,
    win_points: 3,
    draw_points: 1,
    loss_points: 0,
    tie_breakers: ["points", "goal_difference", "goals_for", "head_to_head"],
    regulations: [],
    is_featured: false,
    status: "draft",
});
const sf = useForm(emptySeason());
const pf = useForm({ participants: [] });
function editCompetition(c = null) {
    current.value = c;
    selectedLogo.value = c?.logo_media_id
        ? p.media.find(
              (item) => Number(item.id) === Number(c.logo_media_id),
          ) || null
        : null;
    selectedBanner.value = c?.banner_media_id
        ? p.media.find(
              (item) => Number(item.id) === Number(c.banner_media_id),
          ) || null
        : null;
    cf.defaults(c ? { ...emptyCompetition(), ...c } : emptyCompetition());
    cf.reset();
    modal.value = "competition";
}
function saveCompetition() {
    let o = { onSuccess: () => (modal.value = "") };
    current.value
        ? cf.put(route("admin.competitions.update", current.value.id), o)
        : cf.post(route("admin.competitions.store"), o);
}
function editSeason(c, s = null) {
    current.value = c;
    season.value = s;
    sf.defaults(s ? { ...emptySeason(), ...s } : emptySeason());
    sf.reset();
    sf.clearErrors();
    seasonTab.value = "info";
    modal.value = "season";
}
function saveSeason() {
    sf.post(
        route("admin.competitions.seasons.save", {
            competition: current.value.id,
            season: season.value?.id,
        }),
        { onSuccess: () => (modal.value = "") },
    );
}
function editParticipants(c, s) {
    current.value = c;
    season.value = s;
    pf.defaults({
        participants: (s.participants || []).map((x) => ({
            club_id: x.club_id,
        })),
    });
    pf.reset();
    modal.value = "participants";
}
function saveParticipants() {
    pf.put(route("admin.competitions.participants.sync", season.value.id), {
        onSuccess: () => (modal.value = ""),
    });
}
function search() {
    router.get(route("admin.competitions.index"), filter.value, {
        preserveState: true,
        replace: true,
    });
}
function resetFilters() {
    filter.value = { search: "", type: "", status: "" };
    search();
}
const typeLabel = (value) =>
    ({
        league: "Liga",
        tournament: "Turnamen",
        friendly_series: "Seri Persahabatan",
        single_event: "Pertandingan Tunggal",
    })[value] || value;
const competitionStatusLabel = (value) =>
    ({
        draft: "Draft",
        active: "Aktif",
        inactive: "Nonaktif",
        archived: "Diarsipkan",
    })[value] || value;
const seasonStatusLabel = (value) =>
    ({
        draft: "Draft",
        registration: "Pendaftaran",
        scheduled: "Terjadwal",
        ongoing: "Sedang Berlangsung",
        completed: "Selesai",
        cancelled: "Dibatalkan",
    })[value] || value;
const formatDate = (value) => {
    if (!value) return "Belum ditentukan";
    return new Intl.DateTimeFormat("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
    }).format(new Date(`${value}T00:00:00`));
};
</script>
<template>
    <Head title="Kompetisi & Musim" /><AuthenticatedLayout
        ><template #page-title>Kompetisi & Musim</template>
        <div class="page">
            <div v-if="page.props.flash?.success" class="notice">
                {{ page.props.flash.success }}
            </div>
            <header class="hero">
                <div>
                    <span>Competition Control</span>
                    <h1>Competition <b>Seasons.</b></h1>
                    <p>
                        Kelola kompetisi, musim, peserta, sistem poin, dan
                        regulasi publik.
                    </p>
                </div>
                <Link v-if="can.create" :href="route('admin.competitions.create')" class="hero-action">
                    + Kompetisi
                </Link>
            </header>
            <div class="stats">
                <article>
                    <b>{{ competitions.total }}</b
                    ><span>Kompetisi</span>
                </article>
                <article>
                    <b>{{
                        competitions.data.flatMap((x) => x.seasons).length
                    }}</b
                    ><span>Musim</span>
                </article>
                <article>
                    <b>{{
                        competitions.data
                            .flatMap((x) => x.seasons)
                            .find((x) => x.is_featured)?.name || "–"
                    }}</b
                    ><span>Featured</span>
                </article>
            </div>
            <section class="filter-panel">
                <header>
                    <div>
                        <span>Filter Data</span>
                        <h2>Daftar Kompetisi</h2>
                    </div>
                    <strong
                        >{{ competitions.total }} kompetisi ditemukan</strong
                    >
                </header>
                <form class="filters" @submit.prevent="search">
                    <label class="search-field"
                        ><span>Pencarian</span
                        ><input
                            v-model="filter.search"
                            placeholder="Cari nama kompetisi atau musim"
                        /><small
                            >Pencarian berdasarkan nama kompetisi dan
                            musim.</small
                        ></label
                    >
                    <label
                        ><span>Format kompetisi</span
                        ><select v-model="filter.type">
                            <option value="">Semua format</option>
                            <option value="league">Liga</option>
                            <option value="tournament">Turnamen</option>
                            <option value="friendly_series">
                                Seri pertandingan persahabatan
                            </option>
                            <option value="single_event">
                                Pertandingan tunggal
                            </option></select
                        ><small
                            >Saring berdasarkan jenis kompetisi.</small
                        ></label
                    >
                    <label
                        ><span>Status kompetisi</span
                        ><select v-model="filter.status">
                            <option value="">Semua status</option>
                            <option value="draft">Draft</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                            <option value="archived">Diarsipkan</option></select
                        ><small
                            >Saring berdasarkan status pengelolaan.</small
                        ></label
                    >
                    <div class="filter-actions">
                        <button type="submit">Terapkan Filter</button
                        ><button
                            v-if="filter.search || filter.type || filter.status"
                            type="button"
                            class="reset"
                            @click="resetFilters"
                        >
                            Reset
                        </button>
                    </div>
                </form>
            </section>
            <article
                v-for="c in competitions.data"
                :key="c.id"
                class="competition"
            >
                <header class="competition-header">
                    <div class="logo">
                        <img
                            v-if="c.logo_url"
                            :src="c.logo_url"
                            :alt="`Logo ${c.name}`"
                        /><span v-else>{{ c.short_name }}</span>
                    </div>
                    <div class="competition-info">
                        <div class="badges">
                            <span>{{ typeLabel(c.type) }}</span
                            ><span :class="`status-${c.status}`">{{
                                competitionStatusLabel(c.status)
                            }}</span>
                        </div>
                        <h2>{{ c.name }}</h2>
                        <p>
                            Penyelenggara:
                            {{ c.organizer || "Belum ditentukan" }} ·
                            {{ c.seasons.length }} musim
                        </p>
                    </div>
                    <div class="actions">
                        <button
                            v-if="can.update"
                            class="secondary-action"
                            @click="editCompetition(c)"
                        >
                            Edit Kompetisi</button
                        ><Link
                            v-if="can.update"
                            class="primary-action"
                            :href="route('admin.competitions.seasons.create', { competition: c.id })"
                        >
                            Tambah Musim
                        </Link>
                    </div>
                </header>
                <div v-if="!c.seasons.length" class="empty">
                    <strong>Belum ada musim</strong
                    ><span
                        >Tambahkan musim pertama agar peserta, jadwal, dan
                        klasemen dapat dikelola.</span
                    >
                </div>
                <div v-else class="season-table">
                    <div class="season-head">
                        <span>Musim</span><span>Periode</span
                        ><span>Progres</span><span>Peserta</span
                        ><span>Status</span><span>Aksi</span>
                    </div>
                    <div v-for="s in c.seasons" :key="s.id" class="season">
                        <div class="season-name">
                            <em v-if="s.is_featured">Musim Utama</em>
                            <h3>{{ s.name }}</h3>
                        </div>
                        <div data-label="Periode">
                            <strong>{{ formatDate(s.starts_at) }}</strong
                            ><small>sampai {{ formatDate(s.ends_at) }}</small>
                        </div>
                        <div data-label="Progres">
                            <strong
                                >Matchday {{ s.current_matchday }} dari
                                {{ s.total_matchdays }}</strong
                            ><small
                                >{{
                                    s.total_matchdays
                                        ? Math.round(
                                              (s.current_matchday /
                                                  s.total_matchdays) *
                                                  100,
                                          )
                                        : 0
                                }}% berjalan</small
                            >
                        </div>
                        <div data-label="Peserta">
                            <strong>{{ s.participants_count }} klub</strong
                            ><small
                                >Kapasitas
                                {{
                                    s.max_participants || "tidak dibatasi"
                                }}</small
                            >
                        </div>
                        <div data-label="Status">
                            <span
                                class="season-status"
                                :class="`is-${s.status}`"
                                >{{ seasonStatusLabel(s.status) }}</span
                            >
                        </div>
                        <div class="season-actions" data-label="Aksi">
                            <button
                                v-if="can.participants"
                                @click="editParticipants(c, s)"
                            >
                                Kelola Peserta</button
                            ><button
                                v-if="can.update"
                                @click="editSeason(c, s)"
                            >
                                Edit Musim
                            </button>
                        </div>
                    </div>
                </div>
            </article>
            <div v-if="modal" class="modal" @click.self="modal = ''">
                <form
                    class="dialog"
                    @submit.prevent="
                        modal === 'competition'
                            ? saveCompetition()
                            : modal === 'season'
                              ? saveSeason()
                              : saveParticipants()
                    "
                >
                    <header>
                        <h2>
                            {{
                                modal === "competition"
                                    ? current
                                        ? "Edit Kompetisi"
                                        : "Tambah Kompetisi"
                                    : modal === "season"
                                      ? "Pengaturan Musim"
                                      : "Peserta Musim"
                            }}
                        </h2>
                        <button type="button" @click="modal = ''">×</button>
                    </header>
                    <section
                        v-if="modal === 'competition'"
                        class="competition-editor"
                    >
                        <div class="editor-intro">
                            <span>Data Kompetisi</span>
                            <div>
                                <h3>Informasi Utama</h3>
                                <p>
                                    Data berikut digunakan pada halaman liga dan
                                    pengelolaan musim.
                                </p>
                            </div>
                        </div>
                        <div class="fields competition-fields">
                            <label
                                ><span>Nama kompetisi <b>Wajib</b></span
                                ><input
                                    v-model="cf.name"
                                    placeholder="Contoh: COSMIC League"
                                /><small
                                    >Nama lengkap yang tampil pada halaman liga
                                    dan daftar kompetisi.</small
                                ><em v-if="cf.errors.name">{{
                                    cf.errors.name
                                }}</em></label
                            >
                            <label
                                ><span>Singkatan kompetisi <b>Wajib</b></span
                                ><input
                                    v-model="cf.short_name"
                                    maxlength="20"
                                    placeholder="Contoh: CSL"
                                /><small
                                    >Nama pendek untuk kartu, tabel, dan ruang
                                    tampilan terbatas.</small
                                ><em v-if="cf.errors.short_name">{{
                                    cf.errors.short_name
                                }}</em></label
                            >
                            <label
                                ><span>Format kompetisi <b>Wajib</b></span
                                ><select v-model="cf.type">
                                    <option value="league">Liga</option>
                                    <option value="tournament">Turnamen</option>
                                    <option value="friendly_series">
                                        Seri pertandingan persahabatan
                                    </option>
                                    <option value="single_event">
                                        Pertandingan tunggal
                                    </option></select
                                ><small
                                    >Menentukan jenis penyelenggaraan
                                    kompetisi.</small
                                ><em v-if="cf.errors.type">{{
                                    cf.errors.type
                                }}</em></label
                            >
                            <label
                                ><span>Penyelenggara <i>Opsional</i></span
                                ><input
                                    v-model="cf.organizer"
                                    placeholder="Contoh: COSMIC"
                                /><small
                                    >Nama organisasi atau pihak yang
                                    menyelenggarakan kompetisi.</small
                                ><em v-if="cf.errors.organizer">{{
                                    cf.errors.organizer
                                }}</em></label
                            >
                            <label
                                ><span>Status kompetisi <b>Wajib</b></span
                                ><select v-model="cf.status">
                                    <option value="draft">
                                        Draft — belum siap digunakan
                                    </option>
                                    <option value="active">
                                        Aktif — dapat digunakan
                                    </option>
                                    <option value="inactive">
                                        Nonaktif — sementara tidak digunakan
                                    </option>
                                    <option value="archived">
                                        Diarsipkan — tidak lagi aktif
                                    </option></select
                                ><small
                                    >Status menentukan ketersediaan kompetisi di
                                    dashboard.</small
                                ><em v-if="cf.errors.status">{{
                                    cf.errors.status
                                }}</em></label
                            >
                            <label
                                ><span>Website kompetisi <i>Opsional</i></span
                                ><input
                                    v-model="cf.website_url"
                                    type="url"
                                    placeholder="https://contoh.com"
                                /><small
                                    >Tautan situs resmi kompetisi, termasuk
                                    https://.</small
                                ><em v-if="cf.errors.website_url">{{
                                    cf.errors.website_url
                                }}</em></label
                            >
                            <label class="full"
                                ><span>Deskripsi kompetisi <i>Opsional</i></span
                                ><textarea
                                    v-model="cf.description"
                                    rows="4"
                                    placeholder="Jelaskan format, tujuan, atau informasi penting kompetisi..."
                                /><small
                                    >Ringkasan publik yang membantu pengunjung
                                    memahami kompetisi.</small
                                ><em v-if="cf.errors.description">{{
                                    cf.errors.description
                                }}</em></label
                            >
                        </div>
                        <div class="editor-intro media-heading">
                            <span>Media</span>
                            <div>
                                <h3>Logo dan Banner</h3>
                                <p>
                                    Pilih gambar dari Media Library dan periksa
                                    tampilannya sebelum disimpan.
                                </p>
                            </div>
                        </div>
                        <div class="fields competition-fields">
                            <div class="media-picker-group">
                                <span class="field-label"
                                    >Logo kompetisi <i>Opsional</i></span
                                >
                                <MediaPicker
                                    v-model="cf.logo_media_id"
                                    value-mode="id"
                                    folder="all"
                                    upload-folder="competitions"
                                    media-type="image"
                                    label="Pilih Logo dari Media Library"
                                    :preview-url="selectedLogo?.url || ''"
                                    :can-browse="can.browse_media"
                                    :can-upload="can.upload_media"
                                    @selected="selectedLogo = $event"
                                />
                                <small
                                    >Gunakan logo persegi atau transparan agar
                                    tampil baik pada kartu dan tabel.</small
                                >
                                <em v-if="cf.errors.logo_media_id">{{
                                    cf.errors.logo_media_id
                                }}</em>
                            </div>
                            <div class="media-picker-group">
                                <span class="field-label"
                                    >Banner kompetisi <i>Opsional</i></span
                                >
                                <MediaPicker
                                    v-model="cf.banner_media_id"
                                    value-mode="id"
                                    folder="all"
                                    upload-folder="competitions"
                                    media-type="image"
                                    label="Pilih Banner dari Media Library"
                                    :preview-url="selectedBanner?.url || ''"
                                    :can-browse="can.browse_media"
                                    :can-upload="can.upload_media"
                                    @selected="selectedBanner = $event"
                                />
                                <small
                                    >Gunakan gambar horizontal untuk header
                                    atau hero kompetisi.</small
                                >
                                <em v-if="cf.errors.banner_media_id">{{
                                    cf.errors.banner_media_id
                                }}</em>
                            </div>
                            <div class="media-previews full">
                                <figure>
                                    <div class="logo-preview">
                                        <img
                                            v-if="cf.logo_media_id"
                                            :src="
                                                selectedLogo?.url ||
                                                media.find(
                                                    (x) =>
                                                        Number(x.id) ===
                                                        Number(cf.logo_media_id),
                                                )?.url
                                            "
                                        /><b v-else>{{
                                            cf.short_name || "LOGO"
                                        }}</b>
                                    </div>
                                    <figcaption>Pratinjau logo</figcaption>
                                </figure>
                                <figure>
                                    <div class="banner-preview">
                                        <img
                                            v-if="cf.banner_media_id"
                                            :src="
                                                selectedBanner?.url ||
                                                media.find(
                                                    (x) =>
                                                        Number(x.id) ===
                                                        Number(cf.banner_media_id),
                                                )?.url
                                            "
                                        /><span v-else>Tanpa banner</span>
                                    </div>
                                    <figcaption>Pratinjau banner</figcaption>
                                </figure>
                            </div>
                        </div>
                    </section>
                    <SeasonEditor v-if="modal === 'season'" :form="sf" />
                    <div v-if="false" class="fields">
                        <label>Nama musim<input v-model="sf.name" /></label
                        ><label>Slug<input v-model="sf.slug" /></label
                        ><label
                            >Mulai<input
                                v-model="sf.starts_at"
                                type="date" /></label
                        ><label
                            >Selesai<input
                                v-model="sf.ends_at"
                                type="date" /></label
                        ><label
                            >Format<select v-model="sf.format">
                                <option>league</option>
                                <option>group</option>
                                <option>knockout</option>
                                <option>hybrid</option>
                                <option>friendly</option>
                            </select></label
                        ><label
                            >Status<select v-model="sf.status">
                                <option>draft</option>
                                <option>registration</option>
                                <option>scheduled</option>
                                <option>ongoing</option>
                                <option>completed</option>
                                <option>cancelled</option>
                            </select></label
                        ><label
                            >Matchday<input
                                v-model="sf.current_matchday"
                                type="number" /></label
                        ><label
                            >Total matchday<input
                                v-model="sf.total_matchdays"
                                type="number" /></label
                        ><label
                            >Kapasitas<input
                                v-model="sf.max_participants"
                                type="number" /></label
                        ><label
                            >Poin menang<input
                                v-model="sf.win_points"
                                type="number" /></label
                        ><label
                            >Poin seri<input
                                v-model="sf.draw_points"
                                type="number" /></label
                        ><label
                            >Poin kalah<input
                                v-model="sf.loss_points"
                                type="number" /></label
                        ><label class="check full"
                            ><input v-model="sf.is_featured" type="checkbox" />
                            Featured season</label
                        >
                        <div class="full rules">
                            <h3>Regulasi</h3>
                            <div v-for="(r, i) in sf.regulations" :key="i">
                                <input
                                    v-model="r.title"
                                    placeholder="Judul"
                                /><textarea
                                    v-model="r.body"
                                    placeholder="Isi"
                                /><button
                                    type="button"
                                    @click="sf.regulations.splice(i, 1)"
                                >
                                    ×
                                </button>
                            </div>
                            <button
                                type="button"
                                @click="
                                    sf.regulations.push({
                                        title: '',
                                        body: '',
                                        active: true,
                                    })
                                "
                            >
                                + Regulasi
                            </button>
                        </div>
                    </div>
                    <ParticipantManager
                        v-if="modal === 'participants'"
                        :form="pf"
                        :clubs="clubs"
                        :competition="current"
                        :season="season"
                    />
                    <div class="errors">
                        <p
                            v-for="(e, k) in modal === 'competition'
                                ? cf.errors
                                : modal === 'season'
                                  ? sf.errors
                                  : pf.errors"
                            :key="k"
                        >
                            {{ e }}
                        </p>
                    </div>
                    <footer>
                        <button
                            type="button"
                            class="cancel"
                            @click="modal = ''"
                        >
                            Batal</button
                        ><button
                            :disabled="
                                (modal === 'competition' && cf.processing) ||
                                (modal === 'season' && sf.processing) ||
                                (modal === 'participants' &&
                                    (pf.processing ||
                                        ([
                                            'scheduled',
                                            'ongoing',
                                            'completed',
                                        ].includes(season?.status) &&
                                            season?.participants_count > 0)))
                            "
                        >
                            {{
                                modal === "competition"
                                    ? cf.processing
                                        ? "Menyimpan..."
                                        : "Simpan Kompetisi"
                                    : modal === "season"
                                      ? sf.processing
                                          ? "Menyimpan..."
                                          : "Simpan Musim"
                                      : modal === "participants"
                                        ? pf.processing
                                            ? "Menyimpan Peserta..."
                                            : "Simpan Daftar Peserta"
                                        : "Simpan"
                            }}
                        </button>
                    </footer>
                </form>
            </div>
        </div></AuthenticatedLayout
    >
</template>
<style scoped>
.page {
    max-width: 1450px;
    margin: auto;
}
.notice {
    padding: 12px;
    margin-bottom: 12px;
    border: 1px solid #45b7ff55;
}
.hero {
    min-height: 190px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    padding: 34px;
    border: 1px solid #afdaff24;
    background: radial-gradient(circle at 80% 20%, #45b7ff22, transparent 28%),
        #07111e;
}
.hero span {
    color: var(--color-accent);
    font: 800 0.55rem var(--font-display);
}
.hero h1 {
    margin: 8px 0;
    font: 900 clamp(3rem, 5vw, 5rem) / 0.85 var(--font-display);
    text-transform: uppercase;
}
.hero h1 b {
    color: var(--color-accent);
}
.hero p {
    margin: 0;
    color: #82929e;
}
.hero button,
.hero-action,
.filters button,
.dialog footer button {
    padding: 12px 16px;
    border: 0;
    color: #03101b;
    background: var(--color-accent);
    font: 800 0.55rem var(--font-display);
    text-transform: uppercase;
    text-decoration: none;
}
.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin: 14px 0;
}
.stats article {
    padding: 18px;
    border: 1px solid #ffffff18;
    background: #07111e;
}
.stats b,
.stats span {
    display: block;
}
.stats b {
    font: 900 1.5rem var(--font-display);
}
.stats span {
    color: #71828e;
}
.filter-panel {
    margin-bottom: 14px;
    border: 1px solid #ffffff18;
    background: #07111e;
}
.filter-panel > header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    padding: 16px 18px;
    border-bottom: 1px solid #ffffff12;
}
.filter-panel > header span {
    color: var(--color-accent);
    font: 800 0.48rem var(--font-display);
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.filter-panel > header h2 {
    margin: 4px 0 0;
    font: 800 0.9rem var(--font-display);
    text-transform: uppercase;
}
.filter-panel > header strong {
    color: #79909f;
    font-size: 0.75rem;
    font-weight: 500;
}
.filters {
    display: grid;
    grid-template-columns:
        minmax(280px, 1.35fr) minmax(190px, 0.8fr) minmax(190px, 0.8fr)
        auto;
    align-items: start;
    gap: 10px;
    padding: 16px 18px;
}
.filters label {
    display: flex;
    min-width: 0;
    flex-direction: column;
}
.filters label > span {
    margin-bottom: 7px;
    color: #91a4b0;
    font: 750 0.52rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.filters label > small {
    min-height: 27px;
    margin-top: 6px;
    color: #617784;
    font-size: 0.68rem;
    line-height: 1.35;
}
.filters input,
.filters select {
    box-sizing: border-box;
    width: 100%;
    min-height: 44px;
}
.filter-actions {
    display: flex;
    gap: 7px;
    padding-top: 20px;
}
.filter-actions button {
    min-height: 44px;
    white-space: nowrap;
}
.filter-actions .reset {
    border: 1px solid #ffffff25;
    color: white;
    background: transparent;
}
.filters input,
.filters select,
.fields input,
.fields select,
.fields textarea,
.rules input,
.rules textarea {
    padding: 12px;
    border: 1px solid #ffffff20;
    color: white;
    background: #07111e;
}
.competition {
    margin-bottom: 12px;
    border: 1px solid #ffffff18;
    background: #07111e;
}
.competition > header {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 18px;
}
.competition-header {
    min-height: 98px;
}
.competition-info {
    min-width: 0;
}
.logo {
    width: 66px;
    height: 66px;
    display: grid;
    place-items: center;
    border: 1px solid #45b7ff66;
    border-radius: 50%;
    color: var(--color-accent);
    font: 900 0.9rem var(--font-display);
}
.logo img {
    width: 48px;
    height: 48px;
    object-fit: contain;
}
.badges {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.badges span {
    padding: 4px 7px;
    border: 1px solid #ffffff1f;
    color: #8498a5;
    font: 750 0.47rem var(--font-display);
    text-transform: uppercase;
}
.badges .status-active {
    border-color: #45b7ff55;
    color: var(--color-accent);
}
.competition h2 {
    margin: 4px 0;
    font: 900 2rem var(--font-display);
    text-transform: uppercase;
}
.competition p,
.competition small {
    margin: 0;
    color: #71828e;
}
.actions {
    display: flex;
    gap: 6px;
    margin-left: auto;
}
.actions button,
.actions a,
.season button {
    min-height: 36px;
    padding: 0 11px;
    border: 1px solid #ffffff20;
    color: white;
    background: none;
    font: 750 0.52rem var(--font-display);
    letter-spacing: 0.04em;
    text-transform: uppercase;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}
.actions .primary-action {
    border-color: var(--color-accent);
    color: #03101b;
    background: var(--color-accent);
}
.actions button:hover,
.actions a:hover,
.season button:hover {
    border-color: #45b7ff88;
    color: var(--color-accent);
}
.actions .primary-action:hover {
    color: #03101b;
    background: #75caff;
}
.empty {
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 22px;
    border-top: 1px solid #ffffff12;
}
.empty strong {
    font: 800 0.7rem var(--font-display);
    text-transform: uppercase;
}
.empty span {
    color: #71828e;
    font-size: 0.78rem;
}
.season-table {
    border-top: 1px solid #ffffff12;
}
.season-head,
.season {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr)
        minmax(0, 1.1fr)
        minmax(0, 1fr)
        minmax(0, 0.65fr)
        minmax(0, 0.8fr)
        128px;
    align-items: center;
    gap: 14px;
    box-sizing: border-box;
}
.season-head {
    padding: 11px 20px;
    color: #617684;
    background: #050c14;
    font: 750 0.47rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.season-head > span:last-child {
    text-align: right;
}
.season {
    padding: 16px 20px;
    border-top: 1px solid #ffffff12;
}
.season:first-of-type {
    border-top: 0;
}
.season > *,
.season-head > * {
    min-width: 0;
}
.season h3 {
    margin: 3px 0;
    font: 900 1.2rem var(--font-display);
    text-transform: uppercase;
}
.season em {
    display: inline-block;
    margin-bottom: 5px;
    padding: 3px 6px;
    border: 1px solid #45b7ff55;
    color: var(--color-accent);
    font: 750 0.44rem var(--font-display);
    font-style: normal;
    text-transform: uppercase;
}
.season small {
    display: block;
}
.season strong {
    display: block;
    color: #dce7ed;
    font-size: 0.76rem;
    font-weight: 600;
}
.season small {
    margin-top: 5px;
    color: #667c89;
    font-size: 0.7rem;
}
.season-status {
    display: inline-flex;
    padding: 5px 8px;
    border: 1px solid #ffffff25;
    color: #91a4b0;
    font: 750 0.47rem var(--font-display);
    text-transform: uppercase;
}
.season-status.is-ongoing,
.season-status.is-scheduled {
    border-color: #45b7ff55;
    color: var(--color-accent);
}
.season-actions {
    display: flex;
    flex-direction: column;
    gap: 6px;
    justify-content: flex-end;
    width: 128px;
}
.season-actions button {
    width: 100%;
    flex: 0 0 36px;
    padding-right: 6px;
    padding-left: 6px;
    font-size: 0.46rem;
    white-space: normal;
}
.modal {
    position: fixed;
    z-index: 100;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 16px;
    background: #01050add;
}
.dialog {
    width: min(850px, 100%);
    max-height: 92vh;
    overflow: auto;
    border: 1px solid #45b7ff55;
    background: #07111e;
}
.dialog > header {
    display: flex;
    justify-content: space-between;
    padding: 20px;
    border-bottom: 1px solid #ffffff18;
}
.dialog h2 {
    margin: 0;
    font: 900 1.4rem var(--font-display);
}
.dialog header button {
    border: 0;
    color: white;
    background: none;
    font-size: 1.5rem;
}
.fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    padding: 20px;
}
.fields label {
    display: grid;
    gap: 7px;
    color: #8fa0ac;
    font: 700 0.53rem var(--font-display);
    text-transform: uppercase;
}
.competition-editor {
    padding: 22px;
}
.competition-editor .fields {
    padding: 0;
}
.editor-intro {
    display: flex;
    gap: 13px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #ffffff14;
}
.editor-intro > span {
    display: grid;
    min-width: 58px;
    height: 34px;
    place-items: center;
    border: 1px solid #45b7ff55;
    color: var(--color-accent);
    font: 800 0.5rem var(--font-display);
    text-transform: uppercase;
}
.editor-intro h3 {
    margin: 0;
    color: white;
    font: 800 0.8rem var(--font-display);
    text-transform: uppercase;
}
.editor-intro p {
    margin: 5px 0 0;
    color: #718895;
    font-size: 0.76rem;
}
.media-heading {
    margin-top: 26px;
}
.competition-fields {
    gap: 20px 18px;
}
.competition-fields label {
    display: flex;
    min-width: 0;
    flex-direction: column;
    color: #d8e2e9;
}
.competition-fields label > span {
    min-height: 17px;
    font: 750 0.62rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.competition-fields label > span b,
.competition-fields label > span i {
    margin-left: 6px;
    color: var(--color-accent);
    font: inherit;
    font-size: 0.48rem;
    font-style: normal;
}
.competition-fields label > span i {
    color: #6f8391;
}
.competition-fields input,
.competition-fields select,
.competition-fields textarea {
    box-sizing: border-box;
    width: 100%;
    min-height: 46px;
    margin-top: 8px;
}
.competition-fields textarea {
    resize: vertical;
}
.competition-fields label > small {
    min-height: 30px;
    margin-top: 7px;
    color: #718895;
    font: 400 0.72rem/1.45 var(--font-body);
    text-transform: none;
}
.competition-fields label > em {
    margin-top: 5px;
    color: #ff8e97;
    font: 500 0.7rem var(--font-body);
    font-style: normal;
    text-transform: none;
}
.media-picker-group {
    display: flex;
    min-width: 0;
    flex-direction: column;
}
.media-picker-group .field-label {
    min-height: 17px;
    color: #d8e2e9;
    font: 750 0.62rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.media-picker-group .field-label i {
    margin-left: 6px;
    color: #6f8391;
    font: inherit;
    font-size: 0.48rem;
    font-style: normal;
}
.media-picker-group > small {
    min-height: 30px;
    margin-top: 7px;
    color: #718895;
    font: 400 0.72rem/1.45 var(--font-body);
}
.media-picker-group > em {
    margin-top: 5px;
    color: #ff8e97;
    font: 500 0.7rem var(--font-body);
    font-style: normal;
}
.media-previews {
    display: grid;
    grid-template-columns: 180px 1fr;
    gap: 14px;
}
.media-previews figure {
    min-width: 0;
    margin: 0;
    border: 1px solid #ffffff18;
    background: #030a12;
}
.media-previews figcaption {
    padding: 9px;
    color: #718895;
    font-size: 0.68rem;
    text-align: center;
}
.logo-preview,
.banner-preview {
    display: grid;
    height: 150px;
    place-items: center;
    overflow: hidden;
    color: #718895;
}
.logo-preview img {
    width: 100px;
    height: 100px;
    object-fit: contain;
}
.logo-preview b {
    color: var(--color-accent);
    font: 900 1.2rem var(--font-display);
}
.banner-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.media-empty {
    padding: 13px;
    border: 1px dashed #45b7ff55;
    color: #89a0ae;
    background: #45b7ff08;
    font-size: 0.78rem;
}
.full {
    grid-column: 1/-1;
}
.check {
    display: flex !important;
}
.rules > div {
    display: grid;
    grid-template-columns: 1fr 2fr auto;
    gap: 6px;
    margin-bottom: 6px;
}
.rules button,
.picker button {
    padding: 10px;
    border: 1px solid #ffffff20;
    color: white;
    background: none;
}
.picker {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    padding: 20px;
}
.picker button {
    display: grid;
    text-align: left;
}
.picker button.selected {
    border-color: var(--color-accent);
    background: #45b7ff12;
}
.picker b {
    color: var(--color-accent);
}
.errors {
    margin: 0 20px;
    color: #ff9da4;
}
.dialog footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 15px 20px;
    border-top: 1px solid #ffffff18;
}
.dialog .cancel {
    color: white;
    background: transparent;
    border: 1px solid #ffffff20;
}
@media (max-width: 700px) {
    .hero {
        align-items: flex-start;
        flex-direction: column;
    }
    .filters,
    .fields {
        grid-template-columns: 1fr;
    }
    .filter-panel > header {
        align-items: flex-start;
        flex-direction: column;
        gap: 7px;
    }
    .filter-actions {
        padding-top: 0;
    }
    .full {
        grid-column: auto;
    }
    .competition-editor {
        padding: 17px;
    }
    .competition-fields label > small {
        min-height: 0;
    }
    .media-previews {
        grid-template-columns: 1fr;
    }
    .competition-header {
        align-items: flex-start;
        flex-wrap: wrap;
    }
    .competition-info {
        flex: 1;
    }
    .season-head {
        display: none;
    }
    .season {
        grid-template-columns: 1fr 1fr;
        gap: 18px 12px;
    }
    .season > div[data-label]::before {
        content: attr(data-label);
        display: block;
        margin-bottom: 7px;
        color: #5e7482;
        font: 750 0.45rem var(--font-display);
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .season-name,
    .season-actions {
        grid-column: 1/-1;
    }
    .season-actions {
        justify-content: flex-start;
        width: auto;
    }
    .picker {
        grid-template-columns: 1fr 1fr;
    }
    .competition > header {
        flex-wrap: wrap;
    }
    .actions {
        width: 100%;
        margin: 0;
    }
}
@media (max-width: 450px) {
    .stats,
    .season,
    .picker {
        grid-template-columns: 1fr;
    }
    .filter-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }
    .season-name,
    .season-actions {
        grid-column: auto;
    }
    .season-actions {
        display: flex;
    }
}
</style>
