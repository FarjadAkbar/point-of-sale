<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ExternalLink, Printer } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { reportRowMatchesSearch } from '@/lib/reportTableSearch';
import reportRoutes from '@/routes/reports';
import type { Team } from '@/types';

type StockRow = {
    product_edit_url: string;
    sku: string;
    product_name: string;
    variation: string;
    category: string;
    location: string;
    unit_selling_price: string;
    current_stock: string;
    unit_short_name: string;
    stock_value_pp: string;
    stock_value_sp: string;
    potential_profit: string;
    total_sold: string;
    total_transferred: string;
    total_adjusted: string;
};

const props = defineProps<{
    filters: {
        business_location_id: number | null;
        category_id: number | null;
        subcategory_id: number | null;
        brand_id: number | null;
        unit_id: number | null;
    };
    businessLocations: Array<{ id: number; name: string }>;
    categories: Array<{ id: number; name: string }>;
    subcategories: Array<{ id: number; name: string; parent_id: number }>;
    brands: Array<{ id: number; name: string }>;
    units: Array<{ id: number; name: string; short_name?: string | null }>;
    rows: StockRow[];
    summary: {
        closing_stock_by_pp: string;
        closing_stock_by_sp: string;
        potential_profit: string;
        profit_margin_percent: string;
    };
    footer: {
        current_stock: string;
        stock_value_pp: string;
        stock_value_sp: string;
        potential_profit: string;
        total_sold: string;
        total_transferred: string;
        total_adjusted: string;
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
                title: 'Stock report',
                href: p.currentTeam ? reportRoutes.stock.url(p.currentTeam.slug) : '#',
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(() => (page.props.currentTeam as Team | null)?.slug ?? '');

const search = ref('');
const perPage = ref('25');
const locationId = ref<string>(
    props.filters.business_location_id != null ? String(props.filters.business_location_id) : '',
);
const categoryId = ref<string>(props.filters.category_id != null ? String(props.filters.category_id) : '');
const subcategoryId = ref<string>(
    props.filters.subcategory_id != null ? String(props.filters.subcategory_id) : '',
);
const brandId = ref<string>(props.filters.brand_id != null ? String(props.filters.brand_id) : '');
const unitId = ref<string>(props.filters.unit_id != null ? String(props.filters.unit_id) : '');

const filteredRows = computed(() =>
    props.rows.filter((r) => reportRowMatchesSearch(r, search.value)),
);

function triggerPrint(): void {
    globalThis.print();
}

const filteredSubcategories = computed(() => {
    if (!categoryId.value) {
        return props.subcategories;
    }

    const pid = Number(categoryId.value);

    return props.subcategories.filter((s) => s.parent_id === pid);
});

watch(
    () => props.filters,
    (f) => {
        locationId.value = f.business_location_id != null ? String(f.business_location_id) : '';
        categoryId.value = f.category_id != null ? String(f.category_id) : '';
        subcategoryId.value = f.subcategory_id != null ? String(f.subcategory_id) : '';
        brandId.value = f.brand_id != null ? String(f.brand_id) : '';
        unitId.value = f.unit_id != null ? String(f.unit_id) : '';
    },
    { deep: true },
);

watch(categoryId, () => {
    if (subcategoryId.value) {
        const sub = props.subcategories.find((s) => String(s.id) === subcategoryId.value);

        if (sub && String(sub.parent_id) !== categoryId.value) {
            subcategoryId.value = '';
        }
    }
});

function currency(n: string) {
    const v = parseFloat(n);

    if (Number.isNaN(v)) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(v);
}

function qty(n: string, unit: string) {
    const v = parseFloat(n);

    if (Number.isNaN(v)) {
        return '—';
    }

    const u = unit || '';

    return `${new Intl.NumberFormat(undefined, { maximumFractionDigits: 4 }).format(v)}${u ? ` ${u}` : ''}`;
}

function applyFilters() {
    const q: Record<string, string> = {};

    if (locationId.value) {
        q.business_location_id = locationId.value;
    }

    if (categoryId.value) {
        q.category_id = categoryId.value;
    }

    if (subcategoryId.value) {
        q.subcategory_id = subcategoryId.value;
    }

    if (brandId.value) {
        q.brand_id = brandId.value;
    }

    if (unitId.value) {
        q.unit_id = unitId.value;
    }

    router.get(reportRoutes.stock.url(teamSlug.value), q, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Stock report" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Stock report</h1>
            <p class="text-muted-foreground text-sm">
                Current stock and value by purchase and sale price, with lifetime sold, transfer, and adjustment
                quantities for the filtered catalog.
            </p>
        </div>

        <Card>
            <CardContent class="grid gap-3 pt-6 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-muted-foreground text-xs">Closing stock (by purchase price)</p>
                    <p class="text-lg font-semibold tabular-nums">{{ currency(summary.closing_stock_by_pp) }}</p>
                </div>
                <div>
                    <p class="text-muted-foreground text-xs">Closing stock (by sale price)</p>
                    <p class="text-lg font-semibold tabular-nums">{{ currency(summary.closing_stock_by_sp) }}</p>
                </div>
                <div>
                    <p class="text-muted-foreground text-xs">Potential profit</p>
                    <p class="text-lg font-semibold tabular-nums">{{ currency(summary.potential_profit) }}</p>
                </div>
                <div>
                    <p class="text-muted-foreground text-xs">Profit margin %</p>
                    <p class="text-lg font-semibold tabular-nums">
                        {{ parseFloat(summary.profit_margin_percent).toFixed(2) }}%
                    </p>
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
                    <Label for="sr-loc">Business location</Label>
                    <select
                        id="sr-loc"
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
                    <Label for="sr-cat">Category</Label>
                    <select
                        id="sr-cat"
                        v-model="categoryId"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">All</option>
                        <option v-for="c in categories" :key="c.id" :value="String(c.id)">
                            {{ c.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label for="sr-sub">Sub category</Label>
                    <select
                        id="sr-sub"
                        v-model="subcategoryId"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">All</option>
                        <option
                            v-for="s in filteredSubcategories"
                            :key="s.id"
                            :value="String(s.id)"
                        >
                            {{ s.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label for="sr-brand">Brand</Label>
                    <select
                        id="sr-brand"
                        v-model="brandId"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">All</option>
                        <option v-for="b in brands" :key="b.id" :value="String(b.id)">
                            {{ b.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label for="sr-unit">Unit</Label>
                    <select
                        id="sr-unit"
                        v-model="unitId"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">All</option>
                        <option v-for="u in units" :key="u.id" :value="String(u.id)">
                            {{ u.short_name || u.name }}
                        </option>
                    </select>
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
                <table class="w-full min-w-[1100px] border-collapse text-xs sm:text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/40">
                            <th class="px-1 py-2 text-left font-medium">Action</th>
                            <th class="px-1 py-2 text-left font-medium">SKU</th>
                            <th class="px-1 py-2 text-left font-medium">Product</th>
                            <th class="px-1 py-2 text-left font-medium">Variation</th>
                            <th class="px-1 py-2 text-left font-medium">Category</th>
                            <th class="px-1 py-2 text-left font-medium">Location</th>
                            <th class="px-1 py-2 text-right font-medium">Unit sell</th>
                            <th class="px-1 py-2 text-right font-medium">Stock</th>
                            <th class="px-1 py-2 text-right font-medium">Value (PP)</th>
                            <th class="px-1 py-2 text-right font-medium">Value (SP)</th>
                            <th class="px-1 py-2 text-right font-medium">Potential</th>
                            <th class="px-1 py-2 text-right font-medium">Sold</th>
                            <th class="px-1 py-2 text-right font-medium">Xfer</th>
                            <th class="px-1 py-2 text-right font-medium">Adj.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="rows.length === 0">
                            <td colspan="14" class="text-muted-foreground px-3 py-6 text-center">No rows.</td>
                        </tr>
                        <tr v-else-if="filteredRows.length === 0">
                            <td colspan="14" class="text-muted-foreground px-3 py-6 text-center">No rows match your search.</td>
                        </tr>
                        <tr
                            v-for="(row, idx) in filteredRows"
                            :key="idx"
                            class="border-b border-border/80 hover:bg-muted/20"
                        >
                            <td class="px-1 py-2 whitespace-nowrap">
                                <a
                                    :href="row.product_edit_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-primary inline-flex items-center gap-0.5 underline-offset-4 hover:underline print:hidden"
                                >
                                    Edit
                                    <ExternalLink class="size-3" />
                                </a>
                            </td>
                            <td class="px-1 py-2 font-mono text-xs">{{ row.sku }}</td>
                            <td class="px-1 py-2 max-w-[10rem] truncate" :title="row.product_name">
                                {{ row.product_name }}
                            </td>
                            <td class="px-1 py-2">{{ row.variation }}</td>
                            <td class="px-1 py-2">{{ row.category }}</td>
                            <td class="px-1 py-2">{{ row.location }}</td>
                            <td class="px-1 py-2 text-right tabular-nums">{{ currency(row.unit_selling_price) }}</td>
                            <td class="px-1 py-2 text-right tabular-nums">
                                {{ qty(row.current_stock, row.unit_short_name) }}
                            </td>
                            <td class="px-1 py-2 text-right tabular-nums">{{ currency(row.stock_value_pp) }}</td>
                            <td class="px-1 py-2 text-right tabular-nums">{{ currency(row.stock_value_sp) }}</td>
                            <td class="px-1 py-2 text-right tabular-nums">{{ currency(row.potential_profit) }}</td>
                            <td class="px-1 py-2 text-right tabular-nums">
                                {{ qty(row.total_sold, row.unit_short_name) }}
                            </td>
                            <td class="px-1 py-2 text-right tabular-nums">
                                {{ qty(row.total_transferred, row.unit_short_name) }}
                            </td>
                            <td class="px-1 py-2 text-right tabular-nums">
                                {{ qty(row.total_adjusted, row.unit_short_name) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length">
                        <tr class="bg-muted/50 font-medium">
                            <td class="px-1 py-2" colspan="7">Total</td>
                            <td class="px-1 py-2 text-right tabular-nums">
                                {{ qty(footer.current_stock, '') }}
                            </td>
                            <td class="px-1 py-2 text-right tabular-nums">{{ currency(footer.stock_value_pp) }}</td>
                            <td class="px-1 py-2 text-right tabular-nums">{{ currency(footer.stock_value_sp) }}</td>
                            <td class="px-1 py-2 text-right tabular-nums">{{ currency(footer.potential_profit) }}</td>
                            <td class="px-1 py-2 text-right tabular-nums">{{ qty(footer.total_sold, '') }}</td>
                            <td class="px-1 py-2 text-right tabular-nums">{{ qty(footer.total_transferred, '') }}</td>
                            <td class="px-1 py-2 text-right tabular-nums">{{ qty(footer.total_adjusted, '') }}</td>
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
