<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import { Button } from '@/components/ui/button';
import expenseCategoryRoutes from '@/routes/expense-categories';
import expenseRoutes from '@/routes/expenses';
import type { Team } from '@/types';

type Row = {
    id: number;
    ref_no: string | null;
    transaction_date: string | null;
    final_total: string;
    is_refund: boolean;
    business_location: { id: number; name: string } | null;
    expense_category: { id: number; name: string } | null;
    contact: { id: number; display_name: string } | null;
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
    expenses: Paginated;
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
                title: 'Expenses',
                href: expenseRoutes.index.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);
const posPermissions = computed<string[]>(
    () => (page.props.posPermissions as string[] | undefined) ?? [],
);
const canAddExpense = computed(() => posPermissions.value.includes('expense.add'));

const search = ref(props.filters.search ?? '');
const perPage = ref(String(props.filters.per_page ?? 15));

type ColId =
    | 'ref_no'
    | 'transaction_date'
    | 'location'
    | 'category'
    | 'contact'
    | 'refund'
    | 'final_total';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    { id: 'ref_no', label: 'Reference', sortKey: 'ref_no' },
    { id: 'transaction_date', label: 'Date', sortKey: 'transaction_date' },
    { id: 'location', label: 'Location', sortKey: null },
    { id: 'category', label: 'Category', sortKey: null },
    { id: 'contact', label: 'Contact', sortKey: null },
    { id: 'refund', label: 'Refund', sortKey: null },
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
        expenseRoutes.index.url(teamSlug.value),
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
        expenseRoutes.index.url(teamSlug.value),
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

    if (col === 'category') {
        return row.expense_category?.name ?? '—';
    }

    if (col === 'contact') {
        return row.contact?.display_name ?? '—';
    }

    if (col === 'refund') {
        return row.is_refund ? 'Yes' : 'No';
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
    <Head title="Expenses" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    List expenses
                </h1>
                <p class="text-muted-foreground text-sm">
                    Business spending by location and category.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="expenseCategoryRoutes.index.url(teamSlug)">
                        Expense categories
                    </Link>
                </Button>
                <Button v-if="canAddExpense" as-child>
                    <Link :href="expenseRoutes.create.url(teamSlug)">
                        Add expense
                    </Link>
                </Button>
            </div>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Reference, category, contact…"
            :per-page-options="[10, 15, 25, 50]"
            :paginator="expenses"
            @page="goToPage"
        >
            <table class="w-full min-w-[800px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-border">
                        <th
                            v-for="col in allColumns"
                            :key="col.id"
                            class="bg-muted/40 px-3 py-2 text-left font-medium"
                            :class="{
                                'text-center': col.id === 'refund',
                                'text-right': col.id === 'final_total',
                            }"
                        >
                            <button
                                v-if="col.sortKey"
                                type="button"
                                class="inline-flex items-center gap-1 hover:text-primary"
                                :class="
                                    col.id === 'final_total' ? 'float-right' : ''
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
                        v-for="row in expenses.data ?? []"
                        :key="row.id"
                        class="border-b border-border/80 hover:bg-muted/20"
                    >
                        <td
                            v-for="col in allColumns"
                            :key="col.id"
                            class="px-3 py-2"
                            :class="{
                                'text-center': col.id === 'refund',
                                'text-right whitespace-nowrap':
                                    col.id === 'final_total',
                                'whitespace-nowrap':
                                    col.id === 'transaction_date',
                            }"
                        >
                            {{ displayCell(row, col.id) }}
                        </td>
                    </tr>
                    <tr v-if="!(expenses?.data?.length)">
                        <td
                            :colspan="allColumns.length"
                            class="text-muted-foreground px-3 py-8 text-center"
                        >
                            No expenses recorded yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </StandardDataTable>
    </div>
</template>
