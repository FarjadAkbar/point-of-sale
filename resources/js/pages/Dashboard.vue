<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowRight,
    CalendarDays,
    CircleDollarSign,
    Info,
    Package,
    ShoppingCart,
    Sparkles,
    Upload,
    Users,
    Warehouse,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { dashboard } from '@/routes';
import posRoutes from '@/routes/pos';
import productRoutes from '@/routes/products';
import type { Team } from '@/types';

defineOptions({
    layout: (_props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [],
    }),
});

const props = defineProps<{
    importStats: {
        products: {
            total: number;
            last_7_days: number;
            last_30_days: number;
        };
        customers: { last_30_days: number };
        suppliers: { last_30_days: number };
    };
    financialKpis: {
        total_sales: number;
        net: number;
        invoice_due: number;
        total_sell_return: number;
        total_purchase: number;
        purchase_due: number;
        total_purchase_return: number;
        expense: number;
    };
    dashboardPeriod: 'all' | 'today' | 'week' | 'month' | 'last_30';
    salesTrend: number[];
}>();

const page = usePage();
const userName = computed(() => page.props.auth.user?.name ?? 'Admin');

const teamSlug = computed(
    () => (page.props.currentTeam as { slug: string } | null | undefined)?.slug ?? '',
);

const posHref = computed(() =>
    teamSlug.value ? posRoutes.index.url(teamSlug.value) : '#',
);

const productsHref = computed(() =>
    teamSlug.value ? productRoutes.index.url(teamSlug.value) : '#',
);

const periodDisplay: Record<string, string> = {
    all: 'All time',
    today: 'Today',
    week: 'This week',
    month: 'This month',
    last_30: 'Last 30 days',
};

const periodLabel = computed(
    () => periodDisplay[props.dashboardPeriod] ?? 'All time',
);

function visitDashboard(period: string) {
    if (!teamSlug.value) {
        return;
    }

    router.get(
        dashboard.url(teamSlug.value, { query: { period } }),
        {},
        { preserveScroll: true, replace: true },
    );
}

