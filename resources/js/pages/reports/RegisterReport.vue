<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ExternalLink, Printer } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import type { LaravelPaginatorLink } from '@/components/StandardDataTable.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { reportRowMatchesSearch } from '@/lib/reportTableSearch';
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

const FOOTER_KEYS = [
    'total_card_slips',
    'total_cheque',
    'total_cash',
    'total_bank_transfer',
    'total_advance_payment',
    'custom_pay_1',
    'custom_pay_2',
    'custom_pay_3',
    'custom_pay_4',
    'custom_pay_5',
    'custom_pay_6',
    'custom_pay_7',
    'other_payments',
    'total',
] as const satisfies readonly (keyof Pick<
    RegisterRow,
    | 'total_card_slips'
    | 'total_cheque'
    | 'total_cash'
    | 'total_bank_transfer'
    | 'total_advance_payment'
    | 'custom_pay_1'
    | 'custom_pay_2'
    | 'custom_pay_3'
    | 'custom_pay_4'
    | 'custom_pay_5'
    | 'custom_pay_6'
    | 'custom_pay_7'
    | 'other_payments'
    | 'total'
>)[];

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

const search = ref('');
const perPage = ref('25');
const currentPage = ref(1);

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

const filteredRows = computed(() =>
    props.rows.filter((r) => reportRowMatchesSearch(r, search.value)),
);

const perPageNum = computed(() => {
    const n = Number(perPage.value);

    return Number.isFinite(n) && n > 0 ? n : 25;
});

const PAGE_PREFIX = '__reg_page__:';

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
    const total = filteredRows.value.length;
    const per = perPageNum.value;
    const lastPage = Math.max(1, Math.ceil(total / per) || 1);
    const page = Math.min(Math.max(1, currentPage.value), lastPage);
    const from = total === 0 ? 0 : (page - 1) * per + 1;
    const to = total === 0 ? 0 : Math.min(page * per, total);

    return {
        from,
        to,
        total,
        current_page: page,
        last_page: lastPage,
        per_page: per,
        links: clientPaginatorLinks(lastPage, page),
    };
});

const pagedRows = computed(() => {
    const rows = filteredRows.value;
    const per = perPageNum.value;
    const page = Math.min(
        Math.max(1, currentPage.value),
        Math.max(1, Math.ceil(rows.length / per) || 1),
    );
    const start = (page - 1) * per;

    return rows.slice(start, start + per);
});

