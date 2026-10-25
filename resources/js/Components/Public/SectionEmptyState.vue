<script setup>
defineProps({
    eyebrow: { type: String, default: "Data belum tersedia" },
    title: { type: String, required: true },
    description: { type: String, required: true },
});
</script>

<template>
    <section class="empty-section" :aria-label="title">
        <div class="shell">
            <div class="empty-mark" aria-hidden="true">
                <span></span><span></span><span></span>
            </div>
            <div class="empty-copy">
                <small>{{ eyebrow }}</small>
                <h2>{{ title }}</h2>
                <p>{{ description }}</p>
            </div>
        </div>
    </section>
</template>

<style scoped>
.empty-section {
    display: grid;
    min-height: 280px;
    place-items: center;
    border-bottom: 1px solid var(--color-line);
    background:
        radial-gradient(circle at 78% 40%, rgba(69, 183, 255, 0.07), transparent 30%),
        var(--color-surface);
}
.shell {
    display: grid;
    width: min(calc(100% - 80px), var(--content-max));
    grid-template-columns: 110px minmax(0, 1fr);
    align-items: center;
    gap: 34px;
    padding-block: 52px;
}
.empty-mark {
    position: relative;
    display: grid;
    width: 92px;
    height: 92px;
    place-items: center;
    border: 1px solid rgba(69, 183, 255, 0.35);
    border-radius: 50%;
}
.empty-mark span {
    position: absolute;
    width: 46px;
    height: 1px;
    background: rgba(69, 183, 255, 0.65);
}
.empty-mark span:nth-child(2) { transform: rotate(60deg); }
.empty-mark span:nth-child(3) { transform: rotate(-60deg); }
.empty-copy small {
    color: var(--color-accent);
    font: 800 0.58rem var(--font-display);
    letter-spacing: 0.18em;
    text-transform: uppercase;
}
.empty-copy h2 {
    margin: 10px 0 8px;
    color: var(--color-paper);
    font: 900 clamp(2.2rem, 4vw, 4rem)/0.9 var(--font-display);
    letter-spacing: -0.035em;
    text-transform: uppercase;
}
.empty-copy p {
    max-width: 620px;
    margin: 0;
    color: #91a3af;
    font-size: 0.86rem;
    line-height: 1.65;
}
@media (max-width: 640px) {
    .empty-section { min-height: 245px; }
    .shell {
        width: calc(100% - 32px);
        grid-template-columns: 58px minmax(0, 1fr);
        gap: 18px;
        padding-block: 38px;
    }
    .empty-mark { width: 54px; height: 54px; }
    .empty-mark span { width: 27px; }
    .empty-copy h2 { font-size: clamp(1.8rem, 9vw, 2.8rem); }
}
</style>
