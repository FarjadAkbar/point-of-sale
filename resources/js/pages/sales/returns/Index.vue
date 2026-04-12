<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import salesRoutes from '@/routes/sales';
import type { Team } from '@/types';

type Row = {
    id: number;
    invoice_no: string | null;
    transaction_date: string | null;
    total_return: string;
    parent_sale: {
        id: number;
        invoice_no: string | null;
        customer: string | null;
        business_location: string | null;
    } | null;
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
    returns: Paginated;
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
                title: 'Sales',
                href: salesRoutes.index.url(p.currentTeam!.slug),
            },
            {
                title: 'Sell returns',
                href: salesRoutes.returns.index.url(p.currentTeam!.slug),
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
    | 'return_no'
    | 'transaction_date'
    | 'parent_invoice'
    | 'customer'
    | 'location'
    | 'total';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    { id: 'return_no', label: 'Return', sortKey: 'invoice_no' },
    { id: 'transaction_date', label: 'Date', sortKey: 'transaction_date' },
    { id: 'parent_invoice', label: 'Parent invoice', sortKey: null },
    { id: 'customer', label: 'Customer', sortKey: null },
    { id: 'location', label: 'Location', sortKey: null },
    { id: 'total', label: 'Total', sortKey: 'total_return' },
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
        salesRoutes.returns.index.url(teamSlug.value),
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
        salesRoutes.returns.index.url(teamSlug.value),
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
    if (col === 'return_no') {
        return row.invoice_no?.trim() ? row.invoice_no : '—';
    }

    if (col === 'transaction_date' && row.transaction_date) {
        return new Date(row.transaction_date).toLocaleString();
    }

    if (col === 'parent_invoice') {
        return row.parent_sale?.invoice_no ?? '—';
    }

    if (col === 'customer') {
        return row.parent_sale?.customer ?? '—';
    }

    if (col === 'location') {
        return row.parent_sale?.business_location ?? '—';
    }

    if (col === 'total') {
        return row.total_return;
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
    <Head title="Sell returns" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Sell returns</h1>
            <p class="text-muted-foreground text-sm">
                Returns recorded against completed sales.
            </p>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Return invoice, parent invoice, customer…"
            :per-page-options="[10, 15, 25, 50]"
            :paginator="returns"
            @page="goToPage"
        >
            <table class="w-full min-w-[640px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-border">
                        <th
                            v-for="col in allColumns"
                            :key="col.id"
                            class="bg-muted/40 px-3 py-2 text-left font-medium"
                            :class="col.id === 'total' ? 'text-right' : ''"
                        >
                            <button
                                v-if="col.sortKey"
                                type="button"
                                class="inline-flex items-center gap-1 hover:text-primary"
                                :class="col.id === 'total' ? 'float-right' : ''"
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
                        v-for="row in returns.data ?? []"
                        :key="row.id"
                        class="border-b border-border/80 hover:bg-muted/20"
                    >
                        <td
                            v-for="col in allColumns"
                            :key="col.id"
                            class="px-3 py-2"
                            :class="{
                                'text-right': col.id === 'total',
                                'whitespace-nowrap':
                                    col.id === 'transaction_date',
                            }"
                        >
                            {{ displayCell(row, col.id) }}
                        </td>
                    </tr>
                    <tr v-if="!(returns?.data?.length)">
                        <td
                            :colspan="allColumns.length"
                            class="text-muted-foreground px-3 py-8 text-center"
                        >
                            No sell returns yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </StandardDataTable>
    </div>
</template>
