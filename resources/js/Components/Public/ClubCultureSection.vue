<script setup>
defineProps({
    tag: { type: String, default: 'Club Culture' },
    titleTop: { type: String, default: 'More Than' },
    titleBottom: { type: String, default: 'Football' },
    description: {
        type: String,
        default: 'COSMIC bukan cuma soal pertandingan. Ini tentang pertemanan, kerja sama, dan budaya sepak bola yang terus hidup di setiap minggu, di dalam dan luar lapangan.',
    },
    featureImage: { type: String, default: '/Assets/Dokumentasi/Docs-wahyu.jpeg' },
    featureNote: { type: String, default: 'Good people.\nBetter football.' },
    ctaLabel: { type: String, default: 'Lihat Cerita Kami' },
    ctaHref: { type: String, default: '#cerita-kami' },
    galleryNote: { type: String, default: 'Same pitch.\nSame family.' },
    gallery: {
        type: Array,
        default: () => [
            { src: '/Assets/Dokumentasi/Docs-wahyu.jpeg', alt: 'Dokumentasi pertandingan Cosmic', focalPoint: '30% center' },
            { src: '/Assets/Dokumentasi/Docs-wahyu.jpeg', alt: 'Bola di lapangan Cosmic', focalPoint: '52% center' },
            { src: '/Assets/Dokumentasi/Docs-wahyu.jpeg', alt: 'Kebersamaan anggota Cosmic', focalPoint: '75% center' },
        ],
    },
});

</script>

<template>
    <section id="club-culture" class="culture" aria-labelledby="culture-title">
        <div class="culture-grid">
            <div class="culture-feature">
                <img class="culture-photo" :src="featureImage" alt="Pemain Cosmic di lapangan minisoccer" loading="lazy" />
                <div class="culture-tag">{{ tag }}</div>
                <p class="culture-scribble">{{ featureNote }}</p>
            </div>

            <div class="culture-copy">
                <h2 id="culture-title" class="culture-title">{{ titleTop }}<br />{{ titleBottom }}<span>.</span></h2>
                <p class="culture-description">{{ description }}</p>
                <a class="story-button" :href="ctaHref">
                    {{ ctaLabel }}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                </a>
            </div>

            <div class="culture-gallery" aria-label="Galeri budaya klub">
                <figure v-for="(image, index) in gallery" :key="`${image.src}-${index}`" class="gallery-item">
                    <img :src="image.src" :alt="image.alt" :style="{ objectPosition: image.focalPoint ?? 'center' }" loading="lazy" />
                </figure>
                <p class="gallery-note">{{ galleryNote }}<br /><span class="gallery-heart">♡</span></p>
            </div>
        </div>
    </section>
</template>

<style scoped>
.culture { position: relative; overflow: hidden; border-bottom: 1px solid var(--color-line); background: var(--color-surface); }
.culture-grid { min-height: 330px; display: grid; grid-template-columns: minmax(270px,.9fr) minmax(300px,.9fr) minmax(500px,1.75fr); align-items: stretch; }
.culture-feature { position: relative; min-height: 330px; overflow: hidden; border-right: 1px solid rgba(175,218,255,.14); }
.culture-feature::after { content: ''; position: absolute; inset: 0; pointer-events: none; background: linear-gradient(90deg,rgba(4,10,17,.12),transparent 55%,rgba(4,10,17,.35)),linear-gradient(0deg,rgba(4,10,17,.68),transparent 48%); }
.culture-photo { width: 100%; height: 112%; object-fit: cover; filter: saturate(.62) contrast(1.08); transition: transform .7s cubic-bezier(.2,.7,.2,1),filter .35s; }
.culture-feature:hover .culture-photo { filter: saturate(.86) contrast(1.08); transform: scale(1.045); }
.culture-tag { position: absolute; z-index: 2; top: 25px; right: 25px; color: #edf3f8; font: 700 .7rem var(--font-display); letter-spacing: .2em; text-transform: uppercase; }
.culture-scribble { position: absolute; z-index: 2; left: 25px; bottom: 23px; width: 135px; margin: 0; color: var(--color-accent); font: italic 700 1.55rem/.92 var(--font-display); text-transform: uppercase; white-space: pre-line; transform: rotate(-8deg); }
.culture-copy { display: flex; flex-direction: column; justify-content: center; padding: 38px 38px 40px; background: radial-gradient(circle at 15% 20%,rgba(69,183,255,.055),transparent 30%),var(--color-surface); }
.culture-title { max-width: 320px; margin: 0 0 15px; color: var(--color-paper); font: 900 clamp(2.4rem,3.4vw,3.85rem)/.82 var(--font-display); letter-spacing: -.035em; text-transform: uppercase; }
.culture-title span { color: var(--color-accent); }
.culture-description { max-width: 360px; margin: 0 0 21px; color: #d3dde6; font-size: .88rem; line-height: 1.55; }
.story-button { display: inline-flex; min-height: 47px; align-self: flex-start; align-items: center; justify-content: center; gap: 14px; padding: 0 22px; border: 1px solid var(--color-accent); border-radius: 7px; color: #04101c; background: var(--color-accent); box-shadow: 0 0 26px rgba(69,183,255,.14); font: 800 .76rem var(--font-display); letter-spacing: .04em; text-decoration: none; text-transform: uppercase; transition: .2s; }
.story-button:hover { color: white; border-color: var(--color-accent-hover); background: var(--color-accent-hover); transform: translateY(-3px); }
.story-button svg { width: 18px; stroke-width: 2; }
.culture-gallery { position: relative; display: grid; grid-template-columns: repeat(3,1fr); align-items: stretch; gap: 14px; padding: 28px 28px 28px 0; }
.gallery-item { position: relative; min-height: 274px; margin: 0; overflow: hidden; background: #111a24; }
.gallery-item img { width: 100%; height: 100%; object-fit: cover; filter: saturate(.62) contrast(1.06); transition: transform .55s cubic-bezier(.2,.7,.2,1),filter .3s; }
.gallery-item:hover img { filter: saturate(.9) contrast(1.06); transform: scale(1.06); }
.gallery-item::after { content: ''; position: absolute; inset: 0; border: 1px solid rgba(175,218,255,.12); pointer-events: none; transition: border-color .25s; }
.gallery-item:hover::after { border-color: rgba(69,183,255,.56); }
.gallery-note { position: absolute; z-index: 3; top: 20px; right: 6px; width: 100px; margin: 0; color: rgba(255,255,255,.82); font: italic 600 1.25rem/.92 var(--font-display); text-align: right; text-transform: uppercase; white-space: pre-line; transform: rotate(-6deg); }
.gallery-heart { color: var(--color-accent); font-size: 1.5rem; }

@media(max-width:900px) {
    .culture-grid { grid-template-columns: .9fr 1.1fr; }
    .culture-feature { min-height: 360px; }
    .culture-copy { padding: 35px 30px; }
    .culture-gallery { grid-column: 1/-1; padding: 14px 20px 28px; }
    .gallery-item { min-height: 270px; }
}

@media(max-width:520px) {
    .culture-grid { grid-template-columns: 1fr; }
    .culture-feature { min-height: 340px; border-right: 0; }
    .culture-copy { padding: 42px 20px; }
    .culture-gallery { grid-template-columns: repeat(2,1fr); gap: 10px; padding: 0 20px 30px; }
    .gallery-item { min-height: 250px; }
    .gallery-item:last-of-type { min-height: 290px; grid-column: 1/-1; }
    .gallery-note { right: 28px; }
}
</style>
