<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { computed, onMounted, ref } from "vue";

const props = defineProps({
    media: Object,
    filters: Object,
    stats: Object,
    can: Object,
});
const page = usePage();
const selected = ref(null),
    uploading = ref(false),
    uploadMessage = ref(""),
    dragActive = ref(false);
const filter = ref({
    search: props.filters.search || "",
    folder: props.filters.folder || "",
    status: props.filters.status || "",
    sort: props.filters.sort || "newest",
});
const editForm = useForm({
    title: "",
    alt_text: "",
    caption: "",
    folder: "general",
    status: "active",
    tags: [],
});
const tagInput = ref("");
const formattedSize = (bytes) =>
    bytes > 1048576
        ? `${(bytes / 1048576).toFixed(1)} MB`
        : `${Math.max(1, Math.round(bytes / 1024))} KB`;
const totalSize = computed(() => formattedSize(props.stats.size));
onMounted(() => {
    if (
        new URLSearchParams(window.location.search).get("action") ===
            "upload" &&
        props.can?.upload
    ) {
        document
            .querySelector("#upload-media")
            ?.scrollIntoView({ behavior: "smooth", block: "center" });
    }
});
function applyFilters() {
    router.get(route("admin.media.index"), filter.value, {
        preserveState: true,
        replace: true,
    });
}
function resetFilters() {
    filter.value = { search: "", folder: "", status: "", sort: "newest" };
    applyFilters();
}
function open(item) {
    selected.value = item;
    editForm.defaults({
        title: item.title,
        alt_text: item.alt_text || "",
        caption: item.caption || "",
        folder: item.folder,
        status: item.status,
        tags: [...item.tags],
    });
    editForm.reset();
    tagInput.value = item.tags.join(", ");
}
function close() {
    selected.value = null;
    editForm.clearErrors();
}
function save() {
    editForm.tags = tagInput.value
        .split(",")
        .map((v) => v.trim())
        .filter(Boolean);
    editForm.put(route("admin.media.update", selected.value.id), {
        preserveScroll: true,
        onSuccess: () => close(),
    });
}
function remove() {
    if (!confirm(`Pindahkan "${selected.value.title}" ke sampah?`)) return;
    router.delete(route("admin.media.destroy", selected.value.id), {
        preserveScroll: true,
        onSuccess: () => close(),
    });
}
function restore() {
    router.post(
        route("admin.media.restore", selected.value.id),
        {},
        { preserveScroll: true, onSuccess: () => close() },
    );
}
async function uploadFiles(files) {
    if (!files?.length) return;
    uploading.value = true;
    uploadMessage.value = "";
    let uploaded = 0,
        duplicate = 0;
    for (const file of [...files]) {
        const data = new FormData();
        data.append("photo", file);
        data.append("folder", filter.value.folder || "general");
        try {
            const response = await axios.post(
                route("admin.media.store"),
                data,
                { headers: { "Content-Type": "multipart/form-data" } },
            );
            response.data.duplicate ? duplicate++ : uploaded++;
        } catch (error) {
            uploadMessage.value =
                error.response?.data?.message ||
                `Gagal mengunggah ${file.name}`;
        }
    }
    uploading.value = false;
    dragActive.value = false;
    if (!uploadMessage.value)
        uploadMessage.value = `${uploaded} file diunggah${duplicate ? `, ${duplicate} duplikat digunakan kembali` : ""}.`;
    router.reload({ only: ["media", "stats"] });
}
</script>

