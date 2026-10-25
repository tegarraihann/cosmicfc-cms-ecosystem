<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const props = defineProps({
    match: Object,
    candidates: Array,
    assessments: Object,
    award: Object,
    media: Array,
    canPublish: Boolean,
});

const page = usePage();
const publishing = ref(false);
const emptyScore = (candidate) => ({
    player_id: candidate.id,
    result_impact: 3,
    position_performance: 3,
    consistency: 3,
    crucial_moments: 3,
    discipline_teamwork: 3,
    assessment_note: "",
});
const savedCandidates = props.candidates
    .filter((candidate) => props.assessments?.[candidate.id])
    .map((candidate) => ({
        ...emptyScore(candidate),
        ...props.assessments[candidate.id],
    }));
const assessmentForm = useForm({ candidates: savedCandidates });
const decisionForm = useForm({
    player_id: props.award?.player_id ?? "",
    title: props.award?.title ?? "Player of the Match",
    editorial: props.award?.editorial ?? "",
    quote: props.award?.quote ?? "",
    primary_media_id: props.award?.primary_media_id ?? "",
    status: props.award?.status ?? "draft",
    decision_reason: "",
});

const parameters = [
    ["result_impact", "Dampak pada hasil", "Kontribusi pemain terhadap skor dan hasil akhir pertandingan."],
    ["position_performance", "Performa sesuai posisi", "Kualitas menjalankan peran utama sesuai posisi bermain."],
    ["consistency", "Konsistensi permainan", "Stabilitas performa sepanjang waktu bermain."],
    ["crucial_moments", "Momen krusial", "Aksi penting yang mengubah jalannya pertandingan."],
    ["discipline_teamwork", "Disiplin & kerja sama", "Keputusan, sikap, dan kontribusi terhadap permainan tim."],
];
const selectedIds = computed(() => assessmentForm.candidates.map((item) => Number(item.player_id)));
const candidateById = (id) => props.candidates.find((candidate) => candidate.id === Number(id));
const score = (item) => Math.round((item.result_impact * 30 + item.position_performance * 25 + item.consistency * 20 + item.crucial_moments * 15 + item.discipline_teamwork * 10) / 5 * 100) / 100;
const ranking = computed(() => [...assessmentForm.candidates].map((item) => ({ ...item, score: score(item), candidate: candidateById(item.player_id) })).sort((a, b) => b.score - a.score));
const highestId = computed(() => ranking.value[0]?.player_id ?? null);
const needsDecisionReason = computed(() => decisionForm.player_id && highestId.value && Number(decisionForm.player_id) !== Number(highestId.value));

function toggleCandidate(candidate) {
    const index = assessmentForm.candidates.findIndex((item) => Number(item.player_id) === candidate.id);
    if (index >= 0) assessmentForm.candidates.splice(index, 1);
    else if (assessmentForm.candidates.length < 3) assessmentForm.candidates.push(emptyScore(candidate));
}
function saveAssessments() {
    assessmentForm.put(route("admin.matches.mvp.assessments", props.match.id), { preserveScroll: true });
}
function saveDecision(status) {
    decisionForm.status = status;
    decisionForm.clearErrors();
    publishing.value = true;
    assessmentForm.put(route("admin.matches.mvp.assessments", props.match.id), {
        preserveScroll: true,
        onSuccess: () => {
            decisionForm.post(route("admin.matches.mvp.decision", props.match.id), {
                preserveScroll: true,
                onFinish: () => (publishing.value = false),
            });
        },
        onError: () => (publishing.value = false),
    });
}
const assessmentError = (index, field) =>
    assessmentForm.errors[`candidates.${index}.${field}`];
const evidenceText = (candidate) => `${candidate.evidence.goals} gol · ${candidate.evidence.assists} assist · ${candidate.evidence.yellowCards} kartu kuning · ${candidate.evidence.redCards} kartu merah`;
</script>

