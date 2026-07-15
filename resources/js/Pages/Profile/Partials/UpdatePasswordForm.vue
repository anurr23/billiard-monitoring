<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <form @submit.prevent="updatePassword">
        <div class="mb-3">
            <label class="bb-label text-secondary">Password Saat Ini</label>
            <input
                id="current_password"
                ref="currentPasswordInput"
                v-model="form.current_password"
                type="password"
                class="bb-input w-100 mt-1"
                autocomplete="current-password"
            />
            <div v-if="form.errors.current_password" class="small text-danger mt-1">{{ form.errors.current_password }}</div>
        </div>

        <div class="mb-3">
            <label class="bb-label text-secondary">Password Baru</label>
            <input
                id="password"
                ref="passwordInput"
                v-model="form.password"
                type="password"
                class="bb-input w-100 mt-1"
                autocomplete="new-password"
            />
            <div v-if="form.errors.password" class="small text-danger mt-1">{{ form.errors.password }}</div>
        </div>

        <div class="mb-4">
            <label class="bb-label text-secondary">Konfirmasi Password</label>
            <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                class="bb-input w-100 mt-1"
                autocomplete="new-password"
            />
        </div>

        <div class="d-flex align-items-center gap-3">
            <button type="submit" :disabled="form.processing" class="bb-btn bb-btn--success">
                <i v-if="form.processing" class="spinner-border spinner-border-sm me-1"></i>
                <i v-else class="bi bi-check-lg me-1"></i> Simpan
            </button>
            <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                <span v-if="form.recentlySuccessful" class="small text-success"><i class="bi bi-check-circle-fill me-1"></i>Tersimpan</span>
            </Transition>
        </div>
    </form>
</template>
