<script setup>
import axios from "axios";
import { computed, ref } from "vue";
const props = defineProps({
    modelValue: { type: [String, Number], default: "" },
    folder: { type: String, default: "general" },
    uploadFolder: { type: String, default: "" },
    label: { type: String, default: "Pilih dari Media Library" },
    valueMode: {
        type: String,
        default: "url",
        validator: (value) => ["url", "id"].includes(value),
    },
    previewUrl: { type: String, default: "" },
    canBrowse: { type: Boolean, default: true },
    canUpload: { type: Boolean, default: true },
    mediaType: {
        type: String,
        default: "image",
        validator: (value) => ["image", "video", "all"].includes(value),
    },
});
const emit = defineEmits(["update:modelValue", "selected"]);
const open = ref(false),
    loading = ref(false),
    uploading = ref(false),
    items = ref([]),
    search = ref(""),
    error = ref("");
const selectedItem = computed(() =>
    props.valueMode === "id"
        ? items.value.find((item) => Number(item.id) === Number(props.modelValue))
        : items.value.find((item) => item.url === props.modelValue),
);
const selectedUrl = computed(
    () => selectedItem.value?.url || props.previewUrl || (props.valueMode === "url" ? props.modelValue : ""),
);
const results = computed(() => {
    const q = search.value.toLowerCase();
    return items.value.filter(
        (item) =>
            (!q ||
                `${item.title} ${item.name} ${item.alt_text || ""}`
                    .toLowerCase()
                    .includes(q)) &&
            (!props.folder ||
                item.folder === props.folder ||
                props.folder === "all") &&
            (props.mediaType === "all" ||
                item.mime_type?.startsWith(`${props.mediaType}/`)),
    );
});
async function show() {
    if (!props.canBrowse) return;
    open.value = true;
    if (items.value.length) return;
    loading.value = true;
    try {
        items.value = (await axios.get(route("admin.media.items"))).data.data;
    } finally {
        loading.value = false;
    }
}
function choose(item) {
    emit("update:modelValue", props.valueMode === "id" ? item.id : item.url);
    emit("selected", item);
    error.value = "";
    open.value = false;
}
async function upload(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    uploading.value = true;
    const data = new FormData();
    data.append("photo", file);
    data.append(
        "folder",
        props.uploadFolder || (props.folder === "all" ? "general" : props.folder),
    );
    try {
        const item = (
            await axios.post(route("admin.media.store"), data, {
                headers: { "Content-Type": "multipart/form-data" },
            })
        ).data;
        items.value.unshift(item);
        choose(item);
    } catch (exception) {
        error.value =
            exception.response?.data?.message ||
            "Foto gagal diunggah. Periksa format dan ukuran file.";
    } finally {
        uploading.value = false;
        event.target.value = "";
    }
}
</script>
<template>
    <div class="picker-field">
        <div class="picker-actions">
            <button v-if="canBrowse" type="button" @click="show">{{ label }}</button
            ><label v-if="canUpload"
                ><input
                    type="file"
                    :accept="
                        mediaType === 'video'
                            ? 'video/mp4,video/webm'
                            : 'image/jpeg,image/png,image/webp'
                    "
                    @change="upload"
                />{{ uploading ? "Mengunggah..." : "Upload Baru" }}</label
            >
        </div>
        <p v-if="error" class="picker-error">{{ error }}</p>
        <div v-if="selectedUrl" class="chosen">
            <video
                v-if="mediaType === 'video'"
                :src="selectedUrl"
                controls
                preload="metadata"
            ></video>
            <img v-else :src="selectedUrl" alt="Preview media terpilih" /><button
                type="button"
                @click="emit('update:modelValue', valueMode === 'id' ? null : '')"
            >
                Hapus pilihan
            </button>
        </div>
        <div v-if="open" class="picker-modal" @click.self="open = false">
            <section>
                <header>
                    <div>
                        <span>Media Library</span>
                        <h3>Pilih Media</h3>
                    </div>
                    <button type="button" @click="open = false">×</button>
                </header>
                <input
                    v-model="search"
                    class="search"
                    placeholder="Cari media..."
                />
                <p v-if="loading" class="state">Memuat media...</p>
                <div v-else class="picker-grid">
                    <button
                        v-for="item in results"
                        :key="item.id"
                        type="button"
                        @click="choose(item)"
                    >
                        <video
                            v-if="item.mime_type?.startsWith('video/')"
                            :src="item.url"
                            muted
                            preload="metadata"
                        ></video>
                        <img
                            v-else
                            :src="item.url"
                            :alt="item.alt_text || item.title"
                        /><strong>{{ item.title }}</strong
                        ><small
                            >{{ item.folder }} · {{ item.width }} ×
                            {{ item.height }}</small
                        >
                    </button>
                </div>
                <p v-if="!loading && !results.length" class="state">
                    Tidak ada media yang sesuai.
                </p>
            </section>
        </div>
    </div>
