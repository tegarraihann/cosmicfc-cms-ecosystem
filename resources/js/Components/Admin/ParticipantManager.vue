<script setup>
import { computed, ref } from "vue";

const props = defineProps({
    form: Object,
    clubs: Array,
    competition: Object,
    season: Object,
});
const search = ref("");
const view = ref("all");
const locked = computed(
    () =>
        ["scheduled", "ongoing", "completed"].includes(props.season?.status) &&
        (props.season?.participants_count || 0) > 0,
);
const selectedIds = computed(() =>
    props.form.participants.map((item) => Number(item.club_id)),
);
const selectedCount = computed(() => props.form.participants.length);
const capacity = computed(() => Number(props.season?.max_participants) || null);
const slots = computed(() =>
    capacity.value === null
        ? null
        : Math.max(0, capacity.value - selectedCount.value),
);
const percent = computed(() =>
    capacity.value
        ? Math.min(
              100,
              Math.round((selectedCount.value / capacity.value) * 100),
          )
        : 0,
);
const filtered = computed(() =>
    props.clubs.filter((club) => {
        const matches = `${club.name} ${club.shortName}`
            .toLowerCase()
            .includes(search.value.toLowerCase());
        const selected = selectedIds.value.includes(Number(club.id));
        return (
            matches &&
            (view.value === "all" ||
                (view.value === "selected" ? selected : !selected))
        );
    }),
);
const statusLabel = (value) =>
    ({
        draft: "Draft",
        registration: "Pendaftaran",
        scheduled: "Terjadwal",
        ongoing: "Sedang Berlangsung",
        completed: "Selesai",
        cancelled: "Dibatalkan",
    })[value] || value;

function toggle(club) {
    if (locked.value) return;
    const index = props.form.participants.findIndex(
        (item) => Number(item.club_id) === Number(club.id),
    );
    if (index >= 0) {
        props.form.participants.splice(index, 1);
        return;
    }
    if (capacity.value !== null && selectedCount.value >= capacity.value)
        return;
    props.form.participants.push({ club_id: club.id });
}
</script>

<template>
    <section class="manager">
        <header class="context">
            <div>
                <span>Kelola Peserta Musim</span>
                <h3>{{ competition?.name }}</h3>
                <p>{{ season?.name }} · {{ statusLabel(season?.status) }}</p>
            </div>
            <div class="capacity">
                <strong>{{ selectedCount }}</strong>
                <small v-if="capacity !== null">dari {{ capacity }} klub</small>
                <small v-else>klub dipilih</small>
                <span v-if="slots !== null">{{ slots }} slot tersedia</span>
            </div>
        </header>
        <div v-if="capacity" class="progress">
            <i :style="{ width: `${percent}%` }"></i>
        </div>

        <div v-if="locked" class="notice warning">
            <strong>Daftar peserta sudah dikunci</strong>
            <span
                >Peserta tidak dapat diubah karena musim telah dijadwalkan atau
                dimulai.</span
            >
        </div>
        <div v-else-if="capacity !== null && slots === 0" class="notice">
            Kapasitas peserta sudah penuh. Batalkan salah satu pilihan untuk
            mengganti klub.
        </div>

        <section class="directory">
            <header>
                <div>
                    <span>Pilih Klub</span
                    ><strong>{{ filtered.length }} klub ditemukan</strong>
                </div>
                <p>
                    Klik klub untuk menambahkan atau menghapusnya dari daftar
                    peserta musim.
                </p>
            </header>
            <div class="toolbar">
                <label>
                    <span>Pencarian klub</span>
                    <input
                        v-model="search"
                        placeholder="Cari nama atau singkatan klub"
                    />
                </label>
                <label>
                    <span>Status pilihan</span>
                    <select v-model="view">
                        <option value="all">Semua klub</option>
                        <option value="selected">Klub terpilih</option>
                        <option value="available">Belum dipilih</option>
                    </select>
                </label>
            </div>
            <div v-if="!filtered.length" class="empty">
                Tidak ada klub yang sesuai dengan pencarian atau filter.
            </div>
            <div v-else class="club-grid">
                <button
                    v-for="club in filtered"
                    :key="club.id"
                    type="button"
                    :class="{
                        selected: selectedIds.includes(Number(club.id)),
                        disabled:
                            !selectedIds.includes(Number(club.id)) &&
                            slots === 0,
                    }"
                    :disabled="
                        locked ||
                        (!selectedIds.includes(Number(club.id)) && slots === 0)
                    "
                    @click="toggle(club)"
                >
                    <span class="logo">
                        <img
                            v-if="club.logo"
                            :src="club.logo"
                            :alt="`Logo ${club.name}`"
                        />
                        <b v-else>{{ club.shortName }}</b>
                    </span>
                    <span class="club-copy"
                        ><strong>{{ club.name }}</strong
                        ><small>{{ club.shortName }}</small></span
                    >
                    <span class="choice"
                        ><i aria-hidden="true"></i
                        ><small>{{
                            selectedIds.includes(Number(club.id))
                                ? "Terpilih"
                                : "Pilih"
                        }}</small></span
                    >
                </button>
            </div>
        </section>
        <p v-if="form.errors.participants" class="error">
            {{ form.errors.participants }}
        </p>
    </section>
