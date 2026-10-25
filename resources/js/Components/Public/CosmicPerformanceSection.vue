<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    performance: {
        type: Object,
        default: () => ({
            position: 2,
            played: 7,
            points: 17,
            won: 5,
            drawn: 2,
            lost: 0,
            goalsFor: 18,
            goalsAgainst: 8,
            cleanSheets: 3,
            recentForm: ['M', 'M', 'S', 'M', 'S'],
            recentPoints: 11,
        }),
    },
    image: { type: String, default: '' },
    logo: { type: String, default: '/Assets/Logo/Logo.jpeg' },
});

const sectionRoot = ref(null);

const record = computed(() => `${props.performance.won}–${props.performance.drawn}–${props.performance.lost}`);
const chartMax = computed(() => Math.max(props.performance.goalsFor, props.performance.goalsAgainst, 1));
const goalWidth = computed(() => `${Math.round((props.performance.goalsFor / chartMax.value) * 88)}%`);
const concededWidth = computed(() => `${Math.round((props.performance.goalsAgainst / chartMax.value) * 88)}%`);
const formClass = (result) => ({ M: 'win', S: 'draw', K: 'loss' }[result]);
</script>

<template>
    <section ref="sectionRoot" class="cosmic-performance" aria-labelledby="performance-title">
        <div class="shell">
            <header class="performance-heading" data-reveal>
                <div class="performance-title-wrap">
                    <span class="performance-badge"><img :src="logo" alt="Logo COSMIC" loading="lazy" decoding="async" /></span>
                    <h2 id="performance-title">Performa Cosmic</h2>
                </div>
                <div class="performance-caption">Statistik setelah {{ performance.played }} pertandingan</div>
            </header>

            <div class="performance-layout">
                <article class="performance-visual" :style="{ '--performance-image': `url('${image}')` }" data-reveal>
                    <div class="rank-kicker">Posisi Saat Ini</div>
                    <div class="rank-number">#{{ String(performance.position).padStart(2,'0') }}</div>
                    <p>Belum terkalahkan dan hanya terpaut dua poin dari puncak klasemen. Perjalanan masih panjang, target tetap sama.</p>
                </article>

                <div class="performance-data">
                    <article class="performance-card" data-reveal><div class="performance-value accent">{{ performance.points }}</div><div class="performance-label">Total Poin</div></article>
                    <article class="performance-card" data-reveal><div class="performance-value">{{ record }}</div><div class="performance-label">Menang · Seri · Kalah</div></article>
                    <article class="performance-card" data-reveal><div class="performance-value accent">{{ performance.goalsFor }}</div><div class="performance-label">Gol Dicetak</div></article>
                    <article class="performance-card" data-reveal><div class="performance-value">{{ performance.cleanSheets }}</div><div class="performance-label">Clean Sheet</div></article>

                    <article class="form-card" data-reveal>
                        <div>
                            <div class="form-heading">Lima Pertandingan Terakhir</div>
                            <div class="large-form"><span v-for="(result,index) in performance.recentForm" :key="index" :class="formClass(result)">{{ result }}</span></div>
                        </div>
                        <div class="form-summary"><strong>{{ performance.recentPoints }}</strong><span>Poin dari 15</span></div>
                    </article>

                    <article class="goal-chart" data-reveal>
                        <div class="chart-title"><span>Produktivitas Gol</span><span>Musim 2026</span></div>
                        <div class="bar-row"><span>Gol Cetak</span><div class="bar-track"><div class="bar-fill" :style="{ width:goalWidth }"></div></div><span class="bar-value">{{ performance.goalsFor }}</span></div>
                        <div class="bar-row"><span>Kebobolan</span><div class="bar-track"><div class="bar-fill conceded" :style="{ width:concededWidth }"></div></div><span class="bar-value">{{ String(performance.goalsAgainst).padStart(2,'0') }}</span></div>
                    </article>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.cosmic-performance{padding:54px 0 68px;overflow:hidden;border-bottom:1px solid var(--color-line);background:radial-gradient(circle at 83% 18%,rgba(69,183,255,.05),transparent 24%),var(--color-surface)}.shell{width:min(calc(100% - 80px),var(--content-max));margin-inline:auto}.performance-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:30px;margin-bottom:27px}.performance-title-wrap{display:flex;align-items:center;gap:15px}.performance-badge{display:grid;width:48px;height:48px;place-items:center;border:1px solid rgba(69,183,255,.45);border-radius:50%;background:#050b12}.performance-badge img{width:38px;height:38px;object-fit:cover;border-radius:50%}.performance-title-wrap h2{margin:0;font:900 clamp(2.9rem,4.7vw,4.9rem)/.88 var(--font-display);letter-spacing:-.045em;text-transform:uppercase}.performance-caption{color:#8e9ba6;font:650 .64rem var(--font-display);letter-spacing:.14em;text-transform:uppercase}.performance-layout{display:grid;grid-template-columns:minmax(360px,.8fr) minmax(480px,1.2fr);gap:16px}.performance-visual{position:relative;min-height:390px;display:flex;flex-direction:column;justify-content:flex-end;overflow:hidden;padding:27px;border:1px solid rgba(175,218,255,.15);isolation:isolate}.performance-visual::before{content:'';position:absolute;inset:0;z-index:-2;background:linear-gradient(0deg,rgba(5,10,17,.98),rgba(5,10,17,.55) 52%,rgba(5,10,17,.12)),var(--performance-image) center/cover no-repeat;filter:saturate(.65) contrast(1.09);transition:transform .7s}.performance-visual:hover::before{transform:scale(1.035)}.rank-kicker{color:var(--color-accent);font:800 .64rem var(--font-display);letter-spacing:.18em;text-transform:uppercase}.rank-number{margin:8px 0 12px;font:900 7.5rem/.7 var(--font-display);letter-spacing:-.06em}.performance-visual p{max-width:330px;margin:0;color:#bec9d2;font-size:.76rem;line-height:1.55}.performance-data{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}.performance-card{min-height:128px;display:flex;flex-direction:column;justify-content:space-between;padding:19px;border:1px solid rgba(175,218,255,.13);background:rgba(255,255,255,.018);transition:.22s}.performance-card:hover{border-color:rgba(69,183,255,.38);background:rgba(69,183,255,.05);transform:translateY(-3px)}.performance-value{font:900 2.8rem/.85 var(--font-display);letter-spacing:-.035em}.performance-value.accent{color:var(--color-accent)}.performance-label,.form-heading{color:#8f9ca7;font:700 .6rem var(--font-display);letter-spacing:.14em;text-transform:uppercase}.form-card{grid-column:1/-1;min-height:118px;display:grid;grid-template-columns:1fr auto;align-items:center;gap:25px;padding:20px;border:1px solid rgba(175,218,255,.13);background:rgba(255,255,255,.018)}.form-heading{margin-bottom:13px}.large-form{display:flex;gap:8px}.large-form span{display:grid;width:35px;height:35px;place-items:center;border-radius:50%;font:900 .65rem var(--font-display)}.large-form .win{color:#04101c;background:var(--color-accent)}.large-form .draw{color:white;background:#596875}.large-form .loss{color:white;background:#75434a}.form-summary{text-align:right}.form-summary strong{display:block;color:var(--color-accent);font:900 2.5rem/.8 var(--font-display)}.form-summary span{color:#8f9ca7;font:650 .57rem var(--font-display);letter-spacing:.12em;text-transform:uppercase}.goal-chart{grid-column:1/-1;padding:21px;border:1px solid rgba(175,218,255,.13);background:rgba(255,255,255,.018)}.chart-title{display:flex;justify-content:space-between;margin-bottom:18px;color:#8f9ca7;font:700 .6rem var(--font-display);letter-spacing:.13em;text-transform:uppercase}.bar-row{display:grid;grid-template-columns:90px 1fr 35px;align-items:center;gap:12px;margin-top:12px;color:#c6d1da;font:700 .65rem var(--font-display);text-transform:uppercase}.bar-track{height:8px;overflow:hidden;background:rgba(255,255,255,.08)}.bar-fill{height:100%;background:var(--color-accent);box-shadow:0 0 14px rgba(69,183,255,.34);transition:width .7s ease}.bar-fill.conceded{background:#6c7a85;box-shadow:none}.bar-value{text-align:right;font:900 .85rem var(--font-display)}
@media(max-width:900px){.shell{width:min(calc(100% - 40px),var(--content-max))}.performance-layout{grid-template-columns:1fr}.performance-visual{min-height:360px}.performance-data{grid-template-columns:repeat(4,1fr)}.form-card,.goal-chart{grid-column:1/-1}.performance-card{min-height:120px}}
@media(max-width:520px){.cosmic-performance{padding:42px 0 52px}.shell{width:calc(100% - 32px)}.performance-heading{align-items:flex-start;flex-direction:column;gap:12px}.performance-data{grid-template-columns:1fr 1fr}.performance-visual{min-height:410px}.rank-number{font-size:6rem}.form-card{grid-template-columns:1fr}.form-summary{text-align:left}.bar-row{grid-template-columns:75px 1fr 28px}}
</style>
