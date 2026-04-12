<script setup lang="ts">
import { Form, Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Plus, Trash2 } from 'lucide-vue-next';
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
import CustomerForm from '@/pages/customers/CustomerForm.vue';
import {
    customerFormFields,
    transformCustomerSubmit,
} from '@/pages/customers/customerFormState';
import customerRoutes from '@/routes/customers';
import productRoutes from '@/routes/products';
import salesRoutes from '@/routes/sales';
import salesDraftRoutes from '@/routes/sales/drafts';
import salesQuotationsRoutes from '@/routes/sales/quotations';
import type { Team } from '@/types';

type CustomerRow = {
    id: number;
    display_name: string;
    address_line_1: string | null;
    address_line_2: string | null;
    city: string | null;
    state: string | null;
    zip_code: string | null;
    country: string | null;
    shipping_address: string | null;
};

type LineRow = {
    product_id: number;
    name: string;
    sku: string | null;
    quantity: string;
    unit_price_before_discount: string;
    discount_percent: string;
    product_tax_percent: string;
};

const props = withDefaults(
    defineProps<{
        customers: CustomerRow[];
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
        customerGroups: { id: number; name: string }[];
        isDraftSale?: boolean;
        isQuotationSale?: boolean;
    }>(),
    { isDraftSale: false, isQuotationSale: false },
);

defineOptions({
    layout: (p: {
        currentTeam?: Team | null;
        isDraftSale?: boolean;
        isQuotationSale?: boolean;
    }) => {
        const slug = p.currentTeam!.slug;

        if (p.isDraftSale) {
            return {
                breadcrumbs: [
                    {
                        title: 'All sales',
                        href: salesRoutes.index.url(slug),
                    },
                    {
                        title: 'List draft',
                        href: salesDraftRoutes.index.url(slug),
                    },
                    {
                        title: 'Add draft',
                        href: salesDraftRoutes.create.url(slug),
                    },
                ],
            };
        }

        if (p.isQuotationSale) {
            return {
                breadcrumbs: [
                    {
                        title: 'All sales',
                        href: salesRoutes.index.url(slug),
                    },
                    {
                        title: 'List quotation',
                        href: salesQuotationsRoutes.index.url(slug),
                    },
                    {
                        title: 'Add quotation',
                        href: salesQuotationsRoutes.create.url(slug),
                    },
                ],
            };
        }

        return {
            breadcrumbs: [
                {
                    title: 'Sales',
                    href: salesRoutes.index.url(slug),
                },
                {
                    title: 'Add sale',
                    href: salesRoutes.create.url(slug),
                },
            ],
        };
    },
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

function toLocalInput(d = new Date()): string {
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

const addedCustomers = ref<CustomerRow[]>([]);
const allCustomers = computed(() => [...props.customers, ...addedCustomers.value]);

const NONE = '__none__';

const form = useForm({
    business_location_id: '',
    customer_id: '',
    invoice_no: '',
    transaction_date: toLocalInput(),
    status: props.isDraftSale
        ? 'draft'
        : props.isQuotationSale
          ? 'quotation'
          : 'final',
    pay_term_number: '',
    pay_term_type: NONE,
    discount_type: 'none',
    discount_amount: '0',
    tax_rate_id: NONE,
    shipping_details: '',
    shipping_charges: '0',
    shipping_address: '',
    additional_expenses: [
        { name: '', amount: '0' },
        { name: '', amount: '0' },
        { name: '', amount: '0' },
        { name: '', amount: '0' },
    ],
    sale_note: '',
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
        if (
            !form.payment.method ||
            !methodOptions.value.some((x) => x.value === form.payment.method)
        ) {
            form.payment.method = m;
        }
    },
    { immediate: true },
);

const customerModalOpen = ref(false);
const customerSaving = ref(false);
const customerCreateForm = useForm(customerFormFields());

function openCustomerModal() {
    customerCreateForm.reset();
    customerCreateForm.clearErrors();
    Object.assign(customerCreateForm, customerFormFields());
    customerModalOpen.value = true;
}

async function submitNewCustomer() {
    customerSaving.value = true;

    try {
        const r = await fetch(customerRoutes.quickStore.url(teamSlug.value), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': xsrfToken(),
            },
            body: JSON.stringify(
                transformCustomerSubmit(customerCreateForm.data()),
            ),
        });

        if (!r.ok) {
            const body = (await r.json()) as {
                message?: string;
                errors?: Record<string, string[]>;
            };
            const msg =
                body.message ||
                Object.values(body.errors || {})
                    .flat()
                    .join('\n') ||
                'Could not save customer.';

            alert(msg);

            return;
        }

        const j = (await r.json()) as { customer: CustomerRow };
        addedCustomers.value = [...addedCustomers.value, j.customer];
        form.customer_id = String(j.customer.id);
        customerModalOpen.value = false;
        customerCreateForm.reset();
        Object.assign(customerCreateForm, customerFormFields());
    } finally {
        customerSaving.value = false;
    }
}

const selectedCustomer = computed(() => {
    const id = Number(form.customer_id);

    return allCustomers.value.find((c) => c.id === id) ?? null;
});

const billingAddressText = computed(() => {
    const c = selectedCustomer.value;

    if (!c) {
        return '';
    }

    const parts = [
        c.address_line_1,
        c.address_line_2,
        [c.city, c.state, c.zip_code].filter(Boolean).join(', '),
        c.country,
    ].filter((x) => x && String(x).trim());

    return parts.join('\n');
});

const shippingAddressText = computed(() => {
    const c = selectedCustomer.value;

    if (!c) {
        return '';
    }

    const s = c.shipping_address?.trim();

    if (s) {
        return s;
    }

    return billingAddressText.value;
});

watch(
    () => form.business_location_id,
    () => {
        form.lines = [];
        productSearch.value = '';
        productHits.value = [];
    },
);

const productSearch = ref('');
const productHits = ref<
    {
        id: number;
        name: string;
        sku: string | null;
        text: string;
        default_unit_price: string;
    }[]
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
        headers: { Accept: 'application/json' },
    });
    const j = (await r.json()) as {
        data: {
            id: number;
            name: string;
            sku: string | null;
            text: string;
            default_unit_price: string;
        }[];
    };
    productHits.value = j.data ?? [];
}, 300);

