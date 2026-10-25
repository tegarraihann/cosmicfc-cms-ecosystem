<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ConfirmActionModal from "@/Components/Admin/ConfirmActionModal.vue";
import MediaPicker from "@/Components/Admin/MediaPicker.vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { computed, onMounted, ref } from "vue";
const props = defineProps({
    players: Object,
    season: Object,
    filters: Object,
    media: Array,
    award: Object,
    can: Object,
});
const page = usePage(),
    editing = ref(null),
    dialog = ref(false),
    awardDialog = ref(false),
    tab = ref("profile"),
    selectedMedia = ref(null),
    validationAlert = ref({
        open: false,
        title: "Data Belum Dapat Disimpan",
        description: "Periksa kembali data pemain yang ditandai pada formulir.",
        error: "",
    });
const filter = ref({
    search: props.filters.search || "",
    position: props.filters.position || "",
    status: props.filters.status || "",
});
const hasActiveFilters = computed(() =>
    Object.values(filter.value).some((value) => value !== "" && value !== null),
);
const statFields = [
    ["appearances", "Jumlah Penampilan"],
    ["starts", "Menjadi Starter"],
    ["minutes", "Total Menit Bermain"],
    ["goals", "Jumlah Gol"],
    ["assists", "Jumlah Assist"],
    ["clean_sheets", "Clean Sheet"],
    ["yellow_cards", "Kartu Kuning"],
    ["red_cards", "Kartu Merah"],
];
const blank = () => ({
    full_name: "",
    display_name: "",
    birth_date: "",
    height_cm: "",
    dominant_foot: "Kanan",
    hometown: "",
    biography: "",
    primary_media_id: null,
    image_focal_x: 50,
    image_focal_y: 40,
    status: "active",
    shirt_number: 1,
    primary_position: "Forward",
    is_captain: false,
    is_featured: false,
    featured_order: null,
});
const form = useForm(blank());
const awardForm = useForm({
    player_id: props.award?.player_id || "",
    period_month: props.award?.period_month || "2026-10",
    title: props.award?.title || "Player of the Month",
    editorial: props.award?.editorial || "",
    appearances: props.award?.appearances || 0,
    goals: props.award?.goals || 0,
    assists: props.award?.assists || 0,
    rating: props.award?.rating || null,
    is_published: props.award?.is_published ?? true,
});
const selectedAwardPlayer = computed(() =>
    props.players.data.find(
        (player) => Number(player.id) === Number(awardForm.player_id),
    ),
);
const selectedPhotoUrl = computed(() => {
    if (!form.primary_media_id) return "";

    return (
        selectedMedia.value?.url ||
        props.media.find(
            (item) => Number(item.id) === Number(form.primary_media_id),
        )?.url ||
        ""
    );
});
function openPlayer(player = null) {
    editing.value = player;
    selectedMedia.value = player?.primary_media_id
        ? props.media.find(
              (item) =>
                  Number(item.id) === Number(player.primary_media_id),
          ) || null
        : null;
    const defaults = blank();
    const editableData = player
        ? Object.fromEntries(
              Object.keys(defaults).map((key) => [
                  key,
                  player[key] ?? defaults[key],
              ]),
          )
        : defaults;

    form.defaults(editableData);
    form.reset();
    form.clearErrors();
    tab.value = "profile";
    dialog.value = true;
}
onMounted(() => {
    if (
        new URLSearchParams(window.location.search).get("action") ===
            "create" &&
        props.can?.create
    )
        openPlayer();
});
function save() {
    const options = {
        preserveScroll: true,
        onSuccess: () => (dialog.value = false),
        onError: (errors) => {
            validationAlert.value = {
                open: true,
                title: "Pemain Belum Tersimpan",
                description:
                    "Ada data yang belum sesuai. Periksa field yang ditandai pada formulir, lalu coba simpan kembali.",
                error:
                    Object.values(errors)[0] ||
                    "Data pemain belum dapat disimpan.",
            };
        },
    };
    editing.value
        ? form.put(route("admin.players.update", editing.value.id), options)
        : form.post(route("admin.players.store"), options);
}
function remove() {
    if (confirm("Hapus pemain ini?"))
        router.delete(route("admin.players.destroy", editing.value.id), {
            onSuccess: () => (dialog.value = false),
        });
}
function apply() {
    router.get(route("admin.players.index"), filter.value, {
        preserveState: true,
        replace: true,
    });
}
function resetFilters() {
    filter.value = { search: "", position: "", status: "" };
    apply();
}
function saveAward() {
    awardForm.post(route("admin.players.awards.save"), {
        preserveScroll: true,
        onSuccess: () => (awardDialog.value = false),
    });
}
const photo = (p) => p.photo_url || "/Assets/Dokumentasi/Docs-wahyu.jpeg";
</script>
<template>
    <Head title="Kelola Pemain" /><AuthenticatedLayout
        ><template #page-title>Pemain</template>
        <div class="players-page">
            <div v-if="page.props.flash?.success" class="notice">
                {{ page.props.flash.success }}
            </div>
            <div v-if="page.props.errors?.player" class="error">
                {{ page.props.errors.player }}
            </div>
            <header class="hero">
                <div>
                    <span
                        >Squad Management ·
                        {{ season?.name || "Master Pemain COSMIC" }}</span
                    >
                    <h1>Player <b>Roster.</b></h1>
                    <p>
                        Profil, skuad, statistik, kapten, dan pemain unggulan
                        COSMIC.
                    </p>
                </div>
                <div class="hero-actions">
                    <button
                        v-if="can.awards && season"
                        class="secondary"
                        @click="awardDialog = true"
                    >
                        Player of the Month</button
                    ><button v-if="can.create" @click="openPlayer()">
                        + Tambah Pemain
                    </button>
                </div>
            </header>
            <section v-if="!season" class="season-note">
                Profil pemain tetap dapat dikelola tanpa musim aktif. Statistik
                musim dan Player of the Month tersedia setelah musim diaktifkan.
            </section>
            <section class="summary">
                <article>
                    <strong>{{ players.total }}</strong
                    ><span>Total pemain</span>
                </article>
                <article>
                    <strong>{{
                        players.data.filter((p) => p.status === "active").length
                    }}</strong
                    ><span>Aktif di halaman</span>
                </article>
                <article>
                    <strong
                        >{{
                            players.data.filter((p) => p.is_featured).length
                        }}/3</strong
                    ><span>Featured</span>
                </article>
                <article>
                    <strong>{{
                        players.data.find((p) => p.is_captain)?.display_name ||
                        "–"
                    }}</strong
                    ><span>Captain</span>
                </article>
            </section>
            <section class="filter-panel">
                <form class="filters" @submit.prevent="apply">
                    <label for="player-search"
                        >Pencarian Pemain<input
                            id="player-search"
                            v-model="filter.search"
                            type="search"
                            placeholder="Cari nama pemain" /></label
                    ><label for="player-position"
                        >Posisi Pemain<select
                            id="player-position"
                            v-model="filter.position"
                        >
                            <option value="">Semua posisi</option>
                            <option>Goalkeeper</option>
                            <option>Defender</option>
                            <option>Midfielder</option>
                            <option>Forward</option>
                        </select></label
                    ><label for="player-status"
                        >Status Pemain<select
                            id="player-status"
                            v-model="filter.status"
                        >
                            <option value="">Semua status</option>
                            <option value="draft">Draft</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak aktif</option>
                            <option value="alumni">Alumni</option>
                        </select></label
                    ><button class="search-button">Cari</button
                    ><button
                        v-if="hasActiveFilters"
                        type="button"
                        class="reset-button"
                        @click="resetFilters"
                    >
                        Reset Filter
                    </button>
                </form>
                <div class="filter-result">
                    <span>Hasil pencarian</span
                    ><strong
                        >Menampilkan {{ players.data.length }} dari
                        {{ players.total }} pemain</strong
                    >
                </div>
            </section>
            <section class="grid">
                <button
                    v-for="player in players.data"
                    :key="player.id"
                    class="card"
                    @click="openPlayer(player)"
                >
                    <img :src="photo(player)" :alt="player.display_name" /><span
                        class="number"
                        >#{{
                            String(player.shirt_number).padStart(2, "0")
                        }}</span
                    ><span v-if="player.is_captain" class="badge">Captain</span>
                    <div>
                        <h2>{{ player.display_name }}</h2>
                        <strong>{{ player.primary_position }}</strong
                        ><small
                            >{{ player.appearances }} laga ·
                            {{ player.goals }} gol ·
                            {{ player.assists }} assist</small
                        >
                    </div>
                </button>
            </section>
            <div v-if="dialog" class="modal" @click.self="dialog = false">
                <form class="dialog" @submit.prevent="save">
                    <header>
                        <div>
                            <span>Data Pemain</span>
                            <h2>{{ editing ? "Edit" : "Tambah" }} Pemain</h2>
                        </div>
                        <button type="button" @click="dialog = false">×</button>
                    </header>
                    <nav>
                        <button
                            v-for="t in [
                                ['profile', 'Profil'],
                                ['squad', 'Tampilan Tim'],
                                ['media', 'Foto Pemain'],
                            ]"
                            :key="t[0]"
                            type="button"
                            :class="{ active: tab === t[0] }"
                            @click="tab = t[0]"
                        >
                            {{ t[1] }}
                        </button>
                    </nav>
                    <div v-show="tab === 'profile'" class="fields">
                        <label
                            ><span>Nama Lengkap <b>*</b></span
                            ><input
                                v-model="form.full_name"
                                placeholder="Masukkan nama lengkap pemain" /></label
                        ><label
                            ><span>Nama yang Ditampilkan <b>*</b></span
                            ><input
                                v-model="form.display_name"
                                placeholder="Nama pada kartu dan halaman pemain"
                            /><small
                                >Nama singkat yang terlihat oleh pengunjung
                                website.</small
                            ></label
                        ><label
                            >Tanggal Lahir<input
                                v-model="form.birth_date"
                                type="date" /></label
                        ><label
                            >Tinggi Badan (cm)<input
                                v-model="form.height_cm"
                                type="number"
                                min="100"
                                max="230"
                                placeholder="Contoh: 175" /></label
                        ><label
                            >Kaki Dominan<select v-model="form.dominant_foot">
                                <option>Kanan</option>
                                <option>Kiri</option>
                                <option>Keduanya</option>
                            </select></label
                        ><label
                            >Kota Asal<input
                                v-model="form.hometown"
                                placeholder="Contoh: Pekanbaru" /></label
                        ><label
                            ><span>Status Data Pemain <b>*</b></span
                            ><select v-model="form.status">
                                <option value="draft">Draf</option>
                                <option value="active">Aktif</option>
                                <option value="inactive">Tidak Aktif</option>
                                <option value="alumni">Alumni</option></select
                            ><small
                                >Menentukan apakah data pemain dapat digunakan
                                pada website.</small
                            ></label
                        ><label class="full"
                            >Biografi Singkat<textarea
                                v-model="form.biography"
                                rows="3"
                                placeholder="Ceritakan profil singkat pemain"
                            />
                        </label>
                    </div>
                    <div v-show="tab === 'squad'" class="fields">
                        <label
                            ><span>Nomor Punggung <b>*</b></span
                            ><input
                                v-model="form.shirt_number"
                                type="number"
                                min="1"
                                max="99" /></label
                        ><label
                            ><span>Posisi Utama <b>*</b></span
                            ><select v-model="form.primary_position">
                                <option value="Goalkeeper">
                                    Penjaga Gawang
                                </option>
                                <option value="Defender">Bek</option>
                                <option value="Midfielder">Gelandang</option>
                                <option value="Forward">Penyerang</option>
                            </select></label
                        ><label
                            >Urutan Pemain Unggulan<input
                                v-model="form.featured_order"
                                type="number"
                                min="1"
                                max="3"
                                placeholder="1–3"
                            /><small
                                >Isi 1–3 untuk menentukan urutan di halaman
                                utama.</small
                            ></label
                        ><label class="check check-card"
                            ><input v-model="form.is_captain" type="checkbox" />
                            <span
                                ><strong>Kapten Tim</strong
                                ><small
                                    >Tandai sebagai kapten utama COSMIC.</small
                                ></span
                            ></label
                        ><label class="check check-card"
                            ><input
                                v-model="form.is_featured"
                                type="checkbox"
                            />
                            <span
                                ><strong
                                    >Tampilkan sebagai Pemain Unggulan</strong
                                ><small
                                    >Pemain dapat muncul pada bagian Meet the
                                    Squad.</small
                                ></span
                            ></label
                        >
                    </div>
                    <div v-show="tab === 'stats'" class="fields stats-fields">
                        <label v-for="[field, label] in statFields" :key="field"
                            >{{ label
                            }}<input
                                v-model="form[field]"
                                type="number"
                                min="0" /></label
                        ><label
                            >Rating Pemain<input
                                v-model="form.rating"
                                type="number"
                                min="0"
                                max="10"
                                step="0.1" /></label
                        ><label
                            >Persentase Kehadiran<input
                                v-model="form.attendance_percent"
                                type="number"
                                min="0"
                                max="100"
                            /><small
                                >Masukkan nilai antara 0–100%.</small
                            ></label
                        >
                    </div>
                    <div v-show="tab === 'media'" class="fields">
                        <div class="full media-picker-group">
                            <span class="field-label"
                                >Foto utama pemain <i>Opsional</i></span
                            >
                            <MediaPicker
                                v-model="form.primary_media_id"
                                value-mode="id"
                                folder="all"
                                upload-folder="players"
                                media-type="image"
                                label="Pilih dari Media Library"
                                :preview-url="selectedPhotoUrl"
                                :can-browse="can.browse_media"
                                :can-upload="can.upload_media"
                                @selected="selectedMedia = $event"
                            />
                            <small
                                >Pilih foto yang sudah tersedia atau unggah
                                foto baru. Foto yang diunggah otomatis masuk ke
                                Media Library.</small
                            >
                            <em v-if="form.errors.primary_media_id">{{
                                form.errors.primary_media_id
                            }}</em>
                        </div>
                        <label
                            >Posisi Foto Horizontal<input
                                v-model="form.image_focal_x"
                                type="range"
                                min="0"
                                max="100"
                            /><small
                                >Geser untuk menentukan fokus kiri dan
                                kanan.</small
                            ></label
                        ><label
                            >Posisi Foto Vertikal<input
                                v-model="form.image_focal_y"
                                type="range"
                                min="0"
                                max="100"
                            /><small
                                >Geser untuk menentukan fokus atas dan
                                bawah.</small
                            ></label
                        ><img
                            v-if="selectedPhotoUrl"
                            class="preview"
                            :src="selectedPhotoUrl"
                            :style="{
                                objectPosition: `${form.image_focal_x}% ${form.image_focal_y}%`,
                            }"
                            alt="Pratinjau foto pemain"
                        />
                    </div>
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
                            v-if="editing && can.delete"
                            type="button"
                            class="danger"
                            @click="remove"
                        >
                            Hapus</button
                        ><button
                            type="button"
                            class="secondary"
                            @click="dialog = false"
                        >
                            Batal</button
                        ><button :disabled="form.processing">
                            Simpan Pemain
                        </button>
                    </footer>
                </form>
            </div>
            <div
                v-if="awardDialog"
                class="modal"
                @click.self="awardDialog = false"
            >
                <form class="dialog award" @submit.prevent="saveAward">
                    <header>
                        <div>
                            <span>Penghargaan Bulanan</span>
                            <h2>Pemain Terbaik Bulan Ini</h2>
                        </div>
                        <button type="button" @click="awardDialog = false">
                            ×
                        </button>
                    </header>
                    <p class="award-intro">
                        Pilih pemain terbaik, tentukan periode, lalu isi
                        ringkasan statistik dan cerita penampilannya.
                    </p>
                    <div class="award-section">
                        <div class="award-section-head">
                            <span>01</span>
                            <div>
                                <h3>Informasi Penghargaan</h3>
                                <p>Pemain dan periode yang akan ditampilkan.</p>
                            </div>
                        </div>
                        <div class="award-fields">
                            <label
                                ><span>Pemain Terpilih <b>*</b></span
                                ><select v-model="awardForm.player_id">
                                    <option value="">Pilih pemain</option>
                                    <option
                                        v-for="p in players.data"
                                        :key="p.id"
                                        :value="p.id"
                                    >
                                        {{ p.display_name }}
                                    </option></select
                                ><small
                                    >Pemain yang ditetapkan sebagai pemain
                                    terbaik pada periode ini.</small
                                ><em v-if="awardForm.errors.player_id">{{
                                    awardForm.errors.player_id
                                }}</em></label
                            ><label
                                ><span>Periode Penghargaan <b>*</b></span
                                ><input
                                    v-model="awardForm.period_month"
                                    type="month"
                                /><small
                                    >Bulan yang menjadi periode penilaian
                                    pemain.</small
                                ><em v-if="awardForm.errors.period_month">{{
                                    awardForm.errors.period_month
                                }}</em></label
                            ><label class="full"
                                ><span>Judul Penghargaan <b>*</b></span
                                ><input
                                    v-model="awardForm.title"
                                    placeholder="Contoh: Player of the Month"
                                /><small
                                    >Judul utama yang tampil pada
                                    website.</small
                                ><em v-if="awardForm.errors.title">{{
                                    awardForm.errors.title
                                }}</em></label
                            >
                        </div>
                        <article
                            v-if="selectedAwardPlayer"
                            class="award-player-preview"
                        >
                            <img
                                :src="photo(selectedAwardPlayer)"
                                :alt="selectedAwardPlayer.display_name"
                            />
                            <div>
                                <span>Pemain yang dipilih</span
                                ><strong>{{
                                    selectedAwardPlayer.display_name
                                }}</strong
                                ><small
                                    >#{{
                                        String(
                                            selectedAwardPlayer.shirt_number,
                                        ).padStart(2, "0")
                                    }}
                                    ·
                                    {{
                                        selectedAwardPlayer.primary_position
                                    }}</small
                                >
                            </div>
                        </article>
                    </div>
                    <div class="award-section">
                        <div class="award-section-head">
                            <span>02</span>
                            <div>
                                <h3>Ringkasan Performa</h3>
                                <p>
                                    Statistik khusus selama periode penghargaan.
                                </p>
                            </div>
                        </div>
                        <div class="award-fields stats">
                            <label
                                >Jumlah Pertandingan<input
                                    v-model="awardForm.appearances"
                                    type="number"
                                    min="0"
                                /><small
                                    >Laga yang dimainkan pada periode
                                    tersebut.</small
                                ><em v-if="awardForm.errors.appearances">{{
                                    awardForm.errors.appearances
                                }}</em></label
                            ><label
                                >Jumlah Gol<input
                                    v-model="awardForm.goals"
                                    type="number"
                                    min="0"
                                /><small
                                    >Total gol selama periode tersebut.</small
                                ><em v-if="awardForm.errors.goals">{{
                                    awardForm.errors.goals
                                }}</em></label
                            ><label
                                >Jumlah Assist<input
                                    v-model="awardForm.assists"
                                    type="number"
                                    min="0"
                                /><small
                                    >Total assist selama periode
                                    tersebut.</small
                                ><em v-if="awardForm.errors.assists">{{
                                    awardForm.errors.assists
                                }}</em></label
                            ><label
                                >Rating Pemain<input
                                    v-model="awardForm.rating"
                                    type="number"
                                    min="0"
                                    max="10"
                                    step="0.1"
                                    placeholder="0–10"
                                /><small>Nilai performa dari 0 sampai 10.</small
                                ><em v-if="awardForm.errors.rating">{{
                                    awardForm.errors.rating
                                }}</em></label
                            >
                        </div>
                    </div>
                    <div class="award-section">
                        <div class="award-section-head">
                            <span>03</span>
                            <div>
                                <h3>Cerita dan Publikasi</h3>
                                <p>
                                    Alasan pemilihan serta status tampil di
                                    website.
                                </p>
                            </div>
                        </div>
                        <div class="award-fields">
                            <label class="full"
                                >Cerita Penampilan<textarea
                                    v-model="awardForm.editorial"
                                    rows="4"
                                    placeholder="Jelaskan alasan pemain layak mendapatkan penghargaan"
                                /><small
                                    >Ringkasan ini akan menjadi narasi
                                    penghargaan di website.</small
                                ><em v-if="awardForm.errors.editorial">{{
                                    awardForm.errors.editorial
                                }}</em></label
                            ><label class="award-publish-check"
                                ><input
                                    v-model="awardForm.is_published"
                                    type="checkbox"
                                /><span
                                    ><strong>Tampilkan di Website</strong
                                    ><small
                                        >Penghargaan akan muncul pada halaman
                                        publik saat opsi ini aktif.</small
                                    ></span
                                ></label
                            >
                        </div>
                    </div>
                    <footer>
                        <button
                            type="button"
                            class="secondary"
                            @click="awardDialog = false"
                        >
                            Batal</button
                        ><button :disabled="awardForm.processing">
                            Simpan Pemain Terbaik
                        </button>
                    </footer>
                </form>
            </div>
            <ConfirmActionModal
                :open="validationAlert.open"
                mode="alert"
                variant="danger"
                :title="validationAlert.title"
                :description="validationAlert.description"
                :error="validationAlert.error"
                confirm-label="Mengerti"
                @confirm="validationAlert.open = false"
                @close="validationAlert.open = false"
            />
        </div></AuthenticatedLayout
    >
