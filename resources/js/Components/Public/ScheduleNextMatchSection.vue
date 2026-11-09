<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    match: { type: Object, default: () => ({}) },
    label: { type: String, default: "Next Match" },
    emptyTitle: {
        type: String,
        default: "Pertandingan berikutnya belum dijadwalkan.",
    },
    emptyDescription: {
        type: String,
        default: "Informasi jadwal terbaru akan ditampilkan di sini.",
    },
    backgroundImage: { type: String, default: "" },
});
const hasMatch = computed(() => Boolean(props.match?.slug));
const match = computed(() => ({
    competition: '—', matchday: null, date: '—', time: '—', venue: '—', city: '', slug: '',
    ...props.match,
    home: { name: '—', logo: null, ...(props.match.home || {}) },
    away: { name: '—', logo: null, code: '—', ...(props.match.away || {}) },
}));
const sectionStyle = computed(() => {
    const image = props.backgroundImage || props.match.image;

    return image ? { '--schedule-match-image': `url("${image}")` } : {};
});
</script>

<template>
    <section class="next-match" :style="sectionStyle" aria-labelledby="schedule-next-match-title">
        <div class="match-background" aria-hidden="true"></div>
        <div v-if="hasMatch" class="match-wrap shell">
            <div class="match-topline">
                <div id="schedule-next-match-title" class="section-kicker">{{ label }}</div>
                <div class="competition">
                    {{ match.competition
                    }}<template v-if="match.matchday">
                        · Matchday {{ match.matchday }}</template
                    >
                </div>
            </div>
            <div class="match-grid">
                <div class="teams">
                    <div class="team team-home">
                        <h2 class="team-name">{{ match.home.name }}</h2>
                        <div class="crest crest-cosmic"><img v-if="match.home.logo" :src="match.home.logo" alt="Logo Cosmic" loading="lazy" decoding="async" /><span v-else>—</span></div>
                    </div>
                    <div class="versus">VS</div>
                    <div class="team team-away">
                        <div class="crest crest-rivals">
                            <img v-if="match.away.logo" :src="match.away.logo" :alt="`Logo ${match.away.name}`" loading="lazy" decoding="async" />
                            <span v-else>{{ match.away.code }}</span>
                        </div>
                        <h2 class="team-name">{{ match.away.name }}</h2>
                    </div>
                </div>
                <div class="match-meta">
                    <div class="meta-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="5" width="18" height="16" rx="2" /><path d="M16 3v4M8 3v4M3 10h18" /></svg><span>{{ match.date }}</span></div>
                    <div class="meta-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg><span>{{ match.time }}</span></div>
                    <div class="meta-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z" /><circle cx="12" cy="10" r="2.5" /></svg><span>{{ match.venue }}<br />{{ match.city }}</span></div>
                </div>
                <div class="match-actions">
                    <div class="match-status">Pertandingan Mendatang</div>
                    <Link v-if="match.slug" class="detail-button" :href="`/pertandingan/${match.slug}`">Lihat Detail <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14M13 6l6 6-6 6" /></svg></Link>
                    <span v-else class="detail-button is-disabled">Belum dijadwalkan</span>
                </div>
            </div>
        </div>
        <div v-else class="empty-wrap shell">
            <div id="schedule-next-match-title" class="section-kicker">{{ label }}</div>
            <h2>{{ emptyTitle }}</h2>
            <p v-if="emptyDescription">{{ emptyDescription }}</p>
        </div>
    </section>
</template>

