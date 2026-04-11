<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { xsrfToken } from '@/lib/xsrf';
import productRoutes from '@/routes/products';
import stockAdjustmentRoutes from '@/routes/stock-adjustments';
import type { Team } from '@/types';

type LineRow = {
    product_id: number;
    name: string;
    sku: string | null;
    quantity: string;
    unit_price: string;
};

const props = defineProps<{
    businessLocations: { id: number; name: string }[];
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Stock adjustments',
                href: stockAdjustmentRoutes.index.url(p.currentTeam!.slug),
            },
            {
                title: 'Add stock adjustment',
                href: stockAdjustmentRoutes.create.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

function toLocalInput(d = new Date()): string {
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

const form = useForm({
    business_location_id: '',
    ref_no: '',
    transaction_date: toLocalInput(),
    adjustment_type: 'normal' as 'normal' | 'abnormal',
    total_amount_recovered: '0',
    additional_notes: '',
    lines: [] as LineRow[],
});

const productSearch = ref('');
const productHits = ref<
    { id: number; name: string; sku: string | null }[]
>([]);

const debouncedProductSearch = useDebounceFn(async () => {
    const t = productSearch.value.trim();
    if (t.length < 1 || !form.business_location_id) {
        productHits.value = [];

        return;
    }

    const url = productRoutes.search.url(teamSlug.value, {
        query: {
            q: t,
            business_location_id: form.business_location_id,
            active_only: '1',
        },
    });
    const r = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-XSRF-TOKEN': xsrfToken(),
        },
    });
    const j = (await r.json()) as {
        data: { id: number; name: string; sku: string | null }[];
    };
    productHits.value = j.data ?? [];
}, 300);

watch(productSearch, () => debouncedProductSearch());

function addLine(p: { id: number; name: string; sku: string | null }) {
    if (form.lines.some((l) => l.product_id === p.id)) {
        productSearch.value = '';
        productHits.value = [];

        return;
    }

    form.lines.push({
        product_id: p.id,
        name: p.name,
        sku: p.sku,
        quantity: '1',
        unit_price: '0',
    });
    productSearch.value = '';
    productHits.value = [];
}

function removeLine(i: number) {
    form.lines.splice(i, 1);
}

function lineSub(row: LineRow): number {
    const q = Number(row.quantity) || 0;
    const u = Number(row.unit_price) || 0;

    return q * u;
}

const linesTotal = computed(() =>
    form.lines.reduce((s, row) => s + lineSub(row), 0),
);

function fromDatetimeLocal(v: string): string {
    if (!v.includes('T')) {
        return v;
    }
    const [date, time] = v.split('T');
    const t = (time ?? '00:00').slice(0, 5);

    return `${date} ${t}:00`;
}

function submit() {
    form
        .transform((d) => ({
            business_location_id: Number(d.business_location_id),
            ref_no: d.ref_no?.trim() ? d.ref_no : null,
            transaction_date: fromDatetimeLocal(d.transaction_date),
            adjustment_type: d.adjustment_type,
            total_amount_recovered: Number(d.total_amount_recovered) || 0,
            additional_notes: d.additional_notes?.trim() || null,
            lines: d.lines.map((row) => ({
                product_id: row.product_id,
                quantity: Number(row.quantity),
                unit_price: Number(row.unit_price),
            })),
        }))
        .post(stockAdjustmentRoutes.store.url(teamSlug.value));
}
</script>

