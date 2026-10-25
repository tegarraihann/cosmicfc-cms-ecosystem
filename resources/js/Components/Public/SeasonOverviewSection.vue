<script setup>
import { ref } from "vue";

defineProps({
    stats: {
        type: Array,
        default: () => [],
    },
    topScorer: {
        type: Object,
        default: null,
    },
});

const sectionRoot = ref(null);
</script>

<template>
    <section
        ref="sectionRoot"
        class="season-overview"
        aria-labelledby="overview-title"
    >
        <div class="shell">
            <header class="overview-heading" data-reveal>
                <div class="overview-title-wrap">
                    <h2 id="overview-title">Ringkasan Musim</h2>
                    <span aria-hidden="true">→</span>
                </div>
                <p>
                    Angka utama Pekanbaru Mini Soccer League hingga matchday
                    kedelapan.
                </p>
            </header>

            <div class="overview-grid">
                <article
                    v-for="stat in stats"
                    :key="stat.label"
                    :class="['stat-card', { accent: stat.accent }]"
                    data-reveal
                >
                    <div class="stat-icon" aria-hidden="true">
                        <svg
                            v-if="stat.icon === 'teams'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <path
                                d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"
                            />
                        </svg>
                        <svg
                            v-else-if="stat.icon === 'ball'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <circle cx="12" cy="12" r="9" />
                            <path
                                d="m8 4 4 3 4-3M7 14l-4-2M17 14l4-2M9 20l3-4 3 4"
                            />
                        </svg>
                        <svg
                            v-else-if="stat.icon === 'trophy'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <path
                                d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4Z"
                            />
                            <path
                                d="M7 6H4v2a4 4 0 0 0 4 4M17 6h3v2a4 4 0 0 1-4 4"
                            />
                        </svg>
                        <svg
                            v-else
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <rect x="3" y="5" width="18" height="16" rx="2" />
                            <path d="M16 3v4M8 3v4M3 10h18" />
                        </svg>
                    </div>
                    <div>
                        <div class="stat-value">
                            {{ stat.value
                            }}<small v-if="stat.suffix">{{
                                stat.suffix
                            }}</small>
                        </div>
                        <div class="stat-label">{{ stat.label }}</div>
                    </div>
                </article>
            </div>

            <article
                v-if="topScorer"
                class="season-feature"
                :style="{
                    '--feature-image': `url('${topScorer.image || ''}')`,
                }"
                data-reveal
            >
                <div
                    class="feature-photo"
                    role="img"
                    :aria-label="`Dokumentasi ${topScorer.name} di lapangan`"
                ></div>
                <div class="feature-copy">
                    <div class="feature-kicker">Top Scorer Sementara</div>
                    <h3>{{ topScorer.name }}</h3>
                    <div class="feature-info">
                        {{ topScorer.club }} · {{ topScorer.position }} · #{{
                            topScorer.number
                        }}
                    </div>
                </div>
                <div class="feature-score">
                    {{ topScorer.goals }}<small>Gol</small>
                </div>
            </article>
        </div>
    </section>
</template>

