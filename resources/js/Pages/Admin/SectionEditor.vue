<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import MediaGalleryPicker from "@/Components/Admin/MediaGalleryPicker.vue";
import MediaPicker from "@/Components/Admin/MediaPicker.vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
const props = defineProps({
    section: Object,
    fields: Object,
    sources: { type: Array, default: () => [] },
    content: Object,
    publishedContent: Object,
});
const page = usePage(),
    uploadError = ref("");
const initial = {};
Object.entries(props.fields).forEach(
    ([key, field]) =>
        (initial[key] = props.content?.[key] ?? field.default ?? ""),
);
const form = useForm({ content: initial });
function save() {
    form.put(route("admin.sections.update", props.section.id), {
        preserveScroll: true,
    });
}
function isVisible(field) {
    if (field.showWhen)
        return Object.entries(field.showWhen).every(
            ([key, value]) => form.content[key] === value,
        );
    if (field.showWhenAny)
        return field.showWhenAny.some((condition) =>
            Object.entries(condition).every(
                ([key, value]) => form.content[key] === value,
            ),
        );
    return true;
}
const visibleFieldCount = computed(
    () => Object.values(props.fields).filter(isVisible).length,
);
</script>
<template>
    <Head :title="`Edit ${section.label}`" /><AuthenticatedLayout
        ><template #page-title>Edit Section</template>
        <div class="section-editor">
            <div v-if="page.props.flash?.success" class="notice">
                {{ page.props.flash.success }}
            </div>
            <header class="editor-hero">
                <div>
                    <span>Konten Halaman</span>
                    <h1>{{ section.label }}<b>.</b></h1>
                    <p>{{ section.page.label }} · Atur informasi yang dapat diedit pada section ini</p>
                </div>
                <div class="hero-actions">
                    <Link
                        :href="
                            route('admin.pages.index', section.page.page_key)
                        "
                        >Kembali</Link
                    ><a :href="section.page.path" target="_blank"
                        >Lihat Publik ↗</a
                    >
                </div>
            </header>
            <form @submit.prevent="save">
                <div class="layout">
                    <main class="form-panel">
                        <header>
                            <div>
                                <span>Konten Section</span>
                                <h2>Edit Konten</h2>
                            </div>
                            <small
                                >{{ visibleFieldCount }} field tersedia</small
                            >
                        </header>
                        <div class="fields">
                            <label
                                v-for="(field, key) in fields"
                                v-show="isVisible(field)"
                                :key="key"
                                :class="[
                                    'field',
                                    {
                                        full: [
                                            'textarea',
                                            'gallery',
                                            'image',
                                            'video',
                                            'boolean',
                                        ].includes(field.type),
                                    },
                                ]"
                                ><span>{{ field.label }}</span>
                                <textarea
                                    v-if="field.type === 'textarea'"
                                    v-model="form.content[key]"
                                    rows="5"
                                    :maxlength="field.max"
                                />
                                <MediaGalleryPicker
                                    v-else-if="field.type === 'gallery'"
                                    v-model="form.content[key]"
                                    :max="field.max || 6"
                                />
                                <div
                                    v-else-if="field.type === 'image'"
                                    class="image-field"
                                >
                                    <input
                                        v-model="form.content[key]"
                                        placeholder="/storage/uploads/..."
                                    /><MediaPicker
                                        v-model="form.content[key]"
                                        folder="general"
                                    />
                                </div>
                                <div
                                    v-else-if="field.type === 'video'"
                                    class="image-field"
                                >
                                    <input
                                        v-model="form.content[key]"
                                        placeholder="/storage/uploads/general/video.mp4"
                                    /><MediaPicker
                                        v-model="form.content[key]"
                                        folder="general"
                                        media-type="video"
                                        label="Pilih Video dari Media Library"
                                    />
                                </div>
                                <select
                                    v-else-if="field.type === 'select'"
                                    v-model="form.content[key]"
                                >
                                    <option
                                        v-for="(label, value) in field.options"
                                        :key="value"
                                        :value="value"
                                    >
                                        {{ label }}
                                    </option>
                                </select>
                                <span
                                    v-else-if="field.type === 'boolean'"
                                    class="boolean-control"
                                    ><input
                                        v-model="form.content[key]"
                                        type="checkbox"
                                    /><i aria-hidden="true"></i
                                    ><b>{{
                                        form.content[key]
                                            ? "Ditampilkan"
                                            : "Disembunyikan"
                                    }}</b></span
                                >
                                <input
                                    v-else
                                    v-model="form.content[key]"
                                    :type="
                                        field.type === 'number'
                                            ? 'number'
                                            : 'text'
                                    "
                                    :min="field.min"
                                    :max="field.max"
                                />
                                <small v-if="field.help">{{ field.help }}</small
                                ><small
                                    v-if="form.errors[`content.${key}`]"
                                    class="error"
                                    >{{ form.errors[`content.${key}`] }}</small
                                ></label
                            >
                        </div>
                        <div v-if="form.errors.content" class="form-error">
                            {{ form.errors.content }}
                        </div>
                        <section v-if="sources.length" class="data-sources">
                            <header>
                                <span>Sumber Data Otomatis</span>
                                <h2>Data dikelola dari modul utama</h2>
                                <p>Informasi berikut tidak perlu diketik ulang di editor section.</p>
                            </header>
                            <article v-for="source in sources" :key="`${source.route}-${source.label}`">
                                <div><strong>{{ source.label }}</strong><small>{{ source.description }}</small></div>
                                <Link :href="route(source.route)">Kelola Data →</Link>
                            </article>
                        </section>
                    </main>
                    <aside class="side-panel">
                        <section>
                            <span>Status Perubahan</span>
                            <h3>Belum Dipublikasikan</h3>
                            <p>
                                Simpan perubahan sebagai draft, lalu gunakan
                                Preview Draft dari halaman Landing Page sebelum
                                publish.
                            </p>
                        </section>
                        <section>
                            <span>Cara Pengelolaan</span>
                            <h3>Konten &amp; data terpisah</h3>
                            <p>Gunakan halaman ini untuk teks dan visual. Data pertandingan, pemain, klasemen, serta sponsor dikelola melalui modul utama.</p>
                        </section>
                        <section v-if="uploadError" class="upload-error">
                            {{ uploadError }}
                        </section>
                    </aside>
                </div>
                <footer class="savebar">
                    <div>
                        <strong>Perubahan disimpan sebagai draft</strong
                        ><small
                            >Website publik tidak berubah sampai halaman
                            dipublikasikan.</small
                        >
                    </div>
                    <button :disabled="form.processing">
                        {{
                            form.processing
                                ? "Menyimpan..."
                                : "Simpan Draft Konten"
                        }}
                    </button>
                </footer>
            </form>
        </div></AuthenticatedLayout
    >
