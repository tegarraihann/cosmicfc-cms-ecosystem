<script setup>
import { computed, onBeforeUnmount, watch } from "vue";

const props = defineProps({
    open: Boolean,
    title: { type: String, default: "Our Story" },
    source: { type: String, default: "youtube" },
    url: { type: String, default: "" },
    mediaUrl: { type: String, default: "" },
});
const emit = defineEmits(["close"]);

const youtubeId = computed(() => {
    const match = props.url.match(
        /(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{6,})/i,
    );
    return match?.[1] || "";
});
const embedUrl = computed(() =>
    youtubeId.value
        ? `https://www.youtube-nocookie.com/embed/${youtubeId.value}?autoplay=1&rel=0`
        : "",
);
const closeOnEscape = (event) => event.key === "Escape" && emit("close");

watch(
    () => props.open,
    (open) => {
        document.body.style.overflow = open ? "hidden" : "";
        open
            ? window.addEventListener("keydown", closeOnEscape)
            : window.removeEventListener("keydown", closeOnEscape);
    },
);
onBeforeUnmount(() => {
    document.body.style.overflow = "";
    window.removeEventListener("keydown", closeOnEscape);
});
</script>

<template>
    <Teleport to="body">
        <Transition name="story-modal">
            <div
                v-if="open"
                class="story-overlay"
                role="presentation"
                @click.self="emit('close')"
            >
                <section
                    class="story-dialog"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="title"
                >
                    <header>
                        <div>
                            <span>Video COSMIC</span>
                            <h2>{{ title }}</h2>
                        </div>
                        <button
                            type="button"
                            aria-label="Tutup video"
                            @click="emit('close')"
                        >
                            ×
                        </button>
                    </header>
                    <div class="story-frame">
                        <iframe
                            v-if="source === 'youtube' && embedUrl"
                            :src="embedUrl"
                            :title="title"
                            allow="autoplay; encrypted-media; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                        <video
                            v-else-if="source === 'media' && mediaUrl"
                            :src="mediaUrl"
                            controls
                            autoplay
                            playsinline
                        ></video>
                        <p v-else>Video belum tersedia.</p>
                    </div>
                </section>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.story-overlay {
    position: fixed;
    z-index: 1000;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 24px;
    background: rgba(1, 5, 10, 0.9);
    backdrop-filter: blur(9px);
}
.story-dialog {
    width: min(1040px, 100%);
    overflow: hidden;
    border: 1px solid rgba(69, 183, 255, 0.38);
    border-radius: 10px;
    background: #050b13;
    box-shadow: 0 28px 100px rgba(0, 0, 0, 0.65);
}
header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 17px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}
header span {
    color: #45b7ff;
    font: 700 0.64rem var(--font-display);
    letter-spacing: 0.16em;
    text-transform: uppercase;
}
h2 {
    margin: 4px 0 0;
    color: #f4f7fb;
    font: 800 clamp(1rem, 2vw, 1.35rem) var(--font-display);
    text-transform: uppercase;
}
header button {
    display: grid;
    width: 38px;
    height: 38px;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, 0.22);
    border-radius: 50%;
    color: #fff;
    background: transparent;
    font-size: 1.45rem;
    cursor: pointer;
}
.story-frame {
    display: grid;
    aspect-ratio: 16/9;
    place-items: center;
    background: #000;
}
.story-frame iframe,
.story-frame video {
    width: 100%;
    height: 100%;
    border: 0;
    object-fit: contain;
}
.story-frame p {
    color: #91a2ae;
}
.story-modal-enter-active,
.story-modal-leave-active {
    transition: opacity 0.22s ease;
}
.story-modal-enter-active .story-dialog,
.story-modal-leave-active .story-dialog {
    transition: transform 0.22s ease;
}
.story-modal-enter-from,
.story-modal-leave-to {
    opacity: 0;
}
.story-modal-enter-from .story-dialog,
.story-modal-leave-to .story-dialog {
    transform: translateY(14px) scale(0.985);
}
@media (max-width: 600px) {
    .story-overlay {
        padding: 12px;
    }
    .story-dialog {
        border-radius: 7px;
    }
    header {
        padding: 13px 14px;
    }
}
</style>
