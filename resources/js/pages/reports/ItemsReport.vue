<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ChevronDown, ExternalLink, Printer } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import reportRoutes from '@/routes/reports';
import type { Team } from '@/types';

type ItemRow = {
    product_name: string;
    sku: string;
    description: string;
    purchase_date: string;
    purchase_ref: string;
    purchase_url: string;
    lot_number: string;
    supplier: string;
    purchase_price: string;
    sell_date: string;
    sale_ref: string;
    sale_url: string;
    customer: string;
    location: string;
    sell_quantity: string;
    unit_label: string;
    selling_price: string;
    subtotal: string;
};

const props = defineProps<{
    filters: {
        business_location_id: number | null;
        supplier_id: number | null;
        customer_id: number | null;
        purchase_start_date: string | null | undefined;
        purchase_end_date: string | null | undefined;
        sell_start_date: string;
        sell_end_date: string;
    };
    businessLocations: Array<{ id: number; name: string }>;
    suppliers: Array<{ id: number; label: string }>;
    customers: Array<{ id: number; label: string }>;
    rows: ItemRow[];
    footer: {
        purchase_price: string;
        sell_quantity: string;
        selling_price: string;
        subtotal: string;
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
                title: 'Items report',
                href: p.currentTeam ? reportRoutes.items.url(p.currentTeam.slug) : '#',
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(() => (page.props.currentTeam as Team | null)?.slug ?? '');

const filtersOpen = ref(true);
const locationId = ref<string>(
    props.filters.business_location_id != null ? String(props.filters.business_location_id) : '',
);
const supplierId = ref<string>(props.filters.supplier_id != null ? String(props.filters.supplier_id) : '');
const customerId = ref<string>(props.filters.customer_id != null ? String(props.filters.customer_id) : '');
const purchaseStart = ref(props.filters.purchase_start_date ?? '');
const purchaseEnd = ref(props.filters.purchase_end_date ?? '');
const sellStart = ref(props.filters.sell_start_date);
const sellEnd = ref(props.filters.sell_end_date);

watch(
    () => props.filters,
    (f) => {
        locationId.value = f.business_location_id != null ? String(f.business_location_id) : '';
        supplierId.value = f.supplier_id != null ? String(f.supplier_id) : '';
        customerId.value = f.customer_id != null ? String(f.customer_id) : '';
        purchaseStart.value = f.purchase_start_date ?? '';
        purchaseEnd.value = f.purchase_end_date ?? '';
        sellStart.value = f.sell_start_date;
        sellEnd.value = f.sell_end_date;
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

function qtyLabel(q: string, unit: string) {
    const v = parseFloat(q);
    if (Number.isNaN(v)) {
        return '—';
    }
    const u = unit || '';
    return `${new Intl.NumberFormat(undefined, { maximumFractionDigits: 4 }).format(v)}${u ? ` ${u}` : ''}`;
}

function applyFilters() {
    const q: Record<string, string> = {
        sell_start_date: sellStart.value,
        sell_end_date: sellEnd.value,
    };
    if (locationId.value) {
        q.business_location_id = locationId.value;
    }
    if (supplierId.value) {
        q.supplier_id = supplierId.value;
    }
    if (customerId.value) {
        q.customer_id = customerId.value;
    }
    if (purchaseStart.value) {
        q.purchase_start_date = purchaseStart.value;
    }
    if (purchaseEnd.value) {
        q.purchase_end_date = purchaseEnd.value;
    }
    router.get(reportRoutes.items.url(teamSlug.value), q, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Items report" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Items report</h1>
            <p class="text-muted-foreground text-sm">
                Final sale lines with purchase context (latest received purchase per product). Choose a business
                location to load rows.
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
                            <Label for="ir-loc">Business location</Label>
                            <select
                                id="ir-loc"
                                v-model="locationId"
                                class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <option value="">Select location</option>
                                <option v-for="loc in businessLocations" :key="loc.id" :value="String(loc.id)">
                                    {{ loc.name }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="ir-sup">Supplier</Label>
                            <select
                                id="ir-sup"
                                v-model="supplierId"
                                class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <option value="">All</option>
                                <option v-for="s in suppliers" :key="s.id" :value="String(s.id)">
                                    {{ s.label }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="ir-cust">Customer</Label>
                            <select
                                id="ir-cust"
                                v-model="customerId"
                                class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <option value="">All</option>
                                <option v-for="c in customers" :key="c.id" :value="String(c.id)">
                                    {{ c.label }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label>Purchase date</Label>
                            <div class="flex flex-wrap items-center gap-2">
                                <Input v-model="purchaseStart" type="date" class="min-w-[10rem]" />
                                <span class="text-muted-foreground text-sm">to</span>
                                <Input v-model="purchaseEnd" type="date" class="min-w-[10rem]" />
                            </div>
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <Label>Sell date</Label>
                            <div class="flex flex-wrap items-center gap-2">
                                <Input v-model="sellStart" type="date" class="min-w-[10rem]" />
                                <span class="text-muted-foreground text-sm">to</span>
                                <Input v-model="sellEnd" type="date" class="min-w-[10rem]" />
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
                    <table class="w-full min-w-[960px] border-collapse text-xs sm:text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40">
                                <th class="px-1 py-2 text-left font-medium">Product</th>
                                <th class="px-1 py-2 text-left font-medium">SKU</th>
                                <th class="px-1 py-2 text-left font-medium">Description</th>
                                <th class="px-1 py-2 text-left font-medium">Purchase date</th>
                                <th class="px-1 py-2 text-left font-medium">Purchase</th>
                                <th class="px-1 py-2 text-left font-medium">Lot</th>
                                <th class="px-1 py-2 text-left font-medium">Supplier</th>
                                <th class="px-1 py-2 text-right font-medium">Purchase price</th>
                                <th class="px-1 py-2 text-left font-medium">Sell date</th>
                                <th class="px-1 py-2 text-left font-medium">Sale</th>
                                <th class="px-1 py-2 text-left font-medium">Customer</th>
                                <th class="px-1 py-2 text-left font-medium">Location</th>
                                <th class="px-1 py-2 text-right font-medium">Qty</th>
                                <th class="px-1 py-2 text-right font-medium">Sell price</th>
                                <th class="px-1 py-2 text-right font-medium">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="rows.length === 0">
                                <td colspan="15" class="text-muted-foreground px-3 py-6 text-center">
                                    Select a location and apply filters to load sale lines.
                                </td>
                            </tr>
                            <tr
                                v-for="(row, idx) in rows"
                                :key="idx"
                                class="border-b border-border/80 hover:bg-muted/20"
                            >
                                <td class="px-1 py-2 max-w-[8rem] truncate" :title="row.product_name">
                                    {{ row.product_name }}
                                </td>
                                <td class="px-1 py-2 font-mono text-xs">{{ row.sku }}</td>
                                <td class="px-1 py-2 max-w-[8rem] truncate" :title="row.description">
                                    {{ row.description }}
                                </td>
                                <td class="px-1 py-2 whitespace-nowrap">{{ row.purchase_date || '—' }}</td>
                                <td class="px-1 py-2">
                                    <a
                                        v-if="row.purchase_url"
                                        :href="row.purchase_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-primary inline-flex items-center gap-0.5 underline-offset-4 hover:underline print:hidden"
                                    >
                                        {{ row.purchase_ref }}
                                        <ExternalLink class="size-3" />
                                    </a>
                                    <span v-else>{{ row.purchase_ref || '—' }}</span>
                                </td>
                                <td class="px-1 py-2">{{ row.lot_number || '—' }}</td>
                                <td class="px-1 py-2 max-w-[8rem] truncate" :title="row.supplier">
                                    {{ row.supplier || '—' }}
                                </td>
                                <td class="px-1 py-2 text-right tabular-nums">{{ currency(row.purchase_price) }}</td>
                                <td class="px-1 py-2 whitespace-nowrap">{{ row.sell_date }}</td>
                                <td class="px-1 py-2">
                                    <a
                                        :href="row.sale_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-primary inline-flex items-center gap-0.5 underline-offset-4 hover:underline print:hidden"
                                    >
                                        {{ row.sale_ref }}
                                        <ExternalLink class="size-3" />
                                    </a>
                                    <span class="hidden print:inline">{{ row.sale_ref }}</span>
                                </td>
                                <td class="px-1 py-2 max-w-[8rem] truncate">{{ row.customer }}</td>
                                <td class="px-1 py-2">{{ row.location }}</td>
                                <td class="px-1 py-2 text-right tabular-nums">
                                    {{ qtyLabel(row.sell_quantity, row.unit_label) }}
                                </td>
                                <td class="px-1 py-2 text-right tabular-nums">{{ currency(row.selling_price) }}</td>
                                <td class="px-1 py-2 text-right tabular-nums">{{ currency(row.subtotal) }}</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="rows.length">
                            <tr class="bg-muted/50 font-medium">
                                <td class="px-1 py-2" colspan="7">Total</td>
                                <td class="px-1 py-2 text-right tabular-nums">
                                    {{ currency(footer.purchase_price) }}
                                </td>
                                <td class="px-1 py-2" colspan="4" />
                                <td class="px-1 py-2 text-right tabular-nums">{{ qtyLabel(footer.sell_quantity, '') }}</td>
                                <td class="px-1 py-2 text-right tabular-nums">
                                    {{ currency(footer.selling_price) }}
                                </td>
                                <td class="px-1 py-2 text-right tabular-nums">{{ currency(footer.subtotal) }}</td>
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
