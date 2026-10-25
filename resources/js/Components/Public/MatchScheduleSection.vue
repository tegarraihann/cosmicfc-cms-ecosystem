<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    groups: {
        type: Array,
        default: () => [
            { key: 'october', label: 'Oktober 2026', matches: [
                { slug: 'cosmic-vs-northside', day: '05', weekday: 'Sen', status: 'completed', matchday: '06', home: 'Cosmic', away: 'Northside', display: '3 - 1', venue: 'Aero Sport Center', city: 'Pekanbaru' },
                { slug: 'backyard-vs-cosmic', day: '12', weekday: 'Sen', status: 'completed', matchday: '07', home: 'Backyard FC', away: 'Cosmic', display: '2 - 2', venue: 'Rumbai Sport Hall', city: 'Pekanbaru' },
                { slug: 'cosmic-vs-rivals', day: '24', weekday: 'Sab', status: 'upcoming', matchday: '08', home: 'Cosmic', away: 'Rivals', display: '19.30', venue: 'Aero Sport Center', city: 'Pekanbaru' },
            ]},
            { key: 'november', label: 'November 2026', matches: [
                { slug: 'office-vs-cosmic', day: '07', weekday: 'Sab', status: 'upcoming', matchday: '09', home: 'The Office', away: 'Cosmic', display: '20.00', venue: 'Rumbai Sport Hall', city: 'Pekanbaru' },
                { slug: 'cosmic-vs-sudut-kota', day: '21', weekday: 'Sab', status: 'upcoming', matchday: '10', home: 'Cosmic', away: 'Sudut Kota', display: '19.30', venue: 'Aero Sport Center', city: 'Pekanbaru' },
            ]},
            { key: 'december', label: 'Desember 2026', matches: [
                { slug: 'wfc-vs-cosmic', day: '05', weekday: 'Sab', status: 'upcoming', matchday: '11', home: 'WFC', away: 'Cosmic', display: '20.00', venue: 'Panam Soccer Field', city: 'Pekanbaru' },
            ]},
        ],
    },
});

const statusFilter = ref('all');
const monthFilter = ref('all');
const statusFilters = [
    { key: 'all', label: 'Semua' },
    { key: 'live', label: 'Live' },
    { key: 'upcoming', label: 'Mendatang' },
    { key: 'completed', label: 'Selesai' },
];
const statusLabel = (status) => ({
    live: 'Sedang Berlangsung',
    upcoming: 'Mendatang',
    postponed: 'Ditunda',
    completed: 'Full Time',
    walkover: 'Walkover',
}[status] || status);

const filteredGroups = computed(() => props.groups
    .filter((group) => monthFilter.value === 'all' || group.key === monthFilter.value)
    .map((group) => ({
        ...group,
        matches: group.matches.filter((match) => statusFilter.value === 'all' || match.status === statusFilter.value),
    }))
    .filter((group) => group.matches.length));

</script>

<template>
    <section class="schedule" aria-labelledby="schedule-title">
        <div class="shell">
            <div class="schedule-heading">
                <div class="schedule-title-wrap"><h2 id="schedule-title">Match Schedule</h2><span aria-hidden="true">→</span></div>
                <p>Pilih status dan bulan untuk menemukan pertandingan COSMIC sepanjang musim 2026.</p>
            </div>
            <div class="filter-bar" aria-label="Filter jadwal pertandingan">
                <div class="status-filters" role="group" aria-label="Filter berdasarkan status">
                    <button v-for="filter in statusFilters" :key="filter.key" type="button" :class="['filter-button',{active:statusFilter===filter.key}]" :aria-pressed="statusFilter===filter.key" @click="statusFilter=filter.key">{{ filter.label }}</button>
                </div>
                <div class="month-filter"><label for="month-filter">Bulan</label><select id="month-filter" v-model="monthFilter"><option value="all">Semua Bulan</option><option v-for="group in groups" :key="group.key" :value="group.key">{{ group.label }}</option></select></div>
            </div>

            <div v-if="filteredGroups.length" class="schedule-groups">
                <section v-for="group in filteredGroups" :key="group.key" class="month-group">
                    <div class="month-heading"><h3>{{ group.label }}</h3><span></span><small>{{ group.matches.length }} Pertandingan</small></div>
                    <div class="schedule-list">
                        <article v-for="match in group.matches" :key="match.slug" class="schedule-row">
                            <time class="row-date"><strong>{{ match.day }}</strong>{{ match.weekday }}</time>
                            <div class="row-competition">
                                {{ match.competition || "Pertandingan COSMIC"
                                }}<template v-if="match.matchday"
                                    ><br />Matchday {{ match.matchday }}</template
                                >
                            </div>
                            <div class="row-teams"><span>{{ match.home }}</span><strong :class="{upcoming:match.status==='upcoming'||match.status==='postponed',live:match.status==='live'}">{{ match.display }}</strong><span>{{ match.away }}</span></div>
                            <div class="row-location">{{ match.venue }}<br />{{ match.city }}</div>
                            <div :class="['row-status',match.status]">{{ statusLabel(match.status) }}</div>
                            <Link class="row-link" :href="`/pertandingan/${match.slug}`" :aria-label="`Detail ${match.home} melawan ${match.away}`"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14M13 6l6 6-6 6" /></svg></Link>
                        </article>
                    </div>
                </section>
            </div>
            <div v-else class="empty-state">Tidak ada pertandingan untuk filter yang dipilih.</div>
        </div>
    </section>
