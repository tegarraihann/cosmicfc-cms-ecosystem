<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ConfirmActionModal from "@/Components/Admin/ConfirmActionModal.vue";
import MediaPicker from "@/Components/Admin/MediaPicker.vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    sponsors: Array,
    seasons: Array,
    media: Array,
    stats: Object,
    filters: Object,
    can: Object,
});
const page = usePage();
const panel = ref(null);
const selected = ref(null);
const archiveTarget = ref(null);
const selectedLogo = ref(null);
const tab = ref("profile");
const blank = () => ({
    name: "",
    category: "supporting",
    description: "",
    logo_media_id: null,
    website_url: "",
    instagram_url: "",
    contact_name: "",
    contact_email: "",
    status: "draft",
});
const form = useForm(blank());
const term = useForm({
    id: null,
    season_id: null,
    tier: "official",
    title: "",
    starts_at: "",
    ends_at: "",
    status: "draft",
    internal_notes: "",
});
const filter = useForm({
    search: props.filters?.search || "",
    category: props.filters?.category || "",
    status: props.filters?.status || "",
});
function open(s = null) {
    selected.value = s;
    selectedLogo.value = s?.logo_media_id
        ? props.media.find(
              (item) => Number(item.id) === Number(s.logo_media_id),
          ) || { id: s.logo_media_id, title: s.name, url: s.logo_url }
        : null;
    form.defaults(blank());
    form.reset();
    if (s)
        form.defaults(
            Object.fromEntries(
                Object.keys(blank()).map((k) => [k, s[k] ?? blank()[k]]),
            ),
        ).reset();
    panel.value = "editor";
    tab.value = "profile";
}
function save() {
    form.submit(
        selected.value ? "put" : "post",
        selected.value
            ? route("admin.sponsors.update", selected.value.id)
            : route("admin.sponsors.store"),
        { preserveScroll: true, onSuccess: () => (panel.value = null) },
    );
}
function openTerm(s, p = null) {
    selected.value = s;
    term.defaults({
        id: p?.id || null,
        season_id: p?.season_id || null,
        tier: p?.tier || "official",
        title: p?.title || "",
        starts_at: p?.starts_at?.slice(0, 10) || "",
        ends_at: p?.ends_at?.slice(0, 10) || "",
        status: p?.status || "draft",
        internal_notes: p?.internal_notes || "",
    });
    term.reset();
    panel.value = "term";
}
function saveTerm() {
    term.post(
        route("admin.sponsors.partnerships.save", {
            sponsor: selected.value.id,
            partnership: term.id || undefined,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                selected.value = props.sponsors.find(
                    (sponsor) => sponsor.id === selected.value.id,
                );
                panel.value = "terms";
            },
        },
    );
}
function search() {
    router.get(route("admin.sponsors.index"), filter.data(), {
        preserveState: true,
    });
}
function resetFilters() {
    filter.search = "";
    filter.category = "";
    filter.status = "";
    search();
}
function manageTerms(s) {
    selected.value = s;
    panel.value = "terms";
}
function archive(s) {
    archiveTarget.value = s;
}
function confirmArchive() {
    router.post(
        route("admin.sponsors.archive", archiveTarget.value.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => (archiveTarget.value = null),
        },
    );
}
function handleLogoValue(value) {
    if (!value) selectedLogo.value = null;
}
const categoryLabel = (value) =>
    ({
        main: "Sponsor Utama",
        apparel: "Partner Apparel",
        venue: "Partner Venue",
        community: "Partner Komunitas",
        media: "Media Partner",
        supporting: "Supporting Partner",
    })[value] || value;
const sponsorStatusLabel = (value) =>
    ({ draft: "Draft", active: "Aktif", archived: "Diarsipkan" })[value] ||
    value;
const tierLabel = (value) =>
    ({
        principal: "Principal Partner",
        official: "Official Partner",
        supporting: "Supporting Partner",
        community: "Community Partner",
    })[value] || value;
const partnershipStatusLabel = (value) =>
    ({
        draft: "Draft",
        scheduled: "Terjadwal",
        active: "Aktif",
        expired: "Berakhir",
        archived: "Diarsipkan",
    })[value] || value;
const formatDate = (value) =>
    value
        ? new Intl.DateTimeFormat("id-ID", {
              day: "numeric",
              month: "short",
              year: "numeric",
          }).format(new Date(`${value}T00:00:00`))
        : "Tanpa batas waktu";
function primaryPartnership(sponsor) {
    const now = new Date();
    return (
        sponsor.partnerships.find(
            (item) =>
                item.status === "active" &&
                new Date(`${item.starts_at}T00:00:00`) <= now &&
                (!item.ends_at || new Date(`${item.ends_at}T23:59:59`) >= now),
        ) ||
        sponsor.partnerships.find((item) => item.status === "scheduled") ||
        sponsor.partnerships[0] ||
        null
    );
}
function publicState(sponsor) {
    if (sponsor.status === "archived")
        return {
            key: "archived",
            label: "Profil diarsipkan",
            detail: "Sponsor tidak ditampilkan pada website.",
        };
    if (sponsor.status !== "active")
        return {
            key: "draft",
            label: "Belum tayang",
            detail: "Profil sponsor masih berstatus draft.",
        };
    const partnership = primaryPartnership(sponsor);
    if (!partnership)
        return {
            key: "incomplete",
            label: "Belum tayang",
            detail: "Belum memiliki periode kerja sama.",
        };
    if (partnership.status === "scheduled")
        return {
            key: "scheduled",
            label: "Dijadwalkan",
            detail: `Mulai ${formatDate(partnership.starts_at)}.`,
        };
    if (
        partnership.status === "active" &&
        partnership.published_at &&
        partnership.placement_keys?.length
    )
        return {
            key: "public",
            label: "Tampil di website",
            detail: `Aktif di ${partnership.placement_keys.length} lokasi penayangan.`,
        };
    if (
        ["expired", "archived"].includes(partnership.status) ||
        (partnership.ends_at &&
            new Date(`${partnership.ends_at}T23:59:59`) < new Date())
    )
        return {
            key: "expired",
            label: "Kerja sama berakhir",
            detail: "Tidak lagi ditampilkan pada website.",
        };
    return {
        key: "incomplete",
        label: "Belum tayang",
        detail: "Lengkapi status dan lokasi penayangan.",
    };
}
</script>

