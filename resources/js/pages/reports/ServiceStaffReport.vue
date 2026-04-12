<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Printer } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import type { LaravelPaginatorLink } from '@/components/StandardDataTable.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { reportRowMatchesSearch } from '@/lib/reportTableSearch';
import reportRoutes from '@/routes/reports';
import type { Team } from '@/types';

type OrderRow = {
    id: number;
    date: string;
    invoice_no: string;
    service_staff: string;
    location: string;
    subtotal: string;
    total_discount: string;
    total_tax: string;
    total_amount: string;
    sale_url: string;
};

type LineRow = {
    sale_id: number;
    date: string;
    invoice_no: string;
    service_staff: string;
    product: string;
    quantity: string;
    unit_price: string;
    discount: string;
    tax: string;
    net_price: string;
    total: string;
    sale_url: string;
};

const props = defineProps<{
    filters: {
        start_date: string;
        end_date: string;
        business_location_id: number | null;
        service_staff_id: number | null;
    };
    businessLocations: Array<{ id: number; name: string }>;
    serviceStaff: Array<{ id: number; name: string }>;
    orderRows: OrderRow[];
    orderFooter: {
        subtotal: string;
        total_discount: string;
        total_tax: string;
        total_amount: string;
    };
    lineRows: LineRow[];
    lineFooter: {
        quantity: string;
        unit_price: string;
        discount: string;
        tax: string;
        net_price: string;
        total: string;
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
                title: 'Service staff report',
                href: p.currentTeam ? reportRoutes.serviceStaff.url(p.currentTeam.slug) : '#',
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(() => (page.props.currentTeam as Team | null)?.slug ?? '');

const activeTab = ref<'orders' | 'line'>('orders');
const search = ref('');
const perPage = ref('25');
const currentPage = ref(1);

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const locationId = ref<string>(
    props.filters.business_location_id != null ? String(props.filters.business_location_id) : '',
);
const staffId = ref<string>(
    props.filters.service_staff_id != null ? String(props.filters.service_staff_id) : '',
);

watch(
    () => props.filters,
    (f) => {
        startDate.value = f.start_date;
        endDate.value = f.end_date;
        locationId.value = f.business_location_id != null ? String(f.business_location_id) : '';
        staffId.value = f.service_staff_id != null ? String(f.service_staff_id) : '';
    },
    { deep: true },
);

watch(activeTab, () => {
    currentPage.value = 1;
});

const filteredOrderRows = computed(() =>
    props.orderRows.filter((r) => reportRowMatchesSearch(r, search.value)),
);

const filteredLineRows = computed(() =>
    props.lineRows.filter((r) => reportRowMatchesSearch(r, search.value)),
);

const activeFilteredRows = computed(() =>
    activeTab.value === 'orders' ? filteredOrderRows.value : filteredLineRows.value,
);

const perPageNum = computed(() => {
    const n = Number(perPage.value);

    return Number.isFinite(n) && n > 0 ? n : 25;
});

const PAGE_PREFIX = '__ssr_page__:';

function clientPaginatorLinks(lastPage: number, page: number): LaravelPaginatorLink[] {
    const links: LaravelPaginatorLink[] = [
        {
            url: page > 1 ? `${PAGE_PREFIX}${page - 1}` : null,
            label: '&laquo; Previous',
            active: false,
        },
    ];

    for (let i = 1; i <= lastPage; i++) {
        links.push({
            url: i === page ? null : `${PAGE_PREFIX}${i}`,
            label: String(i),
            active: i === page,
        });
    }

    links.push({
        url: page < lastPage ? `${PAGE_PREFIX}${page + 1}` : null,
        label: 'Next &raquo;',
        active: false,
    });

    return links;
}

const clientPaginator = computed(() => {
    const rows = activeFilteredRows.value;
    const total = rows.length;
    const per = perPageNum.value;
    const lastPage = Math.max(1, Math.ceil(total / per) || 1);
    const pageNum = Math.min(Math.max(1, currentPage.value), lastPage);
    const from = total === 0 ? 0 : (pageNum - 1) * per + 1;
    const to = total === 0 ? 0 : Math.min(pageNum * per, total);

    return {
        from,
        to,
        total,
        current_page: pageNum,
        last_page: lastPage,
        per_page: per,
        links: clientPaginatorLinks(lastPage, pageNum),
    };
});

const pagedOrderRows = computed(() => {
    const rows = filteredOrderRows.value;
    const per = perPageNum.value;
    const pageNum = Math.min(
        Math.max(1, currentPage.value),
        Math.max(1, Math.ceil(rows.length / per) || 1),
    );
    const start = (pageNum - 1) * per;

    return rows.slice(start, start + per);
});

const pagedLineRows = computed(() => {
    const rows = filteredLineRows.value;
    const per = perPageNum.value;
    const pageNum = Math.min(
        Math.max(1, currentPage.value),
        Math.max(1, Math.ceil(rows.length / per) || 1),
    );
    const start = (pageNum - 1) * per;

    return rows.slice(start, start + per);
});

watch([filteredOrderRows, filteredLineRows, perPage], () => {
    currentPage.value = 1;
});

function onClientPage(url: string | null) {
    if (!url?.startsWith(PAGE_PREFIX)) {
        return;
    }

    const n = Number(url.slice(PAGE_PREFIX.length));

    if (Number.isFinite(n) && n >= 1) {
        currentPage.value = n;
    }
}

function parseNum(s: string): number {
    const v = parseFloat(s);

    return Number.isFinite(v) ? v : 0;
}

const displayOrderFooter = computed(() => {
    if (!search.value.trim() || activeTab.value !== 'orders') {
        return props.orderFooter;
    }

    let sub = 0;
    let disc = 0;
    let tax = 0;
    let amt = 0;

    for (const r of filteredOrderRows.value) {
        sub += parseNum(r.subtotal);
        disc += parseNum(r.total_discount);
        tax += parseNum(r.total_tax);
        amt += parseNum(r.total_amount);
    }

    return {
        subtotal: sub.toFixed(4),
        total_discount: disc.toFixed(4),
        total_tax: tax.toFixed(4),
        total_amount: amt.toFixed(4),
    };
});

const displayLineFooter = computed(() => {
    if (!search.value.trim() || activeTab.value !== 'line') {
        return props.lineFooter;
    }

    let qty = 0;
    let disc = 0;
    let tax = 0;
    let net = 0;
    let tot = 0;

    for (const r of filteredLineRows.value) {
        qty += parseNum(r.quantity);
        disc += parseNum(r.discount);
        tax += parseNum(r.tax);
        net += parseNum(r.net_price);
        tot += parseNum(r.total);
    }

    return {
        quantity: qty.toFixed(4),
        unit_price: '0.0000',
        discount: disc.toFixed(4),
        tax: tax.toFixed(4),
        net_price: net.toFixed(4),
        total: tot.toFixed(4),
    };
});

function currencyFromNumericString(n: string) {
    const v = parseFloat(n);

    if (Number.isNaN(v)) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(v);
}

function triggerPrint(): void {
    globalThis.print();
}

function applyFilters() {
    const q: Record<string, string> = {
        start_date: startDate.value,
        end_date: endDate.value,
    };

    if (locationId.value) {
        q.business_location_id = locationId.value;
    }

    if (staffId.value) {
        q.service_staff_id = staffId.value;
    }

    router.get(reportRoutes.serviceStaff.url(teamSlug.value), q, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Service staff report" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Service staff report</h1>
            <p class="text-muted-foreground text-sm">
                Finalized sales (orders) and open line orders by creator (service staff), location, and date range.
            </p>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            table-wrapper-id="service-staff-report"
            search-placeholder="Search…"
            :per-page-options="[25, 50, 100, 200, 500, 1000]"
            :paginator="clientPaginator"
            :show-pagination="true"
            :show-per-page="true"
            @page="onClientPage"
        >
            <template #filters>
                <div class="space-y-2">
                    <Label for="ssr-loc">Business location</Label>
                    <select
                        id="ssr-loc"
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
                    <Label for="ssr-staff">Service staff</Label>
                    <select
                        id="ssr-staff"
                        v-model="staffId"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">All</option>
                        <option v-for="u in serviceStaff" :key="u.id" :value="String(u.id)">
                            {{ u.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label>Date range</Label>
                    <div class="flex flex-wrap items-center gap-2">
                        <Input id="ssr-start" v-model="startDate" type="date" class="min-w-[10rem]" />
                        <span class="text-muted-foreground text-sm">to</span>
                        <Input id="ssr-end" v-model="endDate" type="date" class="min-w-[10rem]" />
                    </div>
                </div>
                <div class="pt-1">
                    <Button type="button" size="sm" class="w-full" @click="applyFilters">Apply filters</Button>
                </div>
            </template>
            <template #toolbar-actions>
                <Button variant="outline" type="button" size="sm" class="print:hidden" @click="triggerPrint">
                    <Printer class="mr-2 size-4" />
                    Print
                </Button>
            </template>

            <div class="flex flex-col gap-4">
                <div class="print:hidden flex flex-wrap gap-2 border-b border-border pb-3">
                    <Button
                        type="button"
                        size="sm"
                        :variant="activeTab === 'orders' ? 'default' : 'outline'"
                        @click="activeTab = 'orders'"
                    >
                        Orders
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :variant="activeTab === 'line' ? 'default' : 'outline'"
                        @click="activeTab = 'line'"
                    >
                        Line orders
                    </Button>
                </div>

                <div v-show="activeTab === 'orders'">
                    <h2 class="mb-3 text-base font-semibold">Orders</h2>
                    <table class="w-full min-w-[720px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40">
                                <th class="px-2 py-2 text-left font-medium">Date</th>
                                <th class="px-2 py-2 text-left font-medium">Invoice No.</th>
                                <th class="px-2 py-2 text-left font-medium">Service staff</th>
                                <th class="px-2 py-2 text-left font-medium">Location</th>
                                <th class="px-2 py-2 text-right font-medium">Subtotal</th>
                                <th class="px-2 py-2 text-right font-medium">Total discount</th>
                                <th class="px-2 py-2 text-right font-medium">Total tax</th>
                                <th class="px-2 py-2 text-right font-medium">Total amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="orderRows.length === 0">
                                <td colspan="8" class="text-muted-foreground px-3 py-6 text-center">
                                    No finalized orders for these filters.
                                </td>
                            </tr>
                            <tr v-else-if="filteredOrderRows.length === 0">
                                <td colspan="8" class="text-muted-foreground px-3 py-6 text-center">
                                    No rows match your search.
                                </td>
                            </tr>
                            <tr
                                v-for="(r, i) in pagedOrderRows"
                                :key="`ord-${r.id}-${i}`"
                                class="border-b border-border/80 hover:bg-muted/20"
                            >
                                <td class="px-2 py-2 whitespace-nowrap">
                                    <Link :href="r.sale_url" class="text-primary font-medium hover:underline">
                                        {{ new Date(r.date).toLocaleString() }}
                                    </Link>
                                </td>
                                <td class="px-2 py-2">
                                    <Link :href="r.sale_url" class="hover:underline">{{ r.invoice_no }}</Link>
                                </td>
                                <td class="px-2 py-2">{{ r.service_staff }}</td>
                                <td class="px-2 py-2">{{ r.location }}</td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(r.subtotal) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(r.total_discount) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(r.total_tax) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(r.total_amount) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="orderRows.length">
                            <tr class="bg-muted/50 font-medium">
                                <td class="px-2 py-2" colspan="4">Total</td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(displayOrderFooter.subtotal) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(displayOrderFooter.total_discount) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(displayOrderFooter.total_tax) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(displayOrderFooter.total_amount) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div v-show="activeTab === 'line'">
                    <h2 class="mb-3 text-base font-semibold">Line orders</h2>
                    <table class="w-full min-w-[960px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/40">
                                <th class="px-2 py-2 text-left font-medium">Date</th>
                                <th class="px-2 py-2 text-left font-medium">Invoice No.</th>
                                <th class="px-2 py-2 text-left font-medium">Service staff</th>
                                <th class="px-2 py-2 text-left font-medium">Product</th>
                                <th class="px-2 py-2 text-right font-medium">Quantity</th>
                                <th class="px-2 py-2 text-right font-medium">Unit price</th>
                                <th class="px-2 py-2 text-right font-medium">Discount</th>
                                <th class="px-2 py-2 text-right font-medium">Tax</th>
                                <th class="px-2 py-2 text-right font-medium">Net price</th>
                                <th class="px-2 py-2 text-right font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="lineRows.length === 0">
                                <td colspan="10" class="text-muted-foreground px-3 py-6 text-center">
                                    No line orders for these filters.
                                </td>
                            </tr>
                            <tr v-else-if="filteredLineRows.length === 0">
                                <td colspan="10" class="text-muted-foreground px-3 py-6 text-center">
                                    No rows match your search.
                                </td>
                            </tr>
                            <tr
                                v-for="(r, i) in pagedLineRows"
                                :key="`line-${r.sale_id}-${i}-${r.product}`"
                                class="border-b border-border/80 hover:bg-muted/20"
                            >
                                <td class="px-2 py-2 whitespace-nowrap">
                                    <Link :href="r.sale_url" class="text-primary font-medium hover:underline">
                                        {{ new Date(r.date).toLocaleString() }}
                                    </Link>
                                </td>
                                <td class="px-2 py-2">
                                    <Link :href="r.sale_url" class="hover:underline">{{ r.invoice_no }}</Link>
                                </td>
                                <td class="px-2 py-2">{{ r.service_staff }}</td>
                                <td class="px-2 py-2">{{ r.product }}</td>
                                <td class="px-2 py-2 text-right tabular-nums">{{ r.quantity }}</td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(r.unit_price) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(r.discount) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(r.tax) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(r.net_price) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(r.total) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="lineRows.length">
                            <tr class="bg-muted/50 font-medium">
                                <td class="px-2 py-2" colspan="4">Total</td>
                                <td class="px-2 py-2 text-right tabular-nums">{{ displayLineFooter.quantity }}</td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(displayLineFooter.unit_price) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(displayLineFooter.discount) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(displayLineFooter.tax) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(displayLineFooter.net_price) }}
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ currencyFromNumericString(displayLineFooter.total) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
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
