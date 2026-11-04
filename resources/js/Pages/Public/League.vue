<script setup>
import { Head } from "@inertiajs/vue3";
import { computed } from "vue";
import PageHeroSection from "@/Components/Public/PageHeroSection.vue";
import SiteNavbar from "@/Components/Public/SiteNavbar.vue";
import SeasonOverviewSection from "@/Components/Public/SeasonOverviewSection.vue";
import FullStandingsSection from "@/Components/Public/FullStandingsSection.vue";
import CosmicPerformanceSection from "@/Components/Public/CosmicPerformanceSection.vue";
import TopPlayersSection from "@/Components/Public/TopPlayersSection.vue";
import MatchdayCenterSection from "@/Components/Public/MatchdayCenterSection.vue";
import ParticipatingClubsSection from "@/Components/Public/ParticipatingClubsSection.vue";
import CompetitionRulesSection from "@/Components/Public/CompetitionRulesSection.vue";
import SiteFooter from "@/Components/Public/SiteFooter.vue";
const props = defineProps({
    layoutSections: { type: Array, default: () => [] },
    isPreview: Boolean,
    playerData: { type: Object, default: () => ({}) },
    competitionData: {
        type: Object,
        default: () => ({
            competition: null,
            season: null,
            participants: [],
            regulations: [],
        }),
    },
    matchData: { type: Object, default: () => ({ matchday: [] }) },
    standingsData: {
        type: Object,
        default: () => ({ standings: [], performance: {} }),
    },
});
const matchdayData = computed(() => {
    const map = (m) => ({
        day: m.date?.slice(0, 2),
        month: m.date?.slice(3, 6),
        date: m.kickoff?.slice(0, 10),
        home: m.home.name,
        away: m.away.name,
        homeScore: m.homeScore,
        awayScore: m.awayScore,
        time: m.time?.replace(" WIB", ""),
        venue: m.venue,
        cosmic:
            m.home.shortName === "CSM"
                ? "home"
                : m.away.shortName === "CSM"
                  ? "away"
                  : null,
        href: m.href,
    });
    return {
        results: (props.matchData.matchday || [])
            .filter((m) => ["completed", "walkover"].includes(m.status))
            .map(map),
        fixtures: (props.matchData.matchday || [])
            .filter((m) => ["scheduled", "postponed"].includes(m.status))
            .map(map),
    };
});

const seasonStats = computed(() => {
    if (!props.competitionData.season) return [];
    return [
        {
            value: props.competitionData.participants?.length || 0,
            label: "Tim",
        },
        {
            value: props.competitionData.season.totalMatchdays || 0,
            label: "Matchday",
        },
        {
            value: props.competitionData.season.currentMatchday || 0,
            label: "Saat ini",
        },
    ];
});
const overviewStats = computed(() => [
    {
        value: props.competitionData.participants?.length || 0,
        label: "Tim Peserta",
        icon: "teams",
    },
    {
        value: matchdayData.value.results.length,
        label: "Laga Selesai",
        icon: "ball",
    },
    {
        value: matchdayData.value.results.reduce(
            (total, match) =>
                total +
                Number(match.homeScore || 0) +
                Number(match.awayScore || 0),
            0,
        ),
        label: "Gol Tercipta",
        icon: "trophy",
        accent: true,
    },
    {
        value: props.competitionData.season?.currentMatchday || 0,
        suffix: `/${props.competitionData.season?.totalMatchdays || 0}`,
        label: "Matchday Aktif",
        icon: "calendar",
    },
]);
const participantClubs = computed(() =>
    (props.competitionData.participants || []).map((club) => ({
        ...club,
        code: club.shortName,
        district: club.city,
        cosmic: club.isCosmic,
    })),
);
const competitionRules = computed(() =>
    (props.competitionData.regulations || []).map((rule) => ({
        title: rule.title,
        description: rule.body || rule.description,
    })),
);
</script>

