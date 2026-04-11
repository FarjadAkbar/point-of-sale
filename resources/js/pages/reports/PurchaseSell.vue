<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Printer } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import reportRoutes from '@/routes/reports';
import type { Team } from '@/types';

type Filters = { start_date: string; end_date: string; business_location_id: number | null };
type Summary = Record<string, string>;

const props = defineProps<{
    filters: Filters;
    businessLocations: Array<{ id: number; name: string }>;
    summary: Summary;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Reports',
                href: p.currentTeam ? reportRoutes.profitLoss.url(p.currentTeam.slug) : '#',
            },
            {
                title: 'Purchase & Sell',
                href: p.currentTeam ? reportRoutes.purchaseSell.url(p.currentTeam.slug) : '#',
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(() => (page.props.currentTeam as Team | null)?.slug ?? '');

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const locationId = ref<string>(
    props.filters.business_location_id != null ? String(props.filters.business_location_id) : '',
);

function currency(n: string) {
    const v = parseFloat(n);
    if (Number.isNaN(v)) {
        return '—';
    }
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(v);
}

function applyFilters() {
    const q: Record<string, string> = {
        start_date: startDate.value,
        end_date: endDate.value,
    };
    if (locationId.value) {
        q.business_location_id = locationId.value;
    }
    router.get(reportRoutes.purchaseSell.url(teamSlug.value), q, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function signedClass(key: 'sell_minus_purchase' | 'difference_due') {
    const v = parseFloat(props.summary[key] ?? '0');
    if (v < 0) {
        return 'text-destructive';
    }
    if (v > 0) {
        return 'text-emerald-600 dark:text-emerald-500';
    }
    return '';
}
</script>

<template>
    <Head title="Purchase & Sell report" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div class="mb-2 hidden print:block">
            <h2 class="text-lg font-semibold">Purchase &amp; Sale report</h2>
        </div>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Purchase &amp; Sell</h1>
                <p class="text-muted-foreground text-sm">
                    Received purchases and final sales in the selected range. Due amounts are unpaid balances on those
                    documents (final total minus recorded payments).
                </p>
            </div>
            <Button variant="outline" class="print:hidden" type="button" @click="() => window.print()">
                <Printer class="mr-2 size-4" />
                Print
            </Button>
        </div>

        <div class="grid gap-4 print:hidden md:grid-cols-3">
            <div class="space-y-2 md:col-span-1">
                <Label for="ps-location">Location</Label>
                <select
                    id="ps-location"
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
                <Label for="ps-start">Start date</Label>
                <Input id="ps-start" v-model="startDate" type="date" />
            </div>
            <div class="space-y-2">
                <Label for="ps-end">End date</Label>
                <Input id="ps-end" v-model="endDate" type="date" />
            </div>
            <div class="flex items-end md:col-span-3">
                <Button type="button" @click="applyFilters">Apply filters</Button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">Purchases</CardTitle>
                </CardHeader>
                <CardContent class="space-y-1 text-sm">
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Total purchase</span>
                        <span class="tabular-nums">{{ currency(summary.total_purchase) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Purchase including tax</span>
                        <span class="tabular-nums">{{ currency(summary.purchase_inc_tax) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Total purchase return including tax</span>
                        <span class="tabular-nums">{{ currency(summary.purchase_return_inc_tax) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 py-1.5">
                        <span class="inline-flex items-center gap-1">
                            Purchase due
                            <span
                                class="text-muted-foreground cursor-help text-xs"
                                title="Total unpaid amount on received purchases in this range (final total minus payments)."
                            >
                                (i)
                            </span>
                        </span>
                        <span class="tabular-nums">{{ currency(summary.purchase_due) }}</span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-base">Sales</CardTitle>
                </CardHeader>
                <CardContent class="space-y-1 text-sm">
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Total sale</span>
                        <span class="tabular-nums">{{ currency(summary.total_sell) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Sale including tax</span>
                        <span class="tabular-nums">{{ currency(summary.sell_inc_tax) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-b border-border/60 py-1.5">
                        <span>Total sell return including tax</span>
                        <span class="tabular-nums">{{ currency(summary.total_sell_return) }}</span>
                    </div>
                    <div class="flex justify-between gap-4 py-1.5">
                        <span class="inline-flex items-center gap-1">
                            Sale due
                            <span
                                class="text-muted-foreground cursor-help text-xs"
                                title="Total amount still to receive on final sales in this range (final total minus payments)."
                            >
                                (i)
                            </span>
                        </span>
                        <span class="tabular-nums">{{ currency(summary.sell_due) }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader class="pb-2">
                <CardTitle class="text-base">
                    Overall
                    <span class="text-muted-foreground block text-xs font-normal md:inline md:pl-2">
                        (Sale − sell return) − (Purchase − purchase return)
                    </span>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div>
                    <h3 class="text-muted-foreground text-sm font-medium">Sale − purchase</h3>
                    <p class="text-2xl font-semibold tabular-nums" :class="signedClass('sell_minus_purchase')">
                        {{ currency(summary.sell_minus_purchase) }}
                    </p>
                </div>
                <div>
                    <h3 class="text-muted-foreground text-sm font-medium">Due amount</h3>
                    <p class="text-2xl font-semibold tabular-nums" :class="signedClass('difference_due')">
                        {{ currency(summary.difference_due) }}
                    </p>
                    <p class="text-muted-foreground mt-1 text-xs">
                        Sale due minus purchase due. Negative means more to pay to suppliers; positive means more to
                        collect from customers.
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

<style scoped>
@media print {
    .print\:hidden {
        display: none !important;
    }
    .print\:block {
        display: block !important;
    }
    .print\:p-2 {
        padding: 0.5rem !important;
    }
}
</style>
