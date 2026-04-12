<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Printer } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import type { LaravelPaginatorLink } from '@/components/StandardDataTable.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { reportRowMatchesSearch } from '@/lib/reportTableSearch';
import reportRoutes from '@/routes/reports';
import type { Team } from '@/types';

type ChartRow = { label: string; total: string; total_raw: number };

type TableRow = { category: string; total: string };

const props = defineProps<{
    filters: {
        start_date: string;
        end_date: string;
        business_location_id: number | null;
        category_id: number | null;
    };
    businessLocations: Array<{ id: number; name: string }>;
    categories: Array<{ id: number; name: string }>;
    chart: ChartRow[];
    rows: TableRow[];
    footer: { total: string };
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Reports',
                href: p.currentTeam ? reportRoutes.profitLoss.url(p.currentTeam.slug) : '#',
            },
            {
                title: 'Expense report',
                href: p.currentTeam ? reportRoutes.expense.url(p.currentTeam.slug) : '#',
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(() => (page.props.currentTeam as Team | null)?.slug ?? '');

const search = ref('');
const perPage = ref('25');
const currentPage = ref(1);

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const locationId = ref<string>(
    props.filters.business_location_id != null ? String(props.filters.business_location_id) : '',
);
const categoryId = ref<string>(props.filters.category_id != null ? String(props.filters.category_id) : '');

watch(
    () => props.filters,
    (f) => {
        startDate.value = f.start_date;
        endDate.value = f.end_date;
        locationId.value = f.business_location_id != null ? String(f.business_location_id) : '';
        categoryId.value = f.category_id != null ? String(f.category_id) : '';
    },
    { deep: true },
);

const filteredRows = computed(() =>
    props.rows.filter((r) => reportRowMatchesSearch(r, search.value)),
);

/** Bars for categories still visible after table search. */
const filteredChart = computed(() => {
    const labels = new Set(filteredRows.value.map((r) => r.category));

    return props.chart.filter((c) => labels.has(c.label));
});

const perPageNum = computed(() => {
    const n = Number(perPage.value);

    return Number.isFinite(n) && n > 0 ? n : 25;
});

const PAGE_PREFIX = '__er_page__:';

function clientPaginatorLinks(lastPage: number, page: number): LaravelPaginatorLink[] {
    const links: LaravelPaginatorLink[] = [
        {
            url: page > 1 ? `${PAGE_PREFIX}${page - 1}` : null,
            label: '&laquo; Previous',
            active: false,
        },
    ];

    for (let i = 1; i <= lastPage; i++) {
        links.push({
            url: i === page ? null : `${PAGE_PREFIX}${i}`,
            label: String(i),
            active: i === page,
        });
    }

    links.push({
        url: page < lastPage ? `${PAGE_PREFIX}${page + 1}` : null,
        label: 'Next &raquo;',
        active: false,
    });

    return links;
}

const clientPaginator = computed(() => {
    const total = filteredRows.value.length;
    const per = perPageNum.value;
    const lastPage = Math.max(1, Math.ceil(total / per) || 1);
    const page = Math.min(Math.max(1, currentPage.value), lastPage);
    const from = total === 0 ? 0 : (page - 1) * per + 1;
    const to = total === 0 ? 0 : Math.min(page * per, total);

    return {
        from,
        to,
        total,
        current_page: page,
        last_page: lastPage,
        per_page: per,
        links: clientPaginatorLinks(lastPage, page),
    };
});

const pagedRows = computed(() => {
    const rows = filteredRows.value;
    const per = perPageNum.value;
    const page = Math.min(
        Math.max(1, currentPage.value),
        Math.max(1, Math.ceil(rows.length / per) || 1),
    );
    const start = (page - 1) * per;

    return rows.slice(start, start + per);
});

watch([filteredRows, perPage], () => {
    currentPage.value = 1;
});

function onClientPage(url: string | null) {
    if (!url?.startsWith(PAGE_PREFIX)) {
        return;
    }

    const n = Number(url.slice(PAGE_PREFIX.length));

    if (Number.isFinite(n) && n >= 1) {
        currentPage.value = n;
    }
}

const displayFooterAmount = computed(() => {
    if (search.value.trim()) {
        let s = 0;

        for (const r of filteredRows.value) {
            s += parseFloat(r.total) || 0;
        }

        return s.toFixed(4);
    }

    return props.footer.total;
});

function triggerPrint(): void {
    globalThis.print();
}

const maxChart = computed(() => {
    let m = 0;

    for (const r of filteredChart.value) {
        const v = Math.abs(r.total_raw);
        m = Math.max(m, v);
    }

    return m > 0 ? m : 1;
});

function barHeightPx(row: ChartRow): string {
    const u = Math.abs(row.total_raw);
    const h = Math.max(4, Math.round((u / maxChart.value) * 280));

    return `${h}px`;
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
    };

    if (locationId.value) {
        q.business_location_id = locationId.value;
    }

    if (categoryId.value) {
        q.category_id = categoryId.value;
    }

    router.get(reportRoutes.expense.url(teamSlug.value), q, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Expense report" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Expense report</h1>
            <p class="text-muted-foreground text-sm">
                Expenses aggregated by category for the selected location, category, and date range.
            </p>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            table-wrapper-id="expense-report-table"
            search-placeholder="Search categories…"
            :per-page-options="[25, 50, 100, 200, 500]"
            :paginator="clientPaginator"
            :show-pagination="true"
            :show-per-page="true"
            @page="onClientPage"
        >
            <template #filters>
                <div class="space-y-2">
                    <Label for="er-loc">Business location</Label>
                    <select
                        id="er-loc"
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
                    <Label for="er-cat">Category</Label>
                    <select
                        id="er-cat"
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
                <Button variant="outline" type="button" size="sm" class="print:hidden" @click="triggerPrint">
                    <Printer class="mr-2 size-4" />
                    Print
                </Button>
            </template>

            <div class="space-y-8">
                <div class="print:hidden">
                    <h2 class="mb-3 text-base font-semibold">Expense report</h2>
                    <div
                        v-if="filteredChart.length === 0"
                        class="text-muted-foreground flex h-[320px] items-center justify-center rounded-md border border-border text-sm"
                    >
                        {{
                            chart.length > 0 && search.trim()
                                ? 'No categories match your search.'
                                : 'No data for these filters.'
                        }}
                    </div>
                    <div
                        v-else
                        class="flex min-h-[320px] items-end justify-between gap-2 rounded-md border border-border p-4 pb-2"
                    >
                        <div
                            v-for="(row, idx) in filteredChart"
                            :key="`${row.label}-${idx}`"
                            class="flex min-w-0 flex-1 flex-col items-center gap-2"
                        >
                            <div
                                class="bg-primary/85 w-full max-w-[5rem] rounded-t transition-all"
                                :style="{ height: barHeightPx(row) }"
                                :title="currency(row.total)"
                            />
                            <p
                                class="text-muted-foreground line-clamp-3 max-w-[7rem] text-center text-[10px] leading-tight sm:text-xs"
                            >
                                {{ row.label }}
                            </p>
                            <p class="text-muted-foreground text-[10px] tabular-nums sm:text-xs">
                                {{ currency(row.total) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div>
                    <h2 class="mb-3 text-base font-semibold">Expense categories</h2>
                    <table class="w-full min-w-[480px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40">
                                <th class="px-2 py-2 text-left font-medium">Expense categories</th>
                                <th class="px-2 py-2 text-right font-medium">Total expense</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="rows.length === 0">
                                <td colspan="2" class="text-muted-foreground px-3 py-6 text-center">
                                    No data available in table
                                </td>
                            </tr>
                            <tr v-else-if="filteredRows.length === 0">
                                <td colspan="2" class="text-muted-foreground px-3 py-6 text-center">
                                    No rows match your search.
                                </td>
                            </tr>
                            <tr
                                v-for="(r, i) in pagedRows"
                                :key="`${r.category}-${i}`"
                                class="border-b border-border/80 hover:bg-muted/20"
                            >
                                <td class="px-2 py-2">{{ r.category }}</td>
                                <td class="px-2 py-2 text-right tabular-nums">{{ currency(r.total) }}</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="rows.length">
                            <tr class="bg-muted/50 font-medium">
                                <td class="px-2 py-2">Total</td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currency(displayFooterAmount) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
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
