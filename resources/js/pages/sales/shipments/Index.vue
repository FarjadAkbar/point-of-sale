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
    final_total: string;
    shipping_details: string | null;
    shipping_charges: string;
    shipping_address: string | null;
    shipping_status: string | null;
    delivered_to: string | null;
    delivery_person: string | null;
    customer: { id: number; display_name: string } | null;
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
    shipments: Paginated;
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
                title: 'Shipments',
                href: salesRoutes.shipments.index.url(p.currentTeam!.slug),
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
    | 'invoice'
    | 'transaction_date'
    | 'customer'
    | 'location'
    | 'status'
    | 'delivered_to'
    | 'carrier'
    | 'shipping_charges'
    | 'final_total';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    { id: 'invoice', label: 'Invoice', sortKey: 'invoice_no' },
    { id: 'transaction_date', label: 'Date', sortKey: 'transaction_date' },
    { id: 'customer', label: 'Customer', sortKey: null },
    { id: 'location', label: 'Location', sortKey: null },
    { id: 'status', label: 'Status', sortKey: 'shipping_status' },
    { id: 'delivered_to', label: 'Delivered to', sortKey: null },
    { id: 'carrier', label: 'Carrier / person', sortKey: null },
    {
        id: 'shipping_charges',
        label: 'Shipping',
        sortKey: 'shipping_charges',
    },
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
        salesRoutes.shipments.index.url(teamSlug.value),
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
        salesRoutes.shipments.index.url(teamSlug.value),
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
    if (col === 'invoice') {
        return row.invoice_no?.trim() ? row.invoice_no : '—';
    }

    if (col === 'transaction_date' && row.transaction_date) {
        return new Date(row.transaction_date).toLocaleString();
    }

    if (col === 'customer') {
        return row.customer?.display_name ?? '—';
    }

    if (col === 'location') {
        return row.business_location?.name ?? '—';
    }

    if (col === 'status') {
        return row.shipping_status?.trim() ? row.shipping_status : '—';
    }

    if (col === 'delivered_to') {
        return row.delivered_to?.trim() ? row.delivered_to : '—';
    }

    if (col === 'carrier') {
        return row.delivery_person?.trim() ? row.delivery_person : '—';
    }

    if (col === 'shipping_charges') {
        return row.shipping_charges;
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

function cellTitle(row: Row, col: ColId): string | undefined {
    if (col === 'delivered_to') {
        return row.delivered_to ?? undefined;
    }

    if (col === 'carrier') {
        return row.delivery_person ?? undefined;
    }

    return undefined;
}
</script>

<template>
    <Head title="Shipments" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Shipments</h1>
            <p class="text-muted-foreground text-sm">
                Completed sales with shipping information (fulfillment view).
            </p>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Invoice, customer, status…"
            :per-page-options="[10, 15, 25, 50]"
            :paginator="shipments"
            @page="goToPage"
        >
            <table class="w-full min-w-[960px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-border">
                        <th
                            v-for="col in allColumns"
                            :key="col.id"
                            class="bg-muted/40 px-3 py-2 text-left font-medium"
                            :class="{
                                'text-right':
                                    col.id === 'shipping_charges' ||
                                    col.id === 'final_total',
                            }"
                        >
                            <button
                                v-if="col.sortKey"
                                type="button"
                                class="inline-flex items-center gap-1 hover:text-primary"
                                :class="
                                    col.id === 'shipping_charges' ||
                                    col.id === 'final_total'
                                        ? 'float-right'
                                        : ''
                                "
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
                        v-for="row in shipments.data ?? []"
                        :key="row.id"
                        class="border-b border-border/80 hover:bg-muted/20"
                    >
                        <td
                            v-for="col in allColumns"
                            :key="col.id"
                            class="px-3 py-2"
                            :class="{
                                'font-medium': col.id === 'invoice',
                                'whitespace-nowrap':
                                    col.id === 'transaction_date' ||
                                    col.id === 'shipping_charges' ||
                                    col.id === 'final_total',
                                'text-right':
                                    col.id === 'shipping_charges' ||
                                    col.id === 'final_total',
                                'max-w-[140px] truncate':
                                    col.id === 'delivered_to' ||
                                    col.id === 'carrier',
                            }"
                            :title="cellTitle(row, col.id)"
                        >
                            {{ displayCell(row, col.id) }}
                        </td>
                    </tr>
                    <tr v-if="!(shipments?.data?.length)">
                        <td
                            :colspan="allColumns.length"
                            class="text-muted-foreground px-3 py-8 text-center"
                        >
                            No completed sales yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </StandardDataTable>
    </div>
</template>
