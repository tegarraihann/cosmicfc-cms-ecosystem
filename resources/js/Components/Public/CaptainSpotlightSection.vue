<script setup>
import { computed, ref } from "vue";
const props = defineProps({ captain: { type: Object, default: null } });
const sectionRoot = ref(null);
const captain = computed(() => props.captain || { number: '—', name: 'Belum ditentukan', position: '—', stats: {} });
const sectionStyle = computed(() => props.captain?.image ? ({
    "--captain-image": `url('${props.captain.image}')`,
}) : ({}));
const stats = computed(() => [
    {
        value: String(props.captain?.stats?.appearances ?? 0).padStart(2, "0"),
        label: "Appearances",
    },
    {
        value: String(props.captain?.stats?.assists ?? 0).padStart(2, "0"),
        label: "Assists",
    },
    {
        value: String(props.captain?.stats?.goals ?? 0).padStart(2, "0"),
        label: "Goals",
    },
    { value: `${props.captain?.stats?.attendance ?? 0}%`, label: "Attendance" },
]);
</script>
<template>
    <section
        id="captain"
        ref="sectionRoot"
        class="captain"
        :style="sectionStyle"
        aria-labelledby="captain-title"
    >
        <div class="captain-background"></div>
        <div class="captain-content shell">
            <div data-reveal>
                <div class="captain-kicker">Captain Spotlight</div>
                <h2 id="captain-title">Lead By<span>Example.</span></h2>
                <blockquote>
                    “Kami bermain bukan hanya untuk menang, tetapi untuk menjaga
                    kepercayaan satu sama lain di setiap menit pertandingan.”
                </blockquote>
            </div>
            <aside class="captain-panel" data-reveal>
                <div class="captain-profile">
                    <span>#{{ captain.number }}</span>
                    <div>
                        {{ captain.name
                        }}<small>Captain · {{ captain.position }}</small>
                    </div>
                </div>
                <div class="captain-stats">
                    <div v-for="stat in stats" :key="stat.label">
                        <strong>{{ stat.value }}</strong
                        ><span>{{ stat.label }}</span>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</template>
<style scoped>
.captain {
    position: relative;
    min-height: 480px;
    display: flex;
    align-items: center;
    overflow: hidden;
    isolation: isolate;
    border-bottom: 1px solid var(--color-line);
    background: #07111a;
}
.captain-background {
    position: absolute;
    inset: 0;
    z-index: -1;
    background:
        linear-gradient(
            90deg,
            rgba(4, 9, 16, 0.98),
            rgba(4, 9, 16, 0.84) 39%,
            rgba(4, 9, 16, 0.25) 72%,
            rgba(4, 9, 16, 0.45)
        ),
        linear-gradient(0deg, rgba(4, 9, 16, 0.76), transparent 56%),
        var(--captain-image) center 48% / cover no-repeat;
    filter: saturate(0.65) contrast(1.1);
    transition: transform 0.8s;
}
.captain:hover .captain-background {
    transform: scale(1.025);
}
.shell {
    width: min(calc(100% - 80px), var(--content-max));
    margin-inline: auto;
}
.captain-content {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 310px;
    align-items: center;
    gap: 70px;
    padding: 60px 0;
}
.captain-kicker {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 18px;
    color: var(--color-accent);
    font: 800 0.68rem var(--font-display);
    letter-spacing: 0.2em;
    text-transform: uppercase;
}
.captain-kicker::before {
    content: "";
    width: 38px;
    height: 2px;
    background: var(--color-accent);
}
.captain h2 {
    margin: 0;
    font: 900 clamp(3.7rem, 6.7vw, 7rem) / 0.79 var(--font-display);
    letter-spacing: -0.05em;
    text-transform: uppercase;
}
.captain h2 span {
    display: block;
    color: var(--color-accent);
}
blockquote {
    max-width: 590px;
    margin: 25px 0 0;
    padding-left: 20px;
    border-left: 2px solid var(--color-accent);
    color: #d2dce4;
    font-size: 0.94rem;
    font-style: italic;
    line-height: 1.65;
}
.captain-panel {
    padding: 25px;
    border: 1px solid rgba(175, 218, 255, 0.24);
    background: rgba(5, 9, 15, 0.65);
    backdrop-filter: blur(9px);
}
.captain-profile {
    display: flex;
    align-items: center;
    gap: 16px;
    padding-bottom: 19px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.14);
}
.captain-profile > span {
    display: grid;
    width: 57px;
    height: 57px;
    place-items: center;
    border: 1px solid var(--color-accent);
    border-radius: 50%;
    color: var(--color-accent);
    font: 900 1.4rem var(--font-display);
}
.captain-profile > div {
    font: 900 1.7rem/0.9 var(--font-display);
    text-transform: uppercase;
}
.captain-profile small {
    display: block;
    margin-top: 8px;
    color: #8f9ca7;
    font-size: 0.57rem;
    letter-spacing: 0.15em;
}
.captain-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
}
.captain-stats > div {
    padding: 18px 8px;
    text-align: center;
    border-right: 1px solid rgba(255, 255, 255, 0.12);
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}
.captain-stats > div:nth-child(2n) {
    border-right: 0;
}
.captain-stats > div:nth-last-child(-n + 2) {
    border-bottom: 0;
}
.captain-stats strong {
    display: block;
    color: var(--color-accent);
    font: 900 2rem/0.9 var(--font-display);
}
.captain-stats span {
    color: #8d9aa5;
    font: 650 0.55rem var(--font-display);
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
@media (max-width: 800px) {
    .shell {
        width: calc(100% - 32px);
    }
    .captain {
        min-height: 650px;
        align-items: flex-end;
    }
    .captain-background {
        background:
            linear-gradient(
                0deg,
                rgba(4, 9, 16, 0.98),
                rgba(4, 9, 16, 0.65) 58%,
                rgba(4, 9, 16, 0.15)
            ),
            var(--captain-image) center/cover;
    }
    .captain-content {
        grid-template-columns: 1fr;
        gap: 35px;
        padding: 180px 0 48px;
    }
    .captain h2 {
        font-size: clamp(3.5rem, 17vw, 5.5rem);
    }
}
</style>
