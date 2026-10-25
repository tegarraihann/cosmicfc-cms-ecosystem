<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ConfirmActionModal from "@/Components/Admin/ConfirmActionModal.vue";
import MediaPicker from "@/Components/Admin/MediaPicker.vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
const props = defineProps({
    clubs: Object,
    filters: Object,
    media: Array,
    can: Object,
});
const page = usePage(),
    open = ref(false),
    editing = ref(null),
    deleting = ref(null),
    deleteProcessing = ref(false),
    deleteError = ref(""),
    selectedPrimaryLogo = ref(null),
    tab = ref("identity"),
    filters = ref({
        search: props.filters.search || "",
        type: props.filters.type || "",
        status: props.filters.status || "",
    });
const blank = () => ({
    official_name: "",
    display_name: "",
    short_name: "",
    type: "opponent",
    city: "Pekanbaru",
    country: "Indonesia",
    founded_year: null,
    primary_media_id: null,
    primary_color: "#45B7FF",
    secondary_color: "#FFFFFF",
    website_url: "",
    instagram_url: "",
    contact_email: "",
    default_venue: "",
    is_cosmic: false,
    status: "active",
    aliases: [],
});
const form = useForm(blank());
const aliasesText = ref("");
function edit(club = null) {
    editing.value = club;
    selectedPrimaryLogo.value = club?.primary_media_id
        ? props.media.find(
              (item) => Number(item.id) === Number(club.primary_media_id),
          ) || null
        : null;
    form.defaults(club ? { ...blank(), ...club } : blank());
    form.reset();
    form.clearErrors();
    aliasesText.value = (club?.aliases || []).join("\n");
    tab.value = "identity";
    open.value = true;
}
function save() {
    form.aliases = aliasesText.value
        .split("\n")
        .map((x) => x.trim())
        .filter(Boolean);
    const opt = { preserveScroll: true, onSuccess: () => (open.value = false) };
    editing.value
        ? form.put(route("admin.clubs.update", editing.value.id), opt)
        : form.post(route("admin.clubs.store"), opt);
}
function search() {
    router.get(route("admin.clubs.index"), filters.value, {
        preserveState: true,
        replace: true,
    });
}
function archiveClub() {
    if (confirm("Arsipkan klub ini?"))
        router.post(
            route("admin.clubs.archive", editing.value.id),
            {},
            { onSuccess: () => (open.value = false) },
        );
}
function askDelete(club) {
    deleting.value = club;
    deleteError.value = "";
}
function closeDelete() {
    if (deleteProcessing.value) return;
    deleting.value = null;
    deleteError.value = "";
}
function remove() {
    if (!deleting.value || deleteProcessing.value) return;
    deleteProcessing.value = true;
    deleteError.value = "";
    router.delete(route("admin.clubs.destroy", deleting.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleting.value = null;
            deleteError.value = "";
        },
        onError: (errors) => {
            deleteError.value =
                errors.club || "Klub gagal dihapus. Silakan coba kembali.";
        },
        onFinish: () => (deleteProcessing.value = false),
    });
}
const logo = (club) => club.logo_url || null;
const typeLabel = (type) =>
    ({
        internal: "Internal COSMIC",
        opponent: "Klub lawan",
        community: "Komunitas",
        guest: "Klub tamu",
    })[type] || type;
const statusLabel = (status) =>
    ({
        draft: "Draft",
        active: "Aktif",
        inactive: "Nonaktif",
        archived: "Diarsipkan",
    })[status] || status;
