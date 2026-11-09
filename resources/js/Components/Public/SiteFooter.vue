<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { mainNavigation } from '@/config/navigation';

const props = defineProps({
    location: { type: String, default: 'Pekanbaru, Indonesia' },
    copyrightYear: { type: [String, Number], default: 2026 },
    instagramHref: { type: String, default: '#instagram' },
    tiktokHref: { type: String, default: '#tiktok' },
    youtubeHref: { type: String, default: '#youtube' },
});

const page = usePage();
const site = computed(() => page.props.site ?? {});
const clubName = computed(() => site.value.identity?.shortName || site.value.identity?.name || 'COSMIC');
const officialClubName = computed(() => site.value.identity?.name || clubName.value);
const brandLength = computed(() => Array.from(clubName.value.trim()).length);
const brandSizeClass = computed(() => ({
    'footer-logo--medium': brandLength.value > 12 && brandLength.value <= 20,
    'footer-logo--long': brandLength.value > 20,
}));
const logo = computed(() => site.value.branding?.logo || '/Assets/Logo/Logo.jpeg');
const displayedLocation = computed(() => [site.value.location?.city, site.value.location?.country].filter(Boolean).join(', ') || props.location);
const displayedYear = computed(() => site.value.identity?.foundedYear || props.copyrightYear);
const socialHref = (platform, fallback) => site.value.socials?.find((social) => social.platform === platform && social.active)?.url || fallback;

</script>

<template>
    <footer id="gabung" class="site-footer">
        <div class="shell">
            <div class="footer-main">
                <div class="footer-brand">
                    <a class="footer-logo" :class="brandSizeClass" href="#top" :title="clubName" :aria-label="`${clubName}, kembali ke atas`">
                        <img :src="logo" :alt="`Logo ${clubName}`" loading="lazy" decoding="async" />
                        <span class="footer-logo-name">{{ clubName }}</span><span class="footer-logo-dot" aria-hidden="true">.</span>
                    </a>
                    <div class="footer-address">Mini Soccer Collective<br />{{ displayedLocation }}</div>
                </div>

                <nav class="footer-nav" aria-label="Navigasi footer">
                    <Link v-for="item in mainNavigation" :key="item.key" :href="item.href">{{ item.label }}</Link>
                    <Link href="/login">Masuk</Link>
                </nav>

                <div class="footer-side">
                    <div class="socials" aria-label="Media sosial COSMIC">
                        <a class="social-link" :href="socialHref('instagram', instagramHref)" aria-label="Instagram">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" /><circle cx="12" cy="12" r="4" /><circle cx="17.5" cy="6.5" r=".8" fill="currentColor" stroke="none" /></svg>
                        </a>
                        <a class="social-link" :href="socialHref('tiktok', tiktokHref)" aria-label="TikTok">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M16.6 3c.2 1.8 1.2 3.2 3 3.8v3.1a8.5 8.5 0 0 1-3-.8v6.1a6.2 6.2 0 1 1-5.4-6.1v3.2a3.1 3.1 0 1 0 2.2 2.9V3h3.2Z" /></svg>
                        </a>
                        <a class="social-link" :href="socialHref('youtube', youtubeHref)" aria-label="YouTube">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.6 7.2a2.8 2.8 0 0 0-2-2C17.8 4.7 12 4.7 12 4.7s-5.8 0-7.6.5a2.8 2.8 0 0 0-2 2A29 29 0 0 0 2 12a29 29 0 0 0 .4 4.8 2.8 2.8 0 0 0 2 2c1.8.5 7.6.5 7.6.5s5.8 0 7.6-.5a2.8 2.8 0 0 0 2-2A29 29 0 0 0 22 12a29 29 0 0 0-.4-4.8ZM10 15.2V8.8l5.5 3.2-5.5 3.2Z" /></svg>
                        </a>
                    </div>
                    <div class="footer-motto">Football people<br />always <span>belong.</span></div>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© {{ displayedYear }} {{ officialClubName }}. All rights reserved.</span>
                <span>{{ site.identity?.tagline || 'Good people. Better football.' }}</span>
            </div>
        </div>
    </footer>
</template>

