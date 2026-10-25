<script setup>
import { ref } from "vue";
defineProps({ form: { type: Object, required: true } });
const tab = ref("info");
const tabs = [
    ["info", "Informasi Musim"],
    ["points", "Matchday & Poin"],
    ["rules", "Regulasi"],
];
const tieBreakers = [
    { v: "points", l: "Poin" },
    { v: "goal_difference", l: "Selisih gol" },
    { v: "goals_for", l: "Jumlah gol" },
    { v: "head_to_head", l: "Head-to-head" },
];
</script>

<template>
    <section class="season-editor">
        <nav>
            <button
                v-for="item in tabs"
                :key="item[0]"
                type="button"
                :class="{ active: tab === item[0] }"
                @click="tab = item[0]"
            >
                {{ item[1] }}
            </button>
        </nav>
        <div v-show="tab === 'info'" class="panel">
            <header>
                <b>01</b>
                <div>
                    <h3>Informasi Musim</h3>
                    <p>
                        Tentukan periode, format, status, dan musim utama
                        kompetisi.
                    </p>
                </div>
            </header>
            <div class="fields">
                <label class="full"
                    ><span>Nama musim <b>Wajib</b></span
                    ><input
                        v-model="form.name"
                        placeholder="Contoh: COSMIC League 2027"
                    /><small
                        >Nama yang tampil pada klasemen, jadwal, dan halaman
                        liga.</small
                    ><em v-if="form.errors.name">{{
                        form.errors.name
                    }}</em></label
                >
                <label
                    ><span>Tanggal mulai musim <b>Wajib</b></span
                    ><input v-model="form.starts_at" type="date" /><small
                        >Awal periode berlangsungnya musim.</small
                    ><em v-if="form.errors.starts_at">{{
                        form.errors.starts_at
                    }}</em></label
                >
                <label
                    ><span>Tanggal selesai musim <b>Wajib</b></span
                    ><input v-model="form.ends_at" type="date" /><small
                        >Akhir periode berlangsungnya musim.</small
                    ><em v-if="form.errors.ends_at">{{
                        form.errors.ends_at
                    }}</em></label
                >
                <label
                    ><span>Format musim <b>Wajib</b></span
                    ><select v-model="form.format">
                        <option value="league">Liga</option>
                        <option value="group">Babak grup</option>
                        <option value="knockout">Sistem gugur</option>
                        <option value="hybrid">Grup dan sistem gugur</option>
                        <option value="friendly">
                            Pertandingan persahabatan
                        </option></select
                    ><small>Cara pertandingan diselenggarakan.</small></label
                >
                <label
                    ><span>Status musim <b>Wajib</b></span
                    ><select v-model="form.status">
                        <option value="draft">
                            Draft — belum siap digunakan
                        </option>
                        <option value="registration">
                            Pendaftaran — peserta dikumpulkan
                        </option>
                        <option value="scheduled">
                            Terjadwal — siap dimulai
                        </option>
                        <option value="ongoing">Sedang berlangsung</option>
                        <option value="completed">Selesai</option>
                        <option value="cancelled">Dibatalkan</option></select
                    ><small>Status operasional musim saat ini.</small></label
                >
                <div class="full checks">
                    <label class="check"
                        ><input
                            v-model="form.is_featured"
                            type="checkbox"
                        /><span
                            ><strong>Jadikan musim utama</strong
                            ><small
                                >Menjadi sumber utama klasemen, statistik, dan
                                informasi liga di website.</small
                            ></span
                        ></label
                    >
                </div>
            </div>
        </div>
        <div v-show="tab === 'points'" class="panel">
            <header>
                <b>02</b>
                <div>
                    <h3>Matchday, Peserta, dan Sistem Poin</h3>
                    <p>
                        Atur progres pertandingan, kapasitas peserta, dan
                        klasemen.
                    </p>
                </div>
            </header>
            <div class="fields">
                <label
                    ><span>Matchday berjalan <b>Wajib</b></span
                    ><input
                        v-model="form.current_matchday"
                        type="number"
                        min="0"
                    /><small
                        >Matchday yang sedang atau terakhir dimainkan.</small
                    ><em v-if="form.errors.current_matchday">{{
                        form.errors.current_matchday
                    }}</em></label
                >
                <label
                    ><span>Jumlah seluruh matchday <b>Wajib</b></span
                    ><input
                        v-model="form.total_matchdays"
                        type="number"
                        min="0"
                    /><small>Total matchday yang direncanakan.</small></label
                >
                <label class="full"
                    ><span>Kapasitas klub peserta <i>Opsional</i></span
                    ><input
                        v-model="form.max_participants"
                        type="number"
                        min="2"
                        max="100"
                        placeholder="Contoh: 8"
                    /><small
                        >Jumlah maksimum klub yang dapat mengikuti musim.</small
                    ></label
                >
                <label
                    ><span>Poin kemenangan <b>Wajib</b></span
                    ><input
                        v-model="form.win_points"
                        type="number"
                        min="0"
                    /><small>Poin untuk pemenang.</small></label
                ><label
                    ><span>Poin hasil seri <b>Wajib</b></span
                    ><input
                        v-model="form.draw_points"
                        type="number"
                        min="0"
                    /><small>Poin untuk masing-masing klub.</small></label
                ><label
                    ><span>Poin kekalahan <b>Wajib</b></span
                    ><input
                        v-model="form.loss_points"
                        type="number"
                        min="0"
                    /><small>Poin untuk klub yang kalah.</small></label
                >
                <div class="summary full">
                    <span>Ringkasan sistem poin</span
                    ><strong
                        >Menang +{{ form.win_points }} · Seri +{{
                            form.draw_points
                        }}
                        · Kalah +{{ form.loss_points }}</strong
                    >
                </div>
                <fieldset class="full">
                    <legend>Penentuan peringkat klasemen</legend>
                    <p>
                        Sistem membaca prioritas pilihan berikut dari kiri ke
                        kanan.
                    </p>
                    <label v-for="item in tieBreakers" :key="item.v"
                        ><input
                            v-model="form.tie_breakers"
                            type="checkbox"
                            :value="item.v"
                        /><span>{{ item.l }}</span></label
                    ><em v-if="form.errors.tie_breakers">{{
                        form.errors.tie_breakers
                    }}</em>
                </fieldset>
            </div>
        </div>
        <div v-show="tab === 'rules'" class="panel">
            <header>
                <b>03</b>
                <div>
                    <h3>Regulasi Musim</h3>
                    <p>
                        Aturan aktif dapat ditampilkan pada halaman kompetisi.
                    </p>
                </div>
            </header>
            <div v-if="!form.regulations.length" class="empty">
                <strong>Belum ada regulasi</strong
                ><span
                    >Tambahkan regulasi jika ada aturan yang perlu
                    diinformasikan.</span
                >
            </div>
            <div class="rules">
                <article v-for="(rule, index) in form.regulations" :key="index">
                    <header>
                        <div>
                            <span
                                >Regulasi
                                {{ String(index + 1).padStart(2, "0") }}</span
                            ><strong>{{
                                rule.title || "Regulasi baru"
                            }}</strong>
                        </div>
                        <button
                            type="button"
                            :aria-label="`Hapus regulasi ${index + 1}`"
                            title="Hapus regulasi"
                            @click="form.regulations.splice(index, 1)"
                        >
                            <svg viewBox="0 0 24 24" fill="none">
                                <path
                                    d="M4 7h16M9 7V4h6v3m3 0-1 13H7L6 7m4 4v5m4-5v5"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </button>
                    </header>
                    <div class="fields">
                        <label class="full"
                            ><span>Judul regulasi <b>Wajib</b></span
                            ><input
                                v-model="rule.title"
                                placeholder="Contoh: Format pertandingan"
                            /><small
                                >Judul singkat agar aturan mudah
                                dikenali.</small
                            ></label
                        ><label class="full"
                            ><span>Isi regulasi <i>Opsional</i></span
                            ><textarea
                                v-model="rule.body"
                                rows="3"
                                placeholder="Jelaskan aturan secara singkat..."
                            ></textarea
                            ><small
                                >Informasi yang dibaca pengunjung.</small
                            ></label
                        >
                        <div class="full checks">
                            <label class="check compact"
                                ><input
                                    v-model="rule.active"
                                    type="checkbox"
                                /><span
                                    ><strong>Tampilkan regulasi</strong
                                    ><small
                                        >Regulasi aktif dapat ditampilkan kepada
                                        publik.</small
                                    ></span
                                ></label
                            >
                        </div>
                    </div>
                </article>
            </div>
            <button
                type="button"
                class="add"
                @click="
                    form.regulations.push({ title: '', body: '', active: true })
                "
            >
                + Tambah Regulasi Baru
            </button>
        </div>
    </section>
