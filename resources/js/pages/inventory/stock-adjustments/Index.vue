<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
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
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

defineProps<{
    adjustments: Paginated;
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

function goToPage(url: string | null) {
    if (url) {
        router.visit(url, { preserveState: true, replace: true });
    }
}
</script>

<template>
    <Head title="Stock adjustments" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
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

        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="w-full min-w-[720px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/40">
                        <th class="px-3 py-2 text-left font-medium">Reference</th>
                        <th class="px-3 py-2 text-left font-medium">Date</th>
                        <th class="px-3 py-2 text-left font-medium">Location</th>
                        <th class="px-3 py-2 text-left font-medium">Type</th>
                        <th class="px-3 py-2 text-right font-medium">Recovered</th>
                        <th class="px-3 py-2 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in adjustments.data ?? []"
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
                        <td class="px-3 py-2 capitalize">{{ row.adjustment_type }}</td>
                        <td class="px-3 py-2 text-right">
                            {{ row.total_amount_recovered }}
                        </td>
                        <td class="px-3 py-2 text-right">{{ row.final_total }}</td>
                    </tr>
                    <tr v-if="!(adjustments?.data?.length)">
                        <td
                            colspan="6"
                            class="text-muted-foreground px-3 py-8 text-center"
                        >
                            No stock adjustments yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="adjustments.last_page > 1"
            class="flex flex-wrap justify-center gap-1"
        >
            <Button
                v-for="(link, i) in adjustments.links"
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
