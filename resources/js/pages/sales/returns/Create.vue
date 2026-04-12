<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
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
import salesRoutes from '@/routes/sales';
import type { Team } from '@/types';

type ParentLine = {
    id: number;
    product_name: string;
    sku: string | null;
    quantity: string;
    unit_price_exc_tax: string;
};

const props = defineProps<{
    parent: {
        id: number;
        invoice_no: string | null;
        transaction_date: string | null;
        discount_type: string;
        discount_amount: string;
        customer: { id: number; display_name: string } | null;
        business_location: { id: number; name: string } | null;
        lines: ParentLine[];
    };
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Sales',
                href: salesRoutes.index.url(p.currentTeam!.slug),
            },
            {
                title: 'Sell returns',
                href: salesRoutes.returns.index.url(p.currentTeam!.slug),
            },
            {
                title: 'Add return',
                href: salesRoutes.returns.create.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

function toDatetimeLocal(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const d = new Date(iso);
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function fromDatetimeLocal(v: string): string {
    if (!v.includes('T')) {
        return v;
    }

    const [date, time] = v.split('T');
    const t = (time ?? '00:00').slice(0, 5);

    return `${date} ${t}:00`;
}

const discType =
    props.parent.discount_type === 'fixed' ||
    props.parent.discount_type === 'percentage'
        ? props.parent.discount_type
        : 'none';

const form = useForm({
    parent_sale_id: props.parent.id,
    invoice_no: '' as string | null,
    transaction_date: toDatetimeLocal(props.parent.transaction_date),
    discount_type: discType as 'none' | 'fixed' | 'percentage',
    discount_amount: props.parent.discount_amount ?? '0',
    lines: props.parent.lines.map((l) => ({
        sale_line_id: l.id,
        quantity: '0',
    })),
});

function lineQty(index: number): number {
    const raw = form.lines[index]?.quantity ?? '0';
    const n = parseFloat(String(raw).replace(',', '.'));

    return Number.isFinite(n) ? n : 0;
}

function maxQty(index: number): number {
    const q = parseFloat(props.parent.lines[index]?.quantity ?? '0');

    return Number.isFinite(q) ? q : 0;
}

const lineSubtotals = computed(() =>
    props.parent.lines.map((l, i) => {
        const q = Math.min(Math.max(0, lineQty(i)), maxQty(i));
        const unit = parseFloat(l.unit_price_exc_tax);
        const u = Number.isFinite(unit) ? unit : 0;

        return (q * u).toFixed(2);
    }),
);

const sumBeforeDiscount = computed(() => {
    let s = 0;

    for (let i = 0; i < lineSubtotals.value.length; i++) {
        s += parseFloat(lineSubtotals.value[i] ?? '0') || 0;
    }

    return s;
});

const discountValue = computed(() => {
    const amt = parseFloat(String(form.discount_amount)) || 0;

    if (form.discount_type === 'percentage') {
        return (sumBeforeDiscount.value * amt) / 100;
    }

    if (form.discount_type === 'fixed') {
        return Math.min(amt, sumBeforeDiscount.value);
    }

    return 0;
});

const netReturn = computed(() =>
    Math.max(0, sumBeforeDiscount.value - discountValue.value).toFixed(2),
);

function submit() {
    form
        .transform((data) => ({
            ...data,
            transaction_date: fromDatetimeLocal(data.transaction_date),
            invoice_no: data.invoice_no?.trim() ? data.invoice_no : null,
            lines: data.lines.map((row, i) => ({
                sale_line_id: row.sale_line_id,
                quantity: Math.min(Math.max(0, lineQty(i)), maxQty(i)),
            })),
        }))
        .post(salesRoutes.returns.store.url(teamSlug.value));
}
</script>

<template>
    <Head title="Sell return" />

    <div class="mx-auto flex max-w-5xl flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Sell return</h1>
                <p class="text-muted-foreground text-sm">
                    Record quantities returned to stock.
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="salesRoutes.returns.index.url(teamSlug)">
                    Back to list
                </Link>
            </Button>
        </div>

        <section
            class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6"
        >
            <h2 class="mb-3 text-lg font-medium">Parent sale</h2>
            <div class="grid gap-2 text-sm sm:grid-cols-2">
                <div>
                    <span class="text-muted-foreground">Invoice</span>
                    <div class="font-medium">
                        {{ parent.invoice_no?.trim() ? parent.invoice_no : '—' }}
                    </div>
                </div>
                <div>
                    <span class="text-muted-foreground">Date</span>
                    <div class="font-medium">
                        {{
                            parent.transaction_date
                                ? new Date(
                                      parent.transaction_date,
                                  ).toLocaleString()
                                : '—'
                        }}
                    </div>
                </div>
                <div>
                    <span class="text-muted-foreground">Customer</span>
                    <div class="font-medium">
                        {{ parent.customer?.display_name ?? '—' }}
                    </div>
                </div>
                <div>
                    <span class="text-muted-foreground">Location</span>
                    <div class="font-medium">
                        {{ parent.business_location?.name ?? '—' }}
                    </div>
                </div>
            </div>
        </section>

        <form class="space-y-6" @submit.prevent="submit">
            <section
                class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6"
            >
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="ret-inv">Return invoice no.</Label>
                        <Input
                            id="ret-inv"
                            v-model="form.invoice_no"
                            autocomplete="off"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="ret-dt">Date</Label>
                        <Input
                            id="ret-dt"
                            v-model="form.transaction_date"
                            type="datetime-local"
                            required
                        />
                    </div>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="w-full min-w-[640px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b bg-muted/40">
                                <th class="px-2 py-2 text-left">#</th>
                                <th class="px-2 py-2 text-left">Product</th>
                                <th class="px-2 py-2 text-right">Unit price</th>
                                <th class="px-2 py-2 text-right">Sold qty</th>
                                <th class="px-2 py-2 text-right">Return qty</th>
                                <th class="px-2 py-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(line, i) in parent.lines"
                                :key="line.id"
                                class="border-b border-border/70"
                            >
                                <td class="px-2 py-2">{{ i + 1 }}</td>
                                <td class="px-2 py-2">
                                    {{ line.product_name }}
                                    <span
                                        v-if="line.sku"
                                        class="text-muted-foreground text-xs"
                                        >({{ line.sku }})</span
                                    >
                                </td>
                                <td class="px-2 py-2 text-right">
                                    {{ line.unit_price_exc_tax }}
                                </td>
                                <td class="px-2 py-2 text-right">
                                    {{ line.quantity }}
                                </td>
                                <td class="px-2 py-2 text-right">
                                    <Input
                                        v-model="form.lines[i].quantity"
                                        type="text"
                                        inputmode="decimal"
                                        class="ml-auto max-w-28 text-right"
                                        :aria-label="`Return qty ${line.product_name}`"
                                    />
                                </td>
                                <td class="px-2 py-2 text-right">
                                    {{ lineSubtotals[i] }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Discount type</Label>
                        <Select v-model="form.discount_type">
                            <SelectTrigger>
                                <SelectValue placeholder="None" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">None</SelectItem>
                                <SelectItem value="fixed">Fixed</SelectItem>
                                <SelectItem value="percentage"
                                    >Percentage</SelectItem
                                >
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="ret-disc">Discount amount</Label>
                        <Input
                            id="ret-disc"
                            v-model="form.discount_amount"
                            type="text"
                            inputmode="decimal"
                        />
                    </div>
                </div>

                <div class="mt-6 space-y-1 text-right text-sm">
                    <div>
                        <span class="text-muted-foreground">Return discount:</span>
                        (−) {{ discountValue.toFixed(2) }}
                    </div>
                    <div class="text-base font-semibold">
                        Return total:
                        {{ netReturn }}
                    </div>
                </div>

                <p
                    v-if="form.hasErrors"
                    class="text-destructive mt-4 text-sm"
                >
                    {{ Object.values(form.errors).join(' ') }}
                </p>

                <div class="mt-6 flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        as-child
                    >
                        <Link :href="salesRoutes.returns.index.url(teamSlug)">
                            Cancel
                        </Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner
                            v-if="form.processing"
                            class="mr-2 size-4"
                        />
                        {{ form.processing ? 'Saving…' : 'Save' }}
                    </Button>
                </div>
            </section>
        </form>
    </div>
</template>