</template>

<style scoped>
.season-editor {
    border-top: 1px solid #ffffff14;
}
.season-editor > nav {
    display: flex;
    overflow: auto;
    border-bottom: 1px solid #ffffff14;
}
.season-editor > nav button {
    padding: 14px 18px;
    border: 0;
    border-bottom: 2px solid transparent;
    color: #728793;
    background: none;
    font: 750 0.52rem var(--font-display);
    text-transform: uppercase;
    white-space: nowrap;
}
.season-editor > nav button.active {
    border-color: var(--color-accent);
    color: white;
}
.panel {
    padding: 22px;
}
.panel > header {
    display: flex;
    gap: 13px;
    margin-bottom: 22px;
    padding-bottom: 16px;
    border-bottom: 1px solid #ffffff14;
}
.panel > header > b {
    display: grid;
    width: 34px;
    height: 34px;
    flex: 0 0 34px;
    place-items: center;
    border: 1px solid #45b7ff55;
    color: var(--color-accent);
    font: 0.6rem var(--font-display);
}
h3,
p {
    margin: 0;
}
.panel > header h3 {
    font: 800 0.8rem var(--font-display);
    text-transform: uppercase;
}
.panel > header p {
    margin-top: 5px;
    color: #718895;
    font-size: 0.76rem;
}
.fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px 18px;
}
.full {
    grid-column: 1/-1;
}
.fields > label {
    display: flex;
    min-width: 0;
    flex-direction: column;
}
.fields > label > span {
    font: 750 0.6rem var(--font-display);
    letter-spacing: 0.07em;
    text-transform: uppercase;
}
.fields > label > span b,
.fields > label > span i {
    margin-left: 5px;
    color: var(--color-accent);
    font: inherit;
    font-size: 0.47rem;
    font-style: normal;
}
.fields > label > span i {
    color: #6f8391;
}
.fields input:not([type="checkbox"]),
.fields select,
.fields textarea {
    box-sizing: border-box;
    width: 100%;
    min-height: 46px;
    margin-top: 8px;
    padding: 12px;
    border: 1px solid #ffffff20;
    color: white;
    background: #07111e;
}
.fields > label > small {
    min-height: 29px;
    margin-top: 7px;
    color: #718895;
    font-size: 0.7rem;
    line-height: 1.4;
}
.fields em,
fieldset > em {
    margin-top: 5px;
    color: #ff8e97;
    font-size: 0.7rem;
    font-style: normal;
}
.checks {
    display: grid;
}
.check {
    display: grid !important;
    min-height: 70px;
    grid-template-columns: 20px 1fr;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border: 1px solid #ffffff18;
    background: #030a12;
    cursor: pointer;
}
.check input,
fieldset input {
    appearance: none;
    box-sizing: border-box;
    width: 18px !important;
    height: 18px;
    min-height: 18px;
    margin: 0 !important;
    padding: 0 !important;
    border: 1px solid #617887;
    background: #07111e;
}
.check input:checked,
fieldset input:checked {
    border-color: var(--color-accent);
    background: var(--color-accent);
}
.check input:checked:before,
fieldset input:checked:before {
    content: "✓";
    display: grid;
    height: 16px;
    place-items: center;
    color: #03101b;
    font: bold 13px Arial;
}
.check strong,
.check small {
    display: block;
}
.check strong {
    font: 750 0.62rem var(--font-display);
    text-transform: uppercase;
}
.check small {
    margin-top: 4px;
    color: #718895;
    font-size: 0.7rem;
}
.summary {
    padding: 14px;
    border: 1px solid #45b7ff44;
    background: #45b7ff09;
}
.summary span,
.summary strong {
    display: block;
}
.summary span {
    color: #718895;
    font-size: 0.68rem;
}
.summary strong {
    margin-top: 5px;
    color: var(--color-accent);
}
fieldset {
    display: flex;
    flex-wrap: wrap;
    gap: 9px;
    margin: 0;
    padding: 15px;
    border: 1px solid #ffffff18;
}
fieldset legend {
    padding: 0 7px;
    font: 750 0.58rem var(--font-display);
    text-transform: uppercase;
}
fieldset > p {
    width: 100%;
    color: #718895;
    font-size: 0.7rem;
}
fieldset label {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 8px 10px;
    border: 1px solid #ffffff14;
    font-size: 0.75rem;
}
.empty {
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding: 18px;
    border: 1px dashed #45b7ff44;
    color: #718895;
}
.empty strong {
    color: white;
}
.rules {
    display: grid;
    gap: 13px;
}
.rules article {
    border: 1px solid #ffffff18;
    background: #050d16;
}
.rules article > header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 15px;
    border-bottom: 1px solid #ffffff14;
}
.rules article > header span,
.rules article > header strong {
    display: block;
}
.rules article > header span {
    color: var(--color-accent);
    font: 750 0.48rem var(--font-display);
    text-transform: uppercase;
}
.rules article > header strong {
    margin-top: 4px;
}
.rules article > header button {
    display: grid;
    width: 34px;
    height: 34px;
    place-items: center;
    border: 1px solid #ff6d7744;
    color: #ff8b93;
    background: transparent;
}
.rules article > header svg {
    width: 16px;
}
.rules .fields {
    padding: 15px;
}
.compact {
    min-height: 60px;
}
.add {
    width: 100%;
    margin-top: 14px;
    padding: 13px;
    border: 1px dashed #45b7ff77;
    color: var(--color-accent);
    background: #45b7ff08;
    font: 750 0.55rem var(--font-display);
    text-transform: uppercase;
}
@media (max-width: 700px) {
    .panel {
        padding: 17px;
    }
    .fields {
        grid-template-columns: 1fr;
    }
    .full {
        grid-column: auto;
    }
    .fields > label > small {
        min-height: 0;
    }
    .season-editor > nav button {
        flex: 1;
    }
}
</style>