</template>
<style scoped>
.section-editor {
    max-width: 1400px;
    margin: auto;
}
.notice {
    margin-bottom: 14px;
    padding: 13px 16px;
    border: 1px solid #45b7ff66;
    color: #b8e3ff;
    background: #45b7ff12;
    font-size: 0.72rem;
}
.editor-hero {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 25px;
    min-height: 190px;
    padding: 32px 37px;
    border: 1px solid #afdaff24;
    background:
        linear-gradient(100deg, #07111efa, #07111ebd),
        url("/Assets/Dokumentasi/Docs-wahyu.jpeg") center/cover;
}
.editor-hero span,
.form-panel > header span,
.side-panel section > span {
    color: var(--color-accent);
    font: 800 0.55rem var(--font-display);
    letter-spacing: 0.18em;
    text-transform: uppercase;
}
.editor-hero h1 {
    margin: 10px 0 7px;
    font: 900 clamp(2.8rem, 5vw, 5rem) / 0.84 var(--font-display);
    text-transform: uppercase;
}
.editor-hero h1 b {
    color: var(--color-accent);
}
.editor-hero p {
    margin: 0;
    color: #82929e;
    font-size: 0.64rem;
}
.hero-actions {
    display: flex;
    gap: 8px;
}
.hero-actions a {
    padding: 11px 14px;
    border: 1px solid #ffffff2b;
    border-radius: 3px;
    color: white;
    font: 750 0.55rem var(--font-display);
    letter-spacing: 0.08em;
    text-decoration: none;
    text-transform: uppercase;
}
.hero-actions a:hover {
    border-color: var(--color-accent);
    color: var(--color-accent);
}
.layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 290px;
    gap: 14px;
    margin-top: 14px;
}
.form-panel,
.side-panel section,
.data-sources {
    border: 1px solid #afdaff20;
    background: #07111e;
}
.data-sources {
    margin: 0 22px 22px;
    padding: 18px;
}
.data-sources header {
    margin-bottom: 12px;
}
.data-sources header span {
    color: var(--color-accent);
    font: 800 0.55rem var(--font-display);
    letter-spacing: 0.14em;
    text-transform: uppercase;
}
.data-sources h2 {
    margin: 6px 0;
    font: 800 1rem var(--font-display);
    text-transform: uppercase;
}
.data-sources header p {
    margin: 0;
    color: #71838f;
    font-size: 0.62rem;
}
.data-sources article {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 13px 0;
    border-top: 1px solid #ffffff14;
}
.data-sources strong,
.data-sources small {
    display: block;
}
.data-sources strong {
    font: 750 0.62rem var(--font-display);
    text-transform: uppercase;
}
.data-sources small {
    margin-top: 4px;
    color: #71838f;
    font-size: 0.58rem;
}
.data-sources a {
    flex: 0 0 auto;
    padding: 9px 11px;
    border: 1px solid #45b7ff55;
    color: #9ad8ff;
    font: 750 0.52rem var(--font-display);
    text-decoration: none;
    text-transform: uppercase;
}
.form-panel > header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 19px 22px;
    border-bottom: 1px solid #ffffff17;
}
.form-panel h2 {
    margin: 5px 0 0;
    font: 850 1.3rem var(--font-display);
    text-transform: uppercase;
}
.form-panel header small {
    color: #697b88;
    font-size: 0.55rem;
}
.fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
    padding: 22px;
}
.field {
    display: block;
    color: #91a1ad;
    font: 750 0.56rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.field.full {
    grid-column: 1/-1;
}
.field > span {
    display: flex;
    justify-content: space-between;
}
.field i {
    color: #526673;
    font: 500 0.5rem Consolas;
    font-style: normal;
    text-transform: none;
}
.field > input,
.field > textarea,
.field > select,
.image-field > input {
    width: 100%;
    box-sizing: border-box;
    margin-top: 8px;
    padding: 12px;
    border: 1px solid #ffffff24;
    border-radius: 3px;
    outline: 0;
    color: white;
    background: #050b13;
    font: 500 0.72rem Arial;
    letter-spacing: 0;
    text-transform: none;
}
.field input:focus,
.field textarea:focus,
.field select:focus {
    border-color: #45b7ff99;
}
.field textarea {
    resize: vertical;
    line-height: 1.6;
}
.field > small {
    display: block;
    margin-top: 6px;
    color: #637582;
    font: 500 0.54rem Arial;
    letter-spacing: 0;
    text-transform: none;
}
.field > small.error,
.form-error,
.upload-error {
    color: #ff9299;
}
.boolean-control {
    display: flex !important;
    align-items: center;
    justify-content: flex-start !important;
    gap: 10px;
    margin-top: 9px;
    padding: 13px;
    border: 1px solid #ffffff20;
    background: #050b13;
    cursor: pointer;
}
.boolean-control > input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
}
.boolean-control > i {
    position: relative;
    width: 38px;
    height: 21px;
    border: 1px solid #536774;
    border-radius: 20px;
    background: #0a1722;
}
.boolean-control > i::after {
    content: "";
    position: absolute;
    top: 3px;
    left: 3px;
    width: 13px;
    height: 13px;
    border-radius: 50%;
    background: #718491;
    transition: 0.2s;
}
.boolean-control > input:checked + i {
    border-color: #45b7ff;
    background: #45b7ff2b;
}
.boolean-control > input:checked + i::after {
    background: #45b7ff;
    transform: translateX(17px);
}
.boolean-control > b {
    color: #9fb0bb;
    font: 700 0.55rem var(--font-display);
    text-transform: uppercase;
}
.image-tools {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}
.upload,
.image-tools select {
    padding: 9px 11px;
    border: 1px solid #45b7ff55;
    border-radius: 3px;
    color: #8fd3ff;
    background: #06101b;
    font: 750 0.52rem var(--font-display);
    text-transform: uppercase;
    cursor: pointer;
}
.upload input {
    display: none;
}
.image-field img,
.image-field video {
    width: 100%;
    height: 190px;
    margin-top: 10px;
    border: 1px solid #ffffff1f;
    object-fit: cover;
}
.side-panel {
    display: grid;
    align-content: start;
    gap: 12px;
}
.side-panel section {
    padding: 19px;
}
.side-panel h3 {
    margin: 8px 0;
    font: 850 0.9rem var(--font-display);
    text-transform: uppercase;
}
.side-panel p {
    margin: 0;
    color: #71838f;
    font-size: 0.62rem;
    line-height: 1.6;
}
.side-panel dl {
    margin: 10px 0 0;
}
.side-panel dl div {
    padding: 10px 0;
    border-top: 1px solid #ffffff12;
}
.side-panel dt {
    color: #607380;
    font-size: 0.52rem;
    text-transform: uppercase;
}
.side-panel dd {
    margin: 5px 0 0;
    color: #bac6cf;
    font: 600 0.58rem Consolas;
    overflow-wrap: anywhere;
}
.savebar {
    position: sticky;
    z-index: 10;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 14px;
    padding: 15px 20px;
    border: 1px solid #45b7ff38;
    background: #07111ef2;
    backdrop-filter: blur(10px);
}
.savebar strong,
.savebar small {
    display: block;
}
.savebar strong {
    font: 750 0.6rem var(--font-display);
    text-transform: uppercase;
}
.savebar small {
    margin-top: 4px;
    color: #6d7f8c;
    font-size: 0.54rem;
}
.savebar button {
    padding: 13px 18px;
    border: 0;
    border-radius: 3px;
    color: #03101b;
    background: var(--color-accent);
    font: 850 0.58rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
    cursor: pointer;
}
.savebar button:disabled {
    opacity: 0.45;
}
@media (max-width: 900px) {
    .layout {
        grid-template-columns: 1fr;
    }
    .side-panel {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 600px) {
    .editor-hero {
        align-items: flex-start;
        flex-direction: column;
        padding: 26px 21px;
    }
    .hero-actions {
        width: 100%;
    }
    .hero-actions a {
        flex: 1;
        text-align: center;
    }
    .fields {
        grid-template-columns: 1fr;
        padding: 17px;
    }
    .data-sources {
        margin: 0 17px 17px;
    }
    .data-sources article {
        align-items: flex-start;
        flex-direction: column;
    }
    .field.full {
        grid-column: auto;
    }
    .side-panel {
        grid-template-columns: 1fr;
    }
    .savebar {
        align-items: stretch;
        flex-direction: column;
        gap: 13px;
    }
    .savebar button {
        width: 100%;
    }
    .image-tools {
        flex-direction: column;
    }
}
</style>
