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

type ChartRow = { label: string; total: string; total_raw: number };

const props = defineProps<{
    filters: {
        start_date: string;
        end_date: string;
        business_location_id: number | null;
        category_id: number | null;
    };
    businessLocations: Array<{ id: number; name: string }>;
    categories: Array<{ id: number; name: string }>;
    chart: ChartRow[];
    rows: Array<{ category: string; total: string }>;
    footer: { total: string };
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Reports',
                href: p.currentTeam ? reportRoutes.profitLoss.url(p.currentTeam.slug) : '#',
            },
            {
                title: 'Expense report',
                href: p.currentTeam ? reportRoutes.expense.url(p.currentTeam.slug) : '#',
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
const categoryId = ref<string>(props.filters.category_id != null ? String(props.filters.category_id) : '');

watch(
    () => props.filters,
    (f) => {
        startDate.value = f.start_date;
        endDate.value = f.end_date;
        locationId.value = f.business_location_id != null ? String(f.business_location_id) : '';
        categoryId.value = f.category_id != null ? String(f.category_id) : '';
    },
    { deep: true },
);

const maxChart = computed(() => {
    let m = 0;
    for (const r of props.chart) {
        const v = Math.abs(r.total_raw);
        m = Math.max(m, v);
    }
    return m > 0 ? m : 1;
});

function barHeightPx(row: ChartRow): string {
    const u = Math.abs(row.total_raw);
    const h = Math.max(4, Math.round((u / maxChart.value) * 220));
    return `${h}px`;
}

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
    if (categoryId.value) {
        q.category_id = categoryId.value;
    }
    router.get(reportRoutes.expense.url(teamSlug.value), q, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Expense report" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Expense report</h1>
            <p class="text-muted-foreground text-sm">
                Expenses aggregated by category for the selected location, category, and date range.
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
                            <Label for="er-loc">Business location</Label>
                            <select
                                id="er-loc"
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
                            <Label for="er-cat">Category</Label>
                            <select
                                id="er-cat"
                                v-model="categoryId"
                                class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <option value="">All</option>
                                <option v-for="c in categories" :key="c.id" :value="String(c.id)">
                                    {{ c.name }}
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
                <CardTitle class="text-base">Total expense by category</CardTitle>
            </CardHeader>
            <CardContent>
                <div
                    v-if="chart.length === 0"
                    class="text-muted-foreground flex h-[240px] items-center justify-center text-sm"
                >
                    No data for these filters.
                </div>
                <div v-else class="flex min-h-[240px] items-end justify-between gap-2 border-b border-border pb-2">
                    <div
                        v-for="(row, idx) in chart"
                        :key="`${row.label}-${idx}`"
                        class="flex min-w-0 flex-1 flex-col items-center gap-2"
                    >
                        <div
                            class="bg-primary/85 w-full max-w-[5rem] rounded-t transition-all"
                            :style="{ height: barHeightPx(row) }"
                            :title="currency(row.total)"
                        />
                        <p class="text-muted-foreground line-clamp-3 max-w-[7rem] text-center text-[10px] leading-tight sm:text-xs">
                            {{ row.label }}
                        </p>
                        <p class="text-muted-foreground text-[10px] tabular-nums sm:text-xs">{{ currency(row.total) }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <CardTitle class="text-base">Expense categories</CardTitle>
            </CardHeader>
            <CardContent class="overflow-x-auto">
                <table class="w-full border-collapse text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="p-2 text-left font-medium">Expense categories</th>
                            <th class="p-2 text-right font-medium">Total expense</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="rows.length === 0">
                            <td colspan="2" class="text-muted-foreground p-4 text-center">No data available in table</td>
                        </tr>
                        <tr v-for="(r, i) in rows" :key="i" class="border-b border-border/60">
                            <td class="p-2">{{ r.category }}</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(r.total) }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="font-medium">
                            <td class="p-2">Total</td>
                            <td class="p-2 text-right tabular-nums">{{ currency(footer.total) }}</td>
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
