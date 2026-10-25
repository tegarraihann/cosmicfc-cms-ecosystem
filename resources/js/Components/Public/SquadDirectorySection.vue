<script setup>
import { computed, ref } from "vue";
const props = defineProps({ players: { type: Array, default: () => [] } });
const emit = defineEmits(["view-profile"]);
const sectionRoot = ref(null);
const activePosition = ref("All");
const filters = ["All", "Goalkeeper", "Defender", "Midfielder", "Forward"];
const visiblePlayers = computed(() =>
    activePosition.value === "All"
        ? props.players
        : props.players.filter(
              (player) => player.position === activePosition.value,
          ),
);
</script>

<template>
    <section
        ref="sectionRoot"
        class="squad-directory"
        aria-labelledby="directory-title"
    >
        <div class="shell">
            <header class="directory-heading" data-reveal>
                <div class="title-wrap">
                    <h2 id="directory-title">Daftar Pemain</h2>
                    <span>→</span>
                </div>
                <div class="caption">First Team · Musim 2026</div>
            </header>
            <div class="position-filter" data-reveal>
                <div
                    class="filter-buttons"
                    role="group"
                    aria-label="Filter pemain berdasarkan posisi"
                >
                    <button
                        v-for="filter in filters"
                        :key="filter"
                        :class="{ active: activePosition === filter }"
                        type="button"
                        :aria-pressed="activePosition === filter"
                        @click="activePosition = filter"
                    >
                        {{ filter === "All" ? "Semua" : filter }}
                    </button>
                </div>
                <div class="player-count">
                    <strong>{{ visiblePlayers.length }}</strong> pemain
                    ditampilkan
                </div>
            </div>
            <TransitionGroup name="players" tag="div" class="player-grid">
                <article
                    v-for="player in visiblePlayers"
                    :key="player.id"
                    class="player-card"
                    :style="{
                        '--player-image': `url('${player.image}')`,
                        '--player-position': player.imagePosition,
                    }"
                >
                    <div class="player-top">
                        <span>#{{ player.number }}</span
                        ><span v-if="player.captain" class="captain-badge"
                            >Captain</span
                        >
                    </div>
                    <div class="player-info">
                        <h3>{{ player.name }}</h3>
                        <div class="player-role">{{ player.position }}</div>
                        <button
                            class="player-link"
                            type="button"
                            :aria-label="`Lihat profil ${player.name}`"
                            @click="emit('view-profile', player)"
                        >
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                            >
                                <path d="M5 12h14M13 6l6 6-6 6" />
                            </svg>
                        </button>
                    </div>
                </article>
            </TransitionGroup>
            <div v-if="!visiblePlayers.length" class="empty-players">
                Tidak ada pemain pada posisi ini.
            </div>
        </div>
    </section>
</template>

