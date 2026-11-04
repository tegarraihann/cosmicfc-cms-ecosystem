<script setup>
import MediaPicker from "@/Components/Admin/MediaPicker.vue";
import { computed, ref } from "vue";

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    max: { type: Number, default: 6 },
});
const emit = defineEmits(["update:modelValue"]);
const pickerKey = ref(0);
const items = computed(() =>
    (props.modelValue || []).map((item) =>
        typeof item === "string"
            ? { src: item, alt: "", focalPoint: "center" }
            : { focalPoint: "center", ...item },
    ),
);

function update(next) {
    emit("update:modelValue", next);
}
function add(item) {
    if (!item?.url || items.value.length >= props.max) return;
    if (items.value.some((entry) => entry.src === item.url)) return;
    update([
        ...items.value,
        {
            src: item.url,
            alt: item.alt_text || item.title || "Dokumentasi COSMIC",
            focalPoint: "center",
        },
    ]);
    pickerKey.value += 1;
}
function patch(index, key, value) {
    update(items.value.map((item, position) =>
        position === index ? { ...item, [key]: value } : item,
    ));
}
function remove(index) {
    update(items.value.filter((_, position) => position !== index));
}
function move(index, direction) {
    const target = index + direction;
    if (target < 0 || target >= items.value.length) return;
    const next = [...items.value];
    [next[index], next[target]] = [next[target], next[index]];
    update(next);
}
</script>

<template>
    <div class="gallery-picker">
        <div v-if="items.length" class="gallery-list">
            <article v-for="(item, index) in items" :key="`${item.src}-${index}`">
                <img :src="item.src" :alt="item.alt || `Gambar galeri ${index + 1}`" />
                <div class="gallery-copy">
                    <strong>Gambar {{ index + 1 }}</strong>
                    <label><span>Teks alternatif</span><input :value="item.alt" maxlength="255" placeholder="Jelaskan isi gambar secara singkat" @input="patch(index, 'alt', $event.target.value)" /></label>
                    <label><span>Fokus gambar</span><select :value="item.focalPoint" @change="patch(index, 'focalPoint', $event.target.value)"><option value="left center">Kiri</option><option value="center">Tengah</option><option value="right center">Kanan</option></select></label>
                </div>
                <div class="gallery-actions">
                    <button type="button" :disabled="index === 0" title="Pindahkan ke atas" @click="move(index, -1)">↑</button>
                    <button type="button" :disabled="index === items.length - 1" title="Pindahkan ke bawah" @click="move(index, 1)">↓</button>
                    <button type="button" class="remove" title="Hapus gambar" @click="remove(index)">×</button>
                </div>
            </article>
        </div>
        <p v-else class="empty">Belum ada gambar galeri yang dipilih.</p>
        <div v-if="items.length < max" class="add-media">
            <MediaPicker :key="pickerKey" model-value="" folder="general" label="Tambah dari Media Library" @selected="add" />
            <small>{{ items.length }} dari maksimal {{ max }} gambar</small>
        </div>
    </div>
</template>

<style scoped>
.gallery-picker{margin-top:9px;padding:14px;border:1px solid #ffffff20;background:#050b13}.gallery-list{display:grid;gap:10px}.gallery-list article{display:grid;grid-template-columns:135px minmax(0,1fr) auto;gap:13px;align-items:start;padding:10px;border:1px solid #ffffff17;background:#08131f}.gallery-list img{width:135px;height:105px;object-fit:cover}.gallery-copy{display:grid;grid-template-columns:1fr 150px;gap:9px;min-width:0}.gallery-copy strong{grid-column:1/-1;color:#eef6fb;font:800 .58rem var(--font-display);text-transform:uppercase}.gallery-copy label{display:grid;gap:5px}.gallery-copy span{color:#718593;font:600 .49rem Arial;letter-spacing:0;text-transform:none}.gallery-copy input,.gallery-copy select{min-width:0;padding:9px;border:1px solid #ffffff20;color:white;background:#040a11;font:500 .65rem Arial}.gallery-actions{display:flex;gap:4px}.gallery-actions button{width:30px;height:30px;border:1px solid #ffffff20;color:#9dc9e6;background:transparent;cursor:pointer}.gallery-actions button:disabled{opacity:.25;cursor:not-allowed}.gallery-actions .remove{color:#ff9299}.empty{margin:0;padding:18px;color:#728592;text-align:center;font:500 .65rem Arial;text-transform:none}.add-media{display:flex;align-items:end;justify-content:space-between;gap:12px;margin-top:12px}.add-media small{color:#6f818e;font:500 .55rem Arial;letter-spacing:0;text-transform:none}@media(max-width:700px){.gallery-list article{grid-template-columns:1fr}.gallery-list img{width:100%;height:180px}.gallery-copy{grid-template-columns:1fr}.gallery-copy strong{grid-column:auto}.gallery-actions{justify-content:flex-end}.add-media{align-items:stretch;flex-direction:column}}
</style>
