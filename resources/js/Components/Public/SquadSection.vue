<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: 'Meet the Squad' },
    allPlayersHref: { type: String, default: '/tim' },
    players: {
        type: Array,
        default: () => [
            { number: 10, name: 'Aditya', position: 'Forward', photo: '/Assets/Dokumentasi/Docs-wahyu.jpeg', href: '/tim', focalPoint: '35% 35%' },
            { number: 7, name: 'Raka', position: 'Midfielder', photo: '/Assets/Dokumentasi/Docs-wahyu.jpeg', href: '/tim', focalPoint: '55% 28%' },
            { number: 4, name: 'Daniel', position: 'Defender', photo: '/Assets/Dokumentasi/Docs-wahyu.jpeg', href: '/tim', focalPoint: '72% 35%' },
        ],
    },
});

</script>

<template>
    <section id="squad" class="squad" aria-labelledby="squad-title">
        <div class="shell">
            <div class="squad-heading">
                <div class="squad-title-wrap">
                    <h2 id="squad-title" class="squad-title">{{ title }}</h2>
                    <span class="crown-mark" aria-hidden="true">
                        <svg viewBox="0 0 54 42" fill="none" stroke="currentColor"><path d="M5 33 9 11l12 12L29 4l7 18 13-10-5 23Z" /><path d="m8 39 35-1" /></svg>
                    </span>
                </div>
                <Link class="all-players" :href="allPlayersHref">
                    Lihat semua tim
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </Link>
            </div>

            <div class="squad-grid">
                <article v-if="!players.length" class="player-card empty-card">
                    <div class="player-info"><div class="player-number">#—</div><h3 class="player-name">Belum tersedia</h3><div class="player-role">Data pemain belum dipublikasikan</div></div>
                </article>
                <article v-for="player in players" :key="`${player.number}-${player.name}`" class="player-card">
                    <img
                        class="player-photo"
                        :src="player.photo"
                        :alt="`${player.name}, ${player.position} Cosmic`"
                        :style="{ objectPosition: player.focalPoint ?? 'center 23%' }"
                        loading="lazy"
                        decoding="async"
                    />
                    <div class="player-info">
                        <div class="player-number">#{{ player.number }}</div>
                        <h3 class="player-name">{{ player.name }}</h3>
                        <div class="player-role">{{ player.position }}</div>
                    </div>
                    <Link class="player-link" :href="player.href" :aria-label="`Lihat profil ${player.name}`">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                    </Link>
                </article>
            </div>
        </div>
    </section>
</template>

<style scoped>
.squad { padding: 42px 0 92px; border-bottom: 1px solid var(--color-line); background: linear-gradient(rgba(175,218,255,.025) 1px,transparent 1px),radial-gradient(circle at 15% 10%,rgba(69,183,255,.055),transparent 24%),var(--color-surface); background-size: 100% 90px,auto,auto; }
.shell { width: min(calc(100% - 80px),var(--content-max)); margin-inline: auto; }
.squad-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; margin-bottom: 24px; }
.squad-title-wrap { display: flex; align-items: center; gap: 20px; }
.squad-title { margin: 0; color: var(--color-paper); font: 900 clamp(2.8rem,4.4vw,4.7rem)/.9 var(--font-display); letter-spacing: -.045em; text-transform: uppercase; }
.crown-mark { position: relative; width: 48px; height: 38px; color: var(--color-accent); filter: drop-shadow(0 0 7px rgba(69,183,255,.5)); transform: rotate(8deg); }
.crown-mark svg { width: 100%; height: 100%; overflow: visible; stroke-width: 3; stroke-linecap: round; stroke-linejoin: round; }
.all-players { display: inline-flex; align-items: center; gap: 10px; color: #bdc9d4; font: 700 .67rem var(--font-display); letter-spacing: .12em; text-decoration: none; text-transform: uppercase; transition: color .2s; }
.all-players:hover { color: var(--color-accent); }
.all-players svg { width: 17px; stroke-width: 1.8; }
.squad-grid { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 18px; }
.player-card { position: relative; min-height: 335px; overflow: hidden; isolation: isolate; border: 1px solid rgba(175,218,255,.2); border-radius: 4px; background: #101820; transition: border-color .3s,transform .3s,box-shadow .3s; }
.empty-card { grid-column: 1 / -1; border-style: dashed; background: rgba(255,255,255,.012); }
.empty-card .player-info { top: 50%; right: 22px; transform: translateY(-50%); text-align: center; }
.player-card::before { content: ''; position: absolute; inset: 0; z-index: -1; pointer-events: none; background: linear-gradient(90deg,rgba(5,9,15,.86) 0%,rgba(5,9,15,.3) 54%,rgba(5,9,15,.06) 100%),linear-gradient(0deg,rgba(5,9,15,.58),transparent 55%); }
.player-card:hover { border-color: rgba(69,183,255,.65); box-shadow: 0 12px 35px rgba(0,0,0,.28),0 0 24px rgba(69,183,255,.08); transform: translateY(-5px); }
.player-photo { position: absolute; inset: 0; z-index: -2; width: 100%; height: 100%; object-fit: cover; filter: saturate(.55) contrast(1.08); transition: transform .6s cubic-bezier(.2,.7,.2,1),filter .35s; }
.player-card:hover .player-photo { filter: saturate(.82) contrast(1.08); transform: scale(1.055); }
.player-info { position: absolute; z-index: 2; top: 22px; left: 22px; text-transform: uppercase; }
.player-number { margin-bottom: 3px; color: #ecf2f7; font: 500 1.65rem/1 var(--font-display); }
.player-name { margin: 0; color: var(--color-paper); font: 900 clamp(2rem,3vw,3rem)/.9 var(--font-display); letter-spacing: -.025em; }
.player-role { margin-top: 10px; color: var(--color-accent); font: 800 .68rem var(--font-display); letter-spacing: .22em; text-shadow: 0 0 14px rgba(69,183,255,.2); }
.player-link { position: absolute; right: 18px; bottom: 18px; display: grid; width: 38px; height: 38px; place-items: center; border: 1px solid rgba(255,255,255,.72); border-radius: 50%; color: white; background: rgba(5,9,15,.5); backdrop-filter: blur(5px); transition: .25s; }
.player-link svg { width: 18px; stroke-width: 1.8; }
.player-card:hover .player-link,.player-link:hover { color: #04101c; border-color: var(--color-accent); background: var(--color-accent); transform: translateX(3px); }

@media(max-width:900px) {
    .squad { padding-bottom: 72px; }
    .shell { width: min(calc(100% - 40px),var(--content-max)); }
    .squad-grid { grid-template-columns: repeat(2,1fr); }
    .player-card:last-child { grid-column: 1/-1; }
}

@media(max-width:520px) {
    .squad { padding: 38px 0 54px; }
    .shell { width: calc(100% - 32px); }
    .squad-heading { align-items: flex-end; }
    .squad-title-wrap { gap: 10px; }
    .crown-mark { width: 39px; }
    .all-players { max-width: 80px; justify-content: flex-end; text-align: right; }
    .squad-grid { grid-template-columns: 1fr; }
    .player-card,.player-card:last-child { min-height: 380px; grid-column: auto; }
}
</style>
