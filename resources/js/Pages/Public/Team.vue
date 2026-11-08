<script setup>
import { usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import PageHeroSection from "@/Components/Public/PageHeroSection.vue";
import SiteNavbar from "@/Components/Public/SiteNavbar.vue";
import SquadDirectorySection from "@/Components/Public/SquadDirectorySection.vue";
import PlayerOfMonthSection from "@/Components/Public/PlayerOfMonthSection.vue";
import PlayerProfileModal from "@/Components/Public/PlayerProfileModal.vue";
import CaptainSpotlightSection from "@/Components/Public/CaptainSpotlightSection.vue";
import CoachingStaffSection from "@/Components/Public/CoachingStaffSection.vue";
import SquadStatisticsSection from "@/Components/Public/SquadStatisticsSection.vue";
import PositionCompositionSection from "@/Components/Public/PositionCompositionSection.vue";
import SiteFooter from "@/Components/Public/SiteFooter.vue";
const props = defineProps({
    layoutSections: { type: Array, default: () => [] },
    isPreview: Boolean,
    playerData: { type: Object, default: () => ({}) },
    staffData: { type: Object, default: () => ({ coaching: [], support: [] }) },
});
const page = usePage();
const site = computed(() => page.props.site ?? {});
const squadStats = computed(() => [
    {
        value: String(props.playerData.squadStats?.players || 0).padStart(2, "0"),
        label: "Pemain",
    },
    {
        value: String(props.playerData.squadStats?.appearances || 0).padStart(2, "0"),
        label: "Penampilan",
    },
    {
        value: String(props.playerData.squadStats?.goals || 0).padStart(2, "0"),
        label: "Gol",
    },
    {
        value: site.value.identity?.foundedYear || "—",
        label: "Est.",
    },
]);
const selectedPlayer = ref(null);
const dynamicSquadStats = computed(() => [
    {
        value: String(props.playerData.squadStats?.players || 0).padStart(
            2,
            "0",
        ),
        label: "Pemain Aktif",
    },
    {
        value: String(props.playerData.squadStats?.appearances || 0).padStart(
            2,
            "0",
        ),
        label: "Total Penampilan",
    },
    {
        value: String(props.playerData.squadStats?.goals || 0).padStart(2, "0"),
        label: "Gol Tercipta",
        accent: true,
    },
    {
        value: String(props.playerData.squadStats?.assists || 0).padStart(
            2,
            "0",
        ),
        label: "Total Assist",
    },
    {
        value: String(props.playerData.squadStats?.cleanSheets || 0).padStart(
            2,
            "0",
        ),
        label: "Clean Sheet",
    },
]);
</script>

<template>
    <main>
        <div v-if="isPreview" class="preview-banner">
            MODE PREVIEW DRAFT · Perubahan belum dipublikasikan
        </div>
        <template v-for="section in layoutSections" :key="section.key">
            <PageHeroSection
                v-if="section.key === 'team.hero'"
                page-label="Tim"
                title="Meet The"
                title-accent="Squad."
                description="Kenali para pemain dan staf yang membawa semangat COSMIC ke setiap pertandingan. Berbeda peran, satu tujuan, satu keluarga."
                min-height="90svh"
                panel-width="365px"
                v-bind="section.content"
            >
                <template #navigation><SiteNavbar /></template>

                <template #kicker
                    ><div class="team-kicker">The Collective</div></template
                >

                <template #panel>
                    <aside
                        class="hero-panel squad-card"
                        aria-label="Ringkasan skuad"
                    >
                        <div class="squad-card-top">
                            <span class="squad-logo">
                                <img
                                    v-if="site.branding?.logo"
                                    :src="site.branding.logo"
                                    :alt="`Logo ${site.identity?.name || 'COSMIC'}`"
                                />
                                <strong v-else>{{ site.identity?.shortName || 'CS' }}</strong>
                            </span>
                            <div class="squad-label">
                                Ringkasan Skuad<strong>{{ playerData.players?.length ? 'Skuad Aktif' : 'Belum Ada Data' }}</strong>
                            </div>
                        </div>
                        <div class="squad-stats">
                            <div
                                v-for="stat in squadStats"
                                :key="stat.label"
                                class="squad-stat"
                            >
                                <strong>{{ stat.value }}</strong
                                ><span>{{ stat.label }}</span>
                            </div>
                        </div>
                        <div class="squad-status">
                            {{ playerData.players?.length ? 'Data skuad tersedia' : 'Data skuad belum tersedia' }}
                        </div>
                    </aside>
                </template>

                <template #bottom>
                    <div class="hero-bottom">
                        <div class="hero-bottom-inner">
                            <div class="hero-quote">
                                Different roles. <span>One team.</span>
                            </div>
                            <div class="scroll-note">Kenali Skuad</div>
                        </div>
                    </div>
                </template>
            </PageHeroSection>
            <SquadDirectorySection
                v-else-if="section.key === 'team.directory'"
                v-bind="{ ...section.content, players: playerData.players || [] }"
                @view-profile="selectedPlayer = $event"
            />
            <PlayerOfMonthSection
                v-else-if="section.key === 'team.player_month'"
                v-bind="{
                    ...section.content,
                    player: playerData.playerOfMonth,
                }"
                @view-profile="selectedPlayer = $event"
            />
            <CaptainSpotlightSection
                v-else-if="section.key === 'team.captain'"
                v-bind="{ ...section.content, captain: playerData.captain }"
            />
            <CoachingStaffSection
                v-else-if="section.key === 'team.staff'"
                v-bind="{
                    ...section.content,
                    staff: staffData.coaching,
                    support: staffData.support,
                }"
            />
            <SquadStatisticsSection
                v-else-if="section.key === 'team.statistics'"
                v-bind="{ ...section.content, stats: dynamicSquadStats }"
            />
            <PositionCompositionSection
                v-else-if="section.key === 'team.composition'"
                v-bind="{ ...section.content, positions: playerData.positions || [] }"
            />
        </template>
        <SiteFooter />
        <PlayerProfileModal
            :player="selectedPlayer"
            @close="selectedPlayer = null"
        />
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
.team-kicker {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: -7px 0 21px;
    color: var(--color-accent);
    font: 800 0.72rem var(--font-display);
    letter-spacing: 0.22em;
    text-transform: uppercase;
}
.team-kicker::before {
    content: "";
    width: 42px;
    height: 2px;
    background: var(--color-accent);
    box-shadow: 0 0 12px rgba(69, 183, 255, 0.5);
}
.squad-card {
    align-self: end;
    margin-bottom: 4px;
    padding: 27px;
    border: 1px solid rgba(175, 218, 255, 0.25);
    border-radius: 5px;
    background: rgba(5, 9, 15, 0.65);
    box-shadow: 0 24px 55px rgba(0, 0, 0, 0.25);
    backdrop-filter: blur(10px);
}
.squad-card-top {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 20px;
}
.squad-logo {
    display: grid;
    width: 52px;
    height: 52px;
    place-items: center;
    border: 1px solid rgba(69, 183, 255, 0.45);
    border-radius: 50%;
    background: rgba(5, 9, 15, 0.5);
}
.squad-logo img {
    width: 42px;
    height: 42px;
    object-fit: cover;
    border-radius: 50%;
}
.squad-logo > strong {
    color: var(--color-accent);
    font: 900 0.8rem var(--font-display);
}
.squad-label {
    color: var(--color-accent);
    font: 800 0.65rem var(--font-display);
    letter-spacing: 0.18em;
    text-transform: uppercase;
}
.squad-label strong {
    display: block;
    margin-top: 4px;
    color: white;
    font-size: 1.2rem;
    letter-spacing: 0.04em;
}
.squad-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    border-top: 1px solid rgba(255, 255, 255, 0.14);
    border-bottom: 1px solid rgba(255, 255, 255, 0.14);
}
.squad-stat {
    padding: 17px 8px;
    text-align: center;
    border-right: 1px solid rgba(255, 255, 255, 0.12);
}
.squad-stat:nth-child(2n) {
    border-right: 0;
}
.squad-stat:nth-child(-n + 2) {
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}
.squad-stat strong {
    display: block;
    color: var(--color-paper);
    font: 900 2rem/0.9 var(--font-display);
}
.squad-stat span {
    color: #8e9ba6;
    font: 650 0.56rem var(--font-display);
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.squad-status {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-top: 18px;
    color: #b6c2cc;
    font: 650 0.62rem var(--font-display);
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.squad-status::before {
    content: "";
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--color-accent);
    box-shadow: 0 0 11px rgba(69, 183, 255, 0.7);
}
.hero-bottom {
    position: absolute;
    z-index: 3;
    right: 0;
    bottom: 0;
    left: 0;
    min-height: 69px;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(5, 9, 15, 0.48);
    backdrop-filter: blur(8px);
}
.hero-bottom-inner {
    width: min(calc(100% - 80px), var(--content-max));
    min-height: 69px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    margin-inline: auto;
}
.hero-quote {
    color: #c3ced7;
    font: italic 650 0.78rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.hero-quote span {
    color: var(--color-accent);
}
.scroll-note {
    display: flex;
    align-items: center;
    gap: 11px;
    color: #778590;
    font: 650 0.6rem var(--font-display);
    letter-spacing: 0.16em;
    text-transform: uppercase;
}
.scroll-note::after {
    content: "";
    width: 45px;
    height: 1px;
    background: rgba(255, 255, 255, 0.36);
}
@media (max-width: 900px) {
    :deep(.hero-content) {
        padding-bottom: 135px;
    }
    .squad-card {
        width: min(100%, 365px);
        margin: 0;
    }
    .hero-bottom-inner {
        width: min(calc(100% - 40px), var(--content-max));
    }
}
@media (max-width: 520px) {
    .team-kicker {
        margin-bottom: 16px;
    }
    .squad-card {
        padding: 21px;
    }
    .hero-bottom-inner {
        width: calc(100% - 32px);
    }
    .scroll-note {
        display: none;
    }
}
</style>
