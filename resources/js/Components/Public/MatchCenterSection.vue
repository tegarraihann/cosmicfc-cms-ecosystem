<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, default: 'Match Center' },
    allMatchesHref: { type: String, default: '/jadwal' },
    image: { type: String, default: '' },
    upcoming: {
        type: Array,
        default: () => [
            { day: '24', month: 'Mei', code: 'RVS', opponent: 'Rivals', meta: '19.30 WIB · Lap. Kuningan', href: '/jadwal' },
            { day: '31', month: 'Mei', code: 'OFC', opponent: 'The Office', meta: '20.00 WIB · Lap. Pancoran', href: '/jadwal' },
            { day: '07', month: 'Jun', code: 'NOR', opponent: 'Northside', meta: '19.30 WIB · Lap. Blok S', href: '/jadwal' },
        ],
    },
    results: {
        type: Array,
        default: () => [
            { homeCode: 'CSM', awayCode: 'BYD', opponent: 'Backyard FC', meta: '10 Mei 2026 · Lap. Tebet', score: '4 - 2' },
            { homeCode: 'CSM', awayCode: 'SK', opponent: 'Sudut Kota', meta: '03 Mei 2026 · Lap. Kuningan', score: '1 - 1' },
            { homeCode: 'CSM', awayCode: 'WFC', opponent: 'WFC', meta: '26 Apr 2026 · Lap. Pancoran', score: '3 - 0' },
        ],
    },
});

const sectionStyle = computed(() => props.image ? ({ '--center-image': `url("${props.image}")` }) : ({}));
</script>

<template>
    <section id="match-center" class="match-center" :style="sectionStyle" aria-labelledby="match-center-title">
        <div class="shell">
            <div class="center-heading">
                <div class="center-title-wrap">
                    <h2 id="match-center-title" class="center-title">{{ title }}</h2>
                    <span class="title-arrow" aria-hidden="true">→</span>
                </div>
                <Link class="all-matches" :href="allMatchesHref">
                    Semua pertandingan
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </Link>
            </div>

            <div class="center-grid">
                <div class="fixture-column">
                    <h3 class="column-label">Pertandingan Mendatang</h3>
                    <div class="fixture-list">
                        <article v-if="!upcoming.length" class="fixture fixture-empty">Jadwal pertandingan belum tersedia.</article>
                        <article v-for="match in upcoming" :key="`${match.day}-${match.opponent}`" class="fixture upcoming-fixture">
                            <time class="fixture-date"><strong>{{ match.day }}</strong>{{ match.month }}</time>
                            <div class="mini-crest" aria-hidden="true">{{ match.code }}</div>
                            <div class="fixture-info">
                                <div class="fixture-name">Cosmic <span class="vs">vs</span> {{ match.opponent }}</div>
                                <div class="fixture-place">{{ match.meta }}</div>
                            </div>
                            <Link class="fixture-link" :href="match.href" :aria-label="`Lihat Cosmic melawan ${match.opponent}`">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                            </Link>
                        </article>
                    </div>
                </div>

                <div class="fixture-column">
                    <h3 class="column-label">Hasil Terakhir</h3>
                    <div class="fixture-list result-list">
                        <article v-if="!results.length" class="fixture fixture-empty">Hasil pertandingan belum tersedia.</article>
                        <article v-for="result in results" :key="`${result.meta}-${result.opponent}`" class="fixture">
                            <div class="mini-crest" aria-hidden="true">{{ result.homeCode }}</div>
                            <div class="fixture-info">
                                <div class="fixture-name">Cosmic <span class="dash">—</span> {{ result.opponent }}</div>
                                <div class="fixture-place">{{ result.meta }}</div>
                            </div>
                            <div class="score" :aria-label="`skor ${result.score}`">{{ result.score }}</div>
                            <div class="mini-crest" aria-hidden="true">{{ result.awayCode }}</div>
                        </article>
                    </div>
                </div>

                <aside class="match-photo" aria-label="Foto kebersamaan tim">
                    <div class="match-photo-inner"></div>
                    <p class="photo-note">More games.<br />Bigger <span>stories.</span></p>
                </aside>
            </div>
        </div>
    </section>
</template>

