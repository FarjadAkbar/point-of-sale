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

type TaxRateCol = { id: number; key: string; label: string };
type TaxRow = {
    date: string | null;
    reference: string;
    party: string;
    tax_number: string;
    total_before_tax: string;
    payment_method: string;
    discount: string;
    taxes: Record<string, string>;
};

const props = defineProps<{
    filters: {
        start_date: string;
        end_date: string;
        business_location_id: number | null;
        customer_id: number | null;
    };
    businessLocations: Array<{ id: number; name: string }>;
    customers: Array<{ id: number; label: string }>;
    taxRates: TaxRateCol[];
    summary: Record<string, string>;
    inputRows: TaxRow[];
    outputRows: TaxRow[];
    expenseRows: TaxRow[];
    inputColumnTotals: Record<string, string>;
    outputColumnTotals: Record<string, string>;
    expenseColumnTotals: Record<string, string>;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Reports',
                href: p.currentTeam ? reportRoutes.profitLoss.url(p.currentTeam.slug) : '#',
            },
            {
                title: 'Tax report',
                href: p.currentTeam ? reportRoutes.taxReport.url(p.currentTeam.slug) : '#',
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
const customerId = ref<string>(
    props.filters.customer_id != null ? String(props.filters.customer_id) : '',
);

watch(
    () => props.filters,
    (f) => {
        startDate.value = f.start_date;
        endDate.value = f.end_date;
        locationId.value = f.business_location_id != null ? String(f.business_location_id) : '';
        customerId.value = f.customer_id != null ? String(f.customer_id) : '';
    },
    { deep: true },
);

const activeTab = ref<'input' | 'output' | 'expense'>('input');

const currentRows = computed(() => {
    switch (activeTab.value) {
        case 'input':
            return props.inputRows;
        case 'output':
            return props.outputRows;
        case 'expense':
            return props.expenseRows;
        default:
            return [];
    }
});

const filteredRows = computed(() =>
    currentRows.value.filter((r) => reportRowMatchesSearch(r, search.value)),
);

const currentColumnTotals = computed(() => {
    switch (activeTab.value) {
        case 'input':
            return props.inputColumnTotals;
        case 'output':
            return props.outputColumnTotals;
        case 'expense':
            return props.expenseColumnTotals;
        default:
            return {};
    }
});

const currentPaymentSummary = computed(() => {
    switch (activeTab.value) {
        case 'input':
            return props.summary.input_payment_summary ?? '';
        case 'output':
            return props.summary.output_payment_summary ?? '';
        case 'expense':
            return props.summary.expense_payment_summary ?? '';
        default:
            return '';
    }
});

const currentTotalBeforeTax = computed(() => {
    switch (activeTab.value) {
        case 'input':
            return props.summary.input_total_before_tax ?? '0';
        case 'output':
            return props.summary.output_total_before_tax ?? '0';
        case 'expense':
            return props.summary.expense_total_before_tax ?? '0';
        default:
            return '0';
    }
});

const counterpartyLabel = computed(() => {
    if (activeTab.value === 'input') {
        return 'Supplier';
    }

    if (activeTab.value === 'output') {
        return 'Customer';
    }

    return 'Contact';
});

function triggerPrint(): void {
    globalThis.print();
}

function currency(n: string) {
    const v = parseFloat(n);

    if (Number.isNaN(v)) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(v);
}

function formatDate(iso: string | null) {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleString();
}

function applyFilters() {
    const q: Record<string, string> = {
        start_date: startDate.value,
        end_date: endDate.value,
    };

    if (locationId.value) {
        q.business_location_id = locationId.value;
    }

    if (customerId.value) {
        q.customer_id = customerId.value;
    }

    router.get(reportRoutes.taxReport.url(teamSlug.value), q, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function diffClass() {
    const v = parseFloat(props.summary.tax_difference ?? '0');

    if (v < 0) {
        return 'text-destructive';
    }

    if (v > 0) {
        return 'text-emerald-600 dark:text-emerald-500';
    }

    return '';
}
</script>

<template>
    <Head title="Tax report" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Tax report</h1>
            <p class="text-muted-foreground text-sm">
                Output tax (final sales), input tax (received purchases), and expense tax for the range. Contact
                filters sales and expenses; purchases always include all suppliers.
            </p>
        </div>

        <Card>
            <CardHeader class="pb-2">
                <CardTitle class="text-base">
                    Overall (Output − Input − Expense)
                    <span
                        class="text-muted-foreground ml-1 inline-block cursor-help text-xs font-normal"
                        title="Difference between total tax collected on sales, tax paid on purchases, and tax on expenses in the selected period."
                    >
                        (i)
                    </span>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <p class="text-muted-foreground text-sm">Output tax − Input tax − Expense tax</p>
                <p class="text-2xl font-semibold tabular-nums" :class="diffClass()">
                    {{ currency(summary.tax_difference) }}
                </p>
                <div class="text-muted-foreground mt-3 grid gap-1 text-xs sm:grid-cols-3">
                    <div>Output tax: {{ currency(summary.output_tax_total) }}</div>
                    <div>Input tax: {{ currency(summary.input_tax_total) }}</div>
                    <div>Expense tax: {{ currency(summary.expense_tax_total) }}</div>
                </div>
            </CardContent>
        </Card>

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
                    <Label for="tr-location">Business location</Label>
                    <select
                        id="tr-location"
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
                    <Label for="tr-contact">Contact</Label>
                    <select
                        id="tr-contact"
                        v-model="customerId"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">All</option>
                        <option v-for="c in customers" :key="c.id" :value="String(c.id)">
                            {{ c.label }}
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
                <Button
                    type="button"
                    size="sm"
                    :variant="activeTab === 'input' ? 'default' : 'outline'"
                    @click="activeTab = 'input'"
                >
                    Input tax (Purchase)
                </Button>
                <Button
                    type="button"
                    size="sm"
                    :variant="activeTab === 'output' ? 'default' : 'outline'"
                    @click="activeTab = 'output'"
                >
                    Output tax (Sales)
                </Button>
                <Button
                    type="button"
                    size="sm"
                    :variant="activeTab === 'expense' ? 'default' : 'outline'"
                    @click="activeTab = 'expense'"
                >
                    Expense tax
                </Button>
            </template>
            <div class="rounded-md border border-border overflow-x-auto">
                <table class="w-full min-w-[720px] border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/40">
                            <th class="px-2 py-2 text-left font-medium">Date</th>
                            <th class="px-2 py-2 text-left font-medium">Reference</th>
                            <th class="px-2 py-2 text-left font-medium">{{ counterpartyLabel }}</th>
                            <th class="px-2 py-2 text-left font-medium">Tax number</th>
                            <th class="px-2 py-2 text-right font-medium">Total amount</th>
                            <th class="px-2 py-2 text-left font-medium">Payment method</th>
                            <th class="px-2 py-2 text-right font-medium">Discount</th>
                            <th
                                v-for="col in taxRates"
                                :key="col.id"
                                class="px-2 py-2 text-right font-medium whitespace-nowrap"
                            >
                                {{ col.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="currentRows.length === 0">
                            <td
                                :colspan="7 + taxRates.length"
                                class="text-muted-foreground px-3 py-6 text-center"
                            >
                                No rows for this tab.
                            </td>
                        </tr>
                        <tr v-else-if="filteredRows.length === 0">
                            <td
                                :colspan="7 + taxRates.length"
                                class="text-muted-foreground px-3 py-6 text-center"
                            >
                                No rows match your search.
                            </td>
                        </tr>
                        <tr
                            v-for="(row, idx) in filteredRows"
                            :key="idx"
                            class="border-b border-border/80 hover:bg-muted/20"
                        >
                            <td class="px-2 py-2 whitespace-nowrap">{{ formatDate(row.date) }}</td>
                            <td class="px-2 py-2">{{ row.reference }}</td>
                            <td class="px-2 py-2">{{ row.party }}</td>
                            <td class="px-2 py-2">{{ row.tax_number }}</td>
                            <td class="px-2 py-2 text-right tabular-nums">
                                {{ currency(row.total_before_tax) }}
                            </td>
                            <td class="px-2 py-2 text-xs">{{ row.payment_method }}</td>
                            <td class="px-2 py-2 text-right tabular-nums">{{ currency(row.discount) }}</td>
                            <td
                                v-for="col in taxRates"
                                :key="col.id"
                                class="px-2 py-2 text-right tabular-nums whitespace-nowrap"
                            >
                                {{ currency(row.taxes[col.key] ?? '0') }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="currentRows.length">
                        <tr class="bg-muted/50 font-medium">
                            <td class="px-2 py-2" colspan="4">Total</td>
                            <td class="px-2 py-2 text-right tabular-nums">
                                {{ currency(currentTotalBeforeTax) }}
                            </td>
                            <td class="px-2 py-2 text-left text-xs whitespace-pre-line">
                                {{ currentPaymentSummary || '—' }}
                            </td>
                            <td class="px-2 py-2 text-right">—</td>
                            <td
                                v-for="col in taxRates"
                                :key="col.id"
                                class="px-2 py-2 text-right tabular-nums whitespace-nowrap"
                            >
                                {{ currency(currentColumnTotals[col.key] ?? '0') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </StandardDataTable>
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
}
</style>
