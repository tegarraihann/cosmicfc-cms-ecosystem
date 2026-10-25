<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { gsap } from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import { computed, onBeforeUnmount, onMounted, ref } from "vue";
import VideoModal from "@/Components/Public/VideoModal.vue";

gsap.registerPlugin(ScrollTrigger);

const props = defineProps({
    eyebrow: { type: String, default: "Mini Soccer Collective" },
    titleTop: { type: String, default: "OUR PEOPLE." },
    titleBottom: { type: String, default: "OUR GAME." },
    description: {
        type: String,
        default:
            "COSMIC adalah ruang bagi mereka yang hidup dari keseruan, komunitas, dan kompetisi. Satu tim, satu cerita, satu lapangan.",
    },
    primaryLabel: { type: String, default: "Lihat Jadwal" },
    primaryHref: { type: String, default: "/jadwal" },
    backgroundImage: {
        type: String,
        default: "/Assets/Dokumentasi/Docs-wahyu.jpeg",
    },
    established: { type: String, default: "Est. 2026" },
    storyLabel: { type: String, default: "Play video\nour story" },
    storyTitle: { type: String, default: "COSMIC — Our Story" },
    storySource: { type: String, default: "youtube" },
    storyUrl: { type: String, default: "" },
    storyMediaUrl: { type: String, default: "" },
    storyThumbnail: { type: String, default: "" },
    storyVideoEnabled: { type: Boolean, default: false },
});

const heroStyle = computed(() => ({
    "--hero-image": `url("${props.backgroundImage}")`,
}));
const heroRoot = ref(null);
const storyOpen = ref(false);
const canPlayStory = computed(
    () =>
        props.storyVideoEnabled &&
        (props.storySource === "media" ? props.storyMediaUrl : props.storyUrl),
);
const storyStyle = computed(() =>
    props.storyThumbnail
        ? { "--story-thumbnail": `url("${props.storyThumbnail}")` }
        : {},
);
let animationContext;

function playStory() {
    if (props.storySource === "external") {
        window.open(props.storyUrl, "_blank", "noopener,noreferrer");
        return;
    }
    storyOpen.value = true;
}

onMounted(() => {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

    animationContext = gsap.context(() => {
        const isMobile = window.matchMedia("(max-width: 899px)").matches;
        const saveData = navigator.connection?.saveData === true;
        const timeline = gsap.timeline({
            defaults: { ease: "power3.out" },
            onComplete: () => gsap.set([".site-header", ".eyebrow", ".line-one", ".line-two", ".intro", ".actions .button", ".side-note", ".established", ".scroll-cue"], { clearProps: "transform,opacity,willChange" }),
        });

        timeline
            .from(".site-header", { y: isMobile ? -12 : -28, opacity: 0, duration: isMobile ? 0.4 : 0.7 })
            .from(".eyebrow", { x: isMobile ? -14 : -32, opacity: 0, duration: isMobile ? 0.38 : 0.65 }, "-=.35")
            .from(".line-one", { y: isMobile ? 16 : 28, opacity: 0, duration: isMobile ? 0.5 : 0.9 }, "-=.35")
            .from(".line-two", { y: isMobile ? 18 : 35, opacity: 0, duration: isMobile ? 0.5 : 0.9 }, "-=.7")
            .from(".intro", { y: 24, opacity: 0, duration: 0.7 }, "-=.48")
            .from(
                ".actions .button",
                { y: 20, opacity: 0, duration: 0.55, stagger: 0.1 },
                "-=.42",
            )
            .from(".side-note", { x: 28, opacity: 0, duration: 0.7 }, "-=.5")
            .from(
                [".established", ".scroll-cue"],
                { opacity: 0, duration: 0.5, stagger: 0.08 },
                "-=.3",
            );

        if (!isMobile && !saveData) gsap.to(".hero-background", {
            yPercent: 8,
            scale: 1.08,
            ease: "none",
            scrollTrigger: {
                trigger: heroRoot.value,
                start: "top top",
                end: "bottom top",
                scrub: 0.8,
            },
        });

        if (!isMobile && !saveData) gsap.to(".hero-copy", {
            yPercent: -8,
            opacity: 0.35,
            ease: "none",
            scrollTrigger: {
                trigger: heroRoot.value,
                start: "45% top",
                end: "bottom top",
                scrub: 0.7,
            },
        });
    }, heroRoot.value);
});

