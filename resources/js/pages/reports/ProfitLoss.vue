<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Printer } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { reportRowMatchesSearch } from '@/lib/reportTableSearch';
import reportRoutes from '@/routes/reports';
import type { Team } from '@/types';

type Row = { name?: string; invoice?: string; date?: string; day?: string; gross_profit: string };
type Filters = { start_date: string; end_date: string; business_location_id: number | null };
type Summary = Record<string, string>;

const props = defineProps<{
    filters: Filters;
    businessLocations: Array<{ id: number; name: string }>;
    summary: Summary;
    profitByProducts: Row[];
    profitByCategories: Row[];
    profitByBrands: Row[];
    profitByLocations: Row[];
    profitByInvoice: Row[];
    profitByDate: Row[];
    profitByCustomer: Row[];
    profitByDay: Row[];
    profitByServiceStaff: Row[];
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Reports',
                href: p.currentTeam ? reportRoutes.profitLoss.url(p.currentTeam.slug) : '#',
            },
            {
                title: 'Profit / Loss',
                href: p.currentTeam
                    ? reportRoutes.profitLoss.url(p.currentTeam.slug)
                    : '#',
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

watch(
    () => props.filters,
    (f) => {
        startDate.value = f.start_date;
        endDate.value = f.end_date;
        locationId.value = f.business_location_id != null ? String(f.business_location_id) : '';
    },
    { deep: true },
);

const activeTab = ref<
    | 'products'
    | 'categories'
    | 'brands'
    | 'locations'
    | 'invoice'
    | 'date'
    | 'customer'
    | 'day'
    | 'service_staff'
>('products');

const tabDefs = [
    { id: 'products' as const, label: 'Profit by products' },
    { id: 'categories' as const, label: 'Profit by categories' },
    { id: 'brands' as const, label: 'Profit by brands' },
    { id: 'locations' as const, label: 'Profit by locations' },
    { id: 'invoice' as const, label: 'Profit by invoice' },
    { id: 'date' as const, label: 'Profit by date' },
    { id: 'customer' as const, label: 'Profit by customer' },
    { id: 'day' as const, label: 'Profit by day' },
    { id: 'service_staff' as const, label: 'Profit by service staff' },
];

function currency(n: string | number) {
    const v = typeof n === 'string' ? parseFloat(n) : n;

    if (Number.isNaN(v)) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(v);
}

function sumRows(rows: Row[]): number {
    return rows.reduce((acc, r) => acc + parseFloat(r.gross_profit || '0'), 0);
}

function rowLabel(r: Row): string {
    return (r.name ?? r.invoice ?? r.date ?? r.day ?? '—') as string;
}

function triggerPrint(): void {
    globalThis.print();
}

function applyFilters() {
    const q: Record<string, string> = {
        start_date: startDate.value,
        end_date: endDate.value,
    };

    if (locationId.value) {
        q.business_location_id = locationId.value;
    }

    router.get(reportRoutes.profitLoss.url(teamSlug.value), q, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

const currentRows = computed(() => {
    switch (activeTab.value) {
        case 'products':
            return { rows: props.profitByProducts, col: 'Product' };
        case 'categories':
            return { rows: props.profitByCategories, col: 'Category' };
        case 'brands':
            return { rows: props.profitByBrands, col: 'Brand' };
        case 'locations':
            return { rows: props.profitByLocations, col: 'Location' };
        case 'invoice':
            return { rows: props.profitByInvoice, col: 'Invoice' };
        case 'date':
            return { rows: props.profitByDate, col: 'Date' };
        case 'customer':
            return { rows: props.profitByCustomer, col: 'Customer' };
        case 'day':
            return { rows: props.profitByDay, col: 'Day' };
        case 'service_staff':
            return { rows: props.profitByServiceStaff, col: 'Service staff' };
        default:
            return { rows: [], col: '' };
    }
});

const filteredBreakdownRows = computed(() =>
    currentRows.value.rows.filter((r) => reportRowMatchesSearch(r, search.value)),
);

const tableTotal = computed(() => currency(sumRows(filteredBreakdownRows.value)));
</script>

<template>
    <Head title="Profit / Loss report" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div class="print-section mb-2 hidden print:block">
            <h2 class="text-lg font-semibold">Profit / Loss report</h2>
        </div>

        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Profit / Loss report</h1>
            <p class="text-muted-foreground text-sm">
                Final sales in the selected range. Product gross uses default purchase price
                (single DPP) per line; invoice header discounts are excluded from tab totals.
            </p>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">Costs & deductions</CardTitle>
                </CardHeader>
                <CardContent class="space-y-1 text-sm">
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Opening stock <span class="text-muted-foreground">(purchase)</span></span>
                        <span class="tabular-nums">{{ currency(summary.opening_stock_purchase) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Opening stock <span class="text-muted-foreground">(sale price)</span></span>
                        <span class="tabular-nums">{{ currency(summary.opening_stock_sale) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Total purchase <span class="text-muted-foreground">(exc. tax)</span></span>
                        <span class="tabular-nums">{{ currency(summary.total_purchase_exc_tax) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Total stock adjustment</span>
                        <span class="tabular-nums">{{ currency(summary.total_stock_adjustment) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Total expense</span>
                        <span class="tabular-nums">{{ currency(summary.total_expense) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Total purchase shipping</span>
                        <span class="tabular-nums">{{ currency(summary.total_purchase_shipping) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Purchase additional expenses</span>
                        <span class="tabular-nums">{{ currency(summary.purchase_additional_expenses) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Total transfer shipping</span>
                        <span class="tabular-nums">{{ currency(summary.total_transfer_shipping) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Total sell discount <span class="text-muted-foreground">(line)</span></span>
                        <span class="tabular-nums">{{ currency(summary.total_sell_discount) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Total sell return</span>
                        <span class="tabular-nums">{{ currency(summary.total_sell_return) }}</span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">Revenue & credits</CardTitle>
                </CardHeader>
                <CardContent class="space-y-1 text-sm">
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Closing stock <span class="text-muted-foreground">(purchase)</span></span>
                        <span class="tabular-nums">{{ currency(summary.closing_stock_purchase) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Closing stock <span class="text-muted-foreground">(sale price)</span></span>
                        <span class="tabular-nums">{{ currency(summary.closing_stock_sale) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Total sales <span class="text-muted-foreground">(exc. tax)</span></span>
                        <span class="tabular-nums">{{ currency(summary.total_sales_exc_tax) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Total sell shipping</span>
                        <span class="tabular-nums">{{ currency(summary.total_sell_shipping) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Sell additional expenses</span>
                        <span class="tabular-nums">{{ currency(summary.sell_additional_expenses) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Total stock recovered</span>
                        <span class="tabular-nums">{{ currency(summary.total_stock_recovered) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Total purchase return</span>
                        <span class="tabular-nums">{{ currency(summary.total_purchase_return) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Total purchase discount <span class="text-muted-foreground">(header)</span></span>
                        <span class="tabular-nums">{{ currency(summary.total_purchase_discount) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 py-1.5">
                        <span>HMS / other</span>
                        <span class="tabular-nums">{{ currency(summary.hms_total) }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardContent class="space-y-3 pt-6">
                <div>
                    <h3 class="text-muted-foreground text-sm font-medium">COGS (opening + purchases − closing)</h3>
                    <p class="text-2xl font-semibold tabular-nums">{{ currency(summary.cogs) }}</p>
                    <p class="text-muted-foreground mt-1 text-xs">
                        Sold-goods COGS (default cost × qty on final lines):
                        {{ currency(summary.cogs_sold_goods) }}
                    </p>
                </div>
                <div>
                    <h3 class="text-muted-foreground text-sm font-medium">Gross profit</h3>
                    <p class="text-2xl font-semibold tabular-nums">{{ currency(summary.gross_profit) }}</p>
                    <p class="text-muted-foreground mt-1 text-xs">
                        Sales (exc. tax, after line discounts) minus default purchase cost on lines.
                    </p>
                </div>
                <div>
                    <h3 class="text-muted-foreground text-sm font-medium">Net profit</h3>
                    <p class="text-2xl font-semibold tabular-nums">{{ currency(summary.net_profit) }}</p>
                    <p class="text-muted-foreground mt-1 text-xs">
                        Gross profit adjusted for shipping, additional charges, expenses, returns, and header
                        discounts (see backend formula).
                    </p>
                </div>
            </CardContent>
        </Card>

        <h2 class="text-base font-semibold tracking-tight">Breakdown</h2>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            class="print:hidden"
            search-placeholder="Search table…"
            :show-pagination="false"
            :show-per-page="false"
        >
            <template #filters>
                <div class="space-y-2">
                    <Label for="pl-location">Location</Label>
                    <select
                        id="pl-location"
                        v-model="locationId"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">All locations</option>
                        <option v-for="loc in businessLocations" :key="loc.id" :value="String(loc.id)">
                            {{ loc.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label for="pl-start">Start date</Label>
                    <Input id="pl-start" v-model="startDate" type="date" />
                </div>
                <div class="space-y-2">
                    <Label for="pl-end">End date</Label>
                    <Input id="pl-end" v-model="endDate" type="date" />
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
                <Button
                    v-for="t in tabDefs"
                    :key="t.id"
                    type="button"
                    size="sm"
                    class="shrink-0"
                    :variant="activeTab === t.id ? 'default' : 'outline'"
                    @click="activeTab = t.id"
                >
                    {{ t.label }}
                </Button>
            </template>
            <div class="rounded-md border border-border overflow-x-auto">
                <table class="w-full min-w-[480px] border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/40">
                            <th class="px-3 py-2 text-left font-medium">{{ currentRows.col }}</th>
                            <th class="px-3 py-2 text-right font-medium">Gross profit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="currentRows.rows.length === 0">
                            <td colspan="2" class="text-muted-foreground px-3 py-6 text-center">
                                No data for this view.
                            </td>
                        </tr>
                        <tr v-else-if="filteredBreakdownRows.length === 0">
                            <td colspan="2" class="text-muted-foreground px-3 py-6 text-center">
                                No rows match your search.
                            </td>
                        </tr>
                        <tr
                            v-for="(r, idx) in filteredBreakdownRows"
                            :key="idx"
                            class="border-b border-border/80 hover:bg-muted/20"
                        >
                            <td class="px-3 py-2">{{ rowLabel(r) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">
                                {{ currency(r.gross_profit) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="currentRows.rows.length">
                        <tr class="bg-muted/50 font-medium">
                            <td class="px-3 py-2">Total</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ tableTotal }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </StandardDataTable>

        <p class="text-muted-foreground text-xs">
            <strong>Note:</strong> Profit by product / category / brand uses line-level pricing only; invoice
            header discounts are not included in these tab totals.
        </p>
    </div>
</template>

<style scoped>
@media print {
    .print\:hidden {
        display: none !important;
    }
    .print\:block {
        display: block !important;
    }
    .print\:p-2 {
        padding: 0.5rem !important;
    }
}
</style>
