<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Printer, ArrowLeft, Truck, Package, Phone, MapPin, CheckCircle2, QrCode } from 'lucide-vue-next';

const props = defineProps({
    order: Object,
    storeInfo: Object,
    trackingUrl: String,
});

function printLabel() {
    window.print();
}

const formatCurrency = (val) => {
    return '৳' + Number(val || 0).toLocaleString('en-US');
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};
</script>

<template>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-950 p-4 sm:p-8 flex flex-col items-center">
        <Head :title="`Courier Label - Order ${order.order_number}`" />

        <!-- Print Control Bar (Hidden when printing) -->
        <div class="w-full max-w-xl mb-6 flex items-center justify-between print:hidden">
            <Link
                :href="route('admin.orders.show', order.id)"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-semibold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 transition-colors shadow-xs"
            >
                <ArrowLeft class="w-4 h-4" /> Back to Order
            </Link>

            <div class="flex items-center gap-2">
                <a
                    v-if="trackingUrl"
                    :href="trackingUrl"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-2xl text-xs font-semibold bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-indigo-600 hover:underline shadow-xs"
                >
                    <Truck class="w-4 h-4" /> Track Online
                </a>
                <button
                    type="button"
                    @click="printLabel"
                    class="inline-flex items-center gap-2 px-5 py-2 rounded-2xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-600/25 transition-all cursor-pointer"
                >
                    <Printer class="w-4 h-4" /> Print Shipping Label
                </button>
            </div>
        </div>

        <!-- Shipping Label Container (Designed for 4x6 inch thermal label or standard paper) -->
        <div class="w-full max-w-xl bg-white text-slate-900 border-2 border-slate-900 rounded-3xl p-6 sm:p-8 shadow-xl print:shadow-none print:border-2 print:border-black print:rounded-none print:p-6 print:w-full">
            <!-- Header: Courier & Invoice -->
            <div class="flex items-center justify-between border-b-2 border-slate-900 pb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center font-black text-lg">
                        <Truck class="w-6 h-6" />
                    </div>
                    <div>
                        <h2 class="text-base font-black uppercase tracking-tight text-slate-900">
                            {{ order.courier_provider === 'pathao' ? 'Pathao Courier' : 'Steadfast Courier' }}
                        </h2>
                        <p class="text-[11px] font-mono font-semibold text-slate-500 uppercase">Express Parcel Delivery</p>
                    </div>
                </div>

                <div class="text-right">
                    <p class="text-[11px] font-bold uppercase text-slate-500">Order Invoice</p>
                    <p class="text-sm font-black font-mono text-slate-900">{{ order.order_number }}</p>
                    <p class="text-[10px] text-slate-500">{{ formatDate(order.created_at) }}</p>
                </div>
            </div>

            <!-- Barcode & Tracking Code Section -->
            <div class="py-4 border-b-2 border-slate-900 text-center space-y-1">
                <div class="flex justify-center items-center gap-1 h-12 py-1">
                    <!-- Simulated high-density barcode stripes -->
                    <span v-for="n in 36" :key="n" class="bg-black h-full inline-block" :style="{ width: ((n * 13) % 4 + 1.5) + 'px', marginRight: ((n * 7) % 3 + 1) + 'px' }"></span>
                </div>
                <p class="font-mono text-base font-black tracking-widest text-slate-900">
                    {{ order.courier_tracking_code || order.courier_consignment_id || 'STF-PENDING' }}
                </p>
                <p class="text-[10px] font-mono text-slate-500">Consignment ID: {{ order.courier_consignment_id || '—' }}</p>
            </div>

            <!-- Deliver To (Customer) Section -->
            <div class="py-4 border-b-2 border-slate-900 space-y-2">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Recipient / Ship To:</p>
                <div>
                    <h3 class="text-lg font-black text-slate-900">
                        {{ order.shipping_address?.name || order.customer?.name || 'Customer' }}
                    </h3>
                    <p class="text-base font-black font-mono text-slate-950 flex items-center gap-1.5 mt-0.5">
                        <Phone class="w-4 h-4 text-slate-900" />
                        {{ order.shipping_address?.phone || order.customer?.phone || 'No Phone' }}
                    </p>
                </div>

                <div class="text-xs text-slate-800 leading-relaxed font-medium pt-1">
                    <p>{{ order.shipping_address?.address || order.customer?.address_line_1 }}</p>
                    <p>{{ order.shipping_address?.area }} {{ order.shipping_address?.city }} - {{ order.shipping_address?.postal_code }}</p>
                    <p class="font-bold uppercase text-[11px] text-slate-900">{{ order.shipping_address?.country || 'Bangladesh' }}</p>
                </div>
            </div>

            <!-- Big Cash on Delivery Box -->
            <div class="my-4 p-4 border-2 border-slate-900 rounded-2xl bg-slate-50 text-center">
                <p class="text-xs font-black uppercase tracking-wider text-slate-600">Cash on Delivery (COD) Amount</p>
                <div class="mt-1">
                    <span v-if="(order.courier_cod_amount ?? order.total) > 0" class="text-3xl font-black text-slate-950 tracking-tight">
                        {{ formatCurrency(order.courier_cod_amount ?? order.total) }}
                    </span>
                    <span v-else class="text-2xl font-black text-emerald-700 uppercase tracking-wide">
                        PREPAID - DO NOT COLLECT CASH
                    </span>
                </div>
                <div v-if="order.advance_delivery_charge > 0 && order.advance_payment_status === 'paid'" class="mt-1 text-[11px] font-semibold text-emerald-700 flex items-center justify-center gap-1">
                    <CheckCircle2 class="w-3.5 h-3.5" />
                    ৳{{ order.advance_delivery_charge }} Advance Paid via {{ order.advance_payment_method || 'bKash' }} (TrxID: {{ order.advance_transaction_id || 'Recorded' }})
                </div>
            </div>

            <!-- Items & Package Summary -->
            <div class="py-3 border-b-2 border-slate-900 text-xs">
                <div class="flex justify-between items-center mb-1 text-[11px] font-bold text-slate-500 uppercase">
                    <span>Item Summary ({{ order.items?.length || 0 }} items)</span>
                    <span>Qty</span>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-for="item in order.items" :key="item.id" class="py-1 flex justify-between items-center">
                        <span class="truncate max-w-[320px] font-medium text-slate-800">{{ item.product_name }}</span>
                        <span class="font-bold font-mono">× {{ item.quantity }}</span>
                    </div>
                </div>
            </div>

            <!-- Footer: Sender / Return Merchant Address -->
            <div class="pt-4 flex items-start justify-between text-[11px] text-slate-600 leading-snug">
                <div>
                    <p class="font-bold uppercase text-[10px] text-slate-400">Sender / Return To:</p>
                    <p class="font-bold text-slate-900">{{ storeInfo.name }}</p>
                    <p>{{ storeInfo.address }}</p>
                    <p class="font-mono">Helpline: {{ storeInfo.phone }}</p>
                </div>
                <div class="text-right">
                    <p class="font-bold uppercase text-[10px] text-slate-400">Instructions</p>
                    <p class="italic text-slate-700 max-w-[180px]">{{ order.notes || 'Please handle with care. Check before acceptance.' }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    body {
        background-color: white !important;
    }
}
</style>
