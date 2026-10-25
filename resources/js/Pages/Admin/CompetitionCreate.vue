<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import MediaPicker from "@/Components/Admin/MediaPicker.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({ media: Array, can: Object });
const selectedLogo = ref(null);
const selectedBanner = ref(null);
const form = useForm({
    name: "",
    short_name: "",
    type: "league",
    organizer: "COSMIC",
    description: "",
    logo_media_id: null,
    banner_media_id: null,
    website_url: "",
    status: "active",
    continue_to_season: false,
});

function submit(continueToSeason = false) {
    form.continue_to_season = continueToSeason;
    form.post(route("admin.competitions.store"), { preserveScroll: true });
}
</script>

<template>
    <Head title="Tambah Kompetisi" />
    <AuthenticatedLayout>
        <template #page-title>Tambah Kompetisi</template>
        <main class="page">
            <header class="page-head">
                <div>
                    <span>Kompetisi &amp; Musim</span>
                    <h1>Tambah Kompetisi<span>.</span></h1>
                    <p>Buat identitas kompetisi terlebih dahulu. Slug dan alamat sistem dibuat otomatis.</p>
                </div>
                <Link :href="route('admin.competitions.index')" class="back">Kembali</Link>
            </header>

            <form @submit.prevent="submit(false)" class="editor">
                <section>
                    <header><b>01</b><div><h2>Identitas Kompetisi</h2><p>Informasi utama yang digunakan pada dashboard dan halaman publik.</p></div></header>
                    <div class="fields">
                        <label><span>Nama kompetisi <b>Wajib</b></span><input v-model="form.name" placeholder="Contoh: COSMIC League" /><small>Nama lengkap kompetisi yang akan tampil di website.</small><em v-if="form.errors.name">{{ form.errors.name }}</em></label>
                        <label><span>Singkatan kompetisi <b>Wajib</b></span><input v-model="form.short_name" maxlength="20" placeholder="Contoh: CSL" /><small>Nama pendek untuk kartu dan tabel.</small><em v-if="form.errors.short_name">{{ form.errors.short_name }}</em></label>
                        <label><span>Format kompetisi <b>Wajib</b></span><select v-model="form.type"><option value="league">Liga</option><option value="tournament">Turnamen</option><option value="friendly_series">Seri pertandingan persahabatan</option><option value="single_event">Pertandingan tunggal</option></select><small>Jenis penyelenggaraan kompetisi.</small><em v-if="form.errors.type">{{ form.errors.type }}</em></label>
                        <label><span>Status kompetisi <b>Wajib</b></span><select v-model="form.status"><option value="draft">Draft — belum siap digunakan</option><option value="active">Aktif — dapat digunakan</option><option value="inactive">Nonaktif</option><option value="archived">Diarsipkan</option></select><small>Pilih Aktif agar kompetisi dapat segera digunakan.</small><em v-if="form.errors.status">{{ form.errors.status }}</em></label>
                        <label><span>Penyelenggara <i>Opsional</i></span><input v-model="form.organizer" placeholder="Contoh: COSMIC" /><small>Organisasi atau pihak penyelenggara.</small></label>
                        <label><span>Website kompetisi <i>Opsional</i></span><input v-model="form.website_url" type="url" placeholder="https://contoh.com" /><small>Tautan situs resmi jika tersedia.</small><em v-if="form.errors.website_url">{{ form.errors.website_url }}</em></label>
                        <label class="full"><span>Deskripsi kompetisi <i>Opsional</i></span><textarea v-model="form.description" rows="4" placeholder="Jelaskan tujuan atau informasi penting kompetisi..."></textarea><small>Ringkasan untuk membantu admin dan pengunjung memahami kompetisi.</small></label>
                    </div>
                </section>

                <section>
                    <header><b>02</b><div><h2>Logo dan Banner</h2><p>Pilih dari Media Library atau unggah gambar baru.</p></div></header>
                    <div class="media-grid">
                        <div><span class="label">Logo kompetisi <i>Opsional</i></span><MediaPicker v-model="form.logo_media_id" value-mode="id" folder="all" upload-folder="competitions" media-type="image" label="Pilih Logo" :preview-url="selectedLogo?.url || ''" :can-browse="can.browse_media" :can-upload="can.upload_media" @selected="selectedLogo = $event" /><small>Disarankan menggunakan gambar persegi atau transparan.</small></div>
                        <div><span class="label">Banner kompetisi <i>Opsional</i></span><MediaPicker v-model="form.banner_media_id" value-mode="id" folder="all" upload-folder="competitions" media-type="image" label="Pilih Banner" :preview-url="selectedBanner?.url || ''" :can-browse="can.browse_media" :can-upload="can.upload_media" @selected="selectedBanner = $event" /><small>Disarankan menggunakan gambar horizontal.</small></div>
                    </div>
                </section>

                <div v-if="Object.keys(form.errors).length" class="errors"><p v-for="(error, key) in form.errors" :key="key">{{ error }}</p></div>
                <footer>
                    <Link :href="route('admin.competitions.index')" class="cancel">Batal</Link>
                    <button type="submit" class="secondary" :disabled="form.processing">Simpan Kompetisi</button>
                    <button type="button" :disabled="form.processing" @click="submit(true)">{{ form.processing ? 'Menyimpan...' : 'Simpan & Tambah Musim' }}</button>
                </footer>
            </form>
        </main>
    </AuthenticatedLayout>