onBeforeUnmount(() => animationContext?.revert());
</script>

<template>
    <Head><link v-if="backgroundImage" rel="preload" as="image" :href="backgroundImage" fetchpriority="high" /></Head>
    <section
        id="top"
        ref="heroRoot"
        class="hero"
        :class="{ 'hero--with-story': canPlayStory }"
        :style="heroStyle"
        aria-labelledby="hero-title"
    >
        <div class="hero-background" aria-hidden="true"></div>
        <slot name="navigation" />

        <div class="hero-inner shell">
            <div class="hero-copy">
                <div class="eyebrow">{{ eyebrow }}</div>
                <h1 id="hero-title">
                    <svg
                        class="curved-title"
                        viewBox="0 0 850 265"
                        role="img"
                        :aria-label="`${titleTop} ${titleBottom}`"
                    >
                        <defs>
                            <path
                                id="cosmic-curve-top"
                                d="M 10 130 Q 420 72 840 116"
                            />
                            <path
                                id="cosmic-curve-bottom"
                                d="M 28 246 Q 426 278 822 218"
                            />
                        </defs>
                        <text
                            class="line-one"
                            textLength="820"
                            lengthAdjust="spacingAndGlyphs"
                        >
                            <textPath
                                href="#cosmic-curve-top"
                                startOffset="50%"
                                text-anchor="middle"
                            >
                                {{ titleTop }}
                            </textPath>
                        </text>
                        <text
                            class="line-two"
                            textLength="794"
                            lengthAdjust="spacingAndGlyphs"
                        >
                            <textPath
                                href="#cosmic-curve-bottom"
                                startOffset="50%"
                                text-anchor="middle"
                            >
                                {{ titleBottom }}
                            </textPath>
                        </text>
                    </svg>
                </h1>
                <p class="intro">{{ description }}</p>
                <div class="actions">
                    <Link class="button button--primary" :href="primaryHref">
                        {{ primaryLabel }}
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path d="M5 12h14M13 6l6 6-6 6" />
                        </svg>
                    </Link>
                    <button
                        v-if="canPlayStory"
                        class="watch mobile-watch"
                        type="button"
                        :style="storyStyle"
                        @click="playStory"
                    >
                        <span>{{ storyLabel }}</span
                        ><i class="play" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <aside class="side-note" aria-label="Cerita klub">
                <p class="scribble">
                    Same people.<br />Different pitch.<br /><strong
                        >One family.</strong
                    >
                </p>
                <button
                    v-if="canPlayStory"
                    class="watch desktop-watch"
                    type="button"
                    :style="storyStyle"
                    @click="playStory"
                >
                    <span>{{ storyLabel }}</span>
                    <i class="play" aria-hidden="true"></i>
                </button>
            </aside>
        </div>

        <div class="established">{{ established }}</div>
        <div class="scroll-cue" aria-hidden="true">
            Scroll to explore <span></span>
        </div>
    </section>
    <VideoModal
        :open="storyOpen"
        :title="storyTitle"
        :source="storySource"
        :url="storyUrl"
        :media-url="storyMediaUrl"
        @close="storyOpen = false"
    />
</template>

