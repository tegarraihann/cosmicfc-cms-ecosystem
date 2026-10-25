<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, onMounted, ref, watchEffect } from "vue";
import { mainNavigation } from "@/config/navigation";

defineProps({
    transparent: { type: Boolean, default: true },
});

const page = usePage();
const menuOpen = ref(false);
const site = computed(() => page.props.site ?? {});
const identity = computed(() => site.value.identity ?? {});
const branding = computed(() => site.value.branding ?? {});
const clubName = computed(
    () => identity.value.shortName || identity.value.name || "COSMIC",
);
const logo = computed(() => branding.value.logo || "/Assets/Logo/Logo.jpeg");
const currentPath = computed(() => page.url.split("?")[0]);
const isActive = (item) =>
    item.href === "/"
        ? currentPath.value === "/"
        : currentPath.value.startsWith(item.href);

const applySiteTheme = () => {
    const root = document.documentElement;
    root.style.setProperty(
        "--color-surface",
        branding.value.primaryColor || "#07111e",
    );
    root.style.setProperty(
        "--color-accent",
        branding.value.accentColor || "#45b7ff",
    );
};

onMounted(applySiteTheme);
watchEffect(() => {
    branding.value.primaryColor;
    branding.value.accentColor;
    if (typeof document !== "undefined") applySiteTheme();
});
</script>

<template>
    <header class="site-header" :class="{ 'site-header--solid': !transparent }">
        <nav class="navbar shell" aria-label="Navigasi utama">
            <Link
                class="brand"
                href="/"
                :aria-label="`${clubName}, kembali ke beranda`"
            >
                <img :src="logo" :alt="`Logo ${clubName}`" />
                {{ clubName }}<span>.</span>
            </Link>

            <div
                id="primary-navigation"
                class="desktop-links"
                :class="{ open: menuOpen }"
            >
                <Link
                    v-for="item in mainNavigation"
                    :key="item.key"
                    :href="item.href"
                    :class="{ active: isActive(item) }"
                    :aria-current="isActive(item) ? 'page' : undefined"
                    @click="menuOpen = false"
                >
                    {{ item.label }}
                </Link>
            </div>

            <div class="nav-action">
                <Link class="login-link" href="/login">
                    Masuk
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path d="M5 12h14M13 6l6 6-6 6" />
                    </svg>
                </Link>
            </div>

            <button
                class="menu-button"
                type="button"
                :aria-expanded="menuOpen"
                aria-controls="primary-navigation"
                :aria-label="menuOpen ? 'Tutup menu' : 'Buka menu'"
                @click="menuOpen = !menuOpen"
            >
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    aria-hidden="true"
                >
                    <path d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </button>
        </nav>
    </header>

    <Teleport to="body">
        <nav class="mobile-bottom-nav" aria-label="Navigasi mobile">
            <Link
                v-for="item in mainNavigation"
                :key="`mobile-${item.key}`"
                :href="item.href"
                :class="{ active: isActive(item) }"
                :aria-current="isActive(item) ? 'page' : undefined"
            >
                <svg
                    v-if="item.icon === 'home'"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path d="m3 11 9-8 9 8" />
                    <path d="M5 10v10h14V10M9 20v-6h6v6" />
                </svg>
                <svg
                    v-else-if="item.icon === 'calendar'"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <rect x="3" y="5" width="18" height="16" rx="2" />
                    <path d="M16 3v4M8 3v4M3 10h18" />
                </svg>
                <svg
                    v-else-if="item.icon === 'trophy'"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4Z" />
                    <path d="M7 6H4v2a4 4 0 0 0 4 4M17 6h3v2a4 4 0 0 1-4 4" />
                </svg>
                <svg
                    v-else-if="item.icon === 'news'"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5" />
                </svg>
                <svg v-else viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8ZM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"
                    />
                </svg>
                <span>{{ item.label }}</span>
            </Link>
        </nav>
    </Teleport>
</template>

