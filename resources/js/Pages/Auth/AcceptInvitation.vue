<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ name: String, email: String, submitUrl: String });
const form = useForm({ password: '', password_confirmation: '' });
</script>

<template>
    <GuestLayout>
        <Head title="Aktivasi Akun" />
        <div class="mb-6">
            <h1 class="text-xl font-semibold text-gray-900">Aktifkan akun COSMIC CMS</h1>
            <p class="mt-2 text-sm text-gray-600">
                Halo {{ name }}. Email <strong>{{ email }}</strong> telah berhasil dikenali.
                Tentukan password untuk menyelesaikan aktivasi akun.
            </p>
        </div>
        <form @submit.prevent="form.post(submitUrl)">
            <div>
                <InputLabel for="password" value="Password baru" />
                <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" required autofocus autocomplete="new-password" />
                <p class="mt-1 text-xs text-gray-500">Gunakan minimal 8 karakter dan jangan gunakan password yang mudah ditebak.</p>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>
            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Konfirmasi password" />
                <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
            </div>
            <PrimaryButton class="mt-6" :disabled="form.processing">Aktifkan Akun</PrimaryButton>
        </form>
    </GuestLayout>
</template>
