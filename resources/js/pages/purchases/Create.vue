<script setup lang="ts">
import { Form, Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ChevronDown, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import StandardFormModal from '@/components/StandardFormModal.vue';
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
import SupplierForm from '@/pages/suppliers/SupplierForm.vue';
import {
    supplierFormFields,
    transformSupplierSubmit,
} from '@/pages/suppliers/supplierFormState';
import productRoutes from '@/routes/products';
import purchaseRoutes from '@/routes/purchases';
import supplierRoutes from '@/routes/suppliers';
import type { Team } from '@/types';

type SupplierRow = {
    id: number;
    display_name: string;
    address_line_1: string | null;
    address_line_2: string | null;
    city: string | null;
    state: string | null;
    zip_code: string | null;
    country: string | null;
};

type LineRow = {
    product_id: number;
    name: string;
    sku: string | null;
    quantity: string;
    unit_cost_before_discount: string;
    discount_percent: string;
    product_tax_percent: string;
    profit_margin_percent: string;
    unit_selling_price_inc_tax: string;
};

const props = defineProps<{
    suppliers: SupplierRow[];
    businessLocations: { id: number; name: string }[];
    taxRates: { id: number; name: string; amount: string }[];
    paymentAccounts: {
        id: number;
        name: string;
        payment_method: string;
    }[];
    paymentSettings: {
        cash_enabled: boolean;
        bank_transfer_enabled: boolean;
    };
    teamMembers: { id: number; name: string; email: string }[];
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Purchases',
                href: purchaseRoutes.index.url(p.currentTeam!.slug),
            },
            {
                title: 'Add purchase',
                href: purchaseRoutes.create.url(p.currentTeam!.slug),
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

const addedSuppliers = ref<SupplierRow[]>([]);
const allSuppliers = computed(() => [...props.suppliers, ...addedSuppliers.value]);

const NONE = '__none__';

const form = useForm({
    supplier_id: '',
    business_location_id: '',
    ref_no: '',
    transaction_date: toLocalInput(),
    status: 'pending',
    pay_term_number: '',
    pay_term_type: '',
    discount_type: 'none',
    discount_amount: '0',
    tax_rate_id: NONE,
    shipping_details: '',
    shipping_charges: '0',
    additional_expenses: [
        { name: '', amount: '0' },
        { name: '', amount: '0' },
        { name: '', amount: '0' },
        { name: '', amount: '0' },
    ],
    additional_notes: '',
    exchange_rate: '1',
    document: null as File | null,
    lines: [] as LineRow[],
    payment: {
        amount: '0',
        paid_on: toLocalInput(),
        method: 'cash',
        payment_account_id: NONE,
        note: '',
        bank_account_number: '',
    },
});

const methodOptions = computed(() => {
    const o: { value: string; label: string }[] = [];

    if (props.paymentSettings.cash_enabled) {
        o.push({ value: 'cash', label: 'Cash' });
    }

    if (props.paymentSettings.bank_transfer_enabled) {
        o.push({ value: 'bank_transfer', label: 'Bank transfer' });
    }

    return o;
});

const defaultMethod = computed(() => {
    if (props.paymentSettings.cash_enabled) {
        return 'cash';
    }

    if (props.paymentSettings.bank_transfer_enabled) {
        return 'bank_transfer';
    }

    return 'cash';
});

watch(
    defaultMethod,
    (m) => {
        if (!form.payment.method || !methodOptions.value.some((x) => x.value === form.payment.method)) {
            form.payment.method = m;
        }
    },
    { immediate: true },
);

const showAdditionalExpenses = ref(false);

const supplierModalOpen = ref(false);
const supplierSaving = ref(false);
const supplierCreateForm = useForm(supplierFormFields());

function openSupplierModal() {
    supplierCreateForm.reset();
    supplierCreateForm.clearErrors();
    Object.assign(supplierCreateForm, supplierFormFields());
    supplierModalOpen.value = true;
}

async function submitNewSupplier() {
    supplierSaving.value = true;

    try {
        const r = await fetch(supplierRoutes.quickStore.url(teamSlug.value), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': xsrfToken(),
            },
            body: JSON.stringify(transformSupplierSubmit(supplierCreateForm.data())),
        });

        if (!r.ok) {
            const body = (await r.json()) as { message?: string; errors?: Record<string, string[]> };
            const msg =
                body.message ||
                Object.values(body.errors || {})
                    .flat()
                    .join('\n') ||
                'Could not save supplier.';

            alert(msg);

            return;
        }

        const j = (await r.json()) as { supplier: SupplierRow };
        addedSuppliers.value = [...addedSuppliers.value, j.supplier];
        form.supplier_id = String(j.supplier.id);
        supplierModalOpen.value = false;
        supplierCreateForm.reset();
        Object.assign(supplierCreateForm, supplierFormFields());
    } finally {
        supplierSaving.value = false;
    }
}

