<script setup>
import { ref } from 'vue';

defineProps({
    standings: {
        type: Array,
        default: () => [
            { position: 1, code: 'RVS', name: 'Rivals', played: 7, won: 6, drawn: 1, lost: 0, goals: '21–8', difference: '+13', points: 19, form: ['M','M','M','S','M'] },
            { position: 2, code: 'CSM', name: 'Cosmic', logo: '/Assets/Logo/Logo.jpeg', played: 7, won: 5, drawn: 2, lost: 0, goals: '18–8', difference: '+10', points: 17, form: ['M','M','S','M','S'], cosmic: true },
            { position: 3, code: 'BYD', name: 'Backyard FC', played: 7, won: 4, drawn: 2, lost: 1, goals: '16–10', difference: '+6', points: 14, form: ['M','K','M','S','S'] },
            { position: 4, code: 'OFC', name: 'The Office', played: 7, won: 3, drawn: 1, lost: 3, goals: '13–11', difference: '+2', points: 10, form: ['K','M','M','S','K'] },
            { position: 5, code: 'NOR', name: 'Northside', played: 7, won: 2, drawn: 2, lost: 3, goals: '12–13', difference: '−1', points: 8, form: ['S','M','K','K','S'] },
            { position: 6, code: 'SDK', name: 'Sudut Kota', played: 7, won: 2, drawn: 1, lost: 4, goals: '10–14', difference: '−4', points: 7, form: ['M','K','S','K','K'] },
            { position: 7, code: 'WFC', name: 'WFC', played: 7, won: 2, drawn: 0, lost: 5, goals: '9–16', difference: '−7', points: 6, form: ['K','M','K','M','K'] },
            { position: 8, code: 'PNM', name: 'Panam United', played: 7, won: 1, drawn: 2, lost: 4, goals: '8–15', difference: '−7', points: 5, form: ['S','K','K','M','S'] },
            { position: 9, code: 'TBT', name: 'Tebing Tinggi', played: 7, won: 1, drawn: 1, lost: 5, goals: '7–18', difference: '−11', points: 4, form: ['K','K','S','K','M'] },
            { position: 10, code: 'SKY', name: 'Skyline FC', played: 7, won: 0, drawn: 1, lost: 6, goals: '5–24', difference: '−19', points: 1, form: ['K','K','K','S','K'] },
        ],
    },
});

const sectionRoot = ref(null);

const formClass = (result) => ({ M: 'win', S: 'draw', K: 'loss' }[result]);
</script>

<template>
    <section ref="sectionRoot" class="full-standings" aria-labelledby="full-standings-title">
        <div class="shell">
            <header class="standings-heading" data-reveal>
                <div>
                    <div class="eyebrow">League Table</div>
                    <h2 id="full-standings-title">Klasemen Lengkap</h2>
                </div>
                <div class="standings-controls">
                    <label for="league-season">Musim</label>
                    <select id="league-season" class="season-select">
                        <option>2026 · Regular Season</option>
                    </select>
                </div>
            </header>

            <div class="mobile-scroll-hint" data-reveal>Geser tabel untuk melihat statistik lengkap →</div>
            <div class="standings-scroll" data-reveal>
                <div class="standings-table" role="table" aria-label="Klasemen lengkap Pekanbaru Mini Soccer League">
                    <div class="league-row league-head" role="row">
                        <div>Pos</div><div>Klub</div><div>Main</div><div>M</div><div>S</div><div>K</div><div>GM–GK</div><div>SG</div><div>Poin</div><div>5 Terakhir</div>
                    </div>
                <div v-if="!standings.length" class="standings-empty" role="row">Klasemen belum tersedia.</div>
                <div v-for="club in standings" :key="club.code" :class="['league-row',{ cosmic:club.cosmic }]" role="row">
                        <div class="rank">{{ String(club.position).padStart(2,'0') }}</div>
                        <div class="club-cell">
                            <span class="table-crest"><img v-if="club.logo" :src="club.logo" alt="" loading="lazy" decoding="async" /><template v-else>{{ club.code }}</template></span>
                            {{ club.name }}
                        </div>
                        <div class="table-stat">{{ club.played }}</div><div class="table-stat">{{ club.won }}</div><div class="table-stat">{{ club.drawn }}</div><div class="table-stat">{{ club.lost }}</div><div class="table-stat">{{ club.goals }}</div><div class="table-stat">{{ club.difference }}</div><div class="table-points">{{ club.points }}</div>
                        <div class="form-cell"><span v-for="(result,index) in club.form" :key="index" :class="['form-dot',formClass(result)]">{{ result }}</span></div>
                    </div>
                </div>
            </div>

            <div class="standings-legend" data-reveal>
                <span class="legend-item"><i class="legend-color champion"></i>Zona Final</span>
                <span class="legend-item"><i class="legend-color playoff"></i>Zona Playoff</span>
                <span class="legend-item"><i class="legend-color relegation"></i>Zona Bawah</span>
                <span class="standings-note">M = Menang · S = Seri · K = Kalah · SG = Selisih Gol</span>
            </div>
        </div>
    </section>