</script>
<template>
    <Head title="Klub & Lawan" /><AuthenticatedLayout
        ><template #page-title>Klub & Lawan</template>
        <div class="page">
            <div v-if="page.props.flash?.success" class="notice">
                {{ page.props.flash.success }}
            </div>
            <div v-if="page.props.errors?.club" class="error">
                {{ page.props.errors.club }}
            </div>
            <header class="hero">
                <div>
                    <span>Club Directory</span>
                    <h1>Clubs & <b>Opponents.</b></h1>
                    <p>
                        Satu sumber identitas untuk COSMIC, seluruh lawan, dan
                        peserta kompetisi.
                    </p>
                </div>
                <button v-if="can.create" @click="edit()">+ Tambah Klub</button>
            </header>
            <section class="summary">
                <article>
                    <strong>{{ clubs.total }}</strong
                    ><small>Total klub</small>
                </article>
                <article>
                    <strong>{{
                        clubs.data.filter((x) => x.status === "active").length
                    }}</strong
                    ><small>Aktif</small>
                </article>
                <article>
                    <strong>{{
                        clubs.data.filter((x) => x.type === "opponent").length
                    }}</strong
                    ><small>Lawan</small>
                </article>
                <article>
                    <strong>{{
                        clubs.data.find((x) => x.is_cosmic)?.short_name || "–"
                    }}</strong
                    ><small>Klub internal</small>
                </article>
            </section>
            <form class="filters" @submit.prevent="search">
                <input
                    v-model="filters.search"
                    placeholder="Cari klub, singkatan, atau alias..."
                /><select v-model="filters.type">
                    <option value="">Semua tipe</option>
                    <option value="internal">Klub internal COSMIC</option>
                    <option value="opponent">Klub lawan</option>
                    <option value="community">Klub komunitas</option>
                    <option value="guest">Klub tamu</option></select
                ><select v-model="filters.status">
                    <option value="">Semua status</option>
                    <option value="draft">Draft</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Nonaktif</option>
                    <option value="archived">Diarsipkan</option></select
                ><button>Cari</button>
            </form>
            <section class="grid">
                <article
                    v-for="club in clubs.data"
                    :key="club.id"
                    class="club-card"
                >
                    <button class="card" type="button" @click="edit(club)">
                        <div
                            class="crest"
                            :style="{
                                borderColor: club.primary_color,
                                background: `linear-gradient(145deg,${club.primary_color}33,${club.secondary_color}12)`,
                            }"
                        >
                            <img v-if="logo(club)" :src="logo(club)" /><span
                                v-else
                                >{{ club.short_name }}</span
                            >
                        </div>
                        <div class="info">
                            <div class="labels">
                                <span>{{ typeLabel(club.type) }}</span
                                ><span :class="club.status">{{
                                    statusLabel(club.status)
                                }}</span>
                            </div>
                            <h2>{{ club.display_name }}</h2>
                            <strong
                                >{{ club.short_name }} ·
                                {{ club.city || "Kota belum diisi" }}</strong
                            ><small
                                >{{ club.aliases.length }} alias
                                <b v-if="club.is_cosmic"
                                    >· COSMIC INTERNAL</b
                                ></small
                            >
                        </div>
                    </button>
                    <button
                        v-if="can.delete"
                        type="button"
                        class="delete-card"
                        :aria-label="`Hapus ${club.display_name}`"
                        title="Hapus klub"
                        @click.stop="askDelete(club)"
                    >
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path
                                d="M4 7h16M9 7V4h6v3m3 0-1 13H7L6 7m4 4v5m4-5v5"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </button>
                </article>
            </section>
            <div v-if="open" class="modal" @click.self="open = false">
                <form class="dialog" @submit.prevent="save">
                    <header>
                        <div>
                            <span>Pengelolaan Klub</span>
                            <h2>{{ editing ? "Edit" : "Tambah" }} Klub</h2>
                        </div>
                        <button type="button" @click="open = false">×</button>
                    </header>
                    <nav>
                        <button
                            v-for="item in [
                                ['identity', 'Identitas & Branding'],
                                ['location', 'Lokasi & Informasi'],
                                ['contact', 'Kontak & Nama Lain'],
                            ]"
                            :key="item[0]"
                            type="button"
                            :class="{ active: tab === item[0] }"
                            @click="tab = item[0]"
                        >
                            {{ item[1] }}
                        </button>
                    </nav>
                    <section v-show="tab === 'identity'" class="tab-panel">
                        <div class="section-heading">
                            <span>01</span>
                            <div>
                                <h3>Identitas Klub</h3>
                                <p>
                                    Informasi utama untuk mengenali klub di
                                    website dan pertandingan.
                                </p>
                            </div>
                        </div>
                        <div class="fields">
                            <label
                                ><span>Nama resmi klub <b>Wajib</b></span
                                ><input
                                    v-model="form.official_name"
                                    placeholder="Contoh: Cosmic Mini Soccer Club"
                                /><small
                                    >Nama lengkap klub untuk data resmi.</small
                                ><em v-if="form.errors.official_name">{{
                                    form.errors.official_name
                                }}</em></label
                            >
                            <label
                                ><span>Nama klub di website <b>Wajib</b></span
                                ><input
                                    v-model="form.display_name"
                                    placeholder="Contoh: COSMIC"
                                /><small
                                    >Nama yang tampil pada jadwal, hasil, dan
                                    kartu klub.</small
                                ><em v-if="form.errors.display_name">{{
                                    form.errors.display_name
                                }}</em></label
                            >
                            <label
                                ><span>Singkatan klub <b>Wajib</b></span
                                ><input
                                    v-model="form.short_name"
                                    maxlength="12"
                                    placeholder="Contoh: CSM"
                                /><small
                                    >Gunakan 2–12 huruf atau angka untuk ruang
                                    terbatas.</small
                                ><em v-if="form.errors.short_name">{{
                                    form.errors.short_name
                                }}</em></label
                            >
                            <label
                                ><span>Jenis klub <b>Wajib</b></span
                                ><select v-model="form.type">
                                    <option value="internal">
                                        Klub internal COSMIC
                                    </option>
                                    <option value="opponent">Klub lawan</option>
                                    <option value="community">
                                        Klub komunitas
                                    </option>
                                    <option value="guest">
                                        Klub tamu
                                    </option></select
                                ><small
                                    >Menentukan peran klub di dalam
                                    sistem.</small
                                ><em v-if="form.errors.type">{{
                                    form.errors.type
                                }}</em></label
                            >
                            <label
                                ><span>Status klub <b>Wajib</b></span
                                ><select v-model="form.status">
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
                                    >Menentukan ketersediaan klub di
                                    dashboard.</small
                                ><em v-if="form.errors.status">{{
                                    form.errors.status
                                }}</em></label
                            >
                            <div class="checks full">
                                <label class="check-card"
                                    ><input
                                        v-model="form.is_cosmic"
                                        type="checkbox"
                                    /><span
                                        ><strong
                                            >Tetapkan sebagai klub
                                            COSMIC</strong
                                        ><small
                                            >Klub menjadi identitas utama
                                            COSMIC. Hanya satu klub yang dapat
                                            dipilih.</small
                                        ></span
                                    ></label
                                >
                            </div>
                        </div>
                    </section>
                    <section v-show="tab === 'identity'" class="tab-panel branding-panel">
                        <div class="section-heading">
                            <span>02</span>
                            <div>
                                <h3>Logo dan Warna Klub</h3>
                                <p>
                                    Identitas visual yang digunakan pada kartu,
                                    jadwal, dan klasemen.
                                </p>
                            </div>
                        </div>
                        <div class="fields">
                            <div class="media-picker-group full">
                                <span class="field-label"
                                    >Logo utama <i>Opsional</i></span
                                >
                                <MediaPicker
                                    v-model="form.primary_media_id"
                                    value-mode="id"
                                    folder="all"
                                    upload-folder="clubs"
                                    media-type="image"
                                    label="Pilih dari Media Library"
                                    :preview-url="selectedPrimaryLogo?.url || ''"
                                    :can-browse="can.browse_media"
                                    :can-upload="can.upload_media"
                                    @selected="selectedPrimaryLogo = $event"
                                />
                                <small
                                    >Logo utama pada jadwal, hasil, klasemen,
                                    dan halaman publik.</small
                                >
                                <em v-if="form.errors.primary_media_id">{{
                                    form.errors.primary_media_id
                                }}</em>
                            </div>
                            <label
                                ><span>Warna utama klub <b>Wajib</b></span
                                ><input
                                    v-model="form.primary_color"
                                    type="color"
                                /><small
                                    >Warna dominan identitas klub.</small
                                ></label
                            ><label
                                ><span>Warna pendamping <b>Wajib</b></span
                                ><input
                                    v-model="form.secondary_color"
                                    type="color"
                                /><small
                                    >Warna pasangan untuk aksen dan
                                    kontras.</small
                                ></label
                            >
                            <div
                                class="brand-preview full"
                                :style="{
                                    borderColor: form.primary_color,
                                    background: `linear-gradient(135deg,${form.primary_color}35,${form.secondary_color}12)`,
                                }"
                            >
                                <img
                                    v-if="form.primary_media_id"
                                    :src="
                                        selectedPrimaryLogo?.url ||
                                        media.find(
                                            (x) =>
                                                Number(x.id) ===
                                                Number(form.primary_media_id),
                                        )?.url
                                    "
                                /><b v-else>{{ form.short_name || "CLUB" }}</b
                                ><span>{{
                                    form.display_name || "Nama Klub"
                                }}</span>
                            </div>
                        </div>
                    </section>
                    <section v-show="tab === 'location'" class="tab-panel">
                        <div class="section-heading">
                            <span>03</span>
                            <div>
                                <h3>Lokasi dan Informasi Klub</h3>
                                <p>
                                    Lengkapi asal klub dan lapangan yang biasa
                                    digunakan.
                                </p>
                            </div>
                        </div>
                        <div class="fields">
                            <label
                                ><span>Kota asal <i>Opsional</i></span
                                ><input
                                    v-model="form.city"
                                    placeholder="Contoh: Pekanbaru"
                                /><small
                                    >Kota yang ditampilkan sebagai asal
                                    klub.</small
                                ></label
                            ><label
                                ><span>Negara <b>Wajib</b></span
                                ><input
                                    v-model="form.country"
                                    placeholder="Contoh: Indonesia"
                                /><small>Negara tempat klub berasal.</small
                                ><em v-if="form.errors.country">{{
                                    form.errors.country
                                }}</em></label
                            ><label
                                ><span>Tahun berdiri <i>Opsional</i></span
                                ><input
                                    v-model="form.founded_year"
                                    type="number"
                                    min="1800"
                                    placeholder="Contoh: 2026"
                                /><small
                                    >Tahun pertama klub resmi berdiri.</small
                                ></label
                            ><label
                                ><span>Lapangan utama <i>Opsional</i></span
                                ><input
                                    v-model="form.default_venue"
                                    placeholder="Contoh: Aero Sport Center"
                                /><small
                                    >Lapangan yang disarankan ketika membuat
                                    pertandingan.</small
                                ></label
                            >
                        </div>
                    </section>
                    <section v-show="tab === 'contact'" class="tab-panel">
                        <div class="section-heading">
                            <span>04</span>
                            <div>
                                <h3>Kontak dan Nama Alternatif</h3>
                                <p>
                                    Tambahkan kanal resmi dan variasi nama untuk
                                    memudahkan pencarian.
                                </p>
                            </div>
                        </div>
                        <div class="fields">
                            <label
                                ><span>Alamat website <i>Opsional</i></span
                                ><input
                                    v-model="form.website_url"
                                    type="url"
                                    placeholder="https://contoh.com"
                                /><small
                                    >Tautan website resmi klub, termasuk
                                    https://.</small
                                ><em v-if="form.errors.website_url">{{
                                    form.errors.website_url
                                }}</em></label
                            ><label
                                ><span>Instagram klub <i>Opsional</i></span
                                ><input
                                    v-model="form.instagram_url"
                                    type="url"
                                    placeholder="https://instagram.com/namaklub"
                                /><small
                                    >Tautan lengkap akun Instagram klub.</small
                                ><em v-if="form.errors.instagram_url">{{
                                    form.errors.instagram_url
                                }}</em></label
                            ><label class="full"
                                ><span>Email kontak klub <i>Opsional</i></span
                                ><input
                                    v-model="form.contact_email"
                                    type="email"
                                    placeholder="kontak@klub.com"
                                /><small
                                    >Email yang dapat digunakan untuk
                                    menghubungi klub.</small
                                ><em v-if="form.errors.contact_email">{{
                                    form.errors.contact_email
                                }}</em></label
                            ><label class="full"
                                ><span>Nama lain klub <i>Opsional</i></span
                                ><textarea
                                    v-model="aliasesText"
                                    rows="6"
                                    placeholder="Contoh:&#10;Cosmic FC&#10;Cosmic Pekanbaru"
                                /><small
                                    >Tulis satu nama lain per baris untuk
                                    membantu pencarian dan mencegah data
                                    ganda.</small
                                ><em v-if="form.errors.aliases">{{
                                    form.errors.aliases
                                }}</em>
                            </label>
                        </div>
                    </section>
                    <div
                        v-if="Object.keys(form.errors).length"
                        class="form-errors"
                    >
                        <p v-for="(message, key) in form.errors" :key="key">
                            {{ message }}
                        </p>
                    </div>
                    <footer>
                        <button
                            v-if="
                                editing &&
                                can.archive &&
                                editing.status !== 'archived'
                            "
                            type="button"
                            class="archive"
                            @click="archiveClub"
                        >
                            Arsipkan</button
                        ><button
                            type="button"
                            class="secondary"
                            @click="open = false"
                        >
                            Batal</button
                        ><button :disabled="form.processing">
                            Simpan Klub
                        </button>
                    </footer>
                </form>
            </div>
            <ConfirmActionModal
                :open="Boolean(deleting)"
                title="Hapus klub?"
                :item-name="deleting?.display_name || ''"
                description="Klub akan dihapus dari daftar aktif dan tidak lagi tersedia untuk digunakan dari dashboard."
                note="Klub COSMIC atau klub yang sudah digunakan dalam pertandingan maupun kompetisi tidak dapat dihapus. Gunakan Arsipkan jika klub hanya ingin dinonaktifkan."
                confirm-label="Hapus Klub"
                processing-label="Menghapus..."
                variant="danger"
                :processing="deleteProcessing"
                :error="deleteError"
                @confirm="remove"
                @cancel="closeDelete"
            /></div
    ></AuthenticatedLayout>