const selectedSupplier = computed(() => {
    const id = Number(form.supplier_id);

    return allSuppliers.value.find((s) => s.id === id) ?? null;
});

const supplierAddressText = computed(() => {
    const s = selectedSupplier.value;

    if (!s) {
        return '';
    }

    const parts = [
        s.address_line_1,
        s.address_line_2,
        [s.city, s.state, s.zip_code].filter(Boolean).join(', '),
        s.country,
    ].filter((x) => x && String(x).trim());

    return parts.join('\n');
});

const productSearch = ref('');
const productHits = ref<
    { id: number; name: string; sku: string | null; text: string }[]
>([]);

const debouncedProductSearch = useDebounceFn(async () => {
    const t = productSearch.value.trim();

    if (t.length < 1) {
        productHits.value = [];

        return;
    }

    const url = productRoutes.search.url(teamSlug.value, { query: { q: t } });
    const r = await fetch(url, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    });
    const j = (await r.json()) as {
        data: { id: number; name: string; sku: string | null; text: string }[];
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
        unit_cost_before_discount: '0',
        discount_percent: '0',
        product_tax_percent: '0',
        profit_margin_percent: '0',
        unit_selling_price_inc_tax: '0',
    });
    productSearch.value = '';
    productHits.value = [];
}

function removeLine(i: number) {
    form.lines.splice(i, 1);
}

function lineTotal(row: LineRow): number {
    const qty = Number(row.quantity) || 0;
    const u0 = Number(row.unit_cost_before_discount) || 0;
    const dp = Math.min(100, Math.max(0, Number(row.discount_percent) || 0));
    const uExc = u0 * (1 - dp / 100);
    const pt = Math.max(0, Number(row.product_tax_percent) || 0);
    const subExc = qty * uExc;
    const tax = subExc * (pt / 100);

    return subExc + tax;
}

const linesSum = computed(() =>
    form.lines.reduce((s, row) => s + lineTotal(row), 0),
);

function afterHeaderDiscount(sum: number): number {
    const t = form.discount_type;
    const amt = Number(form.discount_amount) || 0;

    if (t === 'fixed') {
        return Math.max(0, sum - amt);
    }

    if (t === 'percentage') {
        const pct = Math.min(100, Math.max(0, amt));

        return sum * (1 - pct / 100);
    }

    return sum;
}

const afterDiscountTotal = computed(() =>
    afterHeaderDiscount(linesSum.value),
);

const purchaseTaxAmount = computed(() => {
    const id = form.tax_rate_id;

    if (!id || id === NONE) {
        return 0;
    }

    const rate = props.taxRates.find((r) => String(r.id) === String(id));

    if (!rate) {
        return 0;
    }

    const pct = Number(rate.amount) || 0;
    const base = afterDiscountTotal.value;

    return base * (pct / 100);
});

const additionalSum = computed(() => {
    let s = 0;

    for (const row of form.additional_expenses) {
        if (!row.name.trim()) {
            continue;
        }

        s += Number(row.amount) || 0;
    }

    return s;
});

const grandTotal = computed(() => {
    const ship = Number(form.shipping_charges) || 0;

    return (
        afterDiscountTotal.value + purchaseTaxAmount.value + ship + additionalSum.value
    );
});

watch(
    grandTotal,
    (t) => {
        form.payment.amount = t.toFixed(2);
    },
    { immediate: true },
);

watch(
    () => form.payment.method,
    () => {
        form.payment.payment_account_id = '';
    },
);

const accountsForMethod = computed(() =>
    props.paymentAccounts.filter(
        (a) => a.payment_method === form.payment.method,
    ),
);

