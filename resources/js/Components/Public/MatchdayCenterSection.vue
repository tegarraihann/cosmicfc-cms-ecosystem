<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    results: {
        type: Array,
        default: () => [
            { day: '12', month: 'Okt', date: '2026-10-12', home: 'Backyard FC', away: 'Cosmic', homeScore: 2, awayScore: 2, cosmic: 'away', href: '/pertandingan/backyard-vs-cosmic' },
            { day: '12', month: 'Okt', date: '2026-10-12', home: 'Rivals', away: 'The Office', homeScore: 3, awayScore: 1 },
            { day: '13', month: 'Okt', date: '2026-10-13', home: 'Northside', away: 'Sudut Kota', homeScore: 1, awayScore: 0 },
            { day: '13', month: 'Okt', date: '2026-10-13', home: 'WFC', away: 'Panam United', homeScore: 2, awayScore: 1 },
        ],
    },
    fixtures: {
        type: Array,
        default: () => [
            { day: '07', month: 'Nov', date: '2026-11-07', home: 'The Office', away: 'Cosmic', time: '20.00', venue: 'Rumbai Sport Hall', cosmic: 'away', href: '/pertandingan/office-vs-cosmic' },
            { day: '07', month: 'Nov', date: '2026-11-07', home: 'Rivals', away: 'Panam United', time: '19.30', venue: 'Aero Sport Center' },
            { day: '08', month: 'Nov', date: '2026-11-08', home: 'Northside', away: 'WFC', time: '19.30', venue: 'Panam Soccer Field' },
            { day: '08', month: 'Nov', date: '2026-11-08', home: 'Sudut Kota', away: 'Skyline FC', time: '20.00', venue: 'Rumbai Sport Hall' },
        ],
    },
});

const sectionRoot = ref(null);
const selectedMatchday = ref('08');

function selectMatchday(matchday) {
    selectedMatchday.value = matchday;
    sectionRoot.value?.querySelector(matchday === '08' ? '.results-panel' : '.next-panel')?.scrollIntoView({ block: 'nearest' });
}
</script>

<template>
    <section ref="sectionRoot" class="matchday-center" aria-labelledby="matchday-title">
        <div class="shell">
            <header class="matchday-heading" data-reveal>
                <div><div class="eyebrow">Competition Hub</div><h2 id="matchday-title">Matchday Center</h2></div>
                <div class="matchday-nav" aria-label="Pilihan matchday">
                    <button v-for="day in ['08','09']" :key="day" :class="['matchday-button',{ active:selectedMatchday === day }]" type="button" @click="selectMatchday(day)">Matchday {{ day }}</button>
                </div>
            </header>

            <div class="matchday-grid">
                <section :class="['matchday-panel','results-panel',{ selected:selectedMatchday === '08' }]" aria-labelledby="latest-results-title" data-reveal>
                    <div class="matchday-panel-head"><h3 id="latest-results-title">Hasil Terbaru</h3><span>Matchday 08</span></div>
                    <div class="match-list">
                        <article v-if="!results.length" class="league-match empty-match"><div class="match-date"><strong>—</strong>—</div><div class="match-teams">Hasil pertandingan belum tersedia.</div></article>
                        <article v-for="match in results" :key="`${match.date}-${match.home}`" :class="['league-match',{ 'cosmic-fixture':match.cosmic }]">
                            <time class="match-date" :datetime="match.date"><strong>{{ match.day }}</strong>{{ match.month }}</time>
                            <div class="match-teams">
                                <div :class="['match-team-line',{ 'cosmic-team':match.cosmic === 'home' }]"><span>{{ match.home }}</span><strong>{{ match.homeScore }}</strong></div>
                                <div :class="['match-team-line',{ 'cosmic-team':match.cosmic === 'away' }]"><span>{{ match.away }}</span><strong>{{ match.awayScore }}</strong></div>
                            </div>
                            <Link v-if="match.href" class="match-link" :href="match.href" :aria-label="`Detail ${match.home} melawan ${match.away}`"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14M13 6l6 6-6 6"/></svg></Link>
                            <span v-else class="match-link muted" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                        </article>
                    </div>
                    <footer class="matchday-footer"><span>8 pertandingan telah selesai</span><Link class="schedule-link" href="/jadwal">Semua hasil <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14M13 6l6 6-6 6"/></svg></Link></footer>
                </section>

                <section :class="['matchday-panel','next-panel',{ selected:selectedMatchday === '09' }]" aria-labelledby="next-fixtures-title" data-reveal>
                    <div class="matchday-panel-head"><h3 id="next-fixtures-title">Jadwal Berikutnya</h3><span>Matchday 09</span></div>
                    <div class="match-list">
                        <article v-if="!fixtures.length" class="league-match empty-match"><div class="match-date"><strong>—</strong>—</div><div>Jadwal pertandingan belum tersedia.</div></article>
                        <article v-for="match in fixtures" :key="`${match.date}-${match.home}`" :class="['league-match',{ 'cosmic-fixture':match.cosmic }]">
                            <time class="match-date" :datetime="match.date"><strong>{{ match.day }}</strong>{{ match.month }}</time>
                            <div>
                                <div class="match-team-line"><span><b :class="{ 'cosmic-team':match.cosmic === 'home' }">{{ match.home }}</b> vs <b :class="{ 'cosmic-team':match.cosmic === 'away' }">{{ match.away }}</b></span><strong class="kickoff">{{ match.time }}</strong></div>
                                <div class="venue-line">{{ match.venue }} · Pekanbaru</div>
                            </div>
                            <Link v-if="match.href" class="match-link" :href="match.href" :aria-label="`Detail ${match.home} melawan ${match.away}`"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14M13 6l6 6-6 6"/></svg></Link>
                            <span v-else class="match-link muted" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                        </article>
                    </div>
                    <footer class="matchday-footer"><span>Matchday berikutnya dalam 14 hari</span><Link class="schedule-link" href="/jadwal">Lihat jadwal <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14M13 6l6 6-6 6"/></svg></Link></footer>
                </section>
            </div>
        </div>
    </section>
