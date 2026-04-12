<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Printer } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { reportRowMatchesSearch } from '@/lib/reportTableSearch';
import reportRoutes from '@/routes/reports';
import type { Team } from '@/types';

type ChartRow = { product_id: number; label: string; units_sold: string; line_total: string };

const props = defineProps<{
    filters: {
        start_date: string;
        end_date: string;
        limit: number;
        business_location_id: number | null;
        category_id: number | null;
        subcategory_id: number | null;
        brand_id: number | null;
        unit_id: number | null;
        product_type: string;
    };
    businessLocations: Array<{ id: number; name: string }>;
    categories: Array<{ id: number; name: string }>;
    subcategories: Array<{ id: number; name: string; parent_id: number }>;
    brands: Array<{ id: number; name: string }>;
    units: Array<{ id: number; name: string; short_name?: string | null }>;
    chart: ChartRow[];
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Reports',
                href: p.currentTeam ? reportRoutes.profitLoss.url(p.currentTeam.slug) : '#',
            },
            {
                title: 'Trending products',
                href: p.currentTeam ? reportRoutes.trendingProducts.url(p.currentTeam.slug) : '#',
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
const limit = ref(String(props.filters.limit ?? 5));
const locationId = ref<string>(
    props.filters.business_location_id != null ? String(props.filters.business_location_id) : '',
);
const categoryId = ref<string>(props.filters.category_id != null ? String(props.filters.category_id) : '');
const subcategoryId = ref<string>(
    props.filters.subcategory_id != null ? String(props.filters.subcategory_id) : '',
);
const brandId = ref<string>(props.filters.brand_id != null ? String(props.filters.brand_id) : '');
const unitId = ref<string>(props.filters.unit_id != null ? String(props.filters.unit_id) : '');
const productType = ref<string>(props.filters.product_type || '');

const filteredRows = computed(() =>
    props.chart.filter((r) => reportRowMatchesSearch(r, search.value)),
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

const maxUnits = computed(() => {
    let m = 0;

    for (const r of props.chart) {
        const v = parseFloat(r.units_sold);

        if (!Number.isNaN(v)) {
            m = Math.max(m, v);
        }
    }

    return m > 0 ? m : 1;
});

watch(
    () => props.filters,
    (f) => {
        startDate.value = f.start_date;
        endDate.value = f.end_date;
        limit.value = String(f.limit ?? 5);
        locationId.value = f.business_location_id != null ? String(f.business_location_id) : '';
        categoryId.value = f.category_id != null ? String(f.category_id) : '';
        subcategoryId.value = f.subcategory_id != null ? String(f.subcategory_id) : '';
        brandId.value = f.brand_id != null ? String(f.brand_id) : '';
        unitId.value = f.unit_id != null ? String(f.unit_id) : '';
        productType.value = f.product_type || '';
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

function barHeightPx(row: ChartRow): string {
    const u = parseFloat(row.units_sold);

    if (Number.isNaN(u)) {
        return '4px';
    }

    const h = Math.max(4, Math.round((u / maxUnits.value) * 220));

    return `${h}px`;
}

function formatQty(n: string) {
    const v = parseFloat(n);

    if (Number.isNaN(v)) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(v);
}

function currency(n: string) {
    const v = parseFloat(n);

    if (Number.isNaN(v)) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(v);
}

function applyFilters() {
    const q: Record<string, string> = {
        start_date: startDate.value,
        end_date: endDate.value,
        limit: limit.value || '5',
    };

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

    if (productType.value) {
        q.product_type = productType.value;
    }

    router.get(reportRoutes.trendingProducts.url(teamSlug.value), q, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Trending products" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Trending products</h1>
            <p class="text-muted-foreground text-sm">
                Top products by units sold on final invoices for the selected filters and date range.
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
                <div class="space-y-2">
                    <Label for="tp-loc">Business location</Label>
                    <select
                        id="tp-loc"
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
                    <Label for="tp-cat">Category</Label>
                    <select
                        id="tp-cat"
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
                    <Label for="tp-sub">Sub category</Label>
                    <select
                        id="tp-sub"
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
                    <Label for="tp-brand">Brand</Label>
                    <select
                        id="tp-brand"
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
                    <Label for="tp-unit">Unit</Label>
                    <select
                        id="tp-unit"
                        v-model="unitId"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">All</option>
                        <option v-for="u in units" :key="u.id" :value="String(u.id)">
                            {{ u.short_name || u.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label for="tp-ptype">Product type</Label>
                    <select
                        id="tp-ptype"
                        v-model="productType"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">All</option>
                        <option value="single">Single</option>
                        <option value="variable">Variable</option>
                        <option value="combo">Combo</option>
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
                <div class="space-y-2">
                    <Label for="tp-limit">Number of products</Label>
                    <Input id="tp-limit" v-model="limit" type="number" min="1" max="50" class="max-w-[10rem]" />
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
            <div class="space-y-4">
                <div>
                    <h3 class="mb-3 text-base font-semibold">Top trending products (units sold)</h3>
                    <div
                        v-if="chart.length === 0"
                        class="text-muted-foreground flex h-[240px] items-center justify-center rounded-md border border-border text-sm"
                    >
                        No data for these filters.
                    </div>
                    <div
                        v-else-if="filteredRows.length === 0"
                        class="text-muted-foreground flex h-[240px] items-center justify-center rounded-md border border-border text-sm"
                    >
                        No rows match your search.
                    </div>
                    <div
                        v-else
                        class="flex min-h-[240px] items-end justify-between gap-2 rounded-md border border-border p-3 pb-2"
                    >
                        <div
                            v-for="row in filteredRows"
                            :key="row.product_id"
                            class="flex min-w-0 flex-1 flex-col items-center gap-2"
                        >
                            <div
                                class="bg-primary/85 w-full max-w-[5rem] rounded-t transition-all"
                                :style="{ height: barHeightPx(row) }"
                                :title="`${formatQty(row.units_sold)} units`"
                            />
                            <p class="text-muted-foreground line-clamp-3 max-w-[7rem] text-center text-[10px] leading-tight sm:text-xs">
                                {{ row.label }}
                            </p>
                            <p class="text-xs font-medium tabular-nums">{{ formatQty(row.units_sold) }}</p>
                            <p class="text-muted-foreground text-[10px] tabular-nums">{{ currency(row.line_total) }}</p>
                        </div>
                    </div>
                </div>
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
