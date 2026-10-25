<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

gsap.registerPlugin(ScrollTrigger);

const props = defineProps({
    pageLabel: { type: String, required: true },
    title: { type: String, required: true },
    titleAccent: { type: String, required: true },
    description: { type: String, required: true },
    backgroundImage: { type: String, default: '/Assets/Dokumentasi/Docs-wahyu.jpeg' },
    panelLabel: { type: String, default: null },
    panelValue: { type: [String, Number], default: null },
    panelStatus: { type: String, default: null },
    scrollLabel: { type: String, default: 'Scroll untuk melihat' },
    minHeight: { type: String, default: '82svh' },
    panelWidth: { type: String, default: '320px' },
});

const heroRoot = ref(null);
const heroStyle = computed(() => ({
    '--page-hero-image': `url("${props.backgroundImage}")`,
    '--page-hero-height': props.minHeight,
    '--page-hero-panel-width': props.panelWidth,
}));
let animationContext;

onMounted(() => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    animationContext = gsap.context(() => {
        const isMobile = window.matchMedia('(max-width: 899px)').matches;
        const saveData = navigator.connection?.saveData === true;
        gsap.timeline({ defaults: { ease: 'power3.out' }, onComplete: () => gsap.set(['.site-header','.breadcrumb','.hero-title-line','.hero-description','.hero-panel','.hero-foot','.hero-bottom'], { clearProps: 'transform,opacity,willChange' }) })
            .from('.site-header', { y: -28, opacity: 0, duration: 0.65 })
            .from('.breadcrumb', { x: -26, opacity: 0, duration: 0.5 }, '-=.28')
            .from('.hero-title-line', { y: 46, opacity: 0, stagger: 0.11, duration: 0.72 }, '-=.2')
            .from('.hero-description', { y: 24, opacity: 0, duration: 0.62 }, '-=.35')
            .from('.hero-panel', { x: 34, opacity: 0, duration: 0.7 }, '-=.45')
            .from('.hero-foot, .hero-bottom', { opacity: 0, duration: 0.45 }, '-=.2');

        if (!isMobile && !saveData) gsap.to('.page-hero-background', {
            yPercent: 8,
            scale: 1.08,
            ease: 'none',
            scrollTrigger: {
                trigger: heroRoot.value,
                start: 'top top',
                end: 'bottom top',
                scrub: 0.8,
            },
        });

        if (!isMobile && !saveData) gsap.to('.hero-content', {
            yPercent: -7,
            opacity: 0.4,
            ease: 'none',
            scrollTrigger: {
                trigger: heroRoot.value,
                start: '48% top',
                end: 'bottom top',
                scrub: 0.7,
            },
        });
    }, heroRoot.value);
});

onBeforeUnmount(() => animationContext?.revert());
</script>

<template>
    <Head><link v-if="backgroundImage" rel="preload" as="image" :href="backgroundImage" fetchpriority="high" /></Head>
    <section id="top" ref="heroRoot" class="page-hero" :style="heroStyle" aria-labelledby="page-title">
        <div class="page-hero-background" aria-hidden="true"></div>
        <slot name="navigation" />

        <div class="hero-content shell">
            <div class="hero-copy">
                <nav class="breadcrumb" aria-label="Breadcrumb">
                    <Link href="/">Home</Link>
                    <span aria-hidden="true">/</span>
                    <span>{{ pageLabel }}</span>
                </nav>
                <slot name="kicker" />
                <h1 id="page-title" class="hero-title">
                    <span class="hero-title-line">{{ title }}</span>
                    <span class="hero-title-line accent">{{ titleAccent }}</span>
                </h1>
                <p class="hero-description">{{ description }}</p>
            </div>

            <slot name="panel">
                <aside v-if="panelLabel || panelValue || panelStatus" class="hero-panel" :aria-label="panelLabel">
                    <div class="panel-label">{{ panelLabel }}</div>
                    <div class="panel-value">{{ panelValue }}</div>
                    <div v-if="panelStatus" class="panel-status">{{ panelStatus }}</div>
                </aside>
            </slot>
        </div>

        <slot name="bottom">
            <div class="hero-foot" aria-hidden="true">{{ scrollLabel }}</div>
        </slot>
    </section>
</template>