<template>
    <Head title="Penentuan MVP" />
    <AuthenticatedLayout>
        <template #page-title>Penentuan MVP</template>
        <main class="mvp-page">
            <div v-if="page.props.flash?.success" class="notice">{{ page.props.flash.success }}</div>
            <div v-if="page.props.errors?.match" class="alert danger">{{ page.props.errors.match }}</div>

            <header class="page-head">
                <div>
                    <span>Player of the Match</span>
                    <h1>{{ match.home }} <b>vs</b> {{ match.away }}</h1>
                    <p>{{ match.competition }} · {{ match.kickoff }} · {{ match.venue || "Lokasi belum diatur" }}</p>
                </div>
                <Link :href="route('admin.matches.index')">← Kembali ke pertandingan</Link>
            </header>

            <section class="match-summary">
                <article><small>Skor akhir</small><strong>{{ match.home_score ?? "–" }} — {{ match.away_score ?? "–" }}</strong></article>
                <article><small>Status data</small><strong>{{ match.ready ? "Siap dinilai" : "Belum siap" }}</strong></article>
                <article><small>Kandidat dipilih</small><strong>{{ assessmentForm.candidates.length }} / 3</strong></article>
                <article><small>Status MVP</small><strong>{{ award?.status === "published" ? "Dipublikasikan" : award ? "Draft" : "Belum ditentukan" }}</strong></article>
            </section>

            <div v-if="!match.ready" class="alert">Selesaikan pertandingan dan isi susunan pemain terlebih dahulu. MVP hanya dapat dipilih dari pemain yang tercatat dalam susunan pertandingan.</div>
            <div v-if="match.data_changed" class="alert warning">Data pertandingan berubah setelah penilaian terakhir. Tinjau ulang bukti dan nilai kandidat sebelum dipublikasikan.</div>

            <section class="workspace">
                <div class="section-head"><div><span>Langkah 1</span><h2>Pilih kandidat</h2><p>Pilih maksimal tiga pemain dari susunan pertandingan.</p></div><b>{{ candidates.length }} pemain tersedia</b></div>
                <div class="candidate-grid">
                    <button v-for="candidate in candidates" :key="candidate.id" type="button" class="candidate" :class="{ selected: selectedIds.includes(candidate.id) }" :disabled="!match.ready || (!selectedIds.includes(candidate.id) && selectedIds.length >= 3)" @click="toggleCandidate(candidate)">
                        <img v-if="candidate.image" :src="candidate.image" :alt="candidate.name" />
                        <span v-else class="avatar">{{ candidate.name.slice(0, 2) }}</span>
                        <div><small>{{ candidate.club }} · {{ candidate.role === 'starter' ? 'Pemain inti' : 'Cadangan' }}</small><strong>{{ candidate.number ? `#${candidate.number} ` : '' }}{{ candidate.name }}</strong><p>{{ candidate.position || "Posisi belum diatur" }}<i v-if="candidate.captain">Kapten</i></p><em>{{ evidenceText(candidate) }}</em></div>
                        <b>{{ selectedIds.includes(candidate.id) ? "✓ Dipilih" : "+ Pilih" }}</b>
                    </button>
                </div>
            </section>

            <section v-if="assessmentForm.candidates.length" class="workspace">
                <div class="section-head"><div><span>Langkah 2</span><h2>Nilai kandidat</h2><p>Gunakan skala 1–5. Sistem menghitung nilai berbobot sebagai rekomendasi, bukan keputusan otomatis.</p></div></div>
                <div class="assessment-list">
                    <article v-for="(item, index) in assessmentForm.candidates" :key="item.player_id" class="assessment">
                        <header><div><small>Kandidat {{ index + 1 }}</small><h3>{{ candidateById(item.player_id)?.name }}</h3></div><strong>{{ score(item) }}<small>/ 100</small></strong></header>
                        <div class="score-grid">
                            <label v-for="parameter in parameters" :key="parameter[0]"><span>{{ parameter[1] }} <i :title="parameter[2]">?</i></span><select v-model.number="item[parameter[0]]"><option v-for="value in 5" :key="value" :value="value">{{ value }} — {{ ['Sangat kurang','Kurang','Cukup','Baik','Sangat baik'][value-1] }}</option></select><small>{{ parameter[2] }}</small></label>
                        </div>
                        <label class="note"><span>Catatan penilaian</span><textarea v-model="item.assessment_note" rows="3" placeholder="Jelaskan performa dan bukti utama kandidat ini..."></textarea><small>Minimal 10 karakter. Catatan ini menjadi dokumentasi internal.</small><em v-if="assessmentError(index, 'assessment_note')" class="field-error">{{ assessmentError(index, "assessment_note") }}</em></label>
                    </article>
                </div>
                <div v-if="assessmentForm.errors.candidates" class="field-error">{{ assessmentForm.errors.candidates }}</div>
                <footer class="section-actions"><button type="button" :disabled="assessmentForm.processing" @click="saveAssessments">{{ assessmentForm.processing ? "Menyimpan..." : "Simpan penilaian" }}</button></footer>
            </section>

            <section v-if="ranking.length" class="workspace decision-section">
                <div class="section-head"><div><span>Langkah 3</span><h2>Tetapkan pemenang</h2><p>Tinjau peringkat sistem, lalu tetapkan keputusan akhir secara editorial.</p></div></div>
                <div class="decision-grid">
                    <div class="ranking"><h3>Rekomendasi kandidat</h3><button v-for="(item, index) in ranking" :key="item.player_id" type="button" :class="{ active: Number(decisionForm.player_id) === Number(item.player_id) }" @click="decisionForm.player_id = item.player_id"><span>{{ index + 1 }}</span><div><strong>{{ item.candidate?.name }}</strong><small>{{ evidenceText(item.candidate) }}</small></div><b>{{ item.score }}</b></button></div>
                    <div class="decision-form">
                        <label><span>Pemenang MVP</span><select v-model="decisionForm.player_id"><option value="" disabled>Pilih pemain terbaik</option><option v-for="item in ranking" :key="item.player_id" :value="item.player_id">{{ item.candidate?.name }} — {{ item.score }}</option></select><small>Pemenang harus berasal dari kandidat yang telah dinilai.</small></label>
                        <label><span>Judul penghargaan</span><input v-model="decisionForm.title" /><small>Judul yang tampil pada halaman detail pertandingan.</small></label>
                        <label class="wide"><span>Narasi performa</span><textarea v-model="decisionForm.editorial" rows="4" placeholder="Ringkas alasan pemain layak menjadi MVP..."></textarea><small>Minimal 20 karakter dan akan tampil di website.</small></label>
                        <label><span>Kutipan pemain <i>Opsional</i></span><input v-model="decisionForm.quote" placeholder="Kutipan singkat pemain" /></label>
                        <label><span>Foto penghargaan <i>Opsional</i></span><select v-model="decisionForm.primary_media_id"><option value="">Gunakan foto profil pemain</option><option v-for="item in media" :key="item.id" :value="item.id">{{ item.title }}</option></select></label>
                        <label v-if="needsDecisionReason" class="wide"><span>Alasan memilih di luar rekomendasi tertinggi</span><textarea v-model="decisionForm.decision_reason" rows="3" placeholder="Jelaskan pertimbangan akhir admin..."></textarea><small>Wajib minimal 20 karakter untuk menjaga jejak keputusan.</small></label>
                    </div>
                </div>
                <div v-for="error in decisionForm.errors" :key="error" class="field-error">{{ error }}</div>
                <footer class="section-actions"><button type="button" class="secondary" :disabled="publishing || decisionForm.processing || assessmentForm.processing" @click="saveDecision('draft')">{{ publishing ? "Memvalidasi penilaian..." : "Simpan draft" }}</button><button v-if="canPublish" type="button" :disabled="publishing || decisionForm.processing || assessmentForm.processing" @click="saveDecision('published')">{{ publishing ? "Memvalidasi penilaian..." : "Tetapkan & publikasikan" }}</button></footer>
            </section>
        </main>
    </AuthenticatedLayout>
