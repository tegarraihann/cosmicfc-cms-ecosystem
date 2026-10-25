<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";

const page = usePage();
const sidebarOpen = ref(false);
const user = computed(() => page.props.auth.user);
const roleValue = computed(() =>
    typeof user.value?.role === "string"
        ? user.value.role
        : user.value?.role?.value,
);
const roleLabel = computed(() =>
    roleValue.value === "superadmin"
        ? "Super Admin"
        : roleValue.value === "admin"
          ? "Administrator"
          : "Member",
);
const can = (permission) =>
    user.value?.permissions?.includes("*") ||
    user.value?.permissions?.includes(permission);
const initials = computed(
    () =>
        user.value?.name
            ?.split(" ")
            .map((part) => part[0])
            .slice(0, 2)
            .join("")
            .toUpperCase() || "CS",
);

const navigation = computed(() => [
    {
        label: "Dashboard",
        href: route("dashboard"),
        active: route().current("dashboard"),
        icon: "dashboard",
    },
    ...(can("landing.view")
        ? [
              {
                  label: "Landing Page",
                  href: route("admin.pages.index"),
                  active: route().current("admin.pages.*"),
                  icon: "layout",
              },
          ]
        : []),
    ...(can("club-profile.view")
        ? [
              {
                  label: "Pengaturan Website",
                  href: route("admin.club-profile.edit"),
                  active: route().current("admin.club-profile.*"),
                  icon: "club",
              },
          ]
        : []),
    ...(can("matches.view")
        ? [
              {
                  label: "Kelola Pertandingan",
                  href: route("admin.matches.index"),
                  active: route().current("admin.matches.*"),
                  icon: "calendar",
              },
          ]
        : []),
    ...(can("players.view")
        ? [
              {
                  label: "Pemain",
                  href: route("admin.players.index"),
                  active: route().current("admin.players.*"),
                  icon: "users",
              },
          ]
        : []),
    ...(can("staff.view")
        ? [
              {
                  label: "Staff & Officials",
                  href: route("admin.staff.index"),
                  active: route().current("admin.staff.*"),
                  icon: "users",
              },
          ]
        : []),
    ...(can("clubs.view")
        ? [
              {
                  label: "Klub & Lawan",
                  href: route("admin.clubs.index"),
                  active: route().current("admin.clubs.*"),
                  icon: "club",
              },
          ]
        : []),
    ...(can("competitions.view")
        ? [
              {
                  label: "Kompetisi & Musim",
                  href: route("admin.competitions.index"),
                  active: route().current("admin.competitions.*"),
                  icon: "trophy",
              },
          ]
        : []),
    ...(can("standings.view")
        ? [
              {
                  label: "Klasemen & Statistik",
                  href: route("admin.standings.index"),
                  active: route().current("admin.standings.*"),
                  icon: "trophy",
              },
          ]
        : []),
    ...(can("player-awards.view")
        ? [
              {
                  label: "Player Awards",
                  href: route("admin.player-awards.index"),
                  active: route().current("admin.player-awards.*"),
                  icon: "trophy",
              },
          ]
        : []),
    ...(can("sponsors.view")
        ? [
              {
                  label: "Sponsor & Partners",
                  href: route("admin.sponsors.index"),
                  active: route().current("admin.sponsors.*"),
                  icon: "club",
              },
          ]
        : []),
    ...(can("news.view")
        ? [
              {
                  label: "Berita & Dokumentasi",
                  href: route("admin.news.index"),
                  active: route().current("admin.news.*"),
                  icon: "image",
              },
          ]
        : []),
    ...(can("media.view")
        ? [
              {
                  label: "Media Library",
                  href: route("admin.media.index"),
                  active: route().current("admin.media.*"),
                  icon: "image",
              },
          ]
        : []),
    ...(roleValue.value === "superadmin"
        ? [
              {
                  label: "Pengguna & Role",
                  href: route("admin.access.index"),
                  active: route().current("admin.access.*"),
                  icon: "shield",
              },
          ]
        : []),
]);

