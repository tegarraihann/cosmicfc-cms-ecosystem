<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
const p = defineProps({
    articles: Object,
    categories: Array,
    filters: Object,
    stats: Object,
    can: Object,
});
const filter = ref({
        search: p.filters.search || "",
        status: p.filters.status || "",
        type: p.filters.type || "",
        category_id: p.filters.category_id || "",
    }),
    categoryDialog = ref(false);
const categoryForm = useForm({ id: null, name: "", is_active: true });
const apply = () =>
    router.get(route("admin.news.index"), filter.value, {
        preserveState: true,
        replace: true,
    });
const archive = (a) =>
    confirm(`Arsipkan berita "${a.title}"?`) &&
    router.post(
        route("admin.news.archive", a.id),
        {},
        { preserveScroll: true },
    );
const restore = (a) =>
    router.post(
        route("admin.news.restore", a.id),
        {},
        { preserveScroll: true },
    );
const saveCategory = () =>
    categoryForm.post(route("admin.news.categories.save"), {
        preserveScroll: true,
        onSuccess: () => {
            categoryDialog.value = false;
            categoryForm.reset();
        },
    });
</script>
<template>
    <Head title="Berita & Dokumentasi" /><AuthenticatedLayout
        ><template #page-title>Berita & Dokumentasi</template>
        <main class="page">
            <section class="hero">
                <div>
                    <span>Editorial Workspace</span>
                    <h1>News & <b>Stories.</b></h1>
                    <p>
                        Dokumentasikan laporan pertandingan, kabar klub, dan
                        perjalanan COSMIC.
                    </p>
                </div>
                <div class="hero-actions">
                    <button
                        v-if="can.categories"
                        class="secondary"
                        @click="categoryDialog = true"
                    >
                        Kelola Kategori</button
                    ><Link v-if="can.create" :href="route('admin.news.create')"
                        >+ Tambah Berita</Link
                    >
                </div>
            </section>
            <section class="stats">
                <article>
                    <strong>{{ stats.total }}</strong
                    ><span>Total Berita</span>
                </article>
                <article>
                    <strong>{{ stats.draft }}</strong
                    ><span>Draft</span>
                </article>
                <article>
                    <strong>{{ stats.scheduled }}</strong
                    ><span>Terjadwal</span>
                </article>
                <article>
                    <strong>{{ stats.published }}</strong
                    ><span>Terbit</span>
                </article>
            </section>
            <form class="filters" @submit.prevent="apply">
                <label
                    ><span>Pencarian berita</span
                    ><input
                        v-model="filter.search"
                        placeholder="Cari judul atau ringkasan..." /></label
                ><label
                    ><span>Status publikasi</span
                    ><select v-model="filter.status">
                        <option value="">Semua status</option>
                        <option value="draft">Draft</option>
                        <option value="scheduled">Terjadwal</option>
                        <option value="published">Terbit</option>
                        <option value="archived">Arsip</option>
                    </select></label
                ><label
                    ><span>Jenis konten</span
                    ><select v-model="filter.type">
                        <option value="">Semua jenis</option>
                        <option value="general">Berita umum</option>
                        <option value="match_report">
                            Laporan pertandingan
                        </option>
                    </select></label
                ><label
                    ><span>Kategori</span
                    ><select v-model="filter.category_id">
                        <option value="">Semua kategori</option>
                        <option
                            v-for="c in categories"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.name }}
                        </option>
                    </select></label
                ><button>Terapkan</button>
            </form>
            <section v-if="articles.data.length" class="cards">
                <article v-for="a in articles.data" :key="a.id">
                    <img :src="a.cover_url" :alt="a.title" />
                    <div class="body">
                        <span
                            >{{ a.category_name }} ·
                            {{
                                a.type === "match_report"
                                    ? "Laporan laga"
                                    : "Berita umum"
                            }}</span
                        >
                        <h2>{{ a.title }}</h2>
                        <p>{{ a.excerpt }}</p>
                        <small
                            >{{ a.author_name || "Sistem" }} ·
                            {{
                                a.published_label || "Belum diterbitkan"
                            }}</small
                        >
                    </div>
                    <footer>
                        <i>{{ a.status }}</i>
                        <div>
                            <Link
                                v-if="can.update"
                                :href="route('admin.news.edit', a.id)"
                                >Edit Berita</Link
                            ><button
                                v-if="a.status !== 'archived' && can.archive"
                                class="danger"
                                @click="archive(a)"
                            >
                                Arsipkan</button
                            ><button
                                v-if="a.status === 'archived' && can.archive"
                                @click="restore(a)"
                            >
                                Pulihkan
                            </button>
                        </div>
                    </footer>
                </article>
            </section>
            <div v-else class="empty">
                Belum ada berita. Gunakan tombol “Tambah Berita” untuk memulai
                dokumentasi.
            </div>
            <nav class="pagination">
                <Link
                    v-for="l in articles.links"
                    :key="l.label"
                    :href="l.url || '#'"
                    :class="{ active: l.active, disabled: !l.url }"
                    v-html="l.label"
                />
            </nav>
            <div
                v-if="categoryDialog"
                class="modal"
                @click.self="categoryDialog = false"
            >
                <form class="dialog" @submit.prevent="saveCategory">
                    <header>
                        <div>
                            <span>Master Data</span>
                            <h2>Tambah Kategori</h2>
                        </div>
                        <button type="button" @click="categoryDialog = false">
                            ×
                        </button>
                    </header>
                    <div class="fields">
                        <label
                            ><b>Nama kategori</b
                            ><small>Nama pada filter dan kartu berita.</small
                            ><input
                                v-model="categoryForm.name"
                                placeholder="Contoh: Kabar Klub"
                            /><em>{{ categoryForm.errors.name }}</em></label
                        ><label class="check"
                            ><input
                                v-model="categoryForm.is_active"
                                type="checkbox"
                            /><span
                                ><b>Kategori aktif</b
                                ><small
                                    >Dapat dipilih pada berita baru.</small
                                ></span
                            ></label
                        >
                    </div>
                    <footer>
                        <button type="button" @click="categoryDialog = false">
                            Batal</button
                        ><button class="primary">Simpan Kategori</button>
                    </footer>
                </form>
            </div>
        </main></AuthenticatedLayout
    >