function submitPurchase() {
    form
        .transform((d) => {
            const expenses = d.additional_expenses
                .filter((e) => e.name.trim())
                .map((e) => ({
                    name: e.name.trim(),
                    amount: Number(e.amount) || 0,
                }));

            const lines = d.lines.map((row) => ({
                product_id: row.product_id,
                quantity: Number(row.quantity) || 0,
                unit_cost_before_discount: Number(row.unit_cost_before_discount) || 0,
                discount_percent: Number(row.discount_percent) || 0,
                product_tax_percent: Number(row.product_tax_percent) || 0,
                profit_margin_percent:
                    row.profit_margin_percent === ''
                        ? null
                        : Number(row.profit_margin_percent),
                unit_selling_price_inc_tax:
                    row.unit_selling_price_inc_tax === ''
                        ? null
                        : Number(row.unit_selling_price_inc_tax),
            }));

            const pay = {
                amount: Number(d.payment.amount) || 0,
                paid_on: d.payment.paid_on,
                method: d.payment.method,
                payment_account_id:
                    d.payment.payment_account_id === NONE
                        ? null
                        : Number(d.payment.payment_account_id),
                note: d.payment.note?.trim() || null,
                bank_account_number:
                    d.payment.bank_account_number?.trim() || null,
            };

            const { document, lines: _l, additional_expenses: _a, payment: _p, ...rest } = d;

            return {
                ...rest,
                supplier_id: Number(d.supplier_id),
                business_location_id: Number(d.business_location_id),
                ref_no: d.ref_no?.trim() || null,
                pay_term_number:
                    d.pay_term_number === '' ? null : Number(d.pay_term_number),
                pay_term_type: d.pay_term_type || null,
                discount_amount: Number(d.discount_amount) || 0,
                tax_rate_id:
                    d.tax_rate_id === NONE ? null : Number(d.tax_rate_id),
                shipping_charges: Number(d.shipping_charges) || 0,
                exchange_rate: Number(d.exchange_rate) || 1,
                lines: JSON.stringify(lines),
                additional_expenses: JSON.stringify(expenses),
                payment: JSON.stringify(pay),
                document,
            };
        })
        .post(purchaseRoutes.store.url(teamSlug.value), {
            forceFormData: true,
            preserveScroll: true,
        });
}
</script>

