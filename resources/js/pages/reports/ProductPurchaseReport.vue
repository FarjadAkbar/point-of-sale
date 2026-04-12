<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Printer } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { reportRowMatchesSearch } from '@/lib/reportTableSearch';
import { xsrfToken } from '@/lib/xsrf';
import productRoutes from '@/routes/products';
import purchaseRoutes from '@/routes/purchases';
import reportRoutes from '@/routes/reports';
import type { Team } from '@/types';

type ReportRow = {
    purchase_id: number;
    purchase_line_id: number;
    product_name: string;
    sku: string;
    supplier: string;
    reference_no: string;
    date: string;
    quantity: string;
    quantity_adjusted: string;
    unit_label: string;
    unit_price: string;
    subtotal: string;
};

type PurchaseDetail = {
    id: number;
    ref_no: string | null;
    transaction_date: string | null;
    status: string;
    payment_status: string;
    pay_term_number: number | null;
    pay_term_type: string | null;
    discount_type: string;
    discount_amount: string;
    lines_total: string;
    purchase_tax_amount: string;
    shipping_details: string | null;
    shipping_charges: string;
    additional_notes: string | null;
    additional_expenses: unknown[];
    final_total: string;
    supplier: {
        display_name: string;
        business_name: string | null;
        first_name: string | null;
        last_name: string | null;
        tax_number: string | null;
        mobile: string | null;
        email: string | null;
        address_line_1: string | null;
        address_line_2: string | null;
        city: string | null;
        state: string | null;
        country: string | null;
        zip_code: string | null;
    } | null;
    business_location: {
        name: string;
        landmark: string | null;
        city: string | null;
        state: string | null;
        country: string | null;
        zip_code: string | null;
        mobile: string | null;
        email: string | null;
    } | null;
    tax_rate: { name: string; amount: string } | null;
    lines: Array<{
        id: number;
        product_name: string;
        sku: string | null;
        quantity: string;
        unit_short_name: string;
        unit_cost_before_discount: string;
        discount_percent: string;
        unit_cost_exc_tax: string;
        line_subtotal_exc_tax: string;
        product_tax_percent: string;
        line_tax_amount: string;
        line_total: string;
        unit_cost_inc_tax: string;
    }>;
    payments: Array<{
        id: number;
        amount: string;
        paid_on: string | null;
        method: string;
        note: string | null;
        payment_account: string | null;
    }>;
    activities: Array<Record<string, unknown>>;
};