watch(
    () => page.url,
    () => {
        sidebarOpen.value = false;
    },
);
onMounted(() => document.body.classList.add("dashboard-body"));
onBeforeUnmount(() => document.body.classList.remove("dashboard-body"));
</script>

<template>
    <div class="admin-shell">
        <button
            v-if="sidebarOpen"
            class="sidebar-backdrop"
            type="button"
            aria-label="Tutup menu"
            @click="sidebarOpen = false"
        ></button>
        <aside :class="['sidebar', { open: sidebarOpen }]">
            <div class="sidebar-brand">
                <Link href="/"
                    ><img src="/Assets/Logo/Logo.jpeg" alt="" /><span
                        >COSMIC<b>.</b></span
                    ></Link
                ><button
                    type="button"
                    aria-label="Tutup sidebar"
                    @click="sidebarOpen = false"
                >
                    ×
                </button>
            </div>
            <div class="workspace-label">
                <span>Admin Workspace</span><strong>Website Management</strong>
            </div>
            <nav class="sidebar-nav" aria-label="Navigasi dashboard">
                <div class="nav-label">Menu Utama</div>
                <template v-for="item in navigation" :key="item.label">
                    <Link
                        v-if="item.href"
                        :href="item.href"
                        :class="['nav-item', { active: item.active }]"
                    >
                        <svg
                            v-if="item.icon === 'dashboard'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <rect x="3" y="3" width="7" height="7" />
                            <rect x="14" y="3" width="7" height="7" />
                            <rect x="3" y="14" width="7" height="7" />
                            <rect x="14" y="14" width="7" height="7" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'image'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <rect x="3" y="4" width="18" height="16" rx="2" />
                            <circle cx="9" cy="10" r="2" />
                            <path d="m3 17 5-5 4 4 3-3 6 6" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'users'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <circle cx="9" cy="8" r="4" />
                            <path
                                d="M2 21v-2a6 6 0 0 1 12 0v2M16 4a4 4 0 0 1 0 8M17 15a6 6 0 0 1 5 6"
                            />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'club'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <path
                                d="M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-3Z"
                            />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                        <svg
                            v-else
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <path
                                d="M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-3Z"
                            />
                        </svg>
                        <span>{{ item.label }}</span>
                    </Link>
                    <button
                        v-else
                        class="nav-item future"
                        type="button"
                        disabled
                    >
                        <svg
                            v-if="item.icon === 'layout'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <rect x="3" y="4" width="18" height="16" rx="2" />
                            <path d="M3 9h18M9 9v11" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'calendar'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <rect x="3" y="5" width="18" height="16" rx="2" />
                            <path d="M8 3v4M16 3v4M3 10h18" />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'users'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <circle cx="9" cy="8" r="4" />
                            <path
                                d="M2 21v-2a6 6 0 0 1 12 0v2M16 4a4 4 0 0 1 0 8M17 15a6 6 0 0 1 5 6"
                            />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'trophy'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <path
                                d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4Z"
                            />
                            <path
                                d="M7 6H4v2a4 4 0 0 0 4 4M17 6h3v2a4 4 0 0 1-4 4"
                            />
                        </svg>
                        <svg
                            v-else-if="item.icon === 'image'"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <rect x="3" y="4" width="18" height="16" rx="2" />
                            <circle cx="9" cy="10" r="2" />
                            <path d="m3 17 5-5 4 4 3-3 6 6" />
                        </svg>
                        <svg
                            v-else
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <path
                                d="M12 3 4 6v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V6l-8-3Z"
                            />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                        <span>{{ item.label }}</span
                        ><small>Segera</small>
                    </button>
                </template>
            </nav>
            <div class="sidebar-bottom">
                <Link class="profile-link" :href="route('profile.edit')"
                    ><span class="avatar">{{ initials }}</span
                    ><span
                        ><strong>{{ user.name }}</strong
                        ><small>{{ roleLabel }}</small></span
                    ><svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="m9 18 6-6-6-6" /></svg></Link
                ><Link
                    class="logout"
                    :href="route('logout')"
                    method="post"
                    as="button"
                    ><svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path
                            d="M10 17l5-5-5-5M15 12H3M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"
                        /></svg
                    >Keluar</Link
                >
            </div>
        </aside>

        <div class="admin-main">
            <header class="topbar">
                <button
                    class="menu-toggle"
                    type="button"
                    aria-label="Buka menu"
                    @click="sidebarOpen = true"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                </button>
                <div class="topbar-title">
                    <span>Cosmic Control Center</span
                    ><strong><slot name="page-title">Dashboard</slot></strong>
                </div>
                <div class="topbar-actions">
                    <Link class="view-site" href="/" target="_blank"
                        >Lihat Website
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                        >
                            <path
                                d="M14 5h5v5M19 5l-8 8M19 13v6H5V5h6"
                            /></svg></Link
                    ><span class="status-dot" title="Sistem aktif"></span>
                </div>
            </header>
            <main class="admin-content"><slot /></main>
        </div>
    </div>