</template>
<style scoped>
.page {
    max-width: 1450px;
    margin: auto;
}
.notice,
.error {
    margin-bottom: 13px;
    padding: 12px 15px;
    border: 1px solid #45b7ff55;
    color: #b8e3ff;
    background: #45b7ff10;
}
.error {
    color: #ffadb3;
    border-color: #ff7d8655;
}
.hero {
    min-height: 190px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    padding: 34px 38px;
    border: 1px solid #afdaff24;
    background: radial-gradient(circle at 78% 30%, #45b7ff20, transparent 25%),
        #07111e;
}
.hero span,
.dialog header span {
    color: var(--color-accent);
    font: 800 0.55rem var(--font-display);
    letter-spacing: 0.17em;
    text-transform: uppercase;
}
.hero h1 {
    margin: 9px 0;
    font: 900 clamp(3rem, 5vw, 5rem) / 0.84 var(--font-display);
    text-transform: uppercase;
}
.hero h1 b {
    color: var(--color-accent);
}
.hero p {
    margin: 0;
    color: #899aa6;
}
.hero button,
.filters button,
.dialog footer button {
    padding: 12px 15px;
    border: 1px solid var(--color-accent);
    color: #03101b;
    background: var(--color-accent);
    font: 800 0.55rem var(--font-display);
    text-transform: uppercase;
}
.summary {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin: 14px 0;
}
.summary article {
    padding: 18px;
    border: 1px solid #afdaff20;
    background: #07111e;
}
.summary strong,
.summary small {
    display: block;
}
.summary strong {
    font: 900 1.6rem var(--font-display);
    text-transform: uppercase;
}
.summary small {
    margin-top: 5px;
    color: #71828e;
    text-transform: uppercase;
}
.filters {
    display: grid;
    grid-template-columns: 1fr 180px 180px auto;
    gap: 8px;
    margin-bottom: 14px;
}
.filters input,
.filters select,
.fields input:not([type="checkbox"]),
.fields select,
.fields textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 12px;
    border: 1px solid #ffffff22;
    color: white;
    background: #07111e;
}
.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 11px;
}
.club-card {
    position: relative;
    min-width: 0;
}
.card {
    width: 100%;
    height: 100%;
    min-height: 175px;
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    border: 1px solid #afdaff20;
    color: white;
    background: #07111e;
    text-align: left;
    transition: 0.2s;
}
.card:hover {
    transform: translateY(-3px);
    border-color: #45b7ff77;
}
.delete-card {
    position: absolute;
    z-index: 2;
    top: 13px;
    right: 13px;
    display: grid;
    width: 36px;
    height: 36px;
    place-items: center;
    border: 1px solid #ff6d7744;
    color: #ff8b93;
    background: #07111ee8;
    transition: 0.2s ease;
}
.delete-card svg {
    width: 17px;
    height: 17px;
}
.delete-card:hover,
.delete-card:focus-visible {
    border-color: #ff6d77;
    color: white;
    background: #c83d47;
    outline: none;
}
.crest {
    width: 94px;
    height: 94px;
    flex: 0 0 94px;
    display: grid;
    place-items: center;
    border: 2px solid var(--color-accent);
    border-radius: 50%;
}
.crest img {
    width: 72px;
    height: 72px;
    object-fit: contain;
}
.crest span {
    font: 900 1.35rem var(--font-display);
}
.info {
    min-width: 0;
}
.labels {
    display: flex;
    gap: 6px;
}
.labels span {
    padding: 4px 6px;
    border: 1px solid #ffffff20;
    color: #7f919f;
    font: 700 0.45rem var(--font-display);
    text-transform: uppercase;
}
.labels .active {
    color: var(--color-accent);
    border-color: #45b7ff55;
}
.info h2 {
    margin: 14px 0 5px;
    font: 900 2rem/0.9 var(--font-display);
    text-transform: uppercase;
}
.info strong {
    display: block;
    color: var(--color-accent);
    font: 0.58rem var(--font-display);
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.info small {
    display: block;
    margin-top: 8px;
    color: #71828e;
}
.info small b {
    color: #d8e8f4;
}
.modal {
    position: fixed;
    z-index: 100;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 18px;
    background: #01050add;
    backdrop-filter: blur(5px);
}
.dialog {
    width: min(850px, 100%);
    max-height: 92vh;
    overflow: auto;
    border: 1px solid #45b7ff48;
    background: #07111e;
}
.dialog > header {
    position: sticky;
    z-index: 2;
    top: 0;
    display: flex;
    justify-content: space-between;
    padding: 19px 22px;
    border-bottom: 1px solid #ffffff18;
    background: #07111efa;
}
.dialog h2 {
    margin: 5px 0 0;
    font: 850 1.35rem var(--font-display);
    text-transform: uppercase;
}
.dialog header button {
    border: 0;
    color: white;
    background: none;
    font-size: 1.6rem;
}
.dialog nav {
    display: flex;
    overflow: auto;
    border-bottom: 1px solid #ffffff18;
}
.dialog nav button {
    padding: 14px 18px;
    border: 0;
    border-bottom: 2px solid transparent;
    color: #778a97;
    background: none;
    font: 750 0.55rem var(--font-display);
    text-transform: uppercase;
    white-space: nowrap;
}
.dialog nav button.active {
    color: white;
    border-color: var(--color-accent);
}
.fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px 18px;
}
.fields label {
    display: flex;
    min-width: 0;
    flex-direction: column;
    color: #d8e2e9;
}
.fields label > span {
    min-height: 17px;
    font: 750 0.62rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.fields label > span b,
.fields label > span i {
    margin-left: 6px;
    color: var(--color-accent);
    font: inherit;
    font-size: 0.48rem;
    font-style: normal;
}
.fields label > span i {
    color: #6f8391;
}
.fields label > input,
.fields label > select,
.fields label > textarea {
    margin-top: 8px;
    min-height: 46px;
}
.fields label > small {
    min-height: 30px;
    margin-top: 7px;
    color: #718895;
    font: 400 0.72rem/1.45 var(--font-body);
    text-transform: none;
}
.fields label > em {
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
.full {
    grid-column: 1/-1;
}
.tab-panel {
    padding: 22px;
}
.branding-panel {
    padding-top: 4px;
}
.section-heading {
    display: flex;
    gap: 13px;
    margin-bottom: 22px;
    padding-bottom: 17px;
    border-bottom: 1px solid #ffffff14;
}
.section-heading > span {
    display: grid;
    width: 34px;
    height: 34px;
    flex: 0 0 34px;
    place-items: center;
    border: 1px solid #45b7ff55;
    color: var(--color-accent);
    font: 800 0.6rem var(--font-display);
}
.section-heading h3 {
    margin: 0;
    color: white;
    font: 800 0.8rem var(--font-display);
    letter-spacing: 0.05em;
    text-transform: uppercase;
}
.section-heading p {
    margin: 5px 0 0;
    color: #718895;
    font-size: 0.78rem;
}
.checks {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
}
.check-card {
    display: grid !important;
    min-height: 74px;
    grid-template-columns: 20px 1fr;
    align-items: center;
    gap: 12px;
    padding: 13px 14px;
    border: 1px solid #ffffff18;
    background: #030a12;
    cursor: pointer;
}
.check-card input[type="checkbox"] {
    appearance: none;
    box-sizing: border-box;
    width: 18px;
    min-width: 18px;
    height: 18px;
    min-height: 18px;
    margin: 0;
    padding: 0;
    border: 1px solid #617887;
    border-radius: 2px;
    background: #07111e;
}
.check-card input[type="checkbox"]::before {
    content: "";
    display: block;
    width: 9px;
    height: 5px;
    margin: 4px 0 0 3px;
    border-bottom: 2px solid #03101b;
    border-left: 2px solid #03101b;
    opacity: 0;
    transform: rotate(-45deg);
}
.check-card input[type="checkbox"]:checked {
    border-color: var(--color-accent);
    background: var(--color-accent);
}
.check-card input[type="checkbox"]:checked::before {
    opacity: 1;
}
.check-card strong,
.check-card small {
    display: block;
    text-transform: none;
}
.check-card strong {
    color: white;
    font: 750 0.66rem var(--font-display);
    text-transform: uppercase;
}
.check-card small {
    min-height: 0 !important;
    margin-top: 5px !important;
    color: #718895;
    font: 400 0.7rem/1.4 var(--font-body) !important;
}
.check-card input {
    margin: 0;
}
.brand-preview {
    min-height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    border: 1px solid;
    padding: 20px;
}
.brand-preview img {
    width: 90px;
    height: 90px;
    object-fit: contain;
}
.brand-preview b {
    font: 900 2rem var(--font-display);
}
.brand-preview span {
    font: 900 2.2rem var(--font-display);
    text-transform: uppercase;
}
.form-errors {
    margin: 0 21px;
    padding: 10px;
    color: #ff9ca3;
    border: 1px solid #ff7d8644;
}
.dialog footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 15px 21px;
    border-top: 1px solid #ffffff18;
}
.secondary,
.archive,
.danger {
    color: white !important;
    border-color: #ffffff2a !important;
    background: transparent !important;
}
.danger {
    margin-right: auto;
    color: #ff9da4 !important;
    border-color: #ff7d8655 !important;
}
.archive {
    color: #ffc879 !important;
}
@media (max-width: 1050px) {
    .grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 720px) {
    .hero {
        align-items: flex-start;
        flex-direction: column;
        justify-content: flex-end;
        padding: 25px 22px;
    }
    .summary,
    .grid {
        grid-template-columns: 1fr 1fr;
    }
    .filters,
    .fields {
        grid-template-columns: 1fr;
    }
    .full {
        grid-column: auto;
    }
    .fields label > small {
        min-height: 0;
    }
    .dialog footer {
        flex-wrap: wrap;
    }
}
@media (max-width: 520px) {
    .grid {
        grid-template-columns: 1fr;
    }
    .summary {
        grid-template-columns: 1fr 1fr;
    }
    .card {
        min-height: 145px;
    }
    .crest {
        width: 76px;
        height: 76px;
        flex-basis: 76px;
    }
    .brand-preview span {
        font-size: 1.5rem;
    }
}
</style>
