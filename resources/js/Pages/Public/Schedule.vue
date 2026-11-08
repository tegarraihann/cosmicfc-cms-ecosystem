<script setup>
import { computed } from "vue";
import PageHeroSection from "@/Components/Public/PageHeroSection.vue";
import SiteNavbar from "@/Components/Public/SiteNavbar.vue";
import ScheduleNextMatchSection from "@/Components/Public/ScheduleNextMatchSection.vue";
import MatchScheduleSection from "@/Components/Public/MatchScheduleSection.vue";
import MatchResultsSection from "@/Components/Public/MatchResultsSection.vue";
import CompactStandingsSection from "@/Components/Public/CompactStandingsSection.vue";
import SiteFooter from "@/Components/Public/SiteFooter.vue";
import LiveMatchSection from "@/Components/Public/LiveMatchSection.vue";
const props = defineProps({
    layoutSections: { type: Array, default: () => [] },
    isPreview: Boolean,
    matchData: {
        type: Object,
        default: () => ({ live: null, next: null, upcoming: [], results: [] }),
    },
    standingsData: { type: Object, default: () => ({ compact: [] }) },
});
const dynamicNext = computed(() =>
    props.matchData.next
        ? {
              ...props.matchData.next,
              image:
                  props.matchData.next.cover || "",
              display: props.matchData.next.score || "VS",
              city: props.matchData.next.address,
          }
        : {},
);
const scheduleGroups = computed(() => {
    const map = {};
    for (const m of [
        ...(props.matchData.live ? [props.matchData.live] : []),
        ...(props.matchData.results || []),
        ...(props.matchData.upcoming || []),
    ]) {
        const d = new Date(m.kickoff),
            key = d.toLocaleString("en", { month: "long" }).toLowerCase(),
            label = d.toLocaleString("id-ID", {
                month: "long",
                year: "numeric",
            });
        map[key] ??= { key, label, matches: [] };
        map[key].matches.push({
            slug: m.slug,
            day: String(d.getDate()).padStart(2, "0"),
            weekday: d.toLocaleString("id-ID", { weekday: "short" }),
            status: m.status === "scheduled" ? "upcoming" : m.status,
            competition: m.competition,
            type: m.type,
            matchday: String(m.matchday || ""),
            home: m.home.name,
            away: m.away.name,
            display: m.score || m.time?.replace(" WIB", ""),
            venue: m.venue,
            city: m.address,
        });
    }
    return Object.values(map);
});
const resultProps = computed(() => {
    const rows = (props.matchData.results || []).map((m) => ({
        slug: m.slug,
        date: m.date,
        competition: m.competition,
        type: m.type,
        matchday: String(m.matchday || ""),
        home: m.home.name,
        away: m.away.name,
        score: m.score,
        venue: m.venue,
        city: m.address,
        image: m.cover || "",
    }));
    return {
        featured: rows[0] || { slug: '', date: '—', home: '—', away: '—', score: '—', venue: '—', city: '', image: '' },
        results: rows.slice(1),
    };
});
</script>

<template>
    <main>
        <div v-if="isPreview" class="preview-banner">
            MODE PREVIEW DRAFT · Perubahan belum dipublikasikan
        </div>
        <template v-for="section in layoutSections" :key="section.key">
            <template v-if="section.key === 'schedule.hero'">
                <PageHeroSection
                    page-label="Jadwal"
                    title="Jadwal"
                    title-accent="Pertandingan."
                    description="Ikuti setiap langkah COSMIC sepanjang musim. Temukan jadwal pertandingan mendatang, lokasi bermain, dan hasil terbaru tim kami."
                    panel-label="Musim Aktif"
                    panel-value="2026"
                    panel-status="Season in progress"
                    scroll-label="Scroll untuk jadwal"
                    v-bind="section.content"
                >
                    <template #navigation>
                        <SiteNavbar />
                    </template>
                </PageHeroSection>
                <LiveMatchSection v-if="matchData.live" :match="matchData.live" />
            </template>
            <ScheduleNextMatchSection
                v-else-if="section.key === 'schedule.next_match'"
                :match="dynamicNext"
            />
            <MatchScheduleSection
                v-else-if="section.key === 'schedule.matches'"
                v-bind="{ ...section.content, groups: scheduleGroups }"
            />
            <MatchResultsSection
                v-else-if="section.key === 'schedule.results'"
                v-bind="{ ...section.content, ...resultProps }"
            />
            <CompactStandingsSection
                v-else-if="section.key === 'schedule.standings'"
                v-bind="{ ...section.content, clubs: standingsData.compact || [] }"
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
