<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";

defineProps({ label: { type: String, default: "Informasi tambahan" } });
const open = ref(false);
const root = ref(null);
const trigger = ref(null);
const position = ref({
    top: "0px",
    left: "0px",
    width: "290px",
    "--arrow-left": "50%",
});

function updatePosition() {
    if (!open.value || !trigger.value) return;
    const rect = trigger.value.getBoundingClientRect();
    const width = Math.min(290, window.innerWidth - 24);
    const left = Math.min(
        Math.max(12, rect.left + rect.width / 2 - width / 2),
        window.innerWidth - width - 12,
    );
    position.value = {
        top: `${rect.bottom + 9}px`,
        left: `${left}px`,
        width: `${width}px`,
        "--arrow-left": `${rect.left + rect.width / 2 - left}px`,
    };
}
async function toggle() {
    open.value = !open.value;
    if (open.value) {
        await nextTick();
        updatePosition();
    }
}
function onDocumentClick(event) {
    if (open.value && !root.value?.contains(event.target)) open.value = false;
}
function onKeydown(event) {
    if (event.key === "Escape") open.value = false;
}
onMounted(() => {
    document.addEventListener("click", onDocumentClick);
    document.addEventListener("keydown", onKeydown);
    window.addEventListener("resize", updatePosition);
    window.addEventListener("scroll", updatePosition, true);
});
onBeforeUnmount(() => {
    document.removeEventListener("click", onDocumentClick);
    document.removeEventListener("keydown", onKeydown);
    window.removeEventListener("resize", updatePosition);
    window.removeEventListener("scroll", updatePosition, true);
});
</script>

<template>
    <span ref="root" class="info-tooltip">
        <button
            ref="trigger"
            type="button"
            :aria-label="label"
            :aria-expanded="open"
            @click.stop="toggle"
        >
            i
        </button>
        <Teleport to="body">
            <span
                v-if="open"
                class="info-tooltip-bubble"
                role="tooltip"
                :style="position"
                @click.stop
                ><slot
            /></span>
        </Teleport>
    </span>
</template>

<style scoped>
.info-tooltip {
    display: inline-flex;
    margin-left: 6px;
    vertical-align: middle;
}
.info-tooltip > button {
    display: grid;
    width: 18px;
    height: 18px;
    padding: 0;
    place-items: center;
    border: 1px solid #45b7ff88;
    border-radius: 50%;
    color: var(--color-accent);
    background: #45b7ff10;
    font: 700 11px Arial;
    cursor: pointer;
}
.info-tooltip > button:hover,
.info-tooltip > button[aria-expanded="true"] {
    color: #03101b;
    background: var(--color-accent);
}
</style>

<style>
.info-tooltip-bubble {
    position: fixed;
    z-index: 10000;
    box-sizing: border-box;
    padding: 12px 13px;
    border: 1px solid #45b7ff55;
    color: #b9cbd6;
    background: #0a1724;
    box-shadow: 0 14px 36px #000c;
    font:
        400 0.72rem/1.55 Arial,
        sans-serif;
    text-align: left;
    text-transform: none;
    letter-spacing: normal;
}
.info-tooltip-bubble::before {
    position: absolute;
    top: -5px;
    left: calc(var(--arrow-left) - 5px);
    width: 9px;
    height: 9px;
    transform: rotate(45deg);
    border-top: 1px solid #45b7ff55;
    border-left: 1px solid #45b7ff55;
    background: #0a1724;
    content: "";
}
</style>
