<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    match: { type: Object, required: true },
    compact: { type: Boolean, default: false },
});

</script>

<template>
    <section
        :class="['live-match', { compact }]"
        aria-labelledby="live-match-title"
    >
        <div class="shell">
            <article class="live-card">
                <div class="live-heading">
                    <span class="live-badge"
                        ><i></i> Sedang Berlangsung</span
                    >
                    <div>
                        <small>{{
                            match.competition || "Pertandingan COSMIC"
                        }}</small>
                        <strong v-if="match.matchday"
                            >Matchday {{ match.matchday }}</strong
                        >
                    </div>
                </div>

                <div class="live-fixture">
                    <div class="team home">
                        <img
                            v-if="match.home.logo"
                            :src="match.home.logo"
                            :alt="`Logo ${match.home.name}`"
                            loading="lazy"
                            decoding="async"
                        />
                        <span v-else>{{ match.home.shortName }}</span>
                        <h2 id="live-match-title">{{ match.home.name }}</h2>
                    </div>
                    <div class="live-score" aria-label="Skor sementara">
                        <small>Skor Sementara</small>
                        <strong>{{ match.score || "0 - 0" }}</strong>
                    </div>
                    <div class="team away">
                        <h2>{{ match.away.name }}</h2>
                        <img
                            v-if="match.away.logo"
                            :src="match.away.logo"
                            :alt="`Logo ${match.away.name}`"
                            loading="lazy"
                            decoding="async"
                        />
                        <span v-else>{{ match.away.shortName }}</span>
                    </div>
                </div>

                <div class="live-meta">
                    <p>
                        <b>{{ match.time }}</b
                        ><span
                            >{{ match.venue
                            }}<template v-if="match.address">
                                · {{ match.address }}</template
                            ></span
                        >
                    </p>
                    <Link class="live-link" :href="match.href"
                        >Lihat Pertandingan <span>→</span></Link
                    >
                </div>
            </article>
        </div>
    </section>
</template>

<style scoped>
.live-match { padding: 28px 0; border-bottom: 1px solid var(--color-line); background: #030912; }
.shell { width: min(calc(100% - 80px), var(--content-max)); margin-inline: auto; }
.live-card { position: relative; display: grid; grid-template-columns: minmax(180px, .55fr) minmax(480px, 1.45fr) minmax(220px, .65fr); align-items: center; gap: 30px; overflow: hidden; padding: 24px 28px; border: 1px solid rgba(69, 183, 255, .42); background: radial-gradient(circle at 50% 0, rgba(69, 183, 255, .14), transparent 40%), #07111d; box-shadow: inset 4px 0 var(--color-accent); }
.live-heading { display: grid; gap: 12px; }
.live-badge { display: inline-flex; width: max-content; align-items: center; gap: 9px; color: var(--color-accent); font: 800 .68rem var(--font-display); letter-spacing: .14em; text-transform: uppercase; }
.live-badge i { width: 9px; height: 9px; border-radius: 50%; background: var(--color-accent); box-shadow: 0 0 0 5px rgba(69, 183, 255, .12), 0 0 16px rgba(69, 183, 255, .8); }
.live-heading > div { display: grid; gap: 4px; color: #8193a1; font: 700 .58rem var(--font-display); letter-spacing: .11em; text-transform: uppercase; }
.live-heading strong { color: #d8e4eb; font-size: .66rem; }
.live-fixture { display: grid; grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr); align-items: center; gap: 18px; }
.team { display: flex; min-width: 0; align-items: center; gap: 13px; }
.team.away { justify-content: flex-end; text-align: right; }
.team img, .team > span { display: grid; flex: 0 0 50px; width: 50px; height: 50px; place-items: center; object-fit: contain; }
.team > span { border: 1px solid rgba(255, 255, 255, .25); border-radius: 50%; font: 800 .7rem var(--font-display); }
.team h2 { min-width: 0; margin: 0; font: 900 clamp(1.25rem, 2vw, 2.15rem)/.92 var(--font-display); letter-spacing: -.025em; overflow-wrap: anywhere; text-transform: uppercase; }
.live-score { text-align: center; }
.live-score small { display: block; margin-bottom: 5px; color: #7f919e; font: 700 .5rem var(--font-display); letter-spacing: .11em; text-transform: uppercase; }
.live-score strong { color: var(--color-accent); font: 900 clamp(1.9rem, 3vw, 3rem)/1 var(--font-display); letter-spacing: .06em; white-space: nowrap; }
.live-meta { display: flex; align-items: flex-end; flex-direction: column; gap: 12px; text-align: right; }
.live-meta p { display: grid; gap: 3px; margin: 0; }
.live-meta b { font: 750 .74rem var(--font-display); letter-spacing: .08em; text-transform: uppercase; }
.live-meta p span { color: #8395a2; font-size: .68rem; line-height: 1.4; }
.live-link { display: inline-flex; min-height: 42px; align-items: center; gap: 12px; padding: 0 16px; border: 1px solid var(--color-accent); border-radius: 5px; color: #03101b; background: var(--color-accent); font: 800 .64rem var(--font-display); letter-spacing: .08em; text-decoration: none; text-transform: uppercase; transition: .2s; }
.live-link:hover { color: white; background: var(--color-accent-hover); transform: translateY(-2px); }
.compact { padding-block: 18px; }
.compact .live-card { padding-block: 18px; }
@media (max-width: 1000px) { .live-card { grid-template-columns: 1fr; } .live-heading { display: flex; align-items: center; justify-content: space-between; } .live-meta { align-items: center; flex-direction: row; justify-content: space-between; text-align: left; } }
@media (max-width: 700px) { .shell { width: calc(100% - 32px); } .live-card { gap: 22px; padding: 20px 16px; } .live-heading { align-items: flex-start; flex-direction: column; } .live-fixture { gap: 8px; } .team { align-items: center; flex-direction: column; text-align: center; } .team.away { flex-direction: column-reverse; text-align: center; } .team img, .team > span { flex-basis: 42px; width: 42px; height: 42px; } .team h2 { font-size: 1.18rem; } .live-score strong { font-size: 1.8rem; } .live-meta { align-items: stretch; flex-direction: column; text-align: left; } .live-link { justify-content: center; } }
</style>