</template>

<style scoped>
:global(body.dashboard-body) {
    padding-bottom: 0;
    background: #05090f;
}
.admin-shell {
    min-height: 100svh;
    color: var(--color-paper);
    background: radial-gradient(
            circle at 90% 0,
            rgba(69, 183, 255, 0.055),
            transparent 27%
        ),
        #05090f;
}
.sidebar {
    position: fixed;
    z-index: 40;
    inset: 0 auto 0 0;
    width: 270px;
    display: flex;
    flex-direction: column;
    border-right: 1px solid rgba(175, 218, 255, 0.14);
    background: #07111e;
}
.sidebar-brand {
    height: 82px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 23px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.sidebar-brand > a {
    display: flex;
    align-items: center;
    gap: 10px;
    color: white;
    font: 900 1.45rem var(--font-brand);
    letter-spacing: -0.09em;
    text-decoration: none;
}
.sidebar-brand img {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    object-fit: cover;
}
.sidebar-brand b {
    color: var(--color-accent);
}
.sidebar-brand > button {
    display: none;
    border: 0;
    color: white;
    background: none;
    font-size: 1.6rem;
}
.workspace-label {
    display: grid;
    gap: 5px;
    padding: 22px 23px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.workspace-label span,
.nav-label {
    color: var(--color-accent);
    font: 800 0.55rem var(--font-display);
    letter-spacing: 0.18em;
    text-transform: uppercase;
}
.workspace-label strong {
    font: 700 0.72rem var(--font-display);
    letter-spacing: 0.07em;
    text-transform: uppercase;
}
.sidebar-nav {
    flex: 1;
    padding: 22px 14px;
    overflow-y: auto;
}
.nav-label {
    padding: 0 10px 10px;
    color: #647583;
}
.nav-item {
    width: 100%;
    min-height: 45px;
    display: flex;
    align-items: center;
    gap: 13px;
    margin-bottom: 4px;
    padding: 0 12px;
    border: 1px solid transparent;
    border-radius: 5px;
    color: #93a3b0;
    background: transparent;
    font: 750 0.67rem var(--font-display);
    letter-spacing: 0.09em;
    text-align: left;
    text-decoration: none;
    text-transform: uppercase;
    transition: 0.2s;
}
.nav-item svg {
    flex: 0 0 18px;
    width: 18px;
    stroke-width: 1.7;
}
.nav-item.active {
    color: white;
    border-color: rgba(69, 183, 255, 0.22);
    background: linear-gradient(
        90deg,
        rgba(69, 183, 255, 0.15),
        rgba(69, 183, 255, 0.035)
    );
}
.nav-item.active svg {
    color: var(--color-accent);
}
.nav-item.future {
    cursor: not-allowed;
    opacity: 0.62;
}
.nav-item small {
    margin-left: auto;
    color: #536470;
    font-size: 0.48rem;
    letter-spacing: 0.1em;
}
.sidebar-bottom {
    padding: 14px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}
.profile-link {
    display: grid;
    grid-template-columns: 38px 1fr 17px;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 5px;
    color: white;
    text-decoration: none;
    transition: background 0.2s;
}
.profile-link:hover {
    background: rgba(255, 255, 255, 0.035);
}
.avatar {
    display: grid;
    width: 38px;
    height: 38px;
    place-items: center;
    border: 1px solid rgba(69, 183, 255, 0.38);
    border-radius: 50%;
    color: var(--color-accent);
    background: rgba(69, 183, 255, 0.08);
    font: 850 0.7rem var(--font-display);
}
.profile-link strong,
.profile-link small {
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.profile-link strong {
    font: 750 0.68rem var(--font-display);
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.profile-link small {
    margin-top: 4px;
    color: #728390;
    font: 650 0.52rem var(--font-display);
    letter-spacing: 0.1em;
    text-transform: uppercase;
}
.profile-link > svg {
    width: 16px;
    color: #647583;
}
.logout {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 5px;
    padding: 10px;
    border: 0;
    color: #7f8f9b;
    background: transparent;
    font: 700 0.6rem var(--font-display);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    cursor: pointer;
}
.logout:hover {
    color: #ff8b93;
}
.logout svg {
    width: 17px;
}
.admin-main {
    min-height: 100svh;
    margin-left: 270px;
}
.topbar {
    position: sticky;
    z-index: 20;
    top: 0;
    height: 82px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 34px;
    border-bottom: 1px solid rgba(175, 218, 255, 0.12);
    background: rgba(5, 9, 15, 0.88);
    backdrop-filter: blur(12px);
}
.menu-toggle {
    display: none;
    width: 42px;
    height: 42px;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 5px;
    color: white;
    background: transparent;
}
.menu-toggle svg {
    width: 20px;
}
.topbar-title {
    display: grid;
    gap: 3px;
}
.topbar-title span {
    color: #647583;
    font: 700 0.52rem var(--font-display);
    letter-spacing: 0.16em;
    text-transform: uppercase;
}
.topbar-title strong {
    font: 850 1.25rem var(--font-display);
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.topbar-actions {
    display: flex;
    align-items: center;
    gap: 17px;
}
.view-site {
    display: flex;
    align-items: center;
    gap: 9px;
    color: #aebbc5;
    font: 750 0.61rem var(--font-display);
    letter-spacing: 0.1em;
    text-decoration: none;
    text-transform: uppercase;
}
.view-site:hover {
    color: var(--color-accent);
}
.view-site svg {
    width: 16px;
}
.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--color-accent);
    box-shadow: 0 0 11px rgba(69, 183, 255, 0.7);
}
.admin-content {
    padding: 32px;
}
.sidebar-backdrop {
    display: none;
}
@media (max-width: 900px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.25s;
    }
    .sidebar.open {
        transform: translateX(0);
    }
    .sidebar-brand > button,
    .menu-toggle {
        display: grid;
    }
    .sidebar-backdrop {
        position: fixed;
        z-index: 35;
        inset: 0;
        display: block;
        border: 0;
        background: rgba(1, 5, 10, 0.72);
        backdrop-filter: blur(3px);
    }
    .admin-main {
        margin-left: 0;
    }
    .topbar {
        padding: 0 20px;
    }
    .topbar-title {
        margin-right: auto;
        margin-left: 13px;
    }
    .admin-content {
        padding: 24px 20px;
    }
}
@media (max-width: 520px) {
    .topbar {
        height: 72px;
        padding: 0 16px;
    }
    .topbar-title span,
    .view-site span {
        display: none;
    }
    .view-site {
        font-size: 0;
    }
    .view-site svg {
        width: 20px;
    }
    .admin-content {
        padding: 22px 16px;
    }
    .sidebar {
        width: min(290px, 86vw);
    }
}
</style>
