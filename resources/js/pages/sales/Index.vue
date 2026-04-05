<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useDebounceFn, useStorage } from '@vueuse/core';
import { Plus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import salesRoutes from '@/routes/sales';
import type { Team } from '@/types';

type Row = {
    id: number;
    invoice_no: string | null;
    transaction_date: string | null;
    status: string;
    final_total: string;
    customer: { id: number; display_name: string } | null;
    business_location: { id: number; name: string } | null;
    created_at: string | null;
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
    sales: Paginated;
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
    | 'invoice_no'
    | 'transaction_date'
    | 'customer'
    | 'business_location'
    | 'status'
    | 'final_total'
    | 'created_at';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    { id: 'invoice_no', label: 'Invoice no.', sortKey: null },
    { id: 'transaction_date', label: 'Date', sortKey: 'transaction_date' },
    { id: 'customer', label: 'Customer', sortKey: null },
    { id: 'business_location', label: 'Location', sortKey: null },
    { id: 'status', label: 'Status', sortKey: 'status' },
    { id: 'final_total', label: 'Total', sortKey: 'final_total' },
    { id: 'created_at', label: 'Created', sortKey: 'created_at' },
];

const columnVisibility = useStorage<Record<string, boolean>>(
    'sales.datatable.columns',
    Object.fromEntries(allColumns.map((c) => [c.id, true])),
);

function setColumnVisible(id: string, visible: boolean) {
    columnVisibility.value = { ...columnVisibility.value, [id]: visible };
}

const visibleColumns = computed(() =>
    allColumns.filter((c) => columnVisibility.value[c.id] !== false),
);

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
        salesRoutes.index.url(teamSlug.value),
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
        salesRoutes.index.url(teamSlug.value),
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
    if (col === 'invoice_no') {
        return row.invoice_no?.trim() ? row.invoice_no : '—';
    }
    if (col === 'transaction_date' && row.transaction_date) {
        return new Date(row.transaction_date).toLocaleString();
    }
    if (col === 'customer') {
        return row.customer?.display_name ?? '—';
    }
    if (col === 'business_location') {
        return row.business_location?.name ?? '—';
    }
    if (col === 'status') {
        return row.status;
    }
    if (col === 'final_total') {
        return row.final_total;
    }
    if (col === 'created_at' && row.created_at) {
        return new Date(row.created_at).toLocaleDateString();
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
    <Head title="Sales" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">All sales</h1>
                <p class="text-sm text-muted-foreground">
                    Customer sales by location.
                </p>
            </div>
            <Button as-child>
                <Link :href="salesRoutes.create.url(teamSlug)">
                    <Plus class="mr-1 size-4" />
                    Add sale
                </Link>
            </Button>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Invoice, customer…"
            :per-page-options="[10, 15, 25, 50]"
            :paginator="sales"
            @page="goToPage"
        >
            <template #toolbar-actions>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button type="button" variant="outline" size="sm">
                            Columns
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-52">
                        <DropdownMenuLabel>Columns</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuCheckboxItem
                            v-for="col in allColumns"
                            :key="col.id"
                            :model-value="columnVisibility[col.id] !== false"
                            @update:model-value="
                                (v) => setColumnVisible(col.id, !!v)
                            "
                        >
                            {{ col.label }}
                        </DropdownMenuCheckboxItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </template>

            <table class="w-full min-w-[720px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-border">
                        <th
                            v-for="col in visibleColumns"
                            :key="col.id"
                            class="bg-muted/40 px-3 py-2 text-left font-medium"
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
                        v-for="row in sales.data ?? []"
                        :key="row.id"
                        class="border-b border-border/80 hover:bg-muted/20"
                    >
                        <td
                            v-for="col in visibleColumns"
                            :key="col.id"
                            class="px-3 py-2"
                        >
                            {{ displayCell(row, col.id) }}
                        </td>
                    </tr>
                    <tr v-if="!(sales?.data?.length)">
                        <td
                            :colspan="visibleColumns.length"
                            class="px-3 py-8 text-center text-muted-foreground"
                        >
                            No sales yet.
                            <Button
                                as-child
                                variant="link"
                                class="ml-1 h-auto p-0"
                            >
                                <Link :href="salesRoutes.create.url(teamSlug)"
                                    >Create one</Link
                                >
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </StandardDataTable>
    </div>
</template>
