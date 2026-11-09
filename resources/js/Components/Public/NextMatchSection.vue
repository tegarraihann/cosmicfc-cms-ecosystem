<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    label: { type: String, default: "Next Match" },
    emptyTitle: {
        type: String,
        default: "Pertandingan berikutnya belum dijadwalkan.",
    },
    emptyDescription: {
        type: String,
        default: "Informasi jadwal terbaru akan ditampilkan di sini.",
    },
    homeTeam: { type: String, default: "" },
    homeLogo: { type: String, default: null },
    awayTeam: { type: String, default: "" },
    awayLogo: { type: String, default: null },
    awayCode: { type: String, default: "" },
    date: { type: String, default: "" },
    time: { type: String, default: "" },
    venue: { type: String, default: "" },
    city: { type: String, default: "" },
    detailHref: { type: String, default: "" },
    backgroundImage: { type: String, default: "" },
});

const hasMatch = computed(() => Boolean(props.homeTeam || props.awayTeam));
const backgroundStyle = computed(() =>
    props.backgroundImage
        ? { "--match-image": `url("${props.backgroundImage}")` }
        : {},
);
const teamNameClass = (name) => {
    const length = String(name || "").trim().length;

    return {
        "is-medium": length > 11,
        "is-long": length > 19,
        "is-extra-long": length > 29,
    };
};
</script>

<template>
    <section
        id="next-match"
        class="next-match"
        :style="backgroundStyle"
        aria-labelledby="next-match-title"
    >
        <div class="match-background" aria-hidden="true"></div>
        <div v-if="hasMatch" class="match-layout shell">
            <div class="match-main">
                <div id="next-match-title" class="section-label">
                    {{ label }}
                </div>
                <div class="teams">
                    <div class="team team-home">
                        <h2
                            class="team-name"
                            :class="teamNameClass(homeTeam)"
                        >
                            {{ homeTeam || "—" }}
                        </h2>
                        <div
                            class="crest crest-cosmic"
                            :aria-label="`Logo ${homeTeam}`"
                        >
                            <img v-if="homeLogo" :src="homeLogo" alt="" loading="lazy" decoding="async" />
                            <span v-else aria-hidden="true">—</span>
                        </div>
                    </div>

                    <div class="versus" aria-label="melawan">VS</div>

                    <div class="team team-away">
                        <div
                            class="crest crest-rivals"
                            :aria-label="`Logo ${awayTeam}`"
                        >
                            <img v-if="awayLogo" :src="awayLogo" alt="" loading="lazy" decoding="async" />
                            <span v-else>{{ awayCode || "—" }}</span>
                        </div>
                        <h2
                            class="team-name"
                            :class="teamNameClass(awayTeam)"
                        >
                            {{ awayTeam || "—" }}
                        </h2>
                    </div>
                </div>
            </div>

            <div class="match-meta" aria-label="Informasi pertandingan">
                <div class="meta-row">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <rect x="3" y="5" width="18" height="16" rx="2" />
                        <path d="M16 3v4M8 3v4M3 10h18" />
                    </svg>
                    <span>{{ date || "—" }}</span>
                </div>
                <div class="meta-row">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 7v5l3 2" />
                    </svg>
                    <span>{{ time || "—" }}</span>
                </div>
                <div class="meta-row">
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"
                        />
                        <circle cx="12" cy="10" r="2.5" />
                    </svg>
                    <span>{{ venue || "—" }}<br v-if="city" />{{ city }}</span>
                </div>
            </div>

            <div class="match-cta">
                <Link v-if="detailHref" class="detail-button" :href="detailHref">
                    Lihat Detail
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </Link>
                <span v-else class="detail-button is-disabled" aria-disabled="true">
                    {{ hasMatch ? "Detail belum tersedia" : "Belum dijadwalkan" }}
                </span>
            </div>
        </div>
        <div v-else class="empty-layout shell">
            <div id="next-match-title" class="section-label">{{ label }}</div>
            <h2>{{ emptyTitle }}</h2>
            <p v-if="emptyDescription">{{ emptyDescription }}</p>
        </div>
    </section>
</template>