</template>

<style scoped>
.full-standings{padding:54px 0 70px;border-bottom:1px solid var(--color-line);background:radial-gradient(circle at 12% 8%,rgba(69,183,255,.04),transparent 22%),#060d16}.shell{width:min(calc(100% - 80px),var(--content-max));margin-inline:auto}.standings-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:30px;margin-bottom:27px}.eyebrow{margin-bottom:8px;color:var(--color-accent);font:800 .62rem var(--font-display);letter-spacing:.18em;text-transform:uppercase}.standings-heading h2{margin:0;font:900 clamp(2.9rem,4.7vw,4.9rem)/.88 var(--font-display);letter-spacing:-.045em;text-transform:uppercase}.standings-controls{display:flex;align-items:center;gap:12px}.standings-controls label{color:#8996a1;font:700 .61rem var(--font-display);letter-spacing:.14em;text-transform:uppercase}.season-select{min-height:40px;padding:0 35px 0 14px;border:1px solid rgba(175,218,255,.22);border-radius:4px;color:#eef4f8;background:#0b1620;font:700 .68rem var(--font-display);letter-spacing:.08em;text-transform:uppercase}.season-select:focus{outline:1px solid var(--color-accent);outline-offset:2px}.mobile-scroll-hint{display:none;margin-bottom:10px;color:#82909c;font:650 .59rem var(--font-display);letter-spacing:.09em;text-transform:uppercase}.standings-scroll{overflow:hidden;border:1px solid rgba(175,218,255,.14);background:rgba(5,11,18,.72)}.standings-table{min-width:1060px}.league-row{position:relative;display:grid;grid-template-columns:55px minmax(215px,1fr) repeat(7,65px) 145px;align-items:center;min-height:61px;padding:0 18px;border-bottom:1px solid rgba(255,255,255,.085);transition:background .22s,transform .22s}.league-row:last-child{border-bottom:0}.league-row:not(.league-head):hover{z-index:1;background:rgba(69,183,255,.055);transform:translateX(3px)}.league-head{min-height:44px;color:#707d88;background:rgba(255,255,255,.023);font:700 .56rem var(--font-display);letter-spacing:.12em;text-transform:uppercase}.league-row>:not(.club-cell):not(.form-cell){text-align:center}.league-head>:nth-child(2){text-align:left}.rank{color:#c6d1da;font:850 .9rem var(--font-display)}.club-cell{display:flex;min-width:0;align-items:center;gap:12px;font:800 .76rem var(--font-display);letter-spacing:.07em;text-transform:uppercase}.table-crest{display:grid;flex:0 0 32px;width:32px;height:32px;place-items:center;border:1px solid rgba(255,255,255,.3);border-radius:50%;color:#cbd6df;background:rgba(255,255,255,.025);font:800 .45rem var(--font-display)}.table-crest img{width:25px;height:25px;object-fit:cover;border-radius:50%}.table-stat{color:#aebac4;font:650 .7rem var(--font-display)}.table-points{color:white;font:900 1rem var(--font-display)}.form-cell{display:flex;justify-content:flex-end;gap:5px}.form-dot{display:grid;width:21px;height:21px;place-items:center;border-radius:50%;font:800 .52rem var(--font-display)}.form-dot.win{color:#04101c;background:var(--color-accent)}.form-dot.draw{color:#e0e7ed;background:#596875}.form-dot.loss{color:#e3e8ec;background:#75434a}.league-row.cosmic{background:linear-gradient(90deg,rgba(69,183,255,.16),rgba(69,183,255,.025))}.league-row.cosmic::before{content:'';position:absolute;inset:0 auto 0 0;width:3px;background:var(--color-accent)}.cosmic .rank,.cosmic .club-cell,.cosmic .table-points{color:var(--color-accent)}.cosmic .table-crest{border-color:var(--color-accent);background:var(--color-accent)}.standings-legend{display:flex;align-items:center;flex-wrap:wrap;gap:22px;margin-top:17px;color:#8d9aa5;font:650 .58rem var(--font-display);letter-spacing:.1em;text-transform:uppercase}.legend-item{display:flex;align-items:center;gap:8px}.legend-color{width:18px;height:3px;background:#697783}.legend-color.champion{background:var(--color-accent)}.legend-color.playoff{background:#87c7ef}.legend-color.relegation{background:#bd5459}.standings-note{margin-left:auto;color:#65737f;font-size:.56rem}
@media(max-width:900px){.shell{width:min(calc(100% - 40px),var(--content-max))}.standings-scroll{overflow-x:auto}.mobile-scroll-hint{display:block}.standings-note{width:100%;margin-left:0}}
@media(max-width:520px){.full-standings{padding:42px 0 52px}.shell{width:calc(100% - 32px)}.standings-heading{align-items:flex-start;flex-direction:column}.standings-controls{width:100%;justify-content:space-between}.season-select{flex:1}.standings-legend{gap:13px}}
.standings-empty{display:grid;min-height:150px;place-items:center;color:#788894;font-size:.72rem;letter-spacing:.06em}
</style>
