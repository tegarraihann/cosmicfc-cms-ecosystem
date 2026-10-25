<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import MediaPicker from "@/Components/Admin/MediaPicker.vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
const props = defineProps({
    staff: Object,
    filters: Object,
    media: Array,
    can: Object,
});
const page = usePage();
const open = ref(false);
const editing = ref(null);
const tab = ref("profile");
const selectedMedia = ref(null);
const filters = ref({
    search: props.filters.search || "",
    category: props.filters.category || "",
    status: props.filters.status || "",
});
const blank = () => ({
    full_name: "",
    display_name: "",
    hometown: "",
    biography: "",
    primary_media_id: null,
    image_focal_x: 50,
    image_focal_y: 40,
    status: "active",
    role_title: "",
    category: "official",
    short_description: "",
    badge: "Official",
    starts_at: "",
    ends_at: "",
    is_primary_founder: false,
    is_featured: false,
    featured_order: null,
    is_public: true,
    assignment_status: "active",
});
const form = useForm(blank());
function edit(member = null) {
    editing.value = member;
    selectedMedia.value = member?.primary_media_id
        ? props.media.find(
              (item) => Number(item.id) === Number(member.primary_media_id),
          ) || null
        : null;
    form.defaults(member ? { ...blank(), ...member } : blank());
    form.reset();
    form.clearErrors();
    tab.value = "profile";
    open.value = true;
}
function save() {
    const options = {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    };
    editing.value
        ? form.put(route("admin.staff.update", editing.value.id), options)
        : form.post(route("admin.staff.store"), options);
}
function search() {
    router.get(route("admin.staff.index"), filters.value, {
        preserveState: true,
        replace: true,
    });
}
function remove() {
    if (confirm("Hapus data staff ini?"))
        router.delete(route("admin.staff.destroy", editing.value.id), {
            onSuccess: () => (open.value = false),
        });
}
const photo = (member) =>
    member.photo_url || "/Assets/Dokumentasi/Docs-wahyu.jpeg";
