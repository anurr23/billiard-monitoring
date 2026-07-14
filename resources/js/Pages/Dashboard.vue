<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BbSelect from '@/Components/BbSelect.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    tables: Array,
    packages: Array
});

const packageOptions = computed(() => {
    return props.packages.map(pkg => ({
        label: `${pkg.name} — Rp ${pkg.price.toLocaleString('id-ID')}/jam`,
        value: pkg.id
    }));
});

// Modal state
const showOrderModal = ref(false);
const selectedTable = ref(null);

const form = useForm({
    customer_name: '',
    duration_hours: 1,
    package_id: null,
});

const openOrderModal = (table) => {
    form.reset();
    form.clearErrors();
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
        if (table.status === 'active' && table.transactions && table.transactions.length > 0 && table.transactions[0].expected_end_time) {
            newTimers[table.id] = formatCountdown(table.transactions[0].expected_end_time);
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

const showCheckoutModal = ref(false);
const selectedTableForCheckout = ref(null);

const confirmStopTable = (table) => {
    selectedTableForCheckout.value = table;
    showCheckoutModal.value = true;
};

const executeCheckout = () => {
    if (selectedTableForCheckout.value) {
        router.post(route('tables.stop', selectedTableForCheckout.value.id), {}, {
            onSuccess: () => {
                showCheckoutModal.value = false;
                selectedTableForCheckout.value = null;
            }
        });
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
        <div class="d-grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
            <div v-for="table in tables" :key="table.id" class="h-100">
                <div class="bb-table-card w-100 h-100 d-flex flex-column" :class="{ 'bb-table-card--active': table.status === 'active' }">
                    <div class="p-4 d-flex flex-column flex-grow-1">
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
                            <span class="bb-status-badge flex-shrink-0" :class="table.status === 'active' ? 'bb-status-badge--active' : 'bb-status-badge--ready'">
                                <i :class="table.status === 'active' ? 'bi bi-circle-fill' : 'bi bi-circle'" style="font-size: 0.45rem;"></i>
                                {{ table.status === 'active' ? 'In Use' : 'Ready' }}
                            </span>
                        </div>

                        <!-- Timer / Idle -->
                        <div class="text-center py-4 flex-grow-1 d-flex flex-column justify-content-center">
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
                        <div class="mt-auto pt-3">
                            <button v-if="table.status === 'inactive'" @click="openOrderModal(table)"
                                class="bb-btn bb-btn--success w-100 py-3">
                                <i class="bi bi-play-fill"></i> Start Order
                            </button>
                            <button v-else @click="confirmStopTable(table)"
                                class="bb-btn bb-btn--danger bb-btn--sm w-100 mt-auto">
                                <i class="bi bi-stop-circle"></i> Stop & Checkout
                            </button>
                        </div>
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
        <div v-if="showOrderModal" class="bb-modal-backdrop">
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
                            <label class="bb-label">Nama Pelanggan</label>
                            <input type="text" v-model="form.customer_name" @input="form.customer_name = form.customer_name.replace(/\b\w/g, l => l.toUpperCase())" required class="bb-input" placeholder="Masukkan nama pelanggan..." />
                            <div v-if="form.errors.customer_name" class="small text-danger mt-1">{{ form.errors.customer_name }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="bb-label">Durasi (Jam)</label>
                            <input type="number" step="0.5" min="0.5" v-model="form.duration_hours" required class="bb-input" />
                            <div v-if="form.errors.duration_hours" class="small text-danger mt-1">{{ form.errors.duration_hours }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="bb-label">Pilih Paket</label>
                            <BbSelect 
                                v-model="form.package_id" 
                                :options="packageOptions" 
                                placeholder="Pilih Paket..."
                                :error="!!form.errors.package_id"
                            />
                            <div v-if="form.errors.package_id" class="small text-danger mt-1">{{ form.errors.package_id }}</div>
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

        <!-- Checkout Modal -->
        <div v-if="showCheckoutModal" class="bb-modal-backdrop" @click.self="showCheckoutModal = false">
            <div class="bb-modal">
                <div class="bb-modal-header border-bottom-0 pb-0">
                    <h5 class="m-0 text-white"><i class="bi bi-box-arrow-right me-2 text-danger"></i> Konfirmasi Checkout</h5>
                    <button type="button" class="btn-close btn-close-white opacity-50" @click="showCheckoutModal = false"></button>
                </div>
                <div class="bb-modal-body pt-4">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-danger bg-opacity-10 mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-exclamation-triangle text-danger fs-1"></i>
                        </div>
                        <h4 class="text-white mb-2">Selesaikan Sesi?</h4>
                        <p class="text-secondary mb-0">
                            Anda yakin ingin menyelesaikan sesi pada <strong class="text-white">{{ selectedTableForCheckout?.name }}</strong>?
                            Tindakan ini tidak dapat dibatalkan.
                        </p>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" @click="showCheckoutModal = false" class="bb-btn bb-btn--ghost flex-grow-1 py-3">
                            Batal
                        </button>
                        <button type="button" @click="executeCheckout" class="bb-btn bb-btn--danger flex-grow-1 py-3">
                            <i class="bi bi-stop-circle"></i> Ya, Checkout Sekarang
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
