<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Info, RefreshCw } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { xsrfToken } from '@/lib/xsrf';
import orderRoutes from '@/routes/order';
import salesRoutes from '@/routes/sales';
import type { Team } from '@/types';

type StaffOption = { id: number; name: string };

type OrderCard = {
    id: number;
    invoice_no: string | null;
    transaction_date: string | null;
    status: string;
    customer: { display_name: string } | null;
    business_location: { name: string } | null;
    created_by_name: string | null;
};

type SaleDetailLine = {
    id: number;
    product_id: number;
    product_name: string;
    sku: string | null;
    quantity: string;
    unit_price_before_discount: string;
    discount_percent: string;
    unit_price_exc_tax: string;
    product_tax_percent: string;
    line_total: string;
};

type SaleDetail = {
    id: number;
    invoice_no: string | null;
    transaction_date: string | null;
    status: string;
    payment_status?: string;
    created_by_user?: { id: number; name: string } | null;
    final_total: string;
    lines_total: string;
    sale_note: string | null;
    discount_type: string | null;
    discount_amount: string;
    sale_tax_amount: string;
    shipping_charges: string;
    customer: { id: number; display_name: string } | null;
    business_location: { id: number; name: string } | null;
    tax_rate: { id: number; name: string; amount: string } | null;
    lines: SaleDetailLine[];
    payments: Array<{
        id: number;
        amount: string;
        paid_on: string | null;
        method: string | null;
        note: string | null;
        payment_account: string | null;
    }>;
    activities: Array<{
        id: number;
        action: string;
        note: string | null;
        by: string | null;
        date: string | null;
    }>;
};

const props = defineProps<{
    serviceStaff: StaffOption[];
    serviceStaffFilter: string;
    lineOrders: OrderCard[];
    allOrders: OrderCard[];
}>();

const page = usePage();
const teamSlug = computed(() => (page.props.currentTeam as Team | null)?.slug ?? '');

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Orders',
                href: p.currentTeam ? orderRoutes.index.url(p.currentTeam.slug) : '#',
            },
        ],
    }),
});

const staffValue = ref(props.serviceStaffFilter);

watch(
    () => props.serviceStaffFilter,
    (v) => {
        staffValue.value = v;
    },
);

function refreshOrders() {
    router.reload({
        preserveScroll: true,
    });
}

function onStaffChange(value: string) {
    if (!teamSlug.value) {
        return;
    }

    const q =
        value === 'all'
            ? { service_staff: 'all' }
            : { service_staff: value };
    router.get(orderRoutes.index.url(teamSlug.value), q, {
        preserveScroll: true,
        replace: true,
    });
}

