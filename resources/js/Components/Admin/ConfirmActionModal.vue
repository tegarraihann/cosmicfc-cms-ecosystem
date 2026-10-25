<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from "vue";

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, required: true },
    description: { type: String, default: "" },
    itemName: { type: String, default: "" },
    note: { type: String, default: "" },
    confirmLabel: { type: String, default: "Lanjutkan" },
    cancelLabel: { type: String, default: "Batal" },
    processingLabel: { type: String, default: "Memproses..." },
    mode: {
        type: String,
        default: "confirm",
        validator: (value) => ["confirm", "alert"].includes(value),
    },
    variant: {
        type: String,
        default: "primary",
        validator: (value) => ["primary", "warning", "danger"].includes(value),
    },
    processing: { type: Boolean, default: false },
    error: { type: String, default: "" },
});

const emit = defineEmits(["confirm", "cancel", "close"]);
const cancelButton = ref(null);
const confirmButton = ref(null);

function close() {
    if (props.processing) return;
    emit("cancel");
    emit("close");
}

function onKeydown(event) {
    if (event.key === "Escape" && props.open) close();
}

watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen) {
            document.addEventListener("keydown", onKeydown);
            await nextTick();
            (props.mode === "alert"
                ? confirmButton.value
                : cancelButton.value
            )?.focus();
        } else {
            document.removeEventListener("keydown", onKeydown);
        }
    },
);

onBeforeUnmount(() => document.removeEventListener("keydown", onKeydown));
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="confirm-backdrop"
            role="presentation"
            @click.self="close"
        >
            <section
                class="confirm-dialog"
                :class="[`is-${variant}`, { 'is-alert': mode === 'alert' }]"
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="confirm-title"
                aria-describedby="confirm-description"
            >
                <header>
                    <span class="confirm-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path
                                d="M12 8v5m0 3.5v.01M10.3 3.8 2.7 17a2 2 0 0 0 1.74 3h15.12a2 2 0 0 0 1.74-3L13.7 3.8a2 2 0 0 0-3.4 0Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                            />
                        </svg>
                    </span>
                    <div>
                        <small>{{ mode === "alert" ? "Pemberitahuan" : "Konfirmasi tindakan" }}</small>
                        <h2 id="confirm-title">{{ title }}</h2>
                    </div>
                    <button
                        type="button"
                        class="close"
                        aria-label="Tutup"
                        @click="close"
                    >
                        ×
                    </button>
                </header>

                <div class="confirm-content">
                    <strong v-if="itemName" class="item-name">{{
                        itemName
                    }}</strong>
                    <p id="confirm-description">{{ description }}</p>
                    <p v-if="note" class="note">{{ note }}</p>
                    <p v-if="error" class="error" role="alert">{{ error }}</p>
                </div>

                <footer>
                    <button
                        v-if="mode !== 'alert'"
                        ref="cancelButton"
                        type="button"
                        class="cancel"
                        :disabled="processing"
                        @click="close"
                    >
                        {{ cancelLabel }}
                    </button>
                    <button
                        ref="confirmButton"
                        type="button"
                        class="confirm"
                        :disabled="processing"
                        @click="emit('confirm')"
                    >
                        {{ processing ? processingLabel : confirmLabel }}
                    </button>
                </footer>
            </section>
        </div>
    </Teleport>
</template>

<style scoped>
.confirm-backdrop {
    position: fixed;
    z-index: 1000;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 18px;
    background: #01050ae8;
    backdrop-filter: blur(6px);
}
.confirm-dialog {
    width: min(480px, 100%);
    border: 1px solid #45b7ff44;
    background: #07111e;
    box-shadow: 0 28px 90px #000a;
}
header {
    display: grid;
    grid-template-columns: 42px 1fr auto;
    align-items: center;
    gap: 13px;
    padding: 20px 22px;
    border-bottom: 1px solid #ffffff16;
}
.confirm-icon {
    display: grid;
    width: 42px;
    height: 42px;
    place-items: center;
    border: 1px solid #45b7ff55;
    color: #45b7ff;
    background: #45b7ff0d;
}
.confirm-icon svg {
    width: 23px;
    height: 23px;
}
header small {
    color: #45b7ff;
    font: 800 0.5rem var(--font-display);
    letter-spacing: 0.13em;
    text-transform: uppercase;
}
h2 {
    margin: 5px 0 0;
    color: white;
    font: 850 1.35rem var(--font-display);
    text-transform: uppercase;
}
.close {
    padding: 4px;
    border: 0;
    color: #91a3af;
    background: transparent;
    font-size: 1.5rem;
}
.close:hover {
    color: white;
}
.confirm-content {
    padding: 22px;
}
.item-name {
    display: block;
    margin-bottom: 12px;
    color: white;
    font: 850 1.15rem var(--font-display);
    text-transform: uppercase;
}
.confirm-content p {
    margin: 0;
    color: #9aabb6;
    font-size: 0.88rem;
    line-height: 1.65;
}
.note {
    margin-top: 16px !important;
    padding: 12px 13px;
    border-left: 3px solid #45b7ff;
    background: #45b7ff0b;
    color: #b8cad5 !important;
}
.error {
    margin-top: 14px !important;
    padding: 11px 13px;
    border: 1px solid #ff647050;
    color: #ffadb3 !important;
    background: #ff64700c;
}
footer {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    padding: 16px 22px;
    border-top: 1px solid #ffffff16;
}
footer button {
    min-height: 42px;
    padding: 0 17px;
    font: 800 0.56rem var(--font-display);
    text-transform: uppercase;
}
.cancel {
    border: 1px solid #ffffff28;
    color: white;
    background: transparent;
}
.confirm {
    border: 1px solid #45b7ff;
    color: #03101b;
    background: #45b7ff;
}
.is-danger {
    border-color: #ff647055;
}
.is-danger .confirm-icon {
    border-color: #ff647066;
    color: #ff7d86;
    background: #ff64700d;
}
.is-danger .confirm {
    border-color: #ff6d77;
    color: white;
    background: #d9424d;
}
.is-danger .note {
    border-left-color: #ff6d77;
}
.is-warning .confirm {
    border-color: #ffc45e;
    background: #ffc45e;
}
button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
}
@media (max-width: 520px) {
    header,
    .confirm-content,
    footer {
        padding-right: 17px;
        padding-left: 17px;
    }
    footer {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }
    .confirm-dialog.is-alert footer {
        grid-template-columns: 1fr;
    }
    footer button {
        width: 100%;
    }
}
</style>
