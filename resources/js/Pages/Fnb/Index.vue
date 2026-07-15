<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';

const props = defineProps({
    fnbItems: {
        type: Array,
        default: () => []
    },
    fnbOrders: {
        type: Array,
        default: () => []
    }
});

// ─── Helpers ────────────────────────────────────────────
const formatRupiah = (val) => {
    const amount = Number(val) || 0;
    return 'Rp ' + amount.toLocaleString('id-ID');
};

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

let interval;
onMounted(() => {
    updateClock();
    interval = setInterval(() => {
        updateClock();
    }, 1000);
});
onUnmounted(() => {
    clearInterval(interval);
});

// ─── F&B State ──────────────────────────────────────────
const fnbSearch = ref('');
const fnbCategoryFilter = ref('all');

const fnbCategories = computed(() => {
    const cats = [...new Set(props.fnbItems.map(item => item.category).filter(Boolean))];
    return cats.sort();
});

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

const fnbActiveSearch = ref('');
const filteredActiveFnbOrders = computed(() => {
    let orders = props.fnbOrders;
    if (fnbActiveSearch.value.trim()) {
        const q = fnbActiveSearch.value.toLowerCase();
        orders = orders.filter(o => (o.customer_name || '').toLowerCase().includes(q));
    }
    return orders;
});

// ─── F&B Standalone Order ───────────────────────────────
const showFnbOrderModal = ref(false);
const fnbOrderForm = useForm({
    customer_name: '',
    items: [],
});

const openFnbOrderModal = () => {
    fnbOrderForm.reset();
    fnbOrderForm.clearErrors();
    fnbSearch.value = '';
    fnbCategoryFilter.value = 'all';
    showFnbOrderModal.value = true;
};

const closeFnbOrderModal = () => {
    showFnbOrderModal.value = false;
    fnbOrderForm.reset();
    fnbOrderError.value = '';
};

const addDraftToFnbOrder = (item) => {
    fnbOrderError.value = '';
    const existing = fnbOrderForm.items.find(i => i.fnb_item_id === item.id);
    if (existing) {
        existing.quantity++;
    } else {
        fnbOrderForm.items.push({
            fnb_item_id: item.id,
            name: item.name,
            price: item.price,
            quantity: 1
        });
    }
};

const removeDraftFnbOrder = (index) => {
    fnbOrderForm.items.splice(index, 1);
};

const fnbOrderDraftTotal = computed(() => {
    return fnbOrderForm.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
});

const fnbOrderError = ref('');

const submitFnbOrder = () => {
    fnbOrderError.value = '';
    if (!fnbOrderForm.customer_name.trim()) {
        fnbOrderError.value = 'Nama pelanggan wajib diisi!';
        return;
    }
    if (fnbOrderForm.items.length === 0) {
        fnbOrderError.value = 'Pesanan F&B tidak boleh kosong!';
        return;
    }
    if (fnbOrderForm.items.some(i => i.quantity < 1)) {
        fnbOrderError.value = 'Quantity pesanan minimal 1!';
        return;
    }
    
    fnbOrderForm.post(route('fnb-orders.store'), {
        onSuccess: () => {
            closeFnbOrderModal();
        }
    });
};

// ─── F&B Order Detail Modal ─────────────────────────────
const showFnbDetailModal = ref(false);
const selectedFnbOrder = ref(null);
const fnbDetailSearch = ref('');
const fnbDetailCategoryFilter = ref('all');

const fnbDetailCategories = computed(() => {
    const cats = [...new Set(props.fnbItems.map(item => item.category).filter(Boolean))];
    return cats.sort();
});

const filteredFnbDetailItems = computed(() => {
    let items = props.fnbItems;
    if (fnbDetailCategoryFilter.value !== 'all') {
        items = items.filter(item => item.category === fnbDetailCategoryFilter.value);
    }
    if (fnbDetailSearch.value.trim()) {
        const q = fnbDetailSearch.value.toLowerCase();
        items = items.filter(item => item.name.toLowerCase().includes(q));
    }
    return items;
});

const openFnbDetailModal = (order) => {
    selectedFnbOrder.value = order;
    fnbDetailSearch.value = '';
    fnbDetailCategoryFilter.value = 'all';
    showFnbDetailModal.value = true;
};

