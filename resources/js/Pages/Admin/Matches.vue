<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ConfirmActionModal from "@/Components/Admin/ConfirmActionModal.vue";
import { Head, Link, router, useForm, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { computed, onMounted, onUnmounted, ref, watch } from "vue";
const p = defineProps({
        matches: Object,
        filters: Object,
        clubs: Array,
        seasons: Array,
        players: Array,
        media: Array,
        can: Object,
    }),
    page = usePage(),
    open = ref(false),
    lineupOpen = ref(false),
    eventsOpen = ref(false),
    editing = ref(null),
    editingEvent = ref(null),
    confirmOpen = ref(false),
    confirmAction = ref(null),
    confirmTarget = ref(null),
    confirmProcessing = ref(false),
    confirmError = ref(""),
    tab = ref("fixture"),
    slugTouched = ref(false),
    filtering = ref(false),
    refreshingSeasons = ref(false),
    filter = ref({
        search: p.filters?.search ?? "",
        month: p.filters?.month ?? "",
        status: p.filters?.status ?? "",
        match_type: p.filters?.match_type ?? "",
        season_id: p.filters?.season_id ?? "",
    });
const matchTypeOptions = [
    ["competitive", "Kompetisi"],
    ["friendly", "Persahabatan"],
    ["sparing", "Sparing"],
    ["tournament", "Turnamen"],
    ["exhibition", "Ekshibisi"],
];
const matchTypeLabel = (value) =>
    matchTypeOptions.find(([key]) => key === value)?.[1] || value;
const statusOptions = [
    ["draft", "Draft"],
    ["scheduled", "Terjadwal"],
    ["live", "Sedang berlangsung"],
    ["completed", "Selesai"],
    ["postponed", "Ditunda"],
    ["cancelled", "Dibatalkan"],
    ["walkover", "Menang WO"],
];
const statusLabel = (value) =>
    statusOptions.find(([key]) => key === value)?.[1] || value;
const hasFilters = computed(() =>
    Object.values(filter.value).some((value) => value !== "" && value !== null),
);
const featuredMatch = computed(() =>
    p.matches.data.find((match) => match.is_featured),
);
const canBeFeatured = computed(() =>
    ["scheduled", "postponed"].includes(form.status),
);
const isSparing = computed(() => form.match_type === "sparing");
const isAutomaticScore = computed(() => form.score_mode === "automatic");
const formatKickoff = (value) => {
    if (!value) return { date: "Belum diatur", time: "" };
    const date = new Date(value);
    return {
        date: new Intl.DateTimeFormat("id-ID", {
            day: "2-digit",
            month: "short",
            year: "numeric",
        }).format(date),
        time: `${String(date.getHours()).padStart(2, "0")}.${String(date.getMinutes()).padStart(2, "0")} WIB`,
    };
};
const scoreDisplay = (match) =>
    match.home_score === null || match.away_score === null
        ? "Belum ada"
        : `${match.home_score} — ${match.away_score}`;
const blank = () => ({
    slug: "",
    season_id: null,
    match_type: "competitive",
    matchday: null,
    round_name: "",
    home_club_id: "",
    away_club_id: "",
    kickoff_at: "",
    duration_minutes: 120,
    timezone: "Asia/Jakarta",
    venue_name: "",
    venue_address: "",
    status: "draft",
    score_mode: "automatic",
    home_score: null,
    away_score: null,
    home_penalty_score: null,
    away_penalty_score: null,
    manual_score_reason: "",
    count_for_standings: true,
    is_featured: false,
    is_public: false,
    cover_media_id: null,
    short_report: "",
    attendance: null,
});
const form = useForm(blank());
const lineup = useForm({ lineups: [] });
const eventBlank = () => ({
    club_id: "",
    type: "goal",
    period: "first_half",
    minute: 0,
    added_minute: 0,
    primary_player_id: null,
    primary_player_name: "",
    secondary_player_id: null,
    secondary_player_name: "",
    note: "",
    is_public: true,
    sort_order: 0,
    correction_reason: "",
});
const eventForm = useForm(eventBlank());
const eventTypeLabels = {
    goal: "Gol",
    penalty_goal: "Gol penalti",
    own_goal: "Gol bunuh diri",
    missed_penalty: "Penalti gagal",
    yellow_card: "Kartu kuning",
    second_yellow: "Kartu kuning kedua",
    red_card: "Kartu merah",
    substitution: "Pergantian pemain",
    note: "Catatan pertandingan",
};
const isSubstitutionEvent = computed(() => eventForm.type === "substitution");
const supportsAssist = computed(() => eventForm.type === "goal");
const needsPrimaryPlayer = computed(() => eventForm.type !== "note");
const primaryPlayerLabel = computed(() => {
    if (isSubstitutionEvent.value) return "Pemain keluar";
    if (eventForm.type === "missed_penalty") return "Penendang penalti";
    if (["yellow_card", "second_yellow", "red_card"].includes(eventForm.type))
        return "Penerima kartu";
    if (eventForm.type === "own_goal") return "Pelaku gol bunuh diri";
    if (["goal", "penalty_goal"].includes(eventForm.type))
        return "Pencetak gol";
    return "Pemain terkait";
});
watch(
    () => eventForm.type,
    (type, previous) => {
        if (!previous || type === previous) return;
        eventForm.secondary_player_id = null;
        eventForm.secondary_player_name = "";
    },
);
watch(
    () => form.status,
    (status) => {
        if (!["scheduled", "postponed"].includes(status)) {
            form.is_featured = false;
        }
        if (status === "walkover") form.score_mode = "manual";
    },
);
watch(
    () => form.match_type,
    (type) => {
        if (type !== "sparing") return;
        form.count_for_standings = false;
        form.matchday = null;
        form.round_name = "";
    },
);
function edit(m = null) {
    editing.value = m;
    slugTouched.value = Boolean(m);
    form.defaults(m ? { ...blank(), ...m } : blank());
    form.reset();
    form.clearErrors();
    tab.value = "fixture";
    open.value = true;
}
onMounted(() => {
    if (
        new URLSearchParams(window.location.search).get("action") ===
            "create" &&
        p.can?.create
    )
        edit();
});
let heartbeatTimer = null;
const heartbeatArea = computed(() =>
    eventsOpen.value ? "events" : lineupOpen.value ? "lineup" : open.value && tab.value === "result" ? "result" : null,
);
async function sendHeartbeat() {
    if (!editing.value?.id || !heartbeatArea.value) return;
    try {
        await axios.post(route("admin.matches.heartbeat", editing.value.id), { area: heartbeatArea.value });
    } catch (_) {
        // Kehadiran admin hanya optimasi pengingat; kegagalan tidak boleh mengganggu form.
    }
}
watch([heartbeatArea, () => editing.value?.id], () => {
    clearInterval(heartbeatTimer);
    heartbeatTimer = null;
    if (!heartbeatArea.value || !editing.value?.id) return;
    sendHeartbeat();
    heartbeatTimer = setInterval(sendHeartbeat, 60000);
});
onUnmounted(() => clearInterval(heartbeatTimer));
const slugify = (value) =>
    value
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-|-$/g, "");
watch(
    () => [form.home_club_id, form.away_club_id],
    ([homeId, awayId]) => {
        if (editing.value || slugTouched.value || !homeId || !awayId) return;
        const home = p.clubs.find((club) => club.id == homeId)?.display_name;
        const away = p.clubs.find((club) => club.id == awayId)?.display_name;
        if (home && away) form.slug = slugify(`${home}-vs-${away}`);
    },
);
function save() {
    let o = { preserveScroll: true, onSuccess: () => (open.value = false) };
    editing.value
        ? form.put(route("admin.matches.update", editing.value.id), o)
        : form.post(route("admin.matches.store"), o);
}
function search() {
    filtering.value = true;
    router.get(route("admin.matches.index"), filter.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => (filtering.value = false),
    });
}
function resetFilters() {
    filter.value = {
        search: "",
        month: "",
        status: "",
        match_type: "",
        season_id: "",
    };
    search();
}
function refreshSeasons() {
    refreshingSeasons.value = true;
    router.reload({
        only: ["seasons"],
        preserveScroll: true,
        preserveState: true,
        onFinish: () => (refreshingSeasons.value = false),
    });
}
const confirmationContent = computed(() => {
    if (confirmAction.value === "match") {
        const match = confirmTarget.value;
        return {
            title: "Hapus pertandingan?",
            itemName: match ? `${match.home} vs ${match.away}` : "",
            description:
                "Pertandingan akan dihapus dari dashboard dan tidak lagi tersedia pada halaman publik.",
            note: "Susunan pemain dan kejadian yang terhubung dengan pertandingan ini juga dapat ikut terhapus. Tindakan ini tidak dapat dibatalkan.",
            confirmLabel: "Hapus Pertandingan",
        };
    }

    const event = confirmTarget.value;
    return {
        title: "Hapus kejadian pertandingan?",
        itemName: event
            ? `${eventTypeLabels[event.type] || "Kejadian"} · menit ${event.minute ?? 0}`
            : "",
        description:
            "Kejadian ini akan dihapus dari timeline dan statistik pertandingan.",
        note: "Pastikan kejadian yang dipilih sudah benar. Tindakan ini tidak dapat dibatalkan.",
        confirmLabel: "Hapus Kejadian",
    };
});
function openConfirmation(action, target) {
    confirmAction.value = action;
    confirmTarget.value = target;
    confirmError.value = "";
    confirmOpen.value = true;
}
function closeConfirmation() {
    if (confirmProcessing.value) return;
    confirmOpen.value = false;
    confirmAction.value = null;
    confirmTarget.value = null;
    confirmError.value = "";
}
function remove() {
    openConfirmation("match", editing.value);
}
function confirmRemoval() {
    if (!confirmTarget.value || confirmProcessing.value) return;
    confirmProcessing.value = true;
    confirmError.value = "";

    if (confirmAction.value === "match") {
        router.delete(route("admin.matches.destroy", confirmTarget.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                confirmOpen.value = false;
                open.value = false;
            },
            onError: () => {
                confirmError.value =
                    "Pertandingan belum dapat dihapus. Periksa kembali data yang terhubung.";
            },
            onFinish: () => (confirmProcessing.value = false),
        });
        return;
    }

    router.delete(
        route("admin.matches.events.destroy", [
            editing.value.id,
            confirmTarget.value.id,
        ]),
        {
            data: { correction_reason: eventForm.correction_reason },
            preserveScroll: true,
            onSuccess: () => {
                confirmOpen.value = false;
                eventsOpen.value = false;
            },
            onError: () => {
                confirmError.value =
                    "Kejadian belum dapat dihapus. Periksa alasan koreksi dan coba kembali.";
            },
            onFinish: () => (confirmProcessing.value = false),
        },
    );
}
function editLineup(m) {
    editing.value = m;
    lineup.defaults({ lineups: (m.lineups || []).map((x) => ({ ...x })) });
    lineup.reset();
    lineupOpen.value = true;
}
function addPlayer(clubId) {
    lineup.lineups.push({
        club_id: clubId,
        player_id: null,
        guest_name: "",
        role: "starter",
        position: "",
        shirt_number: null,
        is_captain: false,
        display_order: lineup.lineups.length + 1,
    });
}
const lineupClubs = computed(() =>
    editing.value
        ? [
              {
                  id: editing.value.home_club_id,
                  name: editing.value.home,
                  side: "Klub tuan rumah",
              },
              {
                  id: editing.value.away_club_id,
                  name: editing.value.away,
                  side: "Klub lawan",
              },
          ]
        : [],
);
const clubLineups = (clubId, role = null) =>
    lineup.lineups.filter(
        (row) => row.club_id == clubId && (!role || row.role === role),
    );
