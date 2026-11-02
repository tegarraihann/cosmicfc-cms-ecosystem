<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
const props = defineProps({
    users: Array,
    roles: Array,
    permissions: Object,
    currentUserId: Number,
});
const page = usePage(),
    tab = ref("users"),
    userEditor = ref(false),
    roleEditor = ref(false),
    editingUser = ref(null),
    editingRole = ref(null);
const groups = computed(() => Object.entries(props.permissions || {}));
const userForm = useForm({
    name: "",
    email: "",
    role_id: "",
    password: "",
    password_confirmation: "",
    is_active: true,
});
const roleForm = useForm({
    name: "",
    description: "",
    is_active: true,
    permissions: [],
});
function openUser(user = null) {
    editingUser.value = user;
    userForm.clearErrors();
    userForm
        .defaults({
            name: user?.name || "",
            email: user?.email || "",
            role_id: user?.role_id || "",
            password: "",
            password_confirmation: "",
            is_active: user?.is_active ?? true,
        })
        .reset();
    userEditor.value = true;
}
function saveUser() {
    const o = {
        preserveScroll: true,
        onSuccess: () => (userEditor.value = false),
    };
    userForm.transform((data) => ({
        ...data,
        is_active: Boolean(data.is_active),
    }));
    editingUser.value
        ? userForm.put(
              route("admin.access.users.update", editingUser.value.id),
              o,
          )
        : userForm.post(route("admin.access.users.store"), o);
}
function removeUser(user) {
    if (confirm(`Hapus akun ${user.name}?`))
        router.delete(route("admin.access.users.destroy", user.id), {
            preserveScroll: true,
        });
}
function resendInvitation(user) {
    router.post(route('admin.access.users.invitation.resend', user.id), {}, {
        preserveScroll: true,
    });
}
function accountStatus(user) {
    return {
        active: 'Aktif & terverifikasi',
        inactive: 'Nonaktif',
        pending: 'Menunggu aktivasi',
        expired: 'Undangan kedaluwarsa',
    }[user.invitation_status] || 'Status tidak diketahui';
}
function openRole(role = null) {
    editingRole.value = role;
    roleForm.clearErrors();
    roleForm
        .defaults({
            name: role?.name || "",
            description: role?.description || "",
            is_active: role?.is_active ?? true,
            permissions: role?.permissions?.map((p) => p.id) || [],
        })
        .reset();
    roleEditor.value = true;
}
function saveRole() {
    const o = {
        preserveScroll: true,
        onSuccess: () => (roleEditor.value = false),
    };
    roleForm.transform((data) => ({
        ...data,
        is_active: Boolean(data.is_active),
        permissions: [...new Set(data.permissions.map(Number))],
    }));
    editingRole.value
        ? roleForm.put(
              route("admin.access.roles.update", editingRole.value.id),
              o,
          )
        : roleForm.post(route("admin.access.roles.store"), o);
}
function groupPermissionIds(items) {
    return items.map((permission) => Number(permission.id));
}
function isGroupSelected(items) {
    const ids = groupPermissionIds(items);
    return (
        ids.length > 0 &&
        ids.every((id) => roleForm.permissions.map(Number).includes(id))
    );
}
function togglePermissionGroup(items) {
    const ids = groupPermissionIds(items);
    const selected = new Set(roleForm.permissions.map(Number));
    if (isGroupSelected(items)) {
        ids.forEach((id) => selected.delete(id));
    } else {
        ids.forEach((id) => selected.add(id));
    }
    roleForm.permissions = [...selected];
}
function removeRole(role) {
    if (confirm(`Hapus role ${role.name}?`))
        router.delete(route("admin.access.roles.destroy", role.id), {
            preserveScroll: true,
        });
}
</script>
<template>
    <Head title="Pengguna & Role" /><AuthenticatedLayout
        ><template #page-title>Pengguna & Role</template>
        <div class="access-page">
            <div v-if="page.props.flash?.success" class="notice">
                {{ page.props.flash.success }}
            </div>
            <header class="hero">
                <div>
                    <span>Access Control</span>
                    <h1>Kelola Akses<b>.</b></h1>
                    <p>
                        Atur akun administrator, role, dan izin setiap bagian
                        website.
                    </p>
                </div>
                <button
                    class="primary"
                    @click="tab === 'users' ? openUser() : openRole()"
                >
                    + Tambah {{ tab === "users" ? "Pengguna" : "Role" }}
                </button>
            </header>
            <nav class="tabs">
                <button
                    :class="{ active: tab === 'users' }"
                    @click="tab = 'users'"
                >
                    Pengguna <b>{{ users.length }}</b></button
                ><button
                    :class="{ active: tab === 'roles' }"
                    @click="tab = 'roles'"
                >
                    Role & Permission <b>{{ roles.length }}</b>
                </button>
            </nav>
            <section v-if="tab === 'users'" class="panel">
                <header>
                    <div>
                        <span>Accounts</span>
                        <h2>Daftar Pengguna</h2>
                    </div>
                    <small
                        >{{ users.filter((u) => u.is_active).length }} akun
                        aktif</small
                    >
                </header>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Pengguna</th>
                                <th>Role</th>
                                <th>Status akun</th>
                                <th>Login terakhir</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users" :key="user.id">
                                <td>
                                    <strong>{{ user.name }}</strong
                                    ><small>{{ user.email }}</small>
                                </td>
                                <td>
                                    <i>{{ user.role }}</i>
                                </td>
                                <td>
                                    <span
                                        :class="[
                                            'status',
                                            user.invitation_status === 'active' ? 'on' : 'off',
                                        ]"
                                        >{{
                                            accountStatus(user)
                                        }}</span
                                    >
                                </td>
                                <td>
                                    {{ user.last_login_at || "Belum pernah" }}
                                </td>
                                <td class="actions">
                                    <button @click="openUser(user)">Edit</button
                                    ><button
                                        v-if="['pending', 'expired'].includes(user.invitation_status)"
                                        @click="resendInvitation(user)"
                                    >Kirim Ulang Undangan</button
                                    ><button
                                        v-if="
                                            user.id !== currentUserId &&
                                            user.role_slug !== 'superadmin'
                                        "
                                        class="danger"
                                        @click="removeUser(user)"
                                    >
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
            <section v-else class="cards">
                <article v-for="role in roles" :key="role.id">
                    <header>
                        <div>
                            <span>{{
                                role.is_system ? "System Role" : "Custom Role"
                            }}</span>
                            <h2>{{ role.name }}</h2>
                        </div>
                        <span
                            :class="['status', role.is_active ? 'on' : 'off']"
                            >{{ role.is_active ? "Aktif" : "Nonaktif" }}</span
                        >
                    </header>
                    <p>{{ role.description || "Tanpa deskripsi." }}</p>
                    <div class="meta">
                        <strong>{{ role.permissions.length }}</strong
                        ><small>Permission</small
                        ><strong>{{ role.users_count }}</strong
                        ><small>Pengguna</small>
                    </div>
                    <footer>
                        <button
                            :disabled="role.slug === 'superadmin'"
                            @click="openRole(role)"
                        >
                            Kelola Akses</button
                        ><button
                            v-if="!role.is_system && !role.users_count"
                            class="danger"
                            @click="removeRole(role)"
                        >
                            Hapus
                        </button>
                    </footer>
                </article>
            </section>
        </div>
        <div v-if="userEditor" class="modal" @click.self="userEditor = false">
            <form class="dialog" @submit.prevent="saveUser">
                <header>
                    <div>
                        <span>User Account</span>
                        <h2>{{ editingUser ? "Edit" : "Tambah" }} Pengguna</h2>
                    </div>
                    <button type="button" @click="userEditor = false">×</button>
                </header>
                <div class="fields">
                    <label
                        >Nama<input v-model="userForm.name" required /><small>{{
                            userForm.errors.name
                        }}</small></label
                    ><label
                        >Email<input
                            v-model="userForm.email"
                            type="email"
                            :disabled="Boolean(editingUser)"
                            required
                        /><small>{{ userForm.errors.email || (editingUser ? 'Email hanya dapat diubah dan diverifikasi oleh pemilik akun melalui halaman Profil.' : '') }}</small></label
                    ><label
                        >Role<select v-model="userForm.role_id" required>
                            <option value="" disabled>Pilih role</option>
                            <option
                                v-for="role in roles.filter((r) => r.is_active && (editingUser || r.slug !== 'superadmin'))"
                                :key="role.id"
                                :value="role.id"
                            >
                                {{ role.name }}
                            </option></select
                        ><small>{{ userForm.errors.role_id }}</small></label
                    ><label v-if="editingUser"
                        >Password <em>{{ editingUser ? "(opsional)" : "" }}</em
                        ><input
                            v-model="userForm.password"
                            type="password"
                            :required="false"
                        /><small>{{ userForm.errors.password }}</small></label
                    ><label v-if="editingUser"
                        >Konfirmasi Password<input
                            v-model="userForm.password_confirmation"
                            type="password"
                            :required="false" /></label
                    ><div v-if="!editingUser" class="invitation-info">
                        <b>Aktivasi melalui email</b>
                        <small>Pengguna akan menerima undangan untuk memverifikasi email dan menentukan password sendiri. Tautan berlaku selama 24 jam.</small>
                    </div
                    ><label class="check account-active"
                        ><input v-model="userForm.is_active" type="checkbox" />
                        <span class="checkbox-mark" aria-hidden="true">✓</span
                        ><span
                            ><b>Akun aktif</b
                            ><small
                                >Pengguna dapat masuk dan menggunakan dashboard
                                sesuai role.</small
                            ></span
                        ></label
                    >
                </div>
                <footer>
                    <button type="button" @click="userEditor = false">
                        Batal</button
                    ><button class="primary" :disabled="userForm.processing">
                        {{ editingUser ? 'Simpan Perubahan' : 'Kirim Undangan' }}
                    </button>
                </footer>
            </form>
        </div>
        <div v-if="roleEditor" class="modal" @click.self="roleEditor = false">
            <form class="dialog wide" @submit.prevent="saveRole">
                <header>
                    <div>
                        <span>Role Access</span>
                        <h2>{{ editingRole ? "Edit" : "Tambah" }} Role</h2>
                    </div>
                    <button type="button" @click="roleEditor = false">×</button>
                </header>
                <div class="fields">
                    <label
                        >Nama Role<input
                            v-model="roleForm.name"
                            required
                        /><small>{{ roleForm.errors.name }}</small></label
                    ><label
                        >Deskripsi<textarea
                            v-model="roleForm.description"
                            rows="3"
                        /></label
                    ><label v-if="editingRole" class="check role-active"
                        ><input
                            v-model="roleForm.is_active"
                            type="checkbox"
                        /><span class="checkbox-mark" aria-hidden="true">✓</span
                        ><span
                            ><b>Role aktif</b
                            ><small
                                >Role dapat dipilih dan digunakan oleh
                                pengguna.</small
                            ></span
                        ></label
                    >
                </div>
                <div class="permissions">
                    <div class="permissions-title">
                        <div>
                            <h3>Hak Akses</h3>
                            <p>
                                Pilih fungsi yang boleh digunakan oleh role ini.
                            </p>
                        </div>
                        <strong
                            >{{ roleForm.permissions.length }} dipilih</strong
                        >
                    </div>
                    <section v-for="[group, items] in groups" :key="group">
                        <div class="permission-group">
                            <strong>{{ group }}</strong
                            ><button
                                type="button"
                                @click="togglePermissionGroup(items)"
                            >
                                {{
                                    isGroupSelected(items)
                                        ? "Kosongkan"
                                        : "Pilih semua"
                                }}
                            </button>
                        </div>
                        <div>
                            <label
                                v-for="permission in items"
                                :key="permission.id"
                                class="check permission-check"
                                ><input
                                    v-model="roleForm.permissions"
                                    type="checkbox"
                                    :value="permission.id"
                                /><span class="checkbox-mark" aria-hidden="true"
                                    >✓</span
                                ><span>{{ permission.name }}</span></label
                            >
                        </div>
                    </section>
                </div>
                <footer>
                    <button type="button" @click="roleEditor = false">
                        Batal</button
                    ><button class="primary" :disabled="roleForm.processing">
                        Simpan Role
                    </button>
                </footer>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
