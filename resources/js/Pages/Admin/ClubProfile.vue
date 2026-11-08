<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import MediaPicker from "@/Components/Admin/MediaPicker.vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
const props = defineProps({
    profile: Object,
    published: Object,
    hasChanges: Boolean,
    canPublish: Boolean,
});
const page = usePage(),
    tab = ref("identity"),
    uploadError = ref("");
const profileData = JSON.parse(JSON.stringify(props.profile));
profileData.seo ??= {};
profileData.seo.indexing_enabled ??= true;
profileData.seo.pages ??= {};
for (const key of ["home", "schedule", "league", "team", "news"]) {
    profileData.seo.pages[key] ??= { title: "", description: "" };
}
const form = useForm(profileData);
const tabs = [
    ["identity", "Identitas & Branding"],
    ["location", "Lokasi"],
    ["social", "Media Sosial"],
    ["seo", "SEO & Preview Tautan"],
];
const seoPages = [
    ["home", "Beranda", "Halaman utama website COSMIC."],
    ["schedule", "Jadwal", "Jadwal, pertandingan live, dan hasil."],
    ["league", "Liga", "Klasemen dan statistik kompetisi."],
    ["team", "Tim", "Daftar pemain dan staf COSMIC."],
    ["news", "News", "Daftar berita dan dokumentasi klub."],
];
const socialPlatforms = [
    ["instagram", "Instagram"],
    ["tiktok", "TikTok"],
    ["youtube", "YouTube"],
];
function save() {
    form.put(route("admin.club-profile.update"), { preserveScroll: true });
}
function publishProfile() {
    if (confirm("Publikasikan profil klub ke seluruh website?"))
        router.post(
            route("admin.club-profile.publish"),
            {},
            { preserveScroll: true },
        );
}
function discard() {
    if (confirm("Batalkan seluruh perubahan profil?"))
        router.post(
            route("admin.club-profile.discard"),
            {},
            { preserveScroll: true },
        );
}
function addSocial() {
    form.socials.push({
        platform: "instagram",
        custom_label: "",
        url: "",
        handle: "",
        active: true,
    });
}
function removeSocial(index) {
    form.socials.splice(index, 1);
}
</script>
<template>
    <Head title="Pengaturan Website" /><AuthenticatedLayout
        ><template #page-title>Pengaturan Website</template>
        <div class="profile-page">
            <div v-if="page.props.flash?.success" class="notice">
                {{ page.props.flash.success }}
            </div>
            <header class="profile-hero">
                <div>
                    <span>Website Settings</span>
                    <h1>Pengaturan Website<b>.</b></h1>
                    <p>
                        Kelola identitas, tampilan, dan informasi yang digunakan
                        pada website COSMIC.
                    </p>
                </div>
                <div class="state">
                    <i :class="{ dirty: hasChanges }"></i>
                    <div>
                        <strong>{{
                            hasChanges ? "Draft berubah" : "Sudah sinkron"
                        }}</strong
                        ><small>Versi publik {{ published.version }}</small>
                    </div>
                </div>
            </header>
            <form @submit.prevent="save">
                <div class="tabs">
                    <button
                        v-for="item in tabs"
                        :key="item[0]"
                        type="button"
                        :class="{ active: tab === item[0] }"
                        @click="tab = item[0]"
                    >
                        {{ item[1] }}
                    </button>
                </div>
                <main class="panel">
                    <header>
                        <div>
                            <span>Website Settings</span>
                            <h2>
                                {{ tabs.find((item) => item[0] === tab)?.[1] }}
                            </h2>
                        </div>
                        <small>Draft website</small>
                    </header>
                    <div v-show="tab === 'identity'" class="fields">
                        <div class="settings-group-heading full">
                            <span>Identitas Klub</span>
                            <p>
                                Informasi utama klub yang tampil pada navbar,
                                hero, section Tentang COSMIC, dan footer.
                            </p>
                        </div>
                        <label class="full"
                            >Nama klub navbar &amp; footer<input
                                v-model="form.short_name"
                            /><small
                                >Ditampilkan di samping logo pada navbar dan
                                footer seluruh halaman.</small
                            ><em>{{ form.errors.short_name }}</em></label
                        ><label class="full"
                            >Slogan footer<input v-model="form.tagline" /><small
                                >Ditampilkan pada baris paling bawah
                                footer.</small
                            ></label
                        ><label
                            >Tahun berdiri hero &amp; footer<input
                                v-model="form.founded_year"
                                type="number"
                                min="1900"
                            /><small
                                >Digunakan pada teks “Est.” di hero Beranda dan
                                tahun copyright footer.</small
                            ></label
                        ><label
                            >Tanggal berdiri Tentang COSMIC<input
                                v-model="form.founded_at"
                                type="date"
                            /><small
                                >Ditampilkan sebagai tanggal resmi berdiri pada
                                section Tentang COSMIC.</small
                            ></label
                        >
                        <div class="settings-group-heading full separated">
                            <span>Branding Website</span>
                            <p>
                                Kelola logo, ikon browser, dan warna utama yang
                                digunakan pada seluruh halaman publik.
                            </p>
                        </div>
                        <label class="full"
                            >Logo navbar &amp; footer<input
                                v-model="form.logo_url" /><small
                                >Logo utama yang tampil pada navbar dan footer
                                seluruh halaman.</small
                            ><MediaPicker
                                v-model="form.logo_url"
                                folder="general" /></label
                        ><label class="full"
                            >Ikon tab browser<input
                                v-model="form.favicon_url" /><small
                                >Ikon kecil yang tampil pada tab browser halaman
                                Beranda.</small
                            ><MediaPicker
                                v-model="form.favicon_url"
                                folder="general" /></label
                        ><label
                            >Warna background website
                            <div class="color">
                                <input v-model="form.primary_color" /><input
                                    v-model="form.primary_color"
                                    type="color"
                                />
                            </div>
                            <small
                                >Warna dasar yang digunakan pada seluruh halaman
                                publik.</small
                            ></label
                        ><label
                            >Warna tombol &amp; highlight
                            <div class="color">
                                <input v-model="form.accent_color" /><input
                                    v-model="form.accent_color"
                                    type="color"
                                />
                            </div>
                            <small
                                >Digunakan pada tombol, link aktif, garis, dan
                                elemen penekanan.</small
                            ></label
                        >
                        <p v-if="uploadError" class="form-error">
                            {{ uploadError }}
                        </p>
                    </div>
                    <div v-show="tab === 'location'" class="fields">
                        <label
                            >Kota Tentang &amp; footer<input
                                v-model="form.city"
                            /><small
                                >Ditampilkan pada section Tentang COSMIC dan
                                alamat singkat footer.</small
                            ></label
                        ><label
                            >Negara Tentang &amp; footer<input
                                v-model="form.country"
                            /><small
                                >Ditampilkan bersama kota pada section Tentang
                                COSMIC dan footer.</small
                            ></label
                        ><label
                            >Home base Tentang COSMIC<input
                                v-model="form.home_base"
                            /><small
                                >Ditampilkan pada kartu identitas di section
                                Tentang COSMIC.</small
                            ></label
                        >
                    </div>
                    <div v-show="tab === 'social'" class="repeaters">
                        <div class="social-intro">
                            <div>
                                <strong>Media Sosial Footer</strong>
                                <p>
                                    Kelola tautan Instagram, TikTok, dan YouTube
                                    yang tampil pada footer website.
                                </p>
                            </div>
                            <span>{{ form.socials.length }} / 3 kanal</span>
                        </div>
                        <article
                            v-for="(social, index) in form.socials"
                            :key="index"
                        >
                            <header>
                                <div>
                                    <span>{{
                                        String(index + 1).padStart(2, "0")
                                    }}</span
                                    ><strong>Social Media</strong>
                                </div>
                                <button
                                    type="button"
                                    @click="removeSocial(index)"
                                >
                                    Hapus
                                </button>
                            </header>
                            <div class="social-fields">
                                <label
                                    >Ikon media sosial footer
                                    <select v-model="social.platform">
                                        <option
                                            v-for="platform in socialPlatforms"
                                            :key="platform[0]"
                                            :value="platform[0]"
                                        >
                                            {{ platform[1] }}
                                        </option>
                                    </select>
                                    <small
                                        >Menentukan ikon media sosial yang
                                        digunakan pada footer.</small
                                    >
                                </label>
                                <label
                                    >Link media sosial footer
                                    <input
                                        v-model="social.url"
                                        type="url"
                                        placeholder="https://instagram.com/cosmic"
                                    />
                                    <small
                                        >Alamat tujuan ketika ikon di footer
                                        ditekan.</small
                                    >
                                </label>
                                <label class="check status-check"
                                    ><input
                                        v-model="social.active"
                                        type="checkbox"
                                    />
                                    <span
                                        ><b>Tampilkan di footer</b
                                        ><small>{{
                                            social.active
                                                ? "Ikon tampil pada footer"
                                                : "Ikon disembunyikan dari footer"
                                        }}</small></span
                                    >
                                </label>
                            </div>
                            <p
                                v-if="form.errors[`socials.${index}.url`]"
                                class="social-error"
                            >
                                {{ form.errors[`socials.${index}.url`] }}
                            </p>
                            <p
                                v-if="
                                    form.errors[`socials.${index}.custom_label`]
                                "
                                class="social-error"
                            >
                                {{
                                    form.errors[`socials.${index}.custom_label`]
                                }}
                            </p>
                        </article>
                        <button
                            type="button"
                            class="add"
                            :disabled="form.socials.length >= 3"
                            @click="addSocial"
                        >
                            + Tambah Social Media
                        </button>
                    </div>
                    <div v-show="tab === 'seo'" class="fields">
                        <div class="settings-group-heading full">
                            <span>SEO Utama &amp; Preview Tautan</span>
                            <p>
                                Atur informasi yang dibaca Google dan tampilan
                                saat tautan website dibagikan ke media sosial.
                            </p>
                        </div>
                        <label class="full"
                            >Judul utama website<input
                                v-model="form.seo.default_title"
                                maxlength="70"
                            /><small
                                >Digunakan sebagai judul cadangan pada tab
                                browser, Google, dan preview tautan ·
                                {{ form.seo.default_title?.length || 0 }} /
                                70</small
                            ></label
                        ><label class="full"
                            >Deskripsi utama website<textarea
                                v-model="form.seo.default_description"
                                rows="4"
                                maxlength="170"
                            /><small
                                >Digunakan jika halaman belum memiliki
                                deskripsi khusus ·
                                {{
                                    form.seo.default_description?.length || 0
                                }}
                                / 170</small
                            ></label
                        ><label class="full"
                            >Kata kunci pencarian<input
                                v-model="form.seo.keywords"
                            /><small
                                >Kata atau frasa yang menjelaskan website,
                                pisahkan dengan koma.</small
                            ></label
                        >
                        <label class="full seo-media-field">
                            Gambar default preview tautan
                            <small
                                >Dipakai saat artikel atau pertandingan belum
                                memiliki gambar sendiri. Rasio yang disarankan
                                1200 × 630 piksel.</small
                            >
                            <MediaPicker
                                v-model="form.share_image_url"
                                folder="general"
                            />
                        </label>
                        <label class="check full seo-indexing">
                            <input
                                v-model="form.seo.indexing_enabled"
                                type="checkbox"
                            />
                            <span>
                                <b>Izinkan website tampil di mesin pencari</b>
                                <small
                                    >Nonaktifkan hanya saat website belum siap
                                    dipublikasikan.</small
                                >
                            </span>
                        </label>
                        <div class="seo-preview search-preview">
                            <span>Preview hasil pencarian</span>
                            <strong>{{
                                form.seo.default_title || "Judul website"
                            }}</strong>
                            <small>{{
                                form.seo.default_description ||
                                "Deskripsi website akan tampil di sini."
                            }}</small>
                        </div>
                        <div class="seo-preview share-preview">
                            <span>Preview saat tautan dibagikan</span>
                            <img
                                v-if="form.share_image_url"
                                :src="form.share_image_url"
                                alt="Preview gambar tautan"
                            />
                            <div v-else class="share-placeholder">
                                Gambar preview belum dipilih
                            </div>
                            <strong>{{
                                form.seo.default_title || "Judul website"
                            }}</strong>
                            <small>{{
                                form.seo.default_description ||
                                "Deskripsi website akan tampil di sini."
                            }}</small>
                        </div>
                        <div class="settings-group-heading full separated">
                            <span>SEO Per Halaman</span>
                            <p>
                                Beri judul dan deskripsi yang lebih spesifik
                                agar setiap halaman mudah dikenali oleh pengguna
                                dan mesin pencari.
                            </p>
                        </div>
                        <article
                            v-for="seoPage in seoPages"
                            :key="seoPage[0]"
                            class="seo-page-card full"
                        >
                            <header>
                                <strong>{{ seoPage[1] }}</strong>
                                <small>{{ seoPage[2] }}</small>
                            </header>
                            <label>
                                Judul halaman
                                <input
                                    v-model="form.seo.pages[seoPage[0]].title"
                                    maxlength="70"
                                />
                                <small
                                    >{{
                                        form.seo.pages[seoPage[0]].title
                                            ?.length || 0
                                    }}
                                    / 70 karakter</small
                                >
                            </label>
                            <label>
                                Deskripsi halaman
                                <textarea
                                    v-model="
                                        form.seo.pages[seoPage[0]].description
                                    "
                                    rows="3"
                                    maxlength="170"
                                />
                                <small
                                    >{{
                                        form.seo.pages[seoPage[0]].description
                                            ?.length || 0
                                    }}
                                    / 170 karakter</small
                                >
                            </label>
                        </article>
                    </div>
                </main>
                <footer class="savebar">
                    <div>
                        <strong>Perubahan disimpan sebagai draft</strong
                        ><small
                            >Publik terakhir:
                            {{ published.published_at || "Data awal" }}</small
                        >
                    </div>
                    <div>
                        <button
                            v-if="hasChanges"
                            type="button"
                            class="secondary"
                            @click="discard"
                        >
                            Batalkan</button
                        ><a
                            :href="route('admin.club-profile.preview')"
                            target="_blank"
                            >Preview</a
                        ><button :disabled="form.processing">
                            Simpan Draft</button
                        ><button
                            v-if="canPublish"
                            type="button"
                            class="publish"
                            :disabled="!hasChanges"
                            @click="publishProfile"
                        >
                            Publikasikan
                        </button>
                    </div>
                </footer>
            </form>
        </div></AuthenticatedLayout
    >
