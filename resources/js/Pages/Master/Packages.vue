<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    packages: Array
});

const form = useForm({
    name: '',
    price: ''
});

const submit = () => {
    form.post(route('packages.store'), {
        onSuccess: () => form.reset(),
    });
};

const deletePackage = (id) => {
    if(confirm('Hapus paket ini?')) {
        router.delete(route('packages.destroy', id));
    }
};
</script>

<template>
    <Head title="Paket & Harga" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="bb-header-title"><i class="bi bi-tag-fill me-2" style="color: #f59e0b;"></i>Paket & Harga</h1>
        </template>

        <div class="row g-4">
            <!-- Add Form -->
            <div class="col-lg-4">
                <div class="bb-card h-100">
                    <div class="bb-card-header">
                        <h6 class="fw-bold mb-0"><i class="bi bi-plus-circle me-2 text-success"></i>Tambah Paket</h6>
                    </div>
                    <div class="bb-card-body">
                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label class="bb-label">Nama Paket</label>
                                <input type="text" v-model="form.name" required placeholder="Contoh: VIP Malam" class="bb-input" />
                            </div>
                            <div class="mb-4">
                                <label class="bb-label">Harga per Jam (Rp)</label>
                                <input type="number" v-model="form.price" required min="0" placeholder="50000" class="bb-input" />
                            </div>
                            <button type="submit" :disabled="form.processing" class="bb-btn bb-btn--primary w-100 py-3">
                                <i class="bi bi-plus-lg"></i> Simpan Paket
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Package List -->
            <div class="col-lg-8">
                <div class="bb-card">
                    <div class="bb-card-header">
                        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Paket</h6>
                        <span class="bb-badge" style="background: rgba(245,158,11,0.1); color: #f59e0b;">{{ packages.length }} Paket</span>
                    </div>
                    <div class="bb-card-body p-0">
                        <table class="bb-table">
                            <thead>
                                <tr>
                                    <th class="ps-4">Nama Paket</th>
                                    <th>Harga / Jam</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="pkg in packages" :key="pkg.id">
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bb-stat-icon" style="width: 36px; height: 36px; border-radius: 8px; font-size: 0.9rem; background: rgba(245,158,11,0.1); color: #f59e0b;">
                                                <i class="bi bi-tag"></i>
                                            </div>
                                            <span class="fw-bold">{{ pkg.name }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-gradient">Rp {{ pkg.price.toLocaleString('id-ID') }}</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button @click="deletePackage(pkg.id)" class="bb-btn bb-btn--ghost py-1 px-3" style="font-size: 0.8rem; text-transform: none;">
                                            <i class="bi bi-trash3"></i> Hapus
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="packages.length === 0">
                                    <td colspan="3" class="text-center py-5" style="opacity: 0.4;">
                                        <i class="bi bi-inbox d-block mb-2" style="font-size: 2rem;"></i>
                                        Belum ada data paket
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
