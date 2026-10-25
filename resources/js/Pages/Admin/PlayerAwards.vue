<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ConfirmActionModal from "@/Components/Admin/ConfirmActionModal.vue";
import MediaIdPicker from "@/Components/Admin/MediaIdPicker.vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

const props = defineProps({
    awards: Array,
    seasons: Array,
    seasonId: Number,
    types: Array,
    categoryTypes: { type: Array, default: () => [] },
    candidates: Array,
    matches: Array,
    media: Array,
    filters: Object,
    can: Object,
});
const page = usePage();
const hasSeason = computed(() => Number.isInteger(props.seasonId));
const open = ref(false);
const editing = ref(null);
const typeOpen = ref(false);
const typeEditing = ref(null);
const typeSearch = ref("");
const typeStatus = ref("all");
const confirmDeactivate = ref(false);
const tab = ref("context");
const filterForm = ref({
    season_id: props.seasonId,
    award_type_id: props.filters.award_type_id || "",
    status: props.filters.status || "",
});
const blank = () => ({
    award_type_id: props.types[0]?.id || "",
    player_id: "",
    season_id: props.seasonId,
    match_id: null,
    period_key: "",
    title: "",
    editorial: "",
    quote: "",
    primary_media_id: null,
    status: "draft",
    is_featured: false,
    published_at: "",
    nominee_ids: [],
    correction_reason: "",
});
const form = useForm(blank());
const typeForm = useForm({
    id: null,
    name: "",
    period_type: "custom",
    description: "",
    is_active: true,
});
const selectedType = computed(() =>
    props.categoryTypes.find(
        (item) => Number(item.id) === Number(form.award_type_id),
    ),
);
const awardTypeOptions = computed(() => {
    const current = props.categoryTypes.find(
        (item) => Number(item.id) === Number(form.award_type_id),
    );
    return current && !current.is_active
        ? [...props.types, current]
        : props.types;
});
const filteredCategoryTypes = computed(() =>
    props.categoryTypes.filter((item) => {
        const matchesSearch = item.name
            .toLowerCase()
            .includes(typeSearch.value.toLowerCase());
        const matchesStatus =
            typeStatus.value === "all" ||
            (typeStatus.value === "active" ? item.is_active : !item.is_active);
        return matchesSearch && matchesStatus;
    }),
);
const selectedCandidate = computed(() =>
    props.candidates.find((item) => Number(item.id) === Number(form.player_id)),
);
const currentSeason = computed(() =>
    props.seasons.find((item) => Number(item.id) === Number(props.seasonId)),
);
const availableNominees = computed(() =>
    props.candidates.filter(
        (item) => Number(item.id) !== Number(form.player_id),
    ),
);
const periodLabel = computed(() =>
    selectedType.value?.period_type === "month"
        ? "Bulan penilaian"
        : "Periode penilaian",
);
const periodHelp = computed(() =>
    selectedType.value?.period_type === "month"
        ? "Bulan yang menjadi dasar penilaian penghargaan."
        : "Tuliskan periode yang mudah dipahami, misalnya Turnamen Ramadan 2026.",
);

watch(
    () => form.player_id,
    (playerId) => {
        form.nominee_ids = form.nominee_ids.filter(
            (id) => Number(id) !== Number(playerId),
        );
    },
);
watch(
    () => form.award_type_id,
    () => {
        if (!editing.value) {
            form.match_id = null;
            form.period_key = "";
        }
    },
);

function edit(award = null) {
    if (!hasSeason.value) return;

    editing.value = award;
    form.defaults(
        award
            ? {
                  ...blank(),
                  ...award,
                  season_id: props.seasonId,
                  published_at: award.published_at?.slice(0, 16) || "",
              }
            : blank(),
    );
    form.reset();
    form.clearErrors();
    tab.value = "context";
    open.value = true;
}
function save() {
    if (!hasSeason.value) return;

    const options = {
        preserveScroll: true,
        onSuccess: () => (open.value = false),
    };
    editing.value
        ? form.put(
              route("admin.player-awards.update", editing.value.id),
              options,
          )
        : form.post(route("admin.player-awards.store"), options);
}
function archiveAward() {
    if (confirm("Arsipkan penghargaan ini?"))
        router.post(
            route("admin.player-awards.archive", editing.value.id),
            {},
            { onSuccess: () => (open.value = false) },
        );
}
function applyFilters() {
    if (!hasSeason.value) return;

    router.get(route("admin.player-awards.index"), filterForm.value, {
        preserveState: true,
        replace: true,
    });
}
function resetFilters() {
    filterForm.value = {
        season_id: props.seasonId,
        award_type_id: "",
        status: "",
    };
    applyFilters();
}
function editType(item = null) {
    typeEditing.value = item;
    typeForm.defaults(
        item
            ? {
                  id: item.id,
                  name: item.name,
                  period_type: item.period_type,
                  description: item.description || "",
                  is_active: item.is_active,
              }
            : {
                  id: null,
                  name: "",
                  period_type: "custom",
                  description: "",
                  is_active: true,
              },
    );
    typeForm.reset();
    typeForm.clearErrors();
}
function requestSaveType() {
    if (
        typeEditing.value?.is_active &&
        !typeForm.is_active &&
        typeEditing.value.awards_count > 0
    ) {
        confirmDeactivate.value = true;
        return;
    }
    saveType();
}
function saveType() {
    typeForm.post(route("admin.player-awards.types.save"), {
        preserveScroll: true,
        onSuccess: () => {
            confirmDeactivate.value = false;
            editType();
        },
        onError: () => (confirmDeactivate.value = false),
    });
}
const periodTypeLabel = (value) =>
    ({
        match: "Per pertandingan",
        month: "Bulanan",
        season: "Per musim",
        custom: "Periode khusus",
    })[value] || value;
