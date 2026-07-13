<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useDatatable } from '@/Composables/useDatatable';

const props = defineProps({
    packages: Array
});

const { 
    searchQuery, currentPage, totalPages, paginatedData, nextPage, prevPage 
} = useDatatable(computed(() => props.packages), ['name']);

const isEditing = ref(false);
const editId = ref(null);

const form = useForm({
    name: '',
    price: ''
});

const submit = () => {
    if (isEditing.value) {
        form.put(route('packages.update', editId.value), {
            onSuccess: () => cancelEdit()
        });
    } else {
        form.post(route('packages.store'), {
            onSuccess: () => form.reset(),
        });
    }
};

const editPackage = (pkg) => {
    isEditing.value = true;
    editId.value = pkg.id;
    form.name = pkg.name;
    form.price = pkg.price;
};

const cancelEdit = () => {
    isEditing.value = false;
    editId.value = null;
    form.reset();
};

const deletePackage = (id) => {
    if(confirm('Hapus paket ini?')) {
        router.delete(route('packages.destroy', id));
        if(isEditing.value && editId.value === id) {
            cancelEdit();
        }
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
            <!-- Add/Edit Form -->
            <div class="col-lg-4">
                <div class="bb-card h-100">
                    <div class="bb-card-header">
                        <h6 v-if="!isEditing" class="fw-bold mb-0"><i class="bi bi-plus-circle me-2 text-success"></i>Tambah Paket</h6>
                        <h6 v-else class="fw-bold mb-0"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Paket</h6>
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
                            <div class="d-flex gap-2">
                                <button v-if="isEditing" type="button" @click="cancelEdit" class="bb-btn bb-btn--ghost flex-grow-1 py-3">
                                    Batal
                                </button>
                                <button type="submit" :disabled="form.processing" class="bb-btn bb-btn--primary flex-grow-1 py-3">
                                    <i v-if="!isEditing" class="bi bi-plus-lg"></i>
                                    <i v-else class="bi bi-check-lg"></i>
                                    Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Package List -->
            <div class="col-lg-8">
                <div class="bb-card">
                    <div class="bb-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="fw-bold mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Paket</h6>
                            <span class="bb-badge" style="background: rgba(245,158,11,0.1); color: #f59e0b;">{{ packages.length }} Paket</span>
                        </div>
                        <div style="min-width: 200px;">
                            <input type="text" v-model="searchQuery" placeholder="Cari paket..." class="bb-input form-control-sm py-2" />
                        </div>
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
                                <tr v-for="pkg in paginatedData" :key="pkg.id">
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
                                        <button @click="editPackage(pkg)" class="bb-btn bb-btn--ghost py-1 px-3 me-2" style="font-size: 0.8rem; text-transform: none;">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button @click="deletePackage(pkg.id)" class="bb-btn bb-btn--ghost py-1 px-3" style="font-size: 0.8rem; text-transform: none;">
                                            <i class="bi bi-trash3 text-danger"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="paginatedData.length === 0">
                                    <td colspan="3" class="text-center py-5" style="opacity: 0.4;">
                                        <i class="bi bi-inbox d-block mb-2" style="font-size: 2rem;"></i>
                                        Pencarian tidak ditemukan
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="bb-card-header d-flex justify-content-between align-items-center" style="border-top: 1px solid rgba(255,255,255,0.06); border-bottom: none;">
                        <span class="small text-secondary">Halaman {{ currentPage }} dari {{ totalPages }}</span>
                        <div class="d-flex gap-1">
                            <button @click="prevPage" :disabled="currentPage === 1" class="bb-btn bb-btn--ghost py-1 px-3" style="font-size: 0.8rem; text-transform: none;">
                                <i class="bi bi-chevron-left"></i> Prev
                            </button>
                            <button @click="nextPage" :disabled="currentPage === totalPages" class="bb-btn bb-btn--ghost py-1 px-3" style="font-size: 0.8rem; text-transform: none;">
                                Next <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
