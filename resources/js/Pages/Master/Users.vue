<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useDatatable } from '@/Composables/useDatatable';
import BbSelect from '@/Components/BbSelect.vue';

const props = defineProps({
    users: Array,
});

const {
    searchQuery,
    currentPage,
    totalPages,
    paginatedData,
    nextPage,
    prevPage,
} = useDatatable(props.users, ['name', 'username', 'role']);

const isEditing = ref(false);
const editId = ref(null);
const showModal = ref(false);

const roleOptions = [
    { value: 'kasir', label: 'Kasir' },
    { value: 'admin', label: 'Admin' }
];

const form = useForm({
    name: '',
    username: '',
    role: 'kasir',
    password: ''
});

const openAddModal = () => {
    isEditing.value = false;
    editId.value = null;
    form.reset();
    showModal.value = true;
};

const editUser = (user) => {
    isEditing.value = true;
    editId.value = user.id;
    form.name = user.name;
    form.username = user.username;
    form.role = user.role;
    form.password = '';
    showModal.value = true;
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('users.update', editId.value), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    showModal.value = false;
    setTimeout(() => {
        isEditing.value = false;
        editId.value = null;
        form.reset();
        form.clearErrors();
    }, 300);
};

const deleteUser = (id) => {
    if(confirm('Hapus user ini?')) {
        router.delete(route('users.destroy', id), {
            onError: (errors) => {
                if(errors.message) {
                    alert(errors.message);
                }
            }
        });
    }
};
</script>

<template>
    <Head title="Kelola User" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="bb-header-title"><i class="bi bi-people-fill me-2" style="color: #6366f1;"></i>Kelola User</h1>
        </template>

        <div class="row g-4">
            <!-- User List -->
            <div class="col-12">
                <div class="bb-card">
                    <div class="bb-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-3">
                            <h6 class="fw-bold mb-0"><i class="bi bi-list-ul me-2"></i>Daftar User</h6>
                            <span class="bb-badge" style="background: rgba(99,102,241,0.1); color: #6366f1;">{{ users.length }} User</span>
                        </div>
                        <div class="d-flex gap-2 align-items-center" style="min-width: 300px;">
                            <input type="text" v-model="searchQuery" placeholder="Cari user..." class="bb-input form-control-sm py-2 flex-grow-1" />
                            <button @click="openAddModal" class="bb-btn bb-btn--primary py-2 px-3 text-nowrap">
                                <i class="bi bi-plus-lg me-1"></i> Tambah
                            </button>
                        </div>
                    </div>
                    <div class="bb-card-body p-0">
                        <table class="bb-table">
                            <thead>
                                <tr>
                                    <th class="ps-4">No</th>
                                    <th>Nama</th>
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th class="text-end pe-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(user, index) in paginatedData" :key="user.id">
                                    <td class="ps-4 text-secondary">
                                        {{ (currentPage - 1) * 10 + index + 1 }}
                                    </td>
                                    <td class="fw-bold">{{ user.name }}</td>
                                    <td>{{ user.username }}</td>
                                    <td>
                                        <span class="bb-badge text-capitalize" 
                                            :style="user.role === 'admin' ? 'background: rgba(139,92,246,0.1); color: #8b5cf6;' : 'background: rgba(56,189,248,0.1); color: #38bdf8;'">
                                            <i class="bi" :class="user.role === 'admin' ? 'bi-shield-lock' : 'bi-person'"></i> {{ user.role }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button @click="editUser(user)" class="bb-btn bb-btn--ghost py-1 px-3 me-2" style="font-size: 0.8rem; text-transform: none;">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button v-if="$page.props.auth.user.id !== user.id" @click="deleteUser(user.id)" class="bb-btn bb-btn--ghost py-1 px-3" style="font-size: 0.8rem; text-transform: none;">
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

        <!-- Add/Edit Modal -->
        <div v-if="showModal" class="bb-modal-backdrop" @click.self="closeModal">
            <div class="bb-modal">
                <div class="bb-modal-header">
                    <h5 v-if="!isEditing" class="m-0 bb-text-primary"><i class="bi bi-person-plus-fill me-2 text-success"></i>Tambah User</h5>
                    <h5 v-else class="m-0 bb-text-primary"><i class="bi bi-pencil-square me-2 text-warning"></i>Edit User</h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="bb-modal-body">
                    <form @submit.prevent="submit">
                        <div class="mb-3">
                            <label class="bb-label text-secondary">Nama Lengkap</label>
                            <input type="text" v-model="form.name" required placeholder="Contoh: Budi Santoso" class="bb-input w-100 mt-1" />
                            <div v-if="form.errors.name" class="small text-danger mt-1">{{ form.errors.name }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="bb-label text-secondary">Username</label>
                            <input type="text" v-model="form.username" required placeholder="Username unik" class="bb-input w-100 mt-1" :class="{'border-danger': form.errors.username}" />
                            <div v-if="form.errors.username" class="small text-danger mt-1">{{ form.errors.username }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="bb-label text-secondary">Role</label>
                            <BbSelect 
                                v-model="form.role" 
                                :options="roleOptions" 
                                :error="!!form.errors.role" 
                                class="mt-1" 
                            />
                            <div v-if="form.errors.role" class="small text-danger mt-1">{{ form.errors.role }}</div>
                        </div>
                        <div class="mb-4">
                            <label class="bb-label text-secondary">Password <span v-if="isEditing" class="text-secondary fw-normal">(Kosongkan jika tidak diubah)</span></label>
                            <input type="password" v-model="form.password" :required="!isEditing" placeholder="Password min 4 karakter" class="bb-input w-100 mt-1" />
                            <div v-if="form.errors.password" class="small text-danger mt-1">{{ form.errors.password }}</div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" @click="closeModal" class="bb-btn bb-btn--ghost flex-grow-1 py-3">
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

    </AuthenticatedLayout>
</template>