<style scoped>
.match-center { position: relative; isolation: isolate; overflow: hidden; padding: 34px 0 38px; border-bottom: 1px solid var(--color-line); background: radial-gradient(circle at 82% 30%,rgba(69,183,255,.06),transparent 28%),#050b14; }
.shell { position: relative; z-index: 1; width: min(calc(100% - 80px),var(--content-max)); margin-inline: auto; }
.center-heading { display: flex; align-items: center; justify-content: space-between; gap: 24px; margin-bottom: 24px; }
.center-title-wrap { display: flex; align-items: center; gap: 18px; }
.center-title { margin: 0; color: var(--color-paper); font: 900 clamp(2.8rem,4.4vw,4.65rem)/.9 var(--font-display); letter-spacing: -.04em; text-transform: uppercase; }
.title-arrow { color: var(--color-accent); font: 300 3.3rem/1 var(--font-display); filter: drop-shadow(0 0 7px rgba(69,183,255,.5)); transform: rotate(-42deg); }
.all-matches { display: inline-flex; align-items: center; gap: 10px; color: #bdc9d4; font: 700 .67rem var(--font-display); letter-spacing: .12em; text-decoration: none; text-transform: uppercase; transition: color .2s; }
.all-matches:hover { color: var(--color-accent); }
.all-matches svg { width: 17px; stroke-width: 1.8; }
.center-grid { display: grid; grid-template-columns: minmax(250px,.95fr) minmax(250px,.95fr) minmax(330px,1.08fr); align-items: stretch; gap: 34px; }
.fixture-empty { min-height: 94px; display: flex; align-items: center; justify-content: center; padding: 18px; color: #72818e; border-style: dashed; font: 700 .62rem var(--font-display); letter-spacing: .12em; text-align: center; text-transform: uppercase; }
.fixture-column { min-width: 0; }
.column-label { margin: 0 0 14px; color: #dce5ed; font: 700 .7rem var(--font-display); letter-spacing: .22em; text-transform: uppercase; }
.fixture-list { display: grid; }
.fixture { position: relative; display: grid; min-height: 62px; grid-template-columns: 45px 31px minmax(0,1fr) 30px; align-items: center; gap: 10px; padding: 8px 10px; border-top: 1px solid rgba(255,255,255,.1); background: linear-gradient(90deg,rgba(255,255,255,.028),transparent); transition: background .24s,border-color .24s,transform .24s; }
.fixture:last-child { border-bottom: 1px solid rgba(255,255,255,.1); }
.fixture:hover { z-index: 1; border-color: rgba(69,183,255,.34); background: linear-gradient(90deg,rgba(69,183,255,.14),rgba(69,183,255,.035)); transform: translateX(4px); }
.fixture:hover + .fixture { border-top-color: rgba(69,183,255,.22); }
.fixture-date { color: white; font: 700 .72rem/1.05 var(--font-display); letter-spacing: .08em; text-align: center; text-transform: uppercase; }
.fixture-date strong { display: block; font-size: 1.35rem; letter-spacing: 0; }
.mini-crest { display: grid; width: 29px; height: 29px; place-items: center; border: 1px solid rgba(255,255,255,.45); border-radius: 50%; color: rgba(255,255,255,.88); background: rgba(255,255,255,.04); font: 800 .48rem var(--font-display); }
.fixture-info { min-width: 0; }
.fixture-name { overflow: hidden; margin-bottom: 3px; color: #f1f5f8; font: 700 .75rem var(--font-display); letter-spacing: .035em; text-overflow: ellipsis; text-transform: uppercase; white-space: nowrap; transition: color .22s; }
.fixture:hover .fixture-name { color: var(--color-accent); }
.vs,.dash { padding-inline: 6px; color: #808d99; font-size: .62rem; }
.fixture-place { overflow: hidden; color: #8f9daa; font: 500 .59rem var(--font-display); letter-spacing: .045em; text-overflow: ellipsis; text-transform: uppercase; white-space: nowrap; }
.fixture-link { display: grid; width: 28px; height: 28px; place-items: center; border: 1px solid transparent; border-radius: 50%; color: #aebac5; transition: .2s; }
.fixture-link svg { width: 16px; stroke-width: 1.8; transition: transform .2s; }
.fixture:hover .fixture-link,.fixture-link:hover { color: #04101c; border-color: var(--color-accent); background: var(--color-accent); transform: translateX(3px); }
.fixture:hover .fixture-link svg { transform: translateX(1px); }
.result-list .fixture { grid-template-columns: 31px minmax(0,1fr) auto 31px; }
.result-list .fixture-name { margin: 0; }
.score { color: var(--color-accent); font: 800 1.05rem var(--font-display); letter-spacing: .13em; white-space: nowrap; transition: color .2s,text-shadow .2s; }
.result-list .fixture:hover .score { color: white; text-shadow: 0 0 14px rgba(69,183,255,.65); }
.match-photo { position: absolute; z-index: -1; top: -114px; right: calc((min(100vw - 80px,var(--content-max)) - 100vw)/2); bottom: -38px; width: calc(36% + max(40px,(100vw - var(--content-max))/2)); overflow: hidden; border-left: 1px solid rgba(255,255,255,.1); }
.match-photo-inner { position: absolute; inset: -8%; background: var(--center-image) center/cover no-repeat; filter: saturate(.55) contrast(1.12); }
.match-photo::before { content: ''; position: absolute; z-index: 1; inset: 0; background: linear-gradient(90deg,#050b14 0%,rgba(5,11,20,.66) 18%,transparent 55%),linear-gradient(0deg,rgba(4,10,17,.62),transparent 48%,rgba(4,10,17,.2)); }
.photo-note { position: absolute; z-index: 2; right: 20px; bottom: 17px; width: 120px; color: rgba(255,255,255,.88); font: italic 600 1.35rem/.95 var(--font-display); text-align: right; text-transform: uppercase; transform: rotate(-5deg); }
.photo-note span { color: var(--color-accent); }

@media(max-width:900px) {
    .shell { width: min(calc(100% - 40px),var(--content-max)); }
    .center-grid { grid-template-columns: 1fr 1fr; }
    .match-photo { position: relative; z-index: auto; top: auto; right: auto; bottom: auto; width: auto; min-height: 260px; grid-column: 1/-1; border-left: 0; }
}

@media(max-width:520px) {
    .shell { width: calc(100% - 32px); }
    .center-heading { align-items: flex-end; }
    .all-matches { max-width: 95px; justify-content: flex-end; text-align: right; }
    .center-grid { grid-template-columns: 1fr; gap: 28px; }
    .match-photo { min-height: 240px; grid-column: auto; }
    .fixture { padding-inline: 6px; }
}
</style>