<style scoped>
.squad-directory {
    padding: 54px 0 68px;
    border-bottom: 1px solid var(--color-line);
    background: radial-gradient(
            circle at 87% 8%,
            rgba(69, 183, 255, 0.04),
            transparent 23%
        ),
        var(--color-surface);
}
.shell {
    width: min(calc(100% - 80px), var(--content-max));
    margin-inline: auto;
}
.directory-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 30px;
    margin-bottom: 26px;
}
.title-wrap {
    display: flex;
    align-items: center;
    gap: 18px;
}
.title-wrap h2 {
    margin: 0;
    font: 900 clamp(2.9rem, 4.8vw, 5rem) / 0.88 var(--font-display);
    letter-spacing: -0.045em;
    text-transform: uppercase;
}
.title-wrap > span {
    color: var(--color-accent);
    font: 300 3.5rem/1 var(--font-display);
    transform: rotate(-42deg);
}
.caption {
    color: #8e9ba6;
    font: 650 0.64rem var(--font-display);
    letter-spacing: 0.14em;
    text-transform: uppercase;
}
.position-filter {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 29px;
    padding: 12px 14px;
    border: 1px solid rgba(175, 218, 255, 0.14);
    background: rgba(255, 255, 255, 0.018);
}
.filter-buttons {
    display: flex;
    gap: 6px;
    overflow-x: auto;
}
.filter-buttons button {
    min-height: 39px;
    padding: 0 17px;
    border: 1px solid transparent;
    border-radius: 4px;
    color: #aebac4;
    background: transparent;
    font: 750 0.66rem var(--font-display);
    letter-spacing: 0.11em;
    text-transform: uppercase;
    cursor: pointer;
    transition: 0.2s;
}
.filter-buttons button:hover {
    color: var(--color-accent);
    border-color: rgba(69, 183, 255, 0.25);
}
.filter-buttons button.active {
    color: #04101c;
    border-color: var(--color-accent);
    background: var(--color-accent);
}
.player-count {
    flex: 0 0 auto;
    color: #7f8d98;
    font: 650 0.61rem var(--font-display);
    letter-spacing: 0.13em;
    text-transform: uppercase;
}
.player-count strong {
    color: var(--color-accent);
    font-size: 0.8rem;
}
.player-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
}
.player-card {
    position: relative;
    min-height: 360px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    overflow: hidden;
    padding: 20px;
    border: 1px solid rgba(175, 218, 255, 0.15);
    isolation: isolate;
    transition:
        border-color 0.28s,
        transform 0.28s;
}
.player-card::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: -2;
    background:
        linear-gradient(
            0deg,
            rgba(5, 10, 17, 0.97),
            rgba(5, 10, 17, 0.24) 62%,
            rgba(5, 10, 17, 0.07)
        ),
        var(--player-image) var(--player-position) / cover no-repeat;
    filter: saturate(0.65) contrast(1.08);
    transition:
        transform 0.6s,
        filter 0.3s;
}
.player-card:hover {
    border-color: rgba(69, 183, 255, 0.6);
    transform: translateY(-5px);
}
.player-card:hover::before {
    transform: scale(1.055);
    filter: saturate(0.88) contrast(1.08);
}
.player-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    font: 600 1.45rem var(--font-display);
}
.captain-badge {
    padding: 6px 9px;
    border: 1px solid rgba(69, 183, 255, 0.48);
    border-radius: 30px;
    color: var(--color-accent);
    background: rgba(5, 9, 15, 0.5);
    font: 800 0.52rem var(--font-display);
    letter-spacing: 0.13em;
    text-transform: uppercase;
}
.player-info {
    position: relative;
    padding-right: 43px;
}
.player-info h3 {
    margin: 0;
    font: 900 clamp(2.1rem, 3vw, 3rem) / 0.84 var(--font-display);
    letter-spacing: -0.025em;
    text-transform: uppercase;
}
.player-role {
    margin-top: 9px;
    color: var(--color-accent);
    font: 800 0.61rem var(--font-display);
    letter-spacing: 0.19em;
    text-transform: uppercase;
}
.player-link {
    position: absolute;
    right: 0;
    bottom: 0;
    display: grid;
    width: 37px;
    height: 37px;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, 0.65);
    border-radius: 50%;
    color: white;
    background: rgba(5, 9, 15, 0.55);
    cursor: pointer;
    transition: 0.22s;
}
.player-link svg {
    width: 17px;
    stroke-width: 1.8;
}
.player-card:hover .player-link {
    color: #04101c;
    border-color: var(--color-accent);
    background: var(--color-accent);
}
.empty-players {
    padding: 40px;
    border: 1px dashed rgba(255, 255, 255, 0.18);
    color: #8f9ca7;
    text-align: center;
}
.players-enter-active,
.players-leave-active {
    transition: 0.3s;
}
.players-enter-from,
.players-leave-to {
    opacity: 0;
    transform: scale(0.96);
}
@media (max-width: 1000px) {
    .player-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 700px) {
    .shell {
        width: calc(100% - 32px);
    }
    .directory-heading {
        align-items: flex-start;
        flex-direction: column;
    }
    .position-filter {
        align-items: flex-start;
        flex-direction: column;
    }
    .filter-buttons {
        width: 100%;
    }
    .player-grid {
        grid-template-columns: 1fr;
    }
    .player-card {
        min-height: 390px;
    }
}
</style>
