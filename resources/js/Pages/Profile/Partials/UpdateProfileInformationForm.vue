<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';

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

const form = useForm({
    name: user.name,
    email: user.email,
    current_password: '',
});

const submit = () => form.patch(route('profile.update'), {
    preserveScroll: true,
    onSuccess: () => form.reset('current_password'),
});
</script>

<template>
    <section class="profile-form">
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Informasi Profil
            </h2>

            <p>
                Perbarui nama dan email login akun. Email baru akan digunakan setelah diverifikasi.
            </p>
        </header>

        <form
            @submit.prevent="submit"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="name" value="Nama lengkap" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" value="Email login" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
                <p class="helper">Email login saat ini: {{ user.email }}.</p>
            </div>

            <div v-if="form.email.toLowerCase() !== user.email.toLowerCase()">
                <InputLabel for="current_password" value="Password saat ini" />
                <TextInput id="current_password" v-model="form.current_password" type="password" class="mt-1 block w-full" required autocomplete="current-password" />
                <p class="helper">Diperlukan untuk mengamankan perubahan alamat email.</p>
                <InputError class="mt-2" :message="form.errors.current_password" />
            </div>

            <div v-if="mustChangeEmail" class="status-panel warning">
                <strong>Lengkapi keamanan akun.</strong>
                Akun ini masih menggunakan email awal deployment. Ganti dengan email operasional yang dapat diakses.
            </div>

            <div v-if="pendingEmail" class="status-panel">
                <strong>Email baru menunggu verifikasi:</strong> {{ pendingEmail }}
                <p class="mt-1">Email lama tetap digunakan untuk login sampai tautan verifikasi dibuka.</p>
                <div class="status-actions">
                    <button type="button" @click="router.post(route('profile.email.resend'))">Kirim ulang tautan</button>
                    <button type="button" @click="router.delete(route('profile.email.cancel'))">Batalkan perubahan</button>
                </div>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null && !pendingEmail" class="status-panel warning">
                <p>
                    Alamat email Anda belum diverifikasi.
                </p>
                <div class="status-actions">
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                    >
                        Kirim ulang email verifikasi
                    </Link>
                </div>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="helper"
                >
                    Tautan verifikasi baru telah dikirim.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Simpan Profil</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Tersimpan.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