<template>
    <Head title="Media Library" /><AuthenticatedLayout
        ><template #page-title>Media Library</template>
        <div class="media-page">
            <div v-if="page.props.flash?.success" class="notice">
                {{ page.props.flash.success }}
            </div>
            <div v-if="page.props.errors?.media" class="error-notice">
                {{ page.props.errors.media }}
            </div>
            <header class="media-hero">
                <div>
                    <span>Asset Management</span>
                    <h1>Media <b>Library.</b></h1>
                    <p>Kelola foto dan video COSMIC dari satu tempat.</p>
                </div>
                <label v-if="can.upload" class="upload-button"
                    ><input
                        type="file"
                        multiple
                        accept="image/jpeg,image/png,image/webp,video/mp4,video/webm"
                        @change="uploadFiles($event.target.files)"
                    />{{
                        uploading ? "Mengunggah..." : "+ Upload Media"
                    }}</label
                >
            </header>
            <section class="stats">
                <article>
                    <strong>{{ stats.total }}</strong
                    ><span>Total Media</span>
                </article>
                <article>
                    <strong>{{ totalSize }}</strong
                    ><span>Penyimpanan</span>
                </article>
                <article>
                    <strong>{{ stats.unused }}</strong
                    ><span>Belum Digunakan</span>
                </article>
                <article>
                    <strong>{{ stats.trashed }}</strong
                    ><span>Dalam Sampah</span>
                </article>
            </section>
            <form class="toolbar" @submit.prevent="applyFilters">
                <input
                    v-model="filter.search"
                    placeholder="Cari nama, judul, atau alt text..."
                /><select v-model="filter.folder">
                    <option value="">Semua Folder</option>
                    <option value="general">General</option>
                    <option value="players">Players</option>
                    <option value="staff">Staff &amp; Officials</option>
                    <option value="clubs">Klub &amp; Lawan</option>
                    <option value="competitions">Kompetisi</option>
                    <option value="matches">Matches</option>
                    <option value="sponsors">Sponsors</option></select
                ><select v-model="filter.status">
                    <option value="">Aktif</option>
                    <option value="archived">Arsip</option>
                    <option value="trashed">Sampah</option></select
                ><select v-model="filter.sort">
                    <option value="newest">Terbaru</option>
                    <option value="oldest">Terlama</option>
                    <option value="name">Nama</option>
                    <option value="size">Ukuran</option></select
                ><button>Cari</button
                ><button type="button" class="reset" @click="resetFilters">
                    Reset
                </button>
            </form>
            <div v-if="uploadMessage" class="upload-message">
                {{ uploadMessage }}
            </div>
            <label
                v-if="can.upload"
                id="upload-media"
                class="dropzone"
                :class="{ active: dragActive }"
                @dragenter.prevent="dragActive = true"
                @dragover.prevent
                @dragleave.prevent="dragActive = false"
                @drop.prevent="uploadFiles($event.dataTransfer.files)"
                ><input
                    type="file"
                    multiple
                    accept="image/jpeg,image/png,image/webp,video/mp4,video/webm"
                    @change="uploadFiles($event.target.files)"
                /><strong
                    >Tarik foto atau video ke sini, atau klik untuk
                    memilih</strong
                ><small>JPG, PNG, WebP · maksimum 5 MB per file</small></label
            >
            <section v-if="media.data.length" class="media-grid">
                <button
                    v-for="item in media.data"
                    :key="item.id"
                    type="button"
                    class="media-card"
                    @click="open(item)"
                >
                    <span class="media-thumb"
                        ><video
                            v-if="item.mime_type?.startsWith('video/')"
                            :src="item.url"
                            muted
                            preload="metadata"
                        ></video
                        ><img
                            v-else
                            :src="item.url"
                            :alt="item.alt_text || item.title"
                            loading="lazy"
                        /><i v-if="item.deleted_at">Sampah</i
                        ><i v-else-if="item.status === 'archived'"
                            >Arsip</i
                        ></span
                    ><span class="media-copy"
                        ><strong>{{ item.title }}</strong
                        ><small
                            >{{ item.folder }} ·
                            {{ formattedSize(item.size_bytes) }}</small
                        ><em
                            >{{ item.width || "–" }} ×
                            {{ item.height || "–" }} ·
                            {{ item.usage_count }} pemakaian</em
                        ></span
                    >
                </button>
            </section>
            <section v-else class="empty">
                <strong>Belum ada media ditemukan.</strong>
                <p>Ubah filter atau unggah aset pertama ke Media Library.</p>
            </section>
            <nav v-if="media.links?.length > 3" class="pagination">
                <Link
                    v-for="link in media.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    :class="{ active: link.active, disabled: !link.url }"
                    preserve-scroll
                    v-html="link.label"
                />
            </nav>

            <div v-if="selected" class="drawer-wrap" @click.self="close">
                <aside class="drawer">
                    <header>
                        <div>
                            <span>Media Detail</span>
                            <h2>{{ selected.title }}</h2>
                        </div>
                        <button type="button" @click="close">×</button>
                    </header>
                    <video
                        v-if="selected.mime_type?.startsWith('video/')"
                        class="drawer-preview"
                        :src="selected.url"
                        controls
                        preload="metadata"
                    ></video>
                    <img
                        v-else
                        class="drawer-preview"
                        :src="selected.url"
                        :alt="selected.alt_text || selected.title"
                    />
                    <div class="facts">
                        <span>{{ selected.mime_type }}</span
                        ><span>{{ formattedSize(selected.size_bytes) }}</span
                        ><span
                            >{{ selected.width }} × {{ selected.height }}</span
                        ><span>{{ selected.usage_count }} pemakaian</span>
                    </div>
                    <form @submit.prevent="save">
                        <label
                            >Judul<input
                                v-model="editForm.title"
                                :disabled="!can.update" /></label
                        ><label
                            >Alt text<input
                                v-model="editForm.alt_text"
                                :disabled="!can.update"
                                placeholder="Jelaskan isi gambar" /></label
                        ><label
                            >Caption<textarea
                                v-model="editForm.caption"
                                :disabled="!can.update"
                                rows="3"
                            />
                        </label>
                        <div class="two">
                            <label
                                >Folder<select
                                    v-model="editForm.folder"
                                    :disabled="!can.update"
                                >
                                    <option>general</option>
                                    <option>players</option>
                                    <option>staff</option>
                                    <option>clubs</option>
                                    <option>competitions</option>
                                    <option>matches</option>
                                    <option>sponsors</option>
                                </select></label
                            ><label
                                >Status<select
                                    v-model="editForm.status"
                                    :disabled="!can.update"
                                >
                                    <option>active</option>
                                    <option>archived</option>
                                </select></label
                            >
                        </div>
                        <label
                            >Tag<input
                                v-model="tagInput"
                                :disabled="!can.update"
                                placeholder="hero, matchday, squad"
                            /><small>Pisahkan dengan koma.</small></label
                        >
                        <p class="meta">
                            Diunggah {{ selected.created_at }} oleh
                            {{ selected.uploader || "Sistem" }}<br />File asli:
                            {{ selected.name }}
                        </p>
                        <footer>
                            <button
                                v-if="can.delete && !selected.deleted_at"
                                type="button"
                                class="danger"
                                :disabled="selected.usage_count > 0"
                                @click="remove"
                            >
                                {{
                                    selected.usage_count
                                        ? "Sedang Dipakai"
                                        : "Hapus"
                                }}</button
                            ><button
                                v-if="can.restore && selected.deleted_at"
                                type="button"
                                class="restore"
                                @click="restore"
                            >
                                Pulihkan</button
                            ><button
                                v-if="can.update && !selected.deleted_at"
                                :disabled="editForm.processing"
                            >
                                Simpan Metadata
                            </button>
                        </footer>
                    </form>
                </aside>
            </div>
        </div></AuthenticatedLayout
    >
