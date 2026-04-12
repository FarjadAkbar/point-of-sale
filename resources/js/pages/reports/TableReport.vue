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

type TableRow = { table: string; total: string; total_raw: number };

const props = defineProps<{
    filters: {
        start_date: string;
        end_date: string;
        business_location_id: number | null;
    };
    businessLocations: Array<{ id: number; name: string }>;
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
                title: 'Table report',
                href: p.currentTeam ? reportRoutes.tableReport.url(p.currentTeam.slug) : '#',
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

watch(
    () => props.filters,
    (f) => {
        startDate.value = f.start_date;
        endDate.value = f.end_date;
        locationId.value = f.business_location_id != null ? String(f.business_location_id) : '';
    },
    { deep: true },
);

const filteredRows = computed(() =>
    props.rows.filter((r) => reportRowMatchesSearch(r, search.value)),
);

const perPageNum = computed(() => {
    const n = Number(perPage.value);

    return Number.isFinite(n) && n > 0 ? n : 25;
});

const PAGE_PREFIX = '__tr_page__:';

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
    const pageNum = Math.min(Math.max(1, currentPage.value), lastPage);
    const from = total === 0 ? 0 : (pageNum - 1) * per + 1;
    const to = total === 0 ? 0 : Math.min(pageNum * per, total);

    return {
        from,
        to,
        total,
        current_page: pageNum,
        last_page: lastPage,
        per_page: per,
        links: clientPaginatorLinks(lastPage, pageNum),
    };
});

const pagedRows = computed(() => {
    const rows = filteredRows.value;
    const per = perPageNum.value;
    const pageNum = Math.min(
        Math.max(1, currentPage.value),
        Math.max(1, Math.ceil(rows.length / per) || 1),
    );
    const start = (pageNum - 1) * per;

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
            s += r.total_raw || 0;
        }

        return s.toFixed(4);
    }

    return props.footer.total;
});

function currencyFromNumericString(n: string) {
    const v = parseFloat(n);

    if (Number.isNaN(v)) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(v);
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

    router.get(reportRoutes.tableReport.url(teamSlug.value), q, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Table report" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Table report</h1>
            <p class="text-muted-foreground text-sm">
                Finalized sales totals by restaurant table for the selected location and date range.
            </p>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            table-wrapper-id="table-report-table"
            search-placeholder="Search tables…"
            :per-page-options="[25, 50, 100, 200, 500, 1000]"
            :paginator="clientPaginator"
            :show-pagination="true"
            :show-per-page="true"
            @page="onClientPage"
        >
            <template #filters>
                <div class="space-y-2">
                    <Label for="tr-loc">Business location</Label>
                    <select
                        id="tr-loc"
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
                    <Label>Date range</Label>
                    <div class="flex flex-wrap items-center gap-2">
                        <Input id="tr-start" v-model="startDate" type="date" class="min-w-[10rem]" />
                        <span class="text-muted-foreground text-sm">to</span>
                        <Input id="tr-end" v-model="endDate" type="date" class="min-w-[10rem]" />
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

            <div>
                <h2 class="mb-3 text-base font-semibold">Tables</h2>
                <table class="w-full min-w-[480px] border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/40">
                            <th class="px-2 py-2 text-left font-medium">Table</th>
                            <th class="px-2 py-2 text-right font-medium">Total sale</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="rows.length === 0">
                            <td colspan="2" class="text-muted-foreground px-3 py-6 text-center">
                                No tables configured for this team. Add tables under Settings → Tables.
                            </td>
                        </tr>
                        <tr v-else-if="filteredRows.length === 0">
                            <td colspan="2" class="text-muted-foreground px-3 py-6 text-center">
                                No rows match your search.
                            </td>
                        </tr>
                        <tr
                            v-for="(r, i) in pagedRows"
                            :key="`${r.table}-${i}`"
                            class="border-b border-border/80 hover:bg-muted/20"
                        >
                            <td class="px-2 py-2">{{ r.table }}</td>
                            <td class="px-2 py-2 text-right tabular-nums">
                                {{ currencyFromNumericString(r.total) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length">
                        <tr class="bg-muted/50 font-medium">
                            <td class="px-2 py-2">Total</td>
                            <td class="px-2 py-2 text-right tabular-nums">
                                {{ currencyFromNumericString(displayFooterAmount) }}
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