const closeFnbDetailModal = () => {
    showFnbDetailModal.value = false;
    selectedFnbOrder.value = null;
};

const addItemToFnbOrder = (item) => {
    if (!selectedFnbOrder.value) return;
    router.post(route('transaction-items.store', selectedFnbOrder.value.id), {
        fnb_item_id: item.id,
        quantity: 1,
    }, {
        preserveScroll: true,
        preserveState: false,
    });
};

const incrementFnbOrderItem = (item) => {
    router.patch(route('transaction-items.update-quantity', item.id), {
        change: 1,
    }, {
        preserveScroll: true,
        preserveState: false,
    });
};

const decrementFnbOrderItem = (item) => {
    if (item.quantity <= 1) {
        removeItemFromFnbOrder(item.id);
        return;
    }
    router.patch(route('transaction-items.update-quantity', item.id), {
        change: -1,
    }, {
        preserveScroll: true,
        preserveState: false,
    });
};

const removeItemFromFnbOrder = (itemId) => {
    router.delete(route('transaction-items.destroy', itemId), {
        preserveScroll: true,
        preserveState: false,
    });
};

const checkoutFnbOrder = () => {
    if (!selectedFnbOrder.value) return;
    const order = selectedFnbOrder.value;
    router.post(route('fnb-orders.checkout', order.id), {}, {
        onSuccess: () => {
            printReceipt({
                ...order,
                type: 'fnb_only',
                end_time: new Date().toISOString(),
                status: 'completed',
            });
            closeFnbDetailModal();
        }
    });
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

    const fnbCost = Number(transaction.fnb_cost) || 0;

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
    </table>

    ${transaction.items && transaction.items.length > 0 ? `
        <div class="divider"></div>
        <div class="bold" style="margin-bottom: 4px;">F&B</div>
        <table>${itemsHtml}</table>
    ` : ''}

    <div class="divider"></div>
    <table>
        <tr><td>Subtotal F&B</td><td class="right">${fmtPrice(fnbCost)}</td></tr>
    </table>
    <div class="double-divider"></div>
    <table>
        <tr class="total-row">
            <td>TOTAL</td>
            <td class="right">Rp ${fmtPrice(fnbCost)}</td>
        </tr>
    </table>
    <div class="double-divider"></div>

    <div class="center footer">
        <div>Terima kasih!</div>
        <div>Selamat menikmati ☕</div>
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
    <Head title="Pemesanan F&B" />

    <AuthenticatedLayout>
        <template #header>
            <div class="d-flex justify-content-between align-items-center w-100">
                <h1 class="bb-header-title mb-0"><i class="bi bi-cup-hot-fill" style="color: #f59e0b;"></i> Pemesanan F&B</h1>
                <div class="text-end d-none d-sm-block">
                    <div class="fw-bold fs-4 text-gradient" style="line-height: 1.2;">{{ currentTime }}</div>
                    <div class="small text-secondary" style="letter-spacing: 0.5px;">{{ currentDate }}</div>
                </div>
            </div>
        </template>

        <!-- Filters & Mobile Clock -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div></div>
            <div class="d-sm-none text-end">
                <div class="fw-bold text-gradient fs-5">{{ currentTime }}</div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-list-ul"></i> Daftar Pesanan F&B Aktif
            </h5>
            <div class="d-flex align-items-center gap-3">
                <div class="position-relative" style="max-width: 280px; width: 100%;">
                    <input type="text" v-model="fnbActiveSearch" class="bb-input py-2 px-3" placeholder="Cari pelanggan..." style="font-size: 0.85rem; padding-left: 2.5rem !important;" />
                    <i class="bi bi-search position-absolute top-50 translate-middle-y text-secondary opacity-75" style="left: 1rem; font-size: 0.85rem;"></i>
                </div>
                <button @click="openFnbOrderModal" class="bb-btn bb-btn--sm bb-btn--warning flex-shrink-0">
                    <i class="bi bi-plus-lg me-1"></i> Pesanan Baru
                </button>
            </div>
        </div>

        <div v-if="filteredActiveFnbOrders.length > 0" class="row g-3">
            <div v-for="order in filteredActiveFnbOrders" :key="order.id" class="col-12 col-md-6 col-xl-4">
                <div @click="openFnbDetailModal(order)"
                     class="bb-card overflow-hidden" style="cursor: pointer; border-top: 3px solid #f59e0b; transition: all 0.25s ease;"
                     onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 12px 30px rgba(0,0,0,0.15)'"
                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow=''">
                    <div class="p-3">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width: 42px; height: 42px; background: rgba(245,158,11,0.12);">
                                <i class="bi bi-person-fill" style="font-size: 1.2rem; color: #f59e0b;"></i>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="fw-bold text-truncate" style="font-size: 1rem;">{{ order.customer_name }}</div>
                                <div class="text-secondary" style="font-size: 0.8rem;">
                                    <i class="bi bi-clock me-1"></i>{{ formatDate(order.start_time || order.created_at) }}
                                </div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="fw-bold fs-5" style="color: #f59e0b;">{{ formatRupiah(order.fnb_cost || 0) }}</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3 pt-2 border-top" style="border-color: rgba(255,255,255,0.06) !important;">
                            <span class="small text-secondary">
                                <i class="bi bi-cup-hot me-1"></i>{{ order.items?.length || 0 }} item
                            </span>
                            <span class="small text-secondary">
                                <i class="bi bi-basket me-1"></i>{{ order.items?.reduce((s, i) => s + i.quantity, 0) || 0 }} qty
                            </span>
                            <span class="ms-auto">
                                <i class="bi bi-chevron-right" style="color: #f59e0b;"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div v-else class="text-center py-5 text-secondary opacity-50 border rounded-4" style="border-color: rgba(255,255,255,0.05) !important;">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            Tidak ada pesanan aktif
        </div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- F&B Standalone Order — Create Modal                -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div v-if="showFnbOrderModal" class="bb-modal-backdrop">
            <div class="bb-modal" style="max-width: 900px; width: 95%;">
                <div class="bb-modal-header">
                    <div>
                        <h5 class="fw-bold mb-0"><i class="bi bi-cup-hot-fill me-2" style="color: #f59e0b;"></i>Pesan F&B</h5>
                        <p class="small text-secondary mb-0 mt-1">Pesanan baru tanpa sesi meja</p>
                    </div>
                    <button type="button" @click="closeFnbOrderModal" class="bb-theme-toggle" style="width: 32px; height: 32px; font-size: 0.9rem;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="bb-modal-body p-4">
                    <form @submit.prevent="submitFnbOrder">
                        <div class="row g-4">
                            <!-- Left: F&B Menu -->
                            <div class="col-md-7 border-end" style="border-color: rgba(255,255,255,0.1) !important;">
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
                                <div v-if="filteredFnbItems.length > 0" class="d-grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); max-height: 400px; overflow-y: auto; padding-right: 5px;">
                                    <div v-for="item in filteredFnbItems" :key="item.id" 
                                         class="bb-table-card p-0 overflow-hidden d-flex flex-column" style="border-radius: 0.75rem; cursor: pointer;"
                                         @click="addDraftToFnbOrder(item)">
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
                            
                            <!-- Right: Cart & Form -->
                            <div class="col-md-5">
                                <div class="mb-4">
                                    <label class="bb-label">Nama Pelanggan <span class="text-danger">*</span></label>
                                    <input type="text" v-model="fnbOrderForm.customer_name" 
                                           @input="fnbOrderError = ''; fnbOrderForm.customer_name = fnbOrderForm.customer_name.replace(/\b\w/g, l => l.toUpperCase())" 
                                           required class="bb-input" :class="{ 'border-danger': fnbOrderError && !fnbOrderForm.customer_name.trim() }" placeholder="Masukkan nama pelanggan..." />
                                    <div v-if="fnbOrderForm.errors.customer_name" class="small text-danger mt-1">{{ fnbOrderForm.errors.customer_name }}</div>
                                </div>
                                
                                <h6 class="fw-bold mb-3 d-flex justify-content-between align-items-center">
                                    Pesanan F&B
                                    <span v-if="fnbOrderForm.items.length > 0" class="badge rounded-pill px-2 py-1" style="background: rgba(245,158,11,0.15); color: #f59e0b; font-size: 0.7rem;">{{ fnbOrderForm.items.length }}</span>
                                </h6>
                                
                                <div v-if="fnbOrderForm.items.length > 0" class="d-flex flex-column gap-2 mb-3" style="max-height: 230px; overflow-y: auto;">
                                    <div v-for="(item, index) in fnbOrderForm.items" :key="item.fnb_item_id" class="p-2 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold small">{{ item.name }}</span>
                                            <button type="button" @click="removeDraftFnbOrder(index)" class="btn btn-sm text-danger p-0" title="Hapus">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-secondary small">{{ formatRupiah(item.price) }}</span>
                                            <div class="d-flex align-items-center gap-1">
                                                <button type="button" @click="item.quantity > 1 ? item.quantity-- : null" class="bb-btn bb-btn--ghost px-2 py-0 text-secondary" style="font-size: 0.75rem;">
                                                    <i class="bi bi-dash-lg"></i>
                                                </button>
                                                <input type="number" v-model.number="item.quantity" min="1" class="bb-input py-0 px-1 text-center" :class="{ 'border-danger text-danger': fnbOrderError && item.quantity < 1 }" style="width: 50px; height: 26px; font-size: 0.75rem; border: none; background: rgba(255,255,255,0.05);" />
                                                <button type="button" @click="item.quantity++" class="bb-btn bb-btn--ghost px-2 py-0 text-warning" style="font-size: 0.75rem;">
                                                    <i class="bi bi-plus-lg"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="rounded-3 p-4 mb-3 text-center" style="border: 2px dashed rgba(245,158,11,0.25); background: rgba(245,158,11,0.04);">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 56px; height: 56px; background: rgba(245,158,11,0.1);">
                                        <i class="bi bi-bag-plus" style="font-size: 1.5rem; color: #f59e0b;"></i>
                                    </div>
                                    <div class="fw-bold small mb-1" style="color: #f59e0b;">Keranjang Kosong</div>
                                    <div class="small" style="color: rgba(245,158,11,0.6);">Pilih menu F&B dari daftar sebelah kiri</div>
                                </div>

                                <div v-if="fnbOrderError" class="alert alert-danger rounded-3 mb-3 py-2 px-3 d-flex align-items-center gap-2" style="font-size: 0.85rem; background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #ef4444;">
                                    <i class="bi bi-exclamation-triangle-fill"></i> {{ fnbOrderError }}
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center p-3 rounded mb-4" style="background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2);">
                                    <span class="fw-bold" style="color: #f59e0b;">Total</span>
                                    <span class="fw-bold fs-5" style="color: #f59e0b;">{{ formatRupiah(fnbOrderDraftTotal) }}</span>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="button" @click="closeFnbOrderModal" class="bb-btn bb-btn--ghost flex-grow-1 py-3">
                                        Batal
                                    </button>
                                    <button type="submit" :disabled="fnbOrderForm.processing" class="bb-btn bb-btn--warning flex-grow-1 py-3">
                                        <i class="bi bi-check2-circle me-1"></i> Simpan Pesanan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- F&B Order Detail Modal (Standalone)                -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div v-if="showFnbDetailModal && selectedFnbOrder" class="bb-modal-backdrop">
            <div class="bb-modal" style="max-width: 900px; width: 95%;">
                <!-- Header -->
                <div class="bb-modal-header">
                    <div>
                        <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-cup-hot-fill" style="color: #f59e0b;"></i>
                            Pesanan F&B — {{ selectedFnbOrder.customer_name }}
                        </h5>
                        <div class="d-flex gap-3 mt-1 small text-secondary">
                            <span><i class="bi bi-clock me-1"></i>{{ formatDate(selectedFnbOrder.start_time || selectedFnbOrder.created_at) }}</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" @click="printReceipt(selectedFnbOrder)" class="bb-btn bb-btn--sm bb-btn--ghost text-warning" title="Cetak Struk" style="border: 1px solid rgba(245,158,11,0.3);">
                            <i class="bi bi-printer fs-5"></i>
                        </button>
                        <button @click="closeFnbDetailModal" class="bb-theme-toggle" style="width: 32px; height: 32px; font-size: 0.9rem;">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <div class="bb-modal-body p-4 formodal">
                    <form @submit.prevent>
                        <div class="row g-4">
                            <!-- Left: F&B Menu -->
                            <div class="col-md-7 border-end" style="border-color: rgba(255,255,255,0.1) !important;">
                                <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle text-warning me-2"></i>Tambah Item</h6>
                                <div class="mb-3 d-flex gap-2">
                                    <div class="position-relative flex-grow-1">
                                        <i class="bi bi-search position-absolute top-50 translate-middle-y ms-3 text-secondary"></i>
                                        <input type="text" v-model="fnbDetailSearch" class="bb-input ps-5" placeholder="Cari menu F&B..." />
                                    </div>
                                </div>
                                <div class="d-flex gap-2 overflow-auto pb-2 mb-3" style="scrollbar-width: none;">
                                    <button type="button" @click="fnbDetailCategoryFilter = 'all'"
                                            :class="['bb-btn bb-btn--sm flex-shrink-0', fnbDetailCategoryFilter === 'all' ? 'bb-btn--warning' : 'bb-btn--ghost']">
                                        Semua
                                    </button>
                                    <button type="button" v-for="cat in fnbDetailCategories" :key="cat"
                                            @click="fnbDetailCategoryFilter = cat"
                                            :class="['bb-btn bb-btn--sm flex-shrink-0', fnbDetailCategoryFilter === cat ? 'bb-btn--warning' : 'bb-btn--ghost']">
                                        {{ cat }}
                                    </button>
                                </div>
                                <div v-if="filteredFnbDetailItems.length > 0" class="d-grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); max-height: 400px; overflow-y: auto; padding-right: 5px;">
                                    <div v-for="item in filteredFnbDetailItems" :key="item.id" 
                                         class="bb-table-card p-0 overflow-hidden d-flex flex-column" style="border-radius: 0.75rem; cursor: pointer;"
                                         @click="addItemToFnbOrder(item)">
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
                                    <div class="small">{{ fnbDetailSearch ? 'Menu tidak ditemukan' : 'Belum ada menu F&B' }}</div>
                                </div>
                            </div>

                            <!-- Right: Order Items -->
                            <div class="col-md-5">
                                <h6 class="fw-bold mb-3 d-flex justify-content-between align-items-center">
                                    Daftar Item
                                    <span v-if="selectedFnbOrder?.items?.length > 0" class="badge rounded-pill px-2 py-1" style="background: rgba(245,158,11,0.15); color: #f59e0b; font-size: 0.7rem;">{{ selectedFnbOrder.items.length }}</span>
                                </h6>

                                <div v-if="selectedFnbOrder?.items?.length > 0" class="d-flex flex-column gap-2 mb-3" style="max-height: 360px; overflow-y: auto;">
                                    <div v-for="item in selectedFnbOrder.items" :key="item.id" class="p-2 rounded" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold small">{{ item.fnb_item?.name || 'Item' }}</span>
                                            <button type="button" @click="removeItemFromFnbOrder(item.id)" class="btn btn-sm text-danger p-0" title="Hapus">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-secondary small">{{ formatRupiah(item.price) }}</span>
                                            <div class="d-flex align-items-center gap-1">
                                                <button type="button" @click="decrementFnbOrderItem(item)" class="bb-btn bb-btn--ghost px-2 py-0 text-secondary" style="font-size: 0.75rem;">
                                                    <i class="bi bi-dash-lg"></i>
                                                </button>
                                                <input type="number" :value="item.quantity" min="1" class="bb-input py-0 px-1 text-center" style="width: 50px; height: 26px; font-size: 0.75rem; border: none; background: rgba(255,255,255,0.05);" readonly />
                                                <button type="button" @click="incrementFnbOrderItem(item)" class="bb-btn bb-btn--ghost px-2 py-0 text-warning" style="font-size: 0.75rem;">
                                                    <i class="bi bi-plus-lg"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="text-center py-4 text-secondary opacity-50 small mb-3 border rounded" style="border-color: rgba(255,255,255,0.1) !important;">
                                    <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                    Belum ada item
                                </div>

                                <div class="p-3 rounded-4 mb-4" style="background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2);">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold" style="color: #f59e0b;">Total</span>
                                        <span class="fw-bold fs-5" style="color: #f59e0b;">{{ formatRupiah(selectedFnbOrder.fnb_cost || 0) }}</span>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="button" @click="closeFnbDetailModal" class="bb-btn bb-btn--ghost flex-grow-1 py-3">
                                        Tutup
                                    </button>
                                    <button type="button" @click="checkoutFnbOrder" class="bb-btn bb-btn--danger flex-grow-1 py-3">
                                        <i class="bi bi-check2-circle me-1"></i> Checkout
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
