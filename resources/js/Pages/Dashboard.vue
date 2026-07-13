<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    tables: Array,
    packages: Array
});

// Modal state
const showOrderModal = ref(false);
const selectedTable = ref(null);

const form = useForm({
    duration_hours: 1,
    package_id: props.packages.length > 0 ? props.packages[0].id : null,
});

const openOrderModal = (table) => {
    selectedTable.value = table;
    showOrderModal.value = true;
};

const closeOrderModal = () => {
    showOrderModal.value = false;
    selectedTable.value = null;
    form.reset();
};

const submitOrder = () => {
    form.post(route('tables.start', selectedTable.value.id), {
        onSuccess: () => closeOrderModal()
    });
};

const selectedPackage = computed(() => {
    return props.packages.find(p => p.id === form.package_id) || {};
});

const calculatedPrice = computed(() => {
    return (selectedPackage.value.price || 0) * form.duration_hours;
});

// Timer formatting (Countdown)
const formatCountdown = (endString) => {
    if (!endString) return '00:00:00';
    const end = new Date(endString);
    const now = new Date();
    let diff = Math.floor((end - now) / 1000);
    if (diff < 0) diff = 0;
    const h = String(Math.floor(diff / 3600)).padStart(2, '0');
    const m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
    const s = String(diff % 60).padStart(2, '0');
    return `${h}:${m}:${s}`;
};

const isExpired = (endString) => {
    if (!endString) return false;
    return new Date(endString) <= new Date();
};

const timers = ref({});

const updateTimers = () => {
    const newTimers = {};
    props.tables.forEach(table => {
        if (table.status === 'active' && table.expected_end_time) {
            newTimers[table.id] = formatCountdown(table.expected_end_time);
        }
    });
    timers.value = newTimers;
};

let interval;
onMounted(() => {
    updateTimers();
    interval = setInterval(updateTimers, 1000);
});
onUnmounted(() => {
    clearInterval(interval);
});

const stopTable = (id) => {
    if(confirm('Selesaikan sesi meja ini dan lakukan checkout?')) {
        router.post(route('tables.stop', id));
    }
};
</script>

<template>
    <Head title="Live Monitoring" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="bb-header-title"><i class="bi bi-activity me-2" style="color: #10b981;"></i>Live Monitoring</h1>
        </template>

        <!-- Table Grid -->
        <div class="row g-4">
            <div v-for="table in tables" :key="table.id" class="col-12 col-md-6 col-lg-4 col-xl-3">
                <div class="bb-table-card" :class="{ 'bb-table-card--active': table.status === 'active' }">
                    <div class="p-4">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="mb-0 fw-bold">{{ table.name }}</h4>
                                <i class="bi" 
                                   :class="table.status === 'active' ? 'bi-lightbulb-fill' : 'bi-lightbulb'"
                                   :style="table.status === 'active' ? 'font-size: 1.25rem; color: #f59e0b; filter: drop-shadow(0 0 6px rgba(245,158,11,0.6));' : 'font-size: 1.25rem; opacity: 0.2;'"
                                   :title="table.status === 'active' ? 'Lampu Menyala' : 'Lampu Mati'"
                                ></i>
                            </div>
                            <span class="bb-status-badge" :class="table.status === 'active' ? 'bb-status-badge--active' : 'bb-status-badge--ready'">
                                <i :class="table.status === 'active' ? 'bi bi-circle-fill' : 'bi bi-circle'" style="font-size: 0.45rem;"></i>
                                {{ table.status === 'active' ? 'In Use' : 'Ready' }}
                            </span>
                        </div>

                        <!-- Timer / Idle -->
                        <div class="text-center py-4">
                            <template v-if="table.status === 'active'">
                                <div class="text-uppercase small fw-bold mb-2" style="color: #10b981; letter-spacing: 0.15em; font-size: 0.7rem;">Sisa Waktu</div>
                                <div class="bb-timer display-4 fw-bold" :class="{ 'bb-timer--expired': timers[table.id] === '00:00:00' }" style="color: #10b981;">
                                    {{ timers[table.id] || '00:00:00' }}
                                </div>
                            </template>
                            <template v-else>
                                <div class="py-2" style="opacity: 0.3;">
                                    <i class="bi bi-clock" style="font-size: 3rem;"></i>
                                    <div class="mt-2 fw-semibold">Available</div>
                                </div>
                            </template>
                        </div>

                        <!-- Action Button -->
                        <button v-if="table.status === 'inactive'" @click="openOrderModal(table)"
                            class="bb-btn bb-btn--success w-100 py-3">
                            <i class="bi bi-play-fill"></i> Start Order
                        </button>
                        <button v-else @click="stopTable(table.id)"
                            class="bb-btn bb-btn--danger w-100 py-3">
                            <i class="bi bi-stop-fill"></i> Stop & Checkout
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="tables.length === 0" class="col-12">
                <div class="bb-card text-center py-5">
                    <div class="bb-card-body">
                        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.2;"></i>
                        <h5 class="mt-3 fw-bold" style="opacity: 0.5;">Belum ada meja</h5>
                        <p class="text-secondary small mb-3">Tambahkan meja baru melalui menu Kelola Meja</p>
                        <Link :href="route('tables.index')" class="bb-btn bb-btn--primary">
                            <i class="bi bi-plus-lg"></i> Tambah Meja
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Modal -->
        <div v-if="showOrderModal" class="bb-modal-backdrop" @click.self="closeOrderModal">
            <div class="bb-modal">
                <div class="bb-modal-header">
                    <div>
                        <h5 class="fw-bold mb-0">Order Paket Jam</h5>
                        <p class="small text-secondary mb-0 mt-1">
                            Mulai sesi untuk <strong class="text-gradient">{{ selectedTable?.name }}</strong>
                        </p>
                    </div>
                    <button @click="closeOrderModal" class="bb-theme-toggle" style="width: 32px; height: 32px; font-size: 0.9rem;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="bb-modal-body">
                    <form @submit.prevent="submitOrder">
                        <div class="mb-3">
                            <label class="bb-label">Durasi (Jam)</label>
                            <input type="number" step="0.5" min="0.5" v-model="form.duration_hours" required class="bb-input" />
                        </div>

                        <div class="mb-4">
                            <label class="bb-label">Pilih Paket</label>
                            <select v-model="form.package_id" required class="bb-input">
                                <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                    {{ pkg.name }} — Rp {{ pkg.price.toLocaleString('id-ID') }}/jam
                                </option>
                            </select>
                        </div>

                        <!-- Price Summary -->
                        <div class="p-3 rounded-4 mb-4" style="background: rgba(16,185,129,0.06); border: 1px solid rgba(16,185,129,0.12);">
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-secondary">Harga Paket</span>
                                <span class="fw-semibold">Rp {{ (selectedPackage.price || 0).toLocaleString('id-ID') }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small">
                                <span class="text-secondary">Durasi</span>
                                <span class="fw-semibold">{{ form.duration_hours }} Jam</span>
                            </div>
                            <hr class="my-2" style="opacity: 0.1;">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Total</span>
                                <span class="fw-bold fs-4 text-gradient">Rp {{ calculatedPrice.toLocaleString('id-ID') }}</span>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" @click="closeOrderModal" class="bb-btn bb-btn--ghost flex-grow-1 py-3">
                                Batal
                            </button>
                            <button type="submit" :disabled="form.processing" class="bb-btn bb-btn--success flex-grow-1 py-3">
                                <i class="bi bi-check2-circle"></i> Order Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
