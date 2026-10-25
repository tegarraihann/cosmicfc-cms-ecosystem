<script setup>
import { ref } from 'vue';

defineProps({
    players: {
        type: Array,
        default: () => [
            { category: 'Top Scorer', rank: '01', name: 'Aditya Pratama', position: 'Forward', number: '10', value: '11', metric: 'Gol', featured: true, image: '/Assets/Dokumentasi/Docs-wahyu.jpeg', imagePosition: '48% center' },
            { category: 'Top Assist', rank: '01', name: 'Raka', position: 'Midfielder', number: '07', value: '08', metric: 'Assist', image: '/Assets/Dokumentasi/Docs-wahyu.jpeg', imagePosition: '63% center' },
            { category: 'Clean Sheet', rank: '01', name: 'Fajar', position: 'Goalkeeper', number: '01', value: '03', metric: 'Clean Sheet', image: '/Assets/Dokumentasi/Docs-wahyu.jpeg', imagePosition: '30% center' },
            { category: 'Man of the Match', rank: '01', name: 'Daniel', position: 'Defender', number: '04', value: '04', metric: 'Awards', image: '/Assets/Dokumentasi/Docs-wahyu.jpeg', imagePosition: '75% center' },
        ],
    },
});

const sectionRoot = ref(null);
</script>

<template>
    <section ref="sectionRoot" class="top-players" aria-labelledby="players-title">
        <div class="shell">
            <header class="players-heading" data-reveal>
                <div class="players-title-wrap">
                    <h2 id="players-title">Top Players</h2>
                    <span aria-hidden="true">→</span>
                </div>
                <div class="players-caption">Pemimpin statistik · Musim 2026</div>
            </header>

            <div class="players-grid">
                <article v-if="!players.length" class="top-player empty-player" data-reveal>
                    <div class="player-category"><span>Statistik pemain</span><span class="category-rank">—</span></div>
                    <div class="top-player-info"><div class="player-club">Belum tersedia</div><h3>Belum ada data</h3><div class="player-number">Statistik akan tampil setelah pertandingan tercatat</div></div>
                </article>
                <article
                    v-for="player in players"
                    :key="player.category"
                    :class="['top-player',{ featured:player.featured }]"
                    :style="{ '--player-image': `url('${player.image}')`, '--player-position':player.imagePosition ?? 'center' }"
                    data-reveal
                >
                    <div class="player-category"><span>{{ player.category }}</span><span class="category-rank">{{ player.rank }}</span></div>
                    <div class="top-player-info">
                        <div class="player-club">Cosmic · {{ player.position }}</div>
                        <h3>{{ player.name }}</h3>
                        <div class="player-number">#{{ player.number }}</div>
                        <div class="player-stat">{{ player.value }}<small>{{ player.metric }}</small></div>
                    </div>
                </article>
            </div>
        </div>
    </section>
</template>

<style scoped>
.top-players{padding:54px 0 68px;border-bottom:1px solid var(--color-line);background:radial-gradient(circle at 15% 20%,rgba(69,183,255,.04),transparent 23%),#060d16}.shell{width:min(calc(100% - 80px),var(--content-max));margin-inline:auto}.players-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:30px;margin-bottom:27px}.players-title-wrap{display:flex;align-items:center;gap:18px}.players-title-wrap h2{margin:0;font:900 clamp(2.9rem,4.7vw,4.9rem)/.88 var(--font-display);letter-spacing:-.045em;text-transform:uppercase}.players-title-wrap>span{color:var(--color-accent);font:300 3.4rem/1 var(--font-display);filter:drop-shadow(0 0 7px rgba(69,183,255,.5));transform:rotate(-42deg)}.players-caption{color:#8e9ba6;font:650 .64rem var(--font-display);letter-spacing:.14em;text-transform:uppercase}.players-grid{display:grid;grid-template-columns:1.18fr repeat(3,.94fr);gap:12px}.top-player{position:relative;min-height:340px;display:flex;flex-direction:column;justify-content:space-between;overflow:hidden;padding:21px;border:1px solid rgba(175,218,255,.16);isolation:isolate;transition:border-color .28s,transform .28s}.top-player::before{content:'';position:absolute;inset:0;z-index:-2;background:linear-gradient(0deg,rgba(5,10,17,.98) 0%,rgba(5,10,17,.27) 62%,rgba(5,10,17,.08)),var(--player-image) var(--player-position)/cover no-repeat;filter:saturate(.63) contrast(1.08);transition:transform .6s cubic-bezier(.2,.7,.2,1),filter .3s}.top-player::after{content:'';position:absolute;inset:0;z-index:-1;background:linear-gradient(90deg,rgba(5,10,17,.38),transparent);pointer-events:none}.top-player:hover{border-color:rgba(69,183,255,.58);transform:translateY(-5px)}.top-player:hover::before{transform:scale(1.055);filter:saturate(.9) contrast(1.08)}.top-player.featured{min-height:390px;border-color:rgba(69,183,255,.34);box-shadow:inset 0 0 35px rgba(69,183,255,.05)}.player-category{display:flex;align-items:center;justify-content:space-between;gap:10px;color:var(--color-accent);font:800 .61rem var(--font-display);letter-spacing:.16em;text-transform:uppercase}.category-rank{display:grid;width:29px;height:29px;place-items:center;border:1px solid rgba(69,183,255,.5);border-radius:50%;background:rgba(5,11,18,.58)}.top-player-info{position:relative;padding-right:64px}.player-club{margin-bottom:7px;color:#bbc7d0;font:650 .61rem var(--font-display);letter-spacing:.14em;text-transform:uppercase}.top-player h3{margin:0;font:900 clamp(2.3rem,3.5vw,3.8rem)/.82 var(--font-display);letter-spacing:-.035em;text-transform:uppercase}.player-number{margin-top:9px;color:#aebac4;font:650 .65rem var(--font-display);letter-spacing:.12em;text-transform:uppercase}.player-stat{position:absolute;right:0;bottom:0;color:var(--color-accent);font:900 4.4rem/.72 var(--font-display);letter-spacing:-.04em;text-align:right;text-shadow:0 4px 18px rgba(0,0,0,.45)}.player-stat small{display:block;margin-top:8px;color:#b6c2cc;font-size:.55rem;letter-spacing:.15em;white-space:nowrap;text-transform:uppercase}.featured .player-stat{font-size:5.4rem}.featured .top-player-info{padding-right:80px}
@media(max-width:900px){.shell{width:min(calc(100% - 40px),var(--content-max))}.players-grid{grid-template-columns:repeat(2,1fr)}.top-player,.top-player.featured{min-height:370px}}
@media(max-width:520px){.top-players{padding:42px 0 52px}.shell{width:calc(100% - 32px)}.players-heading{align-items:flex-start;flex-direction:column;gap:10px}.players-grid{grid-template-columns:1fr}.top-player,.top-player.featured{min-height:390px}}
</style>
