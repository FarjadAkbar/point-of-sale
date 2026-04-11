<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import stockTransferRoutes from '@/routes/stock-transfers';
import type { Team } from '@/types';

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

type Row = {
    id: number;
    ref_no: string | null;
    transaction_date: string | null;
    status: string;
    final_total: string;
    from_location: { id: number; name: string } | null;
    to_location: { id: number; name: string } | null;
};

type Paginated = {
    data: Row[];
    current_page: number;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

defineProps<{
    transfers: Paginated;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Stock transfers',
                href: stockTransferRoutes.index.url(p.currentTeam!.slug),
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
    <Head title="Stock transfers" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    List stock transfer
                </h1>
                <p class="text-muted-foreground text-sm">
                    Transfers between business locations.
                </p>
            </div>
            <Button as-child>
                <Link :href="stockTransferRoutes.create.url(teamSlug)">
                    Add stock transfer
                </Link>
            </Button>
        </div>

        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="w-full min-w-[720px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/40">
                        <th class="px-3 py-2 text-left font-medium">Reference</th>
                        <th class="px-3 py-2 text-left font-medium">Date</th>
                        <th class="px-3 py-2 text-left font-medium">From</th>
                        <th class="px-3 py-2 text-left font-medium">To</th>
                        <th class="px-3 py-2 text-left font-medium">Status</th>
                        <th class="px-3 py-2 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in transfers.data ?? []"
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
                            {{ row.from_location?.name ?? '—' }}
                        </td>
                        <td class="px-3 py-2">
                            {{ row.to_location?.name ?? '—' }}
                        </td>
                        <td class="px-3 py-2 capitalize">{{ row.status.replace('_', ' ') }}</td>
                        <td class="px-3 py-2 text-right">{{ row.final_total }}</td>
                    </tr>
                    <tr v-if="!(transfers?.data?.length)">
                        <td
                            colspan="6"
                            class="text-muted-foreground px-3 py-8 text-center"
                        >
                            No stock transfers yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="transfers.last_page > 1"
            class="flex flex-wrap justify-center gap-1"
        >
            <Button
                v-for="(link, i) in transfers.links"
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
