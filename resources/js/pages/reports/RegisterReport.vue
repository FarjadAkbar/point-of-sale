<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronDown, ExternalLink, Printer } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import reportRoutes from '@/routes/reports';
import type { Team } from '@/types';

type RegisterRow = {
    id: number;
    open_time: string;
    close_time: string;
    location: string;
    user: string;
    total_card_slips: string;
    total_cheque: string;
    total_cash: string;
    total_bank_transfer: string;
    total_advance_payment: string;
    custom_pay_1: string;
    custom_pay_2: string;
    custom_pay_3: string;
    custom_pay_4: string;
    custom_pay_5: string;
    custom_pay_6: string;
    custom_pay_7: string;
    other_payments: string;
    total: string;
    action_url: string;
};

const props = defineProps<{
    filters: {
        start_date: string;
        end_date: string;
        register_user_id: number | null;
        register_status: string;
    };
    users: Array<{ id: number; name: string }>;
    rows: RegisterRow[];
    footer: Record<string, string>;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Reports',
                href: p.currentTeam ? reportRoutes.profitLoss.url(p.currentTeam.slug) : '#',
            },
            {
                title: 'Register report',
                href: p.currentTeam ? reportRoutes.register.url(p.currentTeam.slug) : '#',
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(() => (page.props.currentTeam as Team | null)?.slug ?? '');

const filtersOpen = ref(true);
const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const userId = ref<string>(
    props.filters.register_user_id != null ? String(props.filters.register_user_id) : '',
);
const status = ref<string>(props.filters.register_status ?? '');

watch(
    () => props.filters,
    (f) => {
        startDate.value = f.start_date;
        endDate.value = f.end_date;
        userId.value = f.register_user_id != null ? String(f.register_user_id) : '';
        status.value = f.register_status ?? '';
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
    if (userId.value) {
        q.register_user_id = userId.value;
    }
    if (status.value) {
        q.register_status = status.value;
    }
    router.get(reportRoutes.register.url(teamSlug.value), q, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Register report" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Register report</h1>
            <p class="text-muted-foreground text-sm">
                Cash register sessions (opened in the selected date range). Totals populate when register closes are
                recorded.
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
                            <Label for="rr-user">User</Label>
                            <select
                                id="rr-user"
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
                            <Label for="rr-status">Status</Label>
                            <select
                                id="rr-status"
                                v-model="status"
                                class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <option value="">All</option>
                                <option value="open">Open</option>
                                <option value="close">Close</option>
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
                <CardTitle class="text-base">Register sessions</CardTitle>
            </CardHeader>
            <CardContent class="overflow-x-auto">
                <table class="min-w-[1400px] w-full border-collapse text-xs">
                    <thead>
                        <tr class="border-b">
                            <th class="p-2 text-left font-medium whitespace-nowrap">Open time</th>
                            <th class="p-2 text-left font-medium whitespace-nowrap">Close time</th>
                            <th class="p-2 text-left font-medium whitespace-nowrap">Location</th>
                            <th class="p-2 text-left font-medium whitespace-nowrap">User</th>
                            <th class="p-2 text-right font-medium whitespace-nowrap">Total card slips</th>
                            <th class="p-2 text-right font-medium whitespace-nowrap">Total cheques</th>
                            <th class="p-2 text-right font-medium whitespace-nowrap">Total cash</th>
                            <th class="p-2 text-right font-medium whitespace-nowrap">Total bank transfer</th>
                            <th class="p-2 text-right font-medium whitespace-nowrap">Total advance payment</th>
                            <th v-for="n in 7" :key="n" class="p-2 text-right font-medium whitespace-nowrap">
                                Custom payment {{ n }}
                            </th>
                            <th class="p-2 text-right font-medium whitespace-nowrap">Other payments</th>
                            <th class="p-2 text-right font-medium whitespace-nowrap">Total</th>
                            <th class="p-2 text-center font-medium whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="rows.length === 0">
                            <td colspan="19" class="text-muted-foreground p-4 text-center text-sm">No register sessions found.</td>
                        </tr>
                        <tr v-for="r in rows" :key="r.id" class="border-b border-border/60">
                            <td class="p-2 whitespace-nowrap">{{ formatDt(r.open_time) }}</td>
                            <td class="p-2 whitespace-nowrap">{{ formatDt(r.close_time) }}</td>
                            <td class="p-2">{{ r.location }}</td>
                            <td class="p-2">{{ r.user }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(r.total_card_slips) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(r.total_cheque) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(r.total_cash) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(r.total_bank_transfer) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(r.total_advance_payment) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(r.custom_pay_1) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(r.custom_pay_2) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(r.custom_pay_3) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(r.custom_pay_4) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(r.custom_pay_5) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(r.custom_pay_6) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(r.custom_pay_7) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(r.other_payments) }}</td>
                            <td class="p-2 text-right tabular-nums font-medium">{{ currency(r.total) }}</td>
                            <td class="p-2 text-center">
                                <Button variant="ghost" size="sm" class="h-8 px-2" as-child>
                                    <Link :href="r.action_url" class="inline-flex items-center gap-1">
                                        <ExternalLink class="size-3.5" />
                                        POS
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="rows.length > 0">
                        <tr class="border-t font-medium">
                            <td colspan="4" class="p-2">Total</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(footer.total_card_slips) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(footer.total_cheque) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(footer.total_cash) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(footer.total_bank_transfer) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(footer.total_advance_payment) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(footer.custom_pay_1) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(footer.custom_pay_2) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(footer.custom_pay_3) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(footer.custom_pay_4) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(footer.custom_pay_5) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(footer.custom_pay_6) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(footer.custom_pay_7) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(footer.other_payments) }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(footer.total) }}</td>
                            <td class="p-2" />
                        </tr>
                    </tfoot>
                </table>
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