<template>
    <Head title="Sponsor & Partners" /><AuthenticatedLayout
        ><template #page-title>Sponsor & Partners</template>
        <div class="page">
            <div v-if="page.props.flash?.success" class="notice">
                {{ page.props.flash.success }}
            </div>
            <header class="hero">
                <div>
                    <span>Partnership Console</span>
                    <h1>Partners Behind <b>The Journey.</b></h1>
                    <p>
                        Kelola identitas sponsor, masa kerja sama, dan lokasi
                        penayangannya dari satu ruang kerja.
                    </p>
                </div>
                <button v-if="can.create" @click="open()">
                    + Tambah Sponsor
                </button>
            </header>
            <section class="stats">
                <article>
                    <small>Total Partner</small
                    ><strong>{{ stats.total }}</strong>
                </article>
                <article>
                    <small>Partner Aktif</small
                    ><strong>{{ stats.active }}</strong>
                </article>
                <article>
                    <small>Berakhir ≤ 30 Hari</small
                    ><strong>{{ stats.expiring }}</strong>
                </article>
                <article>
                    <small>Lokasi Tayang</small
                    ><strong>{{ stats.placement_locations }}</strong>
                </article>
            </section>
            <form class="filters" @submit.prevent="search">
                <label class="filter-field filter-search">
                    <span>Nama Sponsor</span>
                    <input
                        v-model="filter.search"
                        placeholder="Cari berdasarkan nama sponsor"
                    />
                    <small>Masukkan nama sponsor atau partner.</small>
                </label>
                <label class="filter-field">
                    <span>Kategori Sponsor</span>
                    <select v-model="filter.category">
                        <option value="">Semua kategori</option>
                        <option
                            v-for="v in [
                                'main',
                                'apparel',
                                'venue',
                                'community',
                                'media',
                                'supporting',
                            ]"
                            :key="v"
                            :value="v"
                        >
                            {{ categoryLabel(v) }}
                        </option>
                    </select>
                    <small>Saring berdasarkan jenis kerja sama.</small>
                </label>
                <label class="filter-field">
                    <span>Status Profil</span>
                    <select v-model="filter.status">
                        <option value="">Semua status</option>
                        <option value="draft">Draft</option>
                        <option value="active">Aktif</option>
                        <option value="archived">Diarsipkan</option>
                    </select>
                    <small>Saring status data sponsor.</small>
                </label>
                <div class="filter-actions">
                    <button type="submit">Terapkan Filter</button>
                    <button type="button" class="reset" @click="resetFilters">
                        Reset
                    </button>
                </div>
                <p class="filter-result">
                    {{ sponsors.length }} sponsor ditemukan
                </p>
            </form>
            <section class="grid">
                <article v-for="s in sponsors" :key="s.id" class="card">
                    <div class="card-head">
                        <div class="logo">
                            <img
                                v-if="s.logo_url"
                                :src="s.logo_url"
                                :alt="`Logo ${s.name}`"
                            /><span v-else>{{
                                s.name.slice(0, 2).toUpperCase()
                            }}</span>
                        </div>
                        <div class="identity">
                            <div class="eyebrow">
                                <span>{{ categoryLabel(s.category) }}</span>
                                <i :class="s.status">{{
                                    sponsorStatusLabel(s.status)
                                }}</i>
                            </div>
                            <h2>{{ s.name }}</h2>
                            <p>
                                {{
                                    s.description ||
                                    "Deskripsi sponsor belum ditambahkan."
                                }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="visibility"
                        :class="`visibility-${publicState(s).key}`"
                    >
                        <span class="visibility-dot"></span>
                        <div>
                            <strong>{{ publicState(s).label }}</strong>
                            <small>{{ publicState(s).detail }}</small>
                        </div>
                    </div>
                    <button
                        v-if="primaryPartnership(s)"
                        type="button"
                        class="term-summary"
                        @click="openTerm(s, primaryPartnership(s))"
                    >
                        <span>
                            <small>Kerja Sama Utama</small>
                            <strong>{{
                                primaryPartnership(s).title ||
                                tierLabel(primaryPartnership(s).tier)
                            }}</strong>
                        </span>
                        <span>
                            <small>Periode Tayang</small>
                            <strong>
                                {{
                                    formatDate(primaryPartnership(s).starts_at)
                                }}
                                —
                                {{
                                    primaryPartnership(s).ends_at
                                        ? formatDate(
                                              primaryPartnership(s).ends_at,
                                          )
                                        : "Tanpa batas"
                                }}
                            </strong>
                        </span>
                        <span>
                            <small>Status Kerja Sama</small>
                            <strong>{{
                                partnershipStatusLabel(
                                    primaryPartnership(s).status,
                                )
                            }}</strong>
                        </span>
                    </button>
                    <div v-else class="no-term">
                        <strong>Belum ada kerja sama</strong>
                        <span
                            >Tambahkan periode dan lokasi penayangan agar
                            sponsor dapat tampil di website.</span
                        >
                    </div>
                    <p v-if="s.partnerships.length > 1" class="other-terms">
                        +{{ s.partnerships.length - 1 }} periode kerja sama lain
                    </p>
                    <footer>
                        <button v-if="can.update" @click="open(s)">
                            Edit Sponsor</button
                        ><button
                            v-if="can.update"
                            class="primary-action"
                            @click="manageTerms(s)"
                        >
                            Kelola Kerja Sama</button
                        ><button
                            v-if="can.archive && s.status !== 'archived'"
                            class="muted"
                            @click="archive(s)"
                        >
                            Arsipkan
                        </button>
                    </footer>
                </article>
            </section>
            <section v-if="!sponsors.length" class="empty-results">
                <strong>Tidak ada sponsor yang sesuai</strong>
                <p>
                    Ubah kata pencarian atau reset filter untuk menampilkan
                    seluruh sponsor.
                </p>
                <button type="button" @click="resetFilters">
                    Reset Filter
                </button>
            </section>

            <div v-if="panel" class="overlay" @click.self="panel = null">
                <aside class="drawer">
                    <header>
                        <div>
                            <span>{{
                                panel === "term"
                                    ? "Periode Kerja Sama"
                                    : panel === "terms"
                                      ? "Daftar Kerja Sama"
                                      : "Profil Sponsor"
                            }}</span>
                            <h2>
                                {{
                                    panel === "term" || panel === "terms"
                                        ? selected?.name
                                        : selected
                                          ? "Edit Sponsor"
                                          : "Sponsor Baru"
                                }}
                            </h2>
                        </div>
                        <button @click="panel = null">×</button>
                    </header>
                    <form v-if="panel === 'editor'" @submit.prevent="save">
                        <nav>
                            <button
                                type="button"
                                :class="{ on: tab === 'profile' }"
                                @click="tab = 'profile'"
                            >
                                Profil</button
                            ><button
                                type="button"
                                :class="{ on: tab === 'media' }"
                                @click="tab = 'media'"
                            >
                                Logo</button
                            ><button
                                type="button"
                                :class="{ on: tab === 'contact' }"
                                @click="tab = 'contact'"
                            >
                                Kontak & Tautan
                            </button>
                        </nav>
                        <div v-show="tab === 'profile'" class="fields">
                            <label class="field-block">
                                <span class="field-label"
                                    >Nama Sponsor <b>Wajib</b></span
                                >
                                <input
                                    v-model="form.name"
                                    required
                                    placeholder="Contoh: Benecia Apparel"
                                />
                                <small
                                    >Nama yang ditampilkan pada kartu sponsor di
                                    website.</small
                                >
                                <em v-if="form.errors.name">{{
                                    form.errors.name
                                }}</em>
                            </label>
                            <label class="field-block">
                                <span class="field-label"
                                    >Kategori Sponsor <b>Wajib</b></span
                                >
                                <select v-model="form.category">
                                    <option
                                        v-for="v in [
                                            'main',
                                            'apparel',
                                            'venue',
                                            'community',
                                            'media',
                                            'supporting',
                                        ]"
                                        :key="v"
                                        :value="v"
                                    >
                                        {{ categoryLabel(v) }}
                                    </option>
                                </select>
                                <small
                                    >Menentukan jenis kerja sama sponsor.</small
                                >
                                <em v-if="form.errors.category">{{
                                    form.errors.category
                                }}</em>
                            </label>
                            <label class="field-block">
                                <span class="field-label"
                                    >Status Profil <b>Wajib</b></span
                                >
                                <select v-model="form.status">
                                    <option value="draft">Draft</option>
                                    <option value="active">Aktif</option>
                                    <option value="archived">Diarsipkan</option>
                                </select>
                                <small
                                    >Pilih Aktif agar profil dapat digunakan
                                    dalam kerja sama yang ditayangkan.</small
                                >
                                <em v-if="form.errors.status">{{
                                    form.errors.status
                                }}</em>
                            </label>
                            <label class="field-block full">
                                <span class="field-label"
                                    >Deskripsi Sponsor <i>Opsional</i></span
                                >
                                <textarea
                                    v-model="form.description"
                                    rows="4"
                                    placeholder="Ceritakan secara singkat hubungan sponsor dengan COSMIC"
                                />
                                <small
                                    >Informasi singkat yang dapat dibaca
                                    pengunjung website.</small
                                >
                                <em v-if="form.errors.description">{{
                                    form.errors.description
                                }}</em>
                            </label>
                        </div>
                        <div v-show="tab === 'media'" class="fields">
                            <div class="section-note full">
                                <strong>Logo Sponsor</strong>
                                <p>
                                    Pilih satu logo utama dari Media Library.
                                    Logo ini digunakan pada kartu sponsor dan
                                    lokasi penayangan di website.
                                </p>
                            </div>
                            <div class="field-block full media-picker-group">
                                <span class="field-label"
                                    >Logo Sponsor <i>Opsional</i></span
                                >
                                <MediaPicker
                                    v-model="form.logo_media_id"
                                    value-mode="id"
                                    folder="all"
                                    upload-folder="sponsors"
                                    media-type="image"
                                    label="Pilih dari Media Library"
                                    :preview-url="selectedLogo?.url || ''"
                                    :can-browse="can.browse_media"
                                    :can-upload="can.upload_media"
                                    @selected="selectedLogo = $event"
                                    @update:model-value="handleLogoValue"
                                />
                                <small
                                    >Pilih logo yang sudah tersedia atau unggah
                                    logo baru. Logo yang diunggah otomatis
                                    disimpan ke Media Library.</small
                                >
                                <em v-if="form.errors.logo_media_id">{{
                                    form.errors.logo_media_id
                                }}</em>
                            </div>
                        </div>
                        <div v-show="tab === 'contact'" class="fields">
                            <div class="section-note full">
                                <strong>Tautan Publik</strong>
                                <p>
                                    Tautan berikut boleh dikosongkan. Jika
                                    diisi, tautan dapat digunakan pada profil
                                    sponsor di website.
                                </p>
                            </div>
                            <label class="field-block">
                                <span class="field-label"
                                    >Website Sponsor <i>Opsional</i></span
                                >
                                <input
                                    v-model="form.website_url"
                                    type="url"
                                    placeholder="https://namasponsor.com"
                                />
                                <small>Masukkan alamat website lengkap.</small>
                                <em v-if="form.errors.website_url">{{
                                    form.errors.website_url
                                }}</em>
                            </label>
                            <label class="field-block">
                                <span class="field-label"
                                    >Instagram Sponsor <i>Opsional</i></span
                                >
                                <input
                                    v-model="form.instagram_url"
                                    type="url"
                                    placeholder="https://instagram.com/namasponsor"
                                />
                                <small
                                    >Gunakan tautan profil Instagram
                                    lengkap.</small
                                >
                                <em v-if="form.errors.instagram_url">{{
                                    form.errors.instagram_url
                                }}</em>
                            </label>
                            <div class="section-note internal full">
                                <strong>Kontak Internal</strong>
                                <p>
                                    Data PIC bersifat opsional, hanya untuk
                                    kebutuhan administrasi, dan tidak
                                    ditampilkan di website.
                                </p>
                            </div>
                            <label class="field-block">
                                <span class="field-label"
                                    >Nama PIC <i>Opsional</i></span
                                >
                                <input
                                    v-model="form.contact_name"
                                    placeholder="Nama kontak sponsor"
                                />
                                <small
                                    >Nama orang yang dapat dihubungi terkait
                                    kerja sama.</small
                                >
                                <em v-if="form.errors.contact_name">{{
                                    form.errors.contact_name
                                }}</em>
                            </label>
                            <label class="field-block">
                                <span class="field-label"
                                    >Email PIC <i>Opsional</i></span
                                >
                                <input
                                    v-model="form.contact_email"
                                    type="email"
                                    placeholder="pic@namasponsor.com"
                                />
                                <small
                                    >Email hanya divalidasi jika kolom ini
                                    diisi.</small
                                >
                                <em v-if="form.errors.contact_email">{{
                                    form.errors.contact_email
                                }}</em>
                            </label>
                        </div>
                        <footer>
                            <button
                                type="button"
                                class="cancel"
                                @click="panel = null"
                            >
                                Batal</button
                            ><button :disabled="form.processing">
                                Simpan Sponsor
                            </button>
                        </footer>
                    </form>
                    <section v-else-if="panel === 'terms'" class="term-manager">
                        <div class="term-manager-intro">
                            <div>
                                <strong>Periode kerja sama</strong>
                                <p>
                                    Atur masa aktif dan status penayangan
                                    sponsor pada section Official Partners di
                                    beranda.
                                </p>
                            </div>
                            <button type="button" @click="openTerm(selected)">
                                + Tambah Periode
                            </button>
                        </div>
                        <div
                            v-if="selected?.partnerships?.length"
                            class="term-list"
                        >
                            <article
                                v-for="partnership in selected.partnerships"
                                :key="partnership.id"
                                class="term-card"
                            >
                                <header>
                                    <div>
                                        <small>Nama Kerja Sama</small>
                                        <h3>
                                            {{
                                                partnership.title ||
                                                tierLabel(partnership.tier)
                                            }}
                                        </h3>
                                    </div>
                                    <span
                                        class="term-status"
                                        :class="`is-${partnership.status}`"
                                        >{{
                                            partnershipStatusLabel(
                                                partnership.status,
                                            )
                                        }}</span
                                    >
                                </header>
                                <div class="term-card-details">
                                    <span>
                                        <small>Tingkat Kerja Sama</small>
                                        <strong>{{
                                            tierLabel(partnership.tier)
                                        }}</strong>
                                    </span>
                                    <span>
                                        <small>Periode Berlaku</small>
                                        <strong>
                                            {{
                                                formatDate(
                                                    partnership.starts_at,
                                                )
                                            }}
                                            —
                                            {{
                                                partnership.ends_at
                                                    ? formatDate(
                                                          partnership.ends_at,
                                                      )
                                                    : "Tanpa batas"
                                            }}
                                        </strong>
                                    </span>
                                    <span>
                                        <small>Ditampilkan Pada</small>
                                        <strong
                                            >Beranda · Official Partners</strong
                                        >
                                    </span>
                                </div>
                                <footer>
                                    <span>{{
                                        partnership.season_name ||
                                        "Tidak terkait musim tertentu"
                                    }}</span>
                                    <button
                                        type="button"
                                        @click="openTerm(selected, partnership)"
                                    >
                                        Edit Periode →
                                    </button>
                                </footer>
                            </article>
                        </div>
                        <div v-else class="term-manager-empty">
                            <strong>Belum ada periode kerja sama</strong>
                            <p>
                                Tambahkan periode pertama untuk menentukan masa
                                aktif dan lokasi penayangan sponsor.
                            </p>
                        </div>
                    </section>
                    <form v-else @submit.prevent="saveTerm">
                        <div class="term-form-note">
                            <strong>Informasi Penayangan</strong>
                            <p>
                                Kerja sama otomatis diarahkan ke section
                                <b>Official Partners</b> di beranda. Profil
                                sponsor, status kerja sama, dan periode berlaku
                                harus aktif agar sponsor tampil kepada publik.
                            </p>
                        </div>
                        <div class="fields">
                            <label class="field-block">
                                <span class="field-label"
                                    >Tingkat Kerja Sama <b>Wajib</b></span
                                >
                                <select v-model="term.tier">
                                    <option value="principal">
                                        Principal Partner
                                    </option>
                                    <option value="official">
                                        Official Partner
                                    </option>
                                    <option value="supporting">
                                        Supporting Partner
                                    </option>
                                    <option value="community">
                                        Community Partner
                                    </option>
                                </select>
                                <small
                                    >Menentukan tingkatan hubungan sponsor
                                    dengan COSMIC.</small
                                >
                                <em v-if="term.errors.tier">{{
                                    term.errors.tier
                                }}</em>
                            </label>
                            <label class="field-block">
                                <span class="field-label"
                                    >Nama Kerja Sama <i>Opsional</i></span
                                >
                                <input
                                    v-model="term.title"
                                    placeholder="Contoh: Official Apparel Partner"
                                />
                                <small
                                    >Nama publik kerja sama. Jika kosong,
                                    tingkat kerja sama akan digunakan.</small
                                >
                                <em v-if="term.errors.title">{{
                                    term.errors.title
                                }}</em>
                            </label>
                            <label class="field-block">
                                <span class="field-label"
                                    >Tanggal Mulai <b>Wajib</b></span
                                >
                                <input
                                    v-model="term.starts_at"
                                    required
                                    type="date"
                                />
                                <small
                                    >Tanggal pertama sponsor boleh ditampilkan
                                    di beranda.</small
                                >
                                <em v-if="term.errors.starts_at">{{
                                    term.errors.starts_at
                                }}</em>
                            </label>
                            <label class="field-block">
                                <span class="field-label"
                                    >Tanggal Selesai <i>Opsional</i></span
                                >
                                <input v-model="term.ends_at" type="date" />
                                <small
                                    >Kosongkan jika kerja sama belum memiliki
                                    batas akhir.</small
                                >
                                <em v-if="term.errors.ends_at">{{
                                    term.errors.ends_at
                                }}</em>
                            </label>
                            <label class="field-block">
                                <span class="field-label"
                                    >Musim Terkait <i>Opsional</i></span
                                >
                                <select v-model="term.season_id">
                                    <option :value="null">
                                        Tidak terkait musim tertentu
                                    </option>
                                    <option
                                        v-for="s in seasons"
                                        :key="s.id"
                                        :value="s.id"
                                    >
                                        {{ s.name }}
                                    </option>
                                </select>
                                <small
                                    >Pilih musim jika kerja sama hanya berlaku
                                    untuk kompetisi tertentu.</small
                                >
                                <em v-if="term.errors.season_id">{{
                                    term.errors.season_id
                                }}</em>
                            </label>
                            <label class="field-block">
                                <span class="field-label"
                                    >Status Kerja Sama <b>Wajib</b></span
                                >
                                <select v-model="term.status">
                                    <option value="draft">Draft</option>
                                    <option value="scheduled">Terjadwal</option>
                                    <option value="active">Aktif</option>
                                    <option value="expired">Berakhir</option>
                                    <option value="archived">Diarsipkan</option>
                                </select>
                                <small
                                    >Hanya status Aktif dalam periode berlaku
                                    yang tampil di beranda.</small
                                >
                                <em v-if="term.errors.status">{{
                                    term.errors.status
                                }}</em>
                            </label>
                            <div class="fixed-placement full">
                                <span>Lokasi Penayangan</span>
                                <strong>Beranda · Official Partners</strong>
                                <small
                                    >Ditetapkan otomatis dan tidak memerlukan
                                    pengaturan tambahan.</small
                                >
                            </div>
                            <label class="field-block full">
                                <span class="field-label"
                                    >Catatan Internal <i>Opsional</i></span
                                >
                                <textarea
                                    v-model="term.internal_notes"
                                    rows="3"
                                    placeholder="Catatan kontrak atau informasi internal lainnya"
                                />
                                <small
                                    >Hanya tersedia di dashboard dan tidak
                                    ditampilkan kepada publik.</small
                                >
                                <em v-if="term.errors.internal_notes">{{
                                    term.errors.internal_notes
                                }}</em>
                            </label>
                        </div>
                        <footer>
                            <button
                                type="button"
                                class="cancel"
                                @click="panel = 'terms'"
                            >
                                Kembali</button
                            ><button :disabled="term.processing">
                                Simpan Kerja Sama
                            </button>
                        </footer>
                    </form>
                </aside>
            </div>
            <ConfirmActionModal
                :open="Boolean(archiveTarget)"
                title="Arsipkan sponsor?"
                :item-name="archiveTarget?.name || ''"
                description="Profil sponsor dan seluruh periode kerja samanya akan diarsipkan."
                note="Sponsor tidak lagi tampil pada website, tetapi datanya tetap tersimpan."
                confirm-label="Arsipkan Sponsor"
                processing-label="Mengarsipkan..."
                variant="warning"
                @confirm="confirmArchive"
                @cancel="archiveTarget = null"
            /></div
    ></AuthenticatedLayout>
