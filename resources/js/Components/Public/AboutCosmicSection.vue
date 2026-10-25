<script setup>
import { computed } from "vue";

const props = defineProps({
    kicker: { type: String, default: "Our Story" },
    intro: {
        type: String,
        default:
            "Sebuah kolektif minisoccer yang tumbuh dari pertemanan, kecintaan pada permainan, dan keinginan membangun ruang sepak bola yang terasa seperti keluarga.",
    },
    storyImage: {
        type: String,
        default: "/Assets/Dokumentasi/Docs-wahyu.jpeg",
    },
    city: { type: String, default: "Pekanbaru, Indonesia" },
    storyTitle: { type: String, default: "Built By People." },
    storyAccent: { type: String, default: "Driven By Football." },
    story: {
        type: String,
        default:
            "COSMIC mempertemukan orang-orang dengan latar berbeda di satu lapangan. Bukan hanya mengejar hasil pertandingan, tetapi juga membangun kebiasaan baik, solidaritas, dan cerita yang layak dikenang bersama.",
    },
    identities: {
        type: Array,
        default: () => [
            {
                label: "Resmi Berdiri",
                value: "Tanggal belum diisi",
                placeholder: true,
            },
            { label: "Founder", value: "Nama belum diisi", placeholder: true },
            { label: "Home Base", value: "Pekanbaru" },
        ],
    },
    people: {
        type: Array,
        default: () => [
            {
                name: "Nama Founder",
                role: "Founder · COSMIC",
                badge: "Founder Spotlight",
                note: "Foto, nama, dan kutipan founder dapat diganti setelah data resmi tersedia.",
                photo: "/Assets/Dokumentasi/Docs-wahyu.jpeg",
                focalPoint: "center 44%",
                founder: true,
            },
            {
                name: "Nama Official",
                role: "Club Manager",
                badge: "Official",
                note: "Mengelola kegiatan dan arah perjalanan klub.",
                photo: "/Assets/Dokumentasi/Docs-wahyu.jpeg",
                focalPoint: "28% center",
            },
            {
                name: "Nama Official",
                role: "Head Coach",
                badge: "Official",
                note: "Memimpin persiapan dan performa tim di lapangan.",
                photo: "/Assets/Dokumentasi/Docs-wahyu.jpeg",
                focalPoint: "55% center",
            },
            {
                name: "Nama Official",
                role: "Media & Documentation",
                badge: "Official",
                note: "Merekam dan membagikan setiap cerita COSMIC.",
                photo: "/Assets/Dokumentasi/Docs-wahyu.jpeg",
                focalPoint: "78% center",
            },
        ],
    },
    partners: {
        type: Array,
        default: () => [
            { code: "S01", name: "Nama Sponsor", type: "Main Partner" },
            { code: "S02", name: "Nama Sponsor", type: "Apparel Partner" },
            { code: "S03", name: "Nama Sponsor", type: "Venue Partner" },
            { code: "S04", name: "Nama Sponsor", type: "Community Partner" },
            { code: "S05", name: "Nama Sponsor", type: "Media Partner" },
        ],
    },
});

const storyStyle = computed(() => ({
    "--story-image": `url("${props.storyImage}")`,
}));
const personStyle = (person) => ({
    "--person-image": `url("${person.photo}")`,
    "--person-position": person.focalPoint ?? "center",
});
</script>

