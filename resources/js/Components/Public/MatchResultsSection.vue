<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    featured: {
        type: Object,
        default: () => ({ slug: 'backyard-vs-cosmic', date: '12 Oktober 2026', home: 'Backyard FC', away: 'Cosmic', score: '2 - 2', matchday: '07', venue: 'Rumbai Sport Hall', city: 'Pekanbaru', image: '/Assets/Dokumentasi/Docs-wahyu.jpeg' }),
    },
    results: {
        type: Array,
        default: () => [
            { slug: 'cosmic-vs-northside', date: '05 Oktober 2026', matchday: '06', home: 'Cosmic', away: 'Northside', score: '3 - 1' },
            { slug: 'cosmic-vs-wfc', date: '26 September 2026', matchday: '05', home: 'Cosmic', away: 'WFC', score: '3 - 0' },
        ],
    },
});

const featuredStyle = computed(() => props.featured?.image ? ({ '--result-image': `url("${props.featured.image}")` }) : ({}));
</script>

<template>
    <section class="results" aria-labelledby="results-title">
        <div class="shell">
            <div class="results-heading">
                <h2 id="results-title">Hasil Pertandingan</h2>
                <div>Performa terbaru · Musim 2026</div>
            </div>
            <div class="results-grid">
                <article class="featured-result" :style="featuredStyle">
                    <div class="result-background" aria-hidden="true"></div>
                    <div class="result-topline"><div class="full-time">Full Time</div><time>{{ featured.date }}</time></div>
                    <div class="featured-score"><div class="result-team">{{ featured.home }}</div><div class="big-score">{{ featured.score }}</div><div class="result-team">{{ featured.away }}</div></div>
                    <div class="result-bottom">
                        <div class="result-meta">{{ featured.competition || "Pertandingan COSMIC" }}<template v-if="featured.matchday"> · Matchday {{ featured.matchday }}</template><br />{{ featured.venue }}, {{ featured.city }}</div>
                <Link v-if="featured.slug" class="detail-link" :href="`/pertandingan/${featured.slug}`">Lihat Detail <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14M13 6l6 6-6 6" /></svg></Link>
                <span v-else class="detail-link is-disabled">Belum tersedia</span>
                    </div>
                </article>
                <div class="result-stack">
                <article v-if="!results.length" class="compact-result empty-result"><div><time>Belum ada hasil lain</time><div class="compact-teams"><span>Data pertandingan akan tampil setelah dipublikasikan.</span></div></div><div class="compact-score">—</div></article>
                    <article v-for="result in results" :key="result.slug" class="compact-result">
                        <div><time>{{ result.date }}<template v-if="result.matchday"> · Matchday {{ result.matchday }}</template> · {{ result.competition || "Pertandingan COSMIC" }}</time><div class="compact-teams"><span>{{ result.home }}</span><span>{{ result.away }}</span></div></div>
                        <div class="compact-score">{{ result.score }}</div>
                        <Link class="compact-link" :href="`/pertandingan/${result.slug}`" :aria-label="`Detail ${result.home} melawan ${result.away}`"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14M13 6l6 6-6 6" /></svg></Link>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.results{position:relative;padding:54px 0 68px;overflow:hidden;border-bottom:1px solid var(--color-line);background:radial-gradient(circle at 12% 15%,rgba(69,183,255,.06),transparent 24%),#050b14}.shell{width:min(calc(100% - 80px),var(--content-max));margin-inline:auto}.results-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:30px;margin-bottom:28px}.results-heading h2{margin:0;font:900 clamp(2.9rem,4.7vw,4.9rem)/.88 var(--font-display);letter-spacing:-.045em;text-transform:uppercase}.results-heading>div{color:#8d9aa5;font:650 .65rem var(--font-display);letter-spacing:.14em;text-transform:uppercase}.results-grid{display:grid;grid-template-columns:1.25fr .75fr;gap:19px}.featured-result{position:relative;display:flex;min-height:350px;flex-direction:column;justify-content:space-between;overflow:hidden;isolation:isolate;padding:28px;border:1px solid rgba(175,218,255,.19);background:#0a131d}.result-background{position:absolute;inset:0;z-index:-2;background:linear-gradient(90deg,rgba(5,9,15,.96),rgba(5,9,15,.67) 55%,rgba(5,9,15,.3)),linear-gradient(0deg,rgba(5,9,15,.9),transparent 58%),var(--result-image) center/cover;filter:saturate(.58) contrast(1.08);transition:transform .6s}.featured-result:hover .result-background{transform:scale(1.025)}.result-topline{display:flex;align-items:center;justify-content:space-between;gap:20px}.full-time{display:inline-flex;align-items:center;gap:8px;color:var(--color-accent);font:750 .62rem var(--font-display);letter-spacing:.15em;text-transform:uppercase}.full-time::before{content:'';width:7px;height:7px;border-radius:50%;background:var(--color-accent)}.result-topline time{color:#adb9c3;font:650 .64rem var(--font-display);letter-spacing:.12em;text-transform:uppercase}.featured-score{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:27px;max-width:700px}.result-team{font:900 clamp(2.8rem,5vw,5.2rem)/.82 var(--font-display);letter-spacing:-.045em;text-transform:uppercase}.result-team:last-child{text-align:right;font-style:italic}.big-score{color:var(--color-accent);font:900 clamp(3.2rem,5.4vw,5.8rem)/.8 var(--font-display);letter-spacing:.08em;white-space:nowrap}.result-bottom{display:flex;align-items:flex-end;justify-content:space-between;gap:20px}.result-meta{color:#bac5ce;font:600 .67rem/1.5 var(--font-display);letter-spacing:.07em;text-transform:uppercase}.detail-link{display:inline-flex;min-height:42px;align-items:center;gap:13px;padding:0 16px;border:1px solid rgba(255,255,255,.33);border-radius:4px;color:white;font:750 .64rem var(--font-display);letter-spacing:.1em;text-decoration:none;text-transform:uppercase;transition:.2s}.detail-link svg{width:16px;stroke-width:1.8}.detail-link:hover{color:#04101c;border-color:var(--color-accent);background:var(--color-accent);transform:translateX(3px)}.result-stack{display:grid;gap:10px}.compact-result{display:grid;min-height:110px;grid-template-columns:minmax(0,1fr) auto 34px;align-items:center;gap:15px;padding:17px 18px;border:1px solid rgba(175,218,255,.12);background:rgba(255,255,255,.018);transition:.22s}.compact-result:hover{border-color:rgba(69,183,255,.4);background:rgba(69,183,255,.07);transform:translateX(4px)}.compact-result time{display:block;margin-bottom:9px;color:#7f8c98;font:650 .59rem var(--font-display);letter-spacing:.12em;text-transform:uppercase}.compact-teams{display:grid;gap:5px}.compact-teams span{font:750 .75rem var(--font-display);letter-spacing:.06em;text-transform:uppercase}.compact-score{color:var(--color-accent);font:900 1.8rem var(--font-display);letter-spacing:.1em;white-space:nowrap}.compact-link{display:grid;width:32px;height:32px;place-items:center;border:1px solid rgba(255,255,255,.25);border-radius:50%;color:#c4ced7;transition:.2s}.compact-link svg{width:15px;stroke-width:1.8}.compact-result:hover .compact-link{color:#04101c;border-color:var(--color-accent);background:var(--color-accent)}
@media(max-width:900px){.shell{width:min(calc(100% - 40px),var(--content-max))}.results-grid{grid-template-columns:1fr}.result-stack{grid-template-columns:repeat(2,1fr)}}
@media(max-width:600px){.shell{width:calc(100% - 32px)}.results-heading{align-items:flex-start;flex-direction:column}.featured-result{min-height:420px;padding:20px}.featured-score{gap:10px}.result-team{font-size:2.2rem}.big-score{font-size:2.7rem}.result-bottom{align-items:flex-start;flex-direction:column}.result-stack{grid-template-columns:1fr}}
</style>
