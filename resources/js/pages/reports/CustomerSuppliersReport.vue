<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ChevronDown, Printer } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import reportRoutes from '@/routes/reports';
import type { Team } from '@/types';

type ReportRow = {
    kind: string;
    id: number;
    name: string;
    url: string;
    total_purchase: string;
    total_purchase_return: string;
    total_sale: string;
    total_sell_return: string;
    opening_balance_due: string;
    due: string;
};

const props = defineProps<{
    filters: {
        start_date: string;
        end_date: string;
        business_location_id: number | null;
        customer_group_id: number | null;
        contact_type: string;
        contact_key: string | null;
    };
    businessLocations: Array<{ id: number; name: string }>;
    customerGroups: Array<{ id: number; name: string }>;
    contacts: Array<{ value: string; label: string }>;
    rows: ReportRow[];
    footer: {
        total_purchase: string;
        total_purchase_return: string;
        total_sale: string;
        total_sell_return: string;
        opening_balance_due: string;
        due: string;
    };
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Reports',
                href: p.currentTeam ? reportRoutes.profitLoss.url(p.currentTeam.slug) : '#',
            },
            {
                title: 'Customer and suppliers report',
                href: p.currentTeam ? reportRoutes.customerSuppliers.url(p.currentTeam.slug) : '#',
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(() => (page.props.currentTeam as Team | null)?.slug ?? '');

const filtersOpen = ref(true);
const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const locationId = ref<string>(
    props.filters.business_location_id != null ? String(props.filters.business_location_id) : '',
);
const customerGroupId = ref<string>(
    props.filters.customer_group_id != null ? String(props.filters.customer_group_id) : '',
);
const contactType = ref<string>(props.filters.contact_type || '');
const contactKey = ref<string>(props.filters.contact_key ?? '');

watch(
    () => props.filters,
    (f) => {
        startDate.value = f.start_date;
        endDate.value = f.end_date;
        locationId.value = f.business_location_id != null ? String(f.business_location_id) : '';
        customerGroupId.value = f.customer_group_id != null ? String(f.customer_group_id) : '';
        contactType.value = f.contact_type || '';
        contactKey.value = f.contact_key ?? '';
    },
    { deep: true },
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
    if (customerGroupId.value) {
        q.customer_group_id = customerGroupId.value;
    }
    if (contactType.value) {
        q.contact_type = contactType.value;
    }
    if (contactKey.value) {
        q.contact_key = contactKey.value;
    }
    router.get(reportRoutes.customerSuppliers.url(teamSlug.value), q, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Customer and suppliers report" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Customer and suppliers report</h1>
            <p class="text-muted-foreground text-sm">
                Purchases, sales, returns, opening balance, and amounts due by contact for the selected period and
                location.
            </p>
        </div>

        <Collapsible v-model:open="filtersOpen" class="group print:hidden">
            <Card>
                <CollapsibleTrigger as-child>
                    <CardHeader class="cursor-pointer select-none pb-2">
                        <CardTitle class="text-base flex items-center gap-2">
                            <ChevronDown
                                class="size-4 shrink-0 transition-transform duration-200 group-data-[state=open]:rotate-180"
                            />
                            Filters
                        </CardTitle>
                    </CardHeader>
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <CardContent class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="csr-customer-group">Customer group</Label>
                            <select
                                id="csr-customer-group"
                                v-model="customerGroupId"
                                class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <option value="">All</option>
                                <option v-for="g in customerGroups" :key="g.id" :value="String(g.id)">
                                    {{ g.name }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="csr-type">Type</Label>
                            <select
                                id="csr-type"
                                v-model="contactType"
                                class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <option value="">All</option>
                                <option value="customer">Customers</option>
                                <option value="supplier">Suppliers</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="csr-location">Location</Label>
                            <select
                                id="csr-location"
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
                            <Label for="csr-contact">Contact</Label>
                            <select
                                id="csr-contact"
                                v-model="contactKey"
                                class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <option value="">All</option>
                                <option v-for="c in contacts" :key="c.value" :value="c.value">
                                    {{ c.label }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2 md:col-span-2 lg:col-span-2">
                            <Label>Date range</Label>
                            <div class="flex flex-wrap items-center gap-2">
                                <Input v-model="startDate" type="date" class="min-w-[10rem]" />
                                <span class="text-muted-foreground text-sm">to</span>
                                <Input v-model="endDate" type="date" class="min-w-[10rem]" />
                            </div>
                        </div>
                        <div class="flex items-end md:col-span-2 lg:col-span-3">
                            <Button type="button" @click="applyFilters">Apply filters</Button>
                        </div>
                    </CardContent>
                </CollapsibleContent>
            </Card>
        </Collapsible>

        <div class="flex justify-end print:hidden">
            <Button variant="outline" type="button" @click="() => window.print()">
                <Printer class="mr-2 size-4" />
                Print
            </Button>
        </div>

        <Card>
            <CardContent class="pt-6">
                <div class="overflow-x-auto rounded-md border border-border">
                    <table class="w-full min-w-[720px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40">
                                <th class="px-2 py-2 text-left font-medium">Contact</th>
                                <th class="px-2 py-2 text-right font-medium">Total purchase</th>
                                <th class="px-2 py-2 text-right font-medium">Total purchase return</th>
                                <th class="px-2 py-2 text-right font-medium">Total sale</th>
                                <th class="px-2 py-2 text-right font-medium">Total sell return</th>
                                <th class="px-2 py-2 text-right font-medium">Opening balance due</th>
                                <th class="px-2 py-2 text-right font-medium">
                                    <span class="inline-flex items-center gap-1">
                                        Due
                                        <span
                                            class="text-muted-foreground cursor-help text-xs font-normal"
                                            title="Negative = amount to pay. Positive = amount to receive."
                                        >
                                            (i)
                                        </span>
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="rows.length === 0">
                                <td colspan="7" class="text-muted-foreground px-3 py-6 text-center">No rows.</td>
                            </tr>
                            <tr
                                v-for="row in rows"
                                :key="`${row.kind}-${row.id}`"
                                class="border-b border-border/80 hover:bg-muted/20"
                            >
                                <td class="px-2 py-2">
                                    <a
                                        :href="row.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-primary underline-offset-4 hover:underline print:hidden"
                                    >
                                        {{ row.name }}
                                    </a>
                                    <span class="hidden print:inline">{{ row.name }}</span>
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">{{ currency(row.total_purchase) }}</td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currency(row.total_purchase_return) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">{{ currency(row.total_sale) }}</td>
                                <td class="px-2 py-2 text-right tabular-nums">{{ currency(row.total_sell_return) }}</td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currency(row.opening_balance_due) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">{{ currency(row.due) }}</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="rows.length">
                            <tr class="bg-muted/50 font-medium">
                                <td class="px-2 py-2">Total</td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currency(footer.total_purchase) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currency(footer.total_purchase_return) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">{{ currency(footer.total_sale) }}</td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currency(footer.total_sell_return) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currency(footer.opening_balance_due) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">{{ currency(footer.due) }}</td>
                            </tr>
                        </tfoot>
                    </table>
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
    .print\:p-2 {
        padding: 0.5rem !important;
    }
    .print\:inline {
        display: inline !important;
    }
}
</style>