</template>
<style scoped>
.picker-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}
.picker-actions button,
.picker-actions label {
    padding: 10px 12px;
    border: 1px solid #45b7ff55;
    border-radius: 3px;
    color: #9ad8ff;
    background: #06101b;
    font: 750 0.52rem var(--font-display);
    letter-spacing: 0.05em;
    text-transform: uppercase;
    cursor: pointer;
}
.picker-actions input {
    display: none;
}
.picker-error {
    margin: 8px 0 0;
    color: #ff8c96;
    font-size: 0.72rem;
}
.chosen {
    position: relative;
    width: min(300px, 100%);
    margin-top: 10px;
}
.chosen img,
.chosen video {
    width: 100%;
    height: 155px;
    border: 1px solid #ffffff1f;
    object-fit: cover;
}
.chosen button {
    position: absolute;
    right: 7px;
    bottom: 9px;
    padding: 6px 8px;
    border: 0;
    color: #ffadb3;
    background: #07111edb;
    font-size: 0.5rem;
    text-transform: uppercase;
}
.picker-modal {
    position: fixed;
    z-index: 200;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 18px;
    background: #01050ade;
    backdrop-filter: blur(5px);
}
.picker-modal > section {
    width: min(920px, 100%);
    max-height: 88vh;
    overflow: auto;
    border: 1px solid #45b7ff55;
    background: #07111e;
    box-shadow: 0 30px 90px #000;
}
.picker-modal header {
    position: sticky;
    z-index: 2;
    top: 0;
    display: flex;
    justify-content: space-between;
    padding: 18px 20px;
    border-bottom: 1px solid #ffffff18;
    background: #07111efa;
}
.picker-modal header span {
    color: var(--color-accent);
    font: 750 0.5rem var(--font-display);
    letter-spacing: 0.14em;
    text-transform: uppercase;
}
.picker-modal h3 {
    margin: 5px 0 0;
    color: white;
    font: 850 1.2rem var(--font-display);
    text-transform: uppercase;
}
.picker-modal header button {
    border: 0;
    color: white;
    background: none;
    font-size: 1.6rem;
}
.search {
    width: calc(100% - 32px);
    box-sizing: border-box;
    margin: 16px;
    padding: 12px;
    border: 1px solid #ffffff22;
    color: white;
    background: #050b13;
}
.picker-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
    padding: 0 16px 18px;
}
.picker-grid button {
    min-width: 0;
    padding: 0 0 9px;
    overflow: hidden;
    border: 1px solid #ffffff1b;
    color: white;
    background: #050b13;
    text-align: left;
    cursor: pointer;
}
.picker-grid img,
.picker-grid video {
    width: 100%;
    aspect-ratio: 4/3;
    object-fit: cover;
}
.picker-grid strong,
.picker-grid small {
    display: block;
    overflow: hidden;
    margin: 8px 9px 0;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.picker-grid strong {
    font: 750 0.56rem var(--font-display);
    text-transform: uppercase;
}
.picker-grid small {
    color: #637684;
    font-size: 0.48rem;
}
.state {
    padding: 35px;
    color: #80929f;
    text-align: center;
}
@media (max-width: 700px) {
    .picker-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .picker-actions {
        flex-direction: column;
        text-align: center;
    }
}
</style>
