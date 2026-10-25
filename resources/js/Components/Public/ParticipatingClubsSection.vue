<script setup>
import { ref } from 'vue';

defineProps({
    clubs: {
        type: Array,
        default: () => [
            { code: 'CSM', name: 'Cosmic', district: 'Pekanbaru', established: '2026', logo: '/Assets/Logo/Logo.jpeg', cosmic: true },
            { code: 'RVS', name: 'Rivals', district: 'Senapelan', established: '2021', shield: true },
            { code: 'BYD', name: 'Backyard FC', district: 'Marpoyan', established: '2022' },
            { code: 'OFC', name: 'The Office', district: 'Sukajadi', established: '2020', shield: true },
            { code: 'NOR', name: 'Northside', district: 'Rumbai', established: '2021' },
            { code: 'SDK', name: 'Sudut Kota', district: 'Lima Puluh', established: '2023', shield: true },
            { code: 'WFC', name: 'WFC', district: 'Tampan', established: '2019' },
            { code: 'PNM', name: 'Panam United', district: 'Panam', established: '2020', shield: true },
            { code: 'TBT', name: 'Tebing Tinggi', district: 'Bukit Raya', established: '2022' },
            { code: 'SKY', name: 'Skyline FC', district: 'Tenayan Raya', established: '2023', shield: true },
        ],
    },
});

const sectionRoot = ref(null);
</script>

<template>
    <section ref="sectionRoot" class="clubs" aria-labelledby="clubs-title">
        <div class="shell">
            <header class="clubs-heading" data-reveal>
                <div class="clubs-title-wrap"><h2 id="clubs-title">Klub Peserta</h2><span aria-hidden="true">→</span></div>
                <div class="clubs-caption">{{ clubs.length }} Klub · Pekanbaru · Musim 2026</div>
            </header>

            <div class="clubs-grid">
                <article v-if="!clubs.length" class="club-card empty-club" data-reveal>
                    <div class="participant-crest">—</div><h3>Belum ada klub</h3><div class="participant-meta">Peserta akan tampil setelah dipublikasikan</div>
                </article>
                <article v-for="club in clubs" :key="club.code" :class="['club-card',{ 'cosmic-club':club.cosmic }]" data-reveal>
                    <div :class="['participant-crest',{ shield:club.shield }]">
                        <img v-if="club.logo" :src="club.logo" :alt="`Logo ${club.name}`" loading="lazy" decoding="async" />
                        <template v-else>{{ club.code }}</template>
                    </div>
                    <h3>{{ club.name }}</h3>
                    <div class="participant-meta">{{ club.district }} · Est. {{ club.established }}</div>
                </article>
            </div>
        </div>
    </section>
</template>

<style scoped>
.clubs{padding:54px 0 68px;border-bottom:1px solid var(--color-line);background:radial-gradient(circle at 12% 10%,rgba(69,183,255,.04),transparent 22%),#060d16}.shell{width:min(calc(100% - 80px),var(--content-max));margin-inline:auto}.clubs-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:30px;margin-bottom:27px}.clubs-title-wrap{display:flex;align-items:center;gap:18px}.clubs-title-wrap h2{margin:0;font:900 clamp(2.9rem,4.7vw,4.9rem)/.88 var(--font-display);letter-spacing:-.045em;text-transform:uppercase}.clubs-title-wrap>span{color:var(--color-accent);font:300 3.4rem/1 var(--font-display);filter:drop-shadow(0 0 7px rgba(69,183,255,.5));transform:rotate(-42deg)}.clubs-caption{color:#8e9ba6;font:650 .64rem var(--font-display);letter-spacing:.14em;text-transform:uppercase}.clubs-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}.club-card{position:relative;min-height:215px;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;padding:20px;border:1px solid rgba(175,218,255,.13);background:rgba(255,255,255,.016);text-align:center;transition:border-color .25s,background .25s,transform .25s}.club-card::after{content:'';position:absolute;right:-35px;bottom:-65px;width:135px;height:135px;border:1px solid rgba(255,255,255,.045);border-radius:50%;pointer-events:none}.club-card:hover{border-color:rgba(69,183,255,.48);background:rgba(69,183,255,.05);transform:translateY(-4px)}.participant-crest{position:relative;display:grid;width:78px;height:78px;place-items:center;margin-bottom:17px;border:2px solid rgba(255,255,255,.55);border-radius:50%;color:#eef4f8;background:rgba(255,255,255,.025);font:900 1.05rem var(--font-display);letter-spacing:.03em;transition:transform .3s,border-color .3s}.club-card:hover .participant-crest{border-color:var(--color-accent);transform:scale(1.06)}.participant-crest.shield{border-radius:8px 8px 28px 28px}.participant-crest img{width:62px;height:62px;object-fit:cover;border-radius:50%}.club-card h3{margin:0;font:900 1.3rem/.9 var(--font-display);letter-spacing:.015em;text-transform:uppercase}.participant-meta{margin-top:10px;color:#778590;font:650 .57rem var(--font-display);letter-spacing:.12em;text-transform:uppercase}.club-card.cosmic-club{border-color:rgba(69,183,255,.34);background:linear-gradient(145deg,rgba(69,183,255,.12),rgba(69,183,255,.018))}.cosmic-club::before{content:'COSMIC';position:absolute;top:15px;right:15px;color:var(--color-accent);font:800 .52rem var(--font-display);letter-spacing:.15em}.cosmic-club .participant-crest{border-color:var(--color-accent);box-shadow:0 0 24px rgba(69,183,255,.12)}.cosmic-club h3{color:var(--color-accent)}
@media(max-width:900px){.shell{width:min(calc(100% - 40px),var(--content-max))}.clubs-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:520px){.clubs{padding:42px 0 52px}.shell{width:calc(100% - 32px)}.clubs-heading{align-items:flex-start;flex-direction:column;gap:10px}.clubs-grid{grid-template-columns:repeat(2,1fr);gap:8px}.club-card{min-height:190px;padding:16px}.participant-crest{width:65px;height:65px}.participant-crest img{width:52px;height:52px}.club-card h3{font-size:1.12rem}}
.empty-club{grid-column:1/-1;min-height:215px}.empty-club:hover{transform:none}
</style>
