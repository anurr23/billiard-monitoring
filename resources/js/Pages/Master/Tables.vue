<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    tables: Array
});

const form = useForm({
    name: '',
    relay_channel: ''
});

const submit = () => {
    form.post(route('tables.store'), {
        onSuccess: () => form.reset(),
    });
};

const deleteTable = (id) => {
    if(confirm('Hapus meja ini? Data sesi aktif akan terpengaruh.')) {
        router.delete(route('tables.destroy', id));
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
            <!-- Add Form -->
            <div class="col-lg-4">
                <div class="bb-card h-100">
                    <div class="bb-card-header">
                        <h6 class="fw-bold mb-0"><i class="bi bi-plus-circle me-2 text-success"></i>Tambah Meja</h6>
                    </div>
                    <div class="bb-card-body">
                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label class="bb-label">Nama Meja</label>
                                <input type="text" v-model="form.name" required placeholder="Contoh: Meja 5 VIP" class="bb-input" />
                            </div>
                            <div class="mb-4">
                                <label class="bb-label">Relay Channel (Hardware)</label>
                                <input type="number" v-model="form.relay_channel" required min="1" placeholder="Channel 1-16" class="bb-input" :class="{'border-danger': form.errors.relay_channel}" />
                                <div v-if="form.errors.relay_channel" class="small text-danger mt-1">{{ form.errors.relay_channel }}</div>
                            </div>
                            <button type="submit" :disabled="form.processing" class="bb-btn bb-btn--primary w-100 py-3">
                                <i class="bi bi-plus-lg"></i> Simpan Meja
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Table List -->
            <div class="col-lg-8">
                <div class="bb-card">
                    <div class="bb-card-header">
                        <h6 class="fw-bold mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Meja</h6>
                        <span class="bb-badge" style="background: rgba(99,102,241,0.1); color: #6366f1;">{{ tables.length }} Meja</span>
                    </div>
                    <div class="bb-card-body p-0">
                        <table class="bb-table">
                            <thead>
                                <tr>
                                    <th class="ps-4">ID</th>
                                    <th>Nama Meja</th>
                                    <th>Relay</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="table in tables" :key="table.id">
                                    <td class="ps-4">
                                        <span class="font-monospace small" style="opacity: 0.5;">{{ table.id.substring(0, 8) }}...</span>
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
                                        <button @click="deleteTable(table.id)" class="bb-btn bb-btn--ghost py-1 px-3" style="font-size: 0.8rem; text-transform: none;">
                                            <i class="bi bi-trash3"></i> Hapus
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="tables.length === 0">
                                    <td colspan="5" class="text-center py-5" style="opacity: 0.4;">
                                        <i class="bi bi-inbox d-block mb-2" style="font-size: 2rem;"></i>
                                        Belum ada meja terdaftar
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