<template>
    <Head title="Add stock adjustment" />

    <div class="mx-auto flex max-w-4xl flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold tracking-tight">
                Add stock adjustment
            </h1>
            <Button variant="outline" as-child>
                <Link :href="stockAdjustmentRoutes.index.url(teamSlug)">
                    Back to list
                </Link>
            </Button>
        </div>

        <form class="space-y-8" @submit.prevent="submit">
            <section
                class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6"
            >
                <h2 class="mb-4 text-lg font-medium">Header</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="grid gap-2">
                        <Label>Business location *</Label>
                        <Select v-model="form.business_location_id" required>
                            <SelectTrigger>
                                <SelectValue placeholder="Select location" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="loc in businessLocations"
                                    :key="loc.id"
                                    :value="String(loc.id)"
                                >
                                    {{ loc.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="sa-ref">Reference no.</Label>
                        <Input id="sa-ref" v-model="form.ref_no" autocomplete="off" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="sa-date">Date *</Label>
                        <Input
                            id="sa-date"
                            v-model="form.transaction_date"
                            type="datetime-local"
                            required
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label>Adjustment type *</Label>
                        <Select v-model="form.adjustment_type" required>
                            <SelectTrigger>
                                <SelectValue placeholder="Type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="normal">Normal</SelectItem>
                                <SelectItem value="abnormal">Abnormal</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <p
                    v-if="form.errors.business_location_id"
                    class="text-destructive mt-2 text-sm"
                >
                    {{ form.errors.business_location_id }}
                </p>
            </section>

            <section
                class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6"
            >
                <h2 class="mb-4 text-lg font-medium">Products</h2>
                <p class="text-muted-foreground mb-3 text-xs">
                    Use a positive quantity to increase stock, or a negative quantity
                    to decrease stock (e.g. leakage).
                </p>
                <div class="grid gap-2">
                    <Label for="sa-search">Search products</Label>
                    <Input
                        id="sa-search"
                        v-model="productSearch"
                        placeholder="Search by name or SKU…"
                        :disabled="!form.business_location_id"
                    />
                    <div
                        v-if="productHits.length"
                        class="max-h-40 overflow-y-auto rounded-md border"
                    >
                        <button
                            v-for="h in productHits"
                            :key="h.id"
                            type="button"
                            class="hover:bg-muted/50 flex w-full items-center justify-between border-b px-3 py-2 text-left text-sm last:border-0"
                            @click="addLine(h)"
                        >
                            <span>{{ h.name }}</span>
                            <span class="text-muted-foreground text-xs">{{
                                h.sku ?? '—'
                            }}</span>
                        </button>
                    </div>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[560px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b bg-muted/40">
                                <th class="px-2 py-2 text-left">Product</th>
                                <th class="px-2 py-2 text-right">Quantity</th>
                                <th class="px-2 py-2 text-right">Unit price</th>
                                <th class="px-2 py-2 text-right">Subtotal</th>
                                <th class="w-10 px-2 py-2" />
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, i) in form.lines"
                                :key="`${row.product_id}-${i}`"
                                class="border-b border-border/70"
                            >
                                <td class="px-2 py-2">
                                    {{ row.name }}
                                    <span
                                        v-if="row.sku"
                                        class="text-muted-foreground text-xs"
                                        >({{ row.sku }})</span
                                    >
                                </td>
                                <td class="px-2 py-2 text-right">
                                    <Input
                                        v-model="row.quantity"
                                        type="text"
                                        inputmode="decimal"
                                        class="ml-auto max-w-24 text-right"
                                    />
                                </td>
                                <td class="px-2 py-2 text-right">
                                    <Input
                                        v-model="row.unit_price"
                                        type="text"
                                        inputmode="decimal"
                                        class="ml-auto max-w-24 text-right"
                                    />
                                </td>
                                <td class="px-2 py-2 text-right">
                                    {{ lineSub(row).toFixed(2) }}
                                </td>
                                <td class="px-2 py-2">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="size-8"
                                        @click="removeLine(i)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p
                        v-if="!form.lines.length"
                        class="text-muted-foreground py-6 text-center text-sm"
                    >
                        Add at least one product line.
                    </p>
                    <p v-if="form.errors.lines" class="text-destructive mt-2 text-sm">
                        {{ form.errors.lines }}
                    </p>
                </div>
            </section>

            <section
                class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6"
            >
                <h2 class="mb-4 text-lg font-medium">Other</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="sa-rec">Total amount recovered</Label>
                        <Input
                            id="sa-rec"
                            v-model="form.total_amount_recovered"
                            type="text"
                            inputmode="decimal"
                        />
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="sa-reason">Reason</Label>
                        <textarea
                            id="sa-reason"
                            v-model="form.additional_notes"
                            rows="3"
                            placeholder="Reason"
                            class="border-input bg-background min-h-[72px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                        />
                    </div>
                </div>
                <div class="mt-4 text-right text-sm">
                    <span class="text-muted-foreground">Lines total:</span>
                    <span class="font-semibold">{{ linesTotal.toFixed(2) }}</span>
                </div>
            </section>

            <div class="flex justify-end gap-2">
                <Button variant="outline" type="button" as-child>
                    <Link :href="stockAdjustmentRoutes.index.url(teamSlug)">
                        Cancel
                    </Link>
                </Button>
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" class="mr-2 size-4" />
                    {{ form.processing ? 'Saving…' : 'Save' }}
                </Button>
            </div>
        </form>
    </div>
</template>
