<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    CalendarDays,
    CircleDollarSign,
    Info,
    ShoppingCart,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Team } from '@/types';

defineOptions({
    layout: (_props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [],
    }),
});

const page = usePage();
const userName = computed(() => page.props.auth.user?.name ?? 'Admin');

const dateFilterLabel = ref('Last 30 days');

const currency = (n: number) =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'USD',
    }).format(n);

type KpiHint = 'info' | 'warn' | null;

type KpiCard = {
    title: string;
    value: number;
    iconBg: string;
    icon: typeof CircleDollarSign;
    hint: KpiHint;
};

const kpiRow1: KpiCard[] = [
    {
        title: 'Total Sales',
        value: 23259.83,
        iconBg: 'bg-sky-100 text-sky-600 dark:bg-sky-950 dark:text-sky-400',
        icon: CircleDollarSign,
        hint: null,
    },
    {
        title: 'Net',
        value: 21259.83,
        iconBg: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400',
        icon: CircleDollarSign,
        hint: 'info',
    },
    {
        title: 'Invoice due',
        value: 0,
        iconBg: 'bg-amber-100 text-amber-600 dark:bg-amber-950 dark:text-amber-400',
        icon: CircleDollarSign,
        hint: null,
    },
    {
        title: 'Total Sell Return',
        value: 13.75,
        iconBg: 'bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400',
        icon: CircleDollarSign,
        hint: null,
    },
];

const kpiRow2: KpiCard[] = [
    {
        title: 'Total purchase',
        value: 235656.0,
        iconBg: 'bg-sky-100 text-sky-600 dark:bg-sky-950 dark:text-sky-400',
        icon: CircleDollarSign,
        hint: null,
    },
    {
        title: 'Purchase due',
        value: 235656.0,
        iconBg: 'bg-amber-100 text-amber-600 dark:bg-amber-950 dark:text-amber-400',
        icon: CircleDollarSign,
        hint: 'warn',
    },
    {
        title: 'Total Purchase Return',
        value: 0,
        iconBg: 'bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400',
        icon: CircleDollarSign,
        hint: null,
    },
    {
        title: 'Expense',
        value: 2000.0,
        iconBg: 'bg-red-100 text-red-600 dark:bg-red-950 dark:text-red-400',
        icon: CircleDollarSign,
        hint: null,
    },
];

const chartWidth = 600;
const chartHeight = 200;
const chartPadding = { top: 16, right: 16, bottom: 28, left: 48 };

const salesSeries = computed(() => {
    const innerW =
        chartWidth - chartPadding.left - chartPadding.right;
    const innerH =
        chartHeight - chartPadding.top - chartPadding.bottom;
    const values = Array.from({ length: 30 }, (_, i) => {
        const wobble = Math.sin(i / 3.2) * 22 + Math.sin(i / 1.7) * 12;
        return 40 + i * 2.2 + wobble + ((i * 13) % 17);
    });
    const minV = Math.min(...values);
    const maxV = Math.max(...values);
    const span = maxV - minV || 1;

    const points = values.map((v, i) => {
        const x =
            chartPadding.left + (i / (values.length - 1)) * innerW;
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

    <div class="flex flex-1 flex-col gap-4 overflow-x-auto p-4 md:p-6">
        <div
            class="flex flex-col gap-4 rounded-xl bg-emerald-950 px-5 py-5 text-white sm:flex-row sm:items-center sm:justify-between sm:py-4"
        >
            <h1 class="text-xl font-semibold tracking-tight sm:text-2xl">
                Welcome {{ userName }}, 👋
            </h1>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        type="button"
                        variant="secondary"
                        class="w-full border-0 bg-white/15 text-white hover:bg-white/25 sm:w-auto"
                    >
                        <CalendarDays class="size-4" />
                        Filter by date
                        <span class="ml-1 text-emerald-100/80"
                            >({{ dateFilterLabel }})</span
                        >
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-48">
                    <DropdownMenuItem
                        @click="dateFilterLabel = 'Today'"
                    >
                        Today
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        @click="dateFilterLabel = 'This week'"
                    >
                        This week
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        @click="dateFilterLabel = 'This month'"
                    >
                        This month
                    </DropdownMenuItem>
                    <DropdownMenuItem
                        @click="dateFilterLabel = 'Last 30 days'"
                    >
                        Last 30 days
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>

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
            class="rounded-xl border border-border bg-card p-5 shadow-sm"
        >
            <div class="mb-6 flex items-center gap-2 font-semibold">
                <div
                    class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                >
                    <ShoppingCart class="size-5" />
                </div>
                Sales Last 30 Days
            </div>

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
