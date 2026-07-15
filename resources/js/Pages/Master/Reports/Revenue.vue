<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    dailyRevenue: Array,
    summary: Object,
    startDate: String,
    endDate: String,
});

const startDate = ref(props.startDate);
const endDate = ref(props.endDate);
const activePreset = ref(null);

const presets = [
    { label: 'Hari Ini', days: 0 },
    { label: '7 Hari', days: 7 },
    { label: '30 Hari', days: 30 },
    { label: 'Bulan Ini', days: 'month' },
];

const applyFilter = () => {
    activePreset.value = null;
    router.get(route('reports.revenue'), {
        start_date: startDate.value,
        end_date: endDate.value,
    }, { preserveState: true });
};

const setPreset = (preset) => {
    activePreset.value = preset.label;
    const end = new Date();
    let start;

    if (preset.days === 0) {
        start = new Date();
    } else if (preset.days === 'month') {
        start = new Date(end.getFullYear(), end.getMonth(), 1);
    } else {
        start = new Date();
        start.setDate(start.getDate() - preset.days);
    }

    startDate.value = start.toISOString().split('T')[0];
    endDate.value = end.toISOString().split('T')[0];
    applyFilter();
};

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID').format(val || 0);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

const formatDateShort = (dateStr) => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
};

const displayStart = computed(() => formatDateShort(startDate.value));
const displayEnd = computed(() => formatDateShort(endDate.value));
</script>