<style scoped>
.shell {
    width: min(calc(100% - 80px), var(--content-max));
    margin-inline: auto;
}
.site-header {
    position: relative;
    z-index: 50;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.site-header--solid {
    background: rgba(5, 9, 15, 0.96);
    backdrop-filter: blur(16px);
}
.navbar {
    height: 108px;
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
}
.brand {
    width: max-content;
    display: flex;
    align-items: center;
    gap: 11px;
    color: var(--color-paper);
    font: 400 clamp(1.55rem, 2.3vw, 2.3rem) / 1 var(--font-brand);
    letter-spacing: -0.09em;
    text-decoration: none;
}
.brand img {
    width: 39px;
    height: 39px;
    border-radius: 50%;
    object-fit: cover;
}
.brand span {
    color: var(--color-accent);
}
.desktop-links {
    display: flex;
    align-items: center;
    gap: 43px;
}
.desktop-links a {
    position: relative;
    padding: 15px 1px;
    color: #d2d9e0;
    font: 700 0.83rem var(--font-display);
    letter-spacing: 0.06em;
    text-decoration: none;
    text-transform: uppercase;
    transition: color 0.2s;
}
.desktop-links a:hover,
.desktop-links a.active {
    color: var(--color-accent);
}
.desktop-links a.active::after {
    content: "";
    position: absolute;
    right: 0;
    bottom: 3px;
    left: 0;
    height: 2px;
    background: var(--color-accent);
    filter: drop-shadow(0 0 7px rgba(69, 183, 255, 0.5));
}
.nav-action {
    justify-self: end;
}
.login-link {
    display: inline-flex;
    min-height: 48px;
    align-items: center;
    gap: 18px;
    padding: 0 20px;
    border: 1px solid rgba(255, 255, 255, 0.34);
    border-radius: 8px;
    color: var(--color-paper);
    font: 700 0.82rem var(--font-display);
    letter-spacing: 0.07em;
    text-decoration: none;
    text-transform: uppercase;
    transition: 0.2s;
}
.login-link:hover {
    color: var(--color-accent);
    border-color: var(--color-accent);
}
.login-link svg {
    width: 20px;
    stroke-width: 1.8;
}
.menu-button {
    display: none;
    width: 46px;
    height: 46px;
    place-items: center;
    border: 1px solid var(--color-line);
    border-radius: 8px;
    color: white;
    background: rgba(8, 14, 21, 0.65);
    cursor: pointer;
}
.menu-button svg {
    width: 22px;
    stroke-width: 2;
}
.mobile-bottom-nav {
    display: none;
}

@media (max-width: 900px) and (min-width: 521px) {
    .shell {
        width: min(calc(100% - 40px), var(--content-max));
    }
    .navbar {
        grid-template-columns: 1fr auto;
    }
    .nav-action {
        display: none;
    }
    .menu-button {
        display: grid;
        justify-self: end;
    }
    .desktop-links {
        position: absolute;
        top: 108px;
        right: 0;
        left: 0;
        visibility: hidden;
        flex-direction: column;
        align-items: stretch;
        gap: 0;
        padding: 16px 20px 24px;
        border-bottom: 1px solid var(--color-line);
        background: rgba(5, 9, 15, 0.97);
        opacity: 0;
        transform: translateY(-10px);
        transition: 0.2s;
    }
    .desktop-links.open {
        visibility: visible;
        opacity: 1;
        transform: translateY(0);
    }
    .desktop-links a {
        padding: 15px 4px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .desktop-links a.active::after {
        display: none;
    }
}

@media (max-width: 520px) {
    .shell {
        width: calc(100% - 32px);
    }
    .navbar {
        height: 78px;
        grid-template-columns: 1fr auto;
    }
    .brand {
        font-size: 1.35rem;
    }
    .brand img {
        width: 34px;
        height: 34px;
    }
    .desktop-links,
    .menu-button {
        display: none;
    }
    .nav-action {
        display: block;
        justify-self: end;
    }
    .login-link {
        min-height: 40px;
        gap: 9px;
        padding-inline: 14px;
        font-size: 0.68rem;
    }
    .login-link svg {
        width: 17px;
    }
    .mobile-bottom-nav {
        position: fixed;
        z-index: 800;
        right: 0;
        bottom: 0;
        left: 0;
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        width: 100%;
        min-height: calc(68px + env(safe-area-inset-bottom, 0px));
        padding: 6px 10px max(6px, env(safe-area-inset-bottom, 0px));
        border-top: 1px solid var(--color-line);
        background: rgba(5, 9, 15, 0.97);
        box-shadow: 0 -12px 34px rgba(0, 0, 0, 0.34);
        backface-visibility: hidden;
        transform: translate3d(0, 0, 0);
    }
    .mobile-bottom-nav a {
        position: relative;
        display: flex;
        min-width: 0;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 4px;
        border-radius: 8px;
        color: #7f8d9b;
        font: 700 0.56rem var(--font-display);
        letter-spacing: 0.09em;
        text-decoration: none;
        text-transform: uppercase;
        touch-action: manipulation;
        transition: 0.2s;
    }
    .mobile-bottom-nav a:hover,
    .mobile-bottom-nav a.active {
        color: var(--color-accent);
        background: var(--color-accent-soft);
    }
    .mobile-bottom-nav a:active {
        transform: scale(0.95);
    }
    .mobile-bottom-nav a.active::before {
        content: "";
        position: absolute;
        top: -6px;
        left: 50%;
        width: 28px;
        height: 2px;
        border-radius: 0 0 4px 4px;
        background: var(--color-accent);
        box-shadow: 0 0 10px rgba(69, 183, 255, 0.7);
        transform: translateX(-50%);
    }
    .mobile-bottom-nav svg {
        width: 21px;
        height: 21px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
}
</style>