</template>

<style scoped>
.page{max-width:1180px;margin:auto;padding:42px}.page-head{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:20px;padding:30px;border:1px solid #1e3447;background:#071524}.page-head span,.editor section>header>b{color:#42b6f5;font:700 .65rem var(--font-display);letter-spacing:.12em;text-transform:uppercase}.page-head h1{margin:8px 0;color:#f5f8fb;font:700 clamp(2.3rem,5vw,4.4rem)/.95 var(--font-display);text-transform:uppercase}.page-head h1 span{font-size:inherit}.page-head p,small{color:#7891a5}.back,.cancel{padding:14px 18px;border:1px solid #294155;color:#d8e5ee;text-decoration:none;font:700 .7rem var(--font-display);text-transform:uppercase}.editor{border:1px solid #1e3447;background:#071321}.editor section{padding:26px 30px;border-bottom:1px solid #1e3447}.editor section>header{display:flex;gap:16px;margin-bottom:22px}.editor h2{margin:0;color:#f5f8fb;font:700 1.4rem var(--font-display);text-transform:uppercase}.editor header p{margin:5px 0 0;color:#7891a5}.fields,.media-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}.fields label{display:flex;flex-direction:column;gap:8px}.full{grid-column:1/-1}.fields span,.label{color:#dbe7ef;font:700 .66rem var(--font-display);letter-spacing:.04em;text-transform:uppercase}.fields span b{color:#42b6f5}.fields i,.label i{color:#7891a5;font-style:normal}.fields input,.fields select,.fields textarea{width:100%;padding:15px;border:1px solid #294155;color:#f4f8fb;background:#06101b;font:500 1rem Arial}.fields em,.errors{color:#ff7180;font-style:normal}.media-grid>div{display:flex;flex-direction:column;gap:9px}.errors{padding:16px 30px;border-bottom:1px solid #4a2630;background:#1d1118}.errors p{margin:3px 0}.editor footer{display:flex;justify-content:flex-end;gap:10px;padding:20px 30px}.editor footer button{padding:15px 20px;border:1px solid #42b6f5;background:#42b6f5;color:#06101b;font:700 .7rem var(--font-display);text-transform:uppercase}.editor footer .secondary{color:#dbe7ef;background:transparent}.editor footer button:disabled{opacity:.55}@media(max-width:760px){.page{padding:18px}.page-head{align-items:flex-start;flex-direction:column;gap:20px}.fields,.media-grid{grid-template-columns:1fr}.full{grid-column:auto}.editor section{padding:22px 18px}.editor footer{align-items:stretch;flex-direction:column}.editor footer>*{text-align:center}}
</style>