<style scoped>
.next-match {
    position: relative;
    isolation: isolate;
    overflow: hidden;
    border-top: 1px solid var(--color-line);
    border-bottom: 1px solid var(--color-line);
    background: var(--color-surface);
}
.match-background {
    position: absolute;
    inset: -5% -3%;
    z-index: -2;
    background:
        linear-gradient(
            90deg,
            rgba(4, 10, 17, 0.99) 0%,
            rgba(4, 10, 17, 0.98) 26%,
            rgba(4, 10, 17, 0.92) 44%,
            rgba(4, 10, 17, 0.72) 59%,
            rgba(4, 10, 17, 0.4) 76%,
            rgba(4, 10, 17, 0.18) 100%
        ),
        linear-gradient(
            0deg,
            rgba(4, 10, 17, 0.82),
            transparent 48%,
            rgba(4, 10, 17, 0.5)
        ),
        var(--match-image) center 58% / cover no-repeat;
    filter: saturate(0.52) contrast(1.12);
}
.next-match::after {
    content: "";
    position: absolute;
    inset: 0;
    z-index: -1;
    pointer-events: none;
    opacity: 0.18;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='3' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.22'/%3E%3C/svg%3E");
    mix-blend-mode: soft-light;
}
.shell {
    width: min(calc(100% - 80px), var(--content-max));
    margin-inline: auto;
}
.match-layout {
    min-height: 205px;
    display: grid;
    grid-template-columns: minmax(480px, 1.5fr) minmax(300px, 0.8fr) minmax(
            190px,
            0.52fr
        );
    align-items: center;
    gap: 34px;
    padding-block: 28px;
}
.empty-layout {
    min-height: 205px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding-block: 42px;
}
.empty-layout .section-label {
    margin-bottom: 13px;
}
.empty-layout h2 {
    max-width: 760px;
    margin: 0;
    color: var(--color-paper);
    font: 900 clamp(2rem, 4vw, 4rem) / 0.92 var(--font-display);
    letter-spacing: -0.035em;
    text-transform: uppercase;
}
.empty-layout p {
    max-width: 620px;
    margin: 14px 0 0;
    color: #9baab6;
    font-size: 0.9rem;
    line-height: 1.6;
}
.match-main {
    min-width: 0;
}
.section-label {
    margin-bottom: 14px;
    color: var(--color-accent);
    font: 800 0.75rem var(--font-display);
    letter-spacing: 0.2em;
    text-transform: uppercase;
}
.teams {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
    align-items: center;
    gap: 14px;
}
.team {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 13px;
}
.team-home .team-name {
    text-align: right;
}
.team-away {
    justify-content: flex-end;
}
.crest {
    position: relative;
    display: grid;
    flex: 0 0 74px;
    width: 74px;
    height: 74px;
    place-items: center;
    border: 2px solid rgba(255, 255, 255, 0.74);
    color: white;
    font: 900 1.25rem var(--font-display);
    letter-spacing: -0.04em;
}
.crest img {
    width: 68%;
    height: 68%;
    border-radius: 50%;
    object-fit: contain;
}
.crest-cosmic {
    border-radius: 50%;
}
.crest-cosmic::before,
.crest-cosmic::after {
    content: "";
    position: absolute;
    inset: 7px -8px;
    border: 1px solid rgba(255, 255, 255, 0.36);
    border-radius: 50%;
    transform: rotate(28deg);
}
.crest-cosmic::after {
    transform: rotate(-28deg);
}
.crest-rivals {
    height: 80px;
    border-radius: 8px 8px 30px 30px;
    background: rgba(255, 255, 255, 0.06);
}
.team-name {
    min-width: 0;
    flex: 1 1 auto;
    margin: 0;
    color: var(--color-paper);
    font: 900 clamp(2rem, 3.4vw, 4rem) / 0.88 var(--font-display);
    letter-spacing: -0.045em;
    text-transform: uppercase;
    text-wrap: balance;
    overflow-wrap: break-word;
    word-break: normal;
}
.team-name.is-medium {
    font-size: clamp(1.65rem, 2.7vw, 3.1rem);
    line-height: 0.9;
}
.team-name.is-long {
    font-size: clamp(1.35rem, 2.05vw, 2.35rem);
    line-height: 0.94;
    letter-spacing: -0.025em;
}
.team-name.is-extra-long {
    font-size: clamp(1.15rem, 1.75vw, 2rem);
    line-height: 0.98;
}
.team-away .team-name {
    font-style: italic;
}
.versus {
    color: rgba(255, 255, 255, 0.8);
    font: italic 800 2rem var(--font-display);
}
.match-meta {
    display: grid;
    gap: 13px;
    padding: 8px 0 8px 35px;
    border-left: 1px solid rgba(255, 255, 255, 0.18);
}
.meta-row {
    display: grid;
    grid-template-columns: 22px 1fr;
    align-items: start;
    gap: 12px;
    color: #edf3f8;
    font: 600 0.84rem/1.45 var(--font-display);
    letter-spacing: 0.035em;
    text-transform: uppercase;
}
.meta-row svg {
    width: 18px;
    height: 18px;
    color: rgba(255, 255, 255, 0.76);
    stroke-width: 1.8;
}
.match-cta {
    align-self: end;
    justify-self: end;
    padding-bottom: 12px;
}
.detail-button {
    display: inline-flex;
    min-height: 48px;
    align-items: center;
    justify-content: center;
    gap: 15px;
    padding: 0 22px;
    border: 1px solid var(--color-accent);
    border-radius: 7px;
    color: #04101c;
    background: var(--color-accent);
    box-shadow: 0 0 26px rgba(69, 183, 255, 0.16);
    font: 800 0.78rem var(--font-display);
    letter-spacing: 0.04em;
    text-decoration: none;
    text-transform: uppercase;
    transition: 0.2s;
}
.detail-button:hover {
    color: var(--color-paper);
    border-color: var(--color-accent-hover);
    background: var(--color-accent-hover);
    box-shadow: 0 0 32px rgba(69, 183, 255, 0.28);
    transform: translateY(-3px);
}
.detail-button.is-disabled {
    cursor: default;
    color: #71808c;
    border-color: rgba(175, 218, 255, 0.18);
    background: rgba(255, 255, 255, 0.035);
    box-shadow: none;
}
.detail-button.is-disabled:hover { transform: none; }
.detail-button svg {
    width: 19px;
    stroke-width: 2;
}