const periodTypeHelp = computed(
    () =>
        ({
            match: "Admin memilih satu pertandingan sebagai dasar award.",
            month: "Admin memilih bulan yang menjadi periode penilaian.",
            season: "Periode otomatis mengikuti musim kompetisi.",
            custom: "Admin menuliskan sendiri periode penilaian award.",
        })[typeForm.period_type],
);
const statusLabel = (value) =>
    ({
        draft: "Draft",
        scheduled: "Terjadwal",
        published: "Dipublikasikan",
        archived: "Diarsipkan",
    })[value] || value;
</script>

<template>
    <Head title="Player Awards" />
    <AuthenticatedLayout>
        <template #page-title>Player Awards</template>
        <div class="page">
            <div v-if="page.props.flash?.success" class="notice">
                {{ page.props.flash.success }}
            </div>
            <header class="hero">
                <div>
                    <span>Pengelolaan Penghargaan</span>
                    <h1>Player <b>Awards.</b></h1>
                    <p>
                        Kelola pemenang, kandidat, cerita, media, dan publikasi
                        penghargaan pemain.
                    </p>
                </div>
                <div class="actions">
                    <button
                        v-if="can.types"
                        class="secondary"
                        @click="(typeOpen = true), editType()"
                    >
                        Kategori Award</button
                    ><button v-if="can.create && hasSeason" @click="edit()">
                        + Buat Award
                    </button>
                </div>
            </header>

            <section class="summary">
                <article>
                    <b>{{ awards.length }}</b
                    ><span>Total Award</span>
                </article>
                <article>
                    <b>{{
                        awards.filter((item) => item.status === "published")
                            .length
                    }}</b
                    ><span>Dipublikasikan</span>
                </article>
                <article>
                    <b>{{
                        awards.filter((item) => item.status === "draft").length
                    }}</b
                    ><span>Draft</span>
                </article>
                <article>
                    <b>{{
                        awards.find((item) => item.is_featured)?.player_name ||
                        "—"
                    }}</b
                    ><span>Penghargaan Utama</span>
                </article>
            </section>

            <form
                v-if="hasSeason"
                class="filters"
                @submit.prevent="applyFilters"
            >
                <label
                    ><span>Musim kompetisi</span
                    ><select v-model="filterForm.season_id">
                        <option
                            v-for="season in seasons"
                            :key="season.id"
                            :value="season.id"
                        >
                            {{ season.name }}
                        </option></select
                    ><small>Tampilkan award dari musim tertentu.</small></label
                >
                <label
                    ><span>Kategori penghargaan</span
                    ><select v-model="filterForm.award_type_id">
                        <option value="">Semua kategori</option>
                        <option
                            v-for="type in categoryTypes"
                            :key="type.id"
                            :value="type.id"
                        >
                            {{ type.name }}
                        </option></select
                    ><small>Saring berdasarkan jenis penghargaan.</small></label
                >
                <label
                    ><span>Status publikasi</span
                    ><select v-model="filterForm.status">
                        <option value="">Semua status</option>
                        <option value="draft">Draft</option>
                        <option value="scheduled">Terjadwal</option>
                        <option value="published">Dipublikasikan</option>
                        <option value="archived">Diarsipkan</option></select
                    ><small>Saring berdasarkan tahapan publikasi.</small></label
                >
                <div class="filter-actions">
                    <button
                        type="button"
                        class="secondary"
                        @click="resetFilters"
                    >
                        Reset</button
                    ><button>Terapkan Filter</button>
                </div>
            </form>

            <section v-else class="season-empty">
                <span>Data penghargaan pemain</span>
                <h2>Belum ada musim kompetisi</h2>
                <p>
                    Tambahkan kompetisi dan musim terlebih dahulu melalui menu
                    Kompetisi &amp; Musim. Setelah itu, award dapat dibuat untuk
                    pemain pada musim yang dipilih.
                </p>
                <small>
                    Master Data Kategori Award tetap dapat dikelola melalui
                    tombol Kategori Award di atas.
                </small>
            </section>

            <section v-if="hasSeason" class="award-grid">
                <button
                    v-for="award in awards"
                    :key="award.id"
                    class="award-card"
                    @click="edit(award)"
                >
                    <div
                        class="photo"
                        :style="{
                            backgroundImage: `linear-gradient(0deg,#06111fee,transparent),url('${award.player_image || '/Assets/Dokumentasi/Docs-wahyu.jpeg'}')`,
                        }"
                    >
                        <span>{{ award.type_name }}</span
                        ><i>{{ statusLabel(award.status) }}</i>
                    </div>
                    <div>
                        <small>{{ award.period_key }}</small>
                        <h2>{{ award.player_name }}</h2>
                        <strong>{{ award.title }}</strong>
                        <p>{{ award.editorial }}</p>
                    </div>
                </button>
                <p v-if="!awards.length" class="empty">
                    Belum ada award yang sesuai dengan filter.
                </p>
            </section>

            <div v-if="open" class="modal" @click.self="open = false">
                <form class="dialog" @submit.prevent="save">
                    <header>
                        <div>
                            <span>Player Awards</span>
                            <h2>{{ editing ? "Edit" : "Buat" }} Award</h2>
                            <p>
                                Lengkapi informasi yang akan ditampilkan pada
                                website.
                            </p>
                        </div>
                        <button
                            type="button"
                            aria-label="Tutup"
                            @click="open = false"
                        >
                            ×
                        </button>
                    </header>
                    <nav>
                        <button
                            v-for="item in [
                                ['context', 'Informasi Award'],
                                ['candidates', 'Pemain'],
                                ['content', 'Konten'],
                                ['publish', 'Publikasi'],
                            ]"
                            :key="item[0]"
                            type="button"
                            :class="{ active: tab === item[0] }"
                            @click="tab = item[0]"
                        >
                            {{ item[1] }}
                        </button>
                    </nav>

                    <div v-show="tab === 'context'" class="fields">
                        <label
                            ><span>Kategori award</span
                            ><select v-model="form.award_type_id" required>
                                <option
                                    v-for="type in awardTypeOptions"
                                    :key="type.id"
                                    :value="type.id"
                                >
                                    {{ type.name }}
                                </option></select
                            ><small
                                >Menentukan jenis award dan periode
                                penilaiannya.</small
                            ></label
                        >
                        <div class="field-readonly">
                            <span>Musim kompetisi</span
                            ><strong>{{ currentSeason?.name }}</strong
                            ><small
                                >Mengikuti musim yang dipilih pada filter
                                halaman.</small
                            >
                        </div>
                        <label
                            v-if="selectedType?.period_type === 'match'"
                            class="full"
                            ><span>Pertandingan</span
                            ><select v-model="form.match_id" required>
                                <option :value="null">
                                    Pilih pertandingan
                                </option>
                                <option
                                    v-for="match in matches"
                                    :key="match.id"
                                    :value="match.id"
                                >
                                    {{ match.label }}
                                </option></select
                            ><small
                                >Pertandingan yang menjadi dasar
                                penghargaan.</small
                            ></label
                        >
                        <label
                            v-if="
                                ['month', 'custom'].includes(
                                    selectedType?.period_type,
                                )
                            "
                            ><span>{{ periodLabel }}</span
                            ><input
                                v-model="form.period_key"
                                :type="
                                    selectedType?.period_type === 'month'
                                        ? 'month'
                                        : 'text'
                                "
                                :placeholder="
                                    selectedType?.period_type === 'month'
                                        ? ''
                                        : 'Contoh: Turnamen Ramadan 2026'
                                "
                                required
                            /><small>{{ periodHelp }}</small></label
                        >
                        <div v-else class="field-readonly">
                            <span>Periode award</span
                            ><strong>{{
                                selectedType?.period_type === "match"
                                    ? "Mengikuti pertandingan"
                                    : currentSeason?.name
                            }}</strong
                            ><small
                                >Periode akan diisi otomatis oleh sistem.</small
                            >
                        </div>
                        <label
                            ><span>Judul publikasi</span
                            ><input
                                v-model="form.title"
                                placeholder="Contoh: Player of the Month"
                                maxlength="150"
                                required
                            /><small
                                >Judul yang tampil pada website.</small
                            ></label
                        >
                    </div>

                    <div v-show="tab === 'candidates'" class="candidate-layout">
                        <div class="fields">
                            <label class="full"
                                ><span>Pemenang award</span
                                ><select v-model="form.player_id" required>
                                    <option value="">
                                        Pilih pemain pemenang
                                    </option>
                                    <option
                                        v-for="candidate in candidates"
                                        :key="candidate.id"
                                        :value="candidate.id"
                                    >
                                        #{{ candidate.number }}
                                        {{ candidate.name }} ·
                                        {{ candidate.position }}
                                    </option></select
                                ><small
                                    >Pemain yang menerima dan ditampilkan
                                    sebagai pemenang award.</small
                                ></label
                            >
                        </div>
                        <div v-if="selectedCandidate" class="snapshot">
                            <img
                                :src="selectedCandidate.image"
                                :alt="selectedCandidate.name"
                            />
                            <div>
                                <span>Pemenang terpilih</span>
                                <h3>{{ selectedCandidate.name }}</h3>
                                <p>
                                    {{
                                        selectedCandidate.statistics.appearances
                                    }}
                                    laga ·
                                    {{ selectedCandidate.statistics.goals }} gol
                                    ·
                                    {{ selectedCandidate.statistics.assists }}
                                    assist
                                </p>
                            </div>
                        </div>
                        <section class="nominees">
                            <header>
                                <div>
                                    <b>Kandidat Nominasi</b>
                                    <p>
                                        Opsional. Pemenang otomatis tercatat
                                        sebagai kandidat.
                                    </p>
                                </div>
                                <span
                                    >{{ form.nominee_ids.length }} dipilih</span
                                >
                            </header>
                            <div class="nominee-grid">
                                <label
                                    v-for="candidate in availableNominees"
                                    :key="candidate.id"
                                    :class="{
                                        checked: form.nominee_ids.includes(
                                            candidate.id,
                                        ),
                                    }"
                                    ><input
                                        v-model="form.nominee_ids"
                                        type="checkbox"
                                        :value="candidate.id"
                                    /><i aria-hidden="true"></i
                                    ><span
                                        >#{{ candidate.number }}
                                        {{ candidate.name
                                        }}<small>{{
                                            candidate.position
                                        }}</small></span
                                    ></label
                                >
                            </div>
                        </section>
                    </div>

                    <div v-show="tab === 'content'" class="fields">
                        <label class="full"
                            ><span>Cerita penghargaan</span
                            ><textarea
                                v-model="form.editorial"
                                rows="5"
                                maxlength="1500"
                                placeholder="Jelaskan performa dan alasan pemain menerima penghargaan"
                            ></textarea
                            ><small
                                >Konten utama yang menjelaskan pencapaian pemain
                                pada website.</small
                            ></label
                        >
                        <label class="full"
                            ><span>Kutipan pemain <em>Opsional</em></span
                            ><textarea
                                v-model="form.quote"
                                rows="3"
                                maxlength="500"
                                placeholder="Tuliskan kutipan singkat dari pemain"
                            ></textarea
                            ><small
                                >Kosongkan jika kutipan tidak digunakan.</small
                            ></label
                        >
                        <label class="full"
                            ><span>Foto penghargaan <em>Opsional</em></span
                            ><MediaIdPicker
                                v-model="form.primary_media_id"
                                :items="media"
                            /><small
                                >Jika tidak dipilih, website menggunakan foto
                                profil pemain.</small
                            ></label
                        >
                    </div>

                    <div v-show="tab === 'publish'" class="fields">
                        <label
                            ><span>Status publikasi</span
                            ><select v-model="form.status">
                                <option value="draft">Draft</option>
                                <option value="scheduled">Terjadwal</option>
                                <option value="published">
                                    Dipublikasikan
                                </option>
                                <option value="archived">
                                    Diarsipkan
                                </option></select
                            ><small
                                >Menentukan kapan award dapat dilihat
                                publik.</small
                            ></label
                        >
                        <label v-if="form.status === 'scheduled'"
                            ><span>Jadwal publikasi</span
                            ><input
                                v-model="form.published_at"
                                type="datetime-local"
                                required
                            /><small
                                >Waktu award otomatis mulai tampil pada
                                website.</small
                            ></label
                        >
                        <div v-else class="field-readonly">
                            <span>Waktu publikasi</span
                            ><strong>{{
                                form.status === "published"
                                    ? "Saat disimpan"
                                    : "Belum ditentukan"
                            }}</strong
                            ><small>{{
                                form.status === "published"
                                    ? "Award akan langsung tampil pada website."
                                    : "Waktu publikasi belum diperlukan untuk status ini."
                            }}</small>
                        </div>
                        <label
                            class="choice full"
                            :class="{ checked: form.is_featured }"
                            ><input
                                v-model="form.is_featured"
                                type="checkbox"
                            /><i aria-hidden="true"></i
                            ><span
                                ><strong>Penghargaan utama</strong
                                ><small
                                    >Prioritaskan award ini sebagai penghargaan
                                    utama pada website.</small
                                ></span
                            ></label
                        >
                        <label
                            v-if="editing?.status === 'published'"
                            class="full"
                            ><span>Alasan perubahan</span
                            ><input
                                v-model="form.correction_reason"
                                placeholder="Jelaskan alasan memperbarui award yang sudah dipublikasikan"
                                required
                            /><small
                                >Disimpan sebagai riwayat internal koreksi
                                konten.</small
                            ></label
                        >
                    </div>

                    <div class="errors">
                        <p v-for="(error, key) in form.errors" :key="key">
                            {{ error }}
                        </p>
                    </div>
                    <footer>
                        <button
                            v-if="
                                editing &&
                                can.archive &&
                                editing.status !== 'archived'
                            "
                            type="button"
                            class="danger"
                            @click="archiveAward"
                        >
                            Arsipkan</button
                        ><button
                            type="button"
                            class="secondary"
                            @click="open = false"
                        >
                            Batal</button
                        ><button :disabled="form.processing">
                            {{
                                form.processing
                                    ? "Menyimpan..."
                                    : "Simpan Award"
                            }}
                        </button>
                    </footer>
                </form>
            </div>

            <div v-if="typeOpen" class="modal" @click.self="typeOpen = false">
                <form
                    class="dialog type-dialog"
                    @submit.prevent="requestSaveType"
                >
                    <header>
                        <div>
                            <span>Master Data</span>
                            <h2>Kategori Award</h2>
                        </div>
                        <button type="button" @click="typeOpen = false">
                            ×
                        </button>
                    </header>
                    <section class="type-manager">
                        <header>
                            <div>
                                <strong>Daftar Kategori</strong
                                ><small
                                    >{{ filteredCategoryTypes.length }} kategori
                                    ditemukan</small
                                >
                            </div>
                            <button type="button" @click="editType()">
                                + Tambah Kategori
                            </button>
                        </header>
                        <div class="type-filters">
                            <label
                                ><span>Pencarian kategori</span
                                ><input
                                    v-model="typeSearch"
                                    placeholder="Cari nama kategori"
                            /></label>
                            <label
                                ><span>Status kategori</span
                                ><select v-model="typeStatus">
                                    <option value="all">Semua status</option>
                                    <option value="active">Aktif</option>
                                    <option value="inactive">Nonaktif</option>
                                </select></label
                            >
                        </div>
                        <div class="type-list">
                            <button
                                v-for="item in filteredCategoryTypes"
                                :key="item.id"
                                type="button"
                                :class="{
                                    selected: typeEditing?.id === item.id,
                                }"
                                @click="editType(item)"
                            >
                                <span
                                    ><strong>{{ item.name }}</strong
                                    ><small
                                        >{{
                                            periodTypeLabel(item.period_type)
                                        }}
                                        · {{ item.awards_count }} award</small
                                    ></span
                                >
                                <i :class="{ inactive: !item.is_active }">{{
                                    item.is_active ? "Aktif" : "Nonaktif"
                                }}</i>
                            </button>
                            <p
                                v-if="!filteredCategoryTypes.length"
                                class="empty"
                            >
                                Tidak ada kategori yang sesuai.
                            </p>
                        </div>
                    </section>
                    <div class="type-form-title">
                        <span>{{
                            typeEditing ? "Edit Kategori" : "Kategori Baru"
                        }}</span
                        ><strong>{{
                            typeEditing?.name || "Tambahkan kategori award"
                        }}</strong>
                    </div>
                    <div class="fields">
                        <label
                            ><span>Nama kategori</span
                            ><input
                                v-model="typeForm.name"
                                maxlength="100"
                                placeholder="Contoh: Best Goalkeeper"
                                required
                            /><small
                                >Nama ini tampil pada pilihan award dan halaman
                                publik.</small
                            ></label
                        ><label
                            ><span>Jenis penilaian</span
                            ><select v-model="typeForm.period_type">
                                <option value="match">Per pertandingan</option>
                                <option value="month">Bulanan</option>
                                <option value="season">Per musim</option>
                                <option value="custom">
                                    Periode khusus
                                </option></select
                            ><small>{{ periodTypeHelp }}</small></label
                        ><label class="full"
                            ><span>Penjelasan kategori <em>Opsional</em></span
                            ><textarea
                                v-model="typeForm.description"
                                rows="4"
                                maxlength="500"
                                placeholder="Jelaskan kapan kategori ini digunakan"
                            ></textarea
                            ><small
                                >Digunakan sebagai panduan internal bagi
                                admin.</small
                            ></label
                        ><label
                            class="choice full"
                            :class="{ checked: typeForm.is_active }"
                            ><input
                                v-model="typeForm.is_active"
                                type="checkbox"
                            /><i></i
                            ><span
                                ><strong>Kategori aktif</strong
                                ><small>{{
                                    typeForm.is_active
                                        ? "Tersedia saat membuat award baru."
                                        : "Tidak tersedia untuk award baru; data lama tetap aman."
                                }}</small></span
                            ></label
                        >
                    </div>
                    <footer>
                        <button
                            v-if="typeEditing"
                            type="button"
                            class="secondary"
                            @click="editType()"
                        >
                            Batal Edit</button
                        ><button :disabled="typeForm.processing">
                            {{
                                typeForm.processing
                                    ? "Menyimpan..."
                                    : typeEditing
                                      ? "Simpan Perubahan"
                                      : "Tambah Kategori"
                            }}
                        </button>
                    </footer>
                </form>
            </div>
            <ConfirmActionModal
                :open="confirmDeactivate"
                title="Nonaktifkan kategori?"
                :item-name="typeEditing?.name || ''"
                :description="`Kategori ini telah digunakan oleh ${typeEditing?.awards_count || 0} award.`"
                note="Kategori tidak tersedia untuk award baru, tetapi seluruh award lama tetap aman dan tetap menampilkan nama kategorinya."
                confirm-label="Nonaktifkan"
                processing-label="Menyimpan..."
                variant="warning"
                :processing="typeForm.processing"
                @confirm="saveType"
                @cancel="confirmDeactivate = false"
            />
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.page {
    max-width: 1450px;
    margin: auto;
}
.notice {
    margin-bottom: 12px;
    padding: 12px;
    border: 1px solid #45b7ff55;
}
.hero {
    min-height: 210px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 24px;
    padding: 34px;
    border: 1px solid #ffffff18;
    background: radial-gradient(circle at 80% 20%, #45b7ff25, transparent 28%),
        #07111e;
}
.hero span,
.dialog header > div > span {
    color: var(--color-accent);
    font: 800 0.55rem var(--font-display);
    letter-spacing: 0.16em;
    text-transform: uppercase;
}
.hero h1 {
    margin: 8px 0;
    font: 900 clamp(3rem, 5vw, 5rem) / 0.85 var(--font-display);
    text-transform: uppercase;
}
.hero h1 b {
    color: var(--color-accent);
}
.hero p,
.dialog header p {
    margin: 0;
    color: #83939f;
}
.actions {
    display: flex;
    gap: 8px;
}
.actions button,
.dialog footer button,
.filter-actions button {
    min-height: 42px;
    padding: 0 15px;
    border: 1px solid var(--color-accent);
    color: #03101b;
    background: var(--color-accent);
    font: 800 0.55rem var(--font-display);
    text-transform: uppercase;
}
.secondary {
    color: white !important;
    border-color: #ffffff25 !important;
    background: none !important;
}
.summary {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin: 14px 0;
}
.summary article {
    padding: 18px;
    border: 1px solid #ffffff18;
    background: #07111e;
}
.summary b,
.summary span {
    display: block;
}
.summary b {
    font: 900 1.5rem var(--font-display);
    text-transform: uppercase;
}
.summary span {
    margin-top: 4px;
    color: #71828e;
}
.filters {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr auto;
    align-items: end;
    gap: 10px;
    margin-bottom: 14px;
    padding: 16px;
    border: 1px solid #ffffff18;
    background: #07111e;
}
.filters label,
.fields label {
    display: flex;
    min-width: 0;
    flex-direction: column;
}
.filters label > span,
.fields label > span,
.field-readonly > span {
    margin-bottom: 7px;
    color: #a6b5bf;
    font: 750 0.52rem var(--font-display);
    text-transform: uppercase;
}
.filters select,
.fields input,
.fields select,
.fields textarea {
    box-sizing: border-box;
    width: 100%;
    min-height: 43px;
    padding: 11px 12px;
    border: 1px solid #ffffff20;
    color: white;
    background: #050d16;
    font: inherit;
}
.filters small,
.fields label > small,
.field-readonly small {
    margin-top: 6px;
    color: #718590;
    font-size: 0.66rem;
    line-height: 1.4;
}
.filter-actions {
    display: flex;
    gap: 7px;
    padding-bottom: 22px;
}
.season-empty {
    margin-bottom: 14px;
    padding: 28px;
    border: 1px dashed #45b7ff55;
    background: #07111e;
}
.season-empty > span {
    color: var(--color-accent);
    font: 800 0.52rem var(--font-display);
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.season-empty h2 {
    margin: 9px 0 7px;
    font: 900 1.6rem var(--font-display);
    text-transform: uppercase;
}
.season-empty p {
    max-width: 720px;
    margin: 0;
    color: #91a3ae;
    line-height: 1.6;
}
.season-empty small {
    display: block;
    margin-top: 12px;
    color: #718590;
}
.award-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}
.award-card {
    padding: 0;
    border: 1px solid #ffffff18;
    color: white;
    background: #07111e;
    text-align: left;
}
.photo {
    height: 220px;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 14px;
    background-position: center;
    background-size: cover;
}
.photo span,
.photo i {
    padding: 5px 7px;
    border: 1px solid #45b7ff77;
    color: var(--color-accent);
    background: #06111edd;
    font: 800 0.5rem var(--font-display);
    font-style: normal;
    text-transform: uppercase;
}
.award-card > div:last-child {
    padding: 17px;
}
.award-card small {
    color: var(--color-accent);
}
.award-card h2 {
    margin: 7px 0 3px;
    font: 900 2.2rem var(--font-display);
    text-transform: uppercase;
}
.award-card strong {
    text-transform: uppercase;
}
.award-card p {
    height: 42px;
    overflow: hidden;
    color: #81919d;
}
.modal {
    position: fixed;
    z-index: 100;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 16px;
    background: #01050add;
}
.dialog {
    width: min(880px, 100%);
    max-height: 92vh;
    overflow: auto;
    border: 1px solid #45b7ff55;
    background: #07111e;
}
.type-dialog {
    width: min(980px, 100%);
}
.type-manager {
    padding: 18px 20px;
    border-bottom: 1px solid #ffffff18;
    background: #050d1680;
}
.type-manager > header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.type-manager > header strong,
.type-manager > header small {
    display: block;
}
.type-manager > header strong {
    font: 800 0.64rem var(--font-display);
    text-transform: uppercase;
}
.type-manager > header small {
    margin-top: 4px;
    color: #718590;
    font-size: 0.66rem;
}
.type-manager > header button {
    min-height: 36px;
    padding: 0 11px;
    border: 1px solid var(--color-accent);
    color: #03101b;
    background: var(--color-accent);
    font: 800 0.5rem var(--font-display);
    text-transform: uppercase;
}
.type-filters {
    display: grid;
    grid-template-columns: 1fr 190px;
    gap: 8px;
    margin-top: 14px;
}
.type-filters label {
    display: flex;
    flex-direction: column;
    gap: 6px;
    color: #8fa0ac;
    font: 700 0.49rem var(--font-display);
    text-transform: uppercase;
}
.type-filters input,
.type-filters select {
    min-height: 40px;
    box-sizing: border-box;
    padding: 10px;
    border: 1px solid #ffffff20;
    color: white;
    background: #07111e;
}
.type-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 7px;
    max-height: 210px;
    margin-top: 10px;
    overflow: auto;
}
.type-list > button {
    display: flex;
    min-width: 0;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 11px;
    border: 1px solid #ffffff16;
    color: white;
    background: #07111e;
    text-align: left;
}
.type-list > button.selected {
    border-color: #45b7ff77;
    background: #45b7ff0c;
}
.type-list span,
.type-list strong,
.type-list small {
    display: block;
    min-width: 0;
}
.type-list strong {
    overflow: hidden;
    font-size: 0.74rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.type-list small {
    margin-top: 4px;
    color: #718590;
    font-size: 0.61rem;
}
.type-list i {
    color: var(--color-accent);
    font: 750 0.47rem var(--font-display);
    font-style: normal;
    text-transform: uppercase;
}
.type-list i.inactive {
    color: #7b8c97;
}
.type-form-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 17px 20px 0;
}
.type-form-title span {
    color: var(--color-accent);
    font: 800 0.51rem var(--font-display);
    text-transform: uppercase;
}
.type-form-title strong {
    font-size: 0.75rem;
}
.dialog > header {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 20px;
    border-bottom: 1px solid #ffffff18;
}
.dialog h2 {
    margin: 4px 0;
    font: 900 1.5rem var(--font-display);
    text-transform: uppercase;
}
.dialog header > button {
    border: 0;
    color: white;
    background: none;
    font-size: 1.5rem;
}
.dialog nav {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    border-bottom: 1px solid #ffffff18;
}
.dialog nav button {
    min-height: 45px;
    padding: 10px;
    border: 0;
    border-bottom: 2px solid transparent;
    color: #7b8c98;
    background: none;
}
.dialog nav button.active {
    color: white;
    border-color: var(--color-accent);
    background: #45b7ff08;
}
.fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    padding: 20px;
}
.fields label > span em {
    margin-left: 4px;
    color: #718590;
    font-family: Arial, sans-serif;
    font-size: 0.62rem;
    font-style: normal;
    text-transform: none;
}
.full {
    grid-column: 1/-1;
}
.field-readonly {
    display: flex;
    min-width: 0;
    flex-direction: column;
    padding: 11px 12px;
    border: 1px solid #ffffff14;
    background: #050d1699;
}
.field-readonly strong {
    min-height: 22px;
    color: white;
    font-size: 0.86rem;
}
.errors {
    padding: 0 20px;
    color: #ff9ca3;
}
.dialog footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 15px 20px;
    border-top: 1px solid #ffffff18;
}
.dialog footer button:disabled {
    opacity: 0.55;
}
.danger {
    margin-right: auto !important;
    color: #ff9ca3 !important;
    border-color: #ff7d8655 !important;
    background: none !important;
}
.candidate-layout {
    padding-bottom: 20px;
}
.snapshot {
    display: flex;
    align-items: center;
    gap: 15px;
    margin: 0 20px;
    padding: 12px;
    border: 1px solid #45b7ff44;
}
.snapshot img {
    width: 75px;
    height: 75px;
    object-fit: cover;
}
.snapshot span {
    color: var(--color-accent);
    font: 750 0.5rem var(--font-display);
    text-transform: uppercase;
}
.snapshot h3,
.snapshot p {
    margin: 4px 0;
}
.snapshot p {
    color: #8596a1;
    font-size: 0.75rem;
}
.nominees {
    margin: 18px 20px 0;
    border-top: 1px solid #ffffff14;
}
.nominees > header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 0 12px;
}
.nominees b {
    color: var(--color-accent);
    font: 800 0.6rem var(--font-display);
    text-transform: uppercase;
}
.nominees p {
    margin: 4px 0 0;
    color: #718590;
    font-size: 0.68rem;
}
.nominees > header > span {
    color: #8295a1;
    font-size: 0.68rem;
}
.nominee-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}
.nominee-grid label,
.choice {
    position: relative;
    display: grid !important;
    grid-template-columns: 18px 1fr;
    align-items: center;
    gap: 10px;
    padding: 11px;
    border: 1px solid #ffffff16;
    background: #050d16;
    cursor: pointer;
}
.nominee-grid input,
.choice input {
    position: absolute;
    width: 1px;
    height: 1px;
    opacity: 0;
}
.nominee-grid i,
.choice i {
    display: grid;
    width: 18px;
    height: 18px;
    place-items: center;
    box-sizing: border-box;
    border: 1px solid #647985;
    font-style: normal;
}
.nominee-grid label.checked,
.choice.checked {
    border-color: #45b7ff66;
    background: #45b7ff0a;
}
.nominee-grid label.checked i,
.choice.checked i {
    border-color: var(--color-accent);
    background: var(--color-accent);
}
.nominee-grid label.checked i::before,
.choice.checked i::before {
    content: "✓";
    color: #03101b;
    font: bold 13px Arial;
}
.nominee-grid label > span {
    color: white;
    font-size: 0.76rem;
}
.nominee-grid label small {
    display: block;
    margin-top: 3px;
    color: #718590;
    font-size: 0.62rem;
}
.choice > span {
    margin: 0 !important;
    text-transform: none !important;
}
.choice strong,
.choice small {
    display: block;
}
.choice strong {
    color: white;
    font: 750 0.6rem var(--font-display);
    text-transform: uppercase;
}
.choice small {
    margin-top: 4px;
    color: #718590;
    font:
        400 0.66rem/1.4 Arial,
        sans-serif;
}
.empty {
    color: #7c8e99;
}
.type-dialog .choice {
    margin: 0;
}
@media (max-width: 950px) {
    .filters {
        grid-template-columns: 1fr 1fr;
    }
    .filter-actions {
        padding: 0;
    }
    .award-grid {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 650px) {
    .hero {
        align-items: flex-start;
        flex-direction: column;
    }
    .actions {
        width: 100%;
    }
    .actions button {
        flex: 1;
    }
    .summary,
    .filters,
    .fields {
        grid-template-columns: 1fr 1fr;
    }
    .award-grid {
        grid-template-columns: 1fr;
    }
    .dialog nav {
        grid-template-columns: 1fr 1fr;
    }
    .full {
        grid-column: 1/-1;
    }
    .filter-actions {
        grid-column: 1/-1;
    }
    .nominee-grid {
        grid-template-columns: 1fr;
    }
    .type-filters,
    .type-list {
        grid-template-columns: 1fr;
    }
    .type-manager > header {
        align-items: flex-start;
        flex-direction: column;
    }
    .type-manager > header button {
        width: 100%;
    }
}
@media (max-width: 450px) {
    .summary,
    .filters,
    .fields {
        grid-template-columns: 1fr;
    }
    .full,
    .filter-actions {
        grid-column: auto;
    }
    .filter-actions {
        flex-direction: column;
    }
    .dialog footer {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }
    .dialog footer .danger {
        grid-column: 1/-1;
        margin: 0 !important;
    }
}
</style>