watch(productSearch, () => debouncedProductSearch());

function addLine(p: {
    id: number;
    name: string;
    sku: string | null;
    default_unit_price: string;
}) {
    form.lines.push({
        product_id: p.id,
        name: p.name,
        sku: p.sku,
        quantity: '1',
        unit_price_before_discount: p.default_unit_price || '0',
        discount_percent: '0',
        product_tax_percent: '0',
    });
    productSearch.value = '';
    productHits.value = [];
}

function removeLine(i: number) {
    form.lines.splice(i, 1);
}

function lineTotal(row: LineRow): number {
    const qty = Number(row.quantity) || 0;
    const u0 = Number(row.unit_price_before_discount) || 0;
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

const saleTaxAmount = computed(() => {
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
        afterDiscountTotal.value + saleTaxAmount.value + ship + additionalSum.value
    );
});

watch(
    [grandTotal, () => props.isQuotationSale],
    () => {
        if (props.isQuotationSale) {
            form.payment.amount = '0';
        } else {
            form.payment.amount = grandTotal.value.toFixed(2);
        }
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

function submitSale() {
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
                unit_price_before_discount:
                    Number(row.unit_price_before_discount) || 0,
                discount_percent: Number(row.discount_percent) || 0,
                product_tax_percent: Number(row.product_tax_percent) || 0,
            }));

            const {
                document,
                lines: _l,
                additional_expenses: _a,
                payment: _p,
                ...rest
            } = d;

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

            const payload: Record<string, unknown> = {
                ...rest,
                status: props.isDraftSale
                    ? 'draft'
                    : props.isQuotationSale
                      ? 'quotation'
                      : d.status,
                customer_id: Number(d.customer_id),
                business_location_id: Number(d.business_location_id),
                invoice_no: d.invoice_no?.trim() || null,
                pay_term_number:
                    d.pay_term_number === '' ? null : Number(d.pay_term_number),
                pay_term_type:
                    !d.pay_term_type || d.pay_term_type === NONE
                        ? null
                        : d.pay_term_type,
                discount_amount: Number(d.discount_amount) || 0,
                tax_rate_id:
                    d.tax_rate_id === NONE ? null : Number(d.tax_rate_id),
                shipping_charges: Number(d.shipping_charges) || 0,
                lines: JSON.stringify(lines),
                additional_expenses: JSON.stringify(expenses),
                document,
            };

            if (!props.isQuotationSale) {
                payload.payment = JSON.stringify(pay);
            }

            return payload;
        })
        .post(
            props.isDraftSale
                ? salesDraftRoutes.store.url(teamSlug.value)
                : props.isQuotationSale
                  ? salesQuotationsRoutes.store.url(teamSlug.value)
                  : salesRoutes.store.url(teamSlug.value),
            {
                forceFormData: true,
                preserveScroll: true,
            },
        );
}
</script>