<template>
    <Head title="Laporan Total Pendapatan" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="bb-header-title"><i class="bi bi-wallet2 me-2" style="color: #6366f1;"></i>Laporan Total Pendapatan</h1>
        </template>

        <!-- Date Filter -->
        <div class="bb-card mb-4">
            <div class="bb-card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-calendar-range me-2"></i>Filter Periode</h6>
                <span class="small text-secondary">{{ displayStart }} — {{ displayEnd }}</span>
            </div>
            <div class="bb-card-body">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button
                        v-for="preset in presets"
                        :key="preset.label"
                        @click="setPreset(preset)"
                        class="bb-btn"
                        :class="activePreset === preset.label ? 'bb-btn--primary' : 'bb-btn--ghost'"
                        style="font-size: 0.8rem; text-transform: none; border-radius: 100px; padding: 6px 18px;"
                    >
                        {{ preset.label }}
                    </button>
                </div>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="bb-label">
                            <i class="bi bi-calendar3 me-1"></i> Dari Tanggal
                        </label>
                        <div class="bb-date-input-wrap">
                            <input type="date" v-model="startDate" class="bb-date-input" />
                            <span class="bb-date-fake">{{ displayStart || 'Pilih tanggal' }}</span>
                            <i class="bi bi-calendar3 bb-date-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="bb-label">
                            <i class="bi bi-calendar3 me-1"></i> Sampai Tanggal
                        </label>
                        <div class="bb-date-input-wrap">
                            <input type="date" v-model="endDate" class="bb-date-input" />
                            <span class="bb-date-fake">{{ displayEnd || 'Pilih tanggal' }}</span>
                            <i class="bi bi-calendar3 bb-date-icon"></i>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button @click="applyFilter" class="bb-btn bb-btn--primary flex-grow-1 py-3">
                            <i class="bi bi-funnel me-2"></i>Terapkan
                        </button>
                        <button
                            @click="router.get(route('reports.revenue'), { start_date: '', end_date: '' }, { preserveState: true })"
                            class="bb-btn bb-btn--ghost px-3"
                            title="Reset"
                        >
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="bb-card">
                    <div class="bb-card-body text-center">
                        <div class="text-secondary small mb-1">Total Transaksi</div>
                        <div class="fs-4 fw-bold" style="color: #6366f1;">{{ formatCurrency(summary?.total_transactions) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bb-card">
                    <div class="bb-card-body text-center">
                        <div class="text-secondary small mb-1">Pendapatan Billiard</div>
                        <div class="fs-4 fw-bold" style="color: #10b981;">Rp {{ formatCurrency(summary?.total_billiard) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bb-card">
                    <div class="bb-card-body text-center">
                        <div class="text-secondary small mb-1">Pendapatan F&B</div>
                        <div class="fs-4 fw-bold" style="color: #f59e0b;">Rp {{ formatCurrency(summary?.total_fnb) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bb-card">
                    <div class="bb-card-body text-center">
                        <div class="text-secondary small mb-1">Total Pendapatan</div>
                        <div class="fs-4 fw-bold" style="color: #ef4444;">Rp {{ formatCurrency(summary?.total_revenue) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Revenue Table -->
        <div class="bb-card">
            <div class="bb-card-header d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-calendar3 me-2"></i>Pendapatan Harian</h6>
                <span class="bb-badge" style="background: rgba(99,102,241,0.1); color: #6366f1;">{{ dailyRevenue.length }} Hari</span>
            </div>
            <div class="bb-card-body p-0">
                <div class="table-responsive">
                    <table class="bb-table">
                        <thead>
                            <tr>
                                <th class="ps-4">No</th>
                                <th>Tanggal</th>
                                <th class="text-end">Jumlah Transaksi</th>
                                <th class="text-end">Pendapatan Billiard</th>
                                <th class="text-end">Pendapatan F&B</th>
                                <th class="text-end pe-4">Total Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(day, index) in dailyRevenue" :key="day.date">
                                <td class="ps-4">{{ index + 1 }}</td>
                                <td class="fw-bold">{{ formatDate(day.date) }}</td>
                                <td class="text-end font-monospace">{{ formatCurrency(day.transaction_count) }}</td>
                                <td class="text-end font-monospace">Rp {{ formatCurrency(day.billiard_revenue) }}</td>
                                <td class="text-end font-monospace">Rp {{ formatCurrency(day.fnb_revenue) }}</td>
                                <td class="text-end pe-4 font-monospace fw-bold">Rp {{ formatCurrency(day.total_revenue) }}</td>
                            </tr>
                            <tr v-if="dailyRevenue.length === 0">
                                <td colspan="6" class="text-center py-5" style="opacity: 0.4;">
                                    <i class="bi bi-inbox d-block mb-2" style="font-size: 2rem;"></i>
                                    Tidak ada data pendapatan untuk periode ini
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="dailyRevenue.length > 0">
                            <tr style="font-weight: 600; background: rgba(255,255,255,0.03);">
                                <td class="ps-4" colspan="2">Total</td>
                                <td class="text-end font-monospace">{{ formatCurrency(summary?.total_transactions) }}</td>
                                <td class="text-end font-monospace">Rp {{ formatCurrency(summary?.total_billiard) }}</td>
                                <td class="text-end font-monospace">Rp {{ formatCurrency(summary?.total_fnb) }}</td>
                                <td class="text-end pe-4 font-monospace">Rp {{ formatCurrency(summary?.total_revenue) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
.bb-date-input-wrap {
    position: relative;
    width: 100%;
}

.bb-date-input {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    z-index: 2;
}

.bb-date-fake {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0.85rem 1rem 0.85rem 2.6rem;
    border-radius: 0.75rem;
    border: 1px solid rgba(0,0,0,0.08);
    background: rgba(0,0,0,0.02);
    color: var(--bs-body-color);
    font-size: 0.9rem;
    transition: all 0.2s ease;
    box-sizing: border-box;
    min-height: 48px;
}

.bb-date-input-wrap:hover .bb-date-fake {
    border-color: var(--bb-accent);
    background: rgba(16,185,129,0.04);
}

.bb-date-icon {
    position: absolute;
    left: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--bb-accent);
    font-size: 1rem;
    z-index: 1;
    pointer-events: none;
}

[data-bs-theme="dark"] .bb-date-fake {
    border: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.03);
}

[data-bs-theme="dark"] .bb-date-input-wrap:hover .bb-date-fake {
    background: rgba(16,185,129,0.08);
}
</style>