</template>

<style scoped>
.mvp-page{padding:34px;display:grid;gap:20px;color:#f4f8fb}.notice,.alert{padding:14px 16px;border:1px solid #45b7ff55;background:#45b7ff10;color:#9edcff;font-size:14px}.danger{border-color:#ff627055;color:#ff9da6}.warning{border-color:#e8b14c66;color:#ffd98c;background:#e8b14c0d}.page-head,.workspace,.match-summary article{border:1px solid #ffffff18;background:#071423}.page-head{display:flex;align-items:flex-end;justify-content:space-between;padding:30px}.page-head span,.section-head span{color:#45b7ff;font:800 11px var(--font-display);letter-spacing:.15em;text-transform:uppercase}.page-head h1{margin:8px 0;font:900 clamp(34px,4vw,58px)/.95 var(--font-display);text-transform:uppercase}.page-head h1 b{color:#45b7ff}.page-head p,.section-head p{margin:0;color:#8194a4}.page-head a{color:#9edcff;text-decoration:none;font:700 12px var(--font-display);text-transform:uppercase}.match-summary{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.match-summary article{padding:18px;display:grid;gap:7px}.match-summary small{color:#7890a1;text-transform:uppercase}.match-summary strong{font:800 20px var(--font-display);text-transform:uppercase}.workspace{padding:26px}.section-head{display:flex;justify-content:space-between;align-items:end;margin-bottom:20px}.section-head h2{margin:5px 0;font:900 30px var(--font-display);text-transform:uppercase}.section-head>b{color:#7890a1}.candidate-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}.candidate{display:grid;grid-template-columns:58px 1fr auto;gap:13px;align-items:center;padding:13px;text-align:left;border:1px solid #ffffff18;background:#050e18;color:#fff;cursor:pointer}.candidate:hover,.candidate.selected{border-color:#45b7ff;background:#45b7ff0d}.candidate:disabled{opacity:.45;cursor:not-allowed}.candidate img,.avatar{width:58px;height:58px;object-fit:cover;background:#0c2030;display:grid;place-items:center}.candidate div{min-width:0;display:grid;gap:4px}.candidate small,.candidate em{color:#7890a1;font-size:11px;font-style:normal}.candidate strong{font:800 16px var(--font-display);text-transform:uppercase}.candidate p{margin:0;color:#aab8c2;font-size:12px}.candidate p i{margin-left:7px;color:#45b7ff;font-style:normal}.candidate>b{color:#45b7ff;font:700 10px var(--font-display);text-transform:uppercase}.assessment-list{display:grid;gap:14px}.assessment{border:1px solid #ffffff18;background:#050e18}.assessment>header{padding:16px 18px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #ffffff14}.assessment h3{margin:4px 0 0;font:900 24px var(--font-display);text-transform:uppercase}.assessment header>strong{color:#45b7ff;font:900 30px var(--font-display)}.assessment header>strong small{font-size:11px}.score-grid,.decision-form{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;padding:18px}.score-grid label,.decision-form label,.note{display:grid;gap:7px}.score-grid label:last-child{grid-column:1/-1}.score-grid span,.decision-form span,.note span{font:700 11px var(--font-display);letter-spacing:.08em;text-transform:uppercase}.score-grid i{display:inline-grid;width:17px;height:17px;place-items:center;border:1px solid #45b7ff66;border-radius:50%;color:#45b7ff;font-style:normal}.score-grid select,.decision-form select,.decision-form input,.decision-form textarea,.note textarea{width:100%;box-sizing:border-box;padding:12px;border:1px solid #ffffff20;background:#071423;color:#fff;font:500 14px Arial,sans-serif}.score-grid small,.decision-form small,.note small{color:#718695;font-size:11px;line-height:1.45}.note{padding:0 18px 18px}.decision-grid{display:grid;grid-template-columns:.8fr 1.2fr;border:1px solid #ffffff18}.ranking{padding:18px;border-right:1px solid #ffffff18}.ranking h3{margin:0 0 14px;font:800 18px var(--font-display);text-transform:uppercase}.ranking button{width:100%;display:grid;grid-template-columns:28px 1fr auto;gap:10px;align-items:center;padding:13px;border:1px solid #ffffff18;border-bottom:0;background:transparent;color:#fff;text-align:left}.ranking button:last-child{border-bottom:1px solid #ffffff18}.ranking button.active{border-color:#45b7ff;background:#45b7ff10}.ranking button>span{display:grid;width:28px;height:28px;place-items:center;background:#102131;color:#45b7ff}.ranking button div{display:grid;gap:3px}.ranking button small{color:#718695}.ranking button>b{color:#45b7ff}.wide{grid-column:1/-1}.decision-form i{color:#718695;font-style:normal;text-transform:none}.section-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:18px}.section-actions button{min-height:42px;padding:0 18px;border:1px solid #45b7ff;background:#45b7ff;color:#03111d;font:800 11px var(--font-display);text-transform:uppercase;cursor:pointer}.section-actions .secondary{background:transparent;color:#a9dfff}.field-error{margin-top:10px;color:#ff8f99;font-size:12px}@media(max-width:1000px){.candidate-grid{grid-template-columns:repeat(2,1fr)}.decision-grid{grid-template-columns:1fr}.ranking{border-right:0;border-bottom:1px solid #ffffff18}}@media(max-width:700px){.mvp-page{padding:18px}.page-head{align-items:flex-start;gap:20px;flex-direction:column}.match-summary{grid-template-columns:1fr 1fr}.candidate-grid,.score-grid,.decision-form{grid-template-columns:1fr}.score-grid label:last-child,.wide{grid-column:auto}.candidate{grid-template-columns:48px 1fr}.candidate img,.avatar{width:48px;height:48px}.candidate>b{grid-column:2}.workspace{padding:18px}}
</style>
