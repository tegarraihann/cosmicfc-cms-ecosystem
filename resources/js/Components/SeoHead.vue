<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();
const meta = computed(() => page.props.seoMeta ?? {});
const schema = computed(() =>
    meta.value.schema
        ? JSON.stringify(meta.value.schema).replaceAll("<", "\\u003c")
        : "",
);
</script>

<template>
    <Head>
        <link v-if="meta.favicon" head-key="favicon" rel="icon" :href="meta.favicon" />
    </Head>
    <Head v-if="meta.managed" :title="meta.title">
        <meta head-key="description" name="description" :content="meta.description" />
        <meta v-if="meta.keywords" head-key="keywords" name="keywords" :content="meta.keywords" />
        <meta head-key="robots" name="robots" :content="meta.robots" />
        <link head-key="canonical" rel="canonical" :href="meta.canonical" />
        <meta head-key="og:type" property="og:type" :content="meta.type" />
        <meta head-key="og:title" property="og:title" :content="meta.title" />
        <meta head-key="og:description" property="og:description" :content="meta.description" />
        <meta head-key="og:url" property="og:url" :content="meta.canonical" />
        <meta head-key="og:site_name" property="og:site_name" :content="meta.siteName" />
        <meta v-if="meta.image" head-key="og:image" property="og:image" :content="meta.image" />
        <meta v-if="meta.image" head-key="og:image:width" property="og:image:width" content="1200" />
        <meta v-if="meta.image" head-key="og:image:height" property="og:image:height" content="630" />
        <meta head-key="twitter:card" name="twitter:card" :content="meta.twitterCard" />
        <meta head-key="twitter:title" name="twitter:title" :content="meta.title" />
        <meta head-key="twitter:description" name="twitter:description" :content="meta.description" />
        <meta v-if="meta.image" head-key="twitter:image" name="twitter:image" :content="meta.image" />
        <meta v-if="meta.publishedTime" head-key="article:published_time" property="article:published_time" :content="meta.publishedTime" />
        <component
            :is="'script'"
            v-if="schema"
            head-key="structured-data"
            type="application/ld+json"
            v-text="schema"
        />
    </Head>
</template>
