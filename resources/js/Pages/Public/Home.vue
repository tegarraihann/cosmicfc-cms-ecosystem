<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import HeroSection from "@/Components/Public/HeroSection.vue";
import NextMatchSection from "@/Components/Public/NextMatchSection.vue";
import MatchCenterSection from "@/Components/Public/MatchCenterSection.vue";
import SquadSection from "@/Components/Public/SquadSection.vue";
import ClubCultureSection from "@/Components/Public/ClubCultureSection.vue";
import AboutCosmicSection from "@/Components/Public/AboutCosmicSection.vue";
import SiteFooter from "@/Components/Public/SiteFooter.vue";
import SiteNavbar from "@/Components/Public/SiteNavbar.vue";
import LiveMatchSection from "@/Components/Public/LiveMatchSection.vue";
const props = defineProps({
    layoutSections: { type: Array, default: () => [] },
    isPreview: Boolean,
    playerData: { type: Object, default: () => ({}) },
    staffData: { type: Object, default: () => ({ people: [], founder: null }) },
    matchData: {
        type: Object,
        default: () => ({ live: null, upcoming: [], results: [], next: null }),
    },
    sponsorData: {
        type: Object,
        default: () => ({ placements: {}, partners: [] }),
    },
});
const page = usePage();
const site = computed(() => page.props.site ?? {});
const formatFoundedDate = (value, fallbackYear) => {
    if (!value) return fallbackYear || "Belum diisi";

    const match = String(value)
        .slice(0, 10)
        .match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return value;

    const months = [
        "Januari",
        "Februari",
        "Maret",
        "April",
        "Mei",
        "Juni",
        "Juli",
        "Agustus",
        "September",
        "Oktober",
        "November",
        "Desember",
    ];

    return `${Number(match[3])} ${months[Number(match[2]) - 1]} ${match[1]}`;
};
const heroProfile = computed(() => ({
    established: `Est. ${site.value.identity?.foundedYear || 2026}`,
}));
const aboutProfile = computed(() => ({
    city: [site.value.location?.city, site.value.location?.country]
        .filter(Boolean)
        .join(", "),
    identities: [
        {
            label: "Resmi Berdiri",
            value: formatFoundedDate(
                site.value.founded_at,
                site.value.identity?.foundedYear,
            ),
        },
        {
            label: "Founder",
            value:
                props.staffData.founder?.name ||
                site.value.contact?.person ||
                "Belum diisi",
            placeholder:
                !props.staffData.founder?.name && !site.value.contact?.person,
        },
        {
            label: "Home Base",
            value:
                site.value.location?.homeBase ||
                site.value.location?.city ||
                "Belum diisi",
        },
    ],
}));
const nextMatch = computed(() =>
    props.matchData.next
        ? {
              homeTeam: props.matchData.next.home.name,
              homeLogo: props.matchData.next.home.logo,
              awayTeam: props.matchData.next.away.name,
              awayLogo: props.matchData.next.away.logo,
              awayCode: props.matchData.next.away.shortName,
              date: props.matchData.next.date,
              time: props.matchData.next.time,
              venue: props.matchData.next.venue,
              city: props.matchData.next.address,
              detailHref: props.matchData.next.href,
              backgroundImage: props.matchData.next.cover,
          }
        : {},
);
const matchCenter = computed(() => ({
    upcoming: (props.matchData.upcoming || []).slice(0, 3).map((m) => ({
        day: m.date?.slice(0, 2),
        month: m.date?.slice(3, 6),
        opponent: m.away.name,
        code: m.away.shortName,
        meta: `${m.time} · ${m.venue}`,
        href: m.href,
    })),
    results: (props.matchData.results || []).slice(0, 3).map((m) => ({
        homeCode: m.home.shortName,
        awayCode: m.away.shortName,
        opponent: m.away.name,
        meta: `${m.date} · ${m.venue}`,
        score: m.score,
    })),
}));
</script>

<template>
    <Head :title="site.seo?.default_title || 'Beranda'">
        <meta
            v-if="site.seo?.default_description"
            head-key="description"
            name="description"
            :content="site.seo.default_description"
        />
        <meta
            v-if="site.seo?.keywords"
            head-key="keywords"
            name="keywords"
            :content="site.seo.keywords"
        />
        <link
            v-if="site.branding?.favicon"
            head-key="favicon"
            rel="icon"
            :href="site.branding.favicon"
        />
    </Head>
    <main>
        <div v-if="isPreview" class="preview-banner">
            MODE PREVIEW DRAFT · Perubahan belum dipublikasikan
        </div>
        <template v-for="section in layoutSections" :key="section.key">
            <template v-if="section.key === 'home.hero'">
                <HeroSection v-bind="{ ...heroProfile, ...section.content }">
                    <template #navigation>
                        <SiteNavbar />
                    </template>
                </HeroSection>
                <LiveMatchSection
                    v-if="matchData.live"
                    :match="matchData.live"
                    compact
                />
            </template>
            <NextMatchSection
                v-else-if="section.key === 'home.next_match'"
                v-bind="{ ...section.content, ...nextMatch }"
            />
            <MatchCenterSection
                v-else-if="section.key === 'home.match_center'"
                v-bind="{ ...section.content, ...matchCenter }"
            />
            <SquadSection
                v-else-if="section.key === 'home.squad'"
                v-bind="{ players: playerData.featured || [], ...section.content }"
            />
            <ClubCultureSection
                v-else-if="section.key === 'home.club_culture'"
                v-bind="section.content"
            />
            <AboutCosmicSection
                v-else-if="section.key === 'home.about'"
                v-bind="{
                    ...aboutProfile,
                    ...section.content,
                    people: staffData.people,
                    partners:
                        sponsorData.placements?.['home.official_partners'] ||
                        [],
                }"
            />
        </template>
        <SiteFooter />
    </main>
</template>
<style scoped>
.preview-banner {
    position: fixed;
    z-index: 9999;
    right: 16px;
    bottom: 16px;
    padding: 10px 14px;
    border: 1px solid #45b7ff;
    color: #fff;
    background: #06111eef;
    font: 800 0.6rem var(--font-display);
    letter-spacing: 0.1em;
}
</style>
