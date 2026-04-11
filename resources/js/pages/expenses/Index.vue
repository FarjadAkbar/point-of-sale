<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
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
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

defineProps<{
    expenses: Paginated;
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

function goToPage(url: string | null) {
    if (url) {
        router.visit(url, { preserveState: true, replace: true });
    }
}
</script>

<template>
    <Head title="Expenses" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">List expenses</h1>
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
                <Button as-child>
                    <Link :href="expenseRoutes.create.url(teamSlug)">
                        Add expense
                    </Link>
                </Button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="w-full min-w-[800px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/40">
                        <th class="px-3 py-2 text-left font-medium">Reference</th>
                        <th class="px-3 py-2 text-left font-medium">Date</th>
                        <th class="px-3 py-2 text-left font-medium">Location</th>
                        <th class="px-3 py-2 text-left font-medium">Category</th>
                        <th class="px-3 py-2 text-left font-medium">Contact</th>
                        <th class="px-3 py-2 text-center font-medium">Refund</th>
                        <th class="px-3 py-2 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in expenses.data ?? []"
                        :key="row.id"
                        class="border-b border-border/80 hover:bg-muted/20"
                    >
                        <td class="px-3 py-2">
                            {{ row.ref_no?.trim() ? row.ref_no : '—' }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{
                                row.transaction_date
                                    ? new Date(row.transaction_date).toLocaleString()
                                    : '—'
                            }}
                        </td>
                        <td class="px-3 py-2">
                            {{ row.business_location?.name ?? '—' }}
                        </td>
                        <td class="px-3 py-2">
                            {{ row.expense_category?.name ?? '—' }}
                        </td>
                        <td class="px-3 py-2">
                            {{ row.contact?.display_name ?? '—' }}
                        </td>
                        <td class="px-3 py-2 text-center">
                            {{ row.is_refund ? 'Yes' : 'No' }}
                        </td>
                        <td class="px-3 py-2 text-right">{{ row.final_total }}</td>
                    </tr>
                    <tr v-if="!(expenses?.data?.length)">
                        <td
                            colspan="7"
                            class="text-muted-foreground px-3 py-8 text-center"
                        >
                            No expenses recorded yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="expenses.last_page > 1"
            class="flex flex-wrap justify-center gap-1"
        >
            <Button
                v-for="(link, i) in expenses.links"
                :key="i"
                type="button"
                variant="outline"
                size="sm"
                :disabled="!link.url"
                :class="link.active ? 'border-primary' : ''"
                @click="goToPage(link.url)"
            >
                <span v-html="link.label" />
            </Button>
        </div>
    </div>
</template>
