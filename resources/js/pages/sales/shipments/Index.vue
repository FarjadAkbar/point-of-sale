<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
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

defineProps<{
    shipments: Paginated;
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

function goToPage(url: string | null) {
    if (url) {
        router.visit(url, { preserveState: true, replace: true });
    }
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

        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="w-full min-w-[960px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/40">
                        <th class="px-3 py-2 text-left font-medium">Invoice</th>
                        <th class="px-3 py-2 text-left font-medium">Date</th>
                        <th class="px-3 py-2 text-left font-medium">Customer</th>
                        <th class="px-3 py-2 text-left font-medium">Location</th>
                        <th class="px-3 py-2 text-left font-medium">Status</th>
                        <th class="px-3 py-2 text-left font-medium">Delivered to</th>
                        <th class="px-3 py-2 text-left font-medium">Carrier / person</th>
                        <th class="px-3 py-2 text-right font-medium">Shipping</th>
                        <th class="px-3 py-2 text-right font-medium">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in shipments.data ?? []"
                        :key="row.id"
                        class="border-b border-border/80 hover:bg-muted/20"
                    >
                        <td class="px-3 py-2 font-medium">
                            {{ row.invoice_no?.trim() ? row.invoice_no : '—' }}
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            {{
                                row.transaction_date
                                    ? new Date(row.transaction_date).toLocaleString()
                                    : '—'
                            }}
                        </td>
                        <td class="px-3 py-2">
                            {{ row.customer?.display_name ?? '—' }}
                        </td>
                        <td class="px-3 py-2">
                            {{ row.business_location?.name ?? '—' }}
                        </td>
                        <td class="px-3 py-2">
                            {{ row.shipping_status?.trim() ? row.shipping_status : '—' }}
                        </td>
                        <td class="px-3 py-2 max-w-[140px] truncate" :title="row.delivered_to ?? ''">
                            {{ row.delivered_to?.trim() ? row.delivered_to : '—' }}
                        </td>
                        <td class="px-3 py-2 max-w-[140px] truncate" :title="row.delivery_person ?? ''">
                            {{ row.delivery_person?.trim() ? row.delivery_person : '—' }}
                        </td>
                        <td class="px-3 py-2 text-right whitespace-nowrap">
                            {{ row.shipping_charges }}
                        </td>
                        <td class="px-3 py-2 text-right whitespace-nowrap">
                            {{ row.final_total }}
                        </td>
                    </tr>
                    <tr v-if="!(shipments?.data?.length)">
                        <td
                            colspan="9"
                            class="text-muted-foreground px-3 py-8 text-center"
                        >
                            No completed sales yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="shipments.last_page > 1"
            class="flex flex-wrap items-center justify-center gap-1"
        >
            <Button
                v-for="(link, i) in shipments.links"
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
