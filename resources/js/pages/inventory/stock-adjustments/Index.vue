<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import { Button } from '@/components/ui/button';
import stockAdjustmentRoutes from '@/routes/stock-adjustments';
import type { Team } from '@/types';

type Row = {
    id: number;
    ref_no: string | null;
    transaction_date: string | null;
    adjustment_type: string;
    final_total: string;
    total_amount_recovered: string;
    business_location: { id: number; name: string } | null;
};

type Paginated = {
    data: Row[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    adjustments: Paginated;
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
    };
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Stock adjustments',
                href: stockAdjustmentRoutes.index.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

const search = ref(props.filters.search ?? '');
const perPage = ref(String(props.filters.per_page ?? 15));

type ColId =
    | 'ref_no'
    | 'transaction_date'
    | 'location'
    | 'type'
    | 'recovered'
    | 'final_total';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    { id: 'ref_no', label: 'Reference', sortKey: null },
    { id: 'transaction_date', label: 'Date', sortKey: 'transaction_date' },
    { id: 'location', label: 'Location', sortKey: null },
    { id: 'type', label: 'Type', sortKey: 'adjustment_type' },
    { id: 'recovered', label: 'Recovered', sortKey: 'total_amount_recovered' },
    { id: 'final_total', label: 'Total', sortKey: 'final_total' },
];

function indexQuery(
    overrides: Record<string, string | number | undefined> = {},
): Record<string, string> {
    const q: Record<string, string> = {
        search: search.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
        per_page: String(overrides.per_page ?? props.filters.per_page),
    };

    for (const [k, v] of Object.entries(overrides)) {
        if (v !== undefined && v !== '') {
            q[k] = String(v);
        }
    }

    return q;
}

function visitWithFilters(overrides: Record<string, string | number> = {}) {
    router.get(
        stockAdjustmentRoutes.index.url(teamSlug.value),
        indexQuery(overrides),
        { preserveState: true, replace: true },
    );
}

const debouncedSearch = useDebounceFn(() => visitWithFilters(), 350);
watch(search, () => debouncedSearch());
watch(perPage, (v) => visitWithFilters({ per_page: Number(v) }));

function toggleSort(sortKey: string) {
    const isCurrent = props.filters.sort === sortKey;
    const dir =
        isCurrent && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(
        stockAdjustmentRoutes.index.url(teamSlug.value),
        indexQuery({ sort: sortKey, direction: dir }),
        { preserveState: true, replace: true },
    );
}

function goToPage(url: string | null) {
    if (url) {
        router.visit(url, { preserveState: true, replace: true });
    }
}

function displayCell(row: Row, col: ColId): string {
    if (col === 'ref_no') {
        return row.ref_no?.trim() ? row.ref_no : '—';
    }

    if (col === 'transaction_date' && row.transaction_date) {
        return new Date(row.transaction_date).toLocaleString();
    }

    if (col === 'location') {
        return row.business_location?.name ?? '—';
    }

    if (col === 'type') {
        return row.adjustment_type;
    }

    if (col === 'recovered') {
        return row.total_amount_recovered;
    }

    if (col === 'final_total') {
        return row.final_total;
    }

    return '—';
}

function sortIndicator(sortKey: string | null): string {
    if (!sortKey || props.filters.sort !== sortKey) {
        return '';
    }

    return props.filters.direction === 'asc' ? ' ↑' : ' ↓';
}
</script>

<template>
    <Head title="Stock adjustments" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    List stock adjustment
                </h1>
                <p class="text-muted-foreground text-sm">
                    Inventory corrections by location.
                </p>
            </div>
            <Button as-child>
                <Link :href="stockAdjustmentRoutes.create.url(teamSlug)">
                    Add stock adjustment
                </Link>
            </Button>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Reference, type, location…"
            :per-page-options="[10, 15, 25, 50]"
            :paginator="adjustments"
            @page="goToPage"
        >
            <table class="w-full min-w-[720px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-border">
                        <th
                            v-for="col in allColumns"
                            :key="col.id"
                            class="bg-muted/40 px-3 py-2 font-medium"
                            :class="
                                col.id === 'recovered' || col.id === 'final_total'
                                    ? 'text-right'
                                    : 'text-left'
                            "
                        >
                            <button
                                v-if="col.sortKey"
                                type="button"
                                class="inline-flex items-center gap-1 hover:text-primary"
                                @click="toggleSort(col.sortKey)"
                            >
                                {{ col.label
                                }}<span class="text-xs text-muted-foreground">{{
                                    sortIndicator(col.sortKey)
                                }}</span>
                            </button>
                            <span v-else>{{ col.label }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in adjustments.data ?? []"
                        :key="row.id"
                        class="border-b border-border/80 hover:bg-muted/20"
                    >
                        <td
                            v-for="col in allColumns"
                            :key="col.id"
                            class="px-3 py-2 capitalize"
                            :class="
                                col.id === 'recovered' || col.id === 'final_total'
                                    ? 'text-right whitespace-nowrap'
                                    : col.id === 'transaction_date'
                                      ? 'whitespace-nowrap'
                                      : ''
                            "
                        >
                            {{ displayCell(row, col.id) }}
                        </td>
                    </tr>
                    <tr v-if="!(adjustments?.data?.length)">
                        <td
                            :colspan="allColumns.length"
                            class="text-muted-foreground px-3 py-8 text-center"
                        >
                            No stock adjustments yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </StandardDataTable>
    </div>
</template>