</template>

<style scoped>
.matchday-center{padding:54px 0 68px;border-bottom:1px solid var(--color-line);background:radial-gradient(circle at 88% 14%,rgba(69,183,255,.04),transparent 22%),var(--color-surface)}.shell{width:min(calc(100% - 80px),var(--content-max));margin-inline:auto}.matchday-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:30px;margin-bottom:27px}.eyebrow{margin-bottom:8px;color:var(--color-accent);font:800 .62rem var(--font-display);letter-spacing:.18em;text-transform:uppercase}.matchday-heading h2{margin:0;font:900 clamp(2.9rem,4.7vw,4.9rem)/.88 var(--font-display);letter-spacing:-.045em;text-transform:uppercase}.matchday-nav{display:flex;gap:7px}.matchday-button{min-height:39px;padding:0 16px;border:1px solid rgba(255,255,255,.2);border-radius:4px;color:#aebac4;background:transparent;font:750 .63rem var(--font-display);letter-spacing:.1em;text-transform:uppercase;cursor:pointer;transition:.2s}.matchday-button:hover,.matchday-button.active{color:#04101c;border-color:var(--color-accent);background:var(--color-accent)}.matchday-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.matchday-panel{border:1px solid rgba(175,218,255,.14);background:rgba(255,255,255,.016);transition:border-color .25s,box-shadow .25s}.matchday-panel.selected{border-color:rgba(69,183,255,.34);box-shadow:0 15px 35px rgba(0,0,0,.16)}.matchday-panel-head{display:flex;align-items:center;justify-content:space-between;gap:20px;min-height:61px;padding:0 21px;border-bottom:1px solid rgba(255,255,255,.12)}.matchday-panel-head h3{margin:0;font:900 1.5rem var(--font-display);letter-spacing:.02em;text-transform:uppercase}.matchday-panel-head span{color:var(--color-accent);font:750 .59rem var(--font-display);letter-spacing:.14em;text-transform:uppercase}.match-list{display:grid}.league-match{position:relative;min-height:87px;display:grid;grid-template-columns:68px minmax(0,1fr) 54px;align-items:center;gap:15px;padding:10px 17px;border-bottom:1px solid rgba(255,255,255,.085);transition:background .22s,transform .22s}.league-match:last-child{border:0}.league-match:hover{z-index:1;background:rgba(69,183,255,.06);transform:translateX(3px)}.match-date{padding-right:14px;border-right:1px solid rgba(255,255,255,.12);color:#b7c3cc;font:700 .59rem/1.15 var(--font-display);letter-spacing:.1em;text-align:center;text-transform:uppercase}.match-date strong{display:block;color:white;font-size:1.45rem;letter-spacing:0}.match-teams{display:grid;gap:7px}.match-team-line{display:grid;grid-template-columns:1fr auto;align-items:center;gap:15px;font:750 .72rem var(--font-display);letter-spacing:.06em;text-transform:uppercase}.match-team-line b{font-weight:750}.cosmic-team{color:var(--color-accent)}.match-team-line>strong{font:900 .95rem var(--font-display)}.match-link{display:grid;width:33px;height:33px;place-items:center;justify-self:end;border:1px solid rgba(255,255,255,.25);border-radius:50%;color:#c3ced7;transition:.2s}.match-link svg,.schedule-link svg{width:15px;stroke-width:1.8}.league-match:hover .match-link:not(.muted){color:#04101c;border-color:var(--color-accent);background:var(--color-accent)}.match-link.muted{opacity:.45}.next-panel .league-match{grid-template-columns:74px minmax(0,1fr) 54px}.kickoff{color:var(--color-accent)!important;font:900 1rem var(--font-display)!important;text-align:right}.venue-line{margin-top:5px;color:#778590;font:600 .57rem var(--font-display);letter-spacing:.07em;text-transform:uppercase}.cosmic-fixture{background:linear-gradient(90deg,rgba(69,183,255,.11),rgba(69,183,255,.015))}.matchday-footer{display:flex;align-items:center;justify-content:space-between;gap:20px;min-height:56px;padding:0 20px;border-top:1px solid rgba(255,255,255,.1);color:#7f8d98;font:650 .58rem var(--font-display);letter-spacing:.12em;text-transform:uppercase}.schedule-link{display:flex;align-items:center;gap:10px;color:#c4ced6;text-decoration:none;transition:color .2s}.schedule-link:hover{color:var(--color-accent)}
@media(max-width:900px){.shell{width:min(calc(100% - 40px),var(--content-max))}.matchday-grid{grid-template-columns:1fr}}
@media(max-width:520px){.matchday-center{padding:42px 0 52px}.shell{width:calc(100% - 32px)}.matchday-heading{align-items:flex-start;flex-direction:column}.matchday-nav{width:100%;display:grid;grid-template-columns:1fr 1fr}.league-match,.next-panel .league-match{grid-template-columns:58px minmax(0,1fr) 38px;padding-inline:11px}.match-date{padding-right:9px}.match-team-line{font-size:.66rem}.matchday-footer{align-items:flex-start;flex-direction:column;justify-content:center;padding-block:14px}}
</style>