<template>
    <section
        id="cerita-kami"
        class="about-cosmic"
        aria-labelledby="about-title"
    >
        <div class="shell">
            <div class="about-heading">
                <div>
                    <div class="about-kicker">{{ kicker }}</div>
                    <h2 id="about-title" class="about-title">
                        Tentang <span>COSMIC.</span>
                    </h2>
                </div>
                <p>{{ intro }}</p>
            </div>

            <div class="about-story">
                <div class="about-photo" :style="storyStyle">
                    <div class="about-photo-label">
                        Based in<span>{{ city }}</span>
                    </div>
                </div>
                <div class="about-copy">
                    <h3>
                        {{ storyTitle }}<br /><span>{{ storyAccent }}</span>
                    </h3>
                    <p>{{ story }}</p>
                    <div class="identity-grid" aria-label="Identitas COSMIC">
                        <div
                            v-for="identity in identities"
                            :key="identity.label"
                            class="identity-item"
                        >
                            <small>{{ identity.label }}</small>
                            <strong
                                :class="{ placeholder: identity.placeholder }"
                                >{{ identity.value }}</strong
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div class="people-heading">
                <h3>People Behind The Club.</h3>
                <span>Founder & Club Officials · 2026</span>
            </div>
            <div class="people-grid">
                <article
                    v-for="person in people"
                    :key="`${person.role}-${person.name}`"
                    class="person-card"
                    :class="{ founder: person.founder }"
                    :style="personStyle(person)"
                >
                    <span class="person-badge">{{ person.badge }}</span>
                    <h4 class="person-name">{{ person.name }}</h4>
                    <div class="person-role">{{ person.role }}</div>
                    <p class="person-note">{{ person.note }}</p>
                </article>
            </div>

            <div class="partners" aria-labelledby="partners-title">
                <div class="partners-heading">
                    <h3 id="partners-title">Official <span>Partners.</span></h3>
                    <p>
                        Terima kasih kepada para partner yang ikut mendukung
                        perjalanan, pertandingan, dan pertumbuhan komunitas
                        COSMIC.
                    </p>
                </div>
                <div v-if="partners.length" class="partner-grid">
                    <article
                        v-for="partner in partners"
                        :key="`${partner.name}-${partner.type}`"
                        class="partner-card"
                    >
                        <div class="partner-logo">
                            <img
                                v-if="partner.logo"
                                :src="partner.logo"
                                :alt="`Logo ${partner.name}`"
                                loading="lazy"
                                decoding="async"
                            />
                            <span v-else>{{ partner.code }}</span>
                        </div>
                        <div class="partner-name">{{ partner.name }}</div>
                        <div class="partner-type">{{ partner.type }}</div>
                    </article>
                </div>
                <p v-else class="partners-empty">
                    Partner resmi akan segera hadir.
                </p>
            </div>
        </div>
    </section>
</template>