<style scoped>
.access-page {
    max-width: 1440px;
    margin: auto;
}
.notice {
    margin-bottom: 14px;
    padding: 13px 16px;
    border: 1px solid #45b7ff66;
    color: #aadaff;
    background: #45b7ff14;
    font-size: 0.72rem;
}
.hero {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 30px;
    padding: 34px 38px;
    border: 1px solid #afdaff24;
    background:
        linear-gradient(110deg, #07111e, #07111eb8),
        url("/Assets/Dokumentasi/Docs-wahyu.jpeg") center 45% / cover;
}
.hero span,
.panel > header span,
.dialog header span {
    color: var(--color-accent);
    font: 800 0.57rem var(--font-display);
    letter-spacing: 0.18em;
    text-transform: uppercase;
}
.hero h1 {
    margin: 10px 0 8px;
    font: 900 clamp(2.9rem, 5vw, 5rem) / 0.85 var(--font-display);
    text-transform: uppercase;
}
.hero h1 b {
    color: var(--color-accent);
}
.hero p {
    margin: 0;
    color: #8899a5;
    font-size: 0.72rem;
}
.primary {
    border-color: var(--color-accent) !important;
    color: #03101b !important;
    background: var(--color-accent) !important;
}
.hero button,
.dialog footer button {
    min-height: 44px;
    padding: 0 20px;
    border: 1px solid #ffffff2b;
    border-radius: 4px;
    color: white;
    background: transparent;
    font: 800 0.6rem var(--font-display);
    letter-spacing: 0.09em;
    text-transform: uppercase;
    cursor: pointer;
}
.tabs {
    display: flex;
    margin-top: 16px;
    border-bottom: 1px solid #ffffff1f;
}
.tabs button {
    padding: 17px 22px;
    border: 0;
    border-bottom: 2px solid transparent;
    color: #788995;
    background: none;
    font: 800 0.65rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
    cursor: pointer;
}
.tabs button.active {
    border-color: var(--color-accent);
    color: white;
}
.tabs b {
    margin-left: 7px;
    color: var(--color-accent);
}
.panel {
    margin-top: 16px;
    border: 1px solid #afdaff21;
    background: #07111e;
}
.panel > header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 23px;
    border-bottom: 1px solid #ffffff17;
}
.panel h2 {
    margin: 5px 0 0;
    font: 850 1.2rem var(--font-display);
    text-transform: uppercase;
}
.panel header small {
    color: #70818d;
}
.table-wrap {
    overflow: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th,
td {
    padding: 16px 20px;
    border-bottom: 1px solid #ffffff13;
    text-align: left;
    font-size: 0.65rem;
    white-space: nowrap;
}
th {
    color: #667783;
    font: 750 0.54rem var(--font-display);
    letter-spacing: 0.12em;
    text-transform: uppercase;
}
td strong,
td small {
    display: block;
}
td strong {
    font: 750 0.67rem var(--font-display);
    text-transform: uppercase;
}
td small {
    margin-top: 5px;
    color: #748591;
}
td i {
    padding: 6px 9px;
    border: 1px solid #45b7ff38;
    color: #aadaff;
    background: #45b7ff12;
    font: 750 0.54rem var(--font-display);
    font-style: normal;
    text-transform: uppercase;
}
.status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #71828e;
    font: 750 0.55rem var(--font-display);
    text-transform: uppercase;
}
.status:before {
    content: "";
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #6b7680;
}
.status.on:before {
    background: var(--color-accent);
    box-shadow: 0 0 8px #45b7ff8c;
}
.actions {
    text-align: right;
}
.actions button,
.cards footer button {
    padding: 7px 9px;
    border: 0;
    color: var(--color-accent);
    background: none;
    font: 750 0.55rem var(--font-display);
    text-transform: uppercase;
    cursor: pointer;
}
.danger {
    color: #ed7a83 !important;
}
.cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    margin-top: 16px;
}
.cards > article {
    display: flex;
    min-height: 250px;
    flex-direction: column;
    padding: 22px;
    border: 1px solid #afdaff24;
    background: #07111e;
}
.cards article > header {
    display: flex;
    justify-content: space-between;
}
.cards header div > span {
    color: var(--color-accent);
    font: 750 0.5rem var(--font-display);
    letter-spacing: 0.14em;
}
.cards h2 {
    margin: 8px 0 0;
    font: 850 1.35rem var(--font-display);
    text-transform: uppercase;
}
.cards p {
    color: #788a96;
    font-size: 0.66rem;
}
.meta {
    display: grid;
    grid-template-columns: auto 1fr auto 1fr;
    align-items: end;
    gap: 7px;
    margin-top: auto;
    padding: 17px 0;
    border-top: 1px solid #ffffff14;
}
.meta strong {
    font: 850 1.4rem var(--font-display);
}
.meta small {
    color: #627481;
    text-transform: uppercase;
}
.cards footer {
    display: flex;
    justify-content: space-between;
}
.cards button:disabled {
    color: #52616b;
    cursor: not-allowed;
}
.modal {
    position: fixed;
    z-index: 100;
    inset: 0;
    display: grid;
    place-items: center;
    padding: 20px;
    background: #01050ad6;
    backdrop-filter: blur(5px);
}
.dialog {
    width: min(560px, 100%);
    max-height: 90vh;
    overflow: auto;
    border: 1px solid #45b7ff40;
    background: #07111e;
    box-shadow: 0 30px 80px #000;
}
.dialog.wide {
    width: min(850px, 100%);
}
.dialog > header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #ffffff1a;
}
.dialog h2 {
    margin: 5px 0 0;
    font: 850 1.35rem var(--font-display);
    text-transform: uppercase;
}
.dialog header button {
    border: 0;
    color: #8c9aa5;
    background: none;
    font-size: 1.5rem;
}
.fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    padding: 22px 24px;
}
.fields label {
    color: #8d9ca7;
    font: 750 0.56rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.fields input,
.fields select,
.fields textarea {
    width: 100%;
    box-sizing: border-box;
    margin-top: 8px;
    padding: 12px;
    border: 1px solid #ffffff24;
    color: white;
    background: #050b13;
}
.fields small {
    display: block;
    color: #ef7c85;
}
.fields em {
    font-style: normal;
    color: #586a77;
}
.check {
    display: flex !important;
    align-items: center;
    gap: 8px;
}
.check input {
    width: auto !important;
    margin: 0 !important;
    accent-color: var(--color-accent);
}
.permissions {
    padding: 0 24px 20px;
}
.permissions h3 {
    font: 850 0.9rem var(--font-display);
    text-transform: uppercase;
}
.permissions section {
    display: grid;
    grid-template-columns: 150px 1fr;
    gap: 14px;
    padding: 14px 0;
    border-top: 1px solid #ffffff14;
}
.permissions section > strong {
    font: 750 0.58rem var(--font-display);
    text-transform: uppercase;
}
.permissions section > div {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 11px;
}
.permissions .check {
    color: #9aa9b4;
    font-size: 0.62rem;
}
.dialog > footer {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    padding: 16px 24px;
    border-top: 1px solid #ffffff1a;
}
@media (max-width: 1050px) {
    .cards {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 700px) {
    .hero {
        align-items: flex-start;
        flex-direction: column;
        padding: 27px 22px;
    }
    .hero button {
        width: 100%;
    }
    .cards {
        grid-template-columns: 1fr;
    }
    .fields {
        grid-template-columns: 1fr;
    }
    .permissions section {
        grid-template-columns: 1fr;
    }
    .permissions section > div {
        grid-template-columns: 1fr;
    }
    .tabs button {
        flex: 1;
        padding: 15px 8px;
    }
}
.permissions-title {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 20px;
}
.permissions-title h3 {
    margin-bottom: 5px;
}
.permissions-title p {
    margin: 0;
    color: #70818d;
    font-size: 0.62rem;
}
.permissions-title > strong {
    color: var(--color-accent);
    font: 750 0.56rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.permissions section > .permission-group {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.permission-group > strong {
    font: 750 0.58rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.permission-group > button {
    padding: 4px 0;
    border: 0;
    color: #8fcfff;
    background: transparent;
    font: 750 0.5rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
    cursor: pointer;
}
.permission-group > button:hover {
    color: white;
}
.account-active,
.role-active,
.permission-check {
    position: relative;
    min-height: 34px;
    align-items: flex-start !important;
    cursor: pointer;
    text-transform: none !important;
}
.account-active input,
.role-active input,
.permission-check input {
    position: absolute;
    width: 1px !important;
    height: 1px;
    margin: 0 !important;
    padding: 0 !important;
    opacity: 0;
}
.checkbox-mark {
    display: grid;
    flex: 0 0 18px;
    width: 18px;
    height: 18px;
    box-sizing: border-box;
    place-items: center;
    border: 1px solid #536777;
    border-radius: 3px;
    color: transparent;
    background: #050b13;
    font: 900 0.72rem/1 Arial;
    transition:
        border-color 0.18s,
        background 0.18s,
        color 0.18s,
        box-shadow 0.18s;
}
.account-active input:checked + .checkbox-mark,
.role-active input:checked + .checkbox-mark,
.permission-check input:checked + .checkbox-mark {
    border-color: var(--color-accent);
    color: #03101b;
    background: var(--color-accent);
    box-shadow: 0 0 0 2px rgba(69, 183, 255, 0.12);
}
.account-active input:focus-visible + .checkbox-mark,
.role-active input:focus-visible + .checkbox-mark,
.permission-check input:focus-visible + .checkbox-mark {
    outline: 2px solid white;
    outline-offset: 2px;
}
.account-active {
    grid-column: 1/-1;
}
.account-active > span:last-child,
.role-active > span:last-child {
    display: grid;
    gap: 4px;
}
.account-active b,
.role-active b {
    color: #dce7ef;
    font: 750 0.61rem var(--font-display);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.account-active small,
.role-active small {
    margin: 0 !important;
    color: #6f8290 !important;
    font: 500 0.58rem/1.4 Arial;
    text-transform: none;
}
.permission-check > span:last-child {
    padding-top: 2px;
    line-height: 1.35;
}
@media (max-width: 700px) {
    .permissions-title {
        align-items: flex-start;
        flex-direction: column;
        gap: 8px;
    }
}
</style>
