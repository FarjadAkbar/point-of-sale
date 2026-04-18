<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Bell,
    Calculator,
    Download,
    LayoutGrid,
    Plus,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Spinner } from '@/components/ui/spinner';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import { dashboard } from '@/routes';
import posRoutes from '@/routes/pos';
import reportRoutes from '@/routes/reports';
import type { BreadcrumbItem } from '@/types';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const auth = computed(() => page.props.auth);

const teamSlug = computed(
    () => (page.props.currentTeam as { slug: string } | null | undefined)?.slug ?? '',
);

const dashboardUrl = computed(() =>
    page.props.currentTeam
        ? dashboard(page.props.currentTeam.slug).url
        : '/',
);

const posUrl = computed(() =>
    page.props.currentTeam
        ? posRoutes.index.url(page.props.currentTeam.slug)
        : '/',
);

const todayLabel = computed(() =>
    new Intl.DateTimeFormat(undefined, {
        month: '2-digit',
        day: '2-digit',
        year: 'numeric',
    }).format(new Date()),
);

const calculatorOpen = ref(false);
const calcBuffer = ref('');

const calcDisplayText = computed(() => {
    const s = calcBuffer.value;

    if (s === 'Error') {
        return s;
    }

    return s.replace(/\//g, '÷').replace(/\*/g, '×');
});

function mapCalcToEval(s: string): string {
    let out = s.replace(/×/g, '*').replace(/÷/g, '/');
    out = out.replace(/(\d+(?:\.\d+)?)%/g, '($1/100)');

    return out;
}

function calcAppendDigit(d: string) {
    if (calcBuffer.value === 'Error') {
        calcBuffer.value = d;

        return;
    }

    if (d === '.' && /\.\d*$/.test(calcBuffer.value)) {
        return;
    }

    calcBuffer.value += d;
}

function calcAppendPercent() {
    if (calcBuffer.value === 'Error') {
        calcBuffer.value = '';

        return;
    }

    if (/\d$/.test(calcBuffer.value)) {
        calcBuffer.value += '%';
    }
}

function calcAppendOp(op: string) {
    if (calcBuffer.value === 'Error') {
        calcBuffer.value = '';

        return;
    }

    const ch = op === '×' ? '*' : op === '÷' ? '/' : op;
    const last = calcBuffer.value.slice(-1);

    if (['+', '-', '*', '/'].includes(last) && ch !== '-') {
        calcBuffer.value = calcBuffer.value.slice(0, -1) + ch;

        return;
    }

    calcBuffer.value += ch;
}

function calcAllClear() {
    calcBuffer.value = '';
}

function calcClearEntry() {
    if (calcBuffer.value === 'Error') {
        calcBuffer.value = '';

        return;
    }

    calcBuffer.value = calcBuffer.value.slice(0, -1);
}

function calcEquals() {
    const raw = calcBuffer.value.trim();

    if (!raw || raw === 'Error') {
        return;
    }

    const expr = mapCalcToEval(raw);

    if (!/^[\d+\-*/.()]+$/.test(expr)) {
        calcBuffer.value = 'Error';

        return;
    }

    try {
        const result = new Function(`"use strict"; return (${expr})`)() as number;

        if (typeof result !== 'number' || !Number.isFinite(result)) {
            calcBuffer.value = 'Error';

            return;
        }

        const rounded = Math.round(result * 1e8) / 1e8;
        calcBuffer.value = String(rounded);
    } catch {
        calcBuffer.value = 'Error';
    }
}

const todayProfitOpen = ref(false);
const todayProfitLoading = ref(false);
const todayProfitError = ref<string | null>(null);
const todayProfitDate = ref('');
const todayProfitSummary = ref<Record<string, string> | null>(null);

type ProfitRow = { key: string; label: string; hint?: string };

const todayProfitLeftRows: ProfitRow[] = [
    {
        key: 'opening_stock_purchase',
        label: 'Opening stock',
        hint: '(By purchase price)',
    },
    {
        key: 'opening_stock_sale',
        label: 'Opening stock',
        hint: '(By sale price)',
    },
    { key: 'total_purchase_exc_tax', label: 'Total purchase', hint: '(Exc. tax, discount)' },
    { key: 'total_stock_adjustment', label: 'Total stock adjustment' },
    { key: 'total_expense', label: 'Total expense' },
    { key: 'total_purchase_shipping', label: 'Total purchase shipping charge' },
    { key: 'purchase_additional_expenses', label: 'Purchase additional expenses' },
    { key: 'total_transfer_shipping', label: 'Total transfer shipping charge' },
    { key: 'total_sell_discount', label: 'Total sell discount' },
    { key: 'total_customer_reward', label: 'Total customer reward' },
    { key: 'total_sell_return', label: 'Total sell return' },
    { key: 'total_payroll', label: 'Total payroll' },
    { key: 'total_production_cost', label: 'Total production cost' },
];

const todayProfitRightRows: ProfitRow[] = [
    {
        key: 'closing_stock_purchase',
        label: 'Closing stock',
        hint: '(By purchase price)',
    },
    {
        key: 'closing_stock_sale',
        label: 'Closing stock',
        hint: '(By sale price)',
    },
    { key: 'total_sales_exc_tax', label: 'Total sales', hint: '(Exc. tax, discount)' },
    { key: 'total_sell_shipping', label: 'Total sell shipping charge' },
    { key: 'sell_additional_expenses', label: 'Sell additional expenses' },
    { key: 'total_stock_recovered', label: 'Total stock recovered' },
    { key: 'total_purchase_return', label: 'Total purchase return' },
    { key: 'total_purchase_discount', label: 'Total purchase discount' },
    { key: 'total_sell_round_off', label: 'Total sell round off' },
    { key: 'hms_total', label: 'HMS total' },
];

function profitMoney(raw: string | undefined): string {
    const v = parseFloat(raw ?? '0');

    if (Number.isNaN(v)) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(v);
}

function summaryVal(key: string): string {
    return todayProfitSummary.value?.[key] ?? '0.0000';
}

async function loadTodayProfit() {
    const slug = teamSlug.value;

    if (!slug) {
        todayProfitError.value = 'No team selected.';

        return;
    }

    todayProfitLoading.value = true;
    todayProfitError.value = null;

    try {
        const url = reportRoutes.todayProfit.url(slug);
        const res = await fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!res.ok) {
            todayProfitError.value = "Could not load today's profit.";

            return;
        }

        const data = (await res.json()) as {
            date: string;
            summary: Record<string, string>;
        };

        todayProfitDate.value = data.date;
        todayProfitSummary.value = data.summary;
    } catch {
        todayProfitError.value = "Could not load today's profit.";
    } finally {
        todayProfitLoading.value = false;
    }
}

