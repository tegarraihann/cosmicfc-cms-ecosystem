<script setup>
import { Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import SiteNavbar from "@/Components/Public/SiteNavbar.vue";
import SiteFooter from "@/Components/Public/SiteFooter.vue";

const props = defineProps({
    matchKey: String,
    matchData: { type: Object, default: null },
    awardData: { type: Object, default: null },
});
const match = computed(() => {
    if (!props.matchData) return null;
    const data = props.matchData;
    const lineupRows = (clubId) =>
        Object.values(data.lineups?.[clubId] ?? {}).map((player) => ({
            ...player,
            label: player.role === "substitute" ? "Cadangan" : "Pemain inti",
        }));
    return {
        ...data,
        image: data.cover || "/Assets/Dokumentasi/Docs-wahyu.jpeg",
        display: data.score || "VS",
        city: data.address || "Pekanbaru",
        home: { ...data.home, code: data.home.shortName },
        away: { ...data.away, code: data.away.shortName },
        lineups: {
            home: lineupRows(data.home.id),
            away: lineupRows(data.away.id),
        },
    };
});
const statusLabel = computed(
    () =>
        ({
            draft: "Draft",
            scheduled: "Pertandingan Mendatang",
            live: "Sedang Berlangsung",
            completed: "Full Time",
            postponed: "Ditunda",
            cancelled: "Dibatalkan",
            walkover: "Walkover",
        })[match.value?.status] || match.value?.status,
);
const pageRoot = ref(null);
</script>

<template>
    <main
        ref="pageRoot"
        class="detail-page"
        :style="{ '--detail-image': `url('${match.image}')` }"
    >
        <section class="match-hero">
            <div class="detail-background" aria-hidden="true"></div>
            <SiteNavbar />
            <div class="hero-body shell">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <Link href="/">Home</Link><span>/</span
                    ><Link href="/jadwal">Jadwal</Link><span>/</span
                    ><span>Detail Pertandingan</span>
                </nav>
                <div class="competition">
                    {{ match.competition
                    }}<template v-if="match.matchday">
                        · Matchday {{ match.matchday }}</template
                    >
                </div>
                <div class="scoreboard" data-reveal>
                    <div class="team home-team">
                        <h1>{{ match.home.name }}</h1>
                        <div class="crest home">
                            <img
                                v-if="match.home.logo"
                                :src="match.home.logo"
                                :alt="`Logo ${match.home.name}`"
                            /><span v-else>{{ match.home.code }}</span>
                        </div>
                    </div>
                    <div class="score-box">
                        <div class="score-time">{{ match.display }}</div>
                        <div v-if="match.penaltyScore" class="penalty-score">
                            Penalti {{ match.penaltyScore }}
                        </div>
                        <div class="score-status">{{ statusLabel }}</div>
                    </div>
                    <div class="team away-team">
                        <div class="crest away">
                            <img
                                v-if="match.away.logo"
                                :src="match.away.logo"
                                :alt="`Logo ${match.away.name}`"
                            /><span v-else>{{ match.away.code }}</span>
                        </div>
                        <h1>{{ match.away.name }}</h1>
                    </div>
                </div>
                <div class="match-facts" data-reveal>
                    <div class="fact">
                        <div class="fact-label">Tanggal</div>
                        <div class="fact-value">{{ match.date }}</div>
                    </div>
                    <div class="fact">
                        <div class="fact-label">Kick Off</div>
                        <div class="fact-value">{{ match.time }}</div>
                    </div>
                    <div class="fact">
                        <div class="fact-label">Venue</div>
                        <div class="fact-value">
                            {{ match.venue }}<br />{{ match.city }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="detail-content">
            <div class="content-grid shell">
                <article v-if="awardData" class="panel potm" data-reveal>
                    <img
                        :src="awardData.player.image"
                        :alt="awardData.player.name"
                    />
                    <div>
                        <small>{{ awardData.title || awardData.typeName || "Player of the Match" }}</small>
                        <h2>{{ awardData.player.name }}</h2>
                        <p>{{ awardData.editorial }}</p>
                        <div class="potm-stats">
                            <span><b>{{ awardData.statistics?.goals ?? 0 }}</b> Gol</span>
                            <span><b>{{ awardData.statistics?.assists ?? 0 }}</b> Assist</span>
                            <span v-if="awardData.statistics?.rating"><b>{{ awardData.statistics.rating }}</b> Rating</span>
                        </div>
                    </div>
                    <strong>★</strong>
                </article>
                <article
                    v-if="match.events?.length"
                    class="panel timeline-panel"
                    data-reveal
                >
                    <header class="panel-head">
                        <h2>Match Timeline</h2>
                        <span>{{ match.events.length }} Events</span>
                    </header>
                    <div class="public-timeline">
                        <div
                            v-for="event in match.events"
                            :key="event.id"
                            class="timeline-event"
                        >
                            <b>{{ event.minute_label }}</b
                            ><span class="event-icon">{{
                                ["goal", "penalty_goal", "own_goal"].includes(
                                    event.type,
                                )
                                    ? "⚽"
                                    : ["yellow_card", "second_yellow"].includes(
                                            event.type,
                                        )
                                      ? "▨"
                                      : event.type === "red_card"
                                        ? "■"
                                        : event.type === "substitution"
                                          ? "↕"
                                          : "•"
                            }}</span>
                            <div>
                                <strong>{{
                                    event.type.replaceAll("_", " ")
                                }}</strong>
                                <p>
                                    {{ event.primary_name
                                    }}<template v-if="event.secondary_name">
                                        ·
                                        {{
                                            event.type === "substitution"
                                                ? "Masuk: "
                                                : "Assist: "
                                        }}{{ event.secondary_name }}</template
                                    >
                                </p>
                            </div>
                            <small>{{ event.club_name }}</small>
                        </div>
                    </div>
                </article>
                <article class="panel" data-reveal>
                    <header class="panel-head">
                        <h2>Susunan Pemain</h2>
                        <span>Match Preview</span>
                    </header>
                    <div class="lineups">
                        <div
                            v-for="side in ['home', 'away']"
                            :key="side"
                            class="lineup"
                        >
                            <h3>{{ match[side].name }}</h3>
                            <div class="players">
                                <div
                                    v-for="(player, index) in match.lineups[
                                        side
                                    ]"
                                    :key="
                                        typeof player === 'string'
                                            ? player
                                            : player.id
                                    "
                                    class="player"
                                >
                                    <span>{{
                                        typeof player === "string"
                                            ? String(index + 1).padStart(2, "0")
                                            : player.number ||
                                              String(index + 1).padStart(2, "0")
                                    }}</span
                                    ><strong
                                        >{{
                                            typeof player === "string"
                                                ? player
                                                : player.name
                                        }}<i
                                            v-if="
                                                typeof player !== 'string' &&
                                                player.captain
                                            "
                                            >Kapten</i
                                        ></strong
                                    ><small>{{
                                        typeof player === "string"
                                            ? "Pemain inti"
                                            : [player.label, player.position]
                                                  .filter(Boolean)
                                                  .join(" · ")
                                    }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
                <aside class="side-stack" data-reveal>
                    <div class="venue-card">
                        <div class="venue-photo"></div>
                        <div class="venue-copy">
                            <small>Match Venue</small>
                            <h2>{{ match.venue }}</h2>
                            <p>
                                {{ match.city }} · Informasi lapangan dan
                                petunjuk lokasi dapat dilengkapi melalui
                                dashboard.
                            </p>
                        </div>
                    </div>
                    <Link class="back-link" href="/jadwal"
                        ><svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <path d="M5 12h14M13 6l6 6-6 6" /></svg
                        >Kembali ke Jadwal</Link
                    >
                </aside>
            </div>
        </section>
        <SiteFooter />
    </main>
</template>

<style scoped>
.detail-page {
    min-height: 100svh;
    background: var(--color-surface);
}
.match-hero {
    position: relative;
    min-height: 72svh;
    isolation: isolate;
    overflow: hidden;
    border-bottom: 1px solid var(--color-line);
}
.detail-background {
    position: absolute;
    inset: 0;
    z-index: -2;
    background:
        linear-gradient(
            90deg,
            rgba(5, 9, 15, 0.92),
            rgba(5, 9, 15, 0.45),
            rgba(5, 9, 15, 0.84)
        ),
        linear-gradient(0deg, rgba(5, 9, 15, 0.94), transparent 55%),
        var(--detail-image) center/cover;
    filter: saturate(0.55) contrast(1.12);
}
.shell {
    width: min(calc(100% - 80px), var(--content-max));
    margin-inline: auto;
}
.hero-body {
    min-height: calc(72svh - 108px);
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 48px 0 70px;
}
.breadcrumb {
    display: flex;
    gap: 10px;
    margin-bottom: 27px;
    color: #aebac5;
    font: 700 0.68rem var(--font-display);
    letter-spacing: 0.15em;
    text-transform: uppercase;
}
.breadcrumb a {
    color: inherit;
    text-decoration: none;
}
.breadcrumb a:hover,
.breadcrumb span:last-child {
    color: var(--color-accent);
}
.competition {
    text-align: center;
    color: var(--color-accent);
    font: 800 0.72rem var(--font-display);
    letter-spacing: 0.22em;
    text-transform: uppercase;
}
.scoreboard {
    display: grid;
    grid-template-columns: 1fr 180px 1fr;
    align-items: center;
    gap: 45px;
    width: 100%;
    max-width: 1040px;
    margin: 32px auto 0;
}
.team {
    display: flex;
    align-items: center;
    gap: 23px;
}
.away-team {
    justify-content: flex-end;
}
.team h1 {
    margin: 0;
    color: var(--color-paper);
    font: 900 clamp(3.2rem, 6vw, 6rem) / 0.82 var(--font-display);
    letter-spacing: -0.05em;
    text-transform: uppercase;
}
.away-team h1 {
    font-style: italic;
}
.crest {
    position: relative;
    display: grid;
    flex: 0 0 106px;
    width: 106px;
    height: 106px;
    place-items: center;
    border: 2px solid rgba(255, 255, 255, 0.72);
    font: 900 1.55rem var(--font-display);
}
.crest img {
    width: 70%;
    height: 70%;
    border-radius: 50%;
    object-fit: contain;
}
.crest.home {
    border-radius: 50%;
}
.crest.home::before,
.crest.home::after {
    content: "";
    position: absolute;
    inset: 10px -11px;
    border: 1px solid rgba(255, 255, 255, 0.32);
    border-radius: 50%;
    transform: rotate(28deg);
}
.crest.home::after {
    transform: rotate(-28deg);
}
.crest.away {
    border-radius: 9px 9px 40px 40px;
}
.score-box {
    text-align: center;
}
.score-time {
    font: 900 clamp(2.7rem, 5vw, 4.8rem) / 0.85 var(--font-display);
}
.penalty-score {
    margin-top: 10px;
    color: var(--color-muted);
    font: 700 0.72rem var(--font-display);
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.score-status {
    display: inline-block;
    margin-top: 14px;
    padding: 6px 12px;
    border: 1px solid rgba(69, 183, 255, 0.4);
    border-radius: 30px;
    color: var(--color-accent);
    font: 700 0.6rem var(--font-display);
    letter-spacing: 0.13em;
    text-transform: uppercase;
}
.match-facts {
    display: flex;
    justify-content: center;
    margin: 38px auto 0;
}
.fact {
    min-width: 180px;
    padding: 0 30px;
    border-right: 1px solid var(--color-line);
    text-align: center;
}
.fact:last-child {
    border: 0;
}
.fact-label {
    margin-bottom: 7px;
    color: #8996a1;
    font: 700 0.58rem var(--font-display);
    letter-spacing: 0.16em;
    text-transform: uppercase;
}
.fact-value {
    font: 700 0.78rem/1.4 var(--font-display);
    letter-spacing: 0.06em;
    text-transform: uppercase;
}
.detail-content {
    padding: 58px 0 75px;
}
.content-grid {
    display: grid;
    grid-template-columns: 1.3fr 0.7fr;
    gap: 28px;
}
.panel,
.venue-card {
    border: 1px solid var(--color-line);
    background: rgba(255, 255, 255, 0.018);
}
.panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 19px 22px;
    border-bottom: 1px solid var(--color-line);
}
.panel-head h2 {
    margin: 0;
    font: 900 1.65rem var(--font-display);
    text-transform: uppercase;
}
.panel-head span,
.venue-copy small {
    color: var(--color-accent);
    font: 700 0.6rem var(--font-display);
    letter-spacing: 0.15em;
    text-transform: uppercase;
}
.lineups {
    display: grid;
    grid-template-columns: 1fr 1fr;
}
.lineup {
    padding: 22px;
}
.lineup:first-child {
    border-right: 1px solid var(--color-line);
}
.lineup h3 {
    margin: 0 0 17px;
    font: 800 0.74rem var(--font-display);
    letter-spacing: 0.14em;
    text-transform: uppercase;
}
.players {
    display: grid;
    gap: 9px;
}
.player {
    display: grid;
    grid-template-columns: 29px 1fr auto;
    align-items: center;
    gap: 9px;
    padding-bottom: 9px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.07);
    font: 650 0.71rem var(--font-display);
    letter-spacing: 0.05em;
    text-transform: uppercase;
}
.player > span {
    color: var(--color-accent);
    font-weight: 800;
}
.player strong i {
    display: inline-block;
    margin-left: 7px;
    padding: 2px 5px;
    border: 1px solid rgba(69, 183, 255, 0.35);
    color: var(--color-accent);
    font-size: 0.46rem;
    font-style: normal;
}
.player small {
    color: #727f8a;
    font-size: 0.58rem;
}
.side-stack {
    display: grid;
    gap: 20px;
}
.venue-photo {
    min-height: 200px;
    background:
        linear-gradient(0deg, rgba(5, 9, 15, 0.7), transparent),
        var(--detail-image) center/cover;
}
.venue-copy {
    padding: 20px 22px;
}
.venue-copy h2 {
    margin: 7px 0;
    font: 900 1.7rem var(--font-display);
    text-transform: uppercase;
}
.venue-copy p {
    margin: 0;
    color: #aebac5;
    font-size: 0.75rem;
    line-height: 1.55;
}
.back-link {
    display: flex;
    min-height: 52px;
    align-items: center;
    justify-content: center;
    gap: 12px;
    border: 1px solid var(--color-accent);
    color: #04101c;
    background: var(--color-accent);
    font: 800 0.72rem var(--font-display);
    letter-spacing: 0.1em;
    text-decoration: none;
    text-transform: uppercase;
    transition: 0.2s;
}
.back-link:hover {
    color: white;
    background: var(--color-accent-hover);
    transform: translateY(-2px);
}
.back-link svg {
    width: 18px;
    stroke-width: 2;
    transform: rotate(180deg);
}
@media (max-width: 900px) {
    .shell {
        width: min(calc(100% - 40px), var(--content-max));
    }
    .scoreboard {
        grid-template-columns: 1fr 120px 1fr;
        gap: 18px;
    }
    .team {
        flex-direction: column;
        text-align: center;
    }
    .away-team {
        flex-direction: column-reverse;
    }
    .crest {
        flex-basis: 80px;
        width: 80px;
        height: 80px;
    }
    .match-facts {
        flex-wrap: wrap;
        row-gap: 25px;
    }
    .content-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 520px) {
    .shell {
        width: calc(100% - 32px);
    }
    .match-hero {
        min-height: 100svh;
    }
    .hero-body {
        min-height: calc(100svh - 78px);
    }
    .scoreboard {
        grid-template-columns: 1fr 65px 1fr;
        gap: 8px;
    }
    .crest {
        flex-basis: 58px;
        width: 58px;
        height: 58px;
        font-size: 0.9rem;
    }
    .team h1 {
        font-size: 2.2rem;
    }
    .score-time {
        font-size: 2rem;
    }
    .match-facts {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }
    .fact {
        min-width: 0;
        padding: 0 10px;
    }
    .fact:nth-child(2) {
        border: 0;
    }
    .content-grid {
        grid-template-columns: 1fr;
    }
    .lineups {
        grid-template-columns: 1fr;
    }
    .lineup:first-child {
        border-right: 0;
        border-bottom: 1px solid var(--color-line);
    }
}
.timeline-panel {
    grid-column: 1/-1;
}
.public-timeline {
    display: grid;
    grid-template-columns: 1fr 1fr;
    padding: 10px 22px 20px;
}
.timeline-event {
    display: grid;
    grid-template-columns: 48px 28px 1fr auto;
    align-items: center;
    gap: 9px;
    padding: 13px 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.timeline-event > b {
    color: var(--color-accent);
    font: 900 1.1rem var(--font-display);
}
.timeline-event strong {
    font: 800 0.7rem var(--font-display);
    text-transform: uppercase;
}
.timeline-event p {
    margin: 3px 0 0;
    color: #aebac5;
    font-size: 0.72rem;
}
.timeline-event small {
    color: #73818d;
    font: 700 0.55rem var(--font-display);
    text-transform: uppercase;
}
.event-icon {
    color: var(--color-accent);
}
@media (max-width: 520px) {
    .public-timeline {
        grid-template-columns: 1fr;
        padding-inline: 14px;
    }
    .timeline-event {
        grid-template-columns: 42px 24px 1fr;
    }
    .timeline-event small {
        grid-column: 3;
    }
}
.potm {
    grid-column: 1/-1;
    display: grid;
    grid-template-columns: 110px 1fr auto;
    align-items: center;
    gap: 22px;
    padding: 16px 22px;
}
.potm img {
    width: 110px;
    height: 110px;
    object-fit: cover;
}
.potm small {
    color: var(--color-accent);
    font: 800 0.6rem var(--font-display);
    letter-spacing: 0.15em;
    text-transform: uppercase;
}
.potm h2 {
    margin: 6px 0;
    font: 900 2rem var(--font-display);
    text-transform: uppercase;
}
.potm p {
    margin: 0;
    color: #aebac5;
}
.potm-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}
.potm-stats span {
    padding: 6px 9px;
    border: 1px solid rgba(69, 183, 255, 0.28);
    color: #93a5b2;
    font: 700 0.58rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.potm-stats b {
    margin-right: 4px;
    color: var(--color-accent);
    font-size: 0.76rem;
}
.potm > strong {
    color: var(--color-accent);
    font-size: 3rem;
}
@media (max-width: 520px) {
    .potm {
        grid-template-columns: 75px 1fr;
    }
    .potm img {
        width: 75px;
        height: 75px;
    }
    .potm > strong {
        display: none;
    }
}
</style>