@media (max-width: 1100px) {
    .match-layout {
        grid-template-columns: 1fr 1fr;
        gap: 26px;
        padding-block: 34px;
    }
    .empty-layout {
        min-height: 230px;
        padding-block: 36px;
    }
    .match-main {
        grid-column: 1/-1;
    }
    .match-meta {
        padding-left: 0;
        border-left: 0;
    }
    .team-name {
        font-size: clamp(2rem, 5vw, 3.6rem);
    }
    .team-name.is-medium {
        font-size: clamp(1.65rem, 4vw, 2.8rem);
    }
    .team-name.is-long {
        font-size: clamp(1.35rem, 3.2vw, 2.2rem);
    }
    .team-name.is-extra-long {
        font-size: clamp(1.15rem, 2.7vw, 1.8rem);
    }
}

@media (max-width: 520px) {
    .shell {
        width: calc(100% - 32px);
    }
    .match-background {
        opacity: 0.65;
        background:
            linear-gradient(
                180deg,
                #07111e 0%,
                #07111e 42%,
                rgba(7, 17, 30, 0.9) 56%,
                rgba(7, 17, 30, 0.42) 78%,
                rgba(7, 17, 30, 0.78) 100%
            ),
            var(--match-image) center bottom/auto 58% no-repeat;
    }
    .match-layout {
        grid-template-columns: 1fr;
        gap: 27px;
        padding-block: 30px 36px;
    }
    .match-main {
        grid-column: auto;
    }
    .teams {
        gap: 9px;
    }
    .team {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    .team-away {
        flex-direction: column-reverse;
    }
    .crest {
        flex-basis: 58px;
        width: 58px;
        height: 58px;
        font-size: 1rem;
    }
    .crest-rivals {
        height: 64px;
    }
    .team-name {
        font-size: clamp(1.35rem, 7vw, 2.25rem);
        line-height: 0.92;
    }
    .team-name.is-medium {
        font-size: clamp(1.15rem, 5.8vw, 1.8rem);
    }
    .team-name.is-long,
    .team-name.is-extra-long {
        font-size: clamp(0.92rem, 4.7vw, 1.45rem);
        line-height: 1;
    }
    .versus {
        font-size: 1.4rem;
    }
    .match-meta {
        gap: 10px;
    }
    .match-cta {
        justify-self: stretch;
        padding: 0;
    }
    .detail-button {
        width: 100%;
    }
}
</style>