<style scoped>
.season-overview {
    padding: 53px 0 64px;
    border-bottom: 1px solid var(--color-line);
    background: radial-gradient(
            circle at 85% 15%,
            rgba(69, 183, 255, 0.05),
            transparent 23%
        ),
        var(--color-surface);
}
.shell {
    width: min(calc(100% - 80px), var(--content-max));
    margin-inline: auto;
}
.overview-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 30px;
    margin-bottom: 27px;
}
.overview-title-wrap {
    display: flex;
    align-items: center;
    gap: 18px;
}
.overview-title-wrap h2 {
    margin: 0;
    font: 900 clamp(2.9rem, 4.7vw, 4.9rem) / 0.88 var(--font-display);
    letter-spacing: -0.045em;
    text-transform: uppercase;
}
.overview-title-wrap span {
    color: var(--color-accent);
    font: 300 3.4rem/1 var(--font-display);
    filter: drop-shadow(0 0 7px rgba(69, 183, 255, 0.5));
    transform: rotate(-42deg);
}
.overview-heading p {
    max-width: 310px;
    margin: 0;
    color: #8f9ca7;
    font-size: 0.76rem;
    line-height: 1.55;
    text-align: right;
}
.overview-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 12px;
}
.stat-card {
    position: relative;
    min-height: 185px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 22px;
    overflow: hidden;
    border: 1px solid rgba(175, 218, 255, 0.14);
    background: rgba(255, 255, 255, 0.018);
    transition:
        border-color 0.25s,
        background 0.25s,
        transform 0.25s;
}
.stat-card:hover {
    border-color: rgba(69, 183, 255, 0.48);
    background: rgba(69, 183, 255, 0.055);
    transform: translateY(-4px);
}
.stat-card::after {
    content: "";
    position: absolute;
    right: -18px;
    bottom: -34px;
    width: 105px;
    height: 105px;
    border: 1px solid rgba(255, 255, 255, 0.055);
    border-radius: 50%;
}
.stat-icon {
    width: 35px;
    height: 35px;
    color: #9cabb7;
}
.stat-icon svg {
    width: 100%;
    height: 100%;
    stroke-width: 1.6;
}
.stat-value {
    position: relative;
    z-index: 1;
    color: var(--color-paper);
    font: 900 clamp(3.4rem, 5vw, 5.2rem) / 0.76 var(--font-display);
    letter-spacing: -0.045em;
}
.stat-value small {
    margin-left: 5px;
    color: var(--color-accent);
    font-size: 1rem;
    letter-spacing: 0;
}
.stat-label {
    position: relative;
    z-index: 1;
    margin-top: 10px;
    color: #aebac4;
    font: 700 0.65rem var(--font-display);
    letter-spacing: 0.15em;
    text-transform: uppercase;
}
.stat-card.accent {
    border-color: rgba(69, 183, 255, 0.32);
    background: linear-gradient(
        145deg,
        rgba(69, 183, 255, 0.16),
        rgba(69, 183, 255, 0.025)
    );
}
.stat-card.accent .stat-value {
    color: var(--color-accent);
}
.season-feature {
    position: relative;
    min-height: 240px;
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    margin-top: 12px;
    overflow: hidden;
    border: 1px solid rgba(175, 218, 255, 0.15);
    background: #09131d;
    isolation: isolate;
}
.feature-copy {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 30px;
}
.feature-kicker {
    margin-bottom: 9px;
    color: var(--color-accent);
    font: 800 0.63rem var(--font-display);
    letter-spacing: 0.18em;
    text-transform: uppercase;
}
.feature-copy h3 {
    max-width: 520px;
    margin: 0;
    font: 900 clamp(2.8rem, 4.2vw, 4.6rem) / 0.82 var(--font-display);
    letter-spacing: -0.035em;
    text-transform: uppercase;
}
.feature-info {
    margin-top: 14px;
    color: #b2bec8;
    font: 600 0.72rem var(--font-display);
    letter-spacing: 0.07em;
    text-transform: uppercase;
}
.feature-score {
    position: absolute;
    right: 33%;
    bottom: 20px;
    z-index: 3;
    color: var(--color-accent);
    font: 900 5rem/0.8 var(--font-display);
    text-shadow: 0 5px 20px #05090f;
}
.feature-score small {
    display: block;
    margin-top: 8px;
    color: #c5d0d9;
    font-size: 0.6rem;
    letter-spacing: 0.16em;
    text-align: center;
    text-transform: uppercase;
}
.feature-photo {
    position: absolute;
    inset: 0 0 0 52%;
    z-index: -1;
    background:
        linear-gradient(
            90deg,
            #09131d 0%,
            rgba(9, 19, 29, 0.18) 43%,
            transparent
        ),
        linear-gradient(0deg, rgba(5, 9, 15, 0.55), transparent),
        var(--feature-image) center/cover no-repeat;
    filter: saturate(0.68) contrast(1.08);
    transition: transform 0.6s;
}
.season-feature:hover .feature-photo {
    transform: scale(1.025);
}
@media (max-width: 900px) {
    .shell {
        width: min(calc(100% - 40px), var(--content-max));
    }
    .overview-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .season-feature {
        grid-template-columns: 1fr;
    }
    .feature-photo {
        inset: 0 0 0 43%;
    }
    .feature-score {
        right: 25px;
    }
}
@media (max-width: 520px) {
    .season-overview {
        padding: 42px 0 50px;
    }
    .shell {
        width: calc(100% - 32px);
    }
    .overview-heading {
        align-items: flex-start;
        flex-direction: column;
    }
    .overview-heading p {
        text-align: left;
    }
    .overview-grid {
        grid-template-columns: 1fr 1fr;
        gap: 8px;
    }
    .stat-card {
        min-height: 160px;
        padding: 17px;
    }
    .stat-value {
        font-size: 3.2rem;
    }
    .season-feature {
        min-height: 370px;
        align-items: flex-end;
    }
    .feature-copy {
        justify-content: flex-end;
        padding: 22px;
    }
    .feature-photo {
        inset: 0;
        background:
            linear-gradient(
                0deg,
                rgba(7, 16, 25, 0.98) 0%,
                rgba(7, 16, 25, 0.54) 57%,
                rgba(7, 16, 25, 0.1)
            ),
            var(--feature-image) center/cover no-repeat;
    }
    .feature-score {
        top: 20px;
        right: 20px;
        bottom: auto;
        font-size: 4rem;
    }
}
</style>
