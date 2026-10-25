<script setup>
import { ref } from 'vue';

defineProps({
    rules: {
        type: Array,
        default: () => [
            { title: 'Format Kompetisi', description: 'Sepuluh klub bertanding dalam format liga dua putaran dengan total 18 matchday.' },
            { title: 'Sistem Poin', description: 'Menang memperoleh 3 poin, seri 1 poin, dan kalah tidak memperoleh poin.' },
            { title: 'Penentuan Posisi', description: 'Urutan ditentukan berdasarkan poin, selisih gol, jumlah gol, lalu rekor pertemuan.' },
            { title: 'Durasi Pertandingan', description: 'Pertandingan berlangsung 2 × 25 menit dengan waktu istirahat selama 10 menit.' },
            { title: 'Fase Akhir', description: 'Empat tim teratas melaju ke playoff untuk menentukan juara kompetisi.' },
        ],
    },
});

const sectionRoot = ref(null);
</script>

<template>
    <section id="competition-rules" ref="sectionRoot" class="regulations" aria-labelledby="regulations-title">
        <div class="regulations-layout shell">
            <div class="regulations-intro" data-reveal>
                <div class="regulations-kicker">Competition Rules</div>
                <h2 id="regulations-title">Regulasi<br /><span>Singkat.</span></h2>
                <p>Ringkasan format dan aturan utama Pekanbaru Mini Soccer League musim 2026. Regulasi dapat diperbarui oleh penyelenggara selama kompetisi berlangsung.</p>
                <a class="regulation-link" href="#rules-list">Lihat Regulasi <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M5 12h14M13 6l6 6-6 6" /></svg></a>
            </div>

            <div id="rules-list" class="rules-list">
                <article v-if="!rules.length" class="rule empty-rule" data-reveal><div class="rule-number">—</div><h3>Regulasi belum tersedia</h3><p>Informasi regulasi akan tampil setelah dipublikasikan.</p><span class="rule-arrow">—</span></article>
                <article v-for="(rule,index) in rules" :key="rule.title" class="rule" data-reveal>
                    <div class="rule-number">{{ String(index + 1).padStart(2,'0') }}</div>
                    <h3>{{ rule.title }}</h3>
                    <p>{{ rule.description }}</p>
                    <span class="rule-arrow" aria-hidden="true">→</span>
                </article>
            </div>
        </div>
    </section>
</template>

<style scoped>
.regulations{padding:54px 0 66px;border-bottom:1px solid var(--color-line);background:radial-gradient(circle at 86% 70%,rgba(69,183,255,.035),transparent 24%),var(--color-surface)}.shell{width:min(calc(100% - 80px),var(--content-max));margin-inline:auto}.regulations-layout{display:grid;grid-template-columns:minmax(300px,.68fr) minmax(520px,1.32fr);gap:45px}.regulations-intro{position:relative}.regulations-kicker{display:flex;align-items:center;gap:11px;margin-bottom:17px;color:var(--color-accent);font:800 .65rem var(--font-display);letter-spacing:.18em;text-transform:uppercase}.regulations-kicker::before{content:'';width:34px;height:2px;background:var(--color-accent);box-shadow:0 0 10px rgba(69,183,255,.45)}.regulations-intro h2{margin:0;font:900 clamp(3rem,4.9vw,5.2rem)/.82 var(--font-display);letter-spacing:-.045em;text-transform:uppercase}.regulations-intro h2 span{color:var(--color-accent)}.regulations-intro>p{max-width:390px;margin:21px 0 0;color:#aebac4;font-size:.8rem;line-height:1.6}.regulation-link{display:inline-flex;align-items:center;gap:11px;margin-top:25px;color:#c5d0d9;font:750 .65rem var(--font-display);letter-spacing:.12em;text-decoration:none;text-transform:uppercase;transition:color .2s,gap .2s}.regulation-link:hover{gap:16px;color:var(--color-accent)}.regulation-link svg{width:17px;stroke-width:1.8}.rules-list{display:grid;scroll-margin-top:30px;border-top:1px solid rgba(175,218,255,.14)}.rule{position:relative;display:grid;grid-template-columns:52px minmax(170px,.6fr) 1fr 20px;align-items:center;gap:18px;min-height:78px;padding:10px 13px;border-bottom:1px solid rgba(255,255,255,.12);transition:background .22s,transform .22s}.rule:hover{background:rgba(69,183,255,.055);transform:translateX(4px)}.rule-number{color:var(--color-accent);font:800 .68rem var(--font-display);letter-spacing:.1em}.rule h3{margin:0;font:850 .82rem var(--font-display);letter-spacing:.08em;text-transform:uppercase}.rule p{margin:0;color:#8f9ca7;font-size:.7rem;line-height:1.55}.rule-arrow{color:#64727e;font:400 1.1rem var(--font-display);transition:color .2s,transform .2s}.rule:hover .rule-arrow{color:var(--color-accent);transform:translateX(3px)}
@media(max-width:900px){.shell{width:min(calc(100% - 40px),var(--content-max))}.regulations-layout{grid-template-columns:1fr}.regulations-intro>p{max-width:600px}}
@media(max-width:520px){.regulations{padding:42px 0 52px}.shell{width:calc(100% - 32px)}.regulations-layout{gap:36px}.rule{grid-template-columns:38px 1fr 18px;gap:11px;padding-block:14px}.rule p{grid-column:2/4}.rule-arrow{grid-column:3;grid-row:1}}
</style>