</template>
<style scoped>
.page {
    max-width: 1440px;
    margin: auto;
}
.hero {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    min-height: 250px;
    padding: 35px 40px;
    border: 1px solid #afdafa24;
    background:
        linear-gradient(100deg, #07111efa, #07111e8c),
        url("/Assets/Dokumentasi/Docs-wahyu.jpeg") center/cover;
}
.hero span,
.cards span,
.dialog header span,
.filters span {
    color: var(--color-accent);
    font: 800 0.56rem var(--font-display);
    letter-spacing: 0.15em;
    text-transform: uppercase;
}
.hero h1 {
    margin: 10px 0;
    font: 900 clamp(3rem, 5vw, 5rem) / 0.85 var(--font-display);
    text-transform: uppercase;
}
.hero h1 b {
    color: var(--color-accent);
}
.hero p {
    margin: 0;
    color: #91a2ae;
}
.hero-actions {
    display: flex;
    gap: 9px;
}
.hero-actions a,
.hero-actions button,
.filters > button {
    display: inline-flex;
    min-height: 45px;
    align-items: center;
    padding: 0 18px;
    border: 1px solid var(--color-accent);
    color: #06101c;
    background: var(--color-accent);
    font: 800 0.58rem var(--font-display);
    text-decoration: none;
    text-transform: uppercase;
}
.hero-actions .secondary {
    color: #fff;
    background: #07111ecc;
}
.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-top: 15px;
}
.stats article {
    padding: 20px;
    border: 1px solid #ffffff18;
    background: #07111e;
}
.stats strong,
.stats span {
    display: block;
}
.stats strong {
    font: 900 2.3rem var(--font-display);
}
.stats span {
    color: #718491;
}
.filters {
    display: grid;
    grid-template-columns: 2fr repeat(3, 1fr) auto;
    align-items: end;
    gap: 10px;
    margin-top: 15px;
}
.filters label > span {
    display: block;
    margin-bottom: 7px;
}
.filters input,
.filters select,
.fields input {
    width: 100%;
    height: 48px;
    box-sizing: border-box;
    padding: 0 13px;
    border: 1px solid #ffffff22;
    color: #fff;
    background: #050c14;
}
.cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-top: 18px;
}
.cards > article {
    display: flex;
    min-height: 450px;
    flex-direction: column;
    border: 1px solid #ffffff1d;
    background: #07111e;
}
.cards img {
    width: 100%;
    aspect-ratio: 16/10;
    object-fit: cover;
}
.body {
    padding: 18px;
}
.body h2 {
    margin: 9px 0;
    font: 850 1.35rem/1 var(--font-display);
    text-transform: uppercase;
}
.body p {
    color: #8798a4;
}
.body small {
    color: #657783;
}
.cards footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: auto;
    padding: 13px 18px;
    border-top: 1px solid #ffffff16;
}
.cards footer i {
    color: var(--color-accent);
    font: 700 0.55rem var(--font-display);
    font-style: normal;
    text-transform: uppercase;
}
.cards footer a,
.cards footer button {
    padding: 5px;
    border: 0;
    color: var(--color-accent);
    background: none;
    font: 750 0.55rem var(--font-display);
    text-decoration: none;
    text-transform: uppercase;
}
.cards footer .danger {
    color: #ef8188;
}
.empty {
    margin-top: 18px;
    padding: 55px;
    border: 1px dashed #ffffff24;
    color: #7f909c;
    text-align: center;
}
.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 20px;
}
.pagination a {
    padding: 8px 11px;
    border: 1px solid #ffffff1f;
    color: #889aa6;
    text-decoration: none;
}
.pagination .active {
    color: #06101c;
    background: var(--color-accent);
}
.pagination .disabled {
    opacity: 0.3;
    pointer-events: none;
}
.modal {
    position: fixed;
    z-index: 200;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 20px;
    background: #01050ade;
}
.dialog {
    width: min(560px, 100%);
    border: 1px solid #45b7ff52;
    background: #07111e;
}
.dialog header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 19px 23px;
    border-bottom: 1px solid #ffffff18;
}
.dialog h2 {
    margin: 5px 0 0;
    font: 850 1.3rem var(--font-display);
    text-transform: uppercase;
}
.dialog header button {
    border: 0;
    color: #fff;
    background: none;
    font-size: 1.5rem;
}
.fields {
    display: grid;
    gap: 16px;
    padding: 22px;
}
.fields label b,
.fields label small {
    display: block;
}
.fields label small {
    margin: 5px 0;
    color: #70818d;
}
.fields em {
    color: #ef8188;
}
.check {
    display: flex;
    align-items: center;
    gap: 10px;
}
.check input {
    width: 18px;
    height: 18px;
    accent-color: var(--color-accent);
}
.dialog > footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 16px 22px;
    border-top: 1px solid #ffffff18;
}
.dialog > footer button {
    padding: 12px 16px;
    border: 1px solid #ffffff27;
    color: #fff;
    background: transparent;
}
.dialog > footer .primary {
    color: #06101c;
    background: var(--color-accent);
}
@media (max-width: 1100px) {
    .filters {
        grid-template-columns: 1fr 1fr;
    }
    .cards {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 700px) {
    .hero {
        align-items: flex-start;
        flex-direction: column;
        padding: 26px 22px;
    }
    .hero-actions,
    .hero-actions > * {
        width: 100%;
    }
    .stats {
        grid-template-columns: 1fr 1fr;
    }
    .filters,
    .cards {
        grid-template-columns: 1fr;
    }
    .cards > article {
        min-height: 0;
    }
}
</style>