function formatWhen(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    const d = new Date(iso);

    return d.toLocaleString(undefined, {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatDecimal(v: string | number): string {
    const n = typeof v === 'string' ? Number.parseFloat(v) : v;

    if (Number.isNaN(n)) {
        return String(v);
    }

    return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

const detailOpen = ref(false);
const detailLoading = ref(false);
const detailError = ref<string | null>(null);
const detail = ref<SaleDetail | null>(null);

async function openOrderDetail(saleId: number) {
    detailOpen.value = true;
    detailLoading.value = true;
    detailError.value = null;
    detail.value = null;

    try {
        const res = await fetch(salesRoutes.detail.url(teamSlug.value, saleId), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken(),
            },
            credentials: 'same-origin',
        });

        if (!res.ok) {
            throw new Error('Could not load order details.');
        }

        const body = (await res.json()) as { sale: SaleDetail };
        detail.value = body.sale;
    } catch (e) {
        detailError.value =
            e instanceof Error ? e.message : 'Could not load order details.';
    } finally {
        detailLoading.value = false;
    }
}

function closeDetail() {
    detailOpen.value = false;
}

const invoiceLabel = computed(() => {
    if (!detail.value) {
        return '';
    }

    const inv = detail.value.invoice_no?.trim();

    return inv ? `#${inv}` : `#${detail.value.id}`;
});

function paymentMethodLabel(method: string | null): string {
    if (!method) {
        return '—';
    }

    const map: Record<string, string> = {
        advance: 'Advance',
        cash: 'Cash',
        card: 'Card',
        cheque: 'Cheque',
        bank_transfer: 'Bank transfer',
        other: 'Other',
        ledger: 'Ledger',
    };

    if (map[method]) {
        return map[method];
    }

    return method.replace(/_/g, ' ');
}
</script>

<template>
    <Head title="Orders" />

    <div class="print:hidden flex min-h-[70vh] flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col items-center gap-2 text-center">
            <h1 class="flex flex-wrap items-center justify-center gap-2 text-2xl font-semibold tracking-tight">
                All orders
                <TooltipProvider :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <button
                                type="button"
                                class="text-sky-600 inline-flex rounded-full p-1 hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                aria-label="About the orders screen"
                            >
                                <Info class="size-5 shrink-0" aria-hidden="true" />
                            </button>
                        </TooltipTrigger>
                        <TooltipContent
                            side="bottom"
                            class="max-w-sm text-left text-sm leading-snug"
                        >
                            This is the service staff screen. Service staff can use this screen to
                            view orders and open full order details.
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </h1>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2">
            <Button id="refresh_orders" type="button" class="gap-2" @click="refreshOrders">
                <RefreshCw class="size-4 shrink-0" aria-hidden="true" />
                Refresh
            </Button>
        </div>

        <input type="hidden" name="orders_for" value="waiter" />

        <div
            class="rounded-xl border border-border bg-card shadow-sm ring-1 ring-border/60 transition-shadow hover:shadow-md"
        >
            <div class="p-4 sm:p-5">
                <Label class="mb-2 block text-sm font-medium">Service staff</Label>
                <Select
                    :model-value="staffValue"
                    @update:model-value="(v) => v && onStaffChange(String(v))"
                >
                    <SelectTrigger class="max-w-md">
                        <SelectValue placeholder="Select service staff" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All staff</SelectItem>
                        <SelectItem v-for="m in serviceStaff" :key="m.id" :value="String(m.id)">
                            {{ m.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div
            class="rounded-xl border border-border bg-card shadow-sm ring-1 ring-border/60 transition-shadow hover:shadow-md"
        >
            <div class="border-b border-border px-4 py-3 sm:px-5">
                <h2 class="text-lg font-semibold">Line orders</h2>
            </div>
            <div class="p-4 sm:p-5">
                <div v-if="lineOrders.length === 0" class="text-muted-foreground py-8 text-center text-lg font-medium">
                    No orders found
                </div>
                <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="o in lineOrders"
                        :key="o.id"
                        class="rounded-lg border border-border bg-muted/30 p-4 shadow-sm"
                    >
                        <h3 class="mb-2 text-center text-base font-semibold">
                            {{ o.invoice_no?.trim() ? `#${o.invoice_no}` : `#${o.id}` }}
                        </h3>
                        <dl class="space-y-1 text-sm">
                            <div class="flex justify-between gap-2">
                                <dt class="text-muted-foreground">Placed at</dt>
                                <dd>{{ formatWhen(o.transaction_date) }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-muted-foreground">Status</dt>
                                <dd class="font-medium">{{ o.status }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-muted-foreground">Customer</dt>
                                <dd>{{ o.customer?.display_name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-muted-foreground">Location</dt>
                                <dd>{{ o.business_location?.name ?? '—' }}</dd>
                            </div>
                        </dl>
                        <Button variant="secondary" class="mt-4 w-full" type="button" @click="openOrderDetail(o.id)">
                            Order details
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="rounded-xl border border-border bg-card shadow-sm ring-1 ring-border/60 transition-shadow hover:shadow-md"
        >
            <div class="border-b border-border px-4 py-3 sm:px-5">
                <h2 class="text-lg font-semibold">All your orders</h2>
            </div>
            <div class="p-4 sm:p-5">
                <div
                    v-if="allOrders.length === 0"
                    class="text-muted-foreground py-8 text-center text-lg font-medium"
                >
                    No orders found
                </div>
                <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="o in allOrders"
                        :key="o.id"
                        class="rounded-lg border border-border bg-muted/30 p-4 shadow-sm"
                    >
                        <h3 class="mb-2 text-center text-base font-semibold">
                            {{ o.invoice_no?.trim() ? `#${o.invoice_no}` : `#${o.id}` }}
                        </h3>
                        <dl class="space-y-1 text-sm">
                            <div class="flex justify-between gap-2">
                                <dt class="text-muted-foreground">Placed at</dt>
                                <dd>{{ formatWhen(o.transaction_date) }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-muted-foreground">Status</dt>
                                <dd class="font-medium">{{ o.status }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-muted-foreground">Customer</dt>
                                <dd>{{ o.customer?.display_name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-2">
                                <dt class="text-muted-foreground">Location</dt>
                                <dd>{{ o.business_location?.name ?? '—' }}</dd>
                            </div>
                        </dl>
                        <Button variant="secondary" class="mt-4 w-full" type="button" @click="openOrderDetail(o.id)">
                            Order details
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <Dialog v-model:open="detailOpen">
            <DialogContent class="flex max-h-[90vh] max-w-4xl flex-col gap-0 overflow-hidden p-0">
                <DialogHeader class="border-b border-border px-6 py-4 text-left">
                    <DialogTitle>Sell details (Invoice {{ invoiceLabel }})</DialogTitle>
                    <DialogDescription class="sr-only">Order line items and payment summary</DialogDescription>
                </DialogHeader>

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
                    <div v-if="detailLoading" class="flex justify-center py-12">
                        <Spinner class="size-8" />
                    </div>
                    <p v-else-if="detailError" class="text-destructive text-sm">
                        {{ detailError }}
                    </p>
                    <div v-else-if="detail" class="space-y-6 text-sm">
                        <div class="flex flex-wrap justify-end gap-2">
                            <p>
                                <span class="font-medium">Date:</span>
                                {{ formatWhen(detail.transaction_date) }}
                            </p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <div><span class="font-medium">Invoice:</span> {{ invoiceLabel }}</div>
                                <div><span class="font-medium">Status:</span> {{ detail.status }}</div>
                                <div v-if="detail.payment_status">
                                    <span class="font-medium">Payment:</span>
                                    {{ detail.payment_status === 'paid' ? 'Paid' : 'Due' }}
                                </div>
                            </div>
                            <div>
                                <div>
                                    <span class="font-medium">Customer:</span>
                                    {{ detail.customer?.display_name ?? '—' }}
                                </div>
                            </div>
                            <div>
                                <div>
                                    <span class="font-medium">Location:</span>
                                    {{ detail.business_location?.name ?? '—' }}
                                </div>
                                <div v-if="detail.created_by_user">
                                    <span class="font-medium">Service staff:</span>
                                    {{ detail.created_by_user.name }}
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="mb-2 text-base font-semibold">Products</h3>
                            <div class="overflow-x-auto rounded-md border">
                                <table class="w-full border-collapse text-xs">
                                    <thead>
                                        <tr class="border-b bg-muted/50">
                                            <th class="px-2 py-2 text-left">#</th>
                                            <th class="px-2 py-2 text-left">Product</th>
                                            <th class="px-2 py-2 text-right">Qty</th>
                                            <th class="px-2 py-2 text-right">Unit</th>
                                            <th class="px-2 py-2 text-right">Disc %</th>
                                            <th class="px-2 py-2 text-right">Tax %</th>
                                            <th class="px-2 py-2 text-right">Line</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(line, idx) in detail.lines"
                                            :key="line.id"
                                            class="border-b border-border/60"
                                        >
                                            <td class="px-2 py-2">{{ idx + 1 }}</td>
                                            <td class="px-2 py-2">
                                                {{ line.product_name }}
                                                <span v-if="line.sku" class="text-muted-foreground">
                                                    ({{ line.sku }})
                                                </span>
                                            </td>
                                            <td class="px-2 py-2 text-right">{{ formatDecimal(line.quantity) }}</td>
                                            <td class="px-2 py-2 text-right">
                                                {{ formatDecimal(line.unit_price_exc_tax) }}
                                            </td>
                                            <td class="px-2 py-2 text-right">
                                                {{ formatDecimal(line.discount_percent) }}
                                            </td>
                                            <td class="px-2 py-2 text-right">
                                                {{ formatDecimal(line.product_tax_percent) }}
                                            </td>
                                            <td class="px-2 py-2 text-right">
                                                {{ formatDecimal(line.line_total) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div>
                            <h3 class="mb-2 text-base font-semibold">Payment info</h3>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="overflow-x-auto rounded-md border">
                                    <table class="w-full border-collapse text-xs">
                                        <thead>
                                            <tr class="border-b bg-muted/50">
                                                <th class="px-2 py-2 text-left">#</th>
                                                <th class="px-2 py-2 text-left">Date</th>
                                                <th class="px-2 py-2 text-right">Amount</th>
                                                <th class="px-2 py-2 text-left">Mode</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="detail.payments.length === 0">
                                                <td colspan="4" class="text-muted-foreground px-2 py-4 text-center">
                                                    No payments recorded
                                                </td>
                                            </tr>
                                            <tr
                                                v-for="(p, idx) in detail.payments"
                                                :key="p.id"
                                                class="border-b border-border/60"
                                            >
                                                <td class="px-2 py-2">{{ idx + 1 }}</td>
                                                <td class="px-2 py-2">{{ formatWhen(p.paid_on) }}</td>
                                                <td class="px-2 py-2 text-right">{{ formatDecimal(p.amount) }}</td>
                                                <td class="px-2 py-2">{{ paymentMethodLabel(p.method) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="space-y-2 rounded-md border p-3">
                                    <div class="flex justify-between">
                                        <span class="font-medium">Total</span>
                                        <span>{{ formatDecimal(detail.final_total) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Lines total</span>
                                        <span>{{ formatDecimal(detail.lines_total) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Discount</span>
                                        <span>{{ formatDecimal(detail.discount_amount) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Order tax</span>
                                        <span>{{ formatDecimal(detail.sale_tax_amount) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Shipping</span>
                                        <span>{{ formatDecimal(detail.shipping_charges) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="detail.sale_note" class="rounded-md border p-3">
                            <div class="text-muted-foreground mb-1 text-xs font-medium">Sell note</div>
                            <div class="whitespace-pre-wrap">{{ detail.sale_note }}</div>
                        </div>

                        <div>
                            <h3 class="mb-2 text-base font-semibold">Activities</h3>
                            <div class="overflow-x-auto rounded-md border">
                                <table class="w-full border-collapse text-xs">
                                    <thead>
                                        <tr class="border-b bg-muted/50">
                                            <th class="px-2 py-2 text-left">Date</th>
                                            <th class="px-2 py-2 text-left">Action</th>
                                            <th class="px-2 py-2 text-left">By</th>
                                            <th class="px-2 py-2 text-left">Note</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="detail.activities.length === 0">
                                            <td colspan="4" class="text-muted-foreground px-2 py-4 text-center">
                                                No records found
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="a in detail.activities"
                                            :key="a.id"
                                            class="border-b border-border/60"
                                        >
                                            <td class="px-2 py-2">{{ formatWhen(a.date) }}</td>
                                            <td class="px-2 py-2">{{ a.action }}</td>
                                            <td class="px-2 py-2">{{ a.by ?? '—' }}</td>
                                            <td class="px-2 py-2">{{ a.note ?? '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter class="border-t border-border px-6 py-4">
                    <Button type="button" variant="outline" @click="closeDetail">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
