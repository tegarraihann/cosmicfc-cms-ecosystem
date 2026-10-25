<script setup>
import { computed, ref } from "vue";
const props = defineProps({ player: { type: Object, default: null } });
const player = computed(() => props.player || {
    period: 'Belum ditentukan', image: null, number: '—', name: 'Belum ditentukan',
    position: '—', awardEditorial: 'Penghargaan akan tampil setelah pemain terbaik dipublikasikan.', monthlyStats: {},
});
const emit = defineEmits(["view-profile"]);
const sectionRoot = ref(null);
const monthlyStats = computed(() => {
    const stats = props.player?.monthlyStats || {};
    return [
        {
            value: String(stats.appearances ?? 0).padStart(2, "0"),
            label: "Laga",
        },
        { value: String(stats.goals ?? 0).padStart(2, "0"), label: "Gol" },
        { value: String(stats.assists ?? 0).padStart(2, "0"), label: "Assist" },
        { value: stats.rating ?? "-", label: "Rating" },
    ];
});
</script>
<template>
    <section
        ref="sectionRoot"
        class="player-month"
        aria-labelledby="month-title"
    >
        <div class="shell">
            <header class="month-heading" data-reveal>
                <h2 id="month-title">Player of the <span>Month.</span></h2>
                <div>{{ player.period || "Oktober 2026" }} · Pilihan Tim</div>
            </header>
            <article class="month-card" data-reveal>
                <div
                    class="month-photo"
                    :style="props.player?.image ? { '--month-image': `url('${props.player.image}')` } : {}"
                    role="img"
                    :aria-label="`${player.name}, Player of the Month`"
                >
                    <span>#{{ player.number }}</span>
                </div>
                <div class="month-copy">
                    <div class="month-kicker">Best Performer</div>
                    <h3>{{ player.name }}</h3>
                    <div class="month-role">
                        #{{ player.number }} · {{ player.position }}
                    </div>
                    <div class="month-stats">
                        <div v-for="stat in monthlyStats" :key="stat.label">
                            <strong>{{ stat.value }}</strong
                            ><span>{{ stat.label }}</span>
                        </div>
                    </div>
                    <p>
                        {{
                            player.awardEditorial ||
                            "Kontribusi konsisten dan energi tinggi sepanjang bulan."
                        }}
                    </p>
                    <button
                        v-if="props.player"
                        type="button"
                        @click="emit('view-profile', props.player)"
                    >
                        Lihat Profil Pemain <span>→</span>
                    </button>
                </div>
            </article>
        </div>
    </section>
</template>
<style scoped>
.player-month {
    padding: 54px 0 68px;
    border-bottom: 1px solid var(--color-line);
    background: radial-gradient(
            circle at 83% 20%,
            rgba(69, 183, 255, 0.07),
            transparent 26%
        ),
        #050b12;
}
.shell {
    width: min(calc(100% - 80px), var(--content-max));
    margin-inline: auto;
}
.month-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 30px;
    margin-bottom: 27px;
}
.month-heading h2 {
    margin: 0;
    font: 900 clamp(2.9rem, 4.8vw, 5rem) / 0.88 var(--font-display);
    letter-spacing: -0.045em;
    text-transform: uppercase;
}
.month-heading h2 span {
    color: var(--color-accent);
}
.month-heading > div {
    color: #8e9ba6;
    font: 700 0.64rem var(--font-display);
    letter-spacing: 0.15em;
    text-transform: uppercase;
}
.month-card {
    min-height: 470px;
    display: grid;
    grid-template-columns: minmax(0, 1.08fr) minmax(360px, 0.92fr);
    overflow: hidden;
    border: 1px solid rgba(175, 218, 255, 0.17);
    background: #08111a;
}
.month-photo {
    position: relative;
    min-height: 470px;
    background:
        linear-gradient(90deg, transparent 53%, #08111a 100%),
        linear-gradient(0deg, rgba(5, 9, 15, 0.72), transparent 58%),
        var(--month-image) center 45% / cover no-repeat;
    filter: saturate(0.68) contrast(1.1);
}
.month-photo span {
    position: absolute;
    bottom: 24px;
    left: 27px;
    font: 900 clamp(5rem, 9vw, 9rem) / 0.72 var(--font-display);
    letter-spacing: -0.07em;
}
.month-copy {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 42px 46px;
}
.month-kicker {
    display: flex;
    align-items: center;
    gap: 11px;
    color: var(--color-accent);
    font: 800 0.64rem var(--font-display);
    letter-spacing: 0.19em;
    text-transform: uppercase;
}
.month-kicker::before {
    content: "";
    width: 35px;
    height: 2px;
    background: var(--color-accent);
}
.month-copy h3 {
    margin: 19px 0 5px;
    font: 900 clamp(3.8rem, 6.4vw, 6.8rem) / 0.76 var(--font-display);
    letter-spacing: -0.05em;
    text-transform: uppercase;
}
.month-role {
    color: #aab6c0;
    font: 700 0.64rem var(--font-display);
    letter-spacing: 0.17em;
    text-transform: uppercase;
}
.month-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    margin: 27px 0 24px;
    border-block: 1px solid rgba(255, 255, 255, 0.13);
}
.month-stats > div {
    padding: 17px 7px;
    text-align: center;
    border-right: 1px solid rgba(255, 255, 255, 0.12);
}
.month-stats > div:last-child {
    border: 0;
}
.month-stats strong {
    display: block;
    color: var(--color-accent);
    font: 900 2rem/0.9 var(--font-display);
}
.month-stats span {
    display: block;
    margin-top: 7px;
    color: #7f8d98;
    font: 650 0.5rem var(--font-display);
    letter-spacing: 0.11em;
    text-transform: uppercase;
}
.month-copy p {
    margin: 0;
    color: #b9c5ce;
    font-size: 0.78rem;
    line-height: 1.65;
}
.month-copy button {
    display: inline-flex;
    width: max-content;
    min-height: 45px;
    align-items: center;
    gap: 20px;
    margin-top: 25px;
    padding: 0 18px;
    border: 0;
    color: #04101c;
    background: var(--color-accent);
    font: 800 0.65rem var(--font-display);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    cursor: pointer;
    transition: 0.22s;
}
.month-copy button:hover {
    color: white;
    background: var(--color-accent-hover);
    transform: translateY(-2px);
}
@media (max-width: 800px) {
    .shell {
        width: calc(100% - 32px);
    }
    .month-heading {
        align-items: flex-start;
        flex-direction: column;
    }
    .month-card {
        grid-template-columns: 1fr;
    }
    .month-photo {
        min-height: 390px;
        background:
            linear-gradient(0deg, #08111a, transparent 45%),
            var(--month-image) center/cover;
    }
    .month-copy {
        padding: 32px 24px;
    }
    .month-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    .month-stats > div:nth-child(2) {
        border-right: 0;
    }
    .month-stats > div:nth-child(-n + 2) {
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    }
}
</style>