</template>
<style scoped>
.players-page {
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
    background:
        linear-gradient(100deg, #07111ef8, #07111eb5),
        url("/Assets/Dokumentasi/Docs-wahyu.jpeg") center/cover;
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
.hero-actions {
    display: flex;
    gap: 8px;
}
.hero button,
.dialog footer button {
    padding: 12px 15px;
    border: 1px solid var(--color-accent);
    color: #03101b;
    background: var(--color-accent);
    font: 800 0.55rem var(--font-display);
    text-transform: uppercase;
}
.secondary {
    color: white !important;
    border-color: #ffffff2a !important;
    background: transparent !important;
}
.season-note {
    margin-top: 20px;
    padding: 14px 18px;
    border: 1px solid rgba(69, 181, 245, 0.28);
    background: rgba(69, 181, 245, 0.07);
    color: #91a8bb;
    font-size: 14px;
    line-height: 1.6;
}
.season-required {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 30px;
    margin-top: 14px;
    padding: 28px 30px;
    border: 1px solid #45b7ff55;
    background: linear-gradient(115deg, #45b7ff12, #07111e 62%);
}
.season-required span {
    color: var(--color-accent);
    font: 800 0.55rem var(--font-display);
    letter-spacing: 0.16em;
    text-transform: uppercase;
}
.season-required h2 {
    margin: 7px 0 8px;
    font: 850 1.45rem var(--font-display);
    text-transform: uppercase;
}
.season-required p {
    max-width: 680px;
    margin: 0;
    color: #8fa1ae;
    font-size: 0.72rem;
    line-height: 1.6;
}
.season-required a {
    flex: 0 0 auto;
    padding: 13px 16px;
    color: #03101b;
    background: var(--color-accent);
    font: 800 0.56rem var(--font-display);
    letter-spacing: 0.05em;
    text-decoration: none;
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
.summary span {
    display: block;
}
.summary strong {
    font: 900 1.6rem var(--font-display);
    text-transform: uppercase;
}
.summary span {
    margin-top: 5px;
    color: #71828e;
    font-size: 0.52rem;
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
.fields input,
.fields select,
.fields textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 12px;
    border: 1px solid #ffffff22;
    color: white;
    background: #07111e;
}
.filters button {
    border: 0;
    color: #03101b;
    background: var(--color-accent);
    font: 800 0.55rem var(--font-display);
    text-transform: uppercase;
}
.grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 11px;
}
.card {
    position: relative;
    min-height: 330px;
    display: flex;
    align-items: flex-end;
    overflow: hidden;
    padding: 18px;
    border: 1px solid #afdaff22;
    color: white;
    background: #07111e;
    text-align: left;
    cursor: pointer;
}
.card:after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(0deg, #05090ff7, transparent 70%);
}
.card img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: saturate(0.65);
}
.card > div,
.number,
.badge {
    position: relative;
    z-index: 1;
}
.number {
    position: absolute;
    top: 15px;
    left: 16px;
    font: 900 1.3rem var(--font-display);
}
.badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 5px 7px;
    border: 1px solid var(--color-accent);
    color: var(--color-accent);
    background: #07111ecc;
    font-size: 0.48rem;
    text-transform: uppercase;
}
.card h2 {
    margin: 0;
    font: 900 2.4rem/0.85 var(--font-display);
    text-transform: uppercase;
}
.card strong {
    display: block;
    margin-top: 8px;
    color: var(--color-accent);
    font: 0.6rem var(--font-display);
    letter-spacing: 0.15em;
    text-transform: uppercase;
}
.card small {
    display: block;
    margin-top: 8px;
    color: #8797a3;
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
    width: min(900px, 100%);
    max-height: 92vh;
    overflow: auto;
    border: 1px solid #45b7ff48;
    background: #07111e;
}
.dialog.award {
    width: min(650px, 100%);
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
}
.dialog nav button.active {
    color: white;
    border-color: var(--color-accent);
}
.fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    padding: 21px;
}
.fields label {
    color: #91a1ad;
    font: 750 0.54rem var(--font-display);
    text-transform: uppercase;
}
.fields label > * {
    margin-top: 7px;
}
.fields .full {
    grid-column: 1/-1;
}
.check {
    display: flex !important;
    align-items: center;
    gap: 8px;
}
.check input {
    width: auto;
    margin: 0;
}
.preview {
    grid-column: 1/-1;
    width: 220px;
    height: 180px;
    object-fit: cover;
}
.form-errors {
    margin: 0 21px;
    padding: 10px;
    color: #ff9ca3;
    border: 1px solid #ff7d8644;
}
.form-errors p {
    margin: 3px;
}
.dialog footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 15px 21px;
    border-top: 1px solid #ffffff18;
}
.dialog footer .danger {
    margin-right: auto;
    color: #ff9da4;
    border-color: #ff7d8655;
    background: transparent;
}
@media (max-width: 1050px) {
    .grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
@media (max-width: 720px) {
    .season-required {
        align-items: flex-start;
        flex-direction: column;
        padding: 23px 21px;
    }
    .season-required a {
        width: 100%;
        box-sizing: border-box;
        text-align: center;
    }
    .hero {
        align-items: flex-start;
        flex-direction: column;
        justify-content: flex-end;
        padding: 26px 22px;
    }
    .hero-actions {
        width: 100%;
    }
    .hero-actions button {
        flex: 1;
    }
    .summary,
    .grid {
        grid-template-columns: 1fr 1fr;
    }
    .filters {
        grid-template-columns: 1fr 1fr;
    }
    .filters input {
        grid-column: 1/-1;
    }
    .fields {
        grid-template-columns: 1fr;
    }
    .fields .full {
        grid-column: auto;
    }
}
@media (max-width: 480px) {
    .grid {
        grid-template-columns: 1fr;
    }
    .summary {
        grid-template-columns: 1fr 1fr;
    }
    .filters {
        grid-template-columns: 1fr;
    }
    .filters input {
        grid-column: auto;
    }
    .dialog nav {
        overflow: auto;
    }
    .card {
        min-height: 380px;
    }
}
.filter-panel {
    margin-bottom: 14px;
    border: 1px solid #afdaff20;
    background: #050c15;
}
.filter-panel .filters {
    display: grid;
    grid-template-columns: minmax(280px, 1fr) 200px 200px 100px auto;
    align-items: end;
    gap: 10px;
    margin: 0;
    padding: 16px;
}
.filter-panel .filters label {
    display: grid;
    gap: 7px;
    color: #aebdc7;
    font:
        600 12px/1.2 Arial,
        sans-serif;
    text-transform: none;
}
.filter-panel .filters input,
.filter-panel .filters select {
    box-sizing: border-box;
    width: 100%;
    height: 46px;
    padding: 0 14px;
    outline: none;
    color: #edf3f6;
    background: #07111e;
    font:
        400 14px/1 Arial,
        sans-serif;
    transition:
        border-color 0.18s ease,
        box-shadow 0.18s ease;
}
.filter-panel .filters input::placeholder {
    color: #657986;
    opacity: 1;
}
.filter-panel .filters input:focus,
.filter-panel .filters select:focus {
    border-color: #45b7ff88;
    box-shadow: 0 0 0 3px #45b7ff10;
}
.filter-panel .filters button {
    height: 46px;
    padding: 0 16px;
    border: 1px solid var(--color-accent);
    color: #03101b;
    background: var(--color-accent);
    font: 800 11px/1 var(--font-display);
    text-transform: uppercase;
    white-space: nowrap;
    cursor: pointer;
}
.filter-panel .filters .search-button {
    min-width: 100px;
}
.filter-panel .filters .reset-button {
    color: #9fdcff;
    border-color: #45b7ff4a;
    background: transparent;
}
.filter-panel .filters .reset-button:hover {
    color: #03101b;
    background: var(--color-accent);
}
.filter-result {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 10px 16px;
    border-top: 1px solid #ffffff12;
}
.filter-result span {
    color: #647885;
    font: 700 10px/1 var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.filter-result strong {
    color: #9eb0bb;
    font:
        500 12px/1.2 Arial,
        sans-serif;
}
@media (max-width: 1050px) {
    .filter-panel .filters {
        grid-template-columns: 1fr 1fr 110px;
    }
    .filter-panel .filters label:first-child {
        grid-column: 1/-1;
    }
    .filter-panel .filters .reset-button {
        grid-column: 1/-1;
        width: max-content;
    }
}
@media (max-width: 600px) {
    .filter-panel .filters {
        grid-template-columns: 1fr;
    }
    .filter-panel .filters label:first-child,
    .filter-panel .filters .reset-button {
        grid-column: auto;
    }
    .filter-panel .filters button,
    .filter-panel .filters .reset-button {
        width: 100%;
    }
    .filter-result {
        align-items: flex-start;
        flex-direction: column;
        gap: 5px;
    }
}

/* Player editor: consistent labels, controls, and spacing across every tab. */
.dialog:not(.award) .fields {
    align-items: start;
    gap: 16px;
    padding: 20px;
}
.dialog:not(.award) .fields label {
    display: grid;
    gap: 6px;
    color: #bccad3;
    font-family: Arial, sans-serif;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.25;
    letter-spacing: 0;
    text-transform: none;
}
.dialog:not(.award) .fields label > * {
    margin-top: 0;
}
.dialog:not(.award) .fields label > b,
.dialog:not(.award) .fields label > span b {
    display: inline;
    color: var(--color-accent);
    font: inherit;
}
.dialog:not(.award) .fields input:not([type="checkbox"]):not([type="range"]),
.dialog:not(.award) .fields select {
    box-sizing: border-box;
    width: 100%;
    height: 40px;
    padding: 0 11px;
    outline: none;
    color: #edf3f6;
    background: #050c14;
    font-family: Arial, sans-serif;
    font-size: 13px;
    font-weight: 400;
}
.dialog:not(.award) .fields textarea {
    min-height: 88px;
    padding: 10px 11px;
    outline: none;
    color: #edf3f6;
    background: #050c14;
    font-family: Arial, sans-serif;
    font-size: 13px;
    font-weight: 400;
    line-height: 1.45;
    resize: vertical;
}
.dialog:not(.award) .fields input::placeholder,
.dialog:not(.award) .fields textarea::placeholder {
    color: #627682;
    opacity: 1;
}
.dialog:not(.award) .fields input:focus,
.dialog:not(.award) .fields select:focus,
.dialog:not(.award) .fields textarea:focus {
    border-color: #45b7ff88;
    box-shadow: 0 0 0 3px #45b7ff0e;
}
.dialog:not(.award) .fields label > small {
    min-height: 14px;
    color: #718591;
    font-family: Arial, sans-serif;
    font-size: 10px;
    font-weight: 400;
    line-height: 1.4;
}
.dialog:not(.award) .media-picker-group {
    display: grid;
    gap: 7px;
}
.dialog:not(.award) .media-picker-group .field-label {
    color: #bccad3;
    font-family: Arial, sans-serif;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.25;
}
.dialog:not(.award) .media-picker-group .field-label i {
    margin-left: 4px;
    color: #718591;
    font-size: 10px;
    font-style: normal;
    font-weight: 400;
}
.dialog:not(.award) .media-picker-group > small {
    color: #718591;
    font-family: Arial, sans-serif;
    font-size: 10px;
    font-weight: 400;
    line-height: 1.4;
}
.dialog:not(.award) .media-picker-group > em {
    color: #ff8c96;
    font-family: Arial, sans-serif;
    font-size: 10px;
    font-style: normal;
}
.dialog:not(.award) .check-card {
    display: flex !important;
    align-items: flex-start;
    min-height: 52px;
    box-sizing: border-box;
    padding: 10px 12px;
    border: 1px solid #ffffff18;
    background: #050c14;
    cursor: pointer;
}
.dialog:not(.award) .check-card input[type="checkbox"] {
    appearance: none;
    -webkit-appearance: none;
    display: grid;
    place-content: center;
    flex: 0 0 16px;
    width: 16px;
    height: 16px;
    margin: 1px 0 0;
    padding: 0;
    border: 1px solid #617887;
    border-radius: 3px;
    outline: none;
    background: #030911;
    cursor: pointer;
    transition: 0.16s ease;
}
.dialog:not(.award) .check-card input[type="checkbox"]::before {
    width: 8px;
    height: 4px;
    content: "";
    border-bottom: 2px solid #03101a;
    border-left: 2px solid #03101a;
    opacity: 0;
    transform: translateY(-1px) rotate(-45deg) scale(0.65);
    transition: 0.16s ease;
}
.dialog:not(.award) .check-card input[type="checkbox"]:checked {
    border-color: var(--color-accent);
    background: var(--color-accent);
}
.dialog:not(.award) .check-card input[type="checkbox"]:checked::before {
    opacity: 1;
    transform: translateY(-1px) rotate(-45deg) scale(1);
}
.dialog:not(.award) .check-card input[type="checkbox"]:focus-visible {
    box-shadow: 0 0 0 3px #45b7ff24;
}
.dialog:not(.award) .check-card:hover input[type="checkbox"] {
    border-color: var(--color-accent);
}
.dialog:not(.award) .check-card > span {
    display: grid;
    gap: 3px;
}
.dialog:not(.award) .check-card strong {
    color: #dce7ed;
    font-size: 12px;
    font-weight: 600;
}
.dialog:not(.award) .check-card small {
    color: #718591;
    font-size: 10px;
    font-weight: 400;
    line-height: 1.35;
}
.dialog:not(.award) input[type="range"] {
    width: 100%;
    margin: 4px 0;
    accent-color: var(--color-accent);
}
.dialog:not(.award) .preview {
    width: 100%;
    height: 220px;
}
.dialog:not(.award) footer {
    padding: 16px 20px;
}
@media (max-width: 720px) {
    .dialog:not(.award) .fields {
        gap: 14px;
        padding: 16px;
    }
    .dialog:not(.award) footer {
        padding: 14px 16px;
    }
}

/* Player of the Month editor */
.dialog.award {
    width: min(760px, 100%);
}
.award-intro {
    margin: 0;
    padding: 14px 20px;
    border-bottom: 1px solid #ffffff12;
    color: #8fa2af;
    background: #45b7ff08;
    font:
        400 12px/1.5 Arial,
        sans-serif;
}
.award-section {
    padding: 18px 20px;
    border-bottom: 1px solid #ffffff12;
}
.award-section-head {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 15px;
}
.award-section-head > span {
    display: grid;
    flex: 0 0 28px;
    width: 28px;
    height: 28px;
    place-items: center;
    border: 1px solid #45b7ff55;
    color: var(--color-accent);
    font: 800 10px/1 var(--font-display);
}
.award-section-head h3 {
    margin: 0;
    color: #edf3f6;
    font: 800 14px/1.15 var(--font-display);
    text-transform: uppercase;
}
.award-section-head p {
    margin: 4px 0 0;
    color: #718591;
    font:
        400 11px/1.35 Arial,
        sans-serif;
}
.award-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px 16px;
}
.award-fields label {
    display: grid;
    align-content: start;
    gap: 6px;
    color: #bdcad2;
    font:
        600 12px/1.25 Arial,
        sans-serif;
}
.award-fields label > span b {
    color: var(--color-accent);
}
.award-fields .full {
    grid-column: 1/-1;
}
.award-fields input:not([type="checkbox"]),
.award-fields select {
    box-sizing: border-box;
    width: 100%;
    height: 40px;
    padding: 0 11px;
    border: 1px solid #ffffff22;
    outline: none;
    color: #edf3f6;
    background: #050c14;
    font:
        400 13px/1 Arial,
        sans-serif;
}
.award-fields textarea {
    box-sizing: border-box;
    width: 100%;
    min-height: 88px;
    padding: 10px 11px;
    border: 1px solid #ffffff22;
    outline: none;
    color: #edf3f6;
    background: #050c14;
    font:
        400 13px/1.45 Arial,
        sans-serif;
    resize: vertical;
}
.award-fields input::placeholder,
.award-fields textarea::placeholder {
    color: #607480;
    opacity: 1;
}
.award-fields input:focus,
.award-fields select:focus,
.award-fields textarea:focus {
    border-color: #45b7ff88;
    box-shadow: 0 0 0 3px #45b7ff0e;
}
.award-fields label > small {
    min-height: 28px;
    color: #718591;
    font:
        400 10px/1.35 Arial,
        sans-serif;
}
.award-fields label > em {
    color: #ff959d;
    font:
        normal 500 10px/1.35 Arial,
        sans-serif;
}
.award-player-preview {
    display: grid;
    grid-template-columns: 56px 1fr;
    align-items: center;
    gap: 12px;
    margin-top: 14px;
    padding: 10px;
    border: 1px solid #45b7ff30;
    background: #45b7ff08;
}
.award-player-preview img {
    width: 56px;
    height: 56px;
    object-fit: cover;
}
.award-player-preview div {
    display: grid;
    gap: 3px;
}
.award-player-preview span {
    color: var(--color-accent);
    font: 700 9px/1 var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.award-player-preview strong {
    color: #edf3f6;
    font:
        700 14px/1.2 Arial,
        sans-serif;
}
.award-player-preview small {
    color: #80929e;
    font:
        400 11px/1.2 Arial,
        sans-serif;
}
.award-publish-check {
    grid-column: 1/-1;
    display: flex !important;
    align-items: flex-start !important;
    width: fit-content;
    cursor: pointer;
}
.award-publish-check input[type="checkbox"] {
    appearance: none;
    -webkit-appearance: none;
    display: grid;
    flex: 0 0 16px;
    width: 16px;
    height: 16px;
    margin: 1px 0 0;
    padding: 0;
    place-content: center;
    border: 1px solid #617887;
    border-radius: 3px;
    background: #030911;
    cursor: pointer;
}
.award-publish-check input[type="checkbox"]::before {
    width: 8px;
    height: 4px;
    content: "";
    border-bottom: 2px solid #03101a;
    border-left: 2px solid #03101a;
    opacity: 0;
    transform: translateY(-1px) rotate(-45deg) scale(0.65);
    transition: 0.16s ease;
}
.award-publish-check input[type="checkbox"]:checked {
    border-color: var(--color-accent);
    background: var(--color-accent);
}
.award-publish-check input[type="checkbox"]:checked::before {
    opacity: 1;
    transform: translateY(-1px) rotate(-45deg) scale(1);
}
.award-publish-check input[type="checkbox"]:focus-visible {
    box-shadow: 0 0 0 3px #45b7ff24;
}
.award-publish-check > span {
    display: grid;
    gap: 3px;
}
.award-publish-check strong {
    color: #dce7ed;
    font:
        600 12px/1.2 Arial,
        sans-serif;
}
.award-publish-check span small {
    min-height: 0;
    color: #718591;
    font:
        400 10px/1.35 Arial,
        sans-serif;
}
.dialog.award footer button {
    min-height: 40px;
}
@media (max-width: 600px) {
    .award-section {
        padding: 16px;
    }
    .award-fields {
        grid-template-columns: 1fr;
    }
    .award-fields .full,
    .award-publish-check {
        grid-column: auto;
    }
}
</style>