const clubIsCosmic = (clubId) =>
    Boolean(p.clubs.find((club) => club.id == clubId)?.is_cosmic);
const selectedPlayerIds = (clubId, current) =>
    lineup.lineups
        .filter(
            (row) => row !== current && row.club_id == clubId && row.player_id,
        )
        .map((row) => Number(row.player_id));
const captainName = (clubId) => {
    const captain = clubLineups(clubId).find((row) => row.is_captain);
    if (!captain) return "Belum dipilih";
    return (
        p.players.find((player) => player.id == captain.player_id)
            ?.display_name ||
        captain.guest_name ||
        "Belum dipilih"
    );
};
function setCaptain(row) {
    if (!row.is_captain) return;
    lineup.lineups.forEach((item) => {
        if (item !== row && item.club_id == row.club_id)
            item.is_captain = false;
    });
}
function removeLineup(row) {
    const index = lineup.lineups.indexOf(row);
    if (index >= 0) lineup.lineups.splice(index, 1);
}
function saveLineup() {
    let displayOrder = 1;
    for (const club of lineupClubs.value) {
        for (const role of ["starter", "substitute"]) {
            clubLineups(club.id, role).forEach((row) => {
                row.display_order = displayOrder++;
            });
        }
    }
    lineup.put(route("admin.matches.lineups.sync", editing.value.id), {
        onSuccess: () => (lineupOpen.value = false),
    });
}
const clubName = (id) => p.clubs.find((x) => x.id === id)?.display_name || "—";
function openEvents(m) {
    editing.value = m;
    editingEvent.value = null;
    eventForm.defaults({ ...eventBlank(), club_id: m.home_club_id });
    eventForm.reset();
    eventForm.clearErrors();
    eventsOpen.value = true;
}
function openMvp(m) {
    router.get(route("admin.matches.mvp.show", m.id));
}
function selectEvent(e = null) {
    editingEvent.value = e;
    eventForm.defaults(
        e
            ? { ...eventBlank(), ...e }
            : { ...eventBlank(), club_id: editing.value.home_club_id },
    );
    eventForm.reset();
    eventForm.clearErrors();
}
function saveEvent() {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            editingEvent.value = null;
            eventsOpen.value = false;
        },
    };
    editingEvent.value
        ? eventForm.put(
              route("admin.matches.events.update", [
                  editing.value.id,
                  editingEvent.value.id,
              ]),
              options,
          )
        : eventForm.post(
              route("admin.matches.events.store", editing.value.id),
              options,
          );
}
function deleteEvent(e) {
    openConfirmation("event", e);
}
</script>
<template>
    <Head title="Kelola Pertandingan" /><AuthenticatedLayout
        ><template #page-title>Kelola Pertandingan</template>
        <div class="page">
            <div v-if="page.props.flash?.success" class="notice">
                {{ page.props.flash.success }}
            </div>
            <div v-if="page.props.errors?.match" class="error">
                {{ page.props.errors.match }}
            </div>
            <header class="hero">
                <div>
                    <span>Kelola Pertandingan</span>
                    <h1>Fixtures & <b>Results.</b></h1>
                    <p>
                        Kelola pertandingan dari penjadwalan hingga hasil dan
                        publikasi.
                    </p>
                </div>
                <button v-if="can.create" class="create-match" @click="edit()">
                    <b>+</b
                    ><span
                        >Tambah pertandingan<small
                            >Buat jadwal baru</small
                        ></span
                    >
                </button>
            </header>
            <section class="summary">
                <article>
                    <b>{{ matches.total }}</b
                    ><span>Total pertandingan</span>
                </article>
                <article>
                    <b>{{
                        matches.data.filter((x) => x.status === "scheduled")
                            .length
                    }}</b
                    ><span>Terjadwal</span>
                </article>
                <article>
                    <b>{{
                        matches.data.filter((x) => x.status === "completed")
                            .length
                    }}</b
                    ><span>Selesai</span>
                </article>
                <article>
                    <b>{{ featuredMatch?.away || "Belum dipilih" }}</b
                    ><span
                        >Next Match utama<small v-if="featuredMatch">{{
                            formatKickoff(featuredMatch.kickoff_at).date
                        }}</small></span
                    >
                </article>
            </section>
            <form class="filters legacy-browser" @submit.prevent="search">
                <label
                    >Pencarian<input
                        v-model="filter.search"
                        placeholder="Nama klub, lapangan, atau alamat detail"
                /></label>
                <label
                    >Bulan pertandingan<input
                        v-model="filter.month"
                        type="month"
                /></label>
                <label
                    >Status pertandingan<select v-model="filter.status">
                        <option value="">Semua status</option>
                        <option
                            v-for="status in statusOptions"
                            :key="status[0]"
                            :value="status[0]"
                        >
                            {{ status[1] }}
                        </option>
                    </select></label
                >
                <label
                    >Kompetisi &amp; musim<select v-model="filter.season_id">
                        <option value="">Semua kompetisi &amp; musim</option>
                        <option v-for="s in seasons" :key="s.id" :value="s.id">
                            {{ s.name }}
                        </option>
                    </select></label
                >
                <button>Cari</button>
            </form>
            <section class="table legacy-browser">
                <div class="row head">
                    <span>Kick Off</span><span>Tuan rumah</span><span>Skor</span
                    ><span>Lawan</span><span>Status</span><span>Aksi</span>
                </div>
                <div v-for="m in matches.data" :key="m.id" class="row">
                    <span>{{
                        m.kickoff_at?.replace("T", " ") || "Belum diatur"
                    }}</span
                    ><b>{{ m.home }}</b
                    ><strong
                        >{{ m.home_score ?? "–" }} :
                        {{ m.away_score ?? "–" }}</strong
                    ><b>{{ m.away }}</b
                    ><span class="status"
                        >{{ statusLabel(m.status)
                        }}<i v-if="m.is_featured">Next Match utama</i></span
                    ><span class="actions"
                        ><button v-if="can.eventsView" @click="openEvents(m)">
                            Kejadian</button
                        ><button v-if="can.lineups" @click="editLineup(m)">
                            Susunan pemain</button
                        ><button @click="edit(m)">Ubah</button></span
                    >
                </div>
            </section>
            <section class="match-browser">
                <div class="browser-head">
                    <div>
                        <span>Daftar Pertandingan</span
                        ><strong
                            >{{ matches.total }} pertandingan ditemukan</strong
                        >
                    </div>
                    <button
                        v-if="hasFilters"
                        type="button"
                        @click="resetFilters"
                    >
                        Reset filter
                    </button>
                </div>
                <form class="filters" @submit.prevent="search">
                    <label class="search-filter"
                        >Pencarian<input
                            v-model="filter.search"
                            placeholder="Nama klub, lapangan, atau alamat detail"
                    /></label>
                    <label
                        >Bulan pertandingan<input
                            v-model="filter.month"
                            type="month"
                    /></label>
                    <label
                        >Status pertandingan<select v-model="filter.status">
                            <option value="">Semua status</option>
                            <option
                                v-for="status in statusOptions"
                                :key="status[0]"
                                :value="status[0]"
                            >
                                {{ status[1] }}
                            </option>
                        </select></label
                    >
                    <label
                        >Jenis pertandingan<select v-model="filter.match_type">
                            <option value="">Semua jenis</option>
                            <option
                                v-for="type in matchTypeOptions"
                                :key="type[0]"
                                :value="type[0]"
                            >
                                {{ type[1] }}
                            </option>
                        </select></label
                    >
                    <label
                        >Kompetisi &amp; musim<select
                            v-model="filter.season_id"
                        >
                            <option value="">
                                Semua kompetisi &amp; musim
                            </option>
                            <option
                                v-for="s in seasons"
                                :key="s.id"
                                :value="s.id"
                            >
                                {{ s.name }}
                            </option>
                        </select></label
                    >
                    <button :disabled="filtering">
                        {{ filtering ? "Mencari…" : "Cari" }}
                    </button>
                </form>
                <div v-if="matches.data.length" class="matches-grid">
                    <div class="matches-grid-head" aria-hidden="true">
                        <span>Kick Off</span>
                        <span>Pertandingan</span>
                        <span>Skor</span>
                        <span>Status</span>
                        <span>Publikasi</span>
                        <span>Aksi</span>
                    </div>
                    <article
                        v-for="m in matches.data"
                        :key="m.id"
                        class="match-grid-row"
                        :class="{ featured: m.is_featured }"
                    >
                        <div
                            class="match-cell kickoff-cell"
                            data-label="Kick Off"
                        >
                            <div class="table-kickoff">
                                <strong>{{
                                    formatKickoff(m.kickoff_at).date
                                }}</strong
                                ><small>{{
                                    formatKickoff(m.kickoff_at).time
                                }}</small>
                            </div>
                        </div>
                        <div
                            class="match-cell fixture-cell"
                            data-label="Pertandingan"
                        >
                            <small v-if="m.is_featured" class="featured-label"
                                >Next Match Utama</small
                            >
                            <small class="match-type-badge">{{
                                matchTypeLabel(m.match_type)
                            }}</small>
                            <div class="table-teams">
                                <strong>{{ m.home }}</strong
                                ><span>vs</span><strong>{{ m.away }}</strong>
                            </div>
                        </div>
                        <div class="match-cell score-cell" data-label="Skor">
                            <strong class="table-score">{{
                                scoreDisplay(m)
                            }}</strong>
                        </div>
                        <div class="match-cell status-cell" data-label="Status">
                            <div class="table-status">
                                <span
                                    class="status-badge"
                                    :class="`status-${m.status}`"
                                    >{{ statusLabel(m.status) }}</span
                                >
                            </div>
                        </div>
                        <div
                            class="match-cell publication-cell"
                            data-label="Publikasi"
                        >
                            <span
                                class="table-publication"
                                :class="{ public: m.is_public }"
                                ><i></i
                                >{{ m.is_public ? "Publik" : "Draft" }}</span
                            >
                        </div>
                        <div class="match-cell actions-cell" data-label="Aksi">
                            <div class="table-actions">
                                <button
                                    v-if="can.eventsView"
                                    type="button"
                                    title="Kejadian pertandingan"
                                    aria-label="Kejadian pertandingan"
                                    @click="openEvents(m)"
                                >
                                    Kejadian
                                </button>
                                <button
                                    v-if="can.lineups"
                                    type="button"
                                    title="Susunan pemain"
                                    aria-label="Susunan pemain"
                                    @click="editLineup(m)"
                                >
                                    Susunan
                                </button>
                                <button
                                    v-if="can.mvp"
                                    type="button"
                                    :disabled="![`completed`, `walkover`].includes(m.status)"
                                    :title="![`completed`, `walkover`].includes(m.status) ? `MVP tersedia setelah pertandingan selesai` : `Kelola Player of the Match`"
                                    @click="openMvp(m)"
                                >
                                    {{ m.mvp ? "Lihat MVP" : "Tentukan MVP" }}
                                </button>
                                <button
                                    type="button"
                                    title="Ubah pertandingan"
                                    aria-label="Ubah pertandingan"
                                    @click="edit(m)"
                                >
                                    Edit
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
                <div v-else class="empty-state">
                    <strong>Tidak ada pertandingan yang sesuai</strong>
                    <p>Coba ubah pencarian atau reset filter yang digunakan.</p>
                    <button
                        v-if="hasFilters"
                        type="button"
                        @click="resetFilters"
                    >
                        Reset filter
                    </button>
                </div>
                <footer v-if="matches.last_page > 1" class="pagination">
                    <span
                        >Menampilkan {{ matches.from }}–{{ matches.to }} dari
                        {{ matches.total }}</span
                    >
                    <nav>
                        <Link
                            v-if="matches.prev_page_url"
                            :href="matches.prev_page_url"
                            preserve-scroll
                            >← Sebelumnya</Link
                        ><strong
                            >Halaman {{ matches.current_page }} /
                            {{ matches.last_page }}</strong
                        ><Link
                            v-if="matches.next_page_url"
                            :href="matches.next_page_url"
                            preserve-scroll
                            >Berikutnya →</Link
                        >
                    </nav>
                </footer>
            </section>
            <div v-if="open" class="modal" @click.self="open = false">
                <form class="dialog" @submit.prevent="save">
                    <header>
                        <h2>{{ editing ? "Edit" : "Tambah" }} Pertandingan</h2>
                        <button type="button" @click="open = false">×</button>
                    </header>
                    <nav>
                        <button
                            type="button"
                            :class="{ active: tab === 'fixture' }"
                            @click="tab = 'fixture'"
                        >
                            Informasi Pertandingan</button
                        ><button
                            type="button"
                            :class="{ active: tab === 'result' }"
                            @click="tab = 'result'"
                        >
                            Hasil</button
                        ><button
                            type="button"
                            :class="{ active: tab === 'publish' }"
                            @click="tab = 'publish'"
                        >
                            Publikasi
                        </button>
                    </nav>
                    <div v-show="tab === 'fixture'" class="fields">
                        <div class="field-intro full">
                            Data ini digunakan pada Next Match, Match Center,
                            halaman Jadwal, Matchday Center, dan detail
                            pertandingan.
                        </div>
                        <label
                            >Alamat halaman detail <b>*</b
                            ><input
                                v-model="form.slug"
                                placeholder="cosmic-vs-rivals"
                                @input="slugTouched = true"
                            /><small
                                >Bagian akhir URL halaman detail. Dibuat
                                otomatis saat kedua klub dipilih dan tetap dapat
                                diubah.</small
                            ><em v-if="form.errors.slug">{{
                                form.errors.slug
                            }}</em></label
                        ><label
                            >Jenis pertandingan <b>*</b
                            ><select v-model="form.match_type">
                                <option value="competitive">Kompetisi</option>
                                <option value="friendly">Persahabatan</option>
                                <option value="sparing">Sparing</option>
                                <option value="tournament">Turnamen</option>
                                <option value="exhibition">
                                    Ekshibisi
                                </option></select
                            ><small
                                >Menentukan kategori pertandingan dan
                                hubungannya dengan kompetisi.</small
                            ><em v-if="form.errors.match_type">{{
                                form.errors.match_type
                            }}</em></label
                        ><div
                            v-if="isSparing"
                            class="field-intro full sparing-info"
                        >
                            Sparing merupakan latihan tanding. Hasil dapat
                            ditampilkan di website, tetapi tidak masuk klasemen
                            atau statistik resmi kompetisi.
                        </div
                        ><div class="season-field">
                            <label>Kompetisi &amp; musim<select
                                v-model="form.season_id"
                            >
                                <option :value="null">
                                    Tidak terkait kompetisi
                                </option>
                                <option
                                    v-for="s in seasons"
                                    :key="s.id"
                                    :value="s.id"
                                >
                                    {{ s.name }}
                                </option></select
                            ><small
                                >Wajib untuk jenis Kompetisi dan digunakan dalam
                                klasemen serta Matchday Center.</small
                            ><em v-if="form.errors.season_id">{{
                                form.errors.season_id
                            }}</em></label>
                            <div class="season-field-actions">
                                <a :href="route('admin.competitions.create')" target="_blank" rel="noopener">+ Kompetisi baru</a>
                                <a :href="route('admin.competitions.seasons.create')" target="_blank" rel="noopener">+ Musim baru</a>
                                <button type="button" :disabled="refreshingSeasons" @click="refreshSeasons">{{ refreshingSeasons ? 'Memuat...' : 'Muat ulang pilihan' }}</button>
                            </div>
                            <small class="season-field-note">Halaman tambah dibuka di tab baru agar data pertandingan yang sedang diisi tidak hilang.</small>
                        </div
                        ><label v-if="!isSparing"
                            >Pekan pertandingan<input
                                v-model="form.matchday"
                                type="number"
                                min="1"
                                placeholder="Contoh: 4"
                            /><small
                                >Nomor pekan yang tampil pada halaman Liga dan
                                detail pertandingan.</small
                            ><em v-if="form.errors.matchday">{{
                                form.errors.matchday
                            }}</em></label
                        ><label
                            >Klub tuan rumah <b>*</b
                            ><select v-model="form.home_club_id">
                                <option value="" disabled>
                                    Pilih klub tuan rumah
                                </option>
                                <option
                                    v-for="c in clubs"
                                    :key="c.id"
                                    :value="c.id"
                                    :disabled="c.id == form.away_club_id"
                                >
                                    {{ c.display_name }}
                                </option></select
                            ><small
                                >Ditampilkan di sisi kiri kartu pertandingan dan
                                papan skor.</small
                            ><em v-if="form.errors.home_club_id">{{
                                form.errors.home_club_id
                            }}</em></label
                        ><label
                            >Klub lawan <b>*</b
                            ><select v-model="form.away_club_id">
                                <option value="" disabled>
                                    Pilih klub lawan
                                </option>
                                <option
                                    v-for="c in clubs"
                                    :key="c.id"
                                    :value="c.id"
                                    :disabled="c.id == form.home_club_id"
                                >
                                    {{ c.display_name }}
                                </option></select
                            ><small
                                >Ditampilkan di sisi kanan kartu pertandingan
                                dan papan skor.</small
                            ><em v-if="form.errors.away_club_id">{{
                                form.errors.away_club_id
                            }}</em></label
                        ><label
                            >Kick Off<input
                                v-model="form.kickoff_at"
                                type="datetime-local"
                            /><small
                                >Tanggal dan waktu yang tampil pada Beranda,
                                Jadwal, Liga, dan detail pertandingan.</small
                            ><em v-if="form.errors.kickoff_at">{{
                                form.errors.kickoff_at
                            }}</em></label
                        ><label
                            >Perkiraan durasi <b>*</b
                            ><input
                                v-model.number="form.duration_minutes"
                                type="number"
                                min="30"
                                max="300"
                                step="5"
                            /><small
                                >Dalam menit (minimal 30). Sistem memakai nilai ini untuk pengingat dan waktu selesai otomatis.</small
                            ><em v-if="form.errors.duration_minutes">{{
                                form.errors.duration_minutes
                            }}</em></label
                        ><label
                            >Nama lapangan<input
                                v-model="form.venue_name"
                                placeholder="Contoh: Lap. Kuningan Mini Soccer"
                            /><small
                                >Nama lokasi yang ditampilkan pada informasi
                                pertandingan.</small
                            ><em v-if="form.errors.venue_name">{{
                                form.errors.venue_name
                            }}</em></label
                        ><label class="full"
                            >Alamat lapangan<input
                                v-model="form.venue_address"
                                placeholder="Contoh: Pekanbaru, Riau"
                            /><small
                                >Keterangan lokasi yang tampil di bawah nama
                                lapangan.</small
                            ><em v-if="form.errors.venue_address">{{
                                form.errors.venue_address
                            }}</em></label
                        >
                    </div>
                    <div v-show="tab === 'result'" class="fields">
                        <label
                            >Status pertandingan<select v-model="form.status">
                                <option
                                    v-for="status in statusOptions"
                                    :key="status[0]"
                                    :value="status[0]"
                                >
                                    {{ status[1] }}
                                </option></select
                            ><small
                                >Menentukan apakah pertandingan tampil sebagai
                                jadwal, pertandingan aktif, atau hasil.</small
                            ><em v-if="form.errors.status">{{
                                form.errors.status
                            }}</em></label
                        ><label
                            >Jumlah penonton<input
                                v-model="form.attendance"
                                type="number"
                                min="0"
                                placeholder="Contoh: 120"
                            /><small
                                >Informasi jumlah penonton pada detail
                                pertandingan.</small
                            ><em v-if="form.errors.attendance">{{
                                form.errors.attendance
                            }}</em></label
                        ><label class="full"
                            >Sumber skor<select v-model="form.score_mode">
                                <option value="automatic">Otomatis dari kejadian gol</option>
                                <option value="manual">Input manual</option>
                            </select><small>{{
                                isAutomaticScore
                                    ? "Skor berubah otomatis saat kejadian gol ditambah, diubah, atau dihapus."
                                    : "Gunakan hanya untuk WO, migrasi data, atau koreksi khusus."
                            }}</small><em v-if="form.errors.score_mode">{{ form.errors.score_mode }}</em></label
                        ><label
                            >Skor {{ clubName(form.home_club_id)
                            }}<input
                                v-model="form.home_score"
                                type="number"
                                min="0"
                                :disabled="isAutomaticScore"
                            /><small
                                >{{ isAutomaticScore ? "Dihitung dari kejadian gol klub ini." : "Isi skor resmi klub ini." }}</small
                            ><em v-if="form.errors.home_score">{{
                                form.errors.home_score
                            }}</em></label
                        ><label
                            >Skor {{ clubName(form.away_club_id)
                            }}<input
                                v-model="form.away_score"
                                type="number"
                                min="0"
                                :disabled="isAutomaticScore"
                            /><small
                                >{{ isAutomaticScore ? "Dihitung dari kejadian gol klub ini." : "Isi skor resmi klub ini." }}</small
                            ><em v-if="form.errors.away_score">{{
                                form.errors.away_score
                            }}</em></label
                        ><label v-if="!isAutomaticScore" class="full"
                            >Alasan penggunaan skor manual<textarea
                                v-model="form.manual_score_reason"
                                rows="2"
                                placeholder="Contoh: Hasil resmi menang WO 3–0"
                            /><small>Wajib diisi agar perubahan skor dapat ditelusuri.</small
                            ><em v-if="form.errors.manual_score_reason">{{ form.errors.manual_score_reason }}</em></label
                        ><label class="check status-check"
                            ><input
                                v-model="form.count_for_standings"
                                type="checkbox"
                                :disabled="isSparing"
                            />
                            <span
                                ><strong>Masukkan hasil ke klasemen</strong
                                ><small>{{
                                    isSparing
                                        ? "Sparing tidak memengaruhi klasemen"
                                        : form.count_for_standings
                                        ? "Hasil ikut dihitung pada klasemen"
                                        : "Hasil tidak memengaruhi klasemen"
                                }}</small></span
                            ></label
                        ><label class="full"
                            >Ringkasan pertandingan<textarea
                                v-model="form.short_report"
                                rows="4"
                            /><small
                                >Catatan singkat mengenai jalannya
                                pertandingan.</small
                            ><em v-if="form.errors.short_report">{{
                                form.errors.short_report
                            }}</em>
                        </label>
                    </div>
                    <div v-show="tab === 'publish'" class="fields">
                        <label
                            >Gambar cover pertandingan<select
                                v-model="form.cover_media_id"
                            >
                                <option :value="null">
                                    Gunakan gambar bawaan
                                </option>
                                <option
                                    v-for="m in media"
                                    :key="m.id"
                                    :value="m.id"
                                >
                                    {{ m.title }}
                                </option></select
                            ><small
                                >Menjadi gambar latar pada Next Match, hasil,
                                dan halaman detail.</small
                            ><em v-if="form.errors.cover_media_id">{{
                                form.errors.cover_media_id
                            }}</em></label
                        ><label class="check status-check"
                            ><input
                                v-model="form.is_featured"
                                type="checkbox"
                                :disabled="!canBeFeatured"
                            />
                            <span
                                ><strong>Jadikan Next Match utama</strong
                                ><small>{{
                                    !canBeFeatured
                                        ? "Hanya tersedia untuk pertandingan berstatus Terjadwal atau Ditunda"
                                        : form.is_featured
                                        ? "Pertandingan diprioritaskan pada section Next Match"
                                        : "Sistem memilih jadwal terdekat"
                                }}</small></span
                            ></label
                        ><label class="check status-check"
                            ><input v-model="form.is_public" type="checkbox" />
                            <span
                                ><strong
                                    >Tampilkan pertandingan di website</strong
                                ><small>{{
                                    form.is_public
                                        ? "Pertandingan dapat tampil pada halaman publik"
                                        : "Pertandingan hanya tersimpan di dashboard"
                                }}</small></span
                            ></label
                        >
                    </div>
                    <div class="errors">
                        <p v-for="(e, k) in form.errors" :key="k">{{ e }}</p>
                    </div>
                    <footer>
                        <button
                            v-if="editing && can.delete"
                            type="button"
                            class="danger"
                            @click="remove"
                        >
                            Hapus</button
                        ><button
                            type="button"
                            class="cancel"
                            @click="open = false"
                        >
                            Batal</button
                        ><button>Simpan</button>
                    </footer>
                </form>
            </div>
            <div
                v-if="lineupOpen"
                class="modal"
                @click.self="lineupOpen = false"
            >
                <form class="dialog lineup" @submit.prevent="saveLineup">
                    <header>
                        <div class="lineup-title">
                            <small>Roster Pertandingan</small>
                            <h2>Susunan Pemain</h2>
                            <p>{{ editing.home }} vs {{ editing.away }}</p>
                        </div>
                        <h2>
                            Lineup · {{ editing.home }} vs {{ editing.away }}
                        </h2>
                        <button type="button" @click="lineupOpen = false">
                            ×
                        </button>
                    </header>
                    <div class="lineup-help">
                        Atur pemain inti, cadangan, posisi, nomor punggung, dan
                        satu kapten untuk setiap klub. Susunan ini tampil pada
                        halaman detail pertandingan.
                    </div>
                    <div class="lineup-actions legacy-lineup">
                        <button
                            type="button"
                            @click="addPlayer(editing.home_club_id)"
                        >
                            + {{ editing.home }}</button
                        ><button
                            type="button"
                            @click="addPlayer(editing.away_club_id)"
                        >
                            + {{ editing.away }}
                        </button>
                    </div>
                    <div class="lineup-list legacy-lineup">
                        <div v-for="(l, i) in lineup.lineups" :key="i">
                            <b>{{ clubName(l.club_id) }}</b
                            ><select v-model="l.player_id">
                                <option :value="null">Guest</option>
                                <option
                                    v-for="pl in players"
                                    :key="pl.id"
                                    :value="pl.id"
                                >
                                    {{ pl.display_name }}
                                </option></select
                            ><input
                                v-if="!l.player_id"
                                v-model="l.guest_name"
                                placeholder="Nama guest"
                            /><select v-model="l.role">
                                <option>starter</option>
                                <option>substitute</option></select
                            ><input
                                v-model="l.shirt_number"
                                type="number"
                                placeholder="#"
                            /><label
                                ><input
                                    v-model="l.is_captain"
                                    type="checkbox"
                                />
                                C</label
                            ><button
                                type="button"
                                @click="lineup.lineups.splice(i, 1)"
                            >
                                ×
                            </button>
                        </div>
                    </div>
                    <div class="lineup-teams">
                        <section
                            v-for="club in lineupClubs"
                            :key="club.id"
                            class="lineup-team"
                        >
                            <header class="team-head">
                                <div>
                                    <small>{{ club.side }}</small>
                                    <h3>{{ club.name }}</h3>
                                </div>
                                <button
                                    type="button"
                                    @click="addPlayer(club.id)"
                                >
                                    <b>+</b
                                    ><span
                                        >Tambah pemain<small>{{
                                            club.name
                                        }}</small></span
                                    >
                                </button>
                            </header>
                            <div class="roster-summary">
                                <span
                                    ><b>{{
                                        clubLineups(club.id, "starter").length
                                    }}</b>
                                    pemain inti</span
                                >
                                <span
                                    ><b>{{
                                        clubLineups(club.id, "substitute")
                                            .length
                                    }}</b>
                                    cadangan</span
                                >
                                <span
                                    ><b>Kapten</b>
                                    {{ captainName(club.id) }}</span
                                >
                            </div>
                            <template
                                v-for="role in ['starter', 'substitute']"
                                :key="role"
                            >
                                <div class="role-heading">
                                    <strong>{{
                                        role === "starter"
                                            ? "Pemain Inti"
                                            : "Pemain Cadangan"
                                    }}</strong
                                    ><span
                                        >{{
                                            clubLineups(club.id, role).length
                                        }}
                                        pemain</span
                                    >
                                </div>
                                <div
                                    v-if="clubLineups(club.id, role).length"
                                    class="roster-list"
                                >
                                    <article
                                        v-for="(row, rowIndex) in clubLineups(
                                            club.id,
                                            role,
                                        )"
                                        :key="
                                            row.id ||
                                            `${club.id}-${role}-${rowIndex}`
                                        "
                                        class="roster-card"
                                    >
                                        <label
                                            >Pemain<select
                                                v-model="row.player_id"
                                            >
                                                <option :value="null">
                                                    Pemain tidak terdaftar
                                                </option>
                                                <option
                                                    v-if="clubIsCosmic(club.id)"
                                                    v-for="player in players"
                                                    :key="player.id"
                                                    :value="player.id"
                                                    :disabled="
                                                        selectedPlayerIds(
                                                            club.id,
                                                            row,
                                                        ).includes(player.id)
                                                    "
                                                >
                                                    {{ player.display_name }}
                                                </option>
                                            </select></label
                                        >
                                        <label
                                            >Nama pemain tamu<input
                                                v-model="row.guest_name"
                                                :disabled="
                                                    Boolean(row.player_id)
                                                "
                                                placeholder="Nama lengkap pemain"
                                        /></label>
                                        <label
                                            >Status pemain<select
                                                v-model="row.role"
                                            >
                                                <option value="starter">
                                                    Pemain inti
                                                </option>
                                                <option value="substitute">
                                                    Pemain cadangan
                                                </option>
                                            </select></label
                                        >
                                        <label
                                            >Posisi<select
                                                v-model="row.position"
                                            >
                                                <option value="">
                                                    Belum ditentukan
                                                </option>
                                                <option value="Goalkeeper">
                                                    Penjaga gawang
                                                </option>
                                                <option value="Defender">
                                                    Bek
                                                </option>
                                                <option value="Midfielder">
                                                    Gelandang
                                                </option>
                                                <option value="Forward">
                                                    Penyerang
                                                </option>
                                                <option value="Utility">
                                                    Fleksibel
                                                </option>
                                            </select></label
                                        >
                                        <label
                                            >Nomor<input
                                                v-model="row.shirt_number"
                                                type="number"
                                                min="1"
                                                max="99"
                                                placeholder="10"
                                        /></label>
                                        <div class="captain-field">
                                            <span class="roster-label"
                                                >Kapten</span
                                            ><label class="check captain-check"
                                                ><input
                                                    v-model="row.is_captain"
                                                    type="checkbox"
                                                    @change="setCaptain(row)"
                                                /><span
                                                    ><strong>Kapten tim</strong
                                                    ><small>{{
                                                        row.is_captain
                                                            ? "Dipilih"
                                                            : "Tidak"
                                                    }}</small></span
                                                ></label
                                            >
                                        </div>
                                        <button
                                            class="remove-player"
                                            type="button"
                                            title="Hapus dari susunan"
                                            @click="removeLineup(row)"
                                        >
                                            <svg
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 10v6M14 10v6"
                                                /></svg
                                            ><span class="sr-only"
                                                >Hapus dari susunan</span
                                            >
                                        </button>
                                    </article>
                                </div>
                                <p v-else class="empty-roster">
                                    Belum ada
                                    {{
                                        role === "starter"
                                            ? "pemain inti"
                                            : "pemain cadangan"
                                    }}.
                                </p>
                            </template>
                        </section>
                    </div>
                    <div class="errors">
                        <p v-for="(e, k) in lineup.errors" :key="k">{{ e }}</p>
                    </div>
                    <footer>
                        <button
                            type="button"
                            class="cancel"
                            @click="lineupOpen = false"
                        >
                            Batal</button
                        ><button>Simpan Susunan Pemain</button>
                    </footer>
                </form>
            </div>
            <div
                v-if="eventsOpen"
                class="modal"
                @click.self="eventsOpen = false"
            >
                <div class="dialog event-dialog">
                    <header>
                        <div>
                            <h2>Kejadian Pertandingan</h2>
                            <small
                                >{{ editing.home }}
                                {{ editing.home_score ?? "–" }} :
                                {{ editing.away_score ?? "–" }}
                                {{ editing.away }}</small
                            >
                        </div>
                        <button type="button" @click="eventsOpen = false">
                            ×
                        </button>
                    </header>
                    <div
                        class="reconcile"
                        :class="{ ok: editing.reconciliation?.synced }"
                    >
                        <b>{{ editing.score_mode === "automatic" ? "Skor otomatis" : "Skor manual" }}</b
                        ><span
                            >{{ editing.reconciliation?.homeEvents }}/{{
                                editing.home_score ?? "–"
                            }}
                            · {{ editing.reconciliation?.awayEvents }}/{{
                                editing.away_score ?? "–"
                            }}</span
                        ><strong>{{
                            editing.reconciliation?.synced
                                ? (editing.score_mode === "automatic" ? "Sinkron" : "Tidak diubah kejadian")
                                : "Perlu diperiksa"
                        }}</strong>
                    </div>
                    <div class="event-layout">
                        <section class="event-timeline">
                            <button
                                v-if="can.eventsCreate"
                                class="new-event"
                                type="button"
                                @click="selectEvent()"
                            >
                                + Tambah Kejadian</button
                            ><button
                                v-for="e in editing.events"
                                :key="e.id"
                                type="button"
                                class="event-row"
                                :class="{ active: editingEvent?.id === e.id }"
                                @click="selectEvent(e)"
                            >
                                <b>{{ e.minute_label }}</b
                                ><span
                                    ><strong>{{
                                        eventTypeLabels[e.type] || e.type
                                    }}</strong
                                    ><small
                                        >{{ e.club_name }} · {{ e.primary_name
                                        }}<template v-if="e.secondary_name">
                                            → {{ e.secondary_name }}</template
                                        ></small
                                    ></span
                                ><i :class="{ public: e.is_public }">{{
                                    e.is_public ? "Publik" : "Draft"
                                }}</i>
                            </button>
                            <p v-if="!editing.events?.length" class="empty">
                                Belum ada event pertandingan.
                            </p>
                        </section>
                        <form class="event-form" @submit.prevent="saveEvent">
                            <h3>
                                {{
                                    editingEvent
                                        ? "Edit Kejadian"
                                        : "Tambah Kejadian"
                                }}
                            </h3>
                            <div class="fields">
                                <label
                                    >{{ eventForm.type === "own_goal" ? "Klub penerima gol" : "Klub terkait" }}<select v-model="eventForm.club_id">
                                        <option :value="editing.home_club_id">
                                            {{ editing.home }}
                                        </option>
                                        <option :value="editing.away_club_id">
                                            {{ editing.away }}
                                        </option>
                                    </select><small>{{ eventForm.type === "own_goal" ? "Pilih klub yang memperoleh skor, bukan klub pelaku gol bunuh diri." : "Pilih klub yang terkait dengan kejadian ini." }}</small></label
                                ><label
                                    >Jenis Kejadian<select
                                        v-model="eventForm.type"
                                    >
                                        <option value="goal">Gol</option>
                                        <option value="penalty_goal">
                                            Gol Penalti
                                        </option>
                                        <option value="own_goal">
                                            Gol Bunuh Diri
                                        </option>
                                        <option value="missed_penalty">
                                            Penalti Gagal
                                        </option>
                                        <option value="yellow_card">
                                            Kartu Kuning
                                        </option>
                                        <option value="second_yellow">
                                            Kuning Kedua
                                        </option>
                                        <option value="red_card">
                                            Kartu Merah
                                        </option>
                                        <option value="substitution">
                                            Pergantian
                                        </option>
                                        <option value="note">Catatan</option>
                                    </select><small>Gol pada babak Adu Penalti dihitung terpisah dari skor pertandingan.</small></label
                                ><label
                                    >Babak<select v-model="eventForm.period">
                                        <option value="first_half">
                                            Babak Pertama
                                        </option>
                                        <option value="second_half">
                                            Babak Kedua
                                        </option>
                                        <option value="extra_time_1">
                                            Extra Time 1
                                        </option>
                                        <option value="extra_time_2">
                                            Extra Time 2
                                        </option>
                                        <option value="shootout">
                                            Adu Penalti
                                        </option>
                                    </select></label
                                >
                                <div class="time-fields">
                                    <label
                                        >Menit<input
                                            v-model="eventForm.minute"
                                            type="number"
                                            min="0"
                                            max="150"
                                            placeholder="Contoh: 45"
                                    /></label>
                                    <label
                                        >Tambahan Waktu<input
                                            v-model="eventForm.added_minute"
                                            type="number"
                                            min="0"
                                            max="30"
                                            placeholder="Contoh: 2"
                                    /></label>
                                    <small
                                        >Contoh: menit 45 dan tambahan 2 akan
                                        tampil sebagai 45+2.</small
                                    >
                                </div>
                                <label v-if="needsPrimaryPlayer"
                                    >{{ primaryPlayerLabel
                                    }}<select
                                        v-model="eventForm.primary_player_id"
                                    >
                                        <option :value="null">
                                            Gunakan nama manual
                                        </option>
                                        <option
                                            v-for="pl in players"
                                            :key="pl.id"
                                            :value="pl.id"
                                        >
                                            {{ pl.display_name }}
                                        </option>
                                    </select></label
                                ><label
                                    v-if="
                                        needsPrimaryPlayer &&
                                        !eventForm.primary_player_id
                                    "
                                    >Nama Pemain Manual<input
                                        v-model="eventForm.primary_player_name"
                                        placeholder="Masukkan nama pemain"
                                    /><small
                                        >Gunakan jika pemain belum terdaftar
                                        dalam sistem.</small
                                    ></label
                                ><template v-if="isSubstitutionEvent"
                                    ><label
                                        >Pemain Masuk<select
                                            v-model="
                                                eventForm.secondary_player_id
                                            "
                                        >
                                            <option :value="null">
                                                Gunakan nama manual
                                            </option>
                                            <option
                                                v-for="pl in players"
                                                :key="pl.id"
                                                :value="pl.id"
                                            >
                                                {{ pl.display_name }}
                                            </option>
                                        </select></label
                                    ><label
                                        v-if="!eventForm.secondary_player_id"
                                        >Nama Pemain Masuk<input
                                            v-model="
                                                eventForm.secondary_player_name
                                            "
                                            placeholder="Masukkan nama pemain" /></label></template
                                ><template v-else-if="supportsAssist"
                                    ><label
                                        >Pemberi Assist<select
                                            v-model="
                                                eventForm.secondary_player_id
                                            "
                                        >
                                            <option :value="null">
                                                Tanpa assist atau gunakan nama
                                                manual
                                            </option>
                                            <option
                                                v-for="pl in players"
                                                :key="pl.id"
                                                :value="pl.id"
                                            >
                                                {{ pl.display_name }}
                                            </option>
                                        </select></label
                                    ><label
                                        v-if="!eventForm.secondary_player_id"
                                        >Nama Pemberi Assist<input
                                            v-model="
                                                eventForm.secondary_player_name
                                            "
                                            placeholder="Kosongkan jika tanpa assist" /></label
                                ></template>
                                <label class="full"
                                    >Catatan<textarea
                                        v-model="eventForm.note"
                                        rows="2"
                                        placeholder="Tambahkan informasi bila diperlukan"
                                    /></label
                                ><label
                                    v-if="
                                        ['completed', 'walkover'].includes(
                                            editing.status,
                                        )
                                    "
                                    class="full"
                                    >Alasan Koreksi<input
                                        v-model="eventForm.correction_reason"
                                        placeholder="Jelaskan alasan perubahan data"
                                    /><small
                                        >Wajib untuk perubahan pada pertandingan
                                        yang sudah selesai.</small
                                    ></label
                                ><label class="check event-public-check"
                                    ><input
                                        v-model="eventForm.is_public"
                                        type="checkbox"
                                    />
                                    <span
                                        ><strong>Tampilkan ke publik</strong
                                        ><small
                                            >Muncul pada timeline halaman detail
                                            pertandingan.</small
                                        ></span
                                    ></label
                                >
                            </div>
                            <div class="errors">
                                <p v-for="(e, k) in eventForm.errors" :key="k">
                                    {{ e }}
                                </p>
                            </div>
                            <footer>
                                <button
                                    v-if="editingEvent && can.eventsDelete"
                                    type="button"
                                    class="danger"
                                    @click="deleteEvent(editingEvent)"
                                >
                                    Hapus</button
                                ><button
                                    type="button"
                                    class="cancel"
                                    @click="
                                        editingEvent
                                            ? selectEvent()
                                            : (eventsOpen = false)
                                    "
                                >
                                    {{
                                        editingEvent ? "Batal Edit" : "Tutup"
                                    }}</button
                                ><button
                                    v-if="
                                        can.eventsCreate ||
                                        (editingEvent && can.eventsUpdate)
                                    "
                                >
                                    Simpan Kejadian
                                </button>
                            </footer>
                        </form>
                    </div>
                </div>
            </div>
        </div></AuthenticatedLayout
    >
    <ConfirmActionModal
        :open="confirmOpen"
        :title="confirmationContent.title"
        :item-name="confirmationContent.itemName"
        :description="confirmationContent.description"
        :note="confirmationContent.note"
        :confirm-label="confirmationContent.confirmLabel"
        processing-label="Menghapus..."
        variant="danger"
        :processing="confirmProcessing"
        :error="confirmError"
        @confirm="confirmRemoval"
        @cancel="closeConfirmation"
    />
