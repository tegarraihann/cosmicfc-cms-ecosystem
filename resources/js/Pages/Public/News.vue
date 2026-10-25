<script setup>
import SiteNavbar from "@/Components/Public/SiteNavbar.vue";
import SiteFooter from "@/Components/Public/SiteFooter.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref } from "vue";
const p = defineProps({
    articles: Object,
    featured: Object,
    categories: Array,
    filters: Object,
});
const filter = ref({
    search: p.filters.search || "",
    category: p.filters.category || "",
});
const apply = () =>
    router.get(route("news.index"), filter.value, {
        preserveState: true,
        replace: true,
    });
</script>
<template>
    <Head title="News | COSMIC"
        ><meta
            name="description"
            content="Berita, laporan pertandingan, dan cerita terbaru COSMIC."
    /></Head>
    <main>
        <section class="hero">
            <SiteNavbar />
            <div class="shell hero-copy">
                <span>Stories From The Pitch</span>
                <h1>Latest <b>News.</b></h1>
                <p>
                    Laporan pertandingan, kabar klub, dan cerita dari perjalanan
                    COSMIC.
                </p>
            </div>
        </section>
        <section class="listing shell">
            <form class="filters" @submit.prevent="apply">
                <label
                    ><span>Cari berita</span
                    ><input
                        v-model="filter.search"
                        placeholder="Judul atau isi berita..." /></label
                ><label
                    ><span>Kategori</span
                    ><select v-model="filter.category">
                        <option value="">Semua kategori</option>
                        <option
                            v-for="c in categories"
                            :key="c.id"
                            :value="c.id"
                        >
                            {{ c.name }}
                        </option>
                    </select></label
                ><button>Terapkan Filter</button>
            </form>
            <Link
                v-if="featured"
                class="featured"
                :href="featured.href"
                :style="{ '--cover': `url('${featured.cover}')` }"
                ><div>
                    <span
                        >{{ featured.category }} ·
                        {{ featured.published_at }}</span
                    >
                    <h2>{{ featured.title }}</h2>
                    <p>{{ featured.excerpt }}</p>
                    <b>Baca Cerita →</b>
                </div></Link
            >
            <header class="section-title">
                <span>Arsip COSMIC</span>
                <h2>Berita Terbaru.</h2>
            </header>
            <div v-if="articles.data.length" class="grid">
                <Link
                    v-for="a in articles.data"
                    :key="a.id"
                    :href="a.href"
                    class="card"
                    ><img :src="a.cover" :alt="a.title" loading="lazy" />
                    <div>
                        <span>{{ a.category }} · {{ a.published_at }}</span>
                        <h3>{{ a.title }}</h3>
                        <p>{{ a.excerpt }}</p>
                        <b>Baca selengkapnya →</b>
                    </div></Link
                >
            </div>
            <div v-else class="empty">
                Belum ada berita yang sesuai dengan pencarian.
            </div>
            <nav v-if="articles.links?.length > 3" class="pagination">
                <Link
                    v-for="l in articles.links"
                    :key="l.label"
                    :href="l.url || '#'"
                    :class="{ active: l.active, disabled: !l.url }"
                    v-html="l.label"
                />
            </nav>
        </section>
        <SiteFooter />
    </main>
</template>
<style scoped>
.shell {
    width: min(calc(100% - 80px), var(--content-max));
    margin: auto;
}
.hero {
    min-height: 500px;
    background:
        linear-gradient(90deg, #05101df5, #07111e8c),
        url("/Assets/Dokumentasi/Docs-wahyu.jpeg") center/cover;
}
.hero-copy {
    padding-top: 120px;
}
.hero-copy > span,
.section-title span,
.card span,
.featured span,
.filters span {
    color: var(--color-accent);
    font: 800 0.6rem var(--font-display);
    letter-spacing: 0.15em;
    text-transform: uppercase;
}
.hero h1 {
    margin: 12px 0;
    font: 900 clamp(4.5rem, 9vw, 9rem) / 0.78 var(--font-display);
    text-transform: uppercase;
}
.hero h1 b {
    color: var(--color-accent);
}
.hero p {
    max-width: 550px;
    color: #b2c0cb;
    font-size: 1rem;
}
.listing {
    padding-block: 48px 80px;
}
.filters {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: 12px;
    margin-bottom: 35px;
}
.filters label span {
    display: block;
    margin-bottom: 8px;
}
.filters input,
.filters select {
    width: 100%;
    height: 52px;
    padding: 0 16px;
    border: 1px solid #ffffff24;
    color: #fff;
    background: #07111e;
}
.filters button {
    align-self: end;
    height: 52px;
    padding: 0 22px;
    border: 0;
    color: #04101c;
    background: var(--color-accent);
    font: 800 0.63rem var(--font-display);
    text-transform: uppercase;
}
.featured {
    min-height: 430px;
    display: flex;
    align-items: flex-end;
    padding: 38px;
    margin-bottom: 55px;
    border: 1px solid #45b7ff48;
    color: #fff;
    background:
        linear-gradient(0deg, #030a12f5, #06101c26),
        var(--cover) center/cover;
    text-decoration: none;
}
.featured > div {
    max-width: 760px;
}
.featured h2 {
    margin: 12px 0;
    font: 900 clamp(2.5rem, 5vw, 5rem) / 0.9 var(--font-display);
    text-transform: uppercase;
}
.featured p {
    color: #d0d9df;
}
.featured b,
.card b {
    color: var(--color-accent);
    font: 800 0.6rem var(--font-display);
    text-transform: uppercase;
}
.section-title h2 {
    margin: 8px 0 24px;
    font: 900 clamp(2.5rem, 5vw, 4.5rem) / 0.9 var(--font-display);
    text-transform: uppercase;
}
.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}
.card {
    border: 1px solid #ffffff1c;
    color: #fff;
    background: #07111e;
    text-decoration: none;
    transition: 0.2s;
}
.card:hover {
    border-color: var(--color-accent);
    transform: translateY(-4px);
}
.card img {
    width: 100%;
    aspect-ratio: 16/10;
    object-fit: cover;
}
.card > div {
    padding: 20px;
}
.card h3 {
    margin: 10px 0;
    font: 850 1.35rem/1 var(--font-display);
    text-transform: uppercase;
}
.card p {
    min-height: 48px;
    color: #82939f;
}
.empty {
    padding: 60px;
    border: 1px dashed #ffffff25;
    color: #82939f;
    text-align: center;
}
.pagination {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin-top: 35px;
}
.pagination a {
    padding: 10px 13px;
    border: 1px solid #ffffff20;
    color: #9fb0bc;
    text-decoration: none;
}
.pagination .active {
    color: #06101c;
    background: var(--color-accent);
}
.pagination .disabled {
    opacity: 0.35;
    pointer-events: none;
}
@media (max-width: 760px) {
    .shell {
        width: min(calc(100% - 36px), var(--content-max));
    }
    .hero {
        min-height: 460px;
    }
    .hero-copy {
        padding-top: 85px;
    }
    .filters,
    .grid {
        grid-template-columns: 1fr;
    }
    .featured {
        min-height: 390px;
        padding: 25px;
    }
    .listing {
        padding-top: 30px;
    }
}
</style>
