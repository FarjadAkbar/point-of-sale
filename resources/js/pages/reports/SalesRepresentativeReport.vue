<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronDown, Printer } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import reportRoutes from '@/routes/reports';
import type { Team } from '@/types';

type SaleRow = {
    date: string;
    invoice_no: string;
    customer_name: string;
    location: string;
    payment_status: string;
    payment_status_label: string;
    total_amount: string;
    total_paid: string;
    total_remaining: string;
    sale_url: string;
};

type ExpenseRow = {
    date: string;
    ref_no: string;
    category: string;
    location: string;
    payment_status: string;
    payment_status_label: string;
    total_amount: string;
    expense_for: string;
    note: string;
};

const props = defineProps<{
    filters: {
        start_date: string;
        end_date: string;
        business_location_id: number | null;
        user_id: number | null;
    };
    users: Array<{ id: number; name: string }>;
    businessLocations: Array<{ id: number; name: string }>;
    summary: {
        total_sales: string;
        total_sales_return: string;
        total_sales_final: string;
        total_expenses: string;
    };
    salesRows: SaleRow[];
    salesFooter: {
        payment_status_html: string;
        total_amount: string;
        total_paid: string;
        remaining_html: string;
    };
    commissionRows: SaleRow[];
    commissionFooter: {
        payment_status_html: string;
        total_amount: string;
        total_paid: string;
        remaining_html: string;
    };
    expenseRows: ExpenseRow[];
    expenseFooter: {
        payment_status_html: string;
        total_amount: string;
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
                title: 'Sales representative report',
                href: p.currentTeam ? reportRoutes.salesRepresentative.url(p.currentTeam.slug) : '#',
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
const userId = ref<string>(props.filters.user_id != null ? String(props.filters.user_id) : '');

const activeTab = ref<'sales' | 'commission' | 'expenses'>('sales');

watch(
    () => props.filters,
    (f) => {
        startDate.value = f.start_date;
        endDate.value = f.end_date;
        locationId.value = f.business_location_id != null ? String(f.business_location_id) : '';
        userId.value = f.user_id != null ? String(f.user_id) : '';
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

function formatDt(iso: string) {
    if (!iso) {
        return '—';
    }
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) {
        return '—';
    }
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(d);
}

function applyFilters() {
    const q: Record<string, string> = {
        start_date: startDate.value,
        end_date: endDate.value,
    };
    if (locationId.value) {
        q.business_location_id = locationId.value;
    }
    if (userId.value) {
        q.user_id = userId.value;
    }
    router.get(reportRoutes.salesRepresentative.url(teamSlug.value), q, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function paymentBadgeVariant(status: string): 'default' | 'secondary' {
    return status === 'paid' ? 'default' : 'secondary';
}
</script>

<template>
    <Head title="Sales representative report" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Sales representative report</h1>
            <p class="text-muted-foreground text-sm">
                Final sales and expenses for the selected user, location, and date range. Sales are attributed to the
                user who created the invoice.
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
                            <Label for="sr-user">User</Label>
                            <select
                                id="sr-user"
                                v-model="userId"
                                class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <option value="">All users</option>
                                <option v-for="u in users" :key="u.id" :value="String(u.id)">
                                    {{ u.name }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="sr-loc">Business location</Label>
                            <select
                                id="sr-loc"
                                v-model="locationId"
                                class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <option value="">All locations</option>
                                <option v-for="loc in businessLocations" :key="loc.id" :value="String(loc.id)">
                                    {{ loc.name }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2 md:col-span-2 lg:col-span-1">
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
            <CardHeader class="pb-2">
                <CardTitle class="text-base">Summary</CardTitle>
            </CardHeader>
            <CardContent class="text-muted-foreground space-y-2 text-sm">
                <p>
                    Total sale − Total sales return:
                    <span class="text-foreground font-medium">{{ currency(summary.total_sales) }}</span>
                    −
                    <span class="text-foreground font-medium">{{ currency(summary.total_sales_return) }}</span>
                    =
                    <span class="text-foreground font-medium">{{ currency(summary.total_sales_final) }}</span>
                </p>
                <p>
                    Total expense:
                    <span class="text-foreground font-medium">{{ currency(summary.total_expenses) }}</span>
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="pt-6">
                <div class="print:hidden mb-4 flex flex-wrap gap-2 border-b border-border pb-2">
                    <Button
                        type="button"
                        size="sm"
                        :variant="activeTab === 'sales' ? 'default' : 'outline'"
                        @click="activeTab = 'sales'"
                    >
                        Sales added
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :variant="activeTab === 'commission' ? 'default' : 'outline'"
                        @click="activeTab = 'commission'"
                    >
                        Sales with commission
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :variant="activeTab === 'expenses' ? 'default' : 'outline'"
                        @click="activeTab = 'expenses'"
                    >
                        Expenses
                    </Button>
                </div>

                <div v-show="activeTab === 'sales'" class="overflow-x-auto">
                    <table class="w-full min-w-[720px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="p-2 text-left font-medium">Date</th>
                                <th class="p-2 text-left font-medium">Invoice no.</th>
                                <th class="p-2 text-left font-medium">Customer</th>
                                <th class="p-2 text-left font-medium">Location</th>
                                <th class="p-2 text-left font-medium">Payment status</th>
                                <th class="p-2 text-right font-medium">Total amount</th>
                                <th class="p-2 text-right font-medium">Total paid</th>
                                <th class="p-2 text-right font-medium">Total remaining</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="salesRows.length === 0">
                                <td colspan="8" class="text-muted-foreground p-4 text-center">No data available in table</td>
                            </tr>
                            <tr v-for="(r, i) in salesRows" :key="i" class="border-b border-border/60">
                                <td class="p-2 whitespace-nowrap">{{ formatDt(r.date) }}</td>
                                <td class="p-2">
                                    <Link :href="r.sale_url" class="text-primary hover:underline">{{ r.invoice_no }}</Link>
                                </td>
                                <td class="p-2">{{ r.customer_name }}</td>
                                <td class="p-2">{{ r.location }}</td>
                                <td class="p-2">
                                    <Link :href="r.sale_url">
                                        <Badge :variant="paymentBadgeVariant(r.payment_status)">
                                            {{ r.payment_status_label }}
                                        </Badge>
                                    </Link>
                                </td>
                                <td class="p-2 text-right tabular-nums">{{ currency(r.total_amount) }}</td>
                                <td class="p-2 text-right tabular-nums">{{ currency(r.total_paid) }}</td>
                                <td class="p-2 text-right tabular-nums">{{ currency(r.total_remaining) }}</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="salesRows.length > 0">
                            <tr class="border-t font-medium">
                                <td colspan="4" class="p-2">Total</td>
                                <td class="p-2 whitespace-pre-line text-left text-xs">{{ salesFooter.payment_status_html }}</td>
                                <td class="p-2 text-right tabular-nums">{{ currency(salesFooter.total_amount) }}</td>
                                <td class="p-2 text-right tabular-nums">{{ currency(salesFooter.total_paid) }}</td>
                                <td class="p-2 text-right text-xs whitespace-pre-line">{{ salesFooter.remaining_html }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div v-show="activeTab === 'commission'" class="overflow-x-auto">
                    <table class="w-full min-w-[720px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="p-2 text-left font-medium">Date</th>
                                <th class="p-2 text-left font-medium">Invoice no.</th>
                                <th class="p-2 text-left font-medium">Customer</th>
                                <th class="p-2 text-left font-medium">Location</th>
                                <th class="p-2 text-left font-medium">Payment status</th>
                                <th class="p-2 text-right font-medium">Total amount</th>
                                <th class="p-2 text-right font-medium">Total paid</th>
                                <th class="p-2 text-right font-medium">Total remaining</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="commissionRows.length === 0">
                                <td colspan="8" class="text-muted-foreground p-4 text-center">No data available in table</td>
                            </tr>
                            <tr v-for="(r, i) in commissionRows" :key="i" class="border-b border-border/60">
                                <td class="p-2 whitespace-nowrap">{{ formatDt(r.date) }}</td>
                                <td class="p-2">
                                    <Link :href="r.sale_url" class="text-primary hover:underline">{{ r.invoice_no }}</Link>
                                </td>
                                <td class="p-2">{{ r.customer_name }}</td>
                                <td class="p-2">{{ r.location }}</td>
                                <td class="p-2">
                                    <Link :href="r.sale_url">
                                        <Badge :variant="paymentBadgeVariant(r.payment_status)">
                                            {{ r.payment_status_label }}
                                        </Badge>
                                    </Link>
                                </td>
                                <td class="p-2 text-right tabular-nums">{{ currency(r.total_amount) }}</td>
                                <td class="p-2 text-right tabular-nums">{{ currency(r.total_paid) }}</td>
                                <td class="p-2 text-right tabular-nums">{{ currency(r.total_remaining) }}</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="commissionRows.length > 0">
                            <tr class="border-t font-medium">
                                <td colspan="4" class="p-2">Total</td>
                                <td class="p-2 whitespace-pre-line text-left text-xs">
                                    {{ commissionFooter.payment_status_html }}
                                </td>
                                <td class="p-2 text-right tabular-nums">{{ currency(commissionFooter.total_amount) }}</td>
                                <td class="p-2 text-right tabular-nums">{{ currency(commissionFooter.total_paid) }}</td>
                                <td class="p-2 text-right text-xs whitespace-pre-line">{{ commissionFooter.remaining_html }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div v-show="activeTab === 'expenses'" class="overflow-x-auto">
                    <table class="w-full min-w-[880px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="p-2 text-left font-medium">Date</th>
                                <th class="p-2 text-left font-medium">Reference no.</th>
                                <th class="p-2 text-left font-medium">Expense category</th>
                                <th class="p-2 text-left font-medium">Location</th>
                                <th class="p-2 text-left font-medium">Payment status</th>
                                <th class="p-2 text-right font-medium">Total amount</th>
                                <th class="p-2 text-left font-medium">Expense for</th>
                                <th class="p-2 text-left font-medium">Expense note</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="expenseRows.length === 0">
                                <td colspan="8" class="text-muted-foreground p-4 text-center">No data available in table</td>
                            </tr>
                            <tr v-for="(r, i) in expenseRows" :key="i" class="border-b border-border/60">
                                <td class="p-2 whitespace-nowrap">{{ formatDt(r.date) }}</td>
                                <td class="p-2">{{ r.ref_no }}</td>
                                <td class="p-2">{{ r.category }}</td>
                                <td class="p-2">{{ r.location }}</td>
                                <td class="p-2">
                                    <Badge :variant="paymentBadgeVariant(r.payment_status)">
                                        {{ r.payment_status_label }}
                                    </Badge>
                                </td>
                                <td class="p-2 text-right tabular-nums">{{ currency(r.total_amount) }}</td>
                                <td class="p-2">{{ r.expense_for }}</td>
                                <td class="p-2 max-w-[200px] truncate" :title="r.note">{{ r.note }}</td>
                            </tr>
                        </tbody>
                        <tfoot v-if="expenseRows.length > 0">
                            <tr class="border-t font-medium">
                                <td colspan="4" class="p-2">Total</td>
                                <td class="p-2 text-left text-xs">{{ expenseFooter.payment_status_html }}</td>
                                <td class="p-2 text-right tabular-nums">{{ currency(expenseFooter.total_amount) }}</td>
                                <td colspan="2" class="p-2" />
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
}
</style>