function openProfitLossReport() {
    const slug = teamSlug.value;

    if (!slug || !todayProfitDate.value) {
        return;
    }

    router.get(
        reportRoutes.profitLoss.url(slug, {
            query: {
                start_date: todayProfitDate.value,
                end_date: todayProfitDate.value,
            },
        }),
    );
}

watch(todayProfitOpen, (open) => {
    if (open) {
        loadTodayProfit();
    }
});

</script>

<template>
    <header class="shrink-0 border-b border-emerald-950/30 bg-emerald-950 text-white">
        <div class="flex h-14 items-center gap-2 px-4 md:h-12 md:gap-3">
            <SidebarTrigger
                class="-ml-1 text-white hover:bg-white/10 hover:text-white [&_svg]:text-white"
            />
            <Link
                :href="dashboardUrl"
                class="flex min-w-0 items-center gap-2 font-semibold tracking-tight"
            >
                <span
                    class="size-2 shrink-0 rounded-full bg-emerald-400 shadow-[0_0_8px_hsl(142_71%_55%)]"
                    aria-hidden="true"
                />
                <span class="truncate">{{ page.props.name }}</span>
            </Link>

            <div class="ml-auto flex items-center gap-1 sm:gap-2">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="hidden h-9 w-9 text-white hover:bg-white/10 hover:text-white sm:flex"
                >
                    <Download class="size-4" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="hidden h-9 w-9 text-white hover:bg-white/10 hover:text-white md:flex"
                >
                    <Plus class="size-4" />
                </Button>
                <Dialog v-model:open="calculatorOpen">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="hidden h-9 w-9 text-white hover:bg-white/10 hover:text-white lg:inline-flex"
                        aria-label="Open calculator"
                        @click="calculatorOpen = true"
                    >
                        <Calculator class="size-4" />
                    </Button>
                    <DialogContent
                        class="sm:max-w-[17rem] gap-3 p-4"
                        :show-close-button="true"
                    >
                        <DialogHeader class="space-y-1">
                            <DialogTitle>Calculator</DialogTitle>
                        </DialogHeader>
                        <Input
                            readonly
                            class="h-11 text-right font-mono text-lg tabular-nums"
                            :model-value="calcDisplayText"
                            aria-label="Calculator display"
                        />
                        <div class="grid grid-cols-4 gap-1.5">
                            <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                class="h-10 text-xs font-semibold"
                                @click="calcAllClear"
                            >
                                AC
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                class="h-10 text-xs font-semibold"
                                @click="calcClearEntry"
                            >
                                CE
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-10 font-semibold"
                                @click="calcAppendPercent"
                            >
                                %
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-10 font-semibold"
                                @click="calcAppendOp('÷')"
                            >
                                ÷
                            </Button>
                            <Button
                                v-for="d in ['7', '8', '9']"
                                :key="d"
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-10 font-semibold"
                                @click="calcAppendDigit(d)"
                            >
                                {{ d }}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-10 font-semibold"
                                @click="calcAppendOp('×')"
                            >
                                ×
                            </Button>
                            <Button
                                v-for="d in ['4', '5', '6']"
                                :key="d"
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-10 font-semibold"
                                @click="calcAppendDigit(d)"
                            >
                                {{ d }}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-10 font-semibold"
                                @click="calcAppendOp('-')"
                            >
                                -
                            </Button>
                            <Button
                                v-for="d in ['1', '2', '3']"
                                :key="d"
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-10 font-semibold"
                                @click="calcAppendDigit(d)"
                            >
                                {{ d }}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-10 font-semibold"
                                @click="calcAppendOp('+')"
                            >
                                +
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-10 font-semibold"
                                @click="calcAppendDigit('0')"
                            >
                                0
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-10 font-semibold"
                                @click="calcAppendDigit('.')"
                            >
                                .
                            </Button>
                            <Button
                                type="button"
                                variant="default"
                                size="sm"
                                class="h-10 bg-emerald-600 font-semibold text-white hover:bg-emerald-700"
                                @click="calcEquals"
                            >
                                =
                            </Button>
                            <div class="h-10" aria-hidden="true" />
                        </div>
                    </DialogContent>
                </Dialog>
                <Dialog v-model:open="todayProfitOpen">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="hidden h-9 w-9 text-white hover:bg-white/10 hover:text-white lg:inline-flex"
                        aria-label="Today’s profit"
                        @click="todayProfitOpen = true"
                    >
                        <LayoutGrid class="size-4" />
                    </Button>
                    <DialogContent
                        class="flex max-h-[min(90vh,720px)] max-w-[calc(100%-2rem)] flex-col gap-0 overflow-hidden p-0 sm:max-w-3xl"
                        :show-close-button="true"
                    >
                        <DialogHeader class="border-b border-border space-y-1 px-4 py-3 sm:px-6">
                            <DialogTitle>Today's profit</DialogTitle>
                            <p
                                v-if="todayProfitDate"
                                class="text-muted-foreground text-xs font-normal tabular-nums"
                            >
                                {{ todayProfitDate }}
                            </p>
                        </DialogHeader>
                        <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3 sm:px-6 sm:py-4">
                            <div
                                v-if="todayProfitLoading"
                                class="text-muted-foreground flex items-center justify-center gap-2 py-16 text-sm"
                            >
                                <Spinner class="size-5" />
                                Loading…
                            </div>
                            <p
                                v-else-if="todayProfitError"
                                class="text-destructive py-8 text-center text-sm"
                            >
                                {{ todayProfitError }}
                            </p>
                            <template v-else-if="todayProfitSummary">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div
                                        class="border-border bg-card text-card-foreground rounded-xl border shadow-sm"
                                    >
                                        <div class="overflow-x-auto p-3 sm:p-4">
                                            <table class="w-full border-collapse text-sm">
                                                <tbody>
                                                    <tr
                                                        v-for="row in todayProfitLeftRows"
                                                        :key="row.key"
                                                        class="border-b border-border/80 last:border-0"
                                                    >
                                                        <th
                                                            class="text-muted-foreground max-w-[55%] py-2 pr-3 text-left align-top font-medium"
                                                        >
                                                            {{ row.label }}
                                                            <span
                                                                v-if="row.hint"
                                                                class="mt-0.5 block text-xs font-normal whitespace-normal"
                                                            >
                                                                {{ row.hint }}
                                                            </span>
                                                        </th>
                                                        <td
                                                            class="py-2 text-right font-medium tabular-nums whitespace-nowrap"
                                                        >
                                                            {{ profitMoney(summaryVal(row.key)) }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div
                                        class="border-border bg-card text-card-foreground rounded-xl border shadow-sm"
                                    >
                                        <div class="overflow-x-auto p-3 sm:p-4">
                                            <table class="w-full border-collapse text-sm">
                                                <tbody>
                                                    <tr
                                                        v-for="row in todayProfitRightRows"
                                                        :key="row.key"
                                                        class="border-b border-border/80 last:border-0"
                                                    >
                                                        <th
                                                            class="text-muted-foreground max-w-[55%] py-2 pr-3 text-left align-top font-medium"
                                                        >
                                                            {{ row.label }}
                                                            <span
                                                                v-if="row.hint"
                                                                class="mt-0.5 block text-xs font-normal whitespace-normal"
                                                            >
                                                                {{ row.hint }}
                                                            </span>
                                                        </th>
                                                        <td
                                                            class="py-2 text-right font-medium tabular-nums whitespace-nowrap"
                                                        >
                                                            {{ profitMoney(summaryVal(row.key)) }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    class="border-border bg-card text-card-foreground mt-4 rounded-xl border shadow-sm"
                                >
                                    <div class="space-y-4 p-4 sm:p-5">
                                        <div>
                                            <h3 class="text-muted-foreground mb-1 text-base font-semibold">
                                                COGS:
                                                {{ profitMoney(summaryVal('cogs')) }}
                                            </h3>
                                            <p class="text-muted-foreground text-xs leading-relaxed">
                                                Cost of goods sold = starting inventory (opening stock) + purchases
                                                − ending inventory (closing stock).
                                            </p>
                                        </div>
                                        <div>
                                            <h3 class="text-muted-foreground mb-1 text-base font-semibold">
                                                Gross profit:
                                                {{ profitMoney(summaryVal('gross_profit')) }}
                                            </h3>
                                            <p class="text-muted-foreground text-xs leading-relaxed">
                                                Total sell price (exc. tax) minus default purchase cost on sold lines.
                                            </p>
                                        </div>
                                        <div>
                                            <h3 class="text-muted-foreground mb-1 text-base font-semibold">
                                                Net profit:
                                                {{ profitMoney(summaryVal('net_profit')) }}
                                            </h3>
                                            <p class="text-muted-foreground text-xs leading-relaxed">
                                                Gross profit adjusted for shipping, expenses, stock, discounts, and
                                                returns (same rules as the profit / loss report).
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <DialogFooter
                            class="border-t border-border flex flex-row flex-wrap items-center justify-end gap-2 px-4 py-3 sm:px-6"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="!teamSlug || !todayProfitDate"
                                @click="openProfitLossReport"
                            >
                                Full P&amp;L report
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                @click="todayProfitOpen = false"
                            >
                                Close
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
                <Button
                    type="button"
                    size="sm"
                    class="hidden h-8 bg-primary px-3 text-primary-foreground shadow-sm hover:bg-primary/90 sm:inline-flex"
                    as-child
                >
                    <Link :href="posUrl">POS</Link>
                </Button>
                <span
                    class="hidden text-xs text-emerald-100/90 tabular-nums sm:inline"
                >
                    {{ todayLabel }}
                </span>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="h-9 w-9 text-white hover:bg-white/10 hover:text-white"
                >
                    <Bell class="size-4" />
                </Button>
                <DropdownMenu v-if="auth.user">
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="h-9 gap-2 rounded-md px-2 text-white hover:bg-white/10 hover:text-white"
                        >
                            <Avatar class="size-7 rounded-full">
                                <AvatarImage
                                    v-if="auth.user.avatar"
                                    :src="auth.user.avatar"
                                    :alt="auth.user.name"
                                />
                                <AvatarFallback
                                    class="rounded-full bg-emerald-700 text-xs text-white"
                                >
                                    {{ getInitials(auth.user?.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <span
                                class="hidden max-w-[7rem] truncate text-sm font-medium lg:inline"
                            >
                                {{ auth.user.name }}
                            </span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent :user="auth.user" />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
        <div
            v-if="props.breadcrumbs.length > 1"
            class="border-t border-emerald-900/60 bg-background px-4 py-2 text-muted-foreground"
        >
            <Breadcrumbs :breadcrumbs="props.breadcrumbs" />
        </div>
    </header>
</template>