watch([filteredRows, perPage], () => {
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

function sumMetric(rows: RegisterRow[], key: (typeof FOOTER_KEYS)[number]): string {
    let s = 0;

    for (const r of rows) {
        s += parseFloat(r[key]) || 0;
    }

    return s.toFixed(4);
}

const displayFooter = computed(() => {
    if (!search.value.trim()) {
        return props.footer;
    }

    const out: Record<string, string> = {};

    for (const k of FOOTER_KEYS) {
        out[k] = sumMetric(filteredRows.value, k);
    }

    return out;
});

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
                Cash register sessions opened in the selected date range. Column totals reflect all rows matching
                server filters; when you use the table search, footer sums match the filtered rows.
            </p>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            table-wrapper-id="register-report-table"
            table-wrapper-class="max-h-[75vh] overflow-x-auto overflow-y-auto"
            search-placeholder="Search table…"
            :per-page-options="[25, 50, 100, 200, 500]"
            :paginator="clientPaginator"
            :show-pagination="true"
            :show-per-page="true"
            @page="onClientPage"
        >
            <template #filters>
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
                <Button variant="outline" type="button" size="sm" class="print:hidden" @click="triggerPrint">
                    <Printer class="mr-2 size-4" />
                    Print
                </Button>
            </template>

            <table class="min-w-[1400px] w-full border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/40">
                        <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Open time</th>
                        <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Close time</th>
                        <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Location</th>
                        <th class="px-2 py-2 text-left font-medium whitespace-nowrap">User</th>
                        <th class="px-2 py-2 text-right font-medium whitespace-nowrap">Total card slips</th>
                        <th class="px-2 py-2 text-right font-medium whitespace-nowrap">Total cheques</th>
                        <th class="px-2 py-2 text-right font-medium whitespace-nowrap">Total cash</th>
                        <th class="px-2 py-2 text-right font-medium whitespace-nowrap">Total bank transfer</th>
                        <th class="px-2 py-2 text-right font-medium whitespace-nowrap">Total advance payment</th>
                        <th v-for="n in 7" :key="n" class="px-2 py-2 text-right font-medium whitespace-nowrap">
                            Custom payment {{ n }}
                        </th>
                        <th class="px-2 py-2 text-right font-medium whitespace-nowrap">Other payments</th>
                        <th class="px-2 py-2 text-right font-medium whitespace-nowrap">Total</th>
                        <th class="px-2 py-2 text-center font-medium whitespace-nowrap print:hidden">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="rows.length === 0">
                        <td colspan="19" class="text-muted-foreground px-3 py-6 text-center text-sm">
                            No register sessions found.
                        </td>
                    </tr>
                    <tr v-else-if="filteredRows.length === 0">
                        <td colspan="19" class="text-muted-foreground px-3 py-6 text-center text-sm">
                            No rows match your search.
                        </td>
                    </tr>
                    <tr
                        v-for="r in pagedRows"
                        :key="r.id"
                        class="border-b border-border/80 hover:bg-muted/20"
                    >
                        <td class="px-2 py-2 whitespace-nowrap">{{ formatDt(r.open_time) }}</td>
                        <td class="px-2 py-2 whitespace-nowrap">{{ formatDt(r.close_time) }}</td>
                        <td class="px-2 py-2">{{ r.location }}</td>
                        <td class="px-2 py-2">{{ r.user }}</td>
                        <td class="px-2 py-2 text-right tabular-nums">{{ currency(r.total_card_slips) }}</td>
                        <td class="px-2 py-2 text-right tabular-nums">{{ currency(r.total_cheque) }}</td>
                        <td class="px-2 py-2 text-right tabular-nums">{{ currency(r.total_cash) }}</td>
                        <td class="px-2 py-2 text-right tabular-nums">{{ currency(r.total_bank_transfer) }}</td>
                        <td class="px-2 py-2 text-right tabular-nums">{{ currency(r.total_advance_payment) }}</td>
                        <td class="px-2 py-2 text-right tabular-nums">{{ currency(r.custom_pay_1) }}</td>
                        <td class="px-2 py-2 text-right tabular-nums">{{ currency(r.custom_pay_2) }}</td>
                        <td class="px-2 py-2 text-right tabular-nums">{{ currency(r.custom_pay_3) }}</td>
                        <td class="px-2 py-2 text-right tabular-nums">{{ currency(r.custom_pay_4) }}</td>
                        <td class="px-2 py-2 text-right tabular-nums">{{ currency(r.custom_pay_5) }}</td>
                        <td class="px-2 py-2 text-right tabular-nums">{{ currency(r.custom_pay_6) }}</td>
                        <td class="px-2 py-2 text-right tabular-nums">{{ currency(r.custom_pay_7) }}</td>
                        <td class="px-2 py-2 text-right tabular-nums">{{ currency(r.other_payments) }}</td>
                        <td class="px-2 py-2 text-right font-medium tabular-nums">{{ currency(r.total) }}</td>
                        <td class="px-2 py-2 text-center print:hidden">
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
                    <tr class="border-t bg-muted/50 font-medium">
                        <td class="px-2 py-2" colspan="4">Total</td>
                        <td class="px-2 py-2 text-right tabular-nums">
                            {{ currency(displayFooter.total_card_slips ?? '0') }}
                        </td>
                        <td class="px-2 py-2 text-right tabular-nums">
                            {{ currency(displayFooter.total_cheque ?? '0') }}
                        </td>
                        <td class="px-2 py-2 text-right tabular-nums">
                            {{ currency(displayFooter.total_cash ?? '0') }}
                        </td>
                        <td class="px-2 py-2 text-right tabular-nums">
                            {{ currency(displayFooter.total_bank_transfer ?? '0') }}
                        </td>
                        <td class="px-2 py-2 text-right tabular-nums">
                            {{ currency(displayFooter.total_advance_payment ?? '0') }}
                        </td>
                        <td class="px-2 py-2 text-right tabular-nums">
                            {{ currency(displayFooter.custom_pay_1 ?? '0') }}
                        </td>
                        <td class="px-2 py-2 text-right tabular-nums">
                            {{ currency(displayFooter.custom_pay_2 ?? '0') }}
                        </td>
                        <td class="px-2 py-2 text-right tabular-nums">
                            {{ currency(displayFooter.custom_pay_3 ?? '0') }}
                        </td>
                        <td class="px-2 py-2 text-right tabular-nums">
                            {{ currency(displayFooter.custom_pay_4 ?? '0') }}
                        </td>
                        <td class="px-2 py-2 text-right tabular-nums">
                            {{ currency(displayFooter.custom_pay_5 ?? '0') }}
                        </td>
                        <td class="px-2 py-2 text-right tabular-nums">
                            {{ currency(displayFooter.custom_pay_6 ?? '0') }}
                        </td>
                        <td class="px-2 py-2 text-right tabular-nums">
                            {{ currency(displayFooter.custom_pay_7 ?? '0') }}
                        </td>
                        <td class="px-2 py-2 text-right tabular-nums">
                            {{ currency(displayFooter.other_payments ?? '0') }}
                        </td>
                        <td class="px-2 py-2 text-right tabular-nums">
                            {{ currency(displayFooter.total ?? '0') }}
                        </td>
                        <td class="px-2 py-2 print:hidden" />
                    </tr>
                </tfoot>
            </table>
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