<style scoped>
.next-match{position:relative;min-height:320px;isolation:isolate;overflow:hidden;border-bottom:1px solid var(--color-line);background:var(--color-surface)}
.match-background{position:absolute;inset:-4%;z-index:-2;background:linear-gradient(90deg,rgba(5,9,15,.98) 0%,rgba(5,9,15,.94) 31%,rgba(5,9,15,.8) 50%,rgba(5,9,15,.45) 69%,rgba(5,9,15,.14) 100%),linear-gradient(0deg,rgba(5,9,15,.82),transparent 52%,rgba(5,9,15,.44)),var(--schedule-match-image) center 53%/cover no-repeat;filter:saturate(.54) contrast(1.12)}
.next-match::after{content:'';position:absolute;inset:0;z-index:-1;opacity:.16;pointer-events:none;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 180 180' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='3'/%3E%3C/filter/%3E%3C/svg%3E")}
.shell{width:min(calc(100% - 80px),var(--content-max));margin-inline:auto}.match-wrap{padding:38px 0 42px}.match-topline{display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:29px}.section-kicker{display:flex;align-items:center;gap:12px;color:var(--color-accent);font:800 .73rem var(--font-display);letter-spacing:.22em;text-transform:uppercase}.section-kicker::before{content:'';width:36px;height:2px;background:var(--color-accent)}.competition{color:#b8c4ce;font:700 .66rem var(--font-display);letter-spacing:.17em;text-transform:uppercase}.match-grid{display:grid;grid-template-columns:minmax(500px,1.35fr) minmax(300px,.75fr) minmax(185px,.42fr);align-items:center;gap:42px}.teams{display:grid;grid-template-columns:minmax(0,1fr) auto minmax(0,1fr);align-items:center;gap:21px}.team{display:flex;align-items:center;gap:17px;min-width:0}.team-away{justify-content:flex-end}.crest{position:relative;display:grid;flex:0 0 82px;width:82px;height:82px;place-items:center;border:2px solid rgba(255,255,255,.73);color:white;font:900 1.28rem var(--font-display)}.crest img{width:68%;height:68%;object-fit:contain;border-radius:50%}.crest-cosmic{border-radius:50%}.crest-cosmic::before,.crest-cosmic::after{content:'';position:absolute;inset:8px -9px;border:1px solid rgba(255,255,255,.34);border-radius:50%;transform:rotate(28deg)}.crest-cosmic::after{transform:rotate(-28deg)}.crest-rivals{height:88px;border-radius:8px 8px 34px 34px;background:rgba(255,255,255,.05)}.team-name{margin:0;color:var(--color-paper);font:900 clamp(2.7rem,4.5vw,5.2rem)/.82 var(--font-display);letter-spacing:-.05em;text-transform:uppercase;white-space:nowrap}.team-away .team-name{font-style:italic}.versus{color:rgba(255,255,255,.8);font:italic 900 2rem var(--font-display)}.match-meta{display:grid;gap:14px;padding:12px 0 12px 35px;border-left:1px solid rgba(255,255,255,.22)}.meta-row{display:grid;grid-template-columns:22px 1fr;align-items:start;gap:12px;color:#edf3f8;font:650 .85rem/1.45 var(--font-display);letter-spacing:.04em;text-transform:uppercase}.meta-row svg{width:18px;height:18px;color:#bdc9d3;stroke-width:1.8}.match-actions{display:grid;justify-items:end;gap:12px}.match-status{display:flex;align-items:center;gap:8px;color:#bec9d3;font:700 .62rem var(--font-display);letter-spacing:.15em;text-transform:uppercase}.match-status::before{content:'';width:7px;height:7px;border-radius:50%;background:var(--color-accent);box-shadow:0 0 10px rgba(69,183,255,.65)}.detail-button{display:inline-flex;min-height:49px;align-items:center;justify-content:center;gap:15px;padding:0 23px;border:1px solid var(--color-accent);border-radius:7px;color:#04101c;background:var(--color-accent);font:800 .78rem var(--font-display);letter-spacing:.05em;text-decoration:none;text-transform:uppercase;transition:.2s}.detail-button:hover{color:white;background:var(--color-accent-hover);transform:translateY(-3px)}.detail-button svg{width:18px;stroke-width:2}
.empty-wrap{min-height:320px;display:flex;flex-direction:column;justify-content:center;padding-block:48px}.empty-wrap .section-kicker{margin-bottom:14px}.empty-wrap h2{max-width:800px;margin:0;color:var(--color-paper);font:900 clamp(2.1rem,4.4vw,4.6rem)/.9 var(--font-display);letter-spacing:-.04em;text-transform:uppercase}.empty-wrap p{max-width:620px;margin:15px 0 0;color:#9baab6;font-size:.9rem;line-height:1.6}
@media(max-width:1100px){.match-grid{grid-template-columns:1fr 1fr;gap:30px}.teams{grid-column:1/-1}.match-meta{padding-left:0;border-left:0}.match-actions{align-self:end}}
@media(max-width:520px){.shell{width:calc(100% - 32px)}.match-background{background:linear-gradient(180deg,rgba(5,9,15,.98),rgba(5,9,15,.7)),var(--schedule-match-image) center/cover}.match-topline{align-items:flex-start;flex-direction:column;gap:10px}.match-grid{grid-template-columns:1fr;gap:28px}.teams{grid-column:auto;gap:9px}.team{flex-direction:column;gap:10px;text-align:center}.team-away{flex-direction:column-reverse}.crest{flex-basis:58px;width:58px;height:58px}.crest-rivals{height:64px}.team-name{font-size:clamp(1.8rem,10vw,2.7rem)}.versus{font-size:1.4rem}.match-actions{justify-items:stretch}.detail-button{width:100%}.empty-wrap{min-height:280px;padding-block:40px}}
</style>
