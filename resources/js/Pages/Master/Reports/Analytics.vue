<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps({
    hourlyStats: Array,
    tableUtilization: Array,
    packageStats: Array,
    dailyStats: Array,
    summary: Object,
    startDate: String,
    endDate: String,
});

const toDatePickerDate = (str) => {
    if (!str) return null;
    const [y, m, d] = str.split('-').map(Number);
    return new Date(y, m - 1, d);
};

const toDateString = (date) => {
    if (!date) return '';
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const startDate = ref(toDatePickerDate(props.startDate));
const endDate = ref(toDatePickerDate(props.endDate));
const activePreset = ref(null);

const presets = [
    { label: 'Hari Ini', days: 0 },
    { label: '7 Hari', days: 7 },
    { label: '30 Hari', days: 30 },
    { label: 'Bulan Ini', days: 'month' },
];

const applyFilter = () => {
    activePreset.value = null;
    router.get(route('reports.analytics'), {
        start_date: toDateString(startDate.value),
        end_date: toDateString(endDate.value),
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

    startDate.value = start;
    endDate.value = end;
    applyFilter();
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('id-ID').format(value);
};

const formatDuration = (minutes) => {
    if (!minutes || minutes < 1) return '0 menit';
    const hours = Math.floor(minutes / 60);
    const mins = Math.round(minutes % 60);
    if (hours > 0) return `${hours} jam ${mins} menit`;
    return `${mins} menit`;
};

const peakHour = computed(() => {
    if (!props.hourlyStats?.length) return null;
    return props.hourlyStats.reduce((max, curr) => curr.transactions > max.transactions ? curr : max);
});

const topTable = computed(() => {
    if (!props.tableUtilization?.length) return null;
    return props.tableUtilization[0];
});

const topPackage = computed(() => {
    if (!props.packageStats?.length) return null;
    return props.packageStats[0];
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Analitik" />

        <template #header>
            <h1 class="bb-header-title"><i class="bi bi-graph-up-arrow me-2" style="color: #8b5cf6;"></i>Analitik</h1>
        </template>

        <div class="p-4">
            <!-- Filter Section -->
            <div class="bb-card mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-medium small">Tanggal Mulai</label>
                            <VueDatePicker v-model="startDate" :disabledDates="{ after: new Date() }" placeholder="Pilih tanggal" class="w-100" />
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-medium small">Tanggal Akhir</label>
                            <VueDatePicker v-model="endDate" :disabledDates="{ after: new Date() }" placeholder="Pilih tanggal" class="w-100" />
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2 flex-wrap">
                                <button v-for="p in presets" :key="p.label" type="button" class="btn btn-sm" :class="activePreset === p.label ? 'btn-primary' : 'btn-outline-secondary'" @click="setPreset(p)">
                                    {{ p.label }}
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <button type="button" class="btn btn-primary" @click="applyFilter">
                                <i class="bi bi-filter me-1"></i> Terapkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="bb-card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted small mb-1">Total Transaksi</p>
                                    <h3 class="fw-bold mb-0">{{ formatNumber(summary.total_transactions) }}</h3>
                                </div>
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                                    <i class="bi bi-receipt fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="bb-card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted small mb-1">Total Pendapatan</p>
                                    <h3 class="fw-bold mb-0 text-success">{{ formatCurrency(summary.total_revenue) }}</h3>
                                </div>
                                <div class="stat-icon bg-success bg-opacity-10 text-success rounded-3 p-3">
                                    <i class="bi bi-currency-rupee fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="bb-card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted small mb-1">Rata-rata Transaksi</p>
                                    <h3 class="fw-bold mb-0">{{ formatCurrency(summary.avg_transaction_value) }}</h3>
                                </div>
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning rounded-3 p-3">
                                    <i class="bi bi-calculator fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="bb-card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="text-muted small mb-1">Rata-rata Durasi</p>
                                    <h3 class="fw-bold mb-0">{{ formatDuration(summary.avg_duration) }}</h3>
                                </div>
                                <div class="stat-icon bg-info bg-opacity-10 text-info rounded-3 p-3">
                                    <i class="bi bi-clock fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Revenue Breakdown -->
            <div class="row g-3 mb-4">
                <div class="col-xl-4 col-md-6">
                    <div class="bb-card stat-card border-start border-4 border-primary">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Pendapatan Billiard</p>
                            <h4 class="fw-bold text-primary mb-0">{{ formatCurrency(summary.total_billiard) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="bb-card stat-card border-start border-4 border-warning">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Pendapatan F&B</p>
                            <h4 class="fw-bold text-warning mb-0">{{ formatCurrency(summary.total_fnb) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-md-6">
                    <div class="bb-card stat-card border-start border-4 border-success">
                        <div class="card-body">
                            <p class="text-muted small mb-1">Meja Terpakai</p>
                            <h4 class="fw-bold text-success mb-0">{{ summary.unique_tables_used }} / {{ tableUtilization.length }} Meja</h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts & Tables -->
            <div class="row g-4 mb-4">
                <!-- Hourly Activity -->
                <div class="col-xl-8">
                    <div class="bb-card h-100">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="fw-semibold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Aktivitas Per Jam</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" v-if="hourlyStats.length">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Jam</th>
                                            <th class="text-center">Transaksi</th>
                                            <th class="text-end">Pendapatan</th>
                                            <th class="text-center">Rata-rata Durasi</th>
                                            <th class="text-center">Persentase</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="h in hourlyStats" :key="h.hour">
                                            <td class="fw-medium">{{ h.hour }}</td>
                                            <td class="text-center">{{ h.transactions }}</td>
                                            <td class="text-end fw-medium text-success">{{ formatCurrency(h.revenue) }}</td>
                                            <td class="text-center">{{ formatDuration(h.avg_duration) }}</td>
                                            <td class="text-center">
                                                <div class="progress" style="height: 6px; width: 100px;" :title="((h.transactions / (hourlyStats.reduce((sum, s) => sum + s.transactions, 0) || 1)) * 100).toFixed(1) + '%'">
                                                    <div class="progress-bar bg-primary" :style="{ width: ((h.transactions / (hourlyStats.reduce((sum, s) => sum + s.transactions, 0) || 1)) * 100).toFixed(1) + '%' }"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-5 text-muted">
                                <i class="bi bi-clock-history fs-1 d-block mb-2 opacity-25"></i>
                                Tidak ada data aktivitas
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Peak Insights -->
                <div class="col-xl-4">
                    <div class="bb-card h-100">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="fw-semibold mb-0"><i class="bi bi-lightning-charge me-2 text-warning"></i>Insight Puncak</h5>
                        </div>
                        <div class="card-body">
                            <div v-if="peakHour" class="mb-4 p-3 rounded-3 bg-primary bg-opacity-10">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-primary rounded-circle p-2 me-3">
                                        <i class="bi bi-clock text-white"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Jam Sibuk Terbanyak</div>
                                        <div class="fw-bold fs-5">{{ peakHour.hour }} ({{ peakHour.transactions }} transaksi)</div>
                                    </div>
                                </div>
                                <div class="text-muted small">Pendapatan: {{ formatCurrency(peakHour.revenue) }}</div>
                            </div>

                            <div v-if="topTable" class="mb-4 p-3 rounded-3 bg-success bg-opacity-10">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-success rounded-circle p-2 me-3">
                                        <i class="bi bi-table text-white"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Meja Terlaris</div>
                                        <div class="fw-bold fs-5">{{ topTable.table_name }} (No. {{ topTable.table_number }})</div>
                                    </div>
                                </div>
                                <div class="row text-center text-muted small g-0">
                                    <div class="col-6 border-end">
                                        <div class="fw-bold">{{ formatCurrency(topTable.revenue) }}</div>
                                        <div>Pendapatan</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="fw-bold">{{ topTable.total_hours }} jam</div>
                                        <div>Total Jam</div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="topPackage" class="p-3 rounded-3 bg-info bg-opacity-10">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="bg-info rounded-circle p-2 me-3">
                                        <i class="bi bi-tag-fill text-white"></i>
                                    </div>
                                    <div>
                                        <div class="small text-muted">Paket Terpopuler</div>
                                        <div class="fw-bold fs-5">{{ topPackage.name }}</div>
                                    </div>
                                </div>
                                <div class="row text-center text-muted small g-0">
                                    <div class="col-6 border-end">
                                        <div class="fw-bold">{{ topPackage.transactions }}x</div>
                                        <div>Transaksi</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="fw-bold">{{ formatCurrency(topPackage.revenue) }}</div>
                                        <div>Pendapatan</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Utilization & Package Performance -->
            <div class="row g-4 mb-4">
                <div class="col-xl-7">
                    <div class="bb-card">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="fw-semibold mb-0"><i class="bi bi-table me-2 text-success"></i>Utilisasi Meja</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" v-if="tableUtilization.length">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Meja</th>
                                            <th class="text-center">Transaksi</th>
                                            <th class="text-center">Total Jam</th>
                                            <th class="text-end">Pendapatan</th>
                                            <th class="text-center">Utilisasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="t in tableUtilization" :key="t.table_number">
                                            <td>
                                                <div class="fw-medium">{{ t.table_name }}</div>
                                                <div class="small text-muted">No. {{ t.table_number }}</div>
                                            </td>
                                            <td class="text-center">{{ t.transactions }}</td>
                                            <td class="text-center">{{ t.total_hours }} jam</td>
                                            <td class="text-end fw-medium text-success">{{ formatCurrency(t.revenue) }}</td>
                                            <td class="text-center">
                                                <div class="progress" style="height: 6px; width: 80px;" :title="((t.transactions / (tableUtilization.reduce((sum, s) => sum + s.transactions, 0) || 1)) * 100).toFixed(1) + '%'">
                                                    <div class="progress-bar bg-success" :style="{ width: ((t.transactions / (tableUtilization.reduce((sum, s) => sum + s.transactions, 0) || 1)) * 100).toFixed(1) + '%' }"></div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-5 text-muted">
                                <i class="bi bi-table fs-1 d-block mb-2 opacity-25"></i>
                                Tidak ada data utilisasi
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-5">
                    <div class="bb-card">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="fw-semibold mb-0"><i class="bi bi-tag-fill me-2 text-warning"></i>Performa Paket</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" v-if="packageStats.length">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Paket</th>
                                            <th class="text-center">Transaksi</th>
                                            <th class="text-center">Total Jam</th>
                                            <th class="text-end">Pendapatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="p in packageStats" :key="p.name">
                                            <td>
                                                <div class="fw-medium">{{ p.name }}</div>
                                                <div class="small text-muted">{{ formatCurrency(p.price_per_hour) }}/jam</div>
                                            </td>
                                            <td class="text-center">{{ p.transactions }}</td>
                                            <td class="text-center">{{ p.total_hours }} jam</td>
                                            <td class="text-end fw-medium text-success">{{ formatCurrency(p.revenue) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-center py-5 text-muted">
                                <i class="bi bi-tag-fill fs-1 d-block mb-2 opacity-25"></i>
                                Tidak ada data paket
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Trend -->
            <div class="bb-card">
                <div class="card-header bg-transparent border-0">
                    <h5 class="fw-semibold mb-0"><i class="bi bi-calendar-event me-2 text-info"></i>Tren Harian</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" v-if="dailyStats.length">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th class="text-center">Transaksi</th>
                                    <th class="text-end">Total Pendapatan</th>
                                    <th class="text-end">Billiard</th>
                                    <th class="text-end">F&B</th>
                                    <th class="text-center">Rata-rata Durasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="d in dailyStats" :key="d.date">
                                    <td class="fw-medium">{{ new Date(d.date).toLocaleDateString('id-ID', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' }) }}</td>
                                    <td class="text-center">{{ d.transactions }}</td>
                                    <td class="text-end fw-bold">{{ formatCurrency(d.revenue) }}</td>
                                    <td class="text-end text-primary">{{ formatCurrency(d.billiard_revenue) }}</td>
                                    <td class="text-end text-warning">{{ formatCurrency(d.fnb_revenue) }}</td>
                                    <td class="text-center">{{ formatDuration(d.avg_duration) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-5 text-muted">
                        <i class="bi bi-calendar-event fs-1 d-block mb-2 opacity-25"></i>
                        Tidak ada data tren harian
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.stat-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}
.stat-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.progress {
    background-color: rgba(0,0,0,0.05);
}
.table td, .table th {
    vertical-align: middle;
}
</style>