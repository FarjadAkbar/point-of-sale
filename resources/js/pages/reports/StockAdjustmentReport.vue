<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ExternalLink, Printer } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { reportRowMatchesSearch } from '@/lib/reportTableSearch';
import reportRoutes from '@/routes/reports';
import type { Team } from '@/types';

const props = defineProps<{
    filters: {
        start_date: string;
        end_date: string;
        business_location_id: number | null;
    };
    businessLocations: Array<{ id: number; name: string }>;
    totals: {
        total_normal: string;
        total_abnormal: string;
        total_amount: string;
        total_recovered: string;
    };
    rows: Array<{
        id: number;
        detail_url: string;
        transaction_date: string | null;
        ref_no: string;
        location: string;
        adjustment_type: string;
        final_total: string;
        total_amount_recovered: string;
        reason: string;
        added_by: string;
    }>;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Reports',
                href: p.currentTeam ? reportRoutes.profitLoss.url(p.currentTeam.slug) : '#',
            },
            {
                title: 'Stock adjustment report',
                href: p.currentTeam ? reportRoutes.stockAdjustment.url(p.currentTeam.slug) : '#',
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(() => (page.props.currentTeam as Team | null)?.slug ?? '');

const search = ref('');
const perPage = ref('25');
const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const locationId = ref<string>(
    props.filters.business_location_id != null ? String(props.filters.business_location_id) : '',
);

const filteredRows = computed(() =>
    props.rows.filter((r) => reportRowMatchesSearch(r, search.value)),
);

watch(
    () => props.filters,
    (f) => {
        startDate.value = f.start_date;
        endDate.value = f.end_date;
        locationId.value = f.business_location_id != null ? String(f.business_location_id) : '';
    },
    { deep: true },
);

function triggerPrint(): void {
    globalThis.print();
}

function currency(n: string) {
    const v = parseFloat(n);

    if (Number.isNaN(v)) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(v);
}

function formatDate(iso: string | null) {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleString();
}

function applyFilters() {
    const q: Record<string, string> = {
        start_date: startDate.value,
        end_date: endDate.value,
    };

    if (locationId.value) {
        q.business_location_id = locationId.value;
    }

    router.get(reportRoutes.stockAdjustment.url(teamSlug.value), q, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Stock adjustment report" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Stock adjustment report</h1>
            <p class="text-muted-foreground text-sm">Adjustments in the selected period and location.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardContent class="space-y-2 pt-6 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Total normal</span>
                        <span class="font-medium tabular-nums">{{ currency(totals.total_normal) }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Total abnormal</span>
                        <span class="font-medium tabular-nums">{{ currency(totals.total_abnormal) }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Total stock adjustment</span>
                        <span class="font-semibold tabular-nums">{{ currency(totals.total_amount) }}</span>
                    </div>
                </CardContent>
            </Card>
            <Card>
                <CardContent class="space-y-2 pt-6 text-sm">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Total amount recovered</span>
                        <span class="font-medium tabular-nums">{{ currency(totals.total_recovered) }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            class="print:hidden"
            search-placeholder="Search table…"
            :show-pagination="false"
            :show-per-page="false"
        >
            <template #filters>
                <div class="space-y-2">
                    <Label for="sar-loc">Business location</Label>
                    <select
                        id="sar-loc"
                        v-model="locationId"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">All locations</option>
                        <option v-for="loc in businessLocations" :key="loc.id" :value="String(loc.id)">
                            {{ loc.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label>Date range</Label>
                    <div class="flex flex-wrap items-center gap-2">
                        <Input v-model="startDate" type="date" class="min-w-[10rem]" />
                        <span class="text-muted-foreground text-sm">to</span>
                        <Input v-model="endDate" type="date" class="min-w-[10rem]" />
                    </div>
                </div>
                <div class="pt-1">
                    <Button type="button" size="sm" class="w-full" @click="applyFilters">Apply filters</Button>
                </div>
            </template>
            <template #toolbar-actions>
                <Button variant="outline" type="button" size="sm" @click="triggerPrint">
                    <Printer class="mr-2 size-4" />
                    Print
                </Button>
            </template>
            <div class="rounded-md border border-border overflow-x-auto">
                    <table class="w-full min-w-[720px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40">
                                <th class="px-2 py-2 text-left font-medium">Action</th>
                                <th class="px-2 py-2 text-left font-medium">Date</th>
                                <th class="px-2 py-2 text-left font-medium">Reference</th>
                                <th class="px-2 py-2 text-left font-medium">Location</th>
                                <th class="px-2 py-2 text-left font-medium">Type</th>
                                <th class="px-2 py-2 text-right font-medium">Total</th>
                                <th class="px-2 py-2 text-right font-medium">Recovered</th>
                                <th class="px-2 py-2 text-left font-medium">Reason</th>
                                <th class="px-2 py-2 text-left font-medium">Added by</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="rows.length === 0">
                                <td colspan="9" class="text-muted-foreground px-3 py-6 text-center">No rows.</td>
                            </tr>
                            <tr v-else-if="filteredRows.length === 0">
                                <td colspan="9" class="text-muted-foreground px-3 py-6 text-center">No rows match your search.</td>
                            </tr>
                            <tr
                                v-for="row in filteredRows"
                                :key="row.id"
                                class="border-b border-border/80 hover:bg-muted/20"
                            >
                                <td class="px-2 py-2">
                                    <a
                                        :href="row.detail_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-primary inline-flex items-center gap-0.5 text-xs underline-offset-4 hover:underline print:hidden"
                                    >
                                        List
                                        <ExternalLink class="size-3" />
                                    </a>
                                </td>
                                <td class="px-2 py-2 whitespace-nowrap">{{ formatDate(row.transaction_date) }}</td>
                                <td class="px-2 py-2">{{ row.ref_no || '—' }}</td>
                                <td class="px-2 py-2">{{ row.location }}</td>
                                <td class="px-2 py-2 capitalize">{{ row.adjustment_type }}</td>
                                <td class="px-2 py-2 text-right tabular-nums">{{ currency(row.final_total) }}</td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currency(row.total_amount_recovered) }}
                                </td>
                                <td class="px-2 py-2 max-w-[12rem] truncate" :title="row.reason">
                                    {{ row.reason || '—' }}
                                </td>
                                <td class="px-2 py-2">{{ row.added_by }}</td>
                            </tr>
                        </tbody>
                    </table>
            </div>
        </StandardDataTable>
    </div>
</template>

<style scoped>
@media print {
    .print\:hidden {
        display: none !important;
    }
    .print\:p-2 {
        padding: 0.5rem !important;
    }
}
</style>
