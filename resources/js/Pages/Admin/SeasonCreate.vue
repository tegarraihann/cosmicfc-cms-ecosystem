<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import SeasonEditor from "@/Components/Admin/SeasonEditor.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({ competitions: Array, selectedCompetitionId: Number });
const form = useForm({
    competition_id: props.selectedCompetitionId || props.competitions?.[0]?.id || "",
    name: "", starts_at: "", ends_at: "", format: "league",
    current_matchday: 0, total_matchdays: 0, max_participants: 8,
    win_points: 3, draw_points: 1, loss_points: 0,
    tie_breakers: ["points", "goal_difference", "goals_for", "head_to_head"],
    regulations: [], is_featured: false, status: "draft", from_create_page: true,
});

function submit() {
    if (!form.competition_id) return;
    form.post(route("admin.competitions.seasons.save", { competition: form.competition_id }), { preserveScroll: true });
}
</script>

<template>
    <Head title="Tambah Musim" />
    <AuthenticatedLayout>
        <template #page-title>Tambah Musim</template>
        <main class="page">
            <header class="page-head"><div><span>Kompetisi &amp; Musim</span><h1>Tambah Musim<span>.</span></h1><p>Lengkapi periode, format, sistem poin, dan status musim.</p></div><Link :href="route('admin.competitions.index')" class="back">Kembali</Link></header>
            <div v-if="!competitions.length" class="empty"><h2>Belum ada kompetisi</h2><p>Buat kompetisi terlebih dahulu sebelum menambahkan musim.</p><Link :href="route('admin.competitions.create')">Tambah Kompetisi</Link></div>
            <form v-else class="editor" @submit.prevent="submit">
                <section class="competition-select"><label><span>Kompetisi induk <b>Wajib</b></span><select v-model="form.competition_id"><option value="" disabled>Pilih kompetisi</option><option v-for="competition in competitions" :key="competition.id" :value="competition.id">{{ competition.name }} ({{ competition.short_name }})</option></select><small>Musim akan tersimpan di dalam kompetisi yang dipilih.</small><em v-if="form.errors.competition_id">{{ form.errors.competition_id }}</em></label></section>
                <SeasonEditor :form="form" />
                <div v-if="Object.keys(form.errors).length" class="errors"><p v-for="(error, key) in form.errors" :key="key">{{ error }}</p></div>
                <footer><Link :href="route('admin.competitions.index')" class="cancel">Batal</Link><button :disabled="form.processing || !form.competition_id">{{ form.processing ? 'Menyimpan...' : 'Simpan Musim' }}</button></footer>
            </form>
        </main>
    </AuthenticatedLayout>
</template>

<style scoped>
.page{max-width:1180px;margin:auto;padding:42px}.page-head{display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:20px;padding:30px;border:1px solid #1e3447;background:#071524}.page-head>div>span{color:#42b6f5;font:700 .65rem var(--font-display);letter-spacing:.12em;text-transform:uppercase}.page-head h1{margin:8px 0;color:#f5f8fb;font:700 clamp(2.3rem,5vw,4.4rem)/.95 var(--font-display);text-transform:uppercase}.page-head h1 span{color:#42b6f5}.page-head p,.competition-select small{color:#7891a5}.back,.cancel,.empty a{padding:14px 18px;border:1px solid #294155;color:#d8e5ee;text-decoration:none;font:700 .7rem var(--font-display);text-transform:uppercase}.editor{border:1px solid #1e3447;background:#071321}.competition-select{padding:24px 28px;border-bottom:1px solid #1e3447}.competition-select label{display:flex;flex-direction:column;gap:8px}.competition-select span{color:#dbe7ef;font:700 .66rem var(--font-display);text-transform:uppercase}.competition-select b{color:#42b6f5}.competition-select select{padding:15px;border:1px solid #294155;color:#f4f8fb;background:#06101b;font:500 1rem Arial}.competition-select em,.errors{color:#ff7180;font-style:normal}.errors{padding:16px 28px;border-top:1px solid #4a2630;background:#1d1118}.errors p{margin:3px 0}.editor footer{display:flex;justify-content:flex-end;gap:10px;padding:20px 28px;border-top:1px solid #1e3447}.editor footer button{padding:15px 20px;border:1px solid #42b6f5;background:#42b6f5;color:#06101b;font:700 .7rem var(--font-display);text-transform:uppercase}.empty{padding:45px;border:1px solid #1e3447;background:#071321;color:#dbe7ef}.empty p{margin-bottom:28px;color:#7891a5}@media(max-width:760px){.page{padding:18px}.page-head{align-items:flex-start;flex-direction:column;gap:20px}.editor footer{align-items:stretch;flex-direction:column}.editor footer>*{text-align:center}}
</style>