</template>
<style scoped>
.profile-page {
    max-width: 1400px;
    margin: auto;
}
.notice {
    margin-bottom: 14px;
    padding: 13px 16px;
    border: 1px solid #45b7ff66;
    color: #b8e3ff;
    background: #45b7ff12;
    font-size: 0.72rem;
}
.profile-hero {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    min-height: 205px;
    padding: 34px 38px;
    border: 1px solid #afdaff24;
    background:
        linear-gradient(100deg, #07111efa, #07111ebd),
        url("/Assets/Dokumentasi/Docs-wahyu.jpeg") center/cover;
}
.profile-hero > div > span,
.panel > header span {
    color: var(--color-accent);
    font: 800 0.56rem var(--font-display);
    letter-spacing: 0.18em;
    text-transform: uppercase;
}
.profile-hero h1 {
    margin: 10px 0 8px;
    font: 900 clamp(3rem, 5vw, 5.2rem) / 0.82 var(--font-display);
    text-transform: uppercase;
}
.profile-hero h1 b {
    color: var(--color-accent);
}
.profile-hero p {
    margin: 0;
    color: #8c9ca8;
    font-size: 0.7rem;
}
.state {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 13px 16px;
    border: 1px solid #ffffff20;
    background: #05090f99;
}
.state i {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--color-accent);
    box-shadow: 0 0 10px #45b7ff;
}
.state i.dirty {
    background: #ffb650;
    box-shadow: 0 0 10px #ffb650;
}
.state strong,
.state small {
    display: block;
}
.state strong {
    font: 750 0.6rem var(--font-display);
    text-transform: uppercase;
}
.state small {
    margin-top: 4px;
    color: #758692;
    font-size: 0.53rem;
}
.tabs {
    display: flex;
    margin-top: 14px;
    overflow: auto;
    border-bottom: 1px solid #ffffff1f;
}
.tabs button {
    padding: 15px 18px;
    border: 0;
    border-bottom: 2px solid transparent;
    color: #788995;
    background: none;
    font: 800 0.58rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
    white-space: nowrap;
    cursor: pointer;
}
.tabs button.active {
    border-color: var(--color-accent);
    color: white;
}
.panel {
    border: 1px solid #afdaff20;
    background: #07111e;
}
.panel > header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 23px;
    border-bottom: 1px solid #ffffff17;
}
.panel h2 {
    margin: 5px 0 0;
    font: 850 1.3rem var(--font-display);
    text-transform: uppercase;
}
.panel header small {
    color: #687a87;
}
.fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
    padding: 24px;
}
.fields label {
    color: #91a1ad;
    font: 750 0.56rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.fields label.full {
    grid-column: 1/-1;
}
.settings-group-heading {
    grid-column: 1/-1;
    padding-bottom: 14px;
    border-bottom: 1px solid #ffffff17;
}
.settings-group-heading.separated {
    margin-top: 10px;
    padding-top: 27px;
    border-top: 1px solid #ffffff17;
}
.settings-group-heading span {
    color: #eef5fa;
    font: 800 0.72rem var(--font-display);
    letter-spacing: 0.09em;
    text-transform: uppercase;
}
.settings-group-heading p {
    max-width: 680px;
    margin: 7px 0 0;
    color: #718493;
    font: 500 0.62rem/1.55 Arial;
}
.fields input,
.fields textarea,
.fields select,
.repeaters input,
.repeaters select {
    width: 100%;
    box-sizing: border-box;
    margin-top: 8px;
    padding: 12px;
    border: 1px solid #ffffff24;
    border-radius: 3px;
    outline: 0;
    color: white;
    background: #050b13;
    font: 500 0.72rem Arial;
}
.fields input:focus,
.fields textarea:focus {
    border-color: #45b7ff99;
}
.fields em {
    display: block;
    margin-top: 5px;
    color: #ff8f96;
    font: 500 0.53rem Arial;
    font-style: normal;
    text-transform: none;
}
.fields small {
    display: block;
    margin-top: 5px;
    color: #627583;
    font: 500 0.52rem Arial;
    text-transform: none;
}
.seo-media-field > small {
    margin-bottom: 10px;
}
.seo-indexing {
    box-sizing: border-box;
    padding: 15px;
    border: 1px solid #45b7ff35;
    background: #45b7ff0a;
}
.seo-indexing span b,
.seo-indexing span small {
    display: block;
}
.seo-indexing span b {
    color: #dce8f0;
    font: 750 0.6rem var(--font-display);
}
.seo-preview {
    min-width: 0;
    padding: 17px;
    border: 1px solid #ffffff17;
    background: #050c14;
}
.seo-preview > span {
    display: block;
    margin-bottom: 12px;
    color: #718493;
    font: 750 0.55rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.seo-preview strong,
.seo-preview small {
    display: block;
    overflow-wrap: anywhere;
}
.search-preview strong {
    color: #8ab4f8;
    font: 500 1.02rem Arial;
}
.search-preview small,
.share-preview small {
    margin-top: 7px;
    color: #9aa7b1;
    font: 500 0.66rem/1.5 Arial;
}
.share-preview img,
.share-placeholder {
    width: 100%;
    aspect-ratio: 1.91 / 1;
    margin-bottom: 12px;
    object-fit: cover;
    background: #0a1825;
}
.share-placeholder {
    display: grid;
    place-items: center;
    color: #627583;
    font: 650 0.58rem Arial;
}
.share-preview strong {
    color: #eef5fa;
    font: 700 0.78rem Arial;
}
.seo-page-card {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    padding: 18px;
    border: 1px solid #ffffff17;
    background: #050c14;
}
.seo-page-card > header {
    grid-column: 1/-1;
    padding-bottom: 12px;
    border-bottom: 1px solid #ffffff12;
}
.seo-page-card > header strong,
.seo-page-card > header small {
    display: block;
}
.seo-page-card > header strong {
    color: #eaf5ff;
    font: 800 0.7rem var(--font-display);
    text-transform: uppercase;
}
.seo-page-card > header small {
    margin-top: 5px;
}
.media-input {
    display: grid;
    grid-template-columns: 1fr auto auto;
    gap: 8px;
    align-items: end;
}
.upload,
.media-input select {
    padding: 11px;
    border: 1px solid #45b7ff55;
    color: #8fd3ff;
    background: #06101b;
    cursor: pointer;
}
.upload input {
    display: none;
}
.media-preview {
    width: 220px;
    height: 130px;
    margin-top: 9px;
    border: 1px solid #ffffff20;
    object-fit: cover;
}
.color {
    display: grid;
    grid-template-columns: 1fr 48px;
    gap: 8px;
}
.color input[type="color"] {
    height: 42px;
    padding: 3px;
}
.check {
    display: flex !important;
    align-items: center;
    gap: 9px;
    cursor: pointer;
}
.check input[type="checkbox"] {
    appearance: none;
    -webkit-appearance: none;
    display: grid;
    flex: 0 0 20px;
    width: 20px !important;
    height: 20px;
    margin: 0 !important;
    padding: 0 !important;
    place-content: center;
    border: 1px solid #526675 !important;
    border-radius: 4px;
    outline: 0;
    background: #030911 !important;
    cursor: pointer;
    transition:
        border-color 0.18s ease,
        background-color 0.18s ease,
        box-shadow 0.18s ease;
}
.check input[type="checkbox"]::before {
    width: 10px;
    height: 6px;
    content: "";
    border-bottom: 2px solid #03101a;
    border-left: 2px solid #03101a;
    opacity: 0;
    transform: translateY(-1px) rotate(-45deg) scale(0.65);
    transition:
        opacity 0.12s ease,
        transform 0.12s ease;
}
.check input[type="checkbox"]:hover {
    border-color: var(--color-accent) !important;
}
.check input[type="checkbox"]:checked {
    border-color: var(--color-accent) !important;
    background: var(--color-accent) !important;
    box-shadow: 0 0 0 3px #45b7ff20;
}
.check input[type="checkbox"]:checked::before {
    opacity: 1;
    transform: translateY(-1px) rotate(-45deg) scale(1);
}
.check input[type="checkbox"]:focus-visible {
    border-color: #ffffff !important;
    box-shadow: 0 0 0 3px #45b7ff42;
}
.check input[type="checkbox"]:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
.repeaters {
    padding: 22px;
}
.social-intro {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 16px;
    padding: 16px 18px;
    border: 1px solid #45b7ff2e;
    background: #45b7ff0a;
}
.social-intro strong {
    color: #eaf5ff;
    font: 800 0.72rem var(--font-display);
    text-transform: uppercase;
}
.social-intro p {
    margin: 6px 0 0;
    color: #768a98;
    font-size: 0.65rem;
    line-height: 1.5;
}
.social-intro > span {
    flex: 0 0 auto;
    color: var(--color-accent);
    font: 750 0.56rem var(--font-display);
    text-transform: uppercase;
}
.repeaters article {
    margin-bottom: 12px;
    padding: 18px;
    border: 1px solid #ffffff17;
    background: #050c14;
}
.repeaters article > header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #ffffff12;
}
.repeaters article > header div {
    display: flex;
    align-items: center;
    gap: 10px;
}
.repeaters article > header span {
    display: grid;
    width: 27px;
    height: 27px;
    place-items: center;
    border: 1px solid #45b7ff55;
    color: var(--color-accent);
    font: 800 0.56rem var(--font-display);
}
.repeaters article > header strong {
    color: #dce8f0;
    font: 800 0.62rem var(--font-display);
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.social-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
.social-fields label {
    color: #91a1ad;
    font: 750 0.56rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.social-fields label.wide {
    grid-column: span 1;
}
.social-fields label small {
    display: block;
    margin-top: 6px;
    color: #5f7483;
    font: 500 0.54rem Arial;
    letter-spacing: 0;
    line-height: 1.4;
    text-transform: none;
}
.status-check {
    align-self: end;
    min-height: 63px;
    padding: 11px 13px;
    border: 1px solid #ffffff14;
    background: #08131e;
}
.status-check:has(input:checked) {
    border-color: #45b7ff55;
    background: #071827;
}
.status-check span b,
.status-check span small {
    display: block;
}
.status-check span b {
    color: #dce7ef;
    font: 750 0.58rem var(--font-display);
    text-transform: uppercase;
}
.status-check span small {
    margin-top: 5px;
    color: #647987;
    font-size: 0.54rem;
}
.maintenance-check {
    align-self: stretch;
    box-sizing: border-box;
    margin-top: 0;
}
.social-error {
    margin: 10px 0 0;
    color: #ff9299;
    font-size: 0.62rem;
}
.repeaters article > * {
    margin-top: 0;
}
.repeaters button {
    border: 0;
    color: #ff9299;
    background: none;
    font: 750 0.52rem var(--font-display);
    text-transform: uppercase;
    cursor: pointer;
}
.repeaters .add {
    padding: 11px 14px;
    border: 1px solid #45b7ff55;
    color: #8fd3ff;
}
.repeaters .add:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
.form-error {
    color: #ff9299;
}
.savebar {
    position: sticky;
    z-index: 10;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 14px;
    padding: 15px 20px;
    border: 1px solid #45b7ff38;
    background: #07111ef2;
    backdrop-filter: blur(10px);
}
.savebar strong,
.savebar small {
    display: block;
}
.savebar strong {
    font: 750 0.6rem var(--font-display);
    text-transform: uppercase;
}
.savebar small {
    margin-top: 4px;
    color: #6d7f8c;
    font-size: 0.54rem;
}
.savebar > div:last-child {
    display: flex;
    gap: 8px;
}
.savebar button,
.savebar a {
    padding: 12px 15px;
    border: 1px solid #ffffff25;
    border-radius: 3px;
    color: white;
    background: transparent;
    font: 800 0.55rem var(--font-display);
    text-decoration: none;
    text-transform: uppercase;
    cursor: pointer;
}
.savebar button:not(.secondary),
.savebar .publish {
    color: #03101b;
    border-color: var(--color-accent);
    background: var(--color-accent);
}
.savebar button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
@media (max-width: 800px) {
    .profile-hero {
        align-items: flex-start;
        flex-direction: column;
        justify-content: flex-end;
    }
    .state {
        width: 100%;
    }
    .repeaters article {
        grid-template-columns: 1fr 1fr;
    }
    .savebar {
        align-items: flex-start;
        flex-direction: column;
        gap: 13px;
    }
    .savebar > div:last-child {
        width: 100%;
        flex-wrap: wrap;
    }
    .savebar button,
    .savebar a {
        flex: 1;
        text-align: center;
    }
}
@media (max-width: 560px) {
    .profile-hero {
        padding: 26px 21px;
    }
    .fields {
        grid-template-columns: 1fr;
        padding: 18px;
    }
    .fields label.full {
        grid-column: auto;
    }
    .seo-preview,
    .seo-page-card,
    .seo-page-card > header,
    .seo-page-card label {
        grid-column: 1/-1;
    }
    .media-input {
        grid-template-columns: 1fr;
    }
    .repeaters article {
        grid-template-columns: 1fr;
    }
    .social-intro {
        flex-direction: column;
    }
    .social-fields {
        grid-template-columns: 1fr;
    }
    .tabs button {
        padding-inline: 13px;
    }
    .savebar > div:last-child {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }
}
</style>