</template>
<style scoped>
.page {
    max-width: 1450px;
    margin: auto;
}
.notice,
.error {
    padding: 12px;
    margin-bottom: 12px;
    border: 1px solid #45b7ff55;
}
.error,
.errors {
    color: #ff9da4;
}
.hero {
    min-height: 190px;
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    padding: 34px;
    border: 1px solid #ffffff18;
    background: radial-gradient(circle at 80% 20%, #45b7ff22, transparent 28%),
        #07111e;
}
.hero span {
    color: var(--color-accent);
    font: 800 0.55rem var(--font-display);
}
.hero h1 {
    margin: 8px 0;
    font: 900 clamp(3rem, 5vw, 5rem) / 0.85 var(--font-display);
    text-transform: uppercase;
}
.hero h1 b {
    color: var(--color-accent);
}
.hero p {
    margin: 0;
    color: #83939f;
}
.hero button,
.filters button,
.dialog footer button {
    padding: 12px 15px;
    border: 0;
    color: #03101b;
    background: var(--color-accent);
    font: 800 0.55rem var(--font-display);
    text-transform: uppercase;
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
    font: 900 1.4rem var(--font-display);
    text-transform: uppercase;
}
.summary span {
    color: #71828e;
}
.filters {
    display: grid;
    grid-template-columns: minmax(260px, 1fr) 190px 190px 240px auto;
    align-items: end;
    gap: 8px;
    margin-bottom: 14px;
}
.filters label {
    display: grid;
    gap: 7px;
    color: #8193a0;
    font: 750 0.52rem var(--font-display);
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.filters input,
.filters select,
.fields input,
.fields select,
.fields textarea,
.lineup-list input,
.lineup-list select {
    padding: 11px;
    border: 1px solid #ffffff20;
    color: white;
    background: #07111e;
}
.filters input,
.filters select {
    box-sizing: border-box;
    width: 100%;
    min-height: 43px;
    margin: 0;
}
.filters button {
    min-height: 43px;
}
.table {
    border: 1px solid #ffffff18;
    background: #07111e;
}
.row {
    display: grid;
    grid-template-columns: 1fr 1fr 0.5fr 1fr 0.7fr 0.8fr;
    align-items: center;
    gap: 9px;
    padding: 13px 16px;
    border-bottom: 1px solid #ffffff10;
}
.row.head {
    color: #71828e;
    font: 700 0.5rem var(--font-display);
    text-transform: uppercase;
}
.row strong {
    text-align: center;
}
.status {
    display: grid;
    gap: 3px;
    text-transform: uppercase;
}
.status i {
    color: var(--color-accent);
    font-size: 0.5rem;
}
.actions {
    display: flex;
    gap: 5px;
}
.actions button,
.lineup-actions button,
.lineup-list button {
    padding: 7px;
    border: 1px solid #ffffff20;
    color: white;
    background: none;
}
.legacy-browser {
    display: none !important;
}
.hero {
    min-height: 150px;
    align-items: center;
    padding: 26px 30px;
}
.hero h1 {
    font-size: clamp(2.7rem, 4.2vw, 4.35rem);
}
.create-match {
    display: flex;
    min-width: 190px;
    align-items: center;
    gap: 11px;
    text-align: left;
}
.create-match > b {
    display: grid;
    width: 28px;
    height: 28px;
    place-items: center;
    border: 1px solid #03101b55;
    font:
        500 1.1rem Arial,
        sans-serif;
}
.create-match span,
.create-match small {
    display: block;
}
.create-match small {
    margin-top: 3px;
    font:
        500 0.52rem Arial,
        sans-serif;
    text-transform: none;
}
.summary article {
    min-height: 78px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.summary span small {
    display: block;
    margin-top: 3px;
    color: #536977;
    font-size: 0.54rem;
}
.match-browser {
    border: 1px solid #ffffff18;
    background: #07111e;
}
.browser-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 18px;
    border-bottom: 1px solid #ffffff12;
}
.browser-head span,
.browser-head strong {
    display: block;
}
.browser-head span {
    color: var(--color-accent);
    font: 800 0.54rem var(--font-display);
    letter-spacing: 0.13em;
    text-transform: uppercase;
}
.browser-head strong {
    margin-top: 5px;
    font: 750 0.68rem var(--font-display);
    text-transform: uppercase;
}
.browser-head button,
.empty-state button {
    padding: 8px 10px;
    border: 1px solid #45b7ff55;
    color: var(--color-accent);
    background: #45b7ff0b;
    font: 750 0.5rem var(--font-display);
    text-transform: uppercase;
    cursor: pointer;
}
.match-browser .filters {
    grid-template-columns:
        minmax(220px, 1.2fr) 155px 165px 155px minmax(190px, 0.85fr)
        auto;
    margin: 0;
    padding: 15px 18px;
    border-bottom: 1px solid #ffffff12;
    background: #050c14;
}
.match-browser .filters input,
.match-browser .filters select {
    font:
        500 0.68rem Arial,
        sans-serif;
}
.match-browser .filters button:disabled {
    opacity: 0.55;
    cursor: wait;
}
.match-browser .table {
    border: 0;
}
.match-browser .row {
    grid-template-columns: 150px minmax(250px, 1.5fr) 90px 145px 90px 122px;
    min-height: 74px;
    padding: 13px 18px;
    transition: background 0.18s ease;
}
.match-browser .row:not(.head):hover {
    background: #45b7ff08;
}
.match-browser .row.featured {
    box-shadow: inset 3px 0 var(--color-accent);
}
.kickoff strong,
.kickoff small {
    display: block;
}
.kickoff strong {
    font: 750 0.7rem var(--font-display);
    text-transform: uppercase;
}
.kickoff small {
    margin-top: 5px;
    color: #657987;
    font-size: 0.58rem;
}
.teams {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 24px minmax(0, 1fr);
    align-items: center;
    gap: 6px;
}
.teams strong {
    overflow: hidden;
    font: 750 0.72rem var(--font-display);
    text-overflow: ellipsis;
    text-transform: uppercase;
    white-space: nowrap;
}
.teams span {
    color: #596c79;
    font: 700 0.53rem var(--font-display);
    text-align: center;
    text-transform: uppercase;
}
.score {
    font: 850 0.95rem var(--font-display);
    text-align: center;
}
.status-stack {
    display: grid;
    justify-items: start;
    gap: 5px;
}
.status-stack small {
    color: var(--color-accent);
    font: italic 700 0.48rem var(--font-display);
    text-transform: uppercase;
}
.status-badge {
    display: inline-flex;
    padding: 5px 8px;
    border: 1px solid #ffffff20;
    border-radius: 20px;
    color: #a9b7c1;
    background: #ffffff08;
    font: 750 0.5rem var(--font-display);
    text-transform: uppercase;
}
.status-scheduled {
    color: #83ceff;
    border-color: #45b7ff55;
    background: #45b7ff0c;
}
.status-live {
    color: #ff8f96;
    border-color: #ff657055;
    background: #ff65700c;
}
.status-completed,
.status-walkover {
    color: #c6d0d7;
}
.status-postponed {
    color: #f3c978;
    border-color: #f3c97855;
    background: #f3c9780c;
}
.status-cancelled {
    color: #ff8f96;
    border-color: #ff657044;
}
.publication {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #687b88;
    font: 750 0.52rem var(--font-display);
    text-transform: uppercase;
}
.publication i {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #52636f;
}
.publication.public {
    color: #9cd9ff;
}
.publication.public i {
    background: var(--color-accent);
    box-shadow: 0 0 8px #45b7ff88;
}
.match-browser .actions {
    justify-content: flex-end;
}
.match-browser .actions button {
    display: grid;
    width: 34px;
    height: 34px;
    padding: 0;
    place-items: center;
    border-color: #ffffff18;
    cursor: pointer;
    transition: 0.18s ease;
}
.match-browser .actions button:hover {
    border-color: #45b7ff66;
    color: var(--color-accent);
    background: #45b7ff0b;
}
.match-browser .actions svg {
    width: 15px;
    height: 15px;
    stroke-width: 1.7;
}
.empty-state {
    padding: 58px 20px;
    text-align: center;
}
.empty-state strong {
    font: 850 1.05rem var(--font-display);
    text-transform: uppercase;
}
.empty-state p {
    margin: 8px 0 17px;
    color: #71838f;
    font-size: 0.67rem;
}
.pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 14px 18px;
    border-top: 1px solid #ffffff12;
    color: #687b88;
    font-size: 0.58rem;
}
.pagination nav {
    display: flex;
    align-items: center;
    gap: 12px;
}
.pagination a {
    padding: 7px 9px;
    border: 1px solid #ffffff18;
    color: #b8c6cf;
    text-decoration: none;
}
.pagination a:hover {
    color: var(--color-accent);
    border-color: #45b7ff55;
}
.pagination strong {
    color: #8fa1ad;
    font-weight: 600;
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
    width: min(850px, 100%);
    max-height: 92vh;
    overflow: auto;
    border: 1px solid #45b7ff55;
    background: #07111e;
}
.dialog > header {
    display: flex;
    justify-content: space-between;
    padding: 20px;
    border-bottom: 1px solid #ffffff18;
}
.dialog h2 {
    margin: 0;
    font: 900 1.4rem var(--font-display);
    text-transform: uppercase;
}
.dialog header button {
    border: 0;
    color: white;
    background: none;
    font-size: 1.5rem;
}
.dialog nav {
    display: flex;
    border-bottom: 1px solid #ffffff18;
}
.dialog nav button {
    padding: 13px;
    border: 0;
    border-bottom: 2px solid transparent;
    color: #71828e;
    background: none;
}
.dialog nav button.active {
    color: white;
    border-color: var(--color-accent);
}
.fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    padding: 20px;
}
.fields label {
    position: relative;
    display: grid;
    gap: 7px;
    color: #8fa0ac;
    font: 700 0.53rem var(--font-display);
    text-transform: uppercase;
}
.fields label > b {
    position: absolute;
    top: 0;
    right: 0;
    color: var(--color-accent);
}
.fields label > small {
    color: #657987;
    font:
        500 0.55rem/1.45 Arial,
        sans-serif;
    letter-spacing: 0;
    text-transform: none;
}
.fields label > em {
    color: #ff9299;
    font:
        500 0.55rem/1.4 Arial,
        sans-serif;
    font-style: normal;
    letter-spacing: 0;
    text-transform: none;
}
.season-field {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 8px;
}
.season-field > label {
    display: grid;
    gap: 7px;
}
.season-field-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.season-field-actions a,
.season-field-actions button {
    min-height: 30px;
    padding: 7px 9px;
    border: 1px solid #45b7ff55;
    color: var(--color-accent);
    background: #45b7ff0b;
    font: 750 0.5rem var(--font-display);
    text-decoration: none;
    text-transform: uppercase;
    cursor: pointer;
}
.season-field-actions button:disabled {
    cursor: wait;
    opacity: 0.55;
}
.season-field-note {
    color: #657987;
    font-size: 0.55rem;
    line-height: 1.45;
}
.field-intro {
    padding: 12px 14px;
    border: 1px solid #45b7ff30;
    color: #9fc6df;
    background: #45b7ff0b;
    font-size: 0.68rem;
    line-height: 1.5;
}
.full {
    grid-column: 1/-1;
}
.check {
    display: flex !important;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}
