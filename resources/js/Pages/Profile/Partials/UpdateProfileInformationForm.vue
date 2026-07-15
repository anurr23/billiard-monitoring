<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    photo: null,
});

const photoPreview = ref(user.photo_url || null);
const photoInput = ref(null);

const capitalizeName = () => {
    form.name = form.name.replace(/\b\w/g, l => l.toUpperCase());
};

const selectPhoto = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    form.photo = file;
    photoPreview.value = URL.createObjectURL(file);
};

const removePhoto = () => {
    form.photo = null;
    photoPreview.value = null;
    if (photoInput.value) photoInput.value.value = '';
};

const submit = () => {
    form.transform(() => ({
        name: form.name,
        ...(form.photo ? { photo: form.photo } : {}),
    })).post(route('profile.update'), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <form @submit.prevent="submit">
        <div class="mb-3">
            <label class="bb-label text-secondary">Nama Lengkap</label>
            <input type="text" v-model="form.name" @input="capitalizeName" required class="bb-input w-100 mt-1" />
            <div v-if="form.errors.name" class="small text-danger mt-1">{{ form.errors.name }}</div>
        </div>

        <div class="mb-4">
            <label class="bb-label text-secondary">Foto Profil</label>
            <div class="d-flex align-items-center gap-3 mt-1">
                <div v-if="photoPreview" class="position-relative">
                    <img :src="photoPreview" style="width: 64px; height: 64px; border-radius: 12px; object-fit: cover;" />
                    <button type="button" @click="removePhoto" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border-0" style="font-size: 0.6rem; cursor: pointer; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div v-else style="width: 64px; height: 64px; border-radius: 12px; background: rgba(99,102,241,0.08); display: flex; align-items: center; justify-content: center; border: 2px dashed rgba(99,102,241,0.2);">
                    <i class="bi bi-camera" style="font-size: 1.3rem; color: #6366f1; opacity: 0.4;"></i>
                </div>
                <div>
                    <label class="bb-btn bb-btn--ghost py-2 px-3 mb-0" style="font-size: 0.8rem; text-transform: none; cursor: pointer;">
                        <i class="bi bi-upload me-1"></i> Pilih Foto
                        <input type="file" ref="photoInput" @change="selectPhoto" accept="image/jpeg,image/png,image/webp" class="d-none" />
                    </label>
                    <div class="small text-secondary mt-1">JPEG/PNG/WebP, maks 2MB</div>
                </div>
            </div>
            <div v-if="form.errors.photo" class="small text-danger mt-1">{{ form.errors.photo }}</div>
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