<template>
    <Head
        :title="
            isDraftSale ? 'Add draft' : isQuotationSale ? 'Add quotation' : 'Add sale'
        "
    />

    <div class="mx-auto flex max-w-6xl flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{
                        isDraftSale
                            ? 'Add draft'
                            : isQuotationSale
                              ? 'Add quotation'
                              : 'Add sale'
                    }}
                </h1>
                <p class="text-muted-foreground text-sm">
                    {{
                        isDraftSale
                            ? 'Save as draft without finalizing. Products are optional until you finalize later.'
                            : isQuotationSale
                              ? 'Save as quotation; stock is not reduced until you convert to a final sale later.'
                              : 'Choose a location first; product search only lists items available there.'
                    }}
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link
                    :href="
                        isDraftSale
                            ? salesDraftRoutes.index.url(teamSlug)
                            : isQuotationSale
                              ? salesQuotationsRoutes.index.url(teamSlug)
                              : salesRoutes.index.url(teamSlug)
                    "
                >
                    Back to list
                </Link>
            </Button>
        </div>

        <Form class="flex flex-col gap-6" @submit.prevent="submitSale">
            <section
                class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6"
            >
                <h2 class="mb-4 text-lg font-medium">Sale location &amp; customer</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div class="grid gap-2 md:col-span-2 lg:col-span-3">
                        <Label>Business location *</Label>
                        <Select v-model="form.business_location_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Select location to sell from" />
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
                        <p class="text-muted-foreground text-xs">
                            Required. Products can only be added if they are
                            assigned to this location.
                        </p>
                    </div>
                    <div class="grid gap-2 md:col-span-2 lg:col-span-1">
                        <Label>Customer *</Label>
                        <div class="flex gap-2">
                            <Select v-model="form.customer_id">
                                <SelectTrigger class="flex-1">
                                    <SelectValue placeholder="Select customer" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="c in allCustomers"
                                        :key="c.id"
                                        :value="String(c.id)"
                                    >
                                        {{ c.display_name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                title="Add customer"
                                @click="openCustomerModal"
                            >
                                <Plus class="size-4" />
                            </Button>
                        </div>
                        <div
                            v-if="billingAddressText"
                            class="text-muted-foreground whitespace-pre-line text-xs"
                        >
                            <span class="font-medium text-foreground"
                                >Billing:</span
                            >
                            {{ billingAddressText }}
                        </div>
                        <div
                            v-if="shippingAddressText"
                            class="text-muted-foreground whitespace-pre-line text-xs"
                        >
                            <span class="font-medium text-foreground"
                                >Shipping:</span
                            >
                            {{ shippingAddressText }}
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="inv-no">Invoice no.</Label>
                        <Input
                            id="inv-no"
                            v-model="form.invoice_no"
                            placeholder="Leave blank to auto-generate later"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="sdate">Sale date *</Label>
                        <Input
                            id="sdate"
                            v-model="form.transaction_date"
                            type="datetime-local"
                            required
                        />
                    </div>
                    <div
                        v-if="!isDraftSale && !isQuotationSale"
                        class="grid gap-2"
                    >
                        <Label>Status *</Label>
                        <Select v-model="form.status">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="final">Final</SelectItem>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="quotation">Quotation</SelectItem>
                                <SelectItem value="proforma">Proforma</SelectItem>
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
                    <div class="grid gap-2 md:col-span-2 lg:col-span-3">
                        <Label for="sdoc">Attach document</Label>
                        <Input
                            id="sdoc"
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
                        :disabled="!form.business_location_id"
                        :placeholder="
                            form.business_location_id
                                ? 'Search product name or SKU…'
                                : 'Select a business location first…'
                        "
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
                    <table class="w-full min-w-[800px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="px-2 py-2 text-left">Product</th>
                                <th class="px-2 py-2 text-left">Qty</th>
                                <th class="px-2 py-2 text-left">
                                    Unit price (before disc.)
                                </th>
                                <th class="px-2 py-2 text-left">Disc %</th>
                                <th class="px-2 py-2 text-left">Product tax %</th>
                                <th class="px-2 py-2 text-right">Line total</th>
                                <th class="w-10" />
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, i) in form.lines"
                                :key="`${row.product_id}-${i}`"
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
                                        v-model="row.unit_price_before_discount"
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
                    {{
                        isDraftSale
                            ? 'No products yet — optional for drafts. Add lines when ready.'
                            : 'Add at least one product from the search field above.'
                    }}
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
                        <Label for="sdisc-amt">Discount amount</Label>
                        <Input
                            id="sdisc-amt"
                            v-model="form.discount_amount"
                            inputmode="decimal"
                        />
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                        <Label>Order tax</Label>
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
                        <Label for="snote">Sell note</Label>
                        <textarea
                            id="snote"
                            v-model="form.sale_note"
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
                        <Label for="sship-det">Shipping details</Label>
                        <Input id="sship-det" v-model="form.shipping_details" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="sship-addr">Shipping address</Label>
                        <textarea
                            id="sship-addr"
                            v-model="form.shipping_address"
                            rows="2"
                            class="border-input bg-background min-h-[56px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="sship-ch">Shipping charges</Label>
                        <Input
                            id="sship-ch"
                            v-model="form.shipping_charges"
                            inputmode="decimal"
                        />
                    </div>
                </div>
                <h3 class="mt-6 mb-2 text-sm font-medium">
                    Additional expenses
                </h3>
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
                <div class="mt-6 text-right text-base font-semibold">
                    Sale total:
                    <span class="tabular-nums">{{
                        grandTotal.toFixed(2)
                    }}</span>
                </div>
            </section>

            <section
                v-if="!isQuotationSale"
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
                        <Label for="spay-amt">Amount *</Label>
                        <Input
                            id="spay-amt"
                            v-model="form.payment.amount"
                            inputmode="decimal"
                            required
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="spay-on">Paid on *</Label>
                        <Input
                            id="spay-on"
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
                        <Label for="spay-bank">Bank account no. (reference)</Label>
                        <Input
                            id="spay-bank"
                            v-model="form.payment.bank_account_number"
                        />
                    </div>
                    <div class="grid gap-2 md:col-span-2 lg:col-span-3">
                        <Label for="spay-note">Payment note</Label>
                        <textarea
                            id="spay-note"
                            v-model="form.payment.note"
                            rows="2"
                            class="border-input bg-background min-h-[56px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                        />
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap justify-end gap-2">
                <Button variant="outline" type="button" as-child>
                    <Link
                        :href="
                            isDraftSale
                                ? salesDraftRoutes.index.url(teamSlug)
                                : isQuotationSale
                                  ? salesQuotationsRoutes.index.url(teamSlug)
                                  : salesRoutes.index.url(teamSlug)
                        "
                    >
                        Cancel
                    </Link>
                </Button>
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    {{
                        isDraftSale
                            ? 'Save draft'
                            : isQuotationSale
                              ? 'Save quotation'
                              : 'Save sale'
                    }}
                </Button>
            </div>
        </Form>

        <StandardFormModal
            v-model:open="customerModalOpen"
            title="Add customer"
            description="Creates the customer and selects them on this sale."
            size="2xl"
            :visit-on-dismiss="false"
        >
            <Form class="contents" @submit.prevent="submitNewCustomer">
                <CustomerForm
                    :form="customerCreateForm"
                    :team-members="teamMembers"
                    :customer-groups="customerGroups"
                />
            </Form>
            <template #footer>
                <div class="flex w-full justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="customerModalOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        :disabled="customerSaving"
                        @click="submitNewCustomer"
                    >
                        <Spinner v-if="customerSaving" />
                        Save customer
                    </Button>
                </div>
            </template>
        </StandardFormModal>
    </div>
</template>
