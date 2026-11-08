<script setup>
import SiteNavbar from "@/Components/Public/SiteNavbar.vue";
import SiteFooter from "@/Components/Public/SiteFooter.vue";
import { Link } from "@inertiajs/vue3";
defineProps({ article: Object, related: Array });
</script>
<template>
    <main>
        <SiteNavbar :transparent="false" />
        <article class="article">
            <header class="article-head shell">
                <span>{{ article.category }} · {{ article.published_at }}</span>
                <h1>{{ article.title }}</h1>
                <p>{{ article.excerpt }}</p>
                <small>Ditulis oleh {{ article.author }}</small>
            </header>
            <img class="cover" :src="article.cover" :alt="article.title" />
            <section v-if="article.match" class="match shell">
                <span>Laporan Pertandingan</span>
                <div>
                    <strong>{{ article.match.home }}</strong
                    ><b
                        >{{ article.match.home_score }} –
                        {{ article.match.away_score }}</b
                    ><strong>{{ article.match.away }}</strong>
                </div>
                <p>{{ article.match.date }} · {{ article.match.venue }}</p>
                <Link :href="`/pertandingan/${article.match.slug}`"
                    >Lihat detail pertandingan →</Link
                >
            </section>
            <div class="content shell">
                <div
                    class="body"
                    v-for="(paragraph, i) in article.body.split(/\n\s*\n/)"
                    :key="i"
                >
                    <p>{{ paragraph }}</p>
                </div>
            </div>
            <section v-if="article.gallery.length" class="gallery shell">
                <img
                    v-for="m in article.gallery"
                    :key="m.id"
                    :src="m.url"
                    :alt="m.alt"
                    loading="lazy"
                />
            </section>
            <section v-if="related.length" class="related shell">
                <h2>Berita Terkait.</h2>
                <div>
                    <Link v-for="a in related" :key="a.id" :href="a.href"
                        ><img :src="a.cover" :alt="a.title" /><strong>{{
                            a.title
                        }}</strong></Link
                    >
                </div>
            </section>
        </article>
        <SiteFooter />
    </main>
</template>
<style scoped>
.shell {
    width: min(calc(100% - 80px), 1050px);
    margin: auto;
}
.article {
    padding-top: 75px;
}
.article-head {
    text-align: center;
}
.article-head > span,
.match > span {
    color: var(--color-accent);
    font: 800 0.6rem var(--font-display);
    letter-spacing: 0.16em;
    text-transform: uppercase;
}
.article h1 {
    max-width: 950px;
    margin: 18px auto;
    font: 900 clamp(3.3rem, 7vw, 7rem) / 0.88 var(--font-display);
    text-transform: uppercase;
}
.article-head p {
    max-width: 720px;
    margin: 0 auto;
    color: #a9b8c3;
    font-size: 1.05rem;
}
.article-head small {
    display: block;
    margin-top: 18px;
    color: #647682;
}
.cover {
    display: block;
    width: min(calc(100% - 80px), 1350px);
    max-height: 700px;
    margin: 55px auto 0;
    object-fit: cover;
}
.match {
    margin-top: 35px;
    padding: 25px;
    border: 1px solid #45b7ff3d;
    background: #071827;
    text-align: center;
}
.match > div {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 25px;
    margin: 15px 0;
}
.match strong {
    font: 850 1.4rem var(--font-display);
    text-transform: uppercase;
}
.match b {
    color: var(--color-accent);
    font: 900 2.3rem var(--font-display);
}
.match p {
    color: #8295a2;
}
.match a {
    color: var(--color-accent);
    font-weight: 800;
    text-decoration: none;
}
.content {
    padding-block: 45px;
}
.body {
    max-width: 760px;
    margin: auto;
}
.body p {
    white-space: pre-line;
    color: #c5d1d9;
    font-size: 1rem;
    line-height: 1.9;
}
.gallery {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px;
    padding-bottom: 55px;
}
.gallery img {
    width: 100%;
    aspect-ratio: 4/3;
    object-fit: cover;
}
.related {
    padding-block: 35px 70px;
    border-top: 1px solid #ffffff1a;
}
.related h2 {
    font: 900 2.7rem var(--font-display);
    text-transform: uppercase;
}
.related > div {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}
.related a {
    color: #fff;
    text-decoration: none;
}
.related img {
    width: 100%;
    aspect-ratio: 16/10;
    object-fit: cover;
}
.related strong {
    display: block;
    margin-top: 10px;
    font: 800 0.85rem var(--font-display);
    text-transform: uppercase;
}
@media (max-width: 700px) {
    .shell,
    .cover {
        width: calc(100% - 36px);
    }
    .article {
        padding-top: 45px;
    }
    .cover {
        margin-top: 35px;
    }
    .match > div {
        grid-template-columns: 1fr;
    }
    .gallery,
    .related > div {
        grid-template-columns: 1fr;
    }
}
</style>