</template>

<style scoped>
.media-page {
    max-width: 1500px;
    margin: auto;
}
.notice,
.error-notice,
.upload-message {
    margin-bottom: 14px;
    padding: 12px 15px;
    border: 1px solid #45b7ff55;
    color: #b8e3ff;
    background: #45b7ff10;
    font-size: 0.7rem;
}
.error-notice {
    border-color: #ff7d8655;
    color: #ffadb3;
    background: #ff7d8610;
}
.media-hero {
    min-height: 190px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    padding: 34px 38px;
    border: 1px solid #afdaff24;
    background:
        linear-gradient(100deg, #07111ef8, #07111ebb),
        url("/Assets/Dokumentasi/Docs-wahyu.jpeg") center/cover;
}
.media-hero span {
    color: var(--color-accent);
    font: 800 0.56rem var(--font-display);
    letter-spacing: 0.18em;
    text-transform: uppercase;
}
.media-hero h1 {
    margin: 9px 0 7px;
    font: 900 clamp(3rem, 5vw, 5rem) / 0.84 var(--font-display);
    text-transform: uppercase;
}
.media-hero h1 b {
    color: var(--color-accent);
}
.media-hero p {
    margin: 0;
    color: #8a9ba7;
    font-size: 0.68rem;
}
.upload-button,
.toolbar button {
    padding: 13px 17px;
    border: 1px solid var(--color-accent);
    border-radius: 3px;
    color: #03101b;
    background: var(--color-accent);
    font: 800 0.56rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
    cursor: pointer;
}
.upload-button input,
.dropzone input {
    display: none;
}
.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin: 14px 0;
}
.stats article {
    padding: 18px 20px;
    border: 1px solid #afdaff1f;
    background: #07111e;
}
.stats strong,
.stats span {
    display: block;
}
.stats strong {
    font: 900 1.7rem var(--font-display);
}
.stats span {
    margin-top: 4px;
    color: #6f818e;
    font: 700 0.52rem var(--font-display);
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.toolbar {
    display: grid;
    grid-template-columns: minmax(220px, 1fr) repeat(3, 150px) auto auto;
    gap: 8px;
    margin-bottom: 12px;
}
.toolbar input,
.toolbar select,
.drawer input,
.drawer textarea,
.drawer select {
    width: 100%;
    box-sizing: border-box;
    padding: 12px;
    border: 1px solid #ffffff22;
    border-radius: 3px;
    outline: none;
    color: white;
    background: #07111e;
}
.toolbar .reset {
    color: #9aabb7;
    border-color: #ffffff20;
    background: transparent;
}
.dropzone {
    min-height: 82px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 5px;
    margin-bottom: 14px;
    border: 1px dashed #45b7ff55;
    color: #a9dfff;
    background: #45b7ff08;
    cursor: pointer;
    transition: 0.2s;
}
.dropzone.active {
    border-color: var(--color-accent);
    background: #45b7ff18;
}
.dropzone strong {
    font: 750 0.63rem var(--font-display);
    text-transform: uppercase;
}
.dropzone small {
    color: #657986;
}
.media-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 11px;
}
.media-card {
    min-width: 0;
    padding: 0;
    overflow: hidden;
    border: 1px solid #afdaff1e;
    color: white;
    background: #07111e;
    text-align: left;
    cursor: pointer;
    transition: 0.2s;
}
.media-card:hover {
    border-color: #45b7ff77;
    transform: translateY(-3px);
}
.media-thumb {
    position: relative;
    display: block;
    aspect-ratio: 4/3;
    overflow: hidden;
    background: #02060b;
}
.media-thumb img,
.media-thumb video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.35s;
}
.media-card:hover img,
.media-card:hover video {
    transform: scale(1.035);
}
.media-thumb i {
    position: absolute;
    top: 8px;
    right: 8px;
    padding: 5px 7px;
    color: #fff;
    background: #07111ed9;
    font: 700 0.48rem var(--font-display);
    font-style: normal;
    text-transform: uppercase;
}
.media-copy {
    display: block;
    padding: 12px;
}
.media-copy strong,
.media-copy small,
.media-copy em {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.media-copy strong {
    font: 750 0.66rem var(--font-display);
    text-transform: uppercase;
}
.media-copy small {
    margin-top: 6px;
    color: var(--color-accent);
    font-size: 0.52rem;
    text-transform: uppercase;
}
.media-copy em {
    margin-top: 5px;
    color: #647784;
    font-size: 0.5rem;
    font-style: normal;
}
.empty {
    padding: 60px;
    border: 1px solid #ffffff17;
    background: #07111e;
    text-align: center;
}
.empty strong {
    font: 850 1.2rem var(--font-display);
    text-transform: uppercase;
}
.empty p {
    color: #71828f;
}
.pagination {
    display: flex;
    justify-content: center;
    gap: 5px;
    margin-top: 20px;
}
.pagination a {
    min-width: 36px;
    padding: 9px;
    border: 1px solid #ffffff20;
    color: #91a2ae;
    text-align: center;
    text-decoration: none;
}
.pagination a.active {
    color: #04101a;
    border-color: var(--color-accent);
    background: var(--color-accent);
}
.pagination a.disabled {
    pointer-events: none;
    opacity: 0.3;
}
.drawer-wrap {
    position: fixed;
    z-index: 100;
    inset: 0;
    background: #01050ad6;
    backdrop-filter: blur(4px);
}
.drawer {
    position: absolute;
    top: 0;
    right: 0;
    width: min(500px, 100%);
    height: 100%;
    overflow: auto;
    border-left: 1px solid #45b7ff44;
    background: #07111e;
    box-shadow: -30px 0 80px #000;
}
.drawer > header {
    position: sticky;
    z-index: 2;
    top: 0;
    display: flex;
    justify-content: space-between;
    padding: 20px 22px;
    border-bottom: 1px solid #ffffff18;
    background: #07111ef5;
}
.drawer header span {
    color: var(--color-accent);
    font: 750 0.52rem var(--font-display);
    letter-spacing: 0.14em;
    text-transform: uppercase;
}
.drawer h2 {
    margin: 6px 0 0;
    font: 850 1.2rem var(--font-display);
    text-transform: uppercase;
}
.drawer header button {
    border: 0;
    color: #9baab5;
    background: none;
    font-size: 1.7rem;
}
.drawer-preview {
    width: 100%;
    height: 290px;
    object-fit: contain;
    background: #02060b;
}
.facts {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    border-bottom: 1px solid #ffffff18;
}
.facts span {
    padding: 11px 5px;
    border-right: 1px solid #ffffff14;
    color: #778995;
    font-size: 0.52rem;
    text-align: center;
}
.drawer form {
    display: grid;
    gap: 14px;
    padding: 22px;
}
.drawer label {
    color: #91a1ad;
    font: 750 0.54rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.drawer label > * {
    margin-top: 7px;
}
.drawer label small {
    display: block;
    color: #5e7180;
    text-transform: none;
}
.drawer .two {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.meta {
    padding: 12px;
    border: 1px solid #ffffff12;
    color: #637583;
    font-size: 0.56rem;
    line-height: 1.7;
}
.drawer footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
}
.drawer footer button {
    padding: 12px 14px;
    border: 1px solid var(--color-accent);
    color: #03101b;
    background: var(--color-accent);
    font: 800 0.53rem var(--font-display);
    text-transform: uppercase;
}
.drawer footer .danger {
    margin-right: auto;
    color: #ff9aa1;
    border-color: #ff7d8644;
    background: transparent;
}
.drawer footer .restore {
    margin-right: auto;
}
.drawer footer button:disabled {
    opacity: 0.4;
}
.upload-message {
    margin-top: 0;
}
@media (max-width: 1100px) {
    .media-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .toolbar {
        grid-template-columns: 1fr 1fr 1fr;
    }
    .toolbar input {
        grid-column: 1/-1;
    }
}
@media (max-width: 700px) {
    .media-hero {
        align-items: flex-start;
        flex-direction: column;
        justify-content: flex-end;
        padding: 26px 22px;
    }
    .upload-button {
        width: 100%;
        text-align: center;
    }
    .stats {
        grid-template-columns: 1fr 1fr;
    }
    .toolbar {
        grid-template-columns: 1fr 1fr;
    }
    .toolbar input {
        grid-column: 1/-1;
    }
    .media-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 480px) {
    .media-grid {
        grid-template-columns: 1fr 1fr;
        gap: 7px;
    }
    .media-copy {
        padding: 9px;
    }
    .stats article {
        padding: 14px;
    }
    .toolbar {
        grid-template-columns: 1fr;
    }
    .toolbar input {
        grid-column: auto;
    }
    .dropzone {
        padding: 15px;
        text-align: center;
    }
    .drawer-preview {
        height: 235px;
    }
    .facts {
        grid-template-columns: 1fr 1fr;
    }
    .drawer .two {
        grid-template-columns: 1fr;
    }
}
</style>
