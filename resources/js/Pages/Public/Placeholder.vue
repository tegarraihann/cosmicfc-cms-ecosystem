<script setup>
import { Head } from '@inertiajs/vue3';
import SiteNavbar from '@/Components/Public/SiteNavbar.vue';
import { ref } from 'vue';

defineProps({ title: String });
const pageRoot = ref(null);
</script>

<template>
    <Head :title="title" />
    <main ref="pageRoot" class="placeholder-page">
        <SiteNavbar :transparent="false" />
        <section data-reveal>
            <p>Halaman</p>
            <h1>{{ title }}</h1>
        </section>
    </main>
</template>

<style scoped>
.placeholder-page { min-height: 100svh; background: var(--color-ink); }
section { width: min(calc(100% - 80px), var(--content-max)); margin-inline: auto; padding: 100px 0; }
p { color: var(--color-accent); font: 800 .75rem var(--font-display); letter-spacing: .2em; text-transform: uppercase; }
h1 { margin: 8px 0 0; font: 900 clamp(4rem,9vw,9rem)/.8 var(--font-display); text-transform: uppercase; }
@media(max-width:520px) { section { width: calc(100% - 32px); } }
</style>
