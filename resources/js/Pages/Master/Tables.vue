<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useDatatable } from '@/Composables/useDatatable';

const props = defineProps({
    tables: Array
});

const { 
    searchQuery, currentPage, totalPages, paginatedData, nextPage, prevPage 
} = useDatatable(computed(() => props.tables), ['name']);

const isEditing = ref(false);
const editId = ref(null);

const form = useForm({
    name: '',
    relay_channel: ''
});

const submit = () => {
    if (isEditing.value) {
        form.put(route('tables.update', editId.value), {
            onSuccess: () => cancelEdit()
        });
    } else {
        form.post(route('tables.store'), {
            onSuccess: () => form.reset(),
        });
    }
};

const editTable = (table) => {
    isEditing.value = true;
    editId.value = table.id;
    form.name = table.name;
    form.relay_channel = table.relay_channel;
};

const cancelEdit = () => {
    isEditing.value = false;
    editId.value = null;
    form.reset();
};

const deleteTable = (id) => {
    if(confirm('Hapus meja ini? Data sesi aktif akan terpengaruh.')) {
        router.delete(route('tables.destroy', id));
        if(isEditing.value && editId.value === id) {
            cancelEdit();
        }
    }
};
</script>

<template>
    <Head title="Kelola Meja" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="bb-header-title"><i class="bi bi-columns-gap me-2" style="color: #6366f1;"></i>Kelola Meja</h1>
        </template>

        <div class="row g-4">
            <!-- Add/Edit Form -->
            <div class="col-lg-4">
                <div class="bb-card h-100">
                    <div class="bb-card-header">
                        <h6 v-if="!isEditing" class="fw-bold mb-0"><i class="bi bi-plus-circle me-2 text-success"></i>Tambah Meja</h6>
                        <h6 v-else class="fw-bold mb-0"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit Meja</h6>
                    </div>
                    <div class="bb-card-body">
                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label class="bb-label">Nama Meja</label>
                                <input type="text" v-model="form.name" required placeholder="Contoh: Meja 5 VIP" class="bb-input" />
                            </div>
                            <div class="mb-4">
                                <label class="bb-label">Relay Channel (Hardware)</label>
                                <input type="number" v-model="form.relay_channel" required min="1" max="16" placeholder="Channel 1-16" class="bb-input" :class="{'border-danger': form.errors.relay_channel}" />
                                <div v-if="form.errors.relay_channel" class="small text-danger mt-1">{{ form.errors.relay_channel }}</div>
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

            <!-- Table List -->
            <div class="col-lg-8">
                <div class="bb-card">
                    <div class="bb-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="fw-bold mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Meja</h6>
                            <span class="bb-badge" style="background: rgba(99,102,241,0.1); color: #6366f1;">{{ tables.length }} Meja</span>
                        </div>
                        <div style="min-width: 200px;">
                            <input type="text" v-model="searchQuery" placeholder="Cari meja..." class="bb-input form-control-sm py-2" />
                        </div>
                    </div>
                    <div class="bb-card-body p-0">
                        <table class="bb-table">
                            <thead>
                                <tr>
                                    <th class="ps-4">No</th>
                                    <th>Nama Meja</th>
                                    <th>Relay</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(table, index) in paginatedData" :key="table.id">
                                    <td class="ps-4 text-secondary">
                                        {{ (currentPage - 1) * 10 + index + 1 }}
                                    </td>
                                    <td class="fw-bold">{{ table.name }}</td>
                                    <td>
                                        <span class="bb-badge" style="background: rgba(6,182,212,0.1); color: #06b6d4;">
                                            <i class="bi bi-cpu"></i> CH {{ table.relay_channel }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="bb-status-badge" :class="table.status === 'active' ? 'bb-status-badge--active' : 'bb-status-badge--ready'">
                                            {{ table.status === 'active' ? 'Active' : 'Idle' }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button @click="editTable(table)" class="bb-btn bb-btn--ghost py-1 px-3 me-2" style="font-size: 0.8rem; text-transform: none;">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button @click="deleteTable(table.id)" class="bb-btn bb-btn--ghost py-1 px-3" style="font-size: 0.8rem; text-transform: none;">
                                            <i class="bi bi-trash3 text-danger"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="paginatedData.length === 0">
                                    <td colspan="5" class="text-center py-5" style="opacity: 0.4;">
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