<template>
    <Head title="Liga" />
    <main>
        <div v-if="isPreview" class="preview-banner">
            MODE PREVIEW DRAFT · Perubahan belum dipublikasikan
        </div>
        <template v-for="section in layoutSections" :key="section.key">
            <PageHeroSection
                v-if="section.key === 'league.hero'"
                page-label="Liga"
                title="Pekanbaru"
                title-accent="Mini Soccer League."
                description="Panggung kompetisi tempat tim-tim terbaik Pekanbaru bertemu. Ikuti posisi COSMIC, statistik musim, dan perjalanan menuju puncak klasemen."
                min-height="88svh"
                panel-width="370px"
                v-bind="section.content"
            >
                <template #navigation>
                    <SiteNavbar />
                </template>

                <template #kicker>
                    <div class="league-kicker">Official Competition</div>
                </template>

                <template #panel>
                    <aside class="hero-panel league-card" aria-label="Ringkasan musim aktif">
                        <div class="league-card-label">Musim Aktif</div>
                        <div v-if="competitionData.season" class="league-season">
                            {{ competitionData.season.name }}
                            <small>{{ competitionData.season.format }}</small>
                        </div>
                        <div v-else class="league-empty">Belum ada musim aktif</div>
                        <div v-if="competitionData.season" class="league-stats">
                            <div v-for="stat in seasonStats" :key="stat.label">
                                <strong>{{ stat.value }}</strong>
                                <span>{{ stat.label }}</span>
                            </div>
                        </div>
                    </aside>
                </template>

                <template #bottom>
                    <div class="hero-bottom">
                        <div class="hero-bottom-inner">
                            <div class="season-status">
                                Kompetisi Berlangsung
                            </div>
                            <div class="scroll-note">Jelajahi Liga</div>
                        </div>
                    </div>
                </template>
            </PageHeroSection>
            <SeasonOverviewSection
                v-else-if="section.key === 'league.overview'"
                v-bind="{
                    ...section.content,
                    season: competitionData.season,
                    competition: competitionData.competition,
                    stats: overviewStats,
                    topScorer: playerData.topPlayers?.[0] || null,
                }"
            />
            <FullStandingsSection
                v-else-if="section.key === 'league.standings'"
                v-bind="{
                    ...section.content,
                    standings: standingsData.standings || [],
                }"
            />
            <CosmicPerformanceSection
                v-else-if="section.key === 'league.performance'"
                v-bind="{
                    ...section.content,
                    performance: Object.keys(standingsData.performance || {}).length
                        ? standingsData.performance
                        : { position: '—', played: 0, points: 0, won: 0, drawn: 0, lost: 0, goalsFor: 0, goalsAgainst: 0, cleanSheets: 0, recentForm: [], recentPoints: 0 },
                }"
            />
            <TopPlayersSection
                v-else-if="section.key === 'league.top_players'"
                v-bind="{ ...section.content, players: playerData.topPlayers || [] }"
            />
            <MatchdayCenterSection
                v-else-if="section.key === 'league.matchday'"
                v-bind="{ ...section.content, ...matchdayData }"
            />
            <ParticipatingClubsSection
                v-else-if="section.key === 'league.clubs'"
                v-bind="{
                    ...section.content,
                    clubs: participantClubs,
                }"
            />
            <CompetitionRulesSection
                v-else-if="section.key === 'league.rules'"
                v-bind="{
                    ...section.content,
                    rules: competitionRules,
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
.league-kicker {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: -7px 0 21px;
    color: var(--color-accent);
    font: 800 0.72rem var(--font-display);
    letter-spacing: 0.2em;
    text-transform: uppercase;
}
.league-kicker::before {
    content: "";
    width: 34px;
    height: 2px;
    background: var(--color-accent);
    box-shadow: 0 0 12px rgba(69, 183, 255, 0.52);
}
.league-card {
    align-self: end;
    margin-bottom: 12px;
    padding: 27px;
    border: 1px solid rgba(175, 218, 255, 0.27);
    border-radius: 5px;
    background: rgba(5, 9, 15, 0.66);
    box-shadow: 0 24px 55px rgba(0, 0, 0, 0.25);
    backdrop-filter: blur(10px);
}
.league-card-label {
    color: var(--color-accent);
    font: 800 0.67rem var(--font-display);
    letter-spacing: 0.22em;
    text-transform: uppercase;
}
.league-season {
    margin-top: 12px;
    color: var(--color-paper);
    font: 900 5rem/0.83 var(--font-display);
    letter-spacing: -0.05em;
}
.league-season small {
    display: block;
    margin-top: 14px;
    color: #becbd5;
    font: 700 0.7rem var(--font-display);
    letter-spacing: 0.15em;
    text-transform: uppercase;
}
.league-empty {
    margin-top: 18px;
    color: #becbd5;
    font: 700 1rem/1.5 var(--font-body);
}
.league-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    margin-top: 26px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
}
.league-stats div {
    display: grid;
    gap: 6px;
    padding-inline: 14px;
    border-right: 1px solid rgba(255, 255, 255, 0.13);
    text-align: center;
}
.league-stats div:first-child {
    padding-left: 0;
}
.league-stats div:last-child {
    padding-right: 0;
    border-right: 0;
}
.league-stats strong {
    color: var(--color-paper);
    font: 900 1.65rem/1 var(--font-display);
}
.league-stats span {
    color: #91a0ad;
    font: 700 0.58rem var(--font-display);
    letter-spacing: 0.13em;
    text-transform: uppercase;
}
.hero-bottom {
    position: absolute;
    z-index: 3;
    right: 0;
    bottom: 0;
    left: 0;
    min-height: 69px;
    border-top: 1px solid rgba(255, 255, 255, 0.17);
    background: rgba(5, 9, 15, 0.6);
    backdrop-filter: blur(8px);
}
.hero-bottom-inner {
    width: min(calc(100% - 80px), var(--content-max));
    min-height: 69px;
    margin-inline: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.season-status,
.scroll-note {
    display: flex;
    align-items: center;
    gap: 11px;
    color: #cbd5dd;
    font: 700 0.64rem var(--font-display);
    letter-spacing: 0.17em;
    text-transform: uppercase;
}
.season-status::before {
    content: "";
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--color-accent);
    box-shadow: 0 0 13px rgba(69, 183, 255, 0.75);
}
.scroll-note::after {
    content: "↓";
    color: var(--color-accent);
    font-size: 1rem;
}

@media (max-width: 900px) {
    :deep(.hero-content) {
        padding-bottom: 135px;
    }
    .league-card {
        width: min(100%, 370px);
        margin: 0;
    }
    .hero-bottom-inner {
        width: min(calc(100% - 40px), var(--content-max));
    }
}

@media (max-width: 520px) {
    .league-kicker {
        margin-bottom: 16px;
    }
    .league-card {
        padding: 21px;
    }
    .league-season {
        font-size: 4rem;
    }
    .hero-bottom-inner {
        width: calc(100% - 32px);
    }
    .scroll-note {
        display: none;
    }
}
</style>