<style scoped>
.hero {
    position: relative;
    min-height: 100svh;
    isolation: isolate;
    overflow: hidden;
    background: var(--color-ink);
}
.hero-background {
    position: absolute;
    inset: -5%;
    z-index: -3;
    background:
        linear-gradient(
            90deg,
            rgba(5, 9, 15, 0.97) 0%,
            rgba(5, 9, 15, 0.8) 36%,
            rgba(5, 9, 15, 0.17) 70%,
            rgba(5, 9, 15, 0.42) 100%
        ),
        linear-gradient(
            0deg,
            rgba(5, 9, 15, 0.94) 0%,
            transparent 49%,
            rgba(5, 9, 15, 0.18) 100%
        ),
        var(--hero-image) center 48% / cover no-repeat;
    filter: saturate(0.48) contrast(1.12);
    transform: scale(1.015);
}
.hero::after {
    content: "";
    position: absolute;
    inset: 0;
    z-index: -2;
    pointer-events: none;
    opacity: 0.2;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.8' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.35'/%3E%3C/svg%3E");
    mix-blend-mode: soft-light;
}
.shell {
    width: min(calc(100% - 80px), var(--content-max));
    margin-inline: auto;
}
.hero-inner {
    min-height: calc(100svh - 108px);
    display: grid;
    grid-template-columns: minmax(0, 1fr) 280px;
    align-items: center;
    gap: 40px;
    padding: 30px 0 126px;
}
.hero-copy {
    max-width: 850px;
}
.eyebrow {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 22px;
    color: var(--color-accent);
    font: 700 0.76rem var(--font-display);
    letter-spacing: 0.24em;
    text-transform: uppercase;
}
.eyebrow::before {
    content: "";
    width: 46px;
    height: 2px;
    background: var(--color-accent);
}
h1 {
    width: min(100%, 850px);
    margin: 0;
    line-height: 0;
    filter: drop-shadow(0 6px 18px rgba(0, 0, 0, 0.28));
}
.curved-title {
    display: block;
    width: 100%;
    height: auto;
    overflow: visible;
}
.curved-title text {
    font-family: var(--font-display);
    font-size: 151px;
    font-weight: 900;
    letter-spacing: -5px;
    text-transform: uppercase;
}
.line-one {
    fill: var(--color-paper);
}
.line-two {
    fill: var(--color-accent);
    filter: drop-shadow(0 0 11px rgba(69, 183, 255, 0.22));
}
.intro {
    max-width: 520px;
    margin: 28px 0 30px;
    color: #e0e7ee;
    font-size: clamp(0.98rem, 1.3vw, 1.18rem);
    line-height: 1.55;
}
.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
}
.button {
    display: inline-flex;
    min-height: 54px;
    align-items: center;
    justify-content: center;
    gap: 16px;
    padding: 0 27px;
    border: 1px solid rgba(255, 255, 255, 0.42);
    border-radius: 7px;
    color: var(--color-paper);
    font: 800 0.87rem var(--font-display);
    letter-spacing: 0.04em;
    text-decoration: none;
    text-transform: uppercase;
    transition:
        transform 0.2s,
        background 0.2s,
        color 0.2s,
        border-color 0.2s;
}
.button:hover {
    border-color: var(--color-accent);
    color: var(--color-accent);
    transform: translateY(-3px);
}
.button--primary {
    border-color: var(--color-accent);
    color: #04101c;
    background: var(--color-accent);
    box-shadow: 0 0 26px rgba(69, 183, 255, 0.16);
}
.button--primary:hover {
    border-color: var(--color-accent-hover);
    color: var(--color-paper);
    background: var(--color-accent-hover);
    box-shadow: 0 0 32px rgba(69, 183, 255, 0.28);
}
.button svg {
    width: 19px;
    stroke-width: 2;
}
.side-note {
    align-self: end;
    justify-self: end;
    padding-bottom: 20px;
    text-align: right;
}
.scribble {
    width: 170px;
    margin: 0 0 56px auto;
    color: rgba(255, 255, 255, 0.8);
    font: italic 500 1.8rem/1.05 var(--font-display);
    letter-spacing: 0.02em;
    text-transform: uppercase;
    transform: rotate(-7deg);
}
.scribble strong {
    color: var(--color-accent);
    font-weight: 600;
}
.watch {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 13px;
    padding: 10px 11px 10px 16px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.35);
    border-radius: 8px;
    color: var(--color-paper);
    background:
        linear-gradient(rgba(4, 9, 15, 0.7), rgba(4, 9, 15, 0.7)),
        var(--story-thumbnail) center/cover,
        rgba(4, 9, 15, 0.52);
    backdrop-filter: blur(8px);
    font: 700 0.72rem/1.15 var(--font-display);
    letter-spacing: 0.12em;
    text-align: left;
    text-decoration: none;
    text-transform: uppercase;
    cursor: pointer;
}
.mobile-watch {
    display: none;
    margin-top: 16px;
}
.watch > span {
    white-space: pre-line;
}
.play {
    display: grid;
    width: 42px;
    height: 42px;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, 0.75);
    border-radius: 50%;
}
.play::after {
    content: "";
    margin-left: 3px;
    border: 6px solid transparent;
    border-left: 9px solid white;
}
.established {
    position: absolute;
    z-index: 3;
    left: max(40px, calc((100vw - var(--content-max)) / 2));
    bottom: 34px;
    display: flex;
    align-items: center;
    gap: 18px;
    color: rgba(255, 255, 255, 0.74);
    font: 600 0.72rem var(--font-display);
    letter-spacing: 0.28em;
    text-transform: uppercase;
}
.established::before {
    content: "";
    width: 2px;
    height: 35px;
    background: var(--color-accent);
    transform: rotate(35deg);
}
.scroll-cue {
    position: absolute;
    right: max(40px, calc((100vw - var(--content-max)) / 2));
    bottom: 31px;
    display: flex;
    align-items: center;
    gap: 11px;
    color: rgba(255, 255, 255, 0.55);
    font: 600 0.65rem var(--font-display);
    letter-spacing: 0.18em;
    text-transform: uppercase;
}
.scroll-cue span {
    width: 45px;
    height: 1px;
    background: rgba(255, 255, 255, 0.45);
}

