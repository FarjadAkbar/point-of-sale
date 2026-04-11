<script setup lang="ts">
import { Form, Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    ArrowRight,
    CreditCard,
    Minus,
    Package,
    Plus,
    ScanLine,
    Search,
    ShoppingCart,
    Trash2,
    Wallet,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import StandardFormModal from '@/components/StandardFormModal.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import posRoutes from '@/routes/pos';
import productRoutes from '@/routes/products';
import salesDraftRoutes from '@/routes/sales/drafts';
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
    image_url: string | null;
    quantity: string;
    unit_price_before_discount: string;
    discount_percent: string;
    product_tax_percent: string;
};

const props = defineProps<{
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
    productCategories?: { id: number; name: string }[];
}>();

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

function toLocalInput(d = new Date()): string {
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

const NONE = '__none__';

const form = useForm({
    business_location_id: '',
    customer_id: '',
    invoice_no: '',
    transaction_date: toLocalInput(),
    status: 'final' as 'final' | 'quotation',
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

const addedCustomers = ref<CustomerRow[]>([]);
const allCustomers = computed(() => [...props.customers, ...addedCustomers.value]);

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

const selectedLocation = computed(() =>
    props.businessLocations.find(
        (l) => String(l.id) === String(form.business_location_id),
    ),
);

type ProductHit = {
    id: number;
    name: string;
    sku: string | null;
    text: string;
    default_unit_price: string;
    category_id: number | null;
    category_name: string | null;
    image_url: string | null;
    manage_stock: boolean;
    stock_quantity: string | null;
};

const productSearch = ref('');
/** Full browse list for the catalog (empty search on the server). */
const browseProducts = ref<ProductHit[]>([]);
/** Suggestions under the search field only while the user is typing. */
const searchDropdownHits = ref<ProductHit[]>([]);
let productSearchRequestId = 0;

const selectedCategoryId = ref<number | null>(null);
/** Optional — for display only until sales supports it on the server. */
const salesRepUserId = ref(
    String((page.props.auth as { user?: { id: number } } | undefined)?.user?.id ?? ''),
);
const taxRateBeforeExempt = ref<string>(NONE);

const selectedTaxRate = computed(() =>
    props.taxRates.find((r) => String(r.id) === String(form.tax_rate_id)),
);

const POS_SCAN_INPUT_ID = 'pos-product-scan';

function focusScanInput() {
    nextTick(() => {
        document.getElementById(POS_SCAN_INPUT_ID)?.focus();
    });
}

function normalizeProductHit(raw: Record<string, unknown>): ProductHit {
    return {
        id: Number(raw.id),
        name: String(raw.name ?? ''),
        sku: raw.sku != null ? String(raw.sku) : null,
        text: String(raw.text ?? raw.name ?? ''),
        default_unit_price: String(raw.default_unit_price ?? '0'),
        category_id:
            raw.category_id != null && raw.category_id !== ''
                ? Number(raw.category_id)
                : null,
        category_name:
            raw.category_name != null ? String(raw.category_name) : null,
        image_url: raw.image_url != null ? String(raw.image_url) : null,
        manage_stock: Boolean(raw.manage_stock),
        stock_quantity:
            raw.stock_quantity != null ? String(raw.stock_quantity) : null,
    };
}

async function fetchProducts(
    q: string,
    categoryId: number | null = null,
): Promise<ProductHit[]> {
    if (!form.business_location_id) {
        return [];
    }

    const query: Record<string, string> = {
        q,
        business_location_id: form.business_location_id,
        active_only: '1',
    };
    if (categoryId != null && categoryId > 0) {
        query.category_id = String(categoryId);
    }

    const url = productRoutes.search.url(teamSlug.value, {
        query,
    });
    const r = await fetch(url, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    });
    const j = (await r.json()) as { data: Record<string, unknown>[] };

    return (j.data ?? []).map((row) => normalizeProductHit(row));
}

watch(
    () => form.business_location_id,
    async () => {
        form.lines = [];
        productSearch.value = '';
        searchDropdownHits.value = [];
        browseProducts.value = [];
        selectedCategoryId.value = null;
        if (form.business_location_id) {
            browseProducts.value = await fetchProducts('', null);
        }
    },
);

watch(selectedCategoryId, async () => {
    if (!form.business_location_id) {
        return;
    }
    productSearch.value = '';
    searchDropdownHits.value = [];
    browseProducts.value = await fetchProducts(
        '',
        selectedCategoryId.value,
    );
});

const catalogGridProducts = computed(() => {
    const q = productSearch.value.trim().toLowerCase();
    if (!q.length) {
        return browseProducts.value;
    }

    return browseProducts.value.filter((p) => {
        const name = p.name.toLowerCase();
        const sku = (p.sku || '').toLowerCase();

        return name.includes(q) || sku.includes(q);
    });
});

function formatMoney(n: number): string {
    return n.toFixed(2);
}

function stockBadge(p: ProductHit): { text: string; variant: 'ok' | 'bad' | 'na' } {
    if (!p.manage_stock) {
        return { text: '—', variant: 'na' };
    }
    const q = Number(p.stock_quantity) || 0;
    if (q > 0) {
        return { text: `${formatMoney(q)} in stock`, variant: 'ok' };
    }

    return { text: 'Out of stock', variant: 'bad' };
}

const debouncedProductSearch = useDebounceFn(async () => {
    const q = productSearch.value.trim();
    if (q.length < 1) {
        searchDropdownHits.value = [];

        return;
    }
    const req = ++productSearchRequestId;
    const hits = await fetchProducts(q, selectedCategoryId.value);
    if (req !== productSearchRequestId) {
        return;
    }
    searchDropdownHits.value = hits;
    if (q.length >= 3 && hits.length === 1) {
        addLine(hits[0]);
    }
}, 300);

watch(productSearch, () => {
    if (!productSearch.value.trim()) {
        searchDropdownHits.value = [];

        return;
    }
    debouncedProductSearch();
});

async function onProductSearchEnter() {
    const q = productSearch.value.trim();
    if (q.length < 1) {
        return;
    }
    const hits = await fetchProducts(q, selectedCategoryId.value);
    searchDropdownHits.value = hits;
    if (hits.length >= 1) {
        addLine(hits[0]);
    }
}

async function addLine(p: ProductHit) {
    form.lines.push({
        product_id: p.id,
        name: p.name,
        sku: p.sku,
        image_url: p.image_url ?? null,
        quantity: '1',
        unit_price_before_discount: p.default_unit_price || '0',
        discount_percent: '0',
        product_tax_percent: '0',
    });
    productSearch.value = '';
    searchDropdownHits.value = [];
    if (form.business_location_id) {
        browseProducts.value = await fetchProducts(
            '',
            selectedCategoryId.value,
        );
    } else {
        browseProducts.value = [];
    }
    focusScanInput();
}

function removeLine(i: number) {
    form.lines.splice(i, 1);
}

function bumpLineQty(i: number, delta: number) {
    const row = form.lines[i];
    if (!row) {
        return;
    }
    const q = Number(row.quantity) || 0;
    const next = q + delta;
    if (next < 1) {
        removeLine(i);

        return;
    }
    row.quantity = String(next);
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

const headerDiscountAmount = computed(() => {
    if (form.discount_type === 'none') {
        return 0;
    }

    return Math.max(0, linesSum.value - afterDiscountTotal.value);
});

const headerDiscountLabel = computed(() => {
    if (form.discount_type === 'none') {
        return '';
    }
    if (form.discount_type === 'percentage') {
        return `Discount (${Number(form.discount_amount) || 0}%)`;
    }

    return 'Discount (fixed)';
});

const selectedProductCount = computed(() => form.lines.length);

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

const canSubmitDraft = computed(
    () => Boolean(form.customer_id && form.business_location_id),
);

const canSubmitSale = computed(
    () =>
        Boolean(
            form.customer_id &&
                form.business_location_id &&
                form.lines.length > 0,
        ),
);

onMounted(() => {
    if (
        props.businessLocations.length === 1 &&
        !form.business_location_id
    ) {
        form.business_location_id = String(props.businessLocations[0].id);
    }

    focusScanInput();
});

const payOpen = ref(false);

watch(payOpen, (open) => {
    if (!open) {
        focusScanInput();
    }
});

watch(customerModalOpen, (open) => {
    if (!open) {
        focusScanInput();
    }
});

function transformPayload(
    d: ReturnType<typeof form.data>,
    status: 'final' | 'quotation' | 'draft',
) {
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

    const {
        document,
        lines: _l,
        additional_expenses: _a,
        payment: _p,
        ...rest
    } = d;

    return {
        ...rest,
        status,
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
        payment: JSON.stringify(pay),
        document,
    };
}

function postCheckout(status: 'final' | 'quotation') {
    form
        .transform((d) => transformPayload(d, status))
        .post(posRoutes.checkout.url(teamSlug.value), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                payOpen.value = false;
            },
        });
}

function submitDraft() {
    if (!canSubmitDraft.value) {
        return;
    }

    form
        .transform((d) => transformPayload(d, 'draft'))
        .post(salesDraftRoutes.store.url(teamSlug.value), {
            forceFormData: true,
            preserveScroll: true,
        });
}

function openPayDialog() {
    if (!canSubmitSale.value) {
        return;
    }
    form.payment.amount = grandTotal.value.toFixed(2);
    payOpen.value = true;
}

function quickCash() {
    if (!canSubmitSale.value) {
        return;
    }
    if (!props.paymentSettings.cash_enabled) {
        alert('Cash payments are disabled for this business.');

        return;
    }
    form.payment.method = 'cash';
    form.payment.amount = grandTotal.value.toFixed(2);
    postCheckout('final');
}

function saveQuotation() {
    if (!canSubmitSale.value) {
        return;
    }
    form.payment.amount = grandTotal.value.toFixed(2);
    postCheckout('quotation');
}

async function clearCart() {
    if (form.lines.length === 0 && !form.sale_note?.trim()) {
        return;
    }
    if (!confirm('Clear the current cart?')) {
        return;
    }
    form.lines = [];
    form.sale_note = '';
    form.invoice_no = '';
    productSearch.value = '';
    searchDropdownHits.value = [];
    if (form.business_location_id) {
        browseProducts.value = await fetchProducts(
            '',
            selectedCategoryId.value,
        );
    } else {
        browseProducts.value = [];
    }
    focusScanInput();
}

watch(
    () => form.tax_rate_id,
    (id) => {
        if (id && id !== NONE) {
            taxRateBeforeExempt.value = String(id);
        }
    },
);

function toggleTaxExempt() {
    if (form.tax_rate_id === NONE || form.tax_rate_id === '') {
        const restore =
            taxRateBeforeExempt.value !== NONE && taxRateBeforeExempt.value !== ''
                ? taxRateBeforeExempt.value
                : props.taxRates[0]
                  ? String(props.taxRates[0].id)
                  : NONE;
        form.tax_rate_id = restore;
    } else {
        taxRateBeforeExempt.value = String(form.tax_rate_id);
        form.tax_rate_id = NONE;
    }
}

const taxExemptActive = computed(
    () => form.tax_rate_id === NONE || form.tax_rate_id === '',
);

function productCanAdd(p: ProductHit): boolean {
    if (!p.manage_stock) {
        return true;
    }

    return (Number(p.stock_quantity) || 0) > 0;
}

function tryAddProduct(p: ProductHit) {
    if (!productCanAdd(p)) {
        alert('This product is out of stock at this location.');

        return;
    }
    addLine(p);
}
</script>

<template>
    <Head title="POS" />

    <div
        class="flex min-h-0 flex-1 flex-col gap-0 overflow-hidden bg-emerald-50/40 dark:bg-background md:gap-2"
    >
        <header
            class="bg-card border-border shrink-0 border-b px-3 py-2 shadow-sm md:px-4 md:py-2.5"
        >
            <div
                class="mx-auto grid w-full max-w-[1920px] gap-3 sm:grid-cols-2 lg:grid-cols-4"
            >
                <div class="grid gap-1.5">
                    <Label class="text-xs font-medium text-muted-foreground"
                        >Business location *</Label
                    >
                    <Select v-model="form.business_location_id">
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
                <div class="grid gap-1.5">
                    <Label class="text-xs font-medium text-muted-foreground"
                        >Customer *</Label
                    >
                    <div class="flex gap-2">
                        <Select v-model="form.customer_id" class="min-w-0 flex-1">
                            <SelectTrigger>
                                <SelectValue placeholder="Customer" />
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
                </div>
                <div class="grid gap-1.5">
                    <Label
                        class="text-xs font-medium text-muted-foreground"
                        for="pos-when"
                        >Sale date *</Label
                    >
                    <Input
                        id="pos-when"
                        v-model="form.transaction_date"
                        type="datetime-local"
                        required
                    />
                </div>
                <div class="grid gap-1.5">
                    <Label class="text-xs font-medium text-muted-foreground"
                        >Staff (display)</Label
                    >
                    <Select v-model="salesRepUserId">
                        <SelectTrigger>
                            <SelectValue placeholder="Sales rep" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="m in teamMembers"
                                :key="m.id"
                                :value="String(m.id)"
                            >
                                {{ m.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
        </header>

        <div
            class="mx-auto flex min-h-0 w-full max-w-[1920px] flex-1 flex-col gap-3 overflow-hidden p-2 md:flex-row md:gap-4 md:p-3"
        >
            <main
                id="pos-product-catalog"
                class="border-border bg-card flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden rounded-xl border shadow-sm"
            >
                <div class="border-border shrink-0 space-y-3 border-b p-3 md:p-4">
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            :class="[
                                'rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-wide transition-colors',
                                selectedCategoryId === null
                                    ? 'border-emerald-600 bg-emerald-600 text-white'
                                    : 'border-border bg-background text-muted-foreground hover:border-emerald-500/50',
                            ]"
                            @click="selectedCategoryId = null"
                        >
                            All categories
                        </button>
                        <button
                            v-for="cat in productCategories ?? []"
                            :key="cat.id"
                            type="button"
                            :class="[
                                'rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-wide transition-colors',
                                selectedCategoryId === cat.id
                                    ? 'border-emerald-600 bg-emerald-600 text-white'
                                    : 'border-border bg-background text-muted-foreground hover:border-emerald-500/50',
                            ]"
                            @click="selectedCategoryId = cat.id"
                        >
                            {{ cat.name }}
                        </button>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-stretch">
                        <div class="relative min-w-0 flex-1">
                            <ScanLine
                                class="text-muted-foreground pointer-events-none absolute left-3 top-1/2 z-10 size-5 -translate-y-1/2"
                            />
                            <Input
                                :id="POS_SCAN_INPUT_ID"
                                v-model="productSearch"
                                :disabled="!form.business_location_id"
                                :placeholder="
                                    form.business_location_id
                                        ? 'Scan barcode or type SKU / product name…'
                                        : 'Select location first…'
                                "
                                autocomplete="off"
                                class="h-11 pl-10"
                                @keydown.enter.prevent="onProductSearchEnter"
                            />
                            <div
                                v-if="
                                    productSearch.trim().length > 0 &&
                                    searchDropdownHits.length
                                "
                                class="bg-popover absolute z-30 mt-1 max-h-52 w-full overflow-auto rounded-md border border-border shadow-lg"
                            >
                                <button
                                    v-for="h in searchDropdownHits"
                                    :key="h.id"
                                    type="button"
                                    class="hover:bg-muted block w-full px-3 py-2 text-left text-sm"
                                    @click="tryAddProduct(h)"
                                >
                                    {{ h.text }}
                                </button>
                            </div>
                        </div>
                        <Button
                            type="button"
                            class="h-11 shrink-0 bg-emerald-600 px-6 text-white hover:bg-emerald-700"
                            :disabled="!form.business_location_id"
                            @click="focusScanInput"
                        >
                            <Search class="mr-1.5 size-4" />
                            Search
                        </Button>
                    </div>
                </div>
                <div
                    class="min-h-0 flex-1 overflow-y-auto p-3 md:p-4"
                >
                    <div
                        v-if="form.business_location_id"
                        class="grid grid-cols-2 gap-3 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4"
                    >
                        <button
                            v-for="h in catalogGridProducts"
                            :key="`tile-${h.id}`"
                            type="button"
                            :disabled="!productCanAdd(h)"
                            class="border-border hover:border-emerald-500/60 flex flex-col overflow-hidden rounded-xl border bg-background text-left shadow-sm transition-all hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                            @click="tryAddProduct(h)"
                        >
                            <div
                                class="bg-muted relative aspect-square w-full overflow-hidden"
                            >
                                <img
                                    v-if="h.image_url"
                                    :src="h.image_url"
                                    :alt="h.name"
                                    class="size-full object-cover"
                                />
                                <div
                                    v-else
                                    class="text-muted-foreground flex size-full items-center justify-center"
                                >
                                    <Package class="size-10 opacity-40" />
                                </div>
                            </div>
                            <div class="flex flex-1 flex-col gap-1 p-2.5">
                                <p
                                    class="text-muted-foreground font-mono text-[10px] uppercase"
                                >
                                    SKU:
                                    {{ h.sku || '—' }}
                                </p>
                                <p
                                    class="line-clamp-2 text-sm font-semibold leading-tight"
                                >
                                    {{ h.name }}
                                </p>
                                <p
                                    class="text-primary text-base font-bold tabular-nums"
                                >
                                    {{ h.default_unit_price }}
                                </p>
                                <span
                                    class="mt-auto inline-flex w-fit rounded px-2 py-0.5 text-[10px] font-bold uppercase"
                                    :class="{
                                        'bg-emerald-600/15 text-emerald-800 dark:text-emerald-300':
                                            stockBadge(h).variant === 'ok',
                                        'bg-red-600/15 text-red-700 dark:text-red-300':
                                            stockBadge(h).variant === 'bad',
                                        'bg-muted text-muted-foreground':
                                            stockBadge(h).variant === 'na',
                                    }"
                                >
                                    {{ stockBadge(h).text }}
                                </span>
                            </div>
                        </button>
                    </div>
                    <p
                        v-if="
                            !catalogGridProducts.length &&
                            form.business_location_id
                        "
                        class="text-muted-foreground py-16 text-center text-sm"
                    >
                        No products match this filter.
                    </p>
                    <p
                        v-if="!form.business_location_id"
                        class="text-muted-foreground py-16 text-center text-sm"
                    >
                        Choose a location to load the catalog.
                    </p>
                </div>
            </main>
            <aside
                class="border-border flex w-full shrink-0 flex-col overflow-hidden rounded-xl border border-emerald-200/80 bg-emerald-50/90 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-950/35 md:w-[min(100%,420px)] lg:w-[420px]"
            >
                <div
                    class="border-border flex items-center justify-between gap-2 border-b bg-emerald-100/80 px-3 py-3 dark:bg-emerald-950/50 md:px-4"
                >
                    <div class="flex items-center gap-2">
                        <div
                            class="bg-emerald-600 text-white flex size-9 items-center justify-center rounded-lg"
                        >
                            <ShoppingCart class="size-5" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold tracking-tight">
                                Current order
                            </h2>
                            <p class="text-muted-foreground text-xs">
                                {{ selectedLocation?.name ?? 'No location' }}
                            </p>
                        </div>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="border-red-300 text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-950/40"
                        :disabled="form.lines.length === 0"
                        @click="clearCart"
                    >
                        Clear
                    </Button>
                </div>
                <div class="min-h-0 flex-1 space-y-2 overflow-y-auto p-3 md:p-4">
                    <div
                        v-for="(row, i) in form.lines"
                        :key="`${row.product_id}-${i}`"
                        class="border-border flex gap-3 rounded-lg border bg-white/80 p-2 dark:bg-emerald-950/30"
                    >
                        <div
                            class="bg-muted size-14 shrink-0 overflow-hidden rounded-md"
                        >
                            <img
                                v-if="row.image_url"
                                :src="row.image_url"
                                :alt="row.name"
                                class="size-full object-cover"
                            />
                            <div
                                v-else
                                class="text-muted-foreground flex size-full items-center justify-center"
                            >
                                <Package class="size-6 opacity-50" />
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-2 text-sm font-semibold">
                                {{ row.name }}
                            </p>
                            <p class="text-muted-foreground text-xs tabular-nums">
                                {{ row.unit_price_before_discount }} each
                            </p>
                            <div class="mt-2 flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon-sm"
                                    class="size-8"
                                    @click="bumpLineQty(i, -1)"
                                >
                                    <Minus class="size-4" />
                                </Button>
                                <Input
                                    v-model="row.quantity"
                                    class="h-8 w-14 text-center"
                                    inputmode="decimal"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon-sm"
                                    class="size-8"
                                    @click="bumpLineQty(i, 1)"
                                >
                                    <Plus class="size-4" />
                                </Button>
                            </div>
                        </div>
                        <div
                            class="flex shrink-0 flex-col items-end justify-between"
                        >
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                class="text-muted-foreground"
                                @click="removeLine(i)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                            <span
                                class="text-sm font-bold tabular-nums text-emerald-800 dark:text-emerald-300"
                            >
                                {{ formatMoney(lineTotal(row)) }}
                            </span>
                        </div>
                    </div>
                    <p
                        v-if="!form.lines.length"
                        class="text-muted-foreground border-border/60 rounded-lg border border-dashed py-10 text-center text-sm"
                    >
                        Tap a product in the catalog or use the search field.
                    </p>
                </div>
                <div
                    class="border-border space-y-3 border-t bg-emerald-100/50 p-3 dark:bg-emerald-950/40 md:p-4"
                >
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-end"
                    >
                        <div class="grid min-w-0 flex-1 gap-1 sm:min-w-[5.5rem]">
                            <Label class="text-xs font-medium">Discount</Label>
                            <Select v-model="form.discount_type">
                                <SelectTrigger class="h-9">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">None</SelectItem>
                                    <SelectItem value="fixed">Fixed</SelectItem>
                                    <SelectItem value="percentage">%</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid w-full gap-1 sm:w-24 sm:flex-none">
                            <Label class="text-xs font-medium">Amount</Label>
                            <Input
                                v-model="form.discount_amount"
                                class="h-9"
                                inputmode="decimal"
                            />
                        </div>
                        <div class="grid min-w-0 flex-1 gap-1 sm:min-w-[7rem]">
                            <Label class="text-xs font-medium">Order tax</Label>
                            <Select v-model="form.tax_rate_id">
                                <SelectTrigger class="h-9">
                                    <SelectValue placeholder="None" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="NONE">None</SelectItem>
                                    <SelectItem
                                        v-for="tr in taxRates"
                                        :key="tr.id"
                                        :value="String(tr.id)"
                                    >
                                        {{ tr.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <div class="space-y-1.5 text-sm">
                        <div class="flex justify-between gap-2">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span class="font-medium tabular-nums">{{
                                formatMoney(linesSum)
                            }}</span>
                        </div>
                        <div
                            v-if="headerDiscountAmount > 0"
                            class="flex justify-between gap-2 text-red-700 dark:text-red-400"
                        >
                            <span>{{ headerDiscountLabel }}</span>
                            <span class="tabular-nums"
                                >-{{ formatMoney(headerDiscountAmount) }}</span
                            >
                        </div>
                        <div
                            v-if="saleTaxAmount > 0"
                            class="flex justify-between gap-2"
                        >
                            <span class="text-muted-foreground"
                                >Tax ({{
                                    selectedTaxRate?.name ?? 'rate'
                                }})</span
                            >
                            <span class="font-medium tabular-nums">{{
                                formatMoney(saleTaxAmount)
                            }}</span>
                        </div>
                        <div
                            class="flex justify-between border-t border-emerald-200/80 pt-2 text-base font-bold dark:border-emerald-800"
                        >
                            <span>Total payable</span>
                            <span
                                class="tabular-nums text-emerald-800 dark:text-emerald-300"
                                >{{ formatMoney(grandTotal) }}</span
                            >
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            class="flex-1"
                            :disabled="!canSubmitDraft || form.processing"
                            @click="submitDraft"
                        >
                            Draft sale
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="flex-1 border-amber-600/50 text-amber-900 hover:bg-amber-50 dark:text-amber-200 dark:hover:bg-amber-950/40"
                            @click="toggleTaxExempt"
                        >
                            {{ taxExemptActive ? 'Apply tax' : 'Tax exempt' }}
                        </Button>
                    </div>
                    <div class="grid gap-1.5">
                        <Label class="text-xs font-medium" for="pos-note-side"
                            >Sale note</Label
                        >
                        <textarea
                            id="pos-note-side"
                            v-model="form.sale_note"
                            rows="2"
                            class="border-input bg-background min-h-[44px] w-full rounded-md border px-2 py-1.5 text-xs shadow-xs outline-none"
                            placeholder="Optional note…"
                        />
                    </div>
                </div>
            </aside>
        </div>

        <footer
            class="sticky bottom-0 z-20 shrink-0 border-t border-zinc-800 bg-zinc-900 px-3 py-3 text-zinc-100 shadow-[0_-8px_32px_rgba(0,0,0,0.25)] md:px-4"
        >
            <div
                class="mx-auto flex w-full max-w-[1920px] flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        class="bg-zinc-700 text-white hover:bg-zinc-600"
                        :disabled="!canSubmitDraft || form.processing"
                        @click="submitDraft"
                    >
                        Hold order
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        class="border-red-400/70 text-red-200 hover:bg-red-950/50 hover:text-red-50"
                        :disabled="form.processing"
                        @click="clearCart"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        class="border-zinc-600 text-zinc-200 hover:bg-zinc-800"
                        :disabled="!canSubmitSale || form.processing"
                        @click="saveQuotation"
                    >
                        Quotation
                    </Button>
                </div>
                <div
                    class="flex flex-col items-center gap-0.5 text-center lg:items-start lg:text-left"
                >
                    <p class="text-zinc-400 text-xs font-medium tracking-wide">
                        Selected items ·
                        {{ selectedProductCount }}
                        {{ selectedProductCount === 1 ? 'product' : 'products' }}
                    </p>
                    <p class="text-lg font-semibold tracking-tight text-white">
                        Amount due
                        <span
                            class="ml-2 text-2xl font-bold tabular-nums text-emerald-400"
                            >{{ formatMoney(grandTotal) }}</span
                        >
                    </p>
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <Button
                        type="button"
                        variant="secondary"
                        class="bg-zinc-700 text-white hover:bg-zinc-600"
                        :disabled="!canSubmitSale || form.processing"
                        @click="quickCash"
                    >
                        <Wallet class="mr-1 size-4" />
                        Cash
                    </Button>
                    <Button
                        type="button"
                        class="bg-emerald-500 px-6 text-base font-semibold text-white hover:bg-emerald-400"
                        :disabled="!canSubmitSale || form.processing"
                        @click="openPayDialog"
                    >
                        Pay now
                        <ArrowRight class="ml-1 inline size-5" />
                    </Button>
                </div>
            </div>
        </footer>

        <Dialog :open="payOpen" @update:open="(v) => (payOpen = v)">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Payment</DialogTitle>
                </DialogHeader>
                <div class="grid gap-3 py-2">
                    <div class="grid gap-2">
                        <Label>Amount *</Label>
                        <Input
                            v-model="form.payment.amount"
                            inputmode="decimal"
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
                        class="grid gap-2"
                    >
                        <Label>Bank reference</Label>
                        <Input v-model="form.payment.bank_account_number" />
                    </div>
                </div>
                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="payOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        :disabled="form.processing"
                        @click="postCheckout('final')"
                    >
                        <Spinner v-if="form.processing" />
                        Complete sale
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

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