</template>

<style scoped>
.page {
    max-width: 1450px;
    margin: auto;
}
.notice {
    margin-bottom: 18px;
    padding: 12px 15px;
    border: 1px solid #277aac;
    color: #8dd3ff;
    background: #071a29;
}
.hero {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 30px;
    margin-bottom: 26px;
    padding: 28px;
    border: 1px solid rgba(69, 183, 255, 0.15);
    background: linear-gradient(
        115deg,
        rgba(69, 183, 255, 0.11),
        transparent 55%
    );
}
.hero span,
.eyebrow span,
.drawer header span {
    color: #45b7ff;
    font: 800 0.57rem var(--font-display);
    letter-spacing: 0.18em;
    text-transform: uppercase;
}
.hero h1 {
    margin: 8px 0;
    color: white;
    font: 900 clamp(2.1rem, 4vw, 4rem) / 0.87 var(--font-display);
    text-transform: uppercase;
}
.hero h1 b {
    color: #45b7ff;
}
.hero p {
    max-width: 650px;
    color: #91a0ac;
}
.hero button,
.filters button,
.drawer footer button {
    padding: 13px 18px;
    border: 0;
    border-radius: 4px;
    background: #45b7ff;
    font: 800 0.62rem var(--font-display);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    cursor: pointer;
}
.stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 18px;
}
.stats article {
    padding: 18px;
    border: 1px solid rgba(175, 218, 255, 0.13);
    background: #08111c;
}
.stats small {
    display: block;
    color: #71818e;
    font: 700 0.52rem var(--font-display);
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.stats strong {
    display: block;
    margin-top: 8px;
    color: white;
    font: 900 1.8rem var(--font-display);
}
.filters {
    display: grid;
    grid-template-columns: 1fr 190px 170px auto;
    gap: 8px;
    margin-bottom: 18px;
}
.filters input,
.filters select,
.fields input,
.fields select,
.fields textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 12px;
    border: 1px solid rgba(175, 218, 255, 0.17);
    border-radius: 3px;
    color: white;
    background: #07111c;
}
.grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}
.card {
    display: grid;
    grid-template-columns: 120px 1fr;
    border: 1px solid rgba(175, 218, 255, 0.14);
    background: #07101a;
}
.logo {
    display: grid;
    min-height: 210px;
    place-items: center;
    border-right: 1px solid rgba(175, 218, 255, 0.12);
    background: #0a1522;
}
.logo img {
    max-width: 90px;
    max-height: 90px;
    object-fit: contain;
}
.logo span {
    color: #45b7ff;
    font: 900 2rem var(--font-display);
}
.card-copy {
    padding: 20px;
}
.eyebrow {
    display: flex;
    justify-content: space-between;
}
.eyebrow i {
    padding: 4px 7px;
    border-radius: 20px;
    color: #7e8d99;
    background: #111f2b;
    font: 700 0.48rem var(--font-display);
    text-transform: uppercase;
}
.eyebrow i.active {
    color: #74d0ff;
}
.card h2 {
    margin: 12px 0 7px;
    color: white;
    font: 900 1.65rem var(--font-display);
    text-transform: uppercase;
}
.card p,
.empty {
    color: #81919d;
    font-size: 0.72rem;
}
.terms {
    display: grid;
    gap: 5px;
    margin-top: 15px;
}
.terms button {
    display: grid;
    grid-template-columns: 85px 1fr auto;
    gap: 8px;
    padding: 9px;
    border: 1px solid rgba(255, 255, 255, 0.07);
    color: #aab6bf;
    background: #091522;
    text-align: left;
    cursor: pointer;
}
.terms b,
.terms i {
    color: #45b7ff;
    font: 700 0.53rem var(--font-display);
    text-transform: uppercase;
}
.card > footer {
    grid-column: 1/-1;
    display: flex;
    gap: 7px;
    padding: 10px;
    border-top: 1px solid rgba(175, 218, 255, 0.1);
}
.card > footer button {
    border: 0;
    color: #8ed5ff;
    background: transparent;
    font: 700 0.54rem var(--font-display);
    text-transform: uppercase;
    cursor: pointer;
}
.card > footer .muted {
    margin-left: auto;
    color: #697985;
}
.overlay {
    position: fixed;
    z-index: 80;
    inset: 0;
    background: rgba(0, 4, 8, 0.78);
}
.drawer {
    position: absolute;
    inset: 0 0 0 auto;
    width: min(650px, 100%);
    overflow: auto;
    border-left: 1px solid rgba(69, 183, 255, 0.25);
    background: #07101a;
}
.drawer > header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 25px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.drawer h2 {
    margin: 5px 0 0;
    color: white;
    font: 900 1.6rem var(--font-display);
    text-transform: uppercase;
}
.drawer > header button {
    border: 0;
    color: white;
    background: transparent;
    font-size: 2rem;
}
.drawer form {
    padding: 25px;
}
.drawer nav {
    display: flex;
    margin-bottom: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.drawer nav button {
    padding: 12px;
    border: 0;
    color: #71818e;
    background: none;
}
.drawer nav button.on {
    color: #45b7ff;
    border-bottom: 2px solid #45b7ff;
}
.fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}
.fields label {
    color: #82919d;
    font: 700 0.56rem var(--font-display);
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.field-block {
    display: flex;
    min-width: 0;
    flex-direction: column;
}
.field-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    color: #c8d5de;
}
.field-label b,
.field-label i {
    font: 700 0.46rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.field-label b {
    color: #45b7ff;
}
.field-label i {
    color: #657887;
}
.field-block > small {
    margin-top: 7px;
    color: #657987;
    font: 400 0.64rem/1.45 inherit;
    letter-spacing: 0;
    text-transform: none;
}
.field-block > em {
    margin-top: 6px;
    color: #ff8e96;
    font: 600 0.63rem/1.4 inherit;
    letter-spacing: 0;
    text-transform: none;
}
.section-note {
    padding: 14px 15px;
    border-left: 2px solid #45b7ff;
    background: rgba(69, 183, 255, 0.06);
}
.section-note strong {
    color: #ddecf5;
    font: 800 0.62rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.section-note p {
    margin: 6px 0 0;
    color: #718593;
    font-size: 0.67rem;
    line-height: 1.5;
}
.section-note.internal {
    margin-top: 5px;
    border-left-color: #7891a2;
    background: rgba(120, 145, 162, 0.06);
}
.fields input,
.fields select,
.fields textarea {
    margin-top: 7px;
    font-family: inherit;
    text-transform: none;
}
.full {
    grid-column: 1/-1;
}
.preview {
    max-width: 150px;
    max-height: 90px;
    object-fit: contain;
}
.logo-preview {
    display: grid;
    justify-items: center;
    gap: 12px;
    padding: 18px;
    border: 1px solid rgba(175, 218, 255, 0.13);
    background: #091522;
}
.logo-preview span {
    color: #6f8290;
    font: 700 0.5rem var(--font-display);
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.privacy {
    padding: 12px;
    color: #7f909e;
    background: #0a1724;
    font-size: 0.7rem;
}
fieldset {
    border: 1px solid rgba(175, 218, 255, 0.14);
}
legend {
    color: #45b7ff;
}
.check {
    display: flex !important;
    align-items: center;
    gap: 8px;
    margin: 10px;
    text-transform: none !important;
}
.check input {
    position: relative;
    width: 18px !important;
    height: 18px !important;
    flex: 0 0 18px;
    margin: 0 !important;
    padding: 0 !important;
    appearance: none;
    -webkit-appearance: none;
    border: 1px solid rgba(175, 218, 255, 0.42) !important;
    border-radius: 3px;
    background: #050c14 !important;
    cursor: pointer;
}
.check input:hover {
    border-color: #45b7ff !important;
    background: rgba(69, 183, 255, 0.08) !important;
}
.check input:checked {
    border-color: #45b7ff !important;
    background: #45b7ff !important;
}
.check input:checked::after {
    content: "✓";
    position: absolute;
    inset: -1px 0 0;
    display: grid;
    place-items: center;
    color: #03101b;
    font:
        bold 13px/1 Arial,
        sans-serif;
}
.check input:focus-visible {
    outline: 2px solid rgba(69, 183, 255, 0.4);
    outline-offset: 2px;
}
.drawer form > footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 25px;
}
.drawer footer .cancel {
    color: #93a1ad;
    background: #101b25;
}
.errors {
    margin-top: 15px;
    color: #ff8e96;
    font-size: 0.72rem;
}

/* Filter yang terbaca sebagai formulir, bukan deretan kolom tanpa konteks. */
.filters {
    grid-template-columns:
        minmax(260px, 1.35fr) minmax(190px, 0.8fr) minmax(170px, 0.7fr)
        auto;
    align-items: start;
    padding: 18px;
    border: 1px solid rgba(175, 218, 255, 0.13);
    background: #08111c;
}
.filter-field {
    display: grid;
    gap: 7px;
}
.filter-field > span {
    color: #d7e4ed;
    font: 800 0.58rem var(--font-display);
    letter-spacing: 0.11em;
    text-transform: uppercase;
}
.filter-field > small {
    color: #647786;
    font-size: 0.65rem;
    line-height: 1.35;
}
.filter-actions {
    display: flex;
    align-items: stretch;
    gap: 7px;
    padding-top: 19px;
}
.filter-actions button {
    min-height: 43px;
    white-space: nowrap;
}
.filter-actions .reset {
    border: 1px solid rgba(175, 218, 255, 0.2);
    color: #9cabb6;
    background: transparent;
}
.filter-result {
    grid-column: 1 / -1;
    margin: 3px 0 0;
    color: #71818e;
    font-size: 0.68rem;
}

/* Kartu sponsor: identitas, status publik, periode, lalu aksi. */
.card {
    display: flex;
    min-width: 0;
    flex-direction: column;
}
.card-head {
    display: grid;
    grid-template-columns: 112px minmax(0, 1fr);
    min-height: 142px;
}
.logo {
    min-height: 142px;
}
.identity {
    min-width: 0;
    padding: 19px 20px;
}
.identity p {
    display: -webkit-box;
    overflow: hidden;
    margin: 0;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    line-height: 1.5;
}
.visibility {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin: 0 16px 10px;
    padding: 12px 13px;
    border: 1px solid rgba(175, 218, 255, 0.12);
    background: rgba(8, 22, 34, 0.75);
}
.visibility-dot {
    width: 7px;
    height: 7px;
    flex: 0 0 7px;
    margin-top: 4px;
    border-radius: 50%;
    background: #71818e;
}
.visibility strong,
.visibility small {
    display: block;
}
.visibility strong {
    color: #dce8ef;
    font: 800 0.6rem var(--font-display);
    letter-spacing: 0.06em;
    text-transform: uppercase;
}
.visibility small {
    margin-top: 4px;
    color: #758897;
    font-size: 0.64rem;
}
.visibility-public .visibility-dot {
    background: #45b7ff;
    box-shadow: 0 0 10px rgba(69, 183, 255, 0.75);
}
.visibility-public strong,
.visibility-scheduled strong {
    color: #77ccff;
}
.visibility-expired .visibility-dot,
.visibility-archived .visibility-dot {
    background: #63717c;
}
.term-summary {
    display: grid;
    grid-template-columns: 1fr 1.35fr 0.9fr;
    gap: 13px;
    width: auto;
    margin: 0 16px 14px;
    padding: 14px;
    border: 1px solid rgba(175, 218, 255, 0.13);
    color: inherit;
    background: #091522;
    text-align: left;
    cursor: pointer;
}
.term-summary:hover {
    border-color: rgba(69, 183, 255, 0.48);
    background: #0b1b2a;
}
.term-summary small,
.term-summary strong {
    display: block;
}
.term-summary small,
.term-list small {
    margin-bottom: 5px;
    color: #617786;
    font: 700 0.47rem var(--font-display);
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.term-summary strong,
.term-list strong {
    color: #dce7ef;
    font-size: 0.69rem;
    line-height: 1.4;
}
.no-term {
    display: grid;
    gap: 5px;
    margin: 0 16px 14px;
    padding: 14px;
    border: 1px dashed rgba(175, 218, 255, 0.17);
    color: #748694;
    font-size: 0.67rem;
}
.no-term strong {
    color: #b7c5cf;
    font: 800 0.58rem var(--font-display);
    text-transform: uppercase;
}
.other-terms {
    margin: -4px 16px 14px !important;
    color: #6dbce9 !important;
    font-size: 0.63rem !important;
}
.card > footer {
    margin-top: auto;
    padding: 12px 15px;
}
.card > footer button {
    padding: 8px 10px;
    border: 1px solid transparent;
}
.card > footer .primary-action {
    border-color: rgba(69, 183, 255, 0.32);
    color: #b6e2ff;
    background: rgba(69, 183, 255, 0.08);
}

.term-manager {
    padding: 25px;
}
.term-manager-intro {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 18px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(175, 218, 255, 0.12);
}
.term-manager-intro strong,
.term-manager-empty strong {
    color: #edf6fb;
    font: 800 0.72rem var(--font-display);
    text-transform: uppercase;
}
.term-manager-intro p,
.term-manager-empty p {
    margin: 6px 0 0;
    color: #768997;
    font-size: 0.7rem;
    line-height: 1.5;
}
.term-manager-intro button {
    flex: 0 0 auto;
    padding: 11px 14px;
    border: 0;
    border-radius: 3px;
    color: #03101b;
    background: #45b7ff;
    font: 800 0.55rem var(--font-display);
    text-transform: uppercase;
    cursor: pointer;
}
.term-list {
    display: grid;
    gap: 9px;
    margin-top: 18px;
}
.term-card {
    border: 1px solid rgba(175, 218, 255, 0.14);
    background: #091522;
}
.term-card > header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 16px 17px;
    border-bottom: 1px solid rgba(175, 218, 255, 0.1);
}
.term-card h3 {
    margin: 5px 0 0;
    color: #f1f7fb;
    font: 850 1rem var(--font-display);
    text-transform: uppercase;
}
.term-card small {
    display: block;
    color: #617786;
    font: 700 0.47rem var(--font-display);
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.term-status {
    padding: 6px 9px;
    border: 1px solid rgba(175, 218, 255, 0.2);
    color: #8da0ae;
    font: 800 0.48rem var(--font-display);
    text-transform: uppercase;
}
.term-status.is-active,
.term-status.is-scheduled {
    border-color: rgba(69, 183, 255, 0.42);
    color: #72cbff;
    background: rgba(69, 183, 255, 0.08);
}
.term-card-details {
    display: grid;
    grid-template-columns: 1fr 1.35fr 1.25fr;
    gap: 18px;
    padding: 16px 17px;
}
.term-card-details strong {
    display: block;
    margin-top: 6px;
    color: #cbd9e2;
    font-size: 0.68rem;
    line-height: 1.45;
}
.term-card > footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 11px 17px;
    border-top: 1px solid rgba(175, 218, 255, 0.1);
    color: #6f8290;
    font-size: 0.62rem;
}
.term-card > footer button {
    padding: 7px 10px;
    border: 1px solid rgba(69, 183, 255, 0.3);
    color: #8ed5ff;
    background: rgba(69, 183, 255, 0.06);
    font: 800 0.5rem var(--font-display);
    text-transform: uppercase;
    cursor: pointer;
}
.term-card > footer button:hover {
    border-color: #45b7ff;
    background: rgba(69, 183, 255, 0.12);
}
.term-form-note {
    margin-bottom: 20px;
    padding: 15px 16px;
    border: 1px solid rgba(69, 183, 255, 0.24);
    border-left: 3px solid #45b7ff;
    background: rgba(69, 183, 255, 0.06);
}
.term-form-note strong,
.fixed-placement > span {
    color: #8dd5ff;
    font: 800 0.55rem var(--font-display);
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.term-form-note p {
    margin: 7px 0 0;
    color: #8194a2;
    font-size: 0.68rem;
    line-height: 1.55;
}
.term-form-note b {
    color: #d7ecf8;
}
.fixed-placement {
    display: grid;
    gap: 6px;
    padding: 15px;
    border: 1px solid rgba(175, 218, 255, 0.14);
    background: #091522;
}
.fixed-placement strong {
    color: #eef7fc;
    font-size: 0.78rem;
}
.fixed-placement small {
    color: #6f8391;
    font-size: 0.64rem;
}
.term-manager-empty {
    margin-top: 18px;
    padding: 24px;
    border: 1px dashed rgba(175, 218, 255, 0.17);
    text-align: center;
}
.empty-results {
    display: grid;
    justify-items: center;
    gap: 8px;
    padding: 44px 24px;
    border: 1px dashed rgba(175, 218, 255, 0.18);
    color: #768997;
    text-align: center;
}
.empty-results strong {
    color: #edf6fb;
    font: 850 1rem var(--font-display);
    text-transform: uppercase;
}
.empty-results p {
    margin: 0;
    font-size: 0.72rem;
}
.empty-results button {
    margin-top: 8px;
    padding: 10px 14px;
    border: 1px solid rgba(69, 183, 255, 0.35);
    color: #8bd4ff;
    background: rgba(69, 183, 255, 0.08);
    font: 800 0.55rem var(--font-display);
    text-transform: uppercase;
    cursor: pointer;
}
@media (max-width: 900px) {
    .grid {
        grid-template-columns: 1fr;
    }
    .stats {
        grid-template-columns: repeat(2, 1fr);
    }
    .filters {
        grid-template-columns: 1fr 1fr;
    }
    .filter-search,
    .filter-result {
        grid-column: 1 / -1;
    }
    .filter-actions {
        padding-top: 19px;
    }
}
@media (max-width: 600px) {
    .hero {
        align-items: flex-start;
        flex-direction: column;
    }
    .filters,
    .fields {
        grid-template-columns: 1fr;
    }
    .filter-search,
    .filter-result {
        grid-column: auto;
    }
    .filter-actions {
        padding-top: 0;
    }
    .full {
        grid-column: auto;
    }
    .stats {
        grid-template-columns: 1fr 1fr;
    }
    .card {
        display: flex;
    }
    .card-head {
        grid-template-columns: 86px minmax(0, 1fr);
    }
    .logo {
        min-height: 132px;
    }
    .term-summary,
    .term-card-details {
        grid-template-columns: 1fr;
    }
    .term-card > footer {
        align-items: flex-start;
        flex-direction: column;
    }
    .card > footer {
        flex-wrap: wrap;
    }
}
</style>
