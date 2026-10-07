<script setup>
import { onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Printer, Store } from 'lucide-vue-next';

const props = defineProps({
    order: Object,
});

const formatCurrency = (val) => {
    return '৳' + Number(val || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
};

function printPage() {
    window.print();
}
</script>

<template>
    <div class="min-h-screen bg-slate-100 p-4 sm:p-8 font-sans text-slate-900 print:bg-white print:p-0">
        <Head :title="`Invoice - ${order.order_number}`" />

        <!-- Print Action Button (Hidden on Print) -->
        <div class="max-w-3xl mx-auto mb-4 flex justify-between items-center print:hidden">
            <button
                @click="printPage"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-md transition-colors"
            >
                <Printer class="w-4 h-4" /> Print / Save as PDF
            </button>
            <span class="text-xs text-slate-500">Press Ctrl+P / Cmd+P to print</span>
        </div>

        <!-- Invoice Paper Sheet -->
        <div class="max-w-3xl mx-auto bg-white border border-slate-200 rounded-3xl p-8 sm:p-12 shadow-xl print:shadow-none print:border-none print:p-4">
            <!-- Header -->
            <div class="flex justify-between items-start border-b border-slate-200 pb-8">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold">
                            <Store class="w-6 h-6" />
                        </div>
                        <span class="text-2xl font-bold tracking-tight text-slate-900">ApexStore</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">support@apexstore.io • +1 (555) 234-5678</p>
                    <p class="text-xs text-slate-500">100 Tech Blvd, Silicon Valley, CA 94025</p>
                </div>

                <div class="text-right">
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-wide">Invoice</h2>
                    <p class="text-xs font-mono font-bold text-indigo-600 mt-1">{{ order.order_number }}</p>
                    <p class="text-xs text-slate-500 mt-1">Date: {{ formatDate(order.created_at) }}</p>
                    <span class="inline-block mt-2 px-3 py-0.5 rounded-full text-xs font-bold uppercase bg-emerald-100 text-emerald-800">
                        {{ order.payment_status }}
                    </span>
                </div>
            </div>

            <!-- Addresses -->
            <div class="grid grid-cols-2 gap-8 py-8 border-b border-slate-200 text-xs">
                <div>
                    <h4 class="font-bold text-slate-400 uppercase tracking-wider mb-2">Billed To</h4>
                    <p class="font-bold text-slate-800 text-sm">{{ order.customer?.name }}</p>
                    <p class="text-slate-600">{{ order.customer?.email }}</p>
                    <p class="text-slate-600">{{ order.customer?.phone }}</p>
                    <p class="text-slate-600 mt-1">{{ order.customer?.address_line_1 }}</p>
                    <p class="text-slate-600">{{ order.customer?.city }}, {{ order.customer?.state }} {{ order.customer?.postal_code }}</p>
                </div>

                <div>
                    <h4 class="font-bold text-slate-400 uppercase tracking-wider mb-2">Payment Details</h4>
                    <p class="text-slate-700"><span class="font-semibold">Method:</span> {{ order.payment_method?.replace('_', ' ') }}</p>
                    <p class="text-slate-700"><span class="font-semibold">Status:</span> {{ order.payment_status }}</p>
                    <p class="text-slate-700"><span class="font-semibold">Fulfillment:</span> {{ order.status }}</p>
                </div>
            </div>

            <!-- Items Table -->
            <table class="w-full text-left my-8 text-xs">
                <thead>
                    <tr class="border-b border-slate-200 text-slate-500 font-semibold uppercase">
                        <th class="py-3">Item Description</th>
                        <th class="py-3 text-center">Unit Price</th>
                        <th class="py-3 text-center">Qty</th>
                        <th class="py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="item in order.items" :key="item.id">
                        <td class="py-3.5">
                            <p class="font-bold text-slate-800">{{ item.product_name }}</p>
                            <p class="font-mono text-[10px] text-slate-400">{{ item.sku }}</p>
                        </td>
                        <td class="py-3.5 text-center font-medium">{{ formatCurrency(item.unit_price) }}</td>
                        <td class="py-3.5 text-center font-bold">× {{ item.quantity }}</td>
                        <td class="py-3.5 text-right font-bold text-slate-900">{{ formatCurrency(item.total) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Summary Totals -->
            <div class="border-t border-slate-200 pt-6">
                <div class="max-w-xs ml-auto space-y-2 text-xs">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal:</span>
                        <span class="font-medium">{{ formatCurrency(order.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Tax:</span>
                        <span class="font-medium">{{ formatCurrency(order.tax) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>Shipping:</span>
                        <span class="font-medium">{{ formatCurrency(order.shipping_cost) }}</span>
                    </div>
                    <div class="pt-3 border-t border-slate-300 flex justify-between text-base font-bold text-slate-900">
                        <span>Total Paid:</span>
                        <span class="text-indigo-600">{{ formatCurrency(order.total) }}</span>
                    </div>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="mt-12 text-center text-xs text-slate-400 border-t border-slate-100 pt-6">
                <p>Thank you for your business! If you have questions about this invoice, contact support@apexstore.io</p>
            </div>
        </div>
    </div>
</template>
