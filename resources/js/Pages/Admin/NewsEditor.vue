<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { computed } from "vue";
const p = defineProps({
    article: Object,
    categories: Array,
    matches: Array,
    media: Array,
    canPublish: Boolean,
});
const blank = {
    type: "general",
    category_id: "",
    match_id: "",
    cover_media_id: "",
    title: "",
    excerpt: "",
    body: "",
    seo_title: "",
    seo_description: "",
    status: "draft",
    is_featured: false,
    published_at: "",
    gallery_media_ids: [],
};
const form = useForm(p.article ? { ...blank, ...p.article } : blank);
const cover = computed(() => p.media.find((m) => m.id == form.cover_media_id));
const gallery = computed(() =>
    p.media.filter((m) => form.gallery_media_ids.includes(m.id)),
);
const save = () =>
    p.article
        ? form.put(route("admin.news.update", p.article.id), {
              preserveScroll: true,
          })
        : form.post(route("admin.news.store"), { preserveScroll: true });
</script>
<template>
    <Head
        :title="article ? 'Edit Berita' : 'Tambah Berita'"
    /><AuthenticatedLayout
        ><template #page-title>{{
            article ? "Edit Berita" : "Tambah Berita"
        }}</template>
        <main class="editor">
            <header class="editor-head">
                <div>
                    <span>Editorial Workspace</span>
                    <h1>{{ article ? "Edit" : "Tambah" }} <b>Berita.</b></h1>
                    <p>
                        Lengkapi informasi berita secara bertahap. Slug dibuat
                        otomatis dari judul.
                    </p>
                </div>
                <Link :href="route('admin.news.index')"
                    >← Kembali ke Daftar</Link
                >
            </header>
            <form @submit.prevent="save">
                <section>
                    <header>
                        <span>01</span>
                        <div>
                            <h2>Informasi Utama</h2>
                            <p>
                                Identitas dan keterkaitan berita yang tampil
                                kepada pembaca.
                            </p>
                        </div>
                    </header>
                    <div class="fields">
                        <label
                            ><b>Jenis konten</b
                            ><small
                                >Pilih berita umum atau laporan
                                pertandingan.</small
                            ><select v-model="form.type">
                                <option value="general">Berita umum</option>
                                <option value="match_report">
                                    Laporan pertandingan
                                </option></select
                            ><em>{{ form.errors.type }}</em></label
                        ><label
                            ><b>Kategori berita</b
                            ><small>Digunakan pada filter halaman News.</small
                            ><select v-model="form.category_id">
                                <option value="">Pilih kategori</option>
                                <option
                                    v-for="c in categories"
                                    :key="c.id"
                                    :value="c.id"
                                >
                                    {{ c.name }}
                                </option></select
                            ><em>{{ form.errors.category_id }}</em></label
                        ><label v-if="form.type === 'match_report'" class="full"
                            ><b>Pertandingan terkait</b
                            ><small
                                >Skor, klub, venue, gol, dan assist diambil
                                otomatis dari pertandingan ini.</small
                            ><select v-model="form.match_id">
                                <option value="">
                                    Pilih pertandingan yang sudah selesai
                                </option>
                                <option
                                    v-for="m in matches"
                                    :key="m.id"
                                    :value="m.id"
                                >
                                    {{ m.label }}
                                </option></select
                            ><em>{{ form.errors.match_id }}</em></label
                        ><label class="full"
                            ><b>Judul berita</b
                            ><small
                                >Judul utama pada daftar dan halaman detail
                                berita.</small
                            ><input
                                v-model="form.title"
                                placeholder="Contoh: COSMIC Menang Meyakinkan di Matchday Keempat"
                            /><em>{{ form.errors.title }}</em></label
                        ><label class="full"
                            ><b>Ringkasan berita</b
                            ><small
                                >Ringkasan maksimal 220 karakter untuk kartu
                                berita.</small
                            ><textarea
                                v-model="form.excerpt"
                                rows="3"
                                placeholder="Tuliskan inti berita secara singkat..."
                            ></textarea
                            ><em>{{ form.errors.excerpt }}</em></label
                        >
                    </div>
                </section>
                <section>
                    <header>
                        <span>02</span>
                        <div>
                            <h2>Isi & Dokumentasi</h2>
                            <p>
                                Tuliskan cerita lengkap dan pilih dokumentasi
                                dari Media Library.
                            </p>
                        </div>
                    </header>
                    <div class="fields">
                        <label class="full"
                            ><b>Isi berita</b
                            ><small
                                >Pisahkan antarparagraf menggunakan satu baris
                                kosong.</small
                            ><textarea
                                v-model="form.body"
                                rows="14"
                                placeholder="Tuliskan cerita, laporan pertandingan, atau dokumentasi kegiatan..."
                            ></textarea
                            ><em>{{ form.errors.body }}</em></label
                        ><label
                            ><b>Foto sampul</b
                            ><small
                                >Gambar utama pada daftar, detail, dan share
                                sosial.</small
                            ><select v-model="form.cover_media_id">
                                <option value="">Pilih foto sampul</option>
                                <option
                                    v-for="m in media"
                                    :key="m.id"
                                    :value="m.id"
                                >
                                    {{ m.title }}
                                </option></select
                            ><em>{{ form.errors.cover_media_id }}</em></label
                        >
                        <div class="cover-preview">
                            <img
                                v-if="cover"
                                :src="cover.url"
                                alt="Preview foto sampul"
                            /><span v-else>Foto sampul belum dipilih</span>
                        </div>
                        <label class="full"
                            ><b>Galeri dokumentasi</b
                            ><small
                                >Gunakan Ctrl/Command untuk memilih beberapa
                                gambar.</small
                            ><select v-model="form.gallery_media_ids" multiple>
                                <option
                                    v-for="m in media"
                                    :key="m.id"
                                    :value="m.id"
                                >
                                    {{ m.title }}
                                </option>
                            </select></label
                        >
                        <div v-if="gallery.length" class="gallery full">
                            <img
                                v-for="m in gallery"
                                :key="m.id"
                                :src="m.url"
                                :alt="m.title"
                            />
                        </div>
                    </div>
                </section>
                <section>
                    <header>
                        <span>03</span>
                        <div>
                            <h2>Publikasi & SEO</h2>
                            <p>
                                Atur kapan berita tampil dan informasi mesin
                                pencari.
                            </p>
                        </div>
                    </header>
                    <div class="fields">
                        <label
                            ><b>Status berita</b
                            ><small>Draft tidak tampil kepada publik.</small
                            ><select v-model="form.status">
                                <option value="draft">Draft</option>
                                <option
                                    value="scheduled"
                                    :disabled="!canPublish"
                                >
                                    Terjadwal
                                </option>
                                <option
                                    value="published"
                                    :disabled="!canPublish"
                                >
                                    Terbit
                                </option>
                                <option value="archived">Arsip</option>
                            </select></label
                        ><label v-if="form.status === 'scheduled'"
                            ><b>Waktu publikasi</b
                            ><small
                                >Berita tampil otomatis setelah waktu
                                ini.</small
                            ><input
                                v-model="form.published_at"
                                type="datetime-local"
                            /><em>{{ form.errors.published_at }}</em></label
                        ><label class="check full"
                            ><input
                                v-model="form.is_featured"
                                type="checkbox"
                            /><span
                                ><b>Berita unggulan</b
                                ><small
                                    >Tampilkan sebagai artikel utama pada
                                    halaman News.</small
                                ></span
                            ></label
                        ><label
                            ><b>Judul SEO</b
                            ><small
                                >Opsional; judul berita digunakan jika
                                kosong.</small
                            ><input
                                v-model="form.seo_title"
                                maxlength="70"
                                placeholder="Judul untuk mesin pencari" /></label
                        ><label
                            ><b>Deskripsi SEO</b
                            ><small
                                >Opsional; ringkasan berita digunakan jika
                                kosong.</small
                            ><textarea
                                v-model="form.seo_description"
                                rows="3"
                                maxlength="170"
                            ></textarea>
                        </label>
                        <div class="seo-article-preview full">
                            <span>Preview saat berita dibagikan</span>
                            <img
                                v-if="cover"
                                :src="cover.url"
                                :alt="cover.title || 'Cover berita'"
                            />
                            <div v-else class="seo-preview-placeholder">
                                Pilih cover berita untuk melengkapi preview
                            </div>
                            <strong>{{
                                form.seo_title || form.title || "Judul berita"
                            }}</strong>
                            <small>{{
                                form.seo_description ||
                                form.excerpt ||
                                "Ringkasan berita akan tampil di sini."
                            }}</small>
                        </div>
                    </div>
                </section>
                <footer class="actions">
                    <Link :href="route('admin.news.index')">Batal</Link
                    ><button :disabled="form.processing">
                        {{
                            form.processing
                                ? "Menyimpan..."
                                : article
                                  ? "Simpan Perubahan"
                                  : "Buat Berita"
                        }}
                    </button>
                </footer>
            </form>
        </main></AuthenticatedLayout
    >