<style scoped>
.site-footer { position: relative; padding: 30px 0 21px; border-top: 1px solid var(--color-line); background: radial-gradient(circle at 88% 10%,rgba(69,183,255,.06),transparent 22%),var(--color-surface); }
.shell { width: min(calc(100% - 80px),var(--content-max)); margin-inline: auto; }
.footer-main { display: grid; grid-template-columns: minmax(0,1fr) auto minmax(280px,1fr); align-items: center; gap: 45px; padding-bottom: 25px; }
.footer-brand { display: flex; min-width: 0; align-items: center; gap: 28px; }
.footer-logo { display: flex; max-width: min(100%,320px); min-width: 0; align-items: center; gap: 10px; color: var(--color-paper); font: 400 2rem/1 var(--font-brand); letter-spacing: -.09em; text-decoration: none; }
.footer-logo-name { min-width: 0; overflow-wrap: anywhere; display: -webkit-box; overflow: hidden; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
.footer-logo--medium { font-size: 1.7rem; }
.footer-logo--long { font-size: 1.4rem; line-height: .94; letter-spacing: -.06em; }
.footer-logo img { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; }
.footer-logo img,.footer-logo-dot { flex: 0 0 auto; }
.footer-logo-dot { align-self: flex-end; color: var(--color-accent); text-shadow: 0 0 14px rgba(69,183,255,.2); }
.footer-address { flex: 0 0 auto; padding-left: 27px; border-left: 1px solid rgba(255,255,255,.25); color: #b9c5cf; font: 600 .62rem/1.55 var(--font-display); letter-spacing: .2em; text-transform: uppercase; }
.footer-nav { display: flex; align-items: center; justify-content: center; gap: 27px; }
.footer-nav a { color: #b9c5cf; font: 700 .63rem var(--font-display); letter-spacing: .12em; text-decoration: none; text-transform: uppercase; transition: color .2s; }
.footer-nav a:hover { color: var(--color-accent); }
.footer-side { display: flex; align-items: center; justify-content: flex-end; gap: 26px; }
.socials { display: flex; align-items: center; gap: 17px; }
.social-link { display: grid; width: 29px; height: 29px; place-items: center; color: #edf3f8; transition: color .2s,transform .2s; }
.social-link:hover { color: var(--color-accent); transform: translateY(-2px); }
.social-link svg { width: 19px; height: 19px; stroke-width: 1.8; }
.footer-motto { max-width: 150px; padding-left: 25px; border-left: 1px solid rgba(255,255,255,.3); color: rgba(255,255,255,.82); font: italic 600 1.15rem/.9 var(--font-display); text-transform: uppercase; transform: rotate(-4deg); }
.footer-motto span { color: var(--color-accent); }
.footer-bottom { display: flex; align-items: center; justify-content: space-between; gap: 25px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,.18); color: #7f8d99; font: 600 .58rem var(--font-display); letter-spacing: .2em; text-transform: uppercase; }

@media(max-width:1100px) {
    .footer-main { grid-template-columns: 1fr 1fr; }
    .footer-brand { grid-column: 1/-1; }
    .footer-nav { justify-content: flex-start; flex-wrap: wrap; }
}

@media(max-width:900px) {
    .shell { width: min(calc(100% - 40px),var(--content-max)); }
}

@media(max-width:520px) {
    .site-footer { padding-top: 34px; padding-bottom: 26px; }
    .shell { width: calc(100% - 32px); }
    .footer-main { grid-template-columns: 1fr; gap: 29px; }
    .footer-brand { grid-column: auto; display: grid; grid-template-columns: minmax(0,1fr) auto; align-items: flex-start; gap: 18px; }
    .footer-logo { max-width: 100%; font-size: 1.65rem; }
    .footer-logo--medium { font-size: 1.35rem; }
    .footer-logo--long { font-size: 1.12rem; }
    .footer-address { padding-left: 18px; }
    .footer-nav { justify-content: flex-start; flex-wrap: wrap; row-gap: 15px; }
    .footer-side { justify-content: space-between; }
    .footer-bottom { align-items: flex-start; flex-direction: column; gap: 10px; }
}
</style>