const props = defineProps<{
    filters: {
        start_date: string;
        end_date: string;
        business_location_id: number | null;
        supplier_id: number | null;
        brand_id: number | null;
        product_id: number | null;
        search_product: string | null;
    };
    businessLocations: Array<{ id: number; name: string }>;
    suppliers: Array<{ id: number; label: string }>;
    brands: Array<{ id: number; name: string }>;
    rows: ReportRow[];
    footer: {
        quantity: string;
        quantity_adjusted: string;
        subtotal: string;
    };
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Reports',
                href: p.currentTeam ? reportRoutes.profitLoss.url(p.currentTeam.slug) : '#',
            },
            {
                title: 'Product purchase report',
                href: p.currentTeam ? reportRoutes.productPurchase.url(p.currentTeam.slug) : '#',
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(() => (page.props.currentTeam as Team | null)?.slug ?? '');

const search = ref('');
const perPage = ref('25');
const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const locationId = ref<string>(
    props.filters.business_location_id != null ? String(props.filters.business_location_id) : '',
);
const supplierId = ref<string>(props.filters.supplier_id != null ? String(props.filters.supplier_id) : '');
const brandId = ref<string>(props.filters.brand_id != null ? String(props.filters.brand_id) : '');
const productId = ref<number | null>(props.filters.product_id);
const productSearch = ref(props.filters.search_product ?? '');
const productHits = ref<{ id: number; name: string; sku: string | null; text: string }[]>([]);

const modalOpen = ref(false);
const detailLoading = ref(false);
const detailError = ref<string | null>(null);
const purchaseDetail = ref<PurchaseDetail | null>(null);

watch(
    () => props.filters,
    (f) => {
        startDate.value = f.start_date;
        endDate.value = f.end_date;
        locationId.value = f.business_location_id != null ? String(f.business_location_id) : '';
        supplierId.value = f.supplier_id != null ? String(f.supplier_id) : '';
        brandId.value = f.brand_id != null ? String(f.brand_id) : '';
        productId.value = f.product_id;
        productSearch.value = f.search_product ?? '';
    },
    { deep: true },
);

const filteredRows = computed(() =>
    props.rows.filter((r) => reportRowMatchesSearch(r, search.value)),
);

function currency(n: string) {
    const v = parseFloat(n);

    if (Number.isNaN(v)) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(v);
}

function qtyCell(q: string, unit: string) {
    const v = parseFloat(q);

    if (Number.isNaN(v)) {
        return '—';
    }

    const u = unit?.trim() || '';

    return `${new Intl.NumberFormat(undefined, { maximumFractionDigits: 4 }).format(v)}${u ? ` ${u}` : ''}`;
}

function refLabel(ref: string | null | undefined) {
    if (!ref) {
        return '—';
    }

    return ref.startsWith('#') ? ref : `#${ref}`;
}

function formatStatus(s: string) {
    return s.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatDate(iso: string | null | undefined) {
    if (!iso) {
        return '—';
    }

    const d = new Date(iso);

    if (Number.isNaN(d.getTime())) {
        return '—';
    }

    return d.toLocaleDateString();
}

function formatDateTime(iso: string | null | undefined) {
    if (!iso) {
        return '—';
    }

    const d = new Date(iso);

    if (Number.isNaN(d.getTime())) {
        return '—';
    }

    return d.toLocaleString();
}

function supplierLines(s: PurchaseDetail['supplier']): string[] {
    if (!s) {
        return [];
    }

    const nameParts = [s.business_name, [s.first_name, s.last_name].filter(Boolean).join(' ')].filter(
        (x) => x && String(x).trim(),
    );
    const lines: string[] = [];

    if (nameParts.length) {
        lines.push(nameParts.join(', '));
    } else if (s.display_name) {
        lines.push(s.display_name);
    }

    const cityLine = [s.city, s.state, s.zip_code]
        .filter((x): x is string => x != null && String(x).trim() !== '')
        .join(', ');
    const addr = [s.address_line_1, s.address_line_2, cityLine || null, s.country].filter(
        (x): x is string => x != null && String(x).trim() !== '',
    );
    lines.push(...addr);

    if (s.tax_number) {
        lines.push(`Tax number: ${s.tax_number}`);
    }

    if (s.mobile) {
        lines.push(`Mobile: ${s.mobile}`);
    }

    if (s.email) {
        lines.push(`Email: ${s.email}`);
    }

    return lines;
}

function businessAddressLines(loc: PurchaseDetail['business_location']): string[] {
    if (!loc) {
        return [];
    }

    const lines: string[] = [];

    if (loc.landmark) {
        lines.push(loc.landmark);
    }

    const cityLine = [loc.city, loc.state, loc.country].filter(Boolean).join(', ');

    if (cityLine) {
        lines.push(cityLine);
    }

    if (loc.zip_code) {
        lines.push(loc.zip_code);
    }

    if (loc.mobile) {
        lines.push(`Tel: ${loc.mobile}`);
    }

    if (loc.email) {
        lines.push(loc.email);
    }

    return lines;
}

function applyFilters() {
    const q: Record<string, string> = {
        start_date: startDate.value,
        end_date: endDate.value,
    };

    if (locationId.value) {
        q.business_location_id = locationId.value;
    }

    if (supplierId.value) {
        q.supplier_id = supplierId.value;
    }

    if (brandId.value) {
        q.brand_id = brandId.value;
    }

    if (productId.value != null) {
        q.product_id = String(productId.value);
    }

    const sp = productSearch.value.trim();

    if (sp) {
        q.search_product = sp;
    }

    router.get(reportRoutes.productPurchase.url(teamSlug.value), q, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

const debouncedProductSearch = useDebounceFn(async () => {
    const t = productSearch.value.trim();

    if (t.length < 1) {
        productHits.value = [];

        return;
    }

    const q: Record<string, string> = { q: t };

    if (locationId.value) {
        q.business_location_id = locationId.value;
    }

    const url = productRoutes.search.url(teamSlug.value, { query: q });
    const r = await fetch(url, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    });
    const j = (await r.json()) as {
        data: { id: number; name: string; sku: string | null; text: string }[];
    };
    productHits.value = j.data ?? [];
}, 300);

watch(productSearch, () => {
    productId.value = null;
    debouncedProductSearch();
});

function pickProduct(p: { id: number; name: string; sku: string | null; text: string }) {
    productId.value = p.id;
    productSearch.value = p.text;
    productHits.value = [];
}

function clearProduct() {
    productId.value = null;
    productSearch.value = '';
    productHits.value = [];
}

async function openPurchaseModal(id: number) {
    modalOpen.value = true;
    detailLoading.value = true;
    detailError.value = null;
    purchaseDetail.value = null;

    try {
        const res = await fetch(
            purchaseRoutes.detail.url({ current_team: teamSlug.value, purchase: id }),
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                credentials: 'same-origin',
            },
        );

        if (!res.ok) {
            throw new Error('Could not load purchase.');
        }

        const body = (await res.json()) as { purchase: PurchaseDetail };
        purchaseDetail.value = body.purchase;
    } catch (e) {
        detailError.value = e instanceof Error ? e.message : 'Could not load purchase.';
    } finally {
        detailLoading.value = false;
    }
}

function closeModal() {
    modalOpen.value = false;
}

function triggerPrint(): void {
    globalThis.print();
}

function additionalExpensesSum(exp: unknown): number {
    if (!Array.isArray(exp)) {
        return 0;
    }

    let s = 0;

    for (const row of exp) {
        if (row && typeof row === 'object' && 'amount' in row) {
            const n = parseFloat(String((row as { amount?: unknown }).amount));

            if (!Number.isNaN(n)) {
                s += n;
            }
        }
    }

    return s;
}
</script>

<template>
    <Head title="Product purchase report" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Product purchase report</h1>
            <p class="text-muted-foreground text-sm">
                Received purchase lines by location, supplier, brand, product, and date range. Click a reference number
                to view purchase details.
            </p>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            class="print:hidden"
            search-placeholder="Search table…"
            :show-pagination="false"
            :show-per-page="false"
        >
            <template #filters>
                <div class="relative space-y-2">
                    <Label for="ppr-product">Search product</Label>
                    <div class="relative">
                        <Input
                            id="ppr-product"
                            v-model="productSearch"
                            type="text"
                            autocomplete="off"
                            placeholder="Name, SKU, or barcode"
                            class="pr-20"
                        />
                        <Button
                            v-if="productSearch || productId"
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="absolute top-1 right-1 h-7 text-xs"
                            @click="clearProduct"
                        >
                            Clear
                        </Button>
                    </div>
                    <ul
                        v-if="productHits.length"
                        class="border-border bg-popover absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-md border text-sm shadow-md"
                    >
                        <li
                            v-for="h in productHits"
                            :key="h.id"
                            class="hover:bg-muted/60 cursor-pointer px-3 py-2"
                            @mousedown.prevent="pickProduct(h)"
                        >
                            {{ h.text }}
                        </li>
                    </ul>
                </div>
                <div class="space-y-2">
                    <Label for="ppr-supplier">Supplier</Label>
                    <select
                        id="ppr-supplier"
                        v-model="supplierId"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">All suppliers</option>
                        <option v-for="s in suppliers" :key="s.id" :value="String(s.id)">
                            {{ s.label }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label for="ppr-loc">Business location</Label>
                    <select
                        id="ppr-loc"
                        v-model="locationId"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">Select location</option>
                        <option v-for="loc in businessLocations" :key="loc.id" :value="String(loc.id)">
                            {{ loc.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label for="ppr-brand">Brand</Label>
                    <select
                        id="ppr-brand"
                        v-model="brandId"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">All brands</option>
                        <option v-for="b in brands" :key="b.id" :value="String(b.id)">
                            {{ b.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label>Date range</Label>
                    <div class="flex flex-wrap items-center gap-2">
                        <Input v-model="startDate" type="date" class="min-w-[10rem]" />
                        <span class="text-muted-foreground text-sm">to</span>
                        <Input v-model="endDate" type="date" class="min-w-[10rem]" />
                    </div>
                </div>
                <div class="pt-1">
                    <Button type="button" size="sm" class="w-full" @click="applyFilters">Apply filters</Button>
                </div>
            </template>
            <template #toolbar-actions>
                <Button variant="outline" type="button" size="sm" @click="triggerPrint">
                    <Printer class="mr-2 size-4" />
                    Print
                </Button>
            </template>
            <div class="rounded-md border border-border overflow-x-auto">
                <table class="w-full min-w-[920px] border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/40">
                            <th class="px-2 py-2 text-left font-medium">Product</th>
                            <th class="px-2 py-2 text-left font-medium">SKU</th>
                            <th class="px-2 py-2 text-left font-medium">Supplier</th>
                            <th class="px-2 py-2 text-left font-medium">Reference no</th>
                            <th class="px-2 py-2 text-left font-medium">Date</th>
                            <th class="px-2 py-2 text-right font-medium">Quantity</th>
                            <th class="px-2 py-2 text-right font-medium">Total unit adjusted</th>
                            <th class="px-2 py-2 text-right font-medium">Unit purchase price</th>
                            <th class="px-2 py-2 text-right font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="rows.length === 0">
                            <td colspan="9" class="text-muted-foreground px-3 py-6 text-center">
                                Select a business location and apply filters to load purchase lines.
                            </td>
                        </tr>
                        <tr v-else-if="filteredRows.length === 0">
                            <td colspan="9" class="text-muted-foreground px-3 py-6 text-center">
                                No rows match your search.
                            </td>
                        </tr>
                        <tr
                            v-for="row in filteredRows"
                            :key="row.purchase_line_id"
                            class="border-b border-border/80 hover:bg-muted/20"
                        >
                            <td class="px-2 py-2 max-w-[10rem] truncate" :title="row.product_name">
                                {{ row.product_name }}
                            </td>
                            <td class="px-2 py-2 font-mono text-xs">{{ row.sku }}</td>
                            <td class="px-2 py-2 max-w-[10rem] whitespace-pre-line text-xs" :title="row.supplier">
                                {{ row.supplier || '—' }}
                            </td>
                            <td class="px-2 py-2">
                                <button
                                    type="button"
                                    class="text-primary cursor-pointer underline-offset-4 hover:underline print:hidden"
                                    @click="openPurchaseModal(row.purchase_id)"
                                >
                                    {{ row.reference_no }}
                                </button>
                                <span class="hidden print:inline">{{ row.reference_no }}</span>
                            </td>
                            <td class="px-2 py-2 whitespace-nowrap">{{ row.date || '—' }}</td>
                            <td class="px-2 py-2 text-right tabular-nums">
                                {{ qtyCell(row.quantity, row.unit_label) }}
                            </td>
                            <td class="px-2 py-2 text-right tabular-nums">
                                {{ qtyCell(row.quantity_adjusted, row.unit_label) }}
                            </td>
                            <td class="px-2 py-2 text-right tabular-nums">{{ currency(row.unit_price) }}</td>
                            <td class="px-2 py-2 text-right tabular-nums">{{ currency(row.subtotal) }}</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length">
                        <tr class="bg-muted/50 font-medium">
                            <td class="px-2 py-2" colspan="5">Total</td>
                            <td class="px-2 py-2 text-right tabular-nums">{{ footer.quantity }}</td>
                            <td class="px-2 py-2 text-right tabular-nums">{{ footer.quantity_adjusted }}</td>
                            <td class="px-2 py-2" />
                            <td class="px-2 py-2 text-right tabular-nums">{{ currency(footer.subtotal) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </StandardDataTable>

        <Dialog v-model:open="modalOpen">
            <DialogContent class="flex max-h-[90vh] max-w-4xl flex-col gap-0 overflow-hidden p-0 sm:max-w-4xl">
                <DialogHeader class="border-b border-border shrink-0 px-6 py-4 text-left">
                    <DialogTitle class="text-lg">
                        Purchase details
                        <span v-if="purchaseDetail" class="text-muted-foreground font-normal">
                            (Reference: {{ refLabel(purchaseDetail.ref_no) }})
                        </span>
                    </DialogTitle>
                </DialogHeader>

                <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
                    <p v-if="detailLoading" class="text-muted-foreground text-sm">Loading…</p>
                    <p v-else-if="detailError" class="text-destructive text-sm">{{ detailError }}</p>
                    <template v-else-if="purchaseDetail">
                        <div class="mb-4 flex justify-end text-sm">
                            <span><strong>Date:</strong> {{ formatDate(purchaseDetail.transaction_date) }}</span>
                        </div>

                        <div class="grid gap-6 md:grid-cols-3">
                            <div class="text-sm">
                                <p class="text-muted-foreground mb-1 font-medium">Supplier</p>
                                <address class="not-italic whitespace-pre-line">
                                    {{ supplierLines(purchaseDetail.supplier).join('\n') || '—' }}
                                </address>
                            </div>
                            <div class="text-sm">
                                <p class="text-muted-foreground mb-1 font-medium">Business</p>
                                <address class="not-italic whitespace-pre-line">
                                    <template v-if="purchaseDetail.business_location">
                                        <strong>{{ purchaseDetail.business_location.name }}</strong>
                                        <template
                                            v-for="(line, i) in businessAddressLines(purchaseDetail.business_location)"
                                            :key="i"
                                        >
                                            <br />
                                            {{ line }}
                                        </template>
                                    </template>
                                    <template v-else>—</template>
                                </address>
                            </div>
                            <div class="text-sm">
                                <p>
                                    <strong>Reference no:</strong> {{ refLabel(purchaseDetail.ref_no) }}
                                </p>
                                <p>
                                    <strong>Date:</strong> {{ formatDate(purchaseDetail.transaction_date) }}
                                </p>
                                <p>
                                    <strong>Purchase status:</strong>
                                    {{ formatStatus(purchaseDetail.status) }}
                                </p>
                                <p>
                                    <strong>Payment status:</strong> {{ purchaseDetail.payment_status }}
                                </p>
                                <p v-if="purchaseDetail.tax_rate" class="mt-1">
                                    <strong>Purchase tax:</strong> {{ purchaseDetail.tax_rate.name }} ({{
                                        purchaseDetail.tax_rate.amount
                                    }}%)
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 overflow-x-auto rounded-md border border-border">
                            <table class="w-full min-w-[720px] border-collapse text-xs">
                                <thead>
                                    <tr class="bg-muted/50">
                                        <th class="px-2 py-2 text-left font-medium">#</th>
                                        <th class="px-2 py-2 text-left font-medium">Product</th>
                                        <th class="px-2 py-2 text-left font-medium">SKU</th>
                                        <th class="px-2 py-2 text-right font-medium">Qty</th>
                                        <th class="px-2 py-2 text-right font-medium">Unit (before discount)</th>
                                        <th class="px-2 py-2 text-right font-medium">Disc %</th>
                                        <th class="px-2 py-2 text-right font-medium">Unit (exc. tax)</th>
                                        <th class="px-2 py-2 text-right font-medium">Subtotal (exc. tax)</th>
                                        <th class="px-2 py-2 text-right font-medium">Tax</th>
                                        <th class="px-2 py-2 text-right font-medium">Unit (inc. tax)</th>
                                        <th class="px-2 py-2 text-right font-medium">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(line, idx) in purchaseDetail.lines"
                                        :key="line.id"
                                        class="border-t border-border/80"
                                    >
                                        <td class="px-2 py-2">{{ idx + 1 }}</td>
                                        <td class="px-2 py-2">{{ line.product_name }}</td>
                                        <td class="px-2 py-2 font-mono">{{ line.sku || '—' }}</td>
                                        <td class="px-2 py-2 text-right tabular-nums">
                                            {{ qtyCell(line.quantity, line.unit_short_name) }}
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums">
                                            {{ currency(line.unit_cost_before_discount) }}
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums">
                                            {{ line.discount_percent }}%
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums">
                                            {{ currency(line.unit_cost_exc_tax) }}
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums">
                                            {{ currency(line.line_subtotal_exc_tax) }}
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums">
                                            {{ currency(line.line_tax_amount) }}
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums">
                                            {{ currency(line.unit_cost_inc_tax) }}
                                        </td>
                                        <td class="px-2 py-2 text-right tabular-nums">
                                            {{ currency(line.line_total) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 grid gap-6 md:grid-cols-2">
                            <div>
                                <h4 class="mb-2 font-medium">Payments</h4>
                                <div class="overflow-x-auto rounded-md border border-border">
                                    <table class="w-full border-collapse text-xs">
                                        <thead>
                                            <tr class="bg-muted/50">
                                                <th class="px-2 py-2 text-left font-medium">#</th>
                                                <th class="px-2 py-2 text-left font-medium">Date</th>
                                                <th class="px-2 py-2 text-left font-medium">Amount</th>
                                                <th class="px-2 py-2 text-left font-medium">Method</th>
                                                <th class="px-2 py-2 text-left font-medium">Note</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="!purchaseDetail.payments.length">
                                                <td colspan="5" class="text-muted-foreground px-2 py-4 text-center">
                                                    No payments found
                                                </td>
                                            </tr>
                                            <template v-else>
                                                <tr
                                                    v-for="(p, pi) in purchaseDetail.payments"
                                                    :key="p.id"
                                                    class="border-t border-border/80"
                                                >
                                                    <td class="px-2 py-2">{{ pi + 1 }}</td>
                                                    <td class="px-2 py-2 whitespace-nowrap">
                                                        {{ formatDateTime(p.paid_on) }}
                                                    </td>
                                                    <td class="px-2 py-2 tabular-nums">{{ currency(p.amount) }}</td>
                                                    <td class="px-2 py-2">
                                                        {{ p.payment_account || p.method || '—' }}
                                                    </td>
                                                    <td class="px-2 py-2">{{ p.note || '—' }}</td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div>
                                <h4 class="mb-2 font-medium">Totals</h4>
                                <div class="overflow-x-auto rounded-md border border-border">
                                    <table class="w-full border-collapse text-xs">
                                        <tbody>
                                            <tr class="border-t border-border/80">
                                                <th class="px-2 py-2 text-left font-medium">Net total (lines)</th>
                                                <td class="px-2 py-2 text-right tabular-nums">
                                                    {{ currency(purchaseDetail.lines_total) }}
                                                </td>
                                            </tr>
                                            <tr class="border-t border-border/80">
                                                <th class="px-2 py-2 text-left font-medium">Discount</th>
                                                <td class="px-2 py-2 text-right tabular-nums">
                                                    {{ currency(purchaseDetail.discount_amount) }}
                                                </td>
                                            </tr>
                                            <tr class="border-t border-border/80">
                                                <th class="px-2 py-2 text-left font-medium">Purchase tax</th>
                                                <td class="px-2 py-2 text-right tabular-nums">
                                                    {{ currency(purchaseDetail.purchase_tax_amount) }}
                                                </td>
                                            </tr>
                                            <tr class="border-t border-border/80">
                                                <th class="px-2 py-2 text-left font-medium">Shipping</th>
                                                <td class="px-2 py-2 text-right tabular-nums">
                                                    {{ currency(purchaseDetail.shipping_charges) }}
                                                </td>
                                            </tr>
                                            <tr
                                                v-if="additionalExpensesSum(purchaseDetail.additional_expenses) > 0"
                                                class="border-t border-border/80"
                                            >
                                                <th class="px-2 py-2 text-left font-medium">Additional expenses</th>
                                                <td class="px-2 py-2 text-right tabular-nums">
                                                    {{
                                                        currency(
                                                            String(additionalExpensesSum(purchaseDetail.additional_expenses)),
                                                        )
                                                    }}
                                                </td>
                                            </tr>
                                            <tr class="border-t border-border/80 font-medium">
                                                <th class="px-2 py-2 text-left">Purchase total</th>
                                                <td class="px-2 py-2 text-right tabular-nums">
                                                    {{ currency(purchaseDetail.final_total) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            <div>
                                <p class="mb-1 font-medium">Shipping details</p>
                                <p
                                    class="bg-muted/40 text-muted-foreground rounded-md border border-border p-3 text-sm whitespace-pre-line"
                                >
                                    {{ purchaseDetail.shipping_details?.trim() || '—' }}
                                </p>
                            </div>
                            <div>
                                <p class="mb-1 font-medium">Additional notes</p>
                                <p
                                    class="bg-muted/40 text-muted-foreground rounded-md border border-border p-3 text-sm whitespace-pre-line"
                                >
                                    {{ purchaseDetail.additional_notes?.trim() || '—' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6">
                            <p class="mb-1 font-medium">Activities</p>
                            <p class="text-muted-foreground text-sm">No records found.</p>
                        </div>
                    </template>
                </div>

                <DialogFooter class="border-t border-border shrink-0 px-6 py-4">
                    <Button type="button" variant="outline" class="print:hidden" @click="triggerPrint">
                        <Printer class="mr-2 size-4" />
                        Print
                    </Button>
                    <Button type="button" variant="secondary" class="print:hidden" @click="closeModal">Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style scoped>
@media print {
    .print\:hidden {
        display: none !important;
    }
    .print\:p-2 {
        padding: 0.5rem !important;
    }
    .print\:inline {
        display: inline !important;
    }
}
</style>
