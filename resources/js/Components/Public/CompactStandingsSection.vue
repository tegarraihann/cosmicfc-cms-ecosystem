<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    clubs: {
        type: Array,
        default: () => [],
    },
    fullStandingsHref: { type: String, default: '/liga' },
    backgroundImage: { type: String, default: '' },
});

const cosmic = computed(() => props.clubs.find((club) => club.cosmic));
</script>

<template>
    <section class="standings" :style="backgroundImage ? { '--standings-image': `url('${backgroundImage}')` } : {}" aria-labelledby="standings-title">
        <div class="standings-photo" aria-hidden="true"></div>
        <div class="standings-inner shell">
            <div class="standings-heading">
                <div class="standings-title-wrap"><h2 id="standings-title">Klasemen Ringkas</h2><span aria-hidden="true">→</span></div>
                <div class="league-label">Pekanbaru Mini Soccer League · Musim 2026</div>
            </div>
            <div class="standings-layout">
                <div class="table-shell" role="table" aria-label="Klasemen lima besar">
                <div class="table-row table-head" role="row"><div>Pos</div><div>Klub</div><div>Main</div><div>M</div><div>S</div><div>K</div><div>SG</div><div>Poin</div></div>
                <div v-if="!clubs.length" class="table-empty" role="row">Klasemen belum tersedia.</div>
                    <div v-for="club in clubs" :key="club.code" :class="['table-row',{ 'cosmic-row':club.cosmic }]" role="row">
                        <div class="position">{{ String(club.position).padStart(2,'0') }}</div>
                        <div class="club"><span class="club-crest"><img v-if="club.logo" :src="club.logo" alt="" loading="lazy" decoding="async" /><template v-else>{{ club.code }}</template></span>{{ club.name }}</div>
                        <div class="stat">{{ club.played }}</div><div class="stat">{{ club.won }}</div><div class="stat">{{ club.drawn }}</div><div class="stat">{{ club.lost }}</div><div class="stat">{{ club.difference }}</div><div class="points">{{ club.points }}</div>
                    </div>
                </div>
                <aside class="standings-side">
                    <div><div class="side-eyebrow">Posisi Cosmic</div><div class="side-rank">#{{ String(cosmic?.position ?? 0).padStart(2,'0') }}</div><p>Belum terkalahkan setelah tujuh pertandingan dan hanya terpaut dua poin dari puncak klasemen.</p></div>
                    <Link class="standings-link" :href="fullStandingsHref">Lihat Klasemen Lengkap <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14M13 6l6 6-6 6" /></svg></Link>
                </aside>
            </div>
        </div>
    </section>
</template>

<style scoped>
.standings{position:relative;padding:54px 0 66px;overflow:hidden;border-bottom:1px solid var(--color-line);background:radial-gradient(circle at 87% 30%,rgba(69,183,255,.06),transparent 23%),var(--color-surface)}.standings-photo{position:absolute;top:0;right:0;width:34%;height:100%;opacity:.12;background:var(--standings-image) center/cover;filter:grayscale(1);mask-image:linear-gradient(90deg,transparent,#000);pointer-events:none}.shell{width:min(calc(100% - 80px),var(--content-max));margin-inline:auto}.standings-inner{position:relative;z-index:1}.standings-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:28px;margin-bottom:27px}.standings-title-wrap{display:flex;align-items:center;gap:18px}.standings-title-wrap h2{margin:0;font:900 clamp(2.9rem,4.7vw,4.9rem)/.88 var(--font-display);letter-spacing:-.045em;text-transform:uppercase}.standings-title-wrap span{color:var(--color-accent);font:300 3.4rem/1 var(--font-display);filter:drop-shadow(0 0 7px rgba(69,183,255,.5));transform:rotate(-42deg)}.league-label{color:#8e9ba6;font:650 .64rem var(--font-display);letter-spacing:.14em;text-transform:uppercase}.standings-layout{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:22px}.table-shell{overflow:hidden;border:1px solid rgba(175,218,255,.14);background:rgba(5,9,15,.76)}.table-row{display:grid;min-height:59px;grid-template-columns:54px minmax(210px,1fr) repeat(5,65px) 80px;align-items:center;padding:0 17px;border-bottom:1px solid rgba(255,255,255,.09);transition:background .22s}.table-row:last-child{border-bottom:0}.table-row:not(.table-head):hover{background:rgba(255,255,255,.035)}.table-head{min-height:43px;color:#707d88;background:rgba(255,255,255,.02);font:700 .57rem var(--font-display);letter-spacing:.12em;text-transform:uppercase}.table-row>:not(.club){text-align:center}.table-head>:nth-child(2){text-align:left}.position{color:#c5d0d9;font:800 .9rem var(--font-display)}.club{display:flex;min-width:0;align-items:center;gap:12px;font:800 .78rem var(--font-display);letter-spacing:.07em;text-transform:uppercase}.club-crest{display:grid;flex:0 0 31px;width:31px;height:31px;place-items:center;border:1px solid rgba(255,255,255,.32);border-radius:50%;color:#cbd5dd;font:800 .47rem var(--font-display)}.club-crest img{width:24px;height:24px;border-radius:50%;object-fit:cover}.stat{color:#aebac5;font:650 .72rem var(--font-display)}.points{color:white;font:900 1rem var(--font-display)}.cosmic-row{position:relative;background:linear-gradient(90deg,rgba(69,183,255,.15),rgba(69,183,255,.025))}.cosmic-row::before{content:'';position:absolute;inset:0 auto 0 0;width:3px;background:var(--color-accent)}.cosmic-row .position,.cosmic-row .club,.cosmic-row .points{color:var(--color-accent)}.cosmic-row .club-crest{color:#04101c;border-color:var(--color-accent);background:var(--color-accent)}.standings-side{display:flex;flex-direction:column;justify-content:space-between;padding:25px;border:1px solid rgba(175,218,255,.14);background:rgba(255,255,255,.018)}.side-eyebrow{margin-bottom:12px;color:var(--color-accent);font:750 .62rem var(--font-display);letter-spacing:.16em;text-transform:uppercase}.side-rank{font:900 4.8rem/.78 var(--font-display);letter-spacing:-.05em}.standings-side p{margin:16px 0 25px;color:#aebac5;font-size:.76rem;line-height:1.55}.standings-link{display:flex;min-height:46px;align-items:center;justify-content:space-between;padding:0 16px;border:1px solid rgba(255,255,255,.28);color:white;font:750 .64rem var(--font-display);letter-spacing:.1em;text-decoration:none;text-transform:uppercase;transition:.2s}.standings-link svg{width:17px;stroke-width:1.8}.standings-link:hover{color:#04101c;border-color:var(--color-accent);background:var(--color-accent)}
@media(max-width:900px){.shell{width:min(calc(100% - 40px),var(--content-max))}.standings-layout{grid-template-columns:1fr}.standings-side{min-height:220px}}
@media(max-width:600px){.shell{width:calc(100% - 32px)}.standings-heading{align-items:flex-start;flex-direction:column}.table-shell{overflow-x:auto}.table-row{min-width:680px;grid-template-columns:45px minmax(180px,1fr) repeat(5,58px) 65px}.standings-side{min-height:210px}}
.table-empty{display:grid;min-height:120px;place-items:center;color:#788894;font-size:.72rem;letter-spacing:.06em}
</style>
