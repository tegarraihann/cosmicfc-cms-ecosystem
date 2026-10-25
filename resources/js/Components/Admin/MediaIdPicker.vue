<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    modelValue: { default: null },
    items: { type: Array, default: () => [] },
});
const emit = defineEmits(["update:modelValue"]);
const open = ref(false);
const search = ref("");
const selected = computed(() =>
    props.items.find((item) => Number(item.id) === Number(props.modelValue)),
);
const results = computed(() => {
    const query = search.value.trim().toLowerCase();
    return props.items.filter(
        (item) =>
            !query ||
            String(item.title || "")
                .toLowerCase()
                .includes(query),
    );
});
function choose(item) {
    emit("update:modelValue", item.id);
    open.value = false;
}
</script>

<template>
    <div class="media-field">
        <div v-if="selected" class="selected-media">
            <img :src="selected.url" :alt="selected.title" />
            <div>
                <strong>{{ selected.title }}</strong
                ><small>Foto award terpilih</small>
            </div>
        </div>
        <div class="media-actions">
            <button type="button" @click="open = true">
                {{ selected ? "Ganti foto" : "Pilih dari Media Library" }}
            </button>
            <button
                v-if="selected"
                type="button"
                class="remove"
                @click="emit('update:modelValue', null)"
            >
                Gunakan foto pemain
            </button>
        </div>
        <Teleport to="body">
            <div v-if="open" class="media-backdrop" @click.self="open = false">
                <section class="media-dialog">
                    <header>
                        <div>
                            <span>Media Library</span>
                            <h3>Pilih Foto Award</h3>
                        </div>
                        <button
                            type="button"
                            aria-label="Tutup"
                            @click="open = false"
                        >
                            ×
                        </button>
                    </header>
                    <label class="media-search"
                        ><span>Cari media</span
                        ><input
                            v-model="search"
                            placeholder="Cari berdasarkan nama media"
                    /></label>
                    <div v-if="results.length" class="media-grid">
                        <button
                            v-for="item in results"
                            :key="item.id"
                            type="button"
                            :class="{
                                active: Number(item.id) === Number(modelValue),
                            }"
                            @click="choose(item)"
                        >
                            <img :src="item.url" :alt="item.title" /><strong>{{
                                item.title
                            }}</strong>
                        </button>
                    </div>
                    <p v-else class="empty">Tidak ada media yang sesuai.</p>
                </section>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.selected-media {
    display: grid;
    grid-template-columns: 100px 1fr;
    align-items: center;
    gap: 12px;
    padding: 10px;
    border: 1px solid #ffffff1b;
    background: #050d16;
}
.selected-media img {
    width: 100px;
    height: 72px;
    object-fit: cover;
}
.selected-media strong,
.selected-media small {
    display: block;
}
.selected-media strong {
    color: white;
    font: 750 0.68rem var(--font-display);
    text-transform: uppercase;
}
.selected-media small {
    margin-top: 5px;
    color: #718590;
    font-size: 0.66rem;
}
.media-actions {
    display: flex;
    gap: 8px;
    margin-top: 9px;
}
.media-actions button {
    min-height: 38px;
    padding: 0 12px;
    border: 1px solid #45b7ff55;
    color: var(--color-accent);
    background: #07111e;
    font: 750 0.5rem var(--font-display);
    text-transform: uppercase;
}
.media-actions button.remove {
    border-color: #ffffff22;
    color: #9aabb6;
}
.media-backdrop {
    position: fixed;
    z-index: 1100;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 18px;
    background: #01050ae8;
    backdrop-filter: blur(6px);
}
.media-dialog {
    width: min(900px, 100%);
    max-height: 88vh;
    overflow: auto;
    border: 1px solid #45b7ff55;
    background: #07111e;
}
.media-dialog header {
    position: sticky;
    z-index: 2;
    top: 0;
    display: flex;
    justify-content: space-between;
    padding: 18px 20px;
    border-bottom: 1px solid #ffffff18;
    background: #07111efa;
}
.media-dialog header span {
    color: var(--color-accent);
    font: 750 0.5rem var(--font-display);
    letter-spacing: 0.14em;
    text-transform: uppercase;
}
.media-dialog h3 {
    margin: 5px 0 0;
    color: white;
    font: 850 1.2rem var(--font-display);
    text-transform: uppercase;
}
.media-dialog header button {
    border: 0;
    color: white;
    background: none;
    font-size: 1.6rem;
}
.media-search {
    display: grid;
    gap: 7px;
    padding: 16px;
    color: #8295a1;
    font: 750 0.5rem var(--font-display);
    text-transform: uppercase;
}
.media-search input {
    padding: 12px;
    border: 1px solid #ffffff22;
    color: white;
    background: #050b13;
    font:
        400 0.82rem Arial,
        sans-serif;
}
.media-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 8px;
    padding: 0 16px 18px;
}
.media-grid button {
    min-width: 0;
    padding: 0 0 10px;
    overflow: hidden;
    border: 1px solid #ffffff1b;
    color: white;
    background: #050b13;
    text-align: left;
}
.media-grid button.active {
    border-color: var(--color-accent);
}
.media-grid img {
    width: 100%;
    aspect-ratio: 4/3;
    object-fit: cover;
}
.media-grid strong {
    display: block;
    overflow: hidden;
    margin: 8px 9px 0;
    font: 750 0.54rem var(--font-display);
    text-overflow: ellipsis;
    text-transform: uppercase;
    white-space: nowrap;
}
.empty {
    padding: 30px;
    color: #80929f;
    text-align: center;
}
@media (max-width: 700px) {
    .media-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .media-actions {
        flex-direction: column;
    }
}
</style>