const currency = (n: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(n);

const formatInt = (n: number) =>
    new Intl.NumberFormat(undefined, { maximumFractionDigits: 0 }).format(n);

type KpiHint = 'info' | 'warn' | null;

type KpiCard = {
    title: string;
    value: number;
    iconBg: string;
    icon: typeof CircleDollarSign;
    hint: KpiHint;
};

const kpiRow1 = computed<KpiCard[]>(() => [
    {
        title: 'Total Sales',
        value: props.financialKpis.total_sales,
        iconBg: 'bg-sky-100 text-sky-600 dark:bg-sky-950 dark:text-sky-400',
        icon: CircleDollarSign,
        hint: null,
    },
    {
        title: 'Net',
        value: props.financialKpis.net,
        iconBg: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400',
        icon: CircleDollarSign,
        hint: 'info',
    },
    {
        title: 'Invoice due',
        value: props.financialKpis.invoice_due,
        iconBg: 'bg-amber-100 text-amber-600 dark:bg-amber-950 dark:text-amber-400',
        icon: CircleDollarSign,
        hint: null,
    },
    {
        title: 'Total Sell Return',
        value: props.financialKpis.total_sell_return,
        iconBg: 'bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400',
        icon: CircleDollarSign,
        hint: null,
    },
]);

const kpiRow2 = computed<KpiCard[]>(() => [
    {
        title: 'Total purchase',
        value: props.financialKpis.total_purchase,
        iconBg: 'bg-sky-100 text-sky-600 dark:bg-sky-950 dark:text-sky-400',
        icon: CircleDollarSign,
        hint: null,
    },
    {
        title: 'Purchase due',
        value: props.financialKpis.purchase_due,
        iconBg: 'bg-amber-100 text-amber-600 dark:bg-amber-950 dark:text-amber-400',
        icon: CircleDollarSign,
        hint: 'warn',
    },
    {
        title: 'Total Purchase Return',
        value: props.financialKpis.total_purchase_return,
        iconBg: 'bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400',
        icon: CircleDollarSign,
        hint: null,
    },
    {
        title: 'Expense',
        value: props.financialKpis.expense,
        iconBg: 'bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400',
        icon: CircleDollarSign,
        hint: null,
    },
]);

type ImportStatTile = {
    label: string;
    value: number;
    sub?: string;
    icon: typeof Package;
    accent: string;
};

const importTiles = computed<ImportStatTile[]>(() => [
    {
        label: 'Products in catalog',
        value: props.importStats.products.total,
        sub: 'All time',
        icon: Package,
        accent: 'from-violet-500/15 to-fuchsia-500/10',
    },
    {
        label: 'New products',
        value: props.importStats.products.last_7_days,
        sub: 'Last 7 days',
        icon: Sparkles,
        accent: 'from-emerald-500/15 to-teal-500/10',
    },
    {
        label: 'New products',
        value: props.importStats.products.last_30_days,
        sub: 'Last 30 days',
        icon: Upload,
        accent: 'from-sky-500/15 to-cyan-500/10',
    },
    {
        label: 'New customers',
        value: props.importStats.customers.last_30_days,
        sub: 'Last 30 days',
        icon: Users,
        accent: 'from-amber-500/15 to-orange-500/10',
    },
    {
        label: 'New suppliers',
        value: props.importStats.suppliers.last_30_days,
        sub: 'Last 30 days',
        icon: Warehouse,
        accent: 'from-rose-500/15 to-pink-500/10',
    },
]);

const chartWidth = 600;
const chartHeight = 200;
const chartPadding = { top: 16, right: 16, bottom: 28, left: 48 };

const salesSeries = computed(() => {
    const innerW =
        chartWidth - chartPadding.left - chartPadding.right;
    const innerH =
        chartHeight - chartPadding.top - chartPadding.bottom;
    const raw =
        Array.isArray(props.salesTrend) && props.salesTrend.length === 30
            ? props.salesTrend
            : Array.from({ length: 30 }, () => 0);
    const values = raw.map((v) => Number(v) || 0);
    const minV = Math.min(...values);
    const maxV = Math.max(...values);
    const span = maxV - minV || 1;
    const xDenom = Math.max(values.length - 1, 1);

    const points = values.map((v, i) => {
        const x = chartPadding.left + (i / xDenom) * innerW;
        const y =
            chartPadding.top +
            innerH -
            ((v - minV) / span) * innerH * 0.92;

        return { x, y, v };
    });

    const pathD = points
        .map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x.toFixed(1)} ${p.y.toFixed(1)}`)
        .join(' ');

    const areaD = `${pathD} L ${points[points.length - 1]!.x.toFixed(1)} ${chartHeight - chartPadding.bottom} L ${points[0]!.x.toFixed(1)} ${chartHeight - chartPadding.bottom} Z`;

    return { points, pathD, areaD, minV, maxV };
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
        <div
            class="flex flex-col gap-4 rounded-2xl bg-gradient-to-br from-emerald-900 via-emerald-950 to-slate-950 px-5 py-6 text-white shadow-lg sm:flex-row sm:items-center sm:justify-between sm:px-7"
        >
            <div>
                <p class="text-sm font-medium text-emerald-200/80">
                    Overview
                </p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">
                    Welcome back, {{ userName }}
                </h1>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <Button
                    v-if="teamSlug"
                    as-child
                    type="button"
                    variant="secondary"
                    class="border-0 bg-white text-emerald-950 shadow-sm hover:bg-emerald-50"
                >
                    <Link :href="posHref" class="inline-flex items-center gap-2">
                        Open POS
                        <ArrowRight class="size-4" />
                    </Link>
                </Button>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            type="button"
                            variant="secondary"
                            class="w-full border-0 bg-white/10 text-white hover:bg-white/20 sm:w-auto"
                        >
                            <CalendarDays class="size-4" />
                            Financial period
                            <span class="ml-1 text-emerald-100/80"
                                >({{ periodLabel }})</span
                            >
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-48">
                        <DropdownMenuItem @click="visitDashboard('all')">
                            All time
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="visitDashboard('today')">
                            Today
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="visitDashboard('week')">
                            This week
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="visitDashboard('month')">
                            This month
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="visitDashboard('last_30')">
                            Last 30 days
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>

        <section
            class="rounded-2xl border border-border/80 bg-card p-5 shadow-sm md:p-6"
        >
            <div
                class="mb-5 flex flex-col gap-4 border-b border-border/60 pb-5 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="flex gap-3">
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                    >
                        <Upload class="size-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold tracking-tight">
                            Catalog &amp; import activity
                        </h2>
                        <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                            Live counts for your team: new catalog rows and
                            contacts added recently. File-based CSV / Excel
                            import will extend this section when processing is
                            wired up.
                        </p>
                    </div>
                </div>
                <Button
                    v-if="teamSlug"
                    as-child
                    variant="outline"
                    size="sm"
                    class="shrink-0"
                >
                    <Link :href="productsHref" class="inline-flex items-center gap-1.5">
                        Manage products
                        <ArrowRight class="size-3.5" />
                    </Link>
                </Button>
            </div>

            <div
                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
            >
                <div
                    v-for="tile in importTiles"
                    :key="`${tile.label}-${tile.sub}`"
                    class="group relative overflow-hidden rounded-xl border border-border/70 bg-gradient-to-br p-4 shadow-sm transition hover:border-primary/30 hover:shadow-md"
                    :class="tile.accent"
                >
                    <div
                        class="mb-3 flex size-9 items-center justify-center rounded-lg bg-background/80 text-foreground shadow-sm ring-1 ring-border/60"
                    >
                        <component :is="tile.icon" class="size-4" />
                    </div>
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        {{ tile.label }}
                    </p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums tracking-tight">
                        {{ formatInt(tile.value) }}
                    </p>
                    <p
                        v-if="tile.sub"
                        class="mt-0.5 text-xs text-muted-foreground"
                    >
                        {{ tile.sub }}
                    </p>
                </div>
            </div>
        </section>

        <div>
            <h2 class="mb-1 text-sm font-semibold uppercase tracking-wider text-muted-foreground">
                Financial snapshot
            </h2>
            <p class="mb-3 max-w-3xl text-xs text-muted-foreground">
                Totals use the same sale rows as the Sales list (every status
                except draft and quotation), filtered by
                <span class="font-medium text-foreground">transaction date</span>
                for the period above. Summing only the current page on Sales
                will not match; use
                <span class="font-medium text-foreground">All time</span>
                here to compare against the full list.
            </p>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="card in kpiRow1"
                    :key="card.title"
                    class="flex gap-4 rounded-xl border border-border bg-card p-4 shadow-sm"
                >
                    <div
                        :class="[
                            'flex size-12 shrink-0 items-center justify-center rounded-full',
                            card.iconBg,
                        ]"
                    >
                        <component :is="card.icon" class="size-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-1 text-sm text-muted-foreground">
                            <span>{{ card.title }}</span>
                            <Info
                                v-if="card.hint === 'info'"
                                class="size-3.5 text-muted-foreground"
                            />
                            <AlertCircle
                                v-if="card.hint === 'warn'"
                                class="size-3.5 text-amber-600"
                            />
                        </div>
                        <p class="mt-1 truncate text-lg font-semibold tabular-nums">
                            {{ currency(card.value) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="card in kpiRow2"
                :key="card.title"
                class="flex gap-4 rounded-xl border border-border bg-card p-4 shadow-sm"
            >
                <div
                    :class="[
                        'flex size-12 shrink-0 items-center justify-center rounded-full',
                        card.iconBg,
                    ]"
                >
                    <component :is="card.icon" class="size-6" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1 text-sm text-muted-foreground">
                        <span>{{ card.title }}</span>
                        <Info
                            v-if="card.hint === 'info'"
                            class="size-3.5 text-muted-foreground"
                        />
                        <AlertCircle
                            v-if="card.hint === 'warn'"
                            class="size-3.5 text-amber-600"
                        />
                    </div>
                    <p class="mt-1 truncate text-lg font-semibold tabular-nums">
                        {{ currency(card.value) }}
                    </p>
                </div>
            </div>
        </div>

        <div
            class="rounded-xl border border-border bg-card p-5 shadow-sm md:p-6"
        >
            <div class="mb-6 flex items-center gap-2 font-semibold">
                <div
                    class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                >
                    <ShoppingCart class="size-5" />
                </div>
                Final sales by day (last 30 days)
            </div>
            <p class="mb-4 text-xs text-muted-foreground">
                Sum of <span class="font-medium text-foreground">final</span>
                sale invoice totals per calendar day (chart range is fixed;
                KPI cards follow the period menu).
            </p>

            <div class="w-full overflow-x-auto">
                <svg
                    class="min-h-[220px] w-full min-w-[320px]"
                    :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                    preserveAspectRatio="xMidYMid meet"
                    role="img"
                    aria-label="Sales trend chart for the last 30 days"
                >
                    <defs>
                        <linearGradient
                            id="sales-area-fill"
                            x1="0"
                            y1="0"
                            x2="0"
                            y2="1"
                        >
                            <stop
                                offset="0%"
                                stop-color="hsl(142 71% 36%)"
                                stop-opacity="0.35"
                            />
                            <stop
                                offset="100%"
                                stop-color="hsl(142 71% 36%)"
                                stop-opacity="0.02"
                            />
                        </linearGradient>
                    </defs>

                    <line
                        :x1="chartPadding.left"
                        :y1="chartHeight - chartPadding.bottom"
                        :x2="chartWidth - chartPadding.right"
                        :y2="chartHeight - chartPadding.bottom"
                        class="stroke-border"
                        stroke-width="1"
                    />

                    <path
                        :d="salesSeries.areaD"
                        fill="url(#sales-area-fill)"
                    />
                    <path
                        :d="salesSeries.pathD"
                        fill="none"
                        class="stroke-primary"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />

                    <text
                        :x="chartPadding.left - 8"
                        :y="chartPadding.top + 4"
                        class="fill-muted-foreground text-[10px]"
                        text-anchor="end"
                    >
                        {{ Math.round(salesSeries.maxV) }}
                    </text>
                    <text
                        :x="chartPadding.left - 8"
                        :y="chartHeight - chartPadding.bottom"
                        class="fill-muted-foreground text-[10px]"
                        text-anchor="end"
                    >
                        {{ Math.round(salesSeries.minV) }}
                    </text>

                    <text
                        :x="chartPadding.left"
                        :y="chartHeight - 6"
                        class="fill-muted-foreground text-[10px]"
                    >
                        Day 1
                    </text>
                    <text
                        :x="chartWidth - chartPadding.right"
                        y="192"
                        class="fill-muted-foreground text-[10px]"
                        text-anchor="end"
                    >
                        Day 30
                    </text>
                </svg>
            </div>
        </div>
    </div>
</template>
