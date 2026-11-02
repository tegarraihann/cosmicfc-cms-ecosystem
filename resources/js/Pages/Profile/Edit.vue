<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    pendingEmail: String,
    pendingEmailExpiresAt: String,
    mustChangeEmail: Boolean,
});

const user = usePage().props.auth.user;
const initials = computed(() => user.name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join(''));
</script>

<template>
    <Head title="Profil & Keamanan" />

    <AuthenticatedLayout>
        <template #page-title>Profil & Keamanan</template>

        <div class="profile-page">
            <header class="profile-hero">
                <div>
                    <span>Pengaturan Akun</span>
                    <h1>PROFIL & <b>KEAMANAN.</b></h1>
                    <p>Kelola identitas, email login, dan keamanan akun Anda.</p>
                </div>
                <div class="identity-summary">
                    <div class="avatar">{{ initials }}</div>
                    <div>
                        <strong>{{ user.name }}</strong>
                        <small>{{ user.role_name }}</small>
                    </div>
                </div>
            </header>

            <section class="account-overview">
                <article>
                    <span>Email aktif</span>
                    <strong>{{ user.email }}</strong>
                    <small>Email yang digunakan untuk login.</small>
                </article>
                <article>
                    <span>Status verifikasi</span>
                    <strong :class="user.email_verified_at ? 'verified' : 'pending'">
                        {{ user.email_verified_at ? 'Terverifikasi' : 'Belum terverifikasi' }}
                    </strong>
                    <small>Menjaga kepemilikan alamat email akun.</small>
                </article>
                <article>
                    <span>Status akun</span>
                    <strong class="verified">Aktif</strong>
                    <small>Akun dapat mengakses dashboard sesuai role.</small>
                </article>
            </section>

            <div class="profile-grid">
                <article class="profile-card">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        :pending-email="pendingEmail"
                        :pending-email-expires-at="pendingEmailExpiresAt"
                        :must-change-email="mustChangeEmail"
                    />
                </article>

                <article class="profile-card">
                    <UpdatePasswordForm />
                </article>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.profile-page{padding:2rem;background:#050d17;min-height:calc(100vh - 96px);color:#f4f8fc}.profile-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:2rem;padding:2rem;border:1px solid #1b3144;background:linear-gradient(135deg,#081727,#0b2032)}.profile-hero>div:first-child>span,.account-overview span{color:#42b5ff;font-size:.72rem;font-weight:800;letter-spacing:.16em;text-transform:uppercase}.profile-hero h1{margin:.55rem 0 .35rem;font-family:Impact,"Arial Narrow",sans-serif;font-size:clamp(2.3rem,4vw,4.5rem);line-height:.95;letter-spacing:.02em}.profile-hero h1 b{color:#45b7ff}.profile-hero p{margin:0;color:#8aa5ba;font-size:1rem}.identity-summary{display:flex;align-items:center;gap:1rem;min-width:260px;padding:1rem 1.15rem;border:1px solid #29485f;background:#07131f}.avatar{display:grid;width:52px;height:52px;place-items:center;border:1px solid #2fa8ee;border-radius:50%;color:#48baff;background:#0b2639;font-family:Impact,"Arial Narrow",sans-serif;font-size:1.1rem}.identity-summary strong,.identity-summary small{display:block}.identity-summary small{margin-top:.25rem;color:#7693a8;font-size:.74rem;text-transform:uppercase;letter-spacing:.1em}.account-overview{display:grid;grid-template-columns:repeat(3,1fr);margin-top:1rem;border:1px solid #1b3144;background:#07131f}.account-overview article{min-width:0;padding:1.25rem 1.4rem;border-right:1px solid #1b3144}.account-overview article:last-child{border-right:0}.account-overview strong,.account-overview small{display:block}.account-overview strong{margin:.55rem 0 .3rem;overflow-wrap:anywhere;font-size:1rem}.account-overview small{color:#68869c;line-height:1.45}.verified{color:#45b7ff}.pending{color:#ffb44c}.profile-grid{display:grid;grid-template-columns:minmax(0,1.12fr) minmax(340px,.88fr);gap:1rem;margin-top:1rem}.profile-card{padding:1.6rem;border:1px solid #1b3144;background:#07131f;min-width:0}:deep(.profile-form>header){padding-bottom:1rem;border-bottom:1px solid #1b3144}:deep(.profile-form h2){margin:0;color:#f4f8fc;font-family:Impact,"Arial Narrow",sans-serif;font-size:1.55rem;letter-spacing:.04em;text-transform:uppercase}:deep(.profile-form>header p){margin:.4rem 0 0;color:#7894a8}:deep(.profile-form form){margin-top:1.25rem!important}:deep(.profile-form label){color:#9db3c3;font-size:.72rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}:deep(.profile-form input){width:100%;margin-top:.45rem;border:1px solid #263d50!important;border-radius:0!important;color:#f5f8fb!important;background:#050d17!important;box-shadow:none!important}:deep(.profile-form input:focus){border-color:#42b5ff!important;box-shadow:0 0 0 1px #42b5ff!important}:deep(.profile-form button[type=submit]){border-radius:0!important;color:#04111d!important;background:#45b7ff!important;font-family:Impact,"Arial Narrow",sans-serif;letter-spacing:.05em;text-transform:uppercase}:deep(.profile-form .helper){margin:.4rem 0 0;color:#6e8ba1;font-size:.78rem;line-height:1.45}:deep(.profile-form .status-panel){padding:1rem;border:1px solid #28516b;background:#0a1b29;color:#b8d0df}:deep(.profile-form .status-panel.warning){border-color:#70592a;background:#201a0e;color:#f0cf8c}:deep(.profile-form .status-actions){display:flex;flex-wrap:wrap;gap:1rem;margin-top:.75rem}:deep(.profile-form .status-actions button){padding:0;border:0;color:#45b7ff;background:transparent;text-decoration:underline}
@media(max-width:900px){.profile-hero{align-items:flex-start;flex-direction:column}.identity-summary{width:100%;min-width:0}.account-overview,.profile-grid{grid-template-columns:1fr}.account-overview article{border-right:0;border-bottom:1px solid #1b3144}.account-overview article:last-child{border-bottom:0}}
@media(max-width:600px){.profile-page{padding:1rem}.profile-hero,.profile-card{padding:1.2rem}.profile-hero h1{font-size:2.35rem}.account-overview article{padding:1rem}}
</style>