<template>
    <Head title="Add purchase" />

    <div class="mx-auto flex max-w-6xl flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Add purchase
                </h1>
                <p class="text-muted-foreground text-sm">
                    Record stock from a supplier, discounts, tax, and payment.
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="purchaseRoutes.index.url(teamSlug)">
                    Back to list
                </Link>
            </Button>
        </div>

        <Form class="flex flex-col gap-6" @submit.prevent="submitPurchase">
            <section
                class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6"
            >
                <h2 class="mb-4 text-lg font-medium">Purchase details</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div class="grid gap-2 md:col-span-2 lg:col-span-1">
                        <Label>Supplier *</Label>
                        <div class="flex gap-2">
                            <Select v-model="form.supplier_id">
                                <SelectTrigger class="flex-1">
                                    <SelectValue placeholder="Select supplier" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="s in allSuppliers"
                                        :key="s.id"
                                        :value="String(s.id)"
                                    >
                                        {{ s.display_name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                title="Add supplier"
                                @click="openSupplierModal"
                            >
                                <Plus class="size-4" />
                            </Button>
                        </div>
                        <div
                            v-if="supplierAddressText"
                            class="text-muted-foreground whitespace-pre-line text-xs"
                        >
                            {{ supplierAddressText }}
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="pref">Reference no.</Label>
                        <Input
                            id="pref"
                            v-model="form.ref_no"
                            placeholder="Leave empty to track internally"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="pdate">Purchase date *</Label>
                        <Input
                            id="pdate"
                            v-model="form.transaction_date"
                            type="datetime-local"
                            required
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label>Status *</Label>
                        <Select v-model="form.status">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="received">Received</SelectItem>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="ordered">Ordered</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label>Business location *</Label>
                        <Select v-model="form.business_location_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Select" />
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
                    <div class="grid gap-2 md:col-span-2">
                        <Label>Pay term</Label>
                        <div class="flex flex-wrap gap-2">
                            <Input
                                v-model="form.pay_term_number"
                                type="number"
                                min="0"
                                placeholder="Number"
                                class="w-28"
                            />
                            <Select v-model="form.pay_term_type">
                                <SelectTrigger class="w-40">
                                    <SelectValue placeholder="Period" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="NONE">—</SelectItem>
                                    <SelectItem value="days">Days</SelectItem>
                                    <SelectItem value="months">Months</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                        <Label for="pdoc">Attach document</Label>
                        <Input
                            id="pdoc"
                            type="file"
                            accept=".pdf,.csv,.zip,.doc,.docx,.jpeg,.jpg,.png"
                            @change="
                                (e) => {
                                    const t = e.target as HTMLInputElement;
                                    form.document = t.files?.[0] ?? null;
                                }
                            "
                        />
                        <p class="text-muted-foreground text-xs">
                            Max 5 MB. PDF, CSV, ZIP, Word, or images.
                        </p>
                    </div>
                </div>
            </section>

            <section
                class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6"
            >
                <h2 class="mb-4 text-lg font-medium">Products</h2>
                <div class="relative mb-4">
                    <Input
                        v-model="productSearch"
                        placeholder="Search product name or SKU…"
                        autocomplete="off"
                    />
                    <div
                        v-if="productHits.length"
                        class="bg-popover absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-md border border-border shadow-md"
                    >
                        <button
                            v-for="h in productHits"
                            :key="h.id"
                            type="button"
                            class="hover:bg-muted block w-full px-3 py-2 text-left text-sm"
                            @click="addLine(h)"
                        >
                            {{ h.text }}
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="px-2 py-2 text-left">Product</th>
                                <th class="px-2 py-2 text-left">Qty</th>
                                <th class="px-2 py-2 text-left">Unit cost (before disc.)</th>
                                <th class="px-2 py-2 text-left">Disc %</th>
                                <th class="px-2 py-2 text-left">Product tax %</th>
                                <th class="px-2 py-2 text-left">Margin %</th>
                                <th class="px-2 py-2 text-left">Sell (inc tax)</th>
                                <th class="px-2 py-2 text-right">Line total</th>
                                <th class="w-10" />
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, i) in form.lines"
                                :key="row.product_id"
                                class="border-b border-border/80"
                            >
                                <td class="px-2 py-2">
                                    <div class="font-medium">{{ row.name }}</div>
                                    <div class="text-muted-foreground text-xs">
                                        {{ row.sku || '—' }}
                                    </div>
                                </td>
                                <td class="px-2 py-2">
                                    <Input
                                        v-model="row.quantity"
                                        class="h-8 w-20"
                                        inputmode="decimal"
                                    />
                                </td>
                                <td class="px-2 py-2">
                                    <Input
                                        v-model="row.unit_cost_before_discount"
                                        class="h-8 w-24"
                                        inputmode="decimal"
                                    />
                                </td>
                                <td class="px-2 py-2">
                                    <Input
                                        v-model="row.discount_percent"
                                        class="h-8 w-16"
                                        inputmode="decimal"
                                    />
                                </td>
                                <td class="px-2 py-2">
                                    <Input
                                        v-model="row.product_tax_percent"
                                        class="h-8 w-16"
                                        inputmode="decimal"
                                    />
                                </td>
                                <td class="px-2 py-2">
                                    <Input
                                        v-model="row.profit_margin_percent"
                                        class="h-8 w-16"
                                        inputmode="decimal"
                                    />
                                </td>
                                <td class="px-2 py-2">
                                    <Input
                                        v-model="row.unit_selling_price_inc_tax"
                                        class="h-8 w-24"
                                        inputmode="decimal"
                                    />
                                </td>
                                <td class="px-2 py-2 text-right tabular-nums">
                                    {{ lineTotal(row).toFixed(2) }}
                                </td>
                                <td class="px-2 py-2">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        @click="removeLine(i)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p
                    v-if="!form.lines.length"
                    class="text-muted-foreground py-4 text-center text-sm"
                >
                    Add at least one product from the search field above.
                </p>
                <div class="mt-4 flex justify-end text-sm">
                    <span class="text-muted-foreground">Lines total:&nbsp;</span>
                    <span class="font-medium tabular-nums">{{
                        linesSum.toFixed(2)
                    }}</span>
                </div>
            </section>

            <section
                class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6"
            >
                <h2 class="mb-4 text-lg font-medium">Discounts, tax &amp; notes</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div class="grid gap-2">
                        <Label>Discount type</Label>
                        <Select v-model="form.discount_type">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">None</SelectItem>
                                <SelectItem value="fixed">Fixed</SelectItem>
                                <SelectItem value="percentage">Percentage</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="disc-amt">Discount amount</Label>
                        <Input
                            id="disc-amt"
                            v-model="form.discount_amount"
                            inputmode="decimal"
                        />
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                        <Label>Purchase tax</Label>
                        <Select v-model="form.tax_rate_id">
                            <SelectTrigger>
                                <SelectValue placeholder="None" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">None</SelectItem>
                                <SelectItem
                                    v-for="tr in taxRates"
                                    :key="tr.id"
                                    :value="String(tr.id)"
                                >
                                    {{ tr.name }} ({{ tr.amount }}%)
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2 md:col-span-2 lg:col-span-4">
                        <Label for="notes">Additional notes</Label>
                        <textarea
                            id="notes"
                            v-model="form.additional_notes"
                            rows="3"
                            class="border-input bg-background min-h-[72px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                        />
                    </div>
                </div>
            </section>

            <section
                class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6"
            >
                <h2 class="mb-4 text-lg font-medium">Shipping &amp; other costs</h2>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="ship-det">Shipping details</Label>
                        <Input id="ship-det" v-model="form.shipping_details" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="ship-ch">Shipping charges</Label>
                        <Input
                            id="ship-ch"
                            v-model="form.shipping_charges"
                            inputmode="decimal"
                        />
                    </div>
                </div>
                <div class="mt-6 flex justify-center">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="gap-1.5"
                        @click="showAdditionalExpenses = !showAdditionalExpenses"
                    >
                        <ChevronDown
                            class="size-4 shrink-0 transition-transform duration-200"
                            :class="
                                showAdditionalExpenses ? 'rotate-180' : ''
                            "
                        />
                        {{
                            showAdditionalExpenses
                                ? 'Hide additional expenses'
                                : 'Add additional expenses'
                        }}
                    </Button>
                </div>
                <div v-show="showAdditionalExpenses" class="mt-4 space-y-3">
                    <h3 class="text-sm font-medium">Additional expenses</h3>
                    <div class="grid gap-2">
                        <div
                            v-for="(ex, i) in form.additional_expenses"
                            :key="i"
                            class="flex flex-wrap gap-2"
                        >
                            <Input
                                v-model="ex.name"
                                placeholder="Name"
                                class="min-w-[140px] flex-1"
                            />
                            <Input
                                v-model="ex.amount"
                                class="w-32"
                                inputmode="decimal"
                            />
                        </div>
                    </div>
                </div>
                <div class="mt-6 text-right text-base font-semibold">
                    Purchase total:
                    <span class="tabular-nums">{{
                        grandTotal.toFixed(2)
                    }}</span>
                </div>
            </section>

            <section
                class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6"
            >
                <h2 class="mb-4 text-lg font-medium">Payment</h2>
                <div
                    v-if="methodOptions.length === 0"
                    class="text-destructive text-sm"
                >
                    No payment methods are enabled. Turn on cash or bank transfer
                    under Settings → Payment accounts &amp; methods.
                </div>
                <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="pay-amt">Amount *</Label>
                        <Input
                            id="pay-amt"
                            v-model="form.payment.amount"
                            inputmode="decimal"
                            required
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="pay-on">Paid on *</Label>
                        <Input
                            id="pay-on"
                            v-model="form.payment.paid_on"
                            type="datetime-local"
                            required
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label>Method *</Label>
                        <Select v-model="form.payment.method">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="m in methodOptions"
                                    :key="m.value"
                                    :value="m.value"
                                >
                                    {{ m.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label>Payment account</Label>
                        <Select v-model="form.payment.payment_account_id">
                            <SelectTrigger>
                                <SelectValue placeholder="None" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">None</SelectItem>
                                <SelectItem
                                    v-for="a in accountsForMethod"
                                    :key="a.id"
                                    :value="String(a.id)"
                                >
                                    {{ a.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div
                        v-if="form.payment.method === 'bank_transfer'"
                        class="grid gap-2 md:col-span-2"
                    >
                        <Label for="pay-bank">Bank account no. (reference)</Label>
                        <Input
                            id="pay-bank"
                            v-model="form.payment.bank_account_number"
                        />
                    </div>
                    <div class="grid gap-2 md:col-span-2 lg:col-span-3">
                        <Label for="pay-note">Payment note</Label>
                        <textarea
                            id="pay-note"
                            v-model="form.payment.note"
                            rows="2"
                            class="border-input bg-background min-h-[56px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                        />
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap justify-end gap-2">
                <Button variant="outline" type="button" as-child>
                    <Link :href="purchaseRoutes.index.url(teamSlug)">
                        Cancel
                    </Link>
                </Button>
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    Save purchase
                </Button>
            </div>
        </Form>

        <StandardFormModal
            v-model:open="supplierModalOpen"
            title="Add supplier"
            description="Creates the supplier and selects them on this purchase."
            size="full"
            :visit-on-dismiss="false"
        >
            <Form class="contents" @submit.prevent="submitNewSupplier">
                <SupplierForm
                    :form="supplierCreateForm"
                    :team-members="teamMembers"
                />
            </Form>
            <template #footer>
                <div class="flex w-full justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="supplierModalOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        :disabled="supplierSaving"
                        @click="submitNewSupplier"
                    >
                        <Spinner v-if="supplierSaving" />
                        Save supplier
                    </Button>
                </div>
            </template>
        </StandardFormModal>
    </div>
</template>
