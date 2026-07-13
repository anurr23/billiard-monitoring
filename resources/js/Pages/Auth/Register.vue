<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <h5 class="text-white fw-bold mb-1">Buat Akun Baru</h5>
        <p class="small mb-4" style="color: #94a3b8;">Daftar untuk mulai mengelola meja biliar Anda</p>

        <form @submit.prevent="submit">
            <div class="mb-3">
                <label for="name" class="bb-label" style="color: #94a3b8;">Nama</label>
                <input id="name" type="text" class="bb-input" :class="{'border-danger': form.errors.name}"
                    v-model="form.name" required autofocus autocomplete="name" placeholder="Nama Lengkap"
                    style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: #e2e8f0;" />
                <div v-if="form.errors.name" class="small text-danger mt-1">{{ form.errors.name }}</div>
            </div>

            <div class="mb-3">
                <label for="email" class="bb-label" style="color: #94a3b8;">Email</label>
                <input id="email" type="email" class="bb-input" :class="{'border-danger': form.errors.email}"
                    v-model="form.email" required autocomplete="username" placeholder="email@example.com"
                    style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: #e2e8f0;" />
                <div v-if="form.errors.email" class="small text-danger mt-1">{{ form.errors.email }}</div>
            </div>

            <div class="mb-3">
                <label for="password" class="bb-label" style="color: #94a3b8;">Password</label>
                <input id="password" type="password" class="bb-input" :class="{'border-danger': form.errors.password}"
                    v-model="form.password" required autocomplete="new-password" placeholder="••••••••"
                    style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: #e2e8f0;" />
                <div v-if="form.errors.password" class="small text-danger mt-1">{{ form.errors.password }}</div>
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="bb-label" style="color: #94a3b8;">Konfirmasi Password</label>
                <input id="password_confirmation" type="password" class="bb-input" :class="{'border-danger': form.errors.password_confirmation}"
                    v-model="form.password_confirmation" required autocomplete="new-password" placeholder="••••••••"
                    style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: #e2e8f0;" />
                <div v-if="form.errors.password_confirmation" class="small text-danger mt-1">{{ form.errors.password_confirmation }}</div>
            </div>

            <button type="submit" :disabled="form.processing" class="bb-btn bb-btn--success w-100 py-3 mb-3">
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-person-plus"></i>
                Daftar
            </button>

            <div class="text-center">
                <span class="small" style="color: #64748b;">Sudah punya akun? </span>
                <Link :href="route('login')" class="small text-decoration-none fw-bold" style="color: #10b981;">Masuk</Link>
            </div>
        </form>
    </GuestLayout>
</template>