</template>
<style scoped>
.editor {
    max-width: 1100px;
    margin: auto;
}
.editor-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    padding: 34px 38px;
    border: 1px solid #afdafa24;
    background:
        linear-gradient(100deg, #07111efa, #07111e8c),
        url("/Assets/Dokumentasi/Docs-wahyu.jpeg") center/cover;
}
.editor-head > div > span,
section > header > span {
    color: var(--color-accent);
    font: 800 0.57rem var(--font-display);
    letter-spacing: 0.17em;
    text-transform: uppercase;
}
.editor-head h1 {
    margin: 10px 0;
    font: 900 clamp(3rem, 5vw, 5rem) / 0.85 var(--font-display);
    text-transform: uppercase;
}
.editor-head h1 b {
    color: var(--color-accent);
}
.editor-head p {
    margin: 0;
    color: #91a2ae;
}
.editor-head > a {
    padding: 13px 16px;
    border: 1px solid #ffffff31;
    color: #fff;
    font: 750 0.58rem var(--font-display);
    text-decoration: none;
    text-transform: uppercase;
}
form > section {
    margin-top: 16px;
    border: 1px solid #ffffff1b;
    background: #07111e;
}
section > header {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 19px 23px;
    border-bottom: 1px solid #ffffff16;
}
section > header > span {
    display: grid;
    width: 36px;
    height: 36px;
    place-items: center;
    border: 1px solid #45b7ff45;
}
section h2 {
    margin: 0;
    font: 850 1.25rem var(--font-display);
    text-transform: uppercase;
}
section header p {
    margin: 4px 0 0;
    color: #70828f;
}
.fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    padding: 24px;
}
.fields label b,
.fields label small {
    display: block;
}
.fields label b {
    font: 750 0.62rem var(--font-display);
    text-transform: uppercase;
}
.fields label small {
    min-height: 26px;
    margin: 5px 0;
    color: #70828f;
}
.fields input,
.fields select,
.fields textarea {
    width: 100%;
    box-sizing: border-box;
    margin-top: 5px;
    padding: 13px;
    border: 1px solid #ffffff24;
    color: #fff;
    background: #050c14;
}
.fields select[multiple] {
    min-height: 145px;
}
.fields em {
    display: block;
    margin-top: 5px;
    color: #ef8188;
}
.full {
    grid-column: 1/-1;
}
.cover-preview {
    display: grid;
    min-height: 130px;
    place-items: center;
    border: 1px dashed #ffffff27;
    color: #657783;
}
.cover-preview img {
    width: 100%;
    height: 160px;
    object-fit: cover;
}
.seo-article-preview {
    overflow: hidden;
    border: 1px solid #45b7ff30;
    background: #050c14;
}
.seo-article-preview > span,
.seo-article-preview strong,
.seo-article-preview small {
    display: block;
    margin: 0;
    padding-inline: 16px;
}
.seo-article-preview > span {
    padding-block: 13px;
    color: #8acfff;
    font: 750 0.56rem var(--font-display);
    letter-spacing: 0.09em;
    text-transform: uppercase;
}
.seo-article-preview img,
.seo-preview-placeholder {
    width: 100%;
    aspect-ratio: 1.91/1;
    object-fit: cover;
    background: #0a1825;
}
.seo-preview-placeholder {
    display: grid;
    place-items: center;
    color: #657783;
    font-size: 0.68rem;
}
.seo-article-preview strong {
    padding-top: 15px;
    color: #eef5fa;
    font-size: 0.86rem;
    overflow-wrap: anywhere;
}
.seo-article-preview small {
    min-height: 0 !important;
    padding-top: 7px;
    padding-bottom: 16px;
    color: #8193a0;
    line-height: 1.5;
}
.gallery {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}
.gallery img {
    width: 100%;
    aspect-ratio: 4/3;
    object-fit: cover;
}
.check {
    display: flex !important;
    align-items: center;
    gap: 12px;
    padding: 15px;
    border: 1px solid #ffffff1c;
}
.check input {
    width: 18px !important;
    height: 18px;
    margin: 0 !important;
    accent-color: var(--color-accent);
}
.check small {
    min-height: 0 !important;
}
.actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 22px 0 50px;
}
.actions a,
.actions button {
    min-width: 140px;
    padding: 14px 18px;
    border: 1px solid #ffffff2a;
    color: #fff;
    background: transparent;
    font: 800 0.6rem var(--font-display);
    text-align: center;
    text-decoration: none;
    text-transform: uppercase;
}
.actions button {
    border-color: var(--color-accent);
    color: #06101c;
    background: var(--color-accent);
}
@media (max-width: 700px) {
    .editor-head {
        align-items: flex-start;
        flex-direction: column;
        gap: 22px;
        padding: 26px 22px;
    }
    .editor-head > a {
        width: 100%;
        text-align: center;
    }
    .fields {
        grid-template-columns: 1fr;
        padding: 19px;
    }
    .full {
        grid-column: auto;
    }
    .gallery {
        grid-template-columns: repeat(2, 1fr);
    }
    .actions {
        padding-inline: 0;
    }
    .actions a,
    .actions button {
        flex: 1;
        min-width: 0;
    }
}
</style>