@media (max-width: 900px) {
    .shell {
        width: min(calc(100% - 40px), var(--content-max));
    }
    .hero-background {
        background:
            linear-gradient(90deg, rgba(5, 9, 15, 0.94), rgba(5, 9, 15, 0.25)),
            linear-gradient(0deg, rgba(5, 9, 15, 0.96) 0%, transparent 45%),
            var(--hero-image) 58% center/cover no-repeat;
    }
    .hero-inner {
        min-height: calc(100svh - 82px);
        grid-template-columns: 1fr;
        padding: 85px 0 130px;
    }
    .side-note,
    .scroll-cue {
        display: none;
    }
    .mobile-watch {
        display: inline-flex;
    }
    h1 {
        width: min(100%, 710px);
    }
    .intro {
        max-width: 470px;
    }
    .established {
        left: 20px;
    }
}

@media (max-width: 520px) {
    .shell {
        width: calc(100% - 32px);
    }
    .hero-inner {
        min-height: calc(100svh - 78px);
        align-items: end;
        padding: 55px 0 112px;
    }
    .eyebrow {
        margin-bottom: 16px;
    }
    h1 {
        width: 112%;
    }
    .intro {
        margin: 23px 0 25px;
        font-size: 0.94rem;
    }
    .actions {
        display: grid;
        grid-template-columns: 1fr;
    }
    .button {
        min-height: 52px;
    }
    .mobile-watch {
        width: 100%;
        min-height: 66px;
        justify-content: space-between;
        gap: 18px;
        padding: 9px 12px 9px 24px;
        border-radius: 7px;
        background:
            linear-gradient(90deg, rgba(4, 9, 15, 0.92), rgba(4, 9, 15, 0.66)),
            var(--story-thumbnail) center/cover,
            rgba(4, 9, 15, 0.72);
        font-size: 0.7rem;
    }
    .mobile-watch .play {
        width: 44px;
        height: 44px;
        flex: 0 0 44px;
    }
    .mobile-watch .play::after {
        border-width: 5px;
        border-left-width: 8px;
    }
    .hero--with-story .hero-inner {
        padding-bottom: 172px;
    }
    .established {
        bottom: calc(88px + env(safe-area-inset-bottom, 0px));
        font-size: 0.64rem;
    }
}
</style>