<style scoped>
.about-cosmic {
    padding: 68px 0 76px;
    border-top: 1px solid var(--color-line);
    border-bottom: 1px solid var(--color-line);
    background: radial-gradient(
            circle at 12% 12%,
            rgba(69, 183, 255, 0.08),
            transparent 24%
        ),
        #050b14;
}
.shell {
    width: min(calc(100% - 80px), var(--content-max));
    margin-inline: auto;
}
.about-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 35px;
    margin-bottom: 30px;
}
.about-kicker {
    margin-bottom: 11px;
    color: var(--color-accent);
    font: 800 0.67rem var(--font-display);
    letter-spacing: 0.2em;
    text-transform: uppercase;
}
.about-title {
    margin: 0;
    color: var(--color-paper);
    font: 900 clamp(3.2rem, 5.6vw, 5.9rem) / 0.8 var(--font-display);
    letter-spacing: -0.05em;
    text-transform: uppercase;
}
.about-title span {
    color: var(--color-accent);
}
.about-heading > p {
    max-width: 400px;
    margin: 0;
    color: #929fab;
    font-size: 0.78rem;
    line-height: 1.65;
    text-align: right;
}
.about-story {
    display: grid;
    min-height: 430px;
    grid-template-columns: 1.15fr 0.85fr;
    overflow: hidden;
    border: 1px solid rgba(175, 218, 255, 0.16);
    background: var(--color-surface-raised);
}
.about-photo {
    position: relative;
    min-height: 430px;
    background:
        linear-gradient(90deg, transparent 60%, var(--color-surface-raised)),
        linear-gradient(0deg, rgba(5, 9, 15, 0.72), transparent 55%),
        var(--story-image) center/cover no-repeat;
    filter: saturate(0.62) contrast(1.08);
}
.about-photo-label {
    position: absolute;
    bottom: 22px;
    left: 24px;
    color: white;
    font: 800 0.61rem var(--font-display);
    letter-spacing: 0.16em;
    text-transform: uppercase;
}
.about-photo-label span {
    display: block;
    margin-top: 6px;
    color: var(--color-accent);
    font-size: 1.2rem;
    letter-spacing: 0.04em;
}
.about-copy {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 42px;
}
.about-copy h3 {
    margin: 0 0 18px;
    color: var(--color-paper);
    font: 900 clamp(2.5rem, 4vw, 4.2rem) / 0.84 var(--font-display);
    letter-spacing: -0.035em;
    text-transform: uppercase;
}
.about-copy h3 span {
    color: var(--color-accent);
}
.about-copy > p {
    margin: 0;
    color: #b7c3cd;
    font-size: 0.82rem;
    line-height: 1.7;
}
.identity-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    margin-top: 28px;
    border-top: 1px solid rgba(255, 255, 255, 0.12);
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}
.identity-item {
    padding: 17px 10px;
    border-right: 1px solid rgba(255, 255, 255, 0.12);
}
.identity-item:last-child {
    border: 0;
}
.identity-item small {
    display: block;
    color: #74818d;
    font: 700 0.51rem var(--font-display);
    letter-spacing: 0.13em;
    text-transform: uppercase;
}
.identity-item strong {
    display: block;
    margin-top: 7px;
    color: #edf3f8;
    font: 800 0.75rem var(--font-display);
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.identity-item .placeholder {
    color: #a8b4bf;
    font-style: italic;
}
.people-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 25px;
    margin: 44px 0 -36px;
}
.people-heading h3 {
    margin: 0;
    color: var(--color-paper);
    font: 900 clamp(2.4rem, 4vw, 4rem) / 0.85 var(--font-display);
    letter-spacing: -0.035em;
    text-transform: uppercase;
}
.people-heading span {
    color: #7e8b97;
    font: 700 0.58rem var(--font-display);
    letter-spacing: 0.15em;
    text-transform: uppercase;
}
.people-grid {
    display: grid;
    grid-template-columns: 1.35fr repeat(3, 1fr);
    gap: 12px;
}
.person-card {
    position: relative;
    display: flex;
    min-height: 350px;
    flex-direction: column;
    justify-content: flex-end;
    overflow: hidden;
    isolation: isolate;
    padding: 20px;
    border: 1px solid rgba(175, 218, 255, 0.14);
    transition:
        transform 0.25s,
        border-color 0.25s;
}
.person-card::before {
    content: "";
    position: absolute;
    inset: 0;
    z-index: -2;
    background:
        linear-gradient(0deg, rgba(5, 9, 15, 0.98), rgba(5, 9, 15, 0.12) 70%),
        var(--person-image) var(--person-position, center) / cover no-repeat;
    filter: saturate(0.5) contrast(1.1);
    transition: transform 0.55s;
}
.person-card::after {
    content: "";
    position: absolute;
    inset: 0;
    z-index: -1;
    background: linear-gradient(90deg, rgba(5, 9, 15, 0.25), transparent);
}
.person-card:hover {
    border-color: rgba(69, 183, 255, 0.5);
    transform: translateY(-4px);
}
.person-card:hover::before {
    transform: scale(1.045);
}
.person-card.founder {
    min-height: 410px;
}
.person-badge {
    position: absolute;
    top: 16px;
    left: 16px;
    padding: 6px 9px;
    border: 1px solid rgba(69, 183, 255, 0.5);
    border-radius: 20px;
    color: var(--color-accent);
    background: rgba(5, 9, 15, 0.6);
    font: 800 0.52rem var(--font-display);
    letter-spacing: 0.13em;
    text-transform: uppercase;
}
.person-name {
    margin: 0;
    color: var(--color-paper);
    font: 900 clamp(2rem, 3vw, 3rem) / 0.84 var(--font-display);
    text-transform: uppercase;
}
.person-role {
    margin-top: 8px;
    color: var(--color-accent);
    font: 800 0.58rem var(--font-display);
    letter-spacing: 0.17em;
    text-transform: uppercase;
}
.person-note {
    margin: 11px 0 0;
    color: #9ca9b5;
    font-size: 0.66rem;
    line-height: 1.5;
}
.partners {
    margin-top: 108px;
    padding-top: 32px;
    border-top: 1px solid rgba(255, 255, 255, 0.14);
}
.partners-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 25px;
    margin-bottom: -10px;
}
.partners-heading h3 {
    margin: 0;
    color: var(--color-paper);
    font: 900 clamp(2.3rem, 3.8vw, 3.8rem) / 0.86 var(--font-display);
    letter-spacing: -0.035em;
    text-transform: uppercase;
}
.partners-heading h3 span {
    color: var(--color-accent);
}
.partners-heading p {
    max-width: 330px;
    margin: 0;
    color: #7f8c98;
    font-size: 0.67rem;
    line-height: 1.55;
    text-align: right;
}
.partner-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 190px));
    justify-content: start;
    gap: 30px 44px;
    margin-top: 30px;
}
.partner-card {
    display: flex;
    min-height: 142px;
    align-items: center;
    justify-content: flex-start;
    flex-direction: column;
    gap: 8px;
    padding: 4px;
    color: #b7c3cd;
    text-align: center;
    transition:
        color 0.22s,
        transform 0.22s;
}
.partner-card:hover {
    color: white;
    transform: translateY(-4px);
}
.partner-logo {
    display: grid;
    width: 138px;
    height: 88px;
    box-sizing: border-box;
    place-items: center;
    padding: 8px 12px;
    color: var(--color-accent);
    font: 900 1.7rem var(--font-display);
    letter-spacing: -0.03em;
}
.partner-logo img {
    display: block;
    width: auto;
    height: auto;
    max-width: 112px;
    max-height: 72px;
    object-fit: contain;
    object-position: center;
    filter: drop-shadow(0 8px 18px rgba(0, 0, 0, 0.32));
    transition:
        filter 0.22s,
        transform 0.22s;
}
.partner-card:hover .partner-logo img {
    filter: drop-shadow(0 9px 22px rgba(69, 183, 255, 0.24));
    transform: scale(1.055);
}
.partner-name {
    font: 800 0.63rem var(--font-display);
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
.partner-type {
    color: #667480;
    font: 650 0.48rem var(--font-display);
    letter-spacing: 0.11em;
    text-transform: uppercase;
}
.partners-empty {
    margin: 35px 0 0;
    padding: 25px;
    border: 1px dashed rgba(175, 218, 255, 0.16);
    color: #71808c;
    text-align: center;
}

@media (max-width: 900px) {
    .shell {
        width: min(calc(100% - 40px), var(--content-max));
    }
    .about-story {
        grid-template-columns: 1fr;
    }
    .about-photo {
        background:
            linear-gradient(0deg, var(--color-surface-raised), transparent 45%),
            var(--story-image) center/cover no-repeat;
    }
    .people-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .person-card.founder {
        grid-column: 1/-1;
    }
    .partner-grid {
        grid-template-columns: repeat(auto-fit, minmax(140px, 180px));
        gap: 26px 34px;
    }
    .partners {
        margin-top: 84px;
    }
}

@media (max-width: 520px) {
    .about-cosmic {
        padding: 48px 0 54px;
    }
    .shell {
        width: calc(100% - 32px);
    }
    .about-heading,
    .people-heading,
    .partners-heading {
        align-items: flex-start;
        flex-direction: column;
    }
    .about-heading > p,
    .partners-heading p {
        text-align: left;
    }
    .about-photo {
        min-height: 330px;
    }
    .about-copy {
        padding: 30px 21px 35px;
    }
    .identity-grid {
        grid-template-columns: 1fr;
    }
    .identity-item {
        border-right: 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    }
    .identity-item:last-child {
        border-bottom: 0;
    }
    .people-grid {
        grid-template-columns: 1fr;
    }
    .person-card,
    .person-card.founder {
        min-height: 390px;
        grid-column: auto;
    }
    .partner-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 24px 12px;
    }
    .partner-card {
        min-height: 126px;
    }
    .partner-logo {
        width: 118px;
        height: 76px;
        padding: 6px 10px;
    }
    .partner-logo img {
        max-width: 98px;
        max-height: 64px;
    }
    .partners {
        margin-top: 68px;
    }
    .partners-heading {
        margin-bottom: -4px;
    }
}
</style>
