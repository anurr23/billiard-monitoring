<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BbSelect from '@/Components/BbSelect.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    tables: {
        type: Array,
        default: () => []
    },
    packages: {
        type: Array,
        default: () => []
    },
    fnbItems: {
        type: Array,
        default: () => []
    }
});

// ─── Helpers ────────────────────────────────────────────
const formatRupiah = (val) => {
    const amount = Number(val) || 0;
    return 'Rp ' + amount.toLocaleString('id-ID');
};

const packageOptions = computed(() => {
    return props.packages.map(pkg => ({
        label: `${pkg.name} — ${formatRupiah(pkg.price)}/jam`,
        value: pkg.id
    }));
});

// ─── Table History Modal State ─────────────────────────────
const showTableHistoryModal = ref(false);
const selectedTableForHistory = ref(null);
const historySearch = ref('');

const openTableHistoryModal = (table) => {
    selectedTableForHistory.value = table;
    historySearch.value = '';
    showTableHistoryModal.value = true;
};

const closeTableHistoryModal = () => {
    showTableHistoryModal.value = false;
    selectedTableForHistory.value = null;
    historySearch.value = '';
};

// ─── Table Order (Start Session) Modal State ──────────────
const showOrderModal = ref(false);
const selectedTable = ref(null);

const form = useForm({
    customer_name: '',
    duration_hours: 1,
    package_id: null,
    items: [],
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
    
    // Return to history modal if it was opened from there
    if (selectedTableForHistory.value) {
        showTableHistoryModal.value = true;
    }
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

const addDraftToTableOrder = (item) => {
    const existing = form.items.find(i => i.fnb_item_id === item.id);
    if (existing) {
        existing.quantity++;
    } else {
        form.items.push({
            fnb_item_id: item.id,
            name: item.name,
            price: item.price,
            quantity: 1
        });
    }
};

const removeDraftTableOrder = (index) => {
    form.items.splice(index, 1);
};

const tableOrderFnbTotal = computed(() => {
    return form.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

// ─── Timer / Countdown ──────────────────────────────────
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
const autoStoppingTables = ref(new Set());

const updateTimers = () => {
    const newTimers = {};

    props.tables.forEach(table => {
        if (table.status === 'active' && table.transactions && table.transactions.length > 0 && table.transactions[0].expected_end_time) {
            newTimers[table.id] = formatCountdown(table.transactions[0].expected_end_time);

            if (isExpired(table.transactions[0].expected_end_time)) {
                if (!autoStoppingTables.value.has(table.id)) {
                    autoStoppingTables.value.add(table.id);
                    
                    // Secara otomatis trigger stop meja dari frontend agar langsung instan
                    // tanpa perlu menunggu cron job server yang berjalan tiap 1 menit
                    router.post(route('tables.stop', table.id), {}, {
                        preserveScroll: true,
                        preserveState: true,
                    });
                }
            }
        }
        
        // Reset state jika meja sudah tidak aktif
        if (table.status !== 'active' && autoStoppingTables.value.has(table.id)) {
            autoStoppingTables.value.delete(table.id);
        }
    });
    timers.value = newTimers;
};

// ─── Clock State ────────────────────────────────────────
const currentTime = ref('');
const currentDate = ref('');

const updateClock = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', { hour12: false });
    currentDate.value = now.toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// ─── Filter State ───────────────────────────────────────
const currentFilter = ref('all');
const filteredTables = computed(() => {
    if (currentFilter.value === 'active') return props.tables.filter(t => t.status === 'active');
    if (currentFilter.value === 'ready') return props.tables.filter(t => t.status !== 'active');
    return props.tables;
});

let interval;
onMounted(() => {
    updateTimers();
    updateClock();
    interval = setInterval(() => {
        updateTimers();
        updateClock();
    }, 1000);
});
onUnmounted(() => {
    clearInterval(interval);
});

// ─── Session Detail Modal (replaces old checkout modal) ──
const showSessionModal = ref(false);
const selectedActiveTable = ref(null);
const sessionTab = ref('edit'); // 'checkout' | 'edit'
const fnbSearch = ref('');
const fnbCategoryFilter = ref('all');

const editSessionForm = useForm({
    package_id: '',
    duration_hours: 1
});

// Get active transaction for the selected table
const activeTransaction = computed(() => {
    if (!selectedActiveTable.value) return null;
    return selectedActiveTable.value.transactions?.[0] || null;
});

const openSessionModal = (table) => {
    selectedActiveTable.value = table;
    sessionTab.value = 'edit';
    fnbSearch.value = '';
    fnbCategoryFilter.value = 'all';
    showSessionModal.value = true;
    
    // Set edit form values based on active transaction
    const tx = activeTransaction.value;
    if (tx) {
        editSessionForm.package_id = tx.package_id;
        const start = new Date(tx.start_time);
        const end = new Date(tx.expected_end_time);
        const diffHours = (end - start) / (1000 * 60 * 60);
        editSessionForm.duration_hours = Math.max(0.5, Math.round(diffHours * 2) / 2); // default or nearest 0.5
    }
};

const closeSessionModal = () => {
    showSessionModal.value = false;
    selectedActiveTable.value = null;
    editSessionForm.reset();
    
    // Return to history modal if it was opened from there
    if (selectedTableForHistory.value) {
        showTableHistoryModal.value = true;
    }
};

const submitEditSession = () => {
    if (!selectedActiveTable.value || !activeTransaction.value) return;
        editSessionForm.put(route('tables.updateSession', [selectedActiveTable.value.id, activeTransaction.value.id]), {
        preserveScroll: true,
        onSuccess: () => {
            closeSessionModal();
        }
    });
};



// F&B categories from items
const fnbCategories = computed(() => {
    const cats = [...new Set(props.fnbItems.map(item => item.category).filter(Boolean))];
    return cats.sort();
});

// Filtered F&B items
const filteredFnbItems = computed(() => {
    let items = props.fnbItems;
    if (fnbCategoryFilter.value !== 'all') {
        items = items.filter(item => item.category === fnbCategoryFilter.value);
    }
    if (fnbSearch.value.trim()) {
        const q = fnbSearch.value.toLowerCase();
        items = items.filter(item => item.name.toLowerCase().includes(q));
    }
    return items;
});

// Add F&B item to transaction
const addFnbItem = (fnbItemId, transactionId) => {
    router.post(route('transaction-items.store', transactionId), {
        fnb_item_id: fnbItemId,
        quantity: 1,
    }, {
        preserveScroll: true,
        preserveState: false,
    });
};

// Increment item quantity
const incrementItem = (itemId) => {
    router.patch(route('transaction-items.add', itemId), {}, {
        preserveScroll: true,
        preserveState: false,
    });
};

// Get item count for badge
const getItemCount = (transaction) => {
    if (!transaction?.items) return 0;
    return transaction.items.reduce((sum, item) => sum + item.quantity, 0);
};

// ─── Checkout (Billiard Session) ────────────────────────
const executeCheckout = () => {
    if (!selectedActiveTable.value) return;
    const tx = activeTransaction.value;
    router.post(route('tables.stop', selectedActiveTable.value.id), {}, {
        onSuccess: () => {
            if (tx) {
                // Refresh tx data after stop, then print
                printReceipt({
                    ...tx,
                    type: 'billiard',
                    table_name: selectedActiveTable.value.name,
                    end_time: new Date().toISOString(),
                    status: 'completed',
                    total_cost: Number(tx.billiard_cost) + Number(tx.fnb_cost),
                });
            }
            closeSessionModal();
        }
    });
};

// ─── Table Click Handler ────────────────────────────────
const handleTableClick = (table) => {
    openTableHistoryModal(table);
};

// ─── Formatting Helpers ─────────────────────────────────
const formatDate = (dateString) => {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const calculateDuration = (tx) => {
    // 1. Calculate duration from start_time and end_time / expected_end_time if available
    const startTimeStr = tx.start_time || tx.created_at;
    const endTimeStr = tx.end_time || tx.expected_end_time;
    
    if (startTimeStr && endTimeStr) {
        const start = new Date(startTimeStr);
        const end = new Date(endTimeStr);
        const diffMs = end - start;
        if (!isNaN(diffMs) && diffMs > 0) {
            const diffMinutes = Math.round(diffMs / (1000 * 60));
            if (diffMinutes < 60) {
                return `${diffMinutes} Mnt`;
            }
            const hours = Math.floor(diffMinutes / 60);
            const mins = diffMinutes % 60;
            if (mins === 0) return `${hours} Jam`;
            return `${hours} Jam ${mins} Mnt`;
        }
    }

    // 2. Fallback: estimate from billiard_cost and package price
    if (tx.package && Number(tx.package.price) > 0 && Number(tx.billiard_cost) > 0) {
        const rawHours = Number(tx.billiard_cost) / Number(tx.package.price);
        const totalMinutes = Math.round(rawHours * 60);
        if (totalMinutes < 60) {
            return `${totalMinutes} Mnt`;
        }
        const hours = Math.floor(totalMinutes / 60);
        const mins = totalMinutes % 60;
        if (mins === 0) return `${hours} Jam`;
        return `${hours} Jam ${mins} Mnt`;
    }

    return '-';
};

const getFilteredHistory = (transactions) => {
    if (!transactions) return [];
    
    let result = [...transactions].sort((a, b) => {
        return new Date(b.created_at || b.start_time) - new Date(a.created_at || a.start_time);
    });

    if (!historySearch.value.trim()) return result;
    const query = historySearch.value.toLowerCase();
    return result.filter(tx => tx.customer_name && tx.customer_name.toLowerCase().includes(query));
};

// ─── Receipt Printing ───────────────────────────────────
const printReceipt = (transaction) => {
    const kasirName = usePage().props.auth?.user?.name || 'Kasir';
    const now = new Date();
    const dateStr = now.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
    const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

    const fmtPrice = (val) => {
        const n = Number(val) || 0;
        return n.toLocaleString('id-ID');
    };

    let itemsHtml = '';
    if (transaction.items && transaction.items.length > 0) {
        transaction.items.forEach(item => {
            const name = item.fnb_item?.name || item.name || 'Item';
            itemsHtml += `
                <tr>
                    <td style="padding: 2px 0;">${name} x${item.quantity}</td>
                    <td style="padding: 2px 0; text-align: right;">${fmtPrice(item.subtotal)}</td>
                </tr>
            `;
        });
    }

    const isBilliard = transaction.type === 'billiard';
    const billiardCost = Number(transaction.billiard_cost) || 0;
    const fnbCost = Number(transaction.fnb_cost) || 0;
    const totalCost = billiardCost + fnbCost;

    const html = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Struk #${transaction.id || ''}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 80mm;
            padding: 8px;
            color: #000;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }
        .double-divider {
            border-top: 2px solid #000;
            margin: 6px 0;
        }
        .store-name { font-size: 18px; font-weight: bold; letter-spacing: 2px; }
        .total-row { font-size: 16px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .footer { margin-top: 10px; font-size: 11px; }
        @media print {
            body { width: 80mm; }
            @page { size: 80mm auto; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="center">
        <div class="store-name">POOLSTREAM</div>
        <div style="font-size: 10px; margin-top: 2px;">Billiard & Lounge</div>
    </div>
    <div class="double-divider"></div>

    <table>
        <tr><td>No</td><td class="right">#${transaction.id || '-'}</td></tr>
        <tr><td>Tanggal</td><td class="right">${dateStr} ${timeStr}</td></tr>
        <tr><td>Kasir</td><td class="right">${kasirName}</td></tr>
        <tr><td>Customer</td><td class="right">${transaction.customer_name || '-'}</td></tr>
        ${isBilliard && transaction.table_name ? `<tr><td>Meja</td><td class="right">${transaction.table_name}</td></tr>` : ''}
    </table>

    ${isBilliard ? `
        <div class="divider"></div>
        <div class="bold" style="margin-bottom: 4px;">BILIAR</div>
        <table>
            <tr>
                <td>${transaction.package?.name || 'Paket'} x ${calculateDuration(transaction)}</td>
                <td class="right">${fmtPrice(billiardCost)}</td>
            </tr>
        </table>
    ` : ''}

    ${transaction.items && transaction.items.length > 0 ? `
        <div class="divider"></div>
        <div class="bold" style="margin-bottom: 4px;">F&B</div>
        <table>${itemsHtml}</table>
    ` : ''}

    <div class="divider"></div>
    <table>
        ${isBilliard ? `
            <tr><td>Subtotal Biliar</td><td class="right">${fmtPrice(billiardCost)}</td></tr>
            <tr><td>Subtotal F&B</td><td class="right">${fmtPrice(fnbCost)}</td></tr>
        ` : `
            <tr><td>Subtotal F&B</td><td class="right">${fmtPrice(fnbCost)}</td></tr>
        `}
    </table>
    <div class="double-divider"></div>
    <table>
        <tr class="total-row">
            <td>TOTAL</td>
            <td class="right">Rp ${fmtPrice(totalCost)}</td>
        </tr>
    </table>
    <div class="double-divider"></div>

    <div class="center footer">
        <div>Terima kasih!</div>
        <div>Selamat bermain 🎱</div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    <\/script>
</body>
</html>`;

    const printWindow = window.open('', '_blank', 'width=400,height=600');
    if (printWindow) {
        printWindow.document.write(html);
        printWindow.document.close();
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex justify-content-between align-items-center w-100">
                <h1 class="bb-header-title mb-0"><i class="bi bi-grid-1x2-fill" style="color: #10b981;"></i></h1>
                <div class="text-end d-none d-sm-block">
                    <div class="fw-bold fs-4 text-gradient" style="line-height: 1.2;">{{ currentTime }}</div>
                    <div class="small text-secondary" style="letter-spacing: 0.5px;">{{ currentDate }}</div>
                </div>
            </div>
        </template>

        <!-- Filters & Mobile Clock -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap">
                <button @click="currentFilter = 'all'" :class="['bb-btn bb-btn--sm', currentFilter === 'all' ? 'bb-btn--success' : 'bb-btn--ghost']">Semua Meja</button>
                <button @click="currentFilter = 'active'" :class="['bb-btn bb-btn--sm', currentFilter === 'active' ? 'bb-btn--success' : 'bb-btn--ghost']">Sedang Jalan</button>
                <button @click="currentFilter = 'ready'" :class="['bb-btn bb-btn--sm', currentFilter === 'ready' ? 'bb-btn--success' : 'bb-btn--ghost']">Kosong</button>
            </div>
            
            <div class="d-flex align-items-center gap-3 ms-auto">
                <div class="d-sm-none text-end">
                    <div class="fw-bold text-gradient fs-5">{{ currentTime }}</div>
                </div>
            </div>
        </div>

        <div class="row align-items-start g-4">
            <!-- Left Side: Table Grid -->
            <div class="col-12">
                <!-- Table Grid -->
                <div class="d-grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));">
                    <div v-for="table in filteredTables" :key="table.id" class="h-100">
                        <div @click="handleTableClick(table)" 
                             class="bb-table-card w-100 h-100 d-flex flex-column" 
                             :class="{ 'bb-table-card--active': table.status === 'active' }"
                             style="cursor: pointer;">
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
                                        <!-- F&B item count badge -->
                                        <div v-if="table.transactions?.[0]?.items?.length > 0" class="mt-2">
                                            <span class="badge rounded-pill px-2 py-1" style="background: rgba(245,158,11,0.15); color: #f59e0b; font-size: 0.7rem;">
                                                <i class="bi bi-cup-hot-fill me-1"></i>{{ getItemCount(table.transactions[0]) }} item F&B
                                            </span>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="py-2" style="opacity: 0.3;">
                                            <i class="bi bi-clock" style="font-size: 3rem;"></i>
                                            <div class="mt-2 fw-semibold">Available</div>
                                        </div>
                                    </template>
                    </div>

                </div>
            </div>
        </div>

                    <!-- Empty State -->
                    <div v-if="filteredTables.length === 0" class="col-12">
                        <div class="bb-card text-center py-5">
                            <div class="bb-card-body">
                                <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.2;"></i>
                                <h5 class="mt-3 fw-bold" style="opacity: 0.5;">
                                    {{ tables.length === 0 ? 'Belum ada meja' : 'Tidak ada meja di kategori ini' }}
                                </h5>
                                <p v-if="tables.length === 0" class="text-secondary small mb-3">Tambahkan meja baru melalui menu Kelola Meja</p>
                                <Link v-if="tables.length === 0" :href="route('tables.index')" class="bb-btn bb-btn--primary">
                                    <i class="bi bi-plus-lg"></i> Tambah Meja
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- ═══════════════════════════════════════════════════ -->
        <!-- Table History Modal (NEW)                          -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div v-if="showTableHistoryModal && selectedTableForHistory" class="bb-modal-backdrop">
            <div class="bb-modal" style="max-width: 650px; width: 95%;">
                <!-- Header -->
                <div class="bb-modal-header">
                    <div>
                        <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history" style="color: #6366f1;"></i>
                            Riwayat Meja: {{ selectedTableForHistory.name }}
                        </h5>
                    </div>
                    <button @click="closeTableHistoryModal" class="bb-theme-toggle" style="width: 32px; height: 32px; font-size: 0.9rem;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="bb-modal-body p-4" style="max-height: 65vh; overflow-y: auto;">
                    <!-- Active Session / Add New Session -->
                    <div class="mb-4 d-flex justify-content-between align-items-center p-3 rounded-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                        <div>
                            <span class="fw-bold fs-6">Status Meja: </span>
                            <span v-if="selectedTableForHistory.status === 'active'" class="badge bg-success ms-2 px-2 py-1">Sedang Jalan</span>
                            <span v-else class="badge bg-secondary ms-2 px-2 py-1">Kosong</span>
                        </div>
                        
                        <div v-if="selectedTableForHistory.status === 'active'">
                        </div>
                        <div v-else>
                            <button @click="showTableHistoryModal = false; openOrderModal(selectedTableForHistory)" class="bb-btn bb-btn--primary bb-btn--sm">
                                <i class="bi bi-play-fill me-1"></i> Mulai Sesi Baru
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-list-ul text-secondary"></i> Riwayat Transaksi
                        </h6>
                        <div class="position-relative" style="width: 220px;">
                            <input type="text" v-model="historySearch" class="bb-input py-1 px-3" placeholder="Cari nama pelanggan..." style="font-size: 0.8rem; padding-left: 2rem !important;" />
                            <i class="bi bi-search position-absolute top-50 translate-middle-y text-secondary opacity-75" style="left: 0.6rem; font-size: 0.75rem;"></i>
                        </div>
                    </div>

                    <div v-if="getFilteredHistory(selectedTableForHistory.recent_transactions)?.length > 0" class="d-flex flex-column gap-3">
                        <div v-for="tx in getFilteredHistory(selectedTableForHistory.recent_transactions)" :key="tx.id" class="bb-card rounded-4 position-relative overflow-hidden d-flex flex-column mb-3" 
                             :style="{
                                border: tx.status === 'active' 
                                    ? '1px solid rgba(16,185,129,0.5)' 
                                    : (tx.status === 'cancelled' ? '1px solid rgba(239,68,68,0.3)' : ''),
                                boxShadow: tx.status === 'active' 
                                    ? '0 0 15px rgba(16,185,129,0.15)' 
                                    : ''
                             }">
                             
                            <!-- Left Accent Line -->
                            <div class="position-absolute top-0 bottom-0 start-0" 
                                 :style="{
                                    width: '4px',
                                    background: tx.status === 'active' ? '#10b981' : (tx.status === 'cancelled' ? '#ef4444' : '#6c757d'),
                                    zIndex: 1
                                 }"></div>
                            
                            <!-- Card Body -->
                            <div class="p-3 ps-4">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <div class="fw-bold fs-6 mb-1">{{ tx.customer_name }}</div>
                                        <div class="small d-flex align-items-center gap-2">
                                            <span class="badge fw-bold rounded-pill" :style="{
                                                backgroundColor: tx.status === 'active' ? 'rgba(16,185,129,0.15)' : (tx.status === 'cancelled' ? 'rgba(239,68,68,0.15)' : 'rgba(108,117,125,0.15)'),
                                                color: tx.status === 'active' ? '#10b981' : (tx.status === 'cancelled' ? '#ef4444' : '#6c757d'),
                                                border: tx.status === 'active' ? '1px solid rgba(16,185,129,0.3)' : (tx.status === 'cancelled' ? '1px solid rgba(239,68,68,0.3)' : '1px solid rgba(108,117,125,0.3)'),
                                                fontSize: '0.65rem',
                                                padding: '0.35em 0.75em',
                                                letterSpacing: '0.5px'
                                            }">{{ tx.status === 'active' ? '● AKTIF' : (tx.status === 'completed' ? 'SELESAI' : 'BATAL') }}</span>
                                            <span class="text-secondary" style="font-size: 0.78rem;"><i class="bi bi-calendar3 me-1"></i>{{ formatDate(tx.start_time) }}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1" style="z-index: 2; position: relative;">
                                        <button v-if="tx.status === 'active'" @click.stop="showTableHistoryModal = false; openSessionModal(selectedTableForHistory);" class="bb-btn bb-btn--ghost bb-btn--sm text-primary" title="Edit Sesi">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button @click.stop="printReceipt({...tx, table_name: selectedTableForHistory.name})" class="bb-btn bb-btn--ghost bb-btn--sm" title="Cetak Struk">
                                            <i class="bi bi-printer"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Billiard details -->
                                <div class="d-flex justify-content-between mb-2" style="font-size: 0.85rem;">
                                    <span class="text-secondary"><i class="bi bi-play-circle me-2" style="color: #10b981;"></i>Paket Biliar ({{ tx.package?.name }})</span>
                                    <span class="fw-semibold">{{ formatRupiah(tx.billiard_cost) }}</span>
                                </div>
                                
                                <!-- F&B details -->
                                <div v-if="tx.items && tx.items.length > 0">
                                    <div class="d-flex justify-content-between mb-1" style="font-size: 0.85rem;">
                                        <span class="text-secondary"><i class="bi bi-cup-hot me-2 text-warning"></i>Pesanan F&B ({{ tx.items.length }} item)</span>
                                        <span class="fw-semibold">{{ formatRupiah(tx.fnb_cost) }}</span>
                                    </div>
                                    <!-- F&B Items Breakdown -->
                                    <div class="ms-4 ps-2 mt-2 mb-2" style="border-left: 2px solid rgba(16,185,129,0.15);">
                                        <div v-for="item in tx.items" :key="item.id" class="d-flex justify-content-between text-secondary" style="font-size: 0.75rem; margin-bottom: 2px;">
                                            <span>{{ item.fnb_item?.name || 'Item' }} <span class="opacity-50">x{{ item.quantity }}</span></span>
                                            <span>{{ formatRupiah(item.subtotal) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Card Footer (Total Cost) -->
                            <div class="p-3 ps-4 mt-auto d-flex justify-content-between align-items-center" 
                                 style="background: rgba(16,185,129,0.06); border-top: 1px solid rgba(16,185,129,0.12);">
                                <span class="fw-bold text-secondary" style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;">Total Pembayaran</span>
                                <span class="fw-bold fs-5" style="color: #10b981;">{{ formatRupiah(Number(tx.billiard_cost) + Number(tx.fnb_cost)) }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-4 text-secondary opacity-50 small">
                        Belum ada riwayat transaksi 48 jam terakhir.
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- Order Modal (Start Session — unchanged)            -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div v-if="showOrderModal" class="bb-modal-backdrop">
            <div class="bb-modal" style="max-width: 1240px; width: 95%;">
                <div class="bb-modal-header">
                    <div>
                        <h5 class="fw-bold mb-0">Order Paket Meja</h5>
                        <p class="small text-secondary mb-0 mt-1">
                            Mulai sesi untuk <strong class="text-gradient">{{ selectedTable?.name }}</strong>
                        </p>
                    </div>
                    <button type="button" @click="closeOrderModal" class="bb-theme-toggle" style="width: 32px; height: 32px; font-size: 0.9rem;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="bb-modal-body p-4">
                    <form @submit.prevent="submitOrder">
                        <div class="row g-4">
                            <!-- Left: F&B Menu -->
                            <div class="col-md-6 border-end" style="border-color: rgba(255,255,255,0.1) !important;">
                                <h6 class="fw-bold mb-3"><i class="bi bi-cup-hot-fill text-warning me-2"></i>Pesan F&B (Opsional)</h6>
                                <div class="mb-3 d-flex gap-2">
                                    <div class="position-relative flex-grow-1">
                                        <i class="bi bi-search position-absolute top-50 translate-middle-y ms-3 text-secondary"></i>
                                        <input type="text" v-model="fnbSearch" class="bb-input ps-5" placeholder="Cari menu F&B..." />
                                    </div>
                                </div>
                                <div class="d-flex gap-2 overflow-auto pb-2 mb-3" style="scrollbar-width: none;">
                                    <button type="button" @click="fnbCategoryFilter = 'all'"
                                            :class="['bb-btn bb-btn--sm flex-shrink-0', fnbCategoryFilter === 'all' ? 'bb-btn--warning' : 'bb-btn--ghost']">
                                        Semua
                                    </button>
                                    <button type="button" v-for="cat in fnbCategories" :key="cat"
                                            @click="fnbCategoryFilter = cat"
                                            :class="['bb-btn bb-btn--sm flex-shrink-0', fnbCategoryFilter === cat ? 'bb-btn--warning' : 'bb-btn--ghost']">
                                        {{ cat }}
                                    </button>
                                </div>
                                <div v-if="filteredFnbItems.length > 0" class="d-grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); max-height: 380px; overflow-y: auto; padding-right: 5px;">
                                    <div v-for="item in filteredFnbItems" :key="item.id" 
                                         class="bb-table-card p-0 overflow-hidden d-flex flex-column" style="border-radius: 0.75rem; cursor: pointer;"
                                         @click="addDraftToTableOrder(item)">
                                        <div v-if="item.image_url" style="height: 80px; overflow: hidden;">
                                            <img :src="item.image_url" :alt="item.name" class="w-100 h-100" style="object-fit: cover;" />
                                        </div>
                                        <div v-else class="d-flex align-items-center justify-content-center" style="height: 80px; background: rgba(255,255,255,0.03);">
                                            <i class="bi bi-cup-hot" style="font-size: 1.5rem; opacity: 0.15;"></i>
                                        </div>
                                        <div class="p-2 d-flex flex-column flex-grow-1">
                                            <div class="fw-bold small text-truncate mb-1" style="font-size: 0.75rem;">{{ item.name }}</div>
                                            <div class="d-flex justify-content-between align-items-center mt-auto pt-1">
                                                <span class="fw-bold small" style="color: #f59e0b; font-size: 0.7rem;">{{ formatRupiah(item.price) }}</span>
                                                <div class="bb-btn bb-btn--warning bb-btn--sm px-1 py-0" style="font-size: 0.6rem;">
                                                    <i class="bi bi-plus-lg"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-5 text-secondary opacity-50">
                                    <i class="bi bi-search fs-2 d-block mb-2"></i>
                                    <div class="small">{{ fnbSearch ? 'Menu tidak ditemukan' : 'Belum ada menu F&B' }}</div>
                                </div>
                            </div>
                            
                            <!-- Right Column: Order Form -->
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3"><i class="bi bi-card-list text-success me-2"></i>Detail Pemesanan</h6>
                                <div class="row g-3 mb-3">
                                    <div class="col-12">
                                        <label class="bb-label">Nama Pelanggan <span class="text-danger">*</span></label>
                                        <input type="text" v-model="form.customer_name" @input="form.customer_name = form.customer_name.replace(/\b\w/g, l => l.toUpperCase())" required class="bb-input" placeholder="Masukkan nama pelanggan..." />
                                        <div v-if="form.errors.customer_name" class="small text-danger mt-1">{{ form.errors.customer_name }}</div>
                                    </div>

                                    <div class="col-4 col-sm-3">
                                        <label class="bb-label">Jam <span class="text-danger">*</span></label>
                                        <input type="number" step="0.5" min="0.5" v-model="form.duration_hours" required class="bb-input" />
                                        <div v-if="form.errors.duration_hours" class="small text-danger mt-1">{{ form.errors.duration_hours }}</div>
                                    </div>

                                    <div class="col-8 col-sm-9">
                                        <label class="bb-label">Pilih Paket <span class="text-danger">*</span></label>
                                        <BbSelect 
                                            v-model="form.package_id" 
                                            :options="packageOptions" 
                                            placeholder="Pilih Paket..."
                                            :error="!!form.errors.package_id"
                                            required
                                        />
                                        <div v-if="form.errors.package_id" class="small text-danger mt-1">{{ form.errors.package_id }}</div>
                                    </div>
                                </div>

                                <div v-if="form.items.length > 0" class="mb-3">
                                    <h6 class="fw-bold mb-2 small d-flex justify-content-between align-items-center">
                                        Keranjang F&B 
                                        <span class="badge rounded-pill px-2 py-1" style="background: rgba(245,158,11,0.15); color: #f59e0b; font-size: 0.7rem;">{{ form.items.length }}</span>
                                    </h6>
                                    <div class="d-flex flex-column gap-2" style="max-height: 150px; overflow-y: auto;">
                                        <div v-for="(item, index) in form.items" :key="item.fnb_item_id" class="p-2 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="fw-bold small">{{ item.name }}</span>
                                                <button type="button" @click="removeDraftTableOrder(index)" class="btn btn-sm text-danger p-0" title="Hapus">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-secondary small">{{ formatRupiah(item.price) }}</span>
                                                <div class="d-flex align-items-center gap-1">
                                                    <button type="button" @click="item.quantity > 1 ? item.quantity-- : null" class="bb-btn bb-btn--ghost px-2 py-0 text-secondary" style="font-size: 0.75rem;">
                                                        <i class="bi bi-dash-lg"></i>
                                                    </button>
                                                    <input type="number" v-model.number="item.quantity" min="1" class="bb-input py-0 px-1 text-center" style="width: 50px; height: 26px; font-size: 0.75rem; border: none; background: rgba(255,255,255,0.05);" />
                                                    <button type="button" @click="item.quantity++" class="bb-btn bb-btn--ghost px-2 py-0 text-warning" style="font-size: 0.75rem;">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Price Summary -->
                                <div class="p-3 rounded-4 mb-4" style="background: rgba(16,185,129,0.06); border: 1px solid rgba(16,185,129,0.12);">
                                    <div class="d-flex justify-content-between mb-2 small">
                                        <span class="text-secondary">Paket ({{ form.duration_hours }} Jam)</span>
                                        <span class="fw-semibold">{{ formatRupiah(calculatedPrice) }}</span>
                                    </div>
                                    <div v-if="form.items.length > 0" class="d-flex justify-content-between mb-2 small">
                                        <span class="text-secondary">F&B ({{ form.items.length }} item)</span>
                                        <span class="fw-semibold" style="color: #f59e0b;">{{ formatRupiah(tableOrderFnbTotal) }}</span>
                                    </div>
                                    <hr class="my-2" style="opacity: 0.1;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">Total Pembayaran</span>
                                        <span class="fw-bold fs-4 text-gradient">{{ formatRupiah(calculatedPrice + tableOrderFnbTotal) }}</span>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="button" @click="closeOrderModal" class="bb-btn bb-btn--ghost flex-grow-1 py-3">
                                        Batal
                                    </button>
                                    <button type="submit" :disabled="form.processing" class="bb-btn bb-btn--success flex-grow-1 py-3">
                                        <i class="bi bi-check2-circle me-1"></i> Mulai Sesi
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- Session Detail Modal (Active Table — with F&B)     -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div v-if="showSessionModal && selectedActiveTable" class="bb-modal-backdrop">
            <div class="bb-modal" style="max-width: 1240px; width: 95%;">
                <!-- Header: Table info + countdown -->
                <div class="bb-modal-header">
                    <div class="d-flex align-items-center gap-3 flex-grow-1">
                        <div>
                            <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-circle-fill" style="font-size: 0.5rem; color: #10b981;"></i>
                                {{ selectedActiveTable.name }}
                            </h5>
                            <div class="d-flex gap-3 mt-1 small text-secondary">
                                <span><i class="bi bi-person-fill me-1"></i>{{ activeTransaction?.customer_name }}</span>
                                <span><i class="bi bi-box me-1"></i>{{ activeTransaction?.package?.name || '-' }}</span>
                                <span class="fw-bold" style="color: #10b981;">
                                    <i class="bi bi-clock me-1"></i>{{ timers[selectedActiveTable.id] || '00:00:00' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <button @click="closeSessionModal" class="bb-theme-toggle" style="width: 32px; height: 32px; font-size: 0.9rem;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <div class="d-flex gap-1 px-4 pt-3" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                    <button @click="sessionTab = 'edit'" 
                            :class="['bb-btn bb-btn--sm rounded-bottom-0 px-3 py-2', sessionTab === 'edit' ? 'bb-btn--primary' : 'bb-btn--ghost']">
                        <i class="bi bi-pencil-square me-1"></i> Edit Sesi & Pesanan
                    </button>
                    <button @click="sessionTab = 'checkout'" 
                            :class="['bb-btn bb-btn--sm rounded-bottom-0 px-3 py-2', sessionTab === 'checkout' ? 'bb-btn--danger' : 'bb-btn--ghost']">
                        <i class="bi bi-cash-stack me-1"></i> Checkout
                    </button>
                </div>

                <div class="bb-modal-body p-4">
                    
                    <!-- TAB: Checkout (Moved outside of tab wrapper so we can just use v-if without breaking layout) -->
                    <div v-if="sessionTab === 'checkout'">
                        <div class="text-center mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 64px; height: 64px; background: rgba(239,68,68,0.1);">
                                <i class="bi bi-cash-stack" style="font-size: 1.8rem; color: #ef4444;"></i>
                            </div>
                            <h5 class="fw-bold mb-1">Checkout & Selesaikan Sesi</h5>
                            <p class="text-secondary small mb-0">{{ selectedActiveTable?.name }} — {{ activeTransaction?.customer_name }}</p>
                        </div>

                        <!-- Cost Breakdown -->
                        <div class="p-4 rounded-4 mb-4" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06);">
                            <!-- Billiard Cost -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary"><i class="bi bi-circle-fill me-2" style="font-size: 0.4rem; color: #10b981;"></i>Biaya Biliar</span>
                                <span class="fw-semibold">{{ formatRupiah(activeTransaction?.billiard_cost) }}</span>
                            </div>
                            <div class="small text-secondary mb-3 ms-3" style="font-size: 0.72rem;">
                                {{ activeTransaction?.package?.name || '-' }} × {{ calculateDuration(activeTransaction || {}) }}
                            </div>

                            <!-- F&B Cost -->
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-secondary"><i class="bi bi-circle-fill me-2" style="font-size: 0.4rem; color: #f59e0b;"></i>Biaya F&B</span>
                                <span class="fw-semibold">{{ formatRupiah(activeTransaction?.fnb_cost) }}</span>
                            </div>
                            <div v-if="activeTransaction?.items?.length > 0" class="ms-3 mb-3">
                                <div v-for="item in activeTransaction.items" :key="item.id" class="d-flex justify-content-between text-secondary" style="font-size: 0.72rem;">
                                    <span>{{ item.fnb_item?.name }} ×{{ item.quantity }}</span>
                                    <span>{{ formatRupiah(item.subtotal) }}</span>
                                </div>
                            </div>

                            <hr style="opacity: 0.1;">

                            <!-- Total -->
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold fs-5">TOTAL</span>
                                <span class="fw-bold fs-4 text-gradient">{{ formatRupiah(Number(activeTransaction?.billiard_cost || 0) + Number(activeTransaction?.fnb_cost || 0)) }}</span>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" @click="closeSessionModal" class="bb-btn bb-btn--ghost flex-grow-1 py-3">
                                Batal
                            </button>
                            <button type="button" @click="executeCheckout" class="bb-btn bb-btn--danger flex-grow-1 py-3">
                                <i class="bi bi-receipt me-1"></i> Checkout & Cetak Struk
                            </button>
                        </div>
                    </div>

                    <!-- TAB: Edit Sesi -->
                    <div v-if="sessionTab === 'edit'">
                        <form @submit.prevent="submitEditSession">
                            <div class="row g-4">
                                <!-- Left: F&B Menu -->
                                <div class="col-md-6 border-end" style="border-color: rgba(255,255,255,0.1) !important;">
                                    <h6 class="fw-bold mb-3"><i class="bi bi-cup-hot-fill text-warning me-2"></i>Pesan F&B (Sesi Aktif)</h6>
                                    
                                    <!-- Search + Category Filter -->
                                    <div class="mb-3 d-flex gap-2">
                                        <div class="position-relative flex-grow-1">
                                            <i class="bi bi-search position-absolute top-50 translate-middle-y ms-3 text-secondary"></i>
                                            <input type="text" v-model="fnbSearch" class="bb-input ps-5" placeholder="Cari menu F&B..." />
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 overflow-auto pb-2 mb-3" style="scrollbar-width: none;">
                                        <button type="button" @click="fnbCategoryFilter = 'all'" :class="['bb-btn bb-btn--sm flex-shrink-0', fnbCategoryFilter === 'all' ? 'bb-btn--warning' : 'bb-btn--ghost']">
                                            Semua
                                        </button>
                                        <button type="button" v-for="cat in fnbCategories" :key="cat" @click="fnbCategoryFilter = cat" :class="['bb-btn bb-btn--sm flex-shrink-0', fnbCategoryFilter === cat ? 'bb-btn--warning' : 'bb-btn--ghost']">
                                            {{ cat }}
                                        </button>
                                    </div>
            
                                    <!-- Menu Grid -->
                                    <div v-if="filteredFnbItems.length > 0" class="d-grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); max-height: 380px; overflow-y: auto; padding-right: 5px;">
                                        <div v-for="item in filteredFnbItems" :key="item.id" class="bb-table-card p-0 overflow-hidden d-flex flex-column" style="border-radius: 0.75rem; cursor: pointer;" @click="addFnbItem(item.id, activeTransaction.id)">
                                            <!-- Image -->
                                            <div v-if="item.image_url" style="height: 80px; overflow: hidden;">
                                                <img :src="item.image_url" :alt="item.name" class="w-100 h-100" style="object-fit: cover;" />
                                            </div>
                                            <div v-else class="d-flex align-items-center justify-content-center" style="height: 80px; background: rgba(255,255,255,0.03);">
                                                <i class="bi bi-cup-hot" style="font-size: 1.5rem; opacity: 0.15;"></i>
                                            </div>
                                            <!-- Info -->
                                            <div class="p-2 d-flex flex-column flex-grow-1">
                                                <div class="fw-bold small text-truncate mb-1" style="font-size: 0.75rem;">{{ item.name }}</div>
                                                <div class="d-flex justify-content-between align-items-center mt-auto pt-1">
                                                    <span class="fw-bold small" style="color: #f59e0b; font-size: 0.7rem;">{{ formatRupiah(item.price) }}</span>
                                                    <div class="bb-btn bb-btn--warning bb-btn--sm px-1 py-0" style="font-size: 0.6rem;">
                                                        <i class="bi bi-plus-lg"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-5 text-secondary opacity-50">
                                        <i class="bi bi-search fs-2 d-block mb-2"></i>
                                        <div class="small">{{ fnbSearch ? 'Menu tidak ditemukan' : 'Belum ada menu F&B' }}</div>
                                    </div>
                                </div>
                                
                                <!-- Right Column: Order Form -->
                                <div class="col-md-6">
                                    <h6 class="fw-bold mb-3"><i class="bi bi-card-list text-primary me-2"></i>Detail Pemesanan (Aktif)</h6>
                                    
                                    <div class="row g-3 mb-3">
                                        <!-- Nama Pelanggan -->
                                        <div class="col-12">
                                            <label class="bb-label">Nama Pelanggan</label>
                                            <input type="text" :value="activeTransaction?.customer_name" readonly class="bb-input" style="background: rgba(255,255,255,0.05); color: #9ca3af; cursor: not-allowed;" />
                                        </div>
        
                                        <!-- Durasi -->
                                        <div class="col-4 col-sm-3">
                                            <label class="bb-label">Jam</label>
                                            <input type="number" step="0.5" min="0.5" v-model="editSessionForm.duration_hours" required class="bb-input" />
                                        </div>

                                        <!-- Paket -->
                                        <div class="col-8 col-sm-9">
                                            <label class="bb-label">Pilih Paket</label>
                                            <select v-model="editSessionForm.package_id" class="bb-input w-100" required>
                                                <option value="" disabled>Pilih Paket</option>
                                                <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                                    {{ pkg.name }} — {{ formatRupiah(pkg.price) }}/jam
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div v-if="activeTransaction?.items?.length > 0" class="mb-3">
                                        <h6 class="fw-bold mb-2 small d-flex justify-content-between align-items-center">
                                            Keranjang F&B 
                                            <span class="badge rounded-pill px-2 py-1" style="background: rgba(16,185,129,0.15); color: #10b981; font-size: 0.7rem;">{{ activeTransaction.items.length }}</span>
                                        </h6>
                                        <div class="d-flex flex-column gap-2" style="max-height: 150px; overflow-y: auto;">
                                            <div v-for="item in activeTransaction.items" :key="item.id" class="p-2 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-bold small">{{ item.fnb_item?.name }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="text-secondary small">{{ formatRupiah(item.price) }}</span>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <span class="fw-bold me-2" style="color: #10b981;">{{ formatRupiah(item.subtotal) }}</span>
                                                        <span class="badge rounded-pill bg-secondary px-2">x{{ item.quantity }}</span>
                                                        <button type="button" @click="incrementItem(item.id)" class="bb-btn bb-btn--ghost px-2 py-0 text-warning" style="font-size: 0.75rem;" title="Tambah">
                                                            <i class="bi bi-plus-lg"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2 mt-4">
                                        <button type="button" @click="closeSessionModal" class="bb-btn bb-btn--ghost flex-grow-1 py-3">
                                            Batal
                                        </button>
                                        <button type="submit" :disabled="editSessionForm.processing || !editSessionForm.package_id" class="bb-btn bb-btn--primary flex-grow-1 py-3">
                                            <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