</template>

<style scoped>
.schedule{padding:55px 0 70px;border-bottom:1px solid var(--color-line);background:radial-gradient(circle at 78% 12%,rgba(69,183,255,.05),transparent 24%),var(--color-surface)}.shell{width:min(calc(100% - 80px),var(--content-max));margin-inline:auto}.schedule-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:30px;margin-bottom:28px}.schedule-title-wrap{display:flex;align-items:center;gap:18px}.schedule-title-wrap h2{margin:0;font:900 clamp(2.9rem,4.8vw,5rem)/.88 var(--font-display);letter-spacing:-.045em;text-transform:uppercase}.schedule-title-wrap span{color:var(--color-accent);font:300 3.5rem/1 var(--font-display);filter:drop-shadow(0 0 7px rgba(69,183,255,.5));transform:rotate(-42deg)}.schedule-heading>p{max-width:300px;margin:0;color:#9faab4;font-size:.77rem;line-height:1.55;text-align:right}.filter-bar{display:flex;align-items:center;justify-content:space-between;gap:25px;margin-bottom:38px;padding:13px 14px;border:1px solid rgba(175,218,255,.15);background:rgba(255,255,255,.022)}.status-filters{display:flex;align-items:center;gap:6px}.filter-button{min-height:39px;padding:0 18px;border:1px solid transparent;border-radius:4px;color:#aebac5;background:transparent;font:750 .68rem var(--font-display);letter-spacing:.12em;text-transform:uppercase;cursor:pointer;transition:.2s}.filter-button:hover{color:var(--color-accent);border-color:rgba(69,183,255,.25)}.filter-button.active{color:#04101c;border-color:var(--color-accent);background:var(--color-accent)}.month-filter{display:flex;align-items:center;gap:12px}.month-filter label{color:#8f9ca7;font:700 .64rem var(--font-display);letter-spacing:.14em;text-transform:uppercase}.month-filter select{min-width:165px;min-height:39px;padding:0 36px 0 14px;border:1px solid rgba(255,255,255,.22);border-radius:4px;color:#edf3f8;background:#0b1520;font:700 .7rem var(--font-display);letter-spacing:.08em;text-transform:uppercase}.month-filter select:focus{outline:1px solid var(--color-accent);outline-offset:2px}.month-group{margin-bottom:39px}.month-group:last-child{margin-bottom:0}.month-heading{display:flex;align-items:center;gap:18px;margin-bottom:13px}.month-heading h3{margin:0;font:800 1.25rem var(--font-display);letter-spacing:.07em;text-transform:uppercase;white-space:nowrap}.month-heading span{width:100%;height:1px;background:rgba(255,255,255,.14)}.month-heading small{color:#77848f;font:650 .6rem var(--font-display);letter-spacing:.12em;text-transform:uppercase;white-space:nowrap}.schedule-list{display:grid;gap:7px}.schedule-row{position:relative;display:grid;min-height:88px;grid-template-columns:80px minmax(160px,.7fr) minmax(300px,1.25fr) minmax(170px,.65fr) 120px 42px;align-items:center;gap:18px;padding:10px 16px;border:1px solid rgba(255,255,255,.1);background:linear-gradient(90deg,rgba(255,255,255,.026),rgba(255,255,255,.01));transition:.24s}.schedule-row:hover{z-index:1;border-color:rgba(69,183,255,.38);background:linear-gradient(90deg,rgba(69,183,255,.12),rgba(69,183,255,.025));transform:translateX(4px)}.row-date{padding-right:16px;border-right:1px solid rgba(255,255,255,.14);color:white;font:700 .68rem/1.05 var(--font-display);letter-spacing:.12em;text-align:center;text-transform:uppercase}.row-date strong{display:block;font-size:1.8rem;letter-spacing:0}.row-competition{color:#99a6b1;font:650 .64rem/1.35 var(--font-display);letter-spacing:.1em;text-transform:uppercase}.row-teams{display:grid;grid-template-columns:minmax(0,1fr) auto minmax(0,1fr);align-items:center;gap:13px}.row-teams span{overflow:hidden;font:800 .9rem var(--font-display);letter-spacing:.04em;text-overflow:ellipsis;text-transform:uppercase;white-space:nowrap}.row-teams span:last-child{text-align:right}.row-teams strong{min-width:54px;color:var(--color-accent);font:900 1.22rem var(--font-display);letter-spacing:.08em;text-align:center}.row-teams strong.upcoming{color:#8996a1;font-size:.7rem;letter-spacing:.12em}.row-location{color:#b3bec8;font:600 .68rem/1.4 var(--font-display);letter-spacing:.05em;text-transform:uppercase}.row-status{justify-self:start;padding:6px 10px;border:1px solid rgba(255,255,255,.18);border-radius:30px;color:#aebac5;font:700 .58rem var(--font-display);letter-spacing:.1em;text-transform:uppercase;white-space:nowrap}.row-status.upcoming{color:var(--color-accent);border-color:rgba(69,183,255,.35)}.row-link{display:grid;width:32px;height:32px;place-items:center;border:1px solid rgba(255,255,255,.25);border-radius:50%;color:#cbd5dd;transition:.2s}.row-link svg{width:16px;stroke-width:1.8}.schedule-row:hover .row-link{color:#04101c;border-color:var(--color-accent);background:var(--color-accent);transform:translateX(2px)}.empty-state{padding:45px 20px;border:1px dashed rgba(255,255,255,.18);color:#8f9ca7;font:700 .75rem var(--font-display);letter-spacing:.14em;text-align:center;text-transform:uppercase}
@media(max-width:1000px){.shell{width:min(calc(100% - 40px),var(--content-max))}.schedule-row{grid-template-columns:70px minmax(130px,.55fr) minmax(260px,1.25fr) 105px 38px}.row-location{display:none}}
@media(max-width:700px){.schedule-heading{align-items:flex-start;flex-direction:column}.schedule-heading>p{text-align:left}.filter-bar{align-items:stretch;flex-direction:column}.status-filters{display:grid;grid-template-columns:repeat(3,1fr)}.filter-button{padding-inline:8px}.month-filter{justify-content:space-between}.month-filter select{flex:1}.schedule-row{grid-template-columns:55px minmax(0,1fr) 34px;gap:10px;padding:12px 9px}.row-date{padding-right:9px}.row-competition{display:none}.row-teams{grid-template-columns:1fr auto 1fr}.row-teams span{font-size:.72rem}.row-status{grid-column:2;font-size:.52rem}.row-link{grid-column:3;grid-row:1/3}.month-heading{gap:10px}.month-heading h3{font-size:1rem}}
.row-status.upcoming,
.row-status.postponed {
    color: var(--color-accent);
    border-color: rgba(69, 183, 255, .35);
}
.row-status.live {
    color: #fff;
    border-color: var(--color-accent);
    background: rgba(69, 183, 255, .16);
    box-shadow: 0 0 16px rgba(69, 183, 255, .1);
}
.row-teams strong.live {
    color: #fff;
    text-shadow: 0 0 12px rgba(69, 183, 255, .55);
}
@media (max-width: 700px) {
    .status-filters {
        grid-template-columns: repeat(4, 1fr);
    }
}
</style>
