<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
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

defineProps<{
    returns: Paginated;
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

function goToPage(url: string | null) {
    if (url) {
        router.visit(url, { preserveState: true, replace: true });
    }
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

        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="w-full min-w-[640px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/40">
                        <th class="px-3 py-2 text-left font-medium">Return</th>
                        <th class="px-3 py-2 text-left font-medium">Date</th>
                        <th class="px-3 py-2 text-left font-medium">Parent invoice</th>
                        <th class="px-3 py-2 text-left font-medium">Customer</th>
                        <th class="px-3 py-2 text-left font-medium">Location</th>
                        <th class="px-3 py-2 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in returns.data ?? []"
                        :key="row.id"
                        class="border-b border-border/80 hover:bg-muted/20"
                    >
                        <td class="px-3 py-2">
                            {{ row.invoice_no?.trim() ? row.invoice_no : '—' }}
                        </td>
                        <td class="px-3 py-2">
                            {{
                                row.transaction_date
                                    ? new Date(row.transaction_date).toLocaleString()
                                    : '—'
                            }}
                        </td>
                        <td class="px-3 py-2">
                            {{ row.parent_sale?.invoice_no ?? '—' }}
                        </td>
                        <td class="px-3 py-2">
                            {{ row.parent_sale?.customer ?? '—' }}
                        </td>
                        <td class="px-3 py-2">
                            {{ row.parent_sale?.business_location ?? '—' }}
                        </td>
                        <td class="px-3 py-2 text-right">
                            {{ row.total_return }}
                        </td>
                    </tr>
                    <tr v-if="!(returns?.data?.length)">
                        <td
                            colspan="6"
                            class="text-muted-foreground px-3 py-8 text-center"
                        >
                            No sell returns yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="returns.last_page > 1"
            class="flex flex-wrap items-center justify-center gap-1"
        >
            <Button
                v-for="(link, i) in returns.links"
                :key="i"
                type="button"
                variant="outline"
                size="sm"
                :disabled="!link.url"
                class="min-w-8"
                :class="link.active ? 'border-primary' : ''"
                @click="goToPage(link.url)"
            >
                <span v-html="link.label" />
            </Button>
        </div>
    </div>
</template>