<style scoped>
.page-hero { position: relative; min-height: var(--page-hero-height); isolation: isolate; overflow: hidden; border-bottom: 1px solid var(--color-line); background: var(--color-surface); }
.page-hero-background { position: absolute; inset: -5%; z-index: -3; background: linear-gradient(90deg,rgba(5,9,15,.98) 0%,rgba(5,9,15,.86) 34%,rgba(5,9,15,.26) 69%,rgba(5,9,15,.42) 100%),linear-gradient(0deg,rgba(5,9,15,.93) 0%,transparent 45%,rgba(5,9,15,.2) 100%),var(--page-hero-image) center 48%/cover no-repeat; filter: saturate(.54) contrast(1.12); transform: scale(1.015); }
.page-hero::after { content: ''; position: absolute; inset: 0; z-index: -2; opacity: .2; pointer-events: none; background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.8' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.35'/%3E%3C/svg%3E"); mix-blend-mode: soft-light; }
.shell { width: min(calc(100% - 80px),var(--content-max)); margin-inline: auto; }
.hero-content { min-height: calc(var(--page-hero-height) - 108px); display: grid; grid-template-columns: minmax(0,1fr) var(--page-hero-panel-width); align-items: center; gap: 70px; padding: 65px 0 90px; }
.breadcrumb { display: flex; align-items: center; gap: 10px; margin-bottom: 27px; color: #aebac5; font: 700 .7rem var(--font-display); letter-spacing: .16em; text-transform: uppercase; }
.breadcrumb a { color: inherit; text-decoration: none; transition: color .2s; }
.breadcrumb a:hover,.breadcrumb span:last-child { color: var(--color-accent); }
.hero-title { max-width: 870px; margin: 0; color: var(--color-paper); font: 900 clamp(4.5rem,8.2vw,9rem)/.8 var(--font-display); letter-spacing: -.055em; text-shadow: 0 7px 25px rgba(0,0,0,.3); text-transform: uppercase; }
.hero-title-line { display: block; }
.hero-title-line.accent { color: var(--color-accent); }
.hero-description { max-width: 550px; margin: 30px 0 0; color: #d8e1e9; font-size: clamp(.98rem,1.25vw,1.15rem); line-height: 1.6; }
.hero-panel { align-self: end; margin-bottom: 12px; padding: 24px; border: 1px solid rgba(175,218,255,.25); border-radius: 5px; background: rgba(5,9,15,.58); backdrop-filter: blur(9px); }
.panel-label { margin-bottom: 8px; color: var(--color-accent); font: 800 .67rem var(--font-display); letter-spacing: .22em; text-transform: uppercase; }
.panel-value { color: var(--color-paper); font: 900 3.8rem/.9 var(--font-display); letter-spacing: -.04em; }
.panel-status { display: flex; align-items: center; gap: 9px; margin-top: 16px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,.14); color: #c5d0da; font: 600 .67rem var(--font-display); letter-spacing: .1em; text-transform: uppercase; }
.panel-status::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: var(--color-accent); box-shadow: 0 0 12px rgba(69,183,255,.7); }
.hero-foot { position: absolute; right: max(40px,calc((100vw - var(--content-max))/2)); bottom: 31px; display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,.58); font: 600 .65rem var(--font-display); letter-spacing: .18em; text-transform: uppercase; }
.hero-foot::after { content: ''; width: 48px; height: 1px; background: rgba(255,255,255,.42); }

@media(max-width:900px) {
    .page-hero { min-height: 100svh; }
    .shell { width: min(calc(100% - 40px),var(--content-max)); }
    .page-hero-background { background: linear-gradient(90deg,rgba(5,9,15,.94),rgba(5,9,15,.32)),linear-gradient(0deg,rgba(5,9,15,.95),transparent 55%),var(--page-hero-image) 58% center/cover no-repeat; }
    .hero-content { min-height: calc(100svh - 82px); grid-template-columns: 1fr; align-content: center; gap: 42px; padding: 75px 0 100px; }
    .hero-panel { width: min(100%,320px); margin: 0; }
    .hero-foot { display: none; }
}

@media(max-width:520px) {
    .shell { width: calc(100% - 32px); }
    .hero-content { min-height: calc(100svh - 78px); padding: 58px 0 90px; }
    .hero-title { font-size: clamp(4rem,20vw,6rem); }
    .hero-description { margin-top: 23px; font-size: .94rem; }
    .breadcrumb { margin-bottom: 20px; }
    .hero-panel { padding: 20px; }
    .panel-value { font-size: 3.2rem; }
}
</style>
