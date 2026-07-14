<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="alert alert-success rounded-3 mb-4 small">
            {{ status }}
        </div>

        <h5 class="bb-text-primary fw-bold mb-1">Selamat Datang</h5>
        <p class="small mb-4 text-secondary">Masuk ke dashboard untuk mengelola meja biliar</p>

        <form @submit.prevent="submit">
            <div class="mb-4">
                <label for="username" class="bb-label text-secondary">Username</label>
                <input
                    id="username"
                    type="text"
                    class="bb-input w-100 mt-1"
                    :class="{'border-danger': form.errors.username}"
                    v-model="form.username"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="admin"
                />
                <div v-if="form.errors.username" class="small text-danger mt-1">{{ form.errors.username }}</div>
            </div>

            <div class="mb-3">
                <label for="password" class="bb-label text-secondary">Password</label>
                <input
                    id="password"
                    type="password"
                    class="bb-input"
                    :class="{'border-danger': form.errors.password}"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <div v-if="form.errors.password" class="small text-danger mt-1">{{ form.errors.password }}</div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember" v-model="form.remember">
                    <label class="form-check-label small text-secondary" for="remember">Remember me</label>
                </div>
                <Link v-if="canResetPassword" :href="route('password.request')" class="small text-decoration-none" style="color: #10b981;">
                    Lupa password?
                </Link>
            </div>

            <button type="submit" :disabled="form.processing" class="bb-btn bb-btn--success w-100 py-3 mb-3">
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                <i v-else class="bi bi-box-arrow-in-right"></i>
                Masuk
            </button>

            <div class="text-center">
                <span class="small" style="color: #64748b;">Belum punya akun? </span>
                <Link :href="route('register')" class="small text-decoration-none fw-bold" style="color: #10b981;">Daftar</Link>
            </div>
        </form>
    </GuestLayout>
</template>