</template>

<style scoped>
.manager {
    padding: 20px;
}
.context {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
    padding: 18px;
    border: 1px solid #45b7ff35;
    background: #45b7ff08;
}
.context > div:first-child > span,
.directory > header span {
    color: var(--color-accent);
    font: 750 0.5rem var(--font-display);
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.context h3 {
    margin: 6px 0 3px;
    font: 900 1.45rem var(--font-display);
    text-transform: uppercase;
}
.context p,
.directory header p {
    margin: 0;
    color: #7d919e;
    font-size: 0.72rem;
}
.capacity {
    text-align: right;
}
.capacity strong,
.capacity small,
.capacity span {
    display: block;
}
.capacity strong {
    font: 900 1.8rem var(--font-display);
}
.capacity small {
    color: white;
    font: 750 0.55rem var(--font-display);
    text-transform: uppercase;
}
.capacity span {
    margin-top: 4px;
    color: #738996;
    font-size: 0.72rem;
}
.progress {
    height: 4px;
    background: #101d27;
}
.progress i {
    display: block;
    height: 100%;
    background: var(--color-accent);
    transition: 0.2s;
}
.notice {
    margin-top: 12px;
    padding: 12px 14px;
    border: 1px solid #45b7ff40;
    color: #9ccbe8;
    background: #45b7ff0a;
    font-size: 0.75rem;
}
.notice.warning {
    display: flex;
    flex-direction: column;
    gap: 4px;
    border-color: #ffc45e55;
    color: #d9b36b;
    background: #ffc45e0a;
}
.notice.warning strong {
    color: #ffd48a;
}
.directory {
    margin-top: 14px;
    border: 1px solid #ffffff18;
    background: #050d16;
}
.directory > header {
    padding: 14px 15px;
    border-bottom: 1px solid #ffffff14;
}
.directory > header > div {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 5px;
}
.directory > header strong {
    font-size: 0.72rem;
}
.toolbar {
    display: grid;
    grid-template-columns: 1fr 220px;
    gap: 10px;
    padding: 14px;
}
.toolbar label {
    display: flex;
    flex-direction: column;
}
.toolbar label > span {
    margin-bottom: 6px;
    color: #8295a1;
    font: 750 0.48rem var(--font-display);
    text-transform: uppercase;
}
.toolbar input,
.toolbar select {
    box-sizing: border-box;
    width: 100%;
    min-height: 42px;
    padding: 10px 12px;
    border: 1px solid #ffffff1e;
    color: white;
    background: #07111e;
}
.club-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
    max-height: 480px;
    overflow: auto;
    padding: 0 14px 14px;
}
.club-grid button {
    display: grid;
    grid-template-columns: 44px minmax(0, 1fr) auto;
    align-items: center;
    gap: 11px;
    padding: 11px;
    border: 1px solid #ffffff16;
    color: white;
    background: #07111e;
    text-align: left;
    cursor: pointer;
}
.club-grid button:hover {
    border-color: #45b7ff55;
}
.club-grid button.selected {
    border-color: #45b7ff88;
    background: #45b7ff0d;
}
.club-grid button.disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
.logo {
    display: grid;
    width: 40px;
    height: 40px;
    place-items: center;
    overflow: hidden;
    border: 1px solid #45b7ff44;
    border-radius: 50%;
    color: var(--color-accent);
    font: 0.6rem var(--font-display);
}
.logo img {
    width: 30px;
    height: 30px;
    object-fit: contain;
}
.club-copy strong,
.club-copy small {
    display: block;
}
.club-copy strong {
    font-size: 0.78rem;
}
.club-copy small {
    margin-top: 4px;
    color: #667b88;
    font-size: 0.68rem;
}
.choice {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #718793;
}
.choice i {
    display: grid;
    box-sizing: border-box;
    width: 18px;
    height: 18px;
    place-items: center;
    border: 1px solid #657b88;
    font-style: normal;
}
.selected .choice {
    color: var(--color-accent);
}
.selected .choice i {
    border-color: var(--color-accent);
    background: var(--color-accent);
}
.selected .choice i::before {
    content: "✓";
    color: #03101b;
    font: bold 13px Arial;
}
.choice small {
    min-width: 42px;
    font-size: 0.62rem;
    text-align: right;
}
.empty {
    margin: 0 14px 14px;
    padding: 18px;
    border: 1px dashed #45b7ff44;
    color: #718895;
    font-size: 0.75rem;
}
.error {
    margin: 12px 0 0;
    padding: 11px;
    border: 1px solid #ff6d7755;
    color: #ff9da4;
    background: #ff6d7709;
}
@media (max-width: 700px) {
    .manager {
        padding: 15px;
    }
    .context {
        align-items: flex-start;
        flex-direction: column;
    }
    .capacity {
        text-align: left;
    }
    .toolbar,
    .club-grid {
        grid-template-columns: 1fr;
    }
    .club-grid {
        max-height: none;
    }
}
</style>