.check input[type="checkbox"] {
    appearance: none;
    -webkit-appearance: none;
    display: grid;
    flex: 0 0 20px;
    width: 20px !important;
    height: 20px;
    margin: 0 !important;
    padding: 0 !important;
    place-content: center;
    border: 1px solid #526675 !important;
    border-radius: 4px;
    background: #030911 !important;
    cursor: pointer;
    transition: 0.18s ease;
}
.check input[type="checkbox"]::before {
    width: 10px;
    height: 6px;
    content: "";
    border-bottom: 2px solid #03101a;
    border-left: 2px solid #03101a;
    opacity: 0;
    transform: translateY(-1px) rotate(-45deg) scale(0.65);
    transition: 0.12s ease;
}
.check input[type="checkbox"]:checked {
    border-color: var(--color-accent) !important;
    background: var(--color-accent) !important;
    box-shadow: 0 0 0 3px #45b7ff20;
}
.check input[type="checkbox"]:checked::before {
    opacity: 1;
    transform: translateY(-1px) rotate(-45deg) scale(1);
}
.check input[type="checkbox"]:focus-visible {
    box-shadow: 0 0 0 3px #45b7ff45;
}
.status-check {
    min-height: 68px;
    box-sizing: border-box;
    padding: 11px 13px;
    border: 1px solid #ffffff14;
    background: #08131e;
}
.status-check:has(input:checked) {
    border-color: #45b7ff55;
    background: #071827;
}
.status-check span,
.status-check strong,
.status-check small {
    display: block;
}
.status-check strong {
    color: #dce7ef;
    font: 750 0.58rem var(--font-display);
}
.status-check small {
    margin-top: 5px;
    color: #647987;
    font:
        500 0.55rem Arial,
        sans-serif;
    text-transform: none;
}
.errors {
    padding: 0 20px;
}
.dialog footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding: 15px 20px;
    border-top: 1px solid #ffffff18;
}
.cancel,
.danger {
    color: white !important;
    background: transparent !important;
    border: 1px solid #ffffff20 !important;
}
.danger {
    margin-right: auto;
    color: #ff9da4 !important;
}
.lineup-actions {
    display: flex;
    gap: 7px;
    padding: 15px 20px;
}
.legacy-lineup {
    display: none !important;
}
.lineup {
    width: min(1380px, calc(100vw - 64px));
    overflow-x: hidden;
}
.lineup > header > h2 {
    display: none;
}
.lineup > header > div small,
.team-head small {
    color: var(--color-accent);
    font: 800 0.52rem var(--font-display);
    letter-spacing: 0.15em;
    text-transform: uppercase;
}
.lineup > header p {
    margin: 6px 0 0;
    color: #80919d;
    font-size: 0.65rem;
}
.lineup-help {
    margin: 16px 20px 0;
    padding: 12px 14px;
    border: 1px solid #45b7ff30;
    color: #9fc6df;
    background: #45b7ff0b;
    font-size: 0.67rem;
    line-height: 1.5;
}
.lineup-teams {
    display: grid;
    grid-template-columns: 1fr;
    gap: 14px;
    padding: 16px 20px 20px;
}
.lineup-team {
    min-width: 0;
    border: 1px solid #ffffff18;
    background: #050c14;
}
.team-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 16px;
    border-bottom: 1px solid #ffffff16;
}
.team-head h3 {
    margin: 5px 0 0;
    font: 850 1.1rem var(--font-display);
    text-transform: uppercase;
}
.team-head button {
    display: flex;
    min-width: 175px;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    border: 1px solid #45b7ff55;
    color: var(--color-accent);
    background: #45b7ff0b;
    font: 750 0.56rem/1.1 var(--font-display);
    text-align: left;
    text-transform: uppercase;
    cursor: pointer;
}
.team-head button > b {
    display: grid;
    flex: 0 0 27px;
    width: 27px;
    height: 27px;
    place-items: center;
    border: 1px solid #45b7ff55;
    font:
        500 1.15rem/1 Arial,
        sans-serif;
}
.team-head button > span,
.team-head button small {
    display: block;
}
.team-head button small {
    margin-top: 3px;
    color: #7893a5;
    font:
        500 0.5rem Arial,
        sans-serif;
    letter-spacing: 0;
    text-transform: none;
}
.roster-summary {
    display: grid;
    grid-template-columns: auto auto 1fr;
    gap: 8px;
    padding: 11px 16px;
    border-bottom: 1px solid #ffffff10;
}
.roster-summary span {
    color: #71838f;
    font-size: 0.56rem;
}
.roster-summary span:last-child {
    text-align: right;
}
.roster-summary b {
    color: #dce8ef;
    text-transform: uppercase;
}
.role-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 15px 16px 8px;
}
.role-heading strong {
    color: #dce8ef;
    font: 800 0.58rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.role-heading span,
.empty-roster {
    color: #627583;
    font-size: 0.54rem;
}
.empty-roster {
    margin: 0 16px 8px;
    padding: 13px;
    border: 1px dashed #ffffff18;
    text-align: center;
}
.roster-list {
    display: grid;
    gap: 8px;
    padding: 0 16px;
}
.roster-card {
    display: grid;
    grid-template-columns: minmax(180px, 1.6fr) minmax(180px, 1.35fr) 145px 140px 78px 125px 46px;
    align-items: end;
    gap: 7px;
    padding: 11px;
    border: 1px solid #ffffff12;
    background: #08131e;
}
.roster-card label {
    display: grid;
    gap: 6px;
    min-width: 0;
    color: #8294a0;
    font: 750 0.48rem var(--font-display);
    letter-spacing: 0.07em;
    text-transform: uppercase;
}
.roster-card input,
.roster-card select {
    box-sizing: border-box;
    width: 100%;
    min-height: 38px;
    padding: 8px;
    border: 1px solid #ffffff20;
    color: white;
    background: #050b13;
    font:
        500 0.69rem Arial,
        sans-serif;
}
.roster-card label > small {
    color: #5d7180;
    font:
        500 0.5rem/1.35 Arial,
        sans-serif;
    letter-spacing: 0;
    text-transform: none;
}
.roster-card input:disabled {
    color: #536571;
    background: #071019;
    cursor: not-allowed;
    opacity: 0.62;
}
.captain-check {
    min-height: 38px;
    gap: 8px;
    padding: 0;
    border: 0;
    background: transparent;
}
.captain-field {
    display: grid;
    align-content: end;
    gap: 6px;
    min-width: 0;
}
.roster-label {
    color: #8294a0;
    font: 750 0.48rem var(--font-display);
    letter-spacing: 0.07em;
    text-transform: uppercase;
}
.captain-check input[type="checkbox"] {
    flex-basis: 16px;
    width: 16px !important;
    min-height: 16px;
    height: 16px;
    padding: 0 !important;
}
.captain-check input[type="checkbox"]::before {
    width: 8px;
    height: 4px;
}
.captain-check strong,
.captain-check small {
    display: block;
}
.captain-check strong {
    color: #dce8ef;
    font-size: 0.49rem;
}
.captain-check small {
    margin-top: 3px;
    color: #607482;
    font:
        500 0.49rem Arial,
        sans-serif;
    text-transform: none;
}
.remove-player {
    display: grid;
    width: 38px;
    min-height: 38px;
    padding: 0;
    place-items: center;
    border: 1px solid #ff667044;
    color: #ff9299;
    background: #ff66700b;
    font: 750 0.48rem var(--font-display);
    text-transform: uppercase;
    cursor: pointer;
}
.remove-player svg {
    width: 16px;
    height: 16px;
    stroke-width: 1.8;
}
.remove-player:hover {
    border-color: #ff667088;
    background: #ff667018;
}
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
.lineup-list {
    display: grid;
    gap: 6px;
    padding: 0 20px 20px;
}
.lineup-list > div {
    display: grid;
    grid-template-columns: 120px 1fr 1fr 110px 65px 45px 35px;
    align-items: center;
    gap: 6px;
}
@media (max-width: 1200px) {
    .roster-card {
        grid-template-columns: 1.4fr 1.2fr 1fr;
    }
}
@media (max-width: 900px) {
    .row {
        grid-template-columns: 1fr 1fr 70px 1fr;
    }
    .row > :nth-child(5),
    .row.head > :nth-child(5) {
        display: none;
    }
    .filters {
        grid-template-columns: 1fr 1fr;
    }
    .lineup-list > div {
        grid-template-columns: 1fr 1fr;
    }
    .lineup-teams {
        grid-template-columns: 1fr;
    }
    .roster-card {
        grid-template-columns: 1fr 1fr;
    }
}
@media (max-width: 600px) {
    .hero {
        align-items: flex-start;
        flex-direction: column;
    }
    .summary {
        grid-template-columns: 1fr 1fr;
    }
    .row {
        grid-template-columns: 1fr 65px 1fr;
    }
    .row > :first-child,
    .row.head > :first-child {
        grid-column: 1/-1;
    }
    .row > :nth-child(6),
    .row.head > :nth-child(6) {
        grid-column: 1/-1;
    }
    .fields {
        grid-template-columns: 1fr;
    }
    .full {
        grid-column: auto;
    }
    .filters {
        grid-template-columns: 1fr;
    }
    .lineup-teams {
        padding: 12px;
    }
    .team-head {
        align-items: flex-start;
        flex-direction: column;
    }
    .team-head button {
        width: 100%;
    }
    .roster-card {
        grid-template-columns: 1fr;
    }
    .roster-summary {
        grid-template-columns: 1fr 1fr;
    }
    .roster-summary span:last-child {
        grid-column: 1/-1;
        text-align: left;
    }
}
.event-dialog {
    width: min(1080px, 100%);
}
.reconcile {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 12px 20px;
    border-bottom: 1px solid #ffffff18;
    color: #ffb6ba;
    background: #ff5d6812;
}
.reconcile.ok {
    color: #6ed5ff;
    background: #45b7ff10;
}
.reconcile strong {
    margin-left: auto;
    text-transform: uppercase;
}
.event-layout {
    display: grid;
    grid-template-columns: 340px 1fr;
    min-height: 570px;
}
.event-timeline {
    padding: 16px;
    border-right: 1px solid #ffffff18;
}
.new-event {
    width: 100%;
    margin-bottom: 10px;
    padding: 11px;
    border: 1px solid var(--color-accent);
    color: var(--color-accent);
    background: none;
}
.event-row {
    display: grid;
    width: 100%;
    grid-template-columns: 48px minmax(0, 1fr) auto;
    gap: 10px;
    padding: 11px 5px;
    border: 0;
    border-bottom: 1px solid #ffffff12;
    color: white;
    background: none;
    text-align: left;
    cursor: pointer;
    transition: 0.18s ease;
}
.event-row:hover,
.event-row.active {
    background: #45b7ff0b;
}
.event-row.active {
    box-shadow: inset 3px 0 var(--color-accent);
}
.event-row > b {
    color: var(--color-accent);
    font: 900 1rem var(--font-display);
}
.event-row span {
    display: grid;
    gap: 3px;
}
.event-row strong {
    text-transform: uppercase;
}
.event-row small,
.empty,
.dialog header small {
    color: #7f909d;
}
.event-row > i {
    align-self: center;
    padding: 4px 6px;
    border: 1px solid #ffffff18;
    border-radius: 10px;
    color: #718591;
    font:
        normal 700 9px/1 Arial,
        sans-serif;
}
.event-row > i.public {
    color: #8ed7ff;
    border-color: #45b7ff45;
}
.event-form h3 {
    margin: 16px 20px 0;
    font-size: 18px;
    text-transform: uppercase;
}
.event-form .fields {
    gap: 12px 14px;
    padding: 14px 20px 16px;
}
.event-form .fields label {
    gap: 6px;
    color: #bdcbd4;
    font-family: Arial, sans-serif;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.2;
    letter-spacing: 0;
    text-transform: none;
}
.event-form .fields input,
.event-form .fields select,
.event-form .fields textarea {
    box-sizing: border-box;
    width: 100%;
    min-height: 40px;
    padding: 9px 11px;
    outline: none;
    font-family: Arial, sans-serif;
    font-size: 13px;
    font-weight: 400;
}
.event-form .fields textarea {
    min-height: 68px;
    resize: vertical;
}
.event-form .fields input::placeholder,
.event-form .fields textarea::placeholder {
    color: #617582;
    opacity: 1;
}
.event-form .fields input:focus,
.event-form .fields select:focus,
.event-form .fields textarea:focus {
    border-color: #45b7ff88;
}
.event-form .fields label > small,
.time-fields > small {
    color: #718591;
    font:
        400 10px/1.35 Arial,
        sans-serif;
    letter-spacing: 0;
    text-transform: none;
}
.time-fields {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 6px;
}
.time-fields > small {
    grid-column: 1/-1;
}
.event-public-check {
    grid-column: 1/-1;
    align-items: flex-start;
    width: fit-content;
    padding-top: 2px;
}
.event-public-check input[type="checkbox"] {
    flex-basis: 16px;
    width: 16px !important;
    height: 16px;
    border-radius: 3px;
}
.event-public-check input[type="checkbox"]::before {
    width: 8px;
    height: 4px;
}
.event-public-check > span {
    display: grid;
    gap: 3px;
}
.event-public-check strong {
    color: #dce7ed;
    font:
        600 12px/1.2 Arial,
        sans-serif;
    text-transform: none;
}
.event-public-check span small {
    color: #718591;
    font:
        400 10px/1.35 Arial,
        sans-serif;
    text-transform: none;
}
.event-form footer {
    gap: 8px;
    padding: 12px 20px 16px;
    border-top: 1px solid #ffffff12;
}
.event-form footer button {
    min-height: 38px;
    padding: 9px 14px;
}
@media (max-width: 900px) {
    .event-layout {
        grid-template-columns: 1fr;
    }
    .event-timeline {
        max-height: 250px;
        overflow: auto;
        border-right: 0;
        border-bottom: 1px solid #ffffff18;
    }
}
@media (max-width: 600px) {
    .reconcile {
        align-items: flex-start;
        flex-direction: column;
        gap: 3px;
    }
    .reconcile strong {
        margin-left: 0;
    }
    .event-form .fields {
        grid-template-columns: 1fr;
    }
    .event-form .fields .full,
    .event-public-check {
        grid-column: auto;
    }
    .time-fields {
        grid-template-columns: 1fr 1fr;
    }
    .event-form footer {
        flex-wrap: wrap;
    }
}
@media (max-width: 1150px) {
    .match-browser .filters {
        grid-template-columns: 1fr 1fr;
    }
    .match-browser .search-filter {
        grid-column: 1/-1;
    }
    .match-browser .row {
        grid-template-columns: 130px minmax(210px, 1fr) 80px 125px 80px 112px;
    }
}
@media (max-width: 800px) {
    .hero {
        align-items: flex-start;
        gap: 20px;
    }
    .create-match {
        width: 100%;
        box-sizing: border-box;
    }
    .summary {
        grid-template-columns: 1fr 1fr;
    }
    .match-browser .filters {
        grid-template-columns: 1fr;
    }
    .match-browser .search-filter {
        grid-column: auto;
    }
    .match-browser .row.head {
        display: none;
    }
    .match-browser .row {
        position: relative;
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 14px;
        min-height: 0;
        padding: 18px;
    }
    .match-browser .row.featured {
        box-shadow: inset 3px 0 var(--color-accent);
    }
    .kickoff {
        grid-column: 1;
    }
    .teams {
        grid-column: 1/-1;
        grid-template-columns: 1fr 30px 1fr;
        padding: 14px 0;
        border-block: 1px solid #ffffff10;
    }
    .teams strong:last-child {
        text-align: right;
    }
    .score {
        position: absolute;
        top: 75px;
        left: 50%;
        z-index: 1;
        padding: 4px 7px;
        background: #07111e;
        transform: translateX(-50%);
    }
    .status-stack {
        grid-column: 1;
        grid-row: 3;
    }
    .publication {
        grid-column: 2;
        grid-row: 3;
        justify-self: end;
    }
    .match-browser .actions {
        grid-column: 1/-1;
        justify-content: flex-end;
        padding-top: 11px;
        border-top: 1px solid #ffffff10;
    }
    .pagination {
        align-items: flex-start;
        flex-direction: column;
    }
}
@media (max-width: 520px) {
    .hero {
        padding: 22px;
    }
    .hero h1 {
        font-size: 2.7rem;
    }
    .summary {
        gap: 8px;
    }
    .summary article {
        padding: 14px;
    }
    .browser-head {
        align-items: flex-start;
        gap: 12px;
    }
    .match-browser .filters {
        padding: 14px;
    }
    .match-browser .row {
        padding: 16px 14px;
    }
    .teams strong {
        font-size: 0.65rem;
    }
    .pagination nav {
        width: 100%;
        justify-content: space-between;
    }
}

/* Match browser: one table structure, one column baseline, one type system. */
.create-match > span {
    color: #03101b;
    font-size: 12px;
    letter-spacing: 0.04em;
}
.create-match > span small {
    color: #183446;
    font-size: 11px;
}
.browser-head span {
    color: var(--color-accent);
    font-size: 11px;
    line-height: 1;
}
.browser-head strong {
    margin-top: 7px;
    color: #dce7ee;
    font-family: Arial, sans-serif;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.2;
    letter-spacing: 0;
    text-transform: none;
}
.match-browser .filters label {
    gap: 8px;
    font-size: 11px;
    line-height: 1;
}
.match-browser .filters input,
.match-browser .filters select {
    min-height: 46px;
    padding-inline: 13px;
    font-family: Arial, sans-serif;
    font-size: 13px;
    font-weight: 500;
}
.match-browser .filters button {
    min-width: 64px;
    min-height: 46px;
    font-size: 11px;
}
.matches-grid {
    width: 100%;
    overflow: hidden;
    font-family: Arial, sans-serif;
}
.matches-grid-head,
.match-grid-row {
    display: grid;
    grid-template-columns:
        minmax(132px, 0.8fr) minmax(250px, 1.75fr) minmax(80px, 0.48fr)
        minmax(118px, 0.75fr) minmax(100px, 0.62fr) 112px;
    align-items: center;
    gap: 14px;
    box-sizing: border-box;
}
.matches-grid-head {
    min-height: 50px;
    padding: 0 20px;
    border-bottom: 1px solid #ffffff14;
    color: #91a4b2;
    background: #091522;
    font-family: var(--font-display);
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.matches-grid-head span:nth-child(n + 2) {
    text-align: center;
}
.match-grid-row {
    position: relative;
    min-height: 108px;
    padding: 14px 20px;
    border-bottom: 1px solid #ffffff10;
    color: #eaf0f4;
    background: #07111e;
    transition: background 0.18s ease;
}
.match-grid-row:hover {
    background: #0a1826;
}
.match-grid-row.featured {
    box-shadow: inset 4px 0 var(--color-accent);
}
.match-cell {
    min-width: 0;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.35;
}
.score-cell,
.status-cell,
.publication-cell {
    text-align: center;
}
.table-kickoff strong,
.table-kickoff small {
    display: block;
    font-family: Arial, sans-serif;
    font-style: normal;
    letter-spacing: 0;
    text-transform: none;
}
.fixture-cell {
    display: flex;
    min-width: 0;
    flex-direction: column;
    gap: 8px;
}
.featured-label {
    align-self: center;
    color: var(--color-accent);
    font-family: var(--font-display);
    font-size: 9px;
    font-style: normal;
    font-weight: 700;
    line-height: 1;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}
.match-type-badge {
    align-self: center;
    padding: 3px 7px;
    border: 1px solid #45b7ff42;
    color: #8fcaee;
    font: 700 8px/1 var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.sparing-info {
    border-color: #45b7ff4f;
    color: #9ed9fb;
    background: #45b7ff0c;
}
.table-kickoff strong {
    color: #eef4f7;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.3;
}
.table-kickoff small {
    margin-top: 4px;
    color: #8093a0;
    font-size: 13px;
    font-weight: 500;
    line-height: 1.3;
}
.table-teams {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 28px minmax(0, 1fr);
    align-items: center;
    gap: 8px;
}
.table-teams strong {
    overflow: hidden;
    color: #edf3f6;
    font-family: Arial, sans-serif;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.3;
    letter-spacing: 0;
    text-overflow: ellipsis;
    text-transform: none;
    white-space: nowrap;
}
.table-teams strong:first-child {
    text-align: right;
}
.table-teams strong:last-child {
    text-align: left;
}
.table-teams span {
    color: #718593;
    font-family: Arial, sans-serif;
    font-size: 12px;
    font-weight: 500;
    text-align: center;
    text-transform: lowercase;
}
.table-score {
    color: #f4f7f9;
    font-family: Arial, sans-serif;
    font-size: 17px;
    font-weight: 700;
    line-height: 1;
    white-space: nowrap;
}
.table-status {
    display: flex;
    align-items: center;
    flex-direction: column;
    gap: 6px;
}
.table-status .status-badge {
    padding: 6px 10px;
    font-size: 10px;
    line-height: 1;
    white-space: nowrap;
}
.table-publication {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #7f929f;
    font-family: Arial, sans-serif;
    font-size: 12px;
    font-weight: 600;
    line-height: 1;
    letter-spacing: 0;
    text-transform: none;
    white-space: nowrap;
}
.table-publication i {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #52636f;
}
.table-publication.public {
    color: #9cd9ff;
}
.table-publication.public i {
    background: var(--color-accent);
    box-shadow: 0 0 8px #45b7ff88;
}
.table-actions {
    display: flex;
    width: 112px;
    align-items: center;
    flex-direction: column;
    justify-content: center;
    gap: 5px;
    white-space: nowrap;
}
.table-actions button {
    display: grid;
    width: 100%;
    min-height: 28px;
    padding: 0 8px;
    place-items: center;
    border: 1px solid #ffffff1c;
    color: #dce6ec;
    font-family: var(--font-display);
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    background: transparent;
    cursor: pointer;
    transition: 0.18s ease;
}
.table-actions button:hover {
    border-color: #45b7ff70;
    color: var(--color-accent);
    background: #45b7ff0d;
}
.table-actions button:disabled {
    opacity: 0.35;
    cursor: not-allowed;
    border-color: #ffffff12;
    color: #71808b;
    background: transparent;
}
@media (max-width: 1100px) {
    .matches-grid-head,
    .match-grid-row {
        grid-template-columns:
            minmax(120px, 0.8fr) minmax(220px, 1.65fr) minmax(72px, 0.45fr)
            minmax(108px, 0.72fr) minmax(90px, 0.58fr) 102px;
        gap: 10px;
        padding-inline: 14px;
    }
    .table-actions {
        width: 102px;
    }
}
@media (max-width: 800px) {
    .matches-grid {
        padding: 12px;
        box-sizing: border-box;
        overflow: visible;
    }
    .matches-grid-head {
        display: none;
    }
    .match-grid-row {
        display: block;
        margin-bottom: 12px;
        padding: 8px 16px;
        border: 1px solid #ffffff14;
    }
    .match-grid-row.featured {
        box-shadow: inset 3px 0 var(--color-accent);
    }
    .match-cell {
        display: grid;
        grid-template-columns: 110px minmax(0, 1fr);
        align-items: center;
        min-height: 52px;
        height: auto;
        padding: 11px 0;
        border-bottom: 1px solid #ffffff0d;
        text-align: left !important;
    }
    .match-cell::before {
        content: attr(data-label);
        color: #778b98;
        font-family: var(--font-display);
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .match-cell:last-child {
        border-bottom: 0;
    }
    .fixture-cell {
        flex-direction: initial;
        gap: 0;
    }
    .fixture-cell::before {
        align-self: center;
    }
    .fixture-cell .featured-label {
        grid-column: 2;
        align-self: start;
        justify-self: start;
        margin-bottom: 7px;
    }
    .fixture-cell .table-teams {
        grid-column: 2;
    }
    .table-teams {
        grid-template-columns: minmax(0, 1fr) 24px minmax(0, 1fr);
    }
    .table-teams strong:first-child {
        text-align: left;
    }
    .table-status {
        align-items: flex-start;
    }
    .table-publication,
    .table-actions {
        justify-content: flex-start;
    }
    .actions-cell {
        grid-template-columns: 110px minmax(0, 1fr);
    }
    .table-actions {
        display: grid;
        width: 100%;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 6px;
    }
}
@media (max-width: 520px) {
    .match-grid-row {
        padding-inline: 12px;
    }
    .match-cell,
    .actions-cell {
        grid-template-columns: 92px minmax(0, 1fr);
    }
    .table-actions {
        grid-template-columns: 1fr;
    }
}
</style>