</script>
<template>
    <Head title="Staff & Officials" /><AuthenticatedLayout
        ><template #page-title>Staff & Officials</template>
        <div class="page">
            <div v-if="page.props.flash?.success" class="notice">
                {{ page.props.flash.success }}
            </div>
            <div v-if="page.props.errors?.staff" class="error">
                {{ page.props.errors.staff }}
            </div>
            <header class="hero">
                <div>
                    <span>Club Organization</span>
                    <h1>People Behind <b>COSMIC.</b></h1>
                    <p>
                        Kelola founder, official, coaching staff, dan tim
                        pendukung dalam satu tempat.
                    </p>
                </div>
                <button v-if="can.create" @click="edit()">
                    + Tambah Staff
                </button>
            </header>
            <section class="summary">
                <article>
                    <strong>{{ staff.total }}</strong
                    ><small>Total staff</small>
                </article>
                <article>
                    <strong>{{
                        staff.data.filter((x) => x.status === "active").length
                    }}</strong
                    ><small>Aktif</small>
                </article>
                <article>
                    <strong
                        >{{
                            staff.data.filter((x) => x.is_featured).length
                        }}/4</strong
                    ><small>Featured</small>
                </article>
                <article>
                    <strong>{{
                        staff.data.find((x) => x.is_primary_founder)
                            ?.display_name || "–"
                    }}</strong
                    ><small>Founder utama</small>
                </article>
            </section>
            <form class="filters" @submit.prevent="search">
                <input
                    v-model="filters.search"
                    placeholder="Cari nama atau jabatan..."
                /><select v-model="filters.category">
                    <option value="">Semua kategori</option>
                    <option value="leadership">Leadership</option>
                    <option value="official">Official</option>
                    <option value="coaching">Coaching</option>
                    <option value="support">Support</option>
                    <option value="creative">Creative</option></select
                ><select v-model="filters.status">
                    <option value="">Semua status</option>
                    <option>draft</option>
                    <option>active</option>
                    <option>inactive</option>
                    <option>alumni</option></select
                ><button>Cari</button>
            </form>
            <section class="grid">
                <button
                    v-for="member in staff.data"
                    :key="member.id"
                    class="card"
                    @click="edit(member)"
                >
                    <img
                        :src="photo(member)"
                        :style="{
                            objectPosition: `${member.image_focal_x}% ${member.image_focal_y}%`,
                        }"
                    /><span class="role">{{ member.role_title }}</span
                    ><span v-if="member.is_primary_founder" class="badge"
                        >Founder</span
                    >
                    <div>
                        <h2>{{ member.display_name }}</h2>
                        <strong>{{ member.category }}</strong
                        ><small
                            >{{ member.assignment_status }} ·
                            {{
                                member.is_public ? "Publik" : "Disembunyikan"
                            }}</small
                        >
                    </div>
                </button>
            </section>
            <div v-if="open" class="modal" @click.self="open = false">
                <form class="dialog" @submit.prevent="save">
                    <header>
                        <div>
                            <span>Pengelolaan Staff</span>
                            <h2>
                                {{ editing ? "Edit" : "Tambah" }} Staff &
                                Official
                            </h2>
                        </div>
                        <button type="button" @click="open = false">×</button>
                    </header>
                    <nav>
                        <button
                            type="button"
                            :class="{ active: tab === 'profile' }"
                            @click="tab = 'profile'"
                        >
                            Profil</button
                        ><button
                            type="button"
                            :class="{ active: tab === 'assignment' }"
                            @click="tab = 'assignment'"
                        >
                            Penugasan</button
                        ><button
                            type="button"
                            :class="{ active: tab === 'media' }"
                            @click="tab = 'media'"
                        >
                            Foto & Tampilan
                        </button>
                    </nav>
                    <section v-show="tab === 'profile'" class="tab-panel">
                        <div class="section-heading">
                            <span>01</span>
                            <div>
                                <h3>Identitas Staff</h3>
                                <p>
                                    Informasi nama dan profil yang akan dikenali
                                    oleh pengunjung website.
                                </p>
                            </div>
                        </div>
                        <div class="fields">
                            <label
                                ><span>Nama lengkap <b>Wajib</b></span
                                ><input
                                    v-model="form.full_name"
                                    placeholder="Contoh: Rian Prakoso"
                                /><small
                                    >Gunakan nama lengkap sesuai identitas
                                    staff.</small
                                ><em v-if="form.errors.full_name">{{
                                    form.errors.full_name
                                }}</em></label
                            >
                            <label
                                ><span>Nama di website <b>Wajib</b></span
                                ><input
                                    v-model="form.display_name"
                                    placeholder="Contoh: Coach Rian"
                                /><small
                                    >Nama singkat yang tampil pada kartu staff
                                    di website.</small
                                ><em v-if="form.errors.display_name">{{
                                    form.errors.display_name
                                }}</em></label
                            >
                            <label
                                ><span>Kota asal <i>Opsional</i></span
                                ><input
                                    v-model="form.hometown"
                                    placeholder="Contoh: Pekanbaru"
                                /><small
                                    >Asal kota staff untuk melengkapi informasi
                                    profil.</small
                                ><em v-if="form.errors.hometown">{{
                                    form.errors.hometown
                                }}</em></label
                            >
                            <label
                                ><span>Status profil <b>Wajib</b></span
                                ><select v-model="form.status">
                                    <option value="draft">
                                        Draft — belum siap ditampilkan
                                    </option>
                                    <option value="active">
                                        Aktif — sedang bergabung
                                    </option>
                                    <option value="inactive">
                                        Nonaktif — tidak sedang bertugas
                                    </option>
                                    <option value="alumni">
                                        Alumni — pernah bergabung
                                    </option></select
                                ><small
                                    >Status administrasi profil staff di dalam
                                    sistem.</small
                                ><em v-if="form.errors.status">{{
                                    form.errors.status
                                }}</em></label
                            >
                            <label class="full"
                                ><span>Biografi <i>Opsional</i></span
                                ><textarea
                                    v-model="form.biography"
                                    rows="4"
                                    placeholder="Ceritakan pengalaman, tanggung jawab, atau latar belakang staff..."
                                /><small
                                    >Profil lengkap yang dapat digunakan pada
                                    halaman detail atau cerita klub.</small
                                ><em v-if="form.errors.biography">{{
                                    form.errors.biography
                                }}</em></label
                            >
                        </div>
                    </section>
                    <section v-show="tab === 'assignment'" class="tab-panel">
                        <div class="section-heading">
                            <span>02</span>
                            <div>
                                <h3>Keanggotaan dan Penugasan</h3>
                                <p>
                                    Tentukan peran, masa tugas, dan visibilitas
                                    staff pada website.
                                </p>
                            </div>
                        </div>
                        <div class="fields">
                            <label
                                ><span>Jabatan di klub <b>Wajib</b></span
                                ><input
                                    v-model="form.role_title"
                                    placeholder="Contoh: Head Coach"
                                /><small
                                    >Jabatan utama yang ditampilkan di kartu
                                    staff.</small
                                ><em v-if="form.errors.role_title">{{
                                    form.errors.role_title
                                }}</em></label
                            >
                            <label
                                ><span>Kelompok staff <b>Wajib</b></span
                                ><select v-model="form.category">
                                    <option value="leadership">
                                        Pimpinan klub
                                    </option>
                                    <option value="official">
                                        Official klub
                                    </option>
                                    <option value="coaching">
                                        Tim pelatih
                                    </option>
                                    <option value="support">
                                        Tim pendukung
                                    </option>
                                    <option value="creative">
                                        Tim kreatif
                                    </option></select
                                ><small
                                    >Menentukan pengelompokan staff pada halaman
                                    tim.</small
                                ><em v-if="form.errors.category">{{
                                    form.errors.category
                                }}</em></label
                            >
                            <label
                                ><span>Label kartu <i>Opsional</i></span
                                ><input
                                    v-model="form.badge"
                                    maxlength="40"
                                    placeholder="Contoh: Official"
                                /><small
                                    >Teks pendek yang muncul sebagai penanda
                                    pada kartu staff.</small
                                ><em v-if="form.errors.badge">{{
                                    form.errors.badge
                                }}</em></label
                            >
                            <label
                                ><span>Status penugasan <b>Wajib</b></span
                                ><select v-model="form.assignment_status">
                                    <option value="scheduled">
                                        Dijadwalkan — belum mulai
                                    </option>
                                    <option value="active">
                                        Aktif — sedang bertugas
                                    </option>
                                    <option value="ended">
                                        Selesai — masa tugas berakhir
                                    </option></select
                                ><small>Status masa tugas staff di klub.</small
                                ><em v-if="form.errors.assignment_status">{{
                                    form.errors.assignment_status
                                }}</em></label
                            >
                            <label
                                ><span>Tanggal mulai <i>Opsional</i></span
                                ><input
                                    v-model="form.starts_at"
                                    type="date"
                                /><small
                                    >Tanggal staff mulai menjalankan
                                    tugas.</small
                                ><em v-if="form.errors.starts_at">{{
                                    form.errors.starts_at
                                }}</em></label
                            >
                            <label
                                ><span>Tanggal selesai <i>Opsional</i></span
                                ><input
                                    v-model="form.ends_at"
                                    type="date"
                                /><small
                                    >Kosongkan apabila staff masih aktif.</small
                                ><em v-if="form.errors.ends_at">{{
                                    form.errors.ends_at
                                }}</em></label
                            >
                            <label class="full"
                                ><span>Ringkasan peran <i>Opsional</i></span
                                ><textarea
                                    v-model="form.short_description"
                                    rows="3"
                                    placeholder="Jelaskan tanggung jawab staff secara singkat..."
                                /><small
                                    >Ringkasan ini dapat ditampilkan bersama
                                    kartu staff.</small
                                ><em v-if="form.errors.short_description">{{
                                    form.errors.short_description
                                }}</em></label
                            >
                            <div class="checks full">
                                <label class="check-card"
                                    ><input
                                        v-model="form.is_primary_founder"
                                        type="checkbox"
                                    /><span
                                        ><strong>Founder utama</strong
                                        ><small
                                            >Jadikan staff ini sebagai founder
                                            utama COSMIC. Hanya satu orang yang
                                            dapat dipilih.</small
                                        ></span
                                    ></label
                                ><label class="check-card"
                                    ><input
                                        v-model="form.is_public"
                                        type="checkbox"
                                    /><span
                                        ><strong>Tampilkan di website</strong
                                        ><small
                                            >Jika tidak dicentang, data tetap
                                            tersimpan tetapi tidak terlihat oleh
                                            pengunjung.</small
                                        ></span
                                    ></label
                                >
                            </div>
                        </div>
                    </section>
                    <section v-show="tab === 'media'" class="tab-panel">
                        <div class="section-heading">
                            <span>03</span>
                            <div>
                                <h3>Foto dan Posisi Tampilan</h3>
                                <p>
                                    Pilih foto, atur fokus gambar, dan tentukan
                                    apakah staff masuk sorotan utama.
                                </p>
                            </div>
                        </div>
                        <div class="media-layout">
                            <div class="fields">
                                <div class="full media-picker-group">
                                    <span class="field-label"
                                        >Foto staff <i>Opsional</i></span
                                    >
                                    <MediaPicker
                                        v-model="form.primary_media_id"
                                        value-mode="id"
                                        folder="all"
                                        upload-folder="staff"
                                        media-type="image"
                                        label="Pilih dari Media Library"
                                        :preview-url="selectedMedia?.url || ''"
                                        :can-browse="can.browse_media"
                                        :can-upload="can.upload_media"
                                        @selected="selectedMedia = $event"
                                    />
                                    <small
                                        >Pilih foto yang sudah tersedia atau
                                        unggah foto baru. Foto yang diunggah
                                        otomatis tersimpan di Media Library.</small
                                    >
                                    <em v-if="form.errors.primary_media_id">{{
                                        form.errors.primary_media_id
                                    }}</em>
                                </div>
                                <label
                                    ><span
                                        >Fokus horizontal:
                                        {{ form.image_focal_x }}%</span
                                    ><input
                                        v-model="form.image_focal_x"
                                        type="range"
                                        min="0"
                                        max="100"
                                    /><small
                                        >Geser untuk mengatur posisi wajah dari
                                        kiri ke kanan.</small
                                    ></label
                                ><label
                                    ><span
                                        >Fokus vertikal:
                                        {{ form.image_focal_y }}%</span
                                    ><input
                                        v-model="form.image_focal_y"
                                        type="range"
                                        min="0"
                                        max="100"
                                    /><small
                                        >Geser untuk mengatur posisi wajah dari
                                        atas ke bawah.</small
                                    ></label
                                >
                                <div class="checks full">
                                    <label class="check-card"
                                        ><input
                                            v-model="form.is_featured"
                                            type="checkbox"
                                        /><span
                                            ><strong
                                                >Tampilkan di People Behind The
                                                Club</strong
                                            ><small
                                                >Staff akan masuk maksimal empat
                                                profil pilihan di halaman
                                                beranda.</small
                                            ></span
                                        ></label
                                    >
                                </div>
                                <label v-if="form.is_featured" class="full"
                                    ><span>Urutan sorotan <b>Wajib</b></span
                                    ><input
                                        v-model="form.featured_order"
                                        type="number"
                                        min="1"
                                        max="4"
                                        placeholder="1–4"
                                    /><small
                                        >Angka 1 tampil paling awal. Gunakan
                                        urutan 1 sampai 4.</small
                                    ><em v-if="form.errors.featured_order">{{
                                        form.errors.featured_order
                                    }}</em></label
                                >
                            </div>
                            <figure>
                                <img
                                    class="preview"
                                    :src="
                                        form.primary_media_id
                                            ? selectedMedia?.url ||
                                              media.find(
                                                  (x) =>
                                                      Number(x.id) ===
                                                      Number(
                                                          form.primary_media_id,
                                                      ),
                                              )?.url
                                            : '/Assets/Dokumentasi/Docs-wahyu.jpeg'
                                    "
                                    :style="{
                                        objectPosition: `${form.image_focal_x}% ${form.image_focal_y}%`,
                                    }"
                                />
                                <figcaption>Pratinjau foto staff</figcaption>
                            </figure>
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
                            v-if="editing && can.delete"
                            type="button"
                            class="danger"
                            @click="remove"
                        >
                            Hapus</button
                        ><button
                            type="button"
                            class="secondary"
                            @click="open = false"
                        >
                            Batal</button
                        ><button :disabled="form.processing">
                            Simpan Staff
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
        linear-gradient(100deg, #07111ef8, #07111eaa),
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
.hero button,
.dialog footer button,
.filters button {
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
    font: 900 1.5rem var(--font-display);
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
.fields input:not([type="checkbox"]):not([type="range"]),
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
.role,
.badge {
    position: relative;
    z-index: 1;
}
.role,
.badge {
    position: absolute;
    top: 15px;
    left: 15px;
    padding: 5px 8px;
    border: 1px solid #45b7ff88;
    color: var(--color-accent);
    background: #07111ecc;
    font: 750 0.5rem var(--font-display);
    text-transform: uppercase;
}
.badge {
    right: 15px;
    left: auto;
}
.card h2 {
    margin: 0;
    font: 900 2.2rem/0.85 var(--font-display);
    text-transform: uppercase;
}
.card strong,
.card small {
    display: block;
    margin-top: 8px;
}
.card strong {
    color: var(--color-accent);
    font: 0.6rem var(--font-display);
    letter-spacing: 0.15em;
    text-transform: uppercase;
}
.card small {
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
    margin-top: 7px;
    color: #718895;
    font: 400 0.72rem/1.45 var(--font-body);
}
.media-picker-group > em {
    margin-top: 5px;
    color: #ff8c96;
    font-size: 0.7rem;
    font-style: normal;
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
.fields label > textarea {
    resize: vertical;
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
    font: 500 0.7rem/1.35 var(--font-body);
    font-style: normal;
    text-transform: none;
}
.fields .full {
    grid-column: 1/-1;
}
.tab-panel {
    padding: 22px;
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
    grid-template-columns: repeat(2, minmax(0, 1fr));
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
    max-height: 18px;
    flex: 0 0 18px;
    margin: 0;
    padding: 0;
    border: 1px solid #617887;
    border-radius: 2px;
    background: #07111e;
    cursor: pointer;
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
.check-card input[type="checkbox"]:focus-visible {
    outline: 2px solid white;
    outline-offset: 3px;
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
.media-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 240px;
    gap: 22px;
}
.media-layout figure {
    margin: 0;
}
.media-layout figcaption {
    padding: 9px;
    color: #718895;
    background: #030a12;
    font-size: 0.7rem;
    text-align: center;
}
.preview {
    display: block;
    width: 100%;
    height: 260px;
    object-fit: cover;
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
.secondary {
    color: white !important;
    border-color: #ffffff2a !important;
    background: transparent !important;
}
.danger {
    margin-right: auto;
    color: #ff9da4 !important;
    border-color: #ff7d8655 !important;
    background: transparent !important;
}
@media (max-width: 1000px) {
    .grid {
        grid-template-columns: repeat(3, 1fr);
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
    .filters {
        grid-template-columns: 1fr 1fr;
    }
    .fields {
        grid-template-columns: 1fr;
    }
    .fields .full {
        grid-column: auto;
    }
    .checks,
    .media-layout {
        grid-template-columns: 1fr;
    }
    .fields label > small {
        min-height: 0;
    }
}
@media (max-width: 480px) {
    .grid,
    .filters {
        grid-template-columns: 1fr;
    }
    .card {
        min-height: 390px;
    }
}
</style>
