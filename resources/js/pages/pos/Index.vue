<script setup lang="ts">
import { Form, Head, useForm, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
    Banknote,
    Briefcase,
    Clock,
    FileText,
    Info,
    Minus,
    Package,
    Pencil,
    Percent,
    Plus,
    ScanLine,
    Search,
    ShoppingCart,
    Table2,
    Tags,
    Trash2,
    UserRound,
    UtensilsCrossed,
    Wallet,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import StandardFormModal from '@/components/StandardFormModal.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { xsrfToken } from '@/lib/xsrf';
import CustomerForm from '@/pages/customers/CustomerForm.vue';
import {
    customerFormFields,
    transformCustomerSubmit,
} from '@/pages/customers/customerFormState';
import cashRegisterRoutes from '@/routes/cash-register';
import customerRoutes from '@/routes/customers';
import posRoutes from '@/routes/pos';
import productRoutes from '@/routes/products';
import salesRoutes from '@/routes/sales';
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
    brands?: { id: number; name: string }[];
    sellingPriceGroups?: { id: number; name: string }[];
    restaurantTables?: { id: number; name: string; business_location_id: number }[];
    cashRegisterSession: {
        id: number;
        business_location_id: number;
        location_name: string;
        opening_cash: string;
        opened_at: string;
    } | null;
    walkInCustomerId?: number | null;
    posPrintSaleId?: number | null;
}>();

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

const canUsePos = computed(() => !!props.cashRegisterSession);

const openRegisterForm = useForm({
    business_location_id: '',
    amount: '',
});

const closeRegisterForm = useForm({});

function submitOpenRegister() {
    openRegisterForm.post(cashRegisterRoutes.store.url(teamSlug.value), {
        preserveScroll: true,
    });
}

function closeRegister() {
    if (!confirm('Close the cash register for this shift?')) {
        return;
    }

    closeRegisterForm.post(cashRegisterRoutes.close.url(teamSlug.value), {
        preserveScroll: true,
    });
}

function toLocalInput(d = new Date()): string {
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

const NONE = '__none__';
/** Select sentinel for “all” category / brand filters */
const FILTER_ALL = 'all';

const authUserId = String(
    (page.props.auth as { user?: { id: number } } | undefined)?.user?.id ?? '',
);

const form = useForm({
    business_location_id: '',
    selling_price_group_id: NONE,
    restaurant_table_id: NONE,
    service_staff_id: authUserId || NONE,
    customer_id:
        props.walkInCustomerId != null && props.walkInCustomerId > 0
            ? String(props.walkInCustomerId)
            : '',
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
const selectedBrandId = ref<number | null>(null);
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
    brandId: number | null = null,
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

    if (brandId != null && brandId > 0) {
        query.brand_id = String(brandId);
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
        selectedBrandId.value = null;

        if (form.business_location_id) {
            browseProducts.value = await fetchProducts(
                '',
                null,
                selectedBrandId.value,
            );
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
        selectedBrandId.value,
    );
});

watch(selectedBrandId, async () => {
    if (!form.business_location_id) {
        return;
    }

    productSearch.value = '';
    searchDropdownHits.value = [];
    browseProducts.value = await fetchProducts(
        '',
        selectedCategoryId.value,
        selectedBrandId.value,
    );
});

function categoryFilterSelectValue(): string {
    return selectedCategoryId.value == null
        ? FILTER_ALL
        : String(selectedCategoryId.value);
}

function brandFilterSelectValue(): string {
    return selectedBrandId.value == null
        ? FILTER_ALL
        : String(selectedBrandId.value);
}

function onCategoryFilterChange(v: string) {
    if (v === FILTER_ALL || !v) {
        selectedCategoryId.value = null;

        return;
    }

    const n = Number.parseInt(v, 10);
    selectedCategoryId.value = Number.isFinite(n) ? n : null;
}

function onBrandFilterChange(v: string) {
    if (v === FILTER_ALL || !v) {
        selectedBrandId.value = null;

        return;
    }

    const n = Number.parseInt(v, 10);
    selectedBrandId.value = Number.isFinite(n) ? n : null;
}

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
    const hits = await fetchProducts(
        q,
        selectedCategoryId.value,
        selectedBrandId.value,
    );

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

    const hits = await fetchProducts(
        q,
        selectedCategoryId.value,
        selectedBrandId.value,
    );
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
            selectedBrandId.value,
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

const totalLineQuantity = computed(() =>
    form.lines.reduce((s, row) => s + (Number(row.quantity) || 0), 0),
);

const editDiscountOpen = ref(false);
const editTaxOpen = ref(false);
const editShippingOpen = ref(false);
const editPackingOpen = ref(false);

/** Mark sale for kitchen; stored as a line prefix on `sale_note` at submit. */
const kitchenOrder = ref(false);

function setKitchenOrder(v: boolean | 'indeterminate') {
    kitchenOrder.value = v === true;
}

const sellingPriceGroupsList = computed(
    () => props.sellingPriceGroups ?? [],
);

const tablesForLocation = computed(() => {
    const loc = form.business_location_id;

    if (!loc) {
        return [];
    }

    return (props.restaurantTables ?? []).filter(
        (t) => String(t.business_location_id) === String(loc),
    );
});

watch(
    () => form.business_location_id,
    () => {
        const tid = form.restaurant_table_id;

        if (!tid || tid === NONE) {
            return;
        }

        const ok = tablesForLocation.value.some((t) => String(t.id) === String(tid));

        if (!ok) {
            form.restaurant_table_id = NONE;
        }
    },
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

const locationLocked = computed(
    () => canUsePos.value && !!props.cashRegisterSession,
);

onMounted(() => {
    if (props.cashRegisterSession) {
        form.business_location_id = String(
            props.cashRegisterSession.business_location_id,
        );
    } else if (
        props.businessLocations.length === 1 &&
        !form.business_location_id
    ) {
        const id = String(props.businessLocations[0].id);
        form.business_location_id = id;
        openRegisterForm.business_location_id = id;
    } else if (props.businessLocations.length > 0) {
        openRegisterForm.business_location_id = String(
            props.businessLocations[0].id,
        );
    }

    focusScanInput();
});

const lastHandledPrintSaleId = ref<number | null>(null);

watch(
    () => props.posPrintSaleId,
    (id) => {
        if (id == null || id < 1) {
            return;
        }

        if (lastHandledPrintSaleId.value === id) {
            return;
        }

        lastHandledPrintSaleId.value = id;
        const path = salesRoutes.documents.invoice.url(
            [teamSlug.value, id],
            { query: { autoprint: '1' } },
        );
        window.open(path, '_blank', 'noopener,noreferrer');
    },
    { immediate: true },
);

const payOpen = ref(false);

type PayRow = {
    amount: string;
    paid_on: string;
    method: string;
    payment_account_id: string;
    note: string;
    bank_account_number: string;
};

const paymentRows = ref<PayRow[]>([]);

type RecentSaleRow = {
    id: number;
    invoice_no: string | null;
    transaction_date: string | null;
    final_total: string;
    status: string;
    customer: { display_name: string } | null;
};

const recentOpen = ref(false);
const draftConfirmOpen = ref(false);
const quotationConfirmOpen = ref(false);
const recentTab = ref<'final' | 'quotation' | 'draft'>('final');
const recentData = ref<{
    final: RecentSaleRow[];
    quotation: RecentSaleRow[];
    draft: RecentSaleRow[];
} | null>(null);
const recentLoading = ref(false);

const registerDetailsOpen = ref(false);

async function loadRecentTransactions() {
    recentLoading.value = true;

    try {
        const r = await fetch(
            posRoutes.recentTransactions.url(teamSlug.value),
            {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            },
        );
        recentData.value = (await r.json()) as {
            final: RecentSaleRow[];
            quotation: RecentSaleRow[];
            draft: RecentSaleRow[];
        };
    } finally {
        recentLoading.value = false;
    }
}

watch(recentOpen, (open) => {
    if (open) {
        void loadRecentTransactions();
    }
});

function accountsForPayMethod(method: string) {
    return props.paymentAccounts.filter((a) => a.payment_method === method);
}

function addPaymentRow() {
    paymentRows.value.push({
        amount: '0',
        paid_on: form.payment.paid_on,
        method: defaultMethod.value,
        payment_account_id: NONE,
        note: '',
        bank_account_number: '',
    });
}

function removePaymentRow(i: number) {
    if (paymentRows.value.length <= 1) {
        return;
    }

    paymentRows.value.splice(i, 1);
}

const paymentRowsTotal = computed(() =>
    paymentRows.value.reduce((s, r) => s + (Number(r.amount) || 0), 0),
);

function finalizeMultiPay() {
    const sum = paymentRowsTotal.value;

    if (Math.abs(sum - grandTotal.value) > 0.02) {
        alert(
            `Payment rows total (${sum.toFixed(2)}) must match amount due (${grandTotal.value.toFixed(2)}).`,
        );

        return;
    }

    postCheckout('final', { multiPay: true });
}

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
    options?: { multiPay?: boolean },
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

    const multiPay = status === 'final' && options?.multiPay;
    let paymentBlock: Record<string, string> = {};

    if (status === 'quotation') {
        paymentBlock = {};
    } else if (multiPay && paymentRows.value.length > 0) {
        const rows = paymentRows.value.map((row) => ({
            amount: Number(row.amount) || 0,
            paid_on: row.paid_on,
            method: row.method,
            payment_account_id:
                row.payment_account_id === NONE ||
                row.payment_account_id === ''
                    ? null
                    : Number(row.payment_account_id),
            note: row.note?.trim() || null,
            bank_account_number: row.bank_account_number?.trim() || null,
        }));
        paymentBlock = { payments: JSON.stringify(rows) };
    } else {
        paymentBlock = { payment: JSON.stringify(pay) };
    }

    const noteBody = (d.sale_note ?? '')
        .replace(/^\[Kitchen order\]\s*\n?/i, '')
        .trim();
    const saleNoteMerged =
        [kitchenOrder.value ? '[Kitchen order]' : null, noteBody || null]
            .filter(Boolean)
            .join('\n') || null;

    /* eslint-disable @typescript-eslint/no-unused-vars -- strip nested keys reshaped below */
    const {
        document,
        lines: _lines,
        additional_expenses: _ae,
        payment: _pay,
        sale_note: _sn,
        selling_price_group_id: _spg,
        restaurant_table_id: _rtid,
        service_staff_id: _ssid,
        ...rest
    } = d;
    /* eslint-enable @typescript-eslint/no-unused-vars */

    return {
        ...rest,
        status,
        customer_id: Number(d.customer_id),
        business_location_id: Number(d.business_location_id),
        selling_price_group_id:
            !d.selling_price_group_id || d.selling_price_group_id === NONE
                ? null
                : Number(d.selling_price_group_id),
        restaurant_table_id:
            !d.restaurant_table_id || d.restaurant_table_id === NONE
                ? null
                : Number(d.restaurant_table_id),
        service_staff_id:
            !d.service_staff_id || d.service_staff_id === NONE
                ? null
                : Number(d.service_staff_id),
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
        sale_note: saleNoteMerged,
        lines: JSON.stringify(lines),
        additional_expenses: JSON.stringify(expenses),
        ...paymentBlock,
        document,
    };
}

async function resetPosFormAfterSale() {
    addedCustomers.value = [];
    form.reset();

    if (props.cashRegisterSession) {
        form.business_location_id = String(
            props.cashRegisterSession.business_location_id,
        );
    }

    if (props.walkInCustomerId != null && props.walkInCustomerId > 0) {
        form.customer_id = String(props.walkInCustomerId);
    }

    form.service_staff_id = authUserId || NONE;
    kitchenOrder.value = false;
    productSearch.value = '';
    searchDropdownHits.value = [];
    selectedCategoryId.value = null;
    selectedBrandId.value = null;

    if (form.business_location_id) {
        browseProducts.value = await fetchProducts('', null, null);
    } else {
        browseProducts.value = [];
    }

    paymentRows.value = [
        {
            amount: '0',
            paid_on: form.payment.paid_on,
            method: defaultMethod.value,
            payment_account_id: NONE,
            note: '',
            bank_account_number: '',
        },
    ];

    await nextTick();
    focusScanInput();
}

function postCheckout(
    status: 'final' | 'quotation',
    opts?: { multiPay?: boolean },
) {
    form
        .transform((d) => transformPayload(d, status, opts))
        .post(posRoutes.checkout.url(teamSlug.value), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                payOpen.value = false;

                if (status === 'final') {
                    void resetPosFormAfterSale();
                }
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

function confirmDraftFromModal() {
    draftConfirmOpen.value = false;
    submitDraft();
}

function confirmQuotationFromModal() {
    quotationConfirmOpen.value = false;
    saveQuotation();
}

function openPayDialog() {
    if (!canSubmitSale.value) {
        return;
    }

    form.payment.amount = grandTotal.value.toFixed(2);
    paymentRows.value = [
        {
            amount: grandTotal.value.toFixed(2),
            paid_on: form.payment.paid_on,
            method: form.payment.method,
            payment_account_id: form.payment.payment_account_id || NONE,
            note: form.payment.note,
            bank_account_number: form.payment.bank_account_number,
        },
    ];
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
    postCheckout('final', { multiPay: false });
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
    form.selling_price_group_id = NONE;
    form.restaurant_table_id = NONE;
    form.service_staff_id = authUserId || NONE;

    if (props.walkInCustomerId != null && props.walkInCustomerId > 0) {
        form.customer_id = String(props.walkInCustomerId);
    }

    kitchenOrder.value = false;
    productSearch.value = '';
    searchDropdownHits.value = [];

    if (form.business_location_id) {
        browseProducts.value = await fetchProducts(
            '',
            selectedCategoryId.value,
            selectedBrandId.value,
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
        v-if="!canUsePos"
        class="flex min-h-0 flex-1 flex-col items-center justify-center gap-6 overflow-auto bg-emerald-50/50 p-6 dark:bg-background"
    >
        <div
            class="bg-card border-border w-full max-w-md rounded-2xl border p-8 shadow-lg"
        >
            <h1 class="mb-1 text-center text-xl font-bold tracking-tight">
                Open cash register
            </h1>
            <p class="text-muted-foreground mb-6 text-center text-sm">
                Enter the opening cash in the drawer for this shift, then
                continue to the POS.
            </p>
            <Form
                class="grid gap-4"
                @submit.prevent="submitOpenRegister"
            >
                <div class="grid gap-2">
                    <Label for="open-reg-loc">Business location *</Label>
                    <Select v-model="openRegisterForm.business_location_id">
                        <SelectTrigger id="open-reg-loc">
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
                    <p
                        v-if="openRegisterForm.errors.business_location_id"
                        class="text-destructive text-xs"
                    >
                        {{ openRegisterForm.errors.business_location_id }}
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label for="open-reg-amt">Cash in hand *</Label>
                    <Input
                        id="open-reg-amt"
                        v-model="openRegisterForm.amount"
                        inputmode="decimal"
                        placeholder="0.00"
                        required
                    />
                    <p
                        v-if="openRegisterForm.errors.amount"
                        class="text-destructive text-xs"
                    >
                        {{ openRegisterForm.errors.amount }}
                    </p>
                </div>
                <Button
                    type="submit"
                    class="w-full bg-emerald-600 text-white hover:bg-emerald-700"
                    :disabled="openRegisterForm.processing"
                >
                    <Spinner v-if="openRegisterForm.processing" />
                    Open register
                </Button>
            </Form>
        </div>
    </div>

    <div
        v-else
        class="flex min-h-0 flex-1 flex-col gap-0 overflow-hidden bg-emerald-50/40 dark:bg-background md:gap-2"
    >
        <header
            class="bg-card border-border shrink-0 border-b px-2 py-1.5 shadow-sm sm:px-3 md:px-4 md:py-2"
        >
            <div
                class="mx-auto flex w-full max-w-[1920px] flex-col gap-2 sm:gap-3 lg:flex-row lg:items-end lg:justify-between"
            >
                <div
                    class="grid w-full gap-2 sm:grid-cols-2 sm:gap-3 lg:grid-cols-3 lg:gap-4"
                >
                <div class="grid gap-1.5">
                    <Label class="text-xs font-medium text-muted-foreground"
                        >Business location *</Label
                    >
                    <Select
                        v-model="form.business_location_id"
                        :disabled="locationLocked"
                    >
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
                <div
                    class="flex w-full flex-wrap items-center justify-end gap-2 lg:w-auto lg:shrink-0"
                >
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="gap-1"
                        @click="registerDetailsOpen = true"
                    >
                        <Briefcase class="size-4" />
                        Register
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="text-destructive border-destructive/40 hover:bg-destructive/10"
                        :disabled="closeRegisterForm.processing"
                        @click="closeRegister"
                    >
                        Close register
                    </Button>
                </div>
            </div>
        </header>

        <div
            class="mx-auto grid min-h-0 w-full max-w-[1920px] flex-1 grid-cols-1 gap-1.5 overflow-hidden p-1 md:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] md:gap-2 md:p-2"
        >
            <aside
                id="pos-order-panel"
                class="border-border bg-card flex min-h-0 min-w-0 flex-col overflow-hidden rounded-2xl border shadow-[0_0_16px_rgba(17,17,26,0.08)] dark:shadow-none"
            >
                <div
                    class="border-border flex shrink-0 items-center justify-between gap-2 border-b bg-muted/25 px-2 py-1.5 sm:px-2.5"
                >
                    <div class="flex min-w-0 items-center gap-2">
                        <div
                            class="bg-primary flex size-7 shrink-0 items-center justify-center rounded-lg text-primary-foreground sm:size-8"
                        >
                            <ShoppingCart class="size-4" />
                        </div>
                        <div class="min-w-0">
                            <h2
                                class="truncate text-xs font-bold tracking-tight sm:text-sm"
                            >
                                Sale / order
                            </h2>
                            <p
                                class="text-muted-foreground truncate text-[10px] sm:text-xs"
                            >
                                {{ selectedLocation?.name ?? 'No location' }}
                            </p>
                        </div>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="h-7 shrink-0 px-2 text-xs text-destructive sm:h-8 sm:px-2.5"
                        :disabled="form.lines.length === 0"
                        @click="clearCart"
                    >
                        Clear
                    </Button>
                </div>
                <div
                    class="border-border shrink-0 overflow-x-auto border-b bg-background px-2 py-1.5"
                >
                    <div
                        class="flex min-w-0 flex-nowrap items-stretch gap-1.5 sm:gap-2"
                    >
                        <div
                            class="flex w-[min(34%,10.5rem)] shrink-0 items-stretch gap-1 sm:w-44"
                        >
                            <Label for="pos-customer-select" class="sr-only"
                                >Customer *</Label
                            >
                            <Select
                                v-model="form.customer_id"
                                class="min-w-0 flex-1"
                            >
                                <SelectTrigger
                                    id="pos-customer-select"
                                    class="h-9 w-full min-w-0 px-2 text-left text-xs sm:text-sm"
                                >
                                    <SelectValue placeholder="Customer *" />
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
                                class="size-9 shrink-0"
                                title="Add customer"
                                @click="openCustomerModal"
                            >
                                <Plus class="size-4" />
                            </Button>
                        </div>
                        <div class="relative min-h-0 min-w-0 flex-1">
                            <Label for="pos-product-scan" class="sr-only"
                                >Product search or scan</Label
                            >
                            <ScanLine
                                class="text-muted-foreground pointer-events-none absolute left-2 top-1/2 z-10 size-4 -translate-y-1/2 sm:left-2.5 sm:size-[1.15rem]"
                            />
                            <Input
                                :id="POS_SCAN_INPUT_ID"
                                v-model="productSearch"
                                :disabled="!form.business_location_id"
                                :placeholder="
                                    form.business_location_id
                                        ? 'Product, SKU, or scan…'
                                        : 'Select location…'
                                "
                                autocomplete="off"
                                class="h-9 w-full min-w-0 pl-8 text-xs sm:pl-9 sm:text-sm"
                                @keydown.enter.prevent="onProductSearchEnter"
                            />
                            <div
                                v-if="
                                    productSearch.trim().length > 0 &&
                                    searchDropdownHits.length
                                "
                                class="bg-popover absolute z-40 mt-1 max-h-48 w-full overflow-auto rounded-md border border-border shadow-lg"
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
                            variant="default"
                            class="h-9 shrink-0 px-2 sm:px-3"
                            :disabled="!form.business_location_id"
                            @click="focusScanInput"
                        >
                            <Search class="size-4 sm:mr-1" />
                            <span class="hidden sm:inline">Search</span>
                        </Button>
                    </div>
                </div>
                <div
                    class="border-border grid shrink-0 grid-cols-2 gap-1.5 border-b bg-background px-2 py-1.5 sm:grid-cols-4 sm:items-center"
                >
                    <div class="flex min-w-0 items-center gap-1">
                        <Tags
                            class="text-muted-foreground size-3.5 shrink-0 sm:size-4"
                            aria-hidden="true"
                        />
                        <Select
                            v-model="form.selling_price_group_id"
                            class="min-w-0 flex-1"
                            :disabled="!form.business_location_id"
                        >
                            <SelectTrigger
                                class="h-8 w-full min-w-0 px-2 text-left text-[11px] sm:text-xs"
                            >
                                <SelectValue placeholder="Service type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">Default pricing</SelectItem>
                                <SelectItem
                                    v-for="g in sellingPriceGroupsList"
                                    :key="g.id"
                                    :value="String(g.id)"
                                >
                                    {{ g.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="flex min-w-0 items-center gap-1">
                        <Table2
                            class="text-muted-foreground size-3.5 shrink-0 sm:size-4"
                            aria-hidden="true"
                        />
                        <Select
                            v-model="form.restaurant_table_id"
                            class="min-w-0 flex-1"
                            :disabled="!form.business_location_id"
                        >
                            <SelectTrigger
                                class="h-8 w-full min-w-0 px-2 text-left text-[11px] sm:text-xs"
                            >
                                <SelectValue placeholder="Table" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">No table</SelectItem>
                                <SelectItem
                                    v-for="t in tablesForLocation"
                                    :key="t.id"
                                    :value="String(t.id)"
                                >
                                    {{ t.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="flex min-w-0 items-center gap-1">
                        <UserRound
                            class="text-muted-foreground size-3.5 shrink-0 sm:size-4"
                            aria-hidden="true"
                        />
                        <Select
                            v-model="form.service_staff_id"
                            class="min-w-0 flex-1"
                            :disabled="!form.business_location_id"
                        >
                            <SelectTrigger
                                class="h-8 w-full min-w-0 px-2 text-left text-[11px] sm:text-xs"
                            >
                                <SelectValue placeholder="Staff" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">Any staff</SelectItem>
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
                    <div
                        class="text-muted-foreground flex min-w-0 items-center gap-1.5 text-[11px] sm:text-xs"
                    >
                        <Checkbox
                            id="pos-kitchen-order"
                            :model-value="kitchenOrder"
                            @update:model-value="setKitchenOrder"
                        />
                        <Label
                            for="pos-kitchen-order"
                            class="line-clamp-2 cursor-pointer font-normal"
                            >Kitchen</Label
                        >
                        <TooltipProvider :delay-duration="200">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <button
                                        type="button"
                                        class="hover:text-foreground inline-flex shrink-0 rounded p-0.5"
                                        aria-label="About kitchen order"
                                    >
                                        <Info class="size-3.5" />
                                    </button>
                                </TooltipTrigger>
                                <TooltipContent
                                    class="max-w-[220px] text-xs"
                                    side="top"
                                >
                                    When checked, "[Kitchen order]" is added to
                                    the sale note when you save the sale.
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                        <UtensilsCrossed
                            class="text-muted-foreground ml-auto size-3.5 shrink-0 opacity-70 sm:size-4"
                            aria-hidden="true"
                        />
                    </div>
                </div>
                <div
                    class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden p-0"
                >
                    <div
                        class="min-h-0 flex-1 overflow-y-auto overflow-x-auto border-b border-border bg-slate-50/80 dark:bg-muted/20"
                    >
                        <table
                            class="w-full min-w-[260px] table-fixed border-collapse text-left text-[11px] leading-tight sm:text-xs"
                        >
                            <thead
                                class="sticky top-0 z-10 border-b border-slate-200 bg-slate-50 shadow-sm dark:border-border dark:bg-muted/95"
                            >
                                <tr
                                    class="text-muted-foreground [&_th]:font-semibold"
                                >
                                    <th
                                        class="px-1.5 py-1.5 text-[10px] uppercase tracking-wide sm:px-2 sm:text-[11px]"
                                    >
                                        Product
                                    </th>
                                    <th
                                        class="w-[4.5rem] px-0.5 py-1.5 text-center text-[10px] uppercase tracking-wide sm:w-[5.25rem] sm:text-[11px]"
                                    >
                                        Qty
                                    </th>
                                    <th
                                        class="w-[3.25rem] px-1 py-1.5 text-right text-[10px] uppercase tracking-wide sm:w-20 sm:text-[11px]"
                                    >
                                        Total
                                    </th>
                                    <th
                                        class="w-7 p-0 text-center sm:w-8"
                                    ></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(row, i) in form.lines"
                                    :key="`${row.product_id}-${i}`"
                                    class="border-b border-border/40 odd:bg-muted/20 hover:bg-muted/40"
                                >
                                    <td class="px-1.5 py-1 align-top sm:px-2">
                                        <div class="flex gap-1.5 sm:gap-2">
                                            <div
                                                class="bg-muted size-9 shrink-0 overflow-hidden rounded sm:size-10"
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
                                                    <Package
                                                        class="size-4 opacity-50 sm:size-5"
                                                    />
                                                </div>
                                            </div>
                                            <div class="min-w-0">
                                                <p
                                                    class="line-clamp-2 font-medium"
                                                >
                                                    {{ row.name }}
                                                </p>
                                                <p
                                                    class="text-muted-foreground text-[10px] tabular-nums sm:text-[11px]"
                                                >
                                                    {{ row.unit_price_before_discount }}
                                                    ea
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        class="px-0.5 py-1 align-middle text-center"
                                    >
                                        <div
                                            class="flex items-center justify-center gap-0.5"
                                        >
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon-sm"
                                                class="size-7 sm:size-8"
                                                @click="bumpLineQty(i, -1)"
                                            >
                                                <Minus class="size-3.5 sm:size-4" />
                                            </Button>
                                            <Input
                                                v-model="row.quantity"
                                                class="h-7 w-10 px-0.5 text-center text-xs sm:h-8 sm:w-11"
                                                inputmode="decimal"
                                            />
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon-sm"
                                                class="size-7 sm:size-8"
                                                @click="bumpLineQty(i, 1)"
                                            >
                                                <Plus class="size-3.5 sm:size-4" />
                                            </Button>
                                        </div>
                                    </td>
                                    <td
                                        class="text-foreground whitespace-nowrap px-1 py-1 text-right align-middle text-[11px] font-semibold tabular-nums sm:text-xs"
                                    >
                                        {{ formatMoney(lineTotal(row)) }}
                                    </td>
                                    <td class="p-0 text-center align-middle">
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon-sm"
                                            class="text-muted-foreground size-7 sm:size-8"
                                            @click="removeLine(i)"
                                        >
                                            <Trash2 class="size-3.5 sm:size-4" />
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p
                            v-if="!form.lines.length"
                            class="text-muted-foreground px-3 py-8 text-center text-xs sm:py-10 sm:text-sm"
                        >
                            Add products from the catalog or search above.
                        </p>
                    </div>
                </div>
                <div
                    class="border-border shrink-0 space-y-1.5 border-t bg-slate-50 p-1.5 dark:bg-muted/25 sm:space-y-2 sm:p-2"
                >
                    <TooltipProvider :delay-duration="200">
                        <div
                            class="border-border bg-background/95 flex w-full min-w-0 flex-nowrap divide-x divide-border overflow-x-auto rounded-md border text-[11px] shadow-sm sm:text-xs"
                        >
                            <div
                                class="flex min-w-[3.5rem] flex-1 basis-0 flex-col items-center justify-center gap-0 px-0.5 py-1.5 sm:min-w-[4rem] sm:px-1"
                            >
                                <span
                                    class="text-muted-foreground text-[9px] font-bold uppercase leading-tight tracking-wide"
                                    >Items</span
                                >
                                <span
                                    class="text-foreground text-[12px] font-bold tabular-nums leading-tight tracking-tight sm:text-[13px]"
                                    >{{ totalLineQuantity.toFixed(2) }}</span
                                >
                            </div>
                            <div
                                class="flex min-w-[3.5rem] flex-1 basis-0 flex-col items-center justify-center gap-0 px-0.5 py-1.5 sm:min-w-[4rem] sm:px-1"
                            >
                                <span
                                    class="text-muted-foreground text-[9px] font-bold uppercase leading-tight tracking-wide"
                                    >Total</span
                                >
                                <span
                                    class="text-foreground text-[12px] font-bold tabular-nums leading-tight tracking-tight sm:text-[13px]"
                                    >{{ formatMoney(linesSum) }}</span
                                >
                            </div>
                            <div
                                class="flex min-w-[3.75rem] flex-1 basis-0 flex-col items-center justify-center gap-0 px-0.5 py-1.5 sm:min-w-[4.25rem] sm:px-1"
                            >
                                <span
                                    class="text-muted-foreground flex max-w-full flex-wrap items-center justify-center gap-0.5 text-[9px] font-bold uppercase leading-tight tracking-wide"
                                >
                                    <span class="whitespace-nowrap"
                                        >Discount(-)</span
                                    >
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <button
                                                type="button"
                                                class="text-muted-foreground hover:text-foreground inline-flex shrink-0 rounded p-0.5"
                                                aria-label="About sale discount"
                                            >
                                                <Info class="size-3" />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent class="max-w-xs text-xs">
                                            Apply a discount to this sale. Set a
                                            default in business settings if you
                                            use the same discount often.
                                        </TooltipContent>
                                    </Tooltip>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="text-primary h-5 w-5 shrink-0 p-0 hover:bg-transparent"
                                        aria-label="Edit discount"
                                        @click="editDiscountOpen = true"
                                    >
                                        <Pencil class="size-3" />
                                    </Button>
                                </span>
                                <span
                                    class="text-[12px] font-bold tabular-nums leading-tight tracking-tight text-red-600 sm:text-[13px]"
                                    >{{ formatMoney(headerDiscountAmount) }}</span
                                >
                            </div>
                            <div
                                class="flex min-w-[3.75rem] flex-1 basis-0 flex-col items-center justify-center gap-0 px-0.5 py-1.5 sm:min-w-[4.25rem] sm:px-1"
                            >
                                <span
                                    class="text-muted-foreground flex max-w-full flex-wrap items-center justify-center gap-0.5 text-[9px] font-bold uppercase leading-tight tracking-wide"
                                >
                                    <span class="whitespace-nowrap"
                                        >Order tax(+)</span
                                    >
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <button
                                                type="button"
                                                class="text-muted-foreground hover:text-foreground inline-flex shrink-0 rounded p-0.5"
                                                aria-label="About order tax"
                                            >
                                                <Info class="size-3" />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent class="max-w-xs text-xs">
                                            Order tax is calculated on the
                                            amount after discount. Use Tax off
                                            below for tax-exempt sales.
                                        </TooltipContent>
                                    </Tooltip>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="text-primary h-5 w-5 shrink-0 p-0 hover:bg-transparent"
                                        aria-label="Edit order tax"
                                        @click="editTaxOpen = true"
                                    >
                                        <Pencil class="size-3" />
                                    </Button>
                                </span>
                                <span
                                    class="text-foreground text-[12px] font-bold tabular-nums leading-tight tracking-tight sm:text-[13px]"
                                    >{{ formatMoney(saleTaxAmount) }}</span
                                >
                            </div>
                            <div
                                class="flex min-w-[3.75rem] flex-1 basis-0 flex-col items-center justify-center gap-0 px-0.5 py-1.5 sm:min-w-[4.25rem] sm:px-1"
                            >
                                <span
                                    class="text-muted-foreground flex max-w-full flex-wrap items-center justify-center gap-0.5 text-[9px] font-bold uppercase leading-tight tracking-wide"
                                >
                                    <span class="whitespace-nowrap"
                                        >Shipping(+)</span
                                    >
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <button
                                                type="button"
                                                class="text-muted-foreground hover:text-foreground inline-flex shrink-0 rounded p-0.5"
                                                aria-label="About shipping"
                                            >
                                                <Info class="size-3" />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent class="max-w-xs text-xs">
                                            Add delivery charges and optional
                                            shipping notes for this sale.
                                        </TooltipContent>
                                    </Tooltip>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="text-primary h-5 w-5 shrink-0 p-0 hover:bg-transparent"
                                        aria-label="Edit shipping"
                                        @click="editShippingOpen = true"
                                    >
                                        <Pencil class="size-3" />
                                    </Button>
                                </span>
                                <span
                                    class="text-foreground text-[12px] font-bold tabular-nums leading-tight tracking-tight sm:text-[13px]"
                                    >{{
                                        formatMoney(
                                            Number(form.shipping_charges) || 0,
                                        )
                                    }}</span
                                >
                            </div>
                            <div
                                class="flex min-w-[3.75rem] flex-1 basis-0 flex-col items-center justify-center gap-0 px-0.5 py-1.5 sm:min-w-[4.25rem] sm:px-1"
                            >
                                <span
                                    class="text-muted-foreground flex max-w-full flex-wrap items-center justify-center gap-0.5 text-[9px] font-bold uppercase leading-tight tracking-wide"
                                >
                                    <span class="whitespace-nowrap"
                                        >Packing(+)</span
                                    >
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <button
                                                type="button"
                                                class="text-muted-foreground hover:text-foreground inline-flex shrink-0 rounded p-0.5"
                                                aria-label="About packing and fees"
                                            >
                                                <Info class="size-3" />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent class="max-w-xs text-xs">
                                            Named line charges (packing,
                                            service fees, etc.) with amounts.
                                        </TooltipContent>
                                    </Tooltip>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="text-primary h-5 w-5 shrink-0 p-0 hover:bg-transparent"
                                        aria-label="Edit packing and fees"
                                        @click="editPackingOpen = true"
                                    >
                                        <Pencil class="size-3" />
                                    </Button>
                                </span>
                                <span
                                    class="text-foreground text-[12px] font-bold tabular-nums leading-tight tracking-tight sm:text-[13px]"
                                    >{{ formatMoney(additionalSum) }}</span
                                >
                            </div>
                        </div>
                    </TooltipProvider>
                </div>
            </aside>
            <main
                id="pos-product-catalog"
                class="border-border bg-card flex min-h-0 min-w-0 flex-col overflow-hidden rounded-xl border shadow-sm"
            >
                <div class="border-border shrink-0 space-y-2 border-b p-2 sm:p-3">
                    <div
                        class="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:items-end"
                    >
                        <div class="grid min-w-0 gap-1.5">
                            <Label
                                class="text-xs font-medium text-muted-foreground"
                                for="pos-filter-category"
                                >Category</Label
                            >
                            <Select
                                id="pos-filter-category"
                                :model-value="categoryFilterSelectValue()"
                                :disabled="!form.business_location_id"
                                @update:model-value="
                                    (v) =>
                                        onCategoryFilterChange(
                                            v as string,
                                        )
                                "
                            >
                                <SelectTrigger class="h-10 w-full">
                                    <SelectValue
                                        placeholder="All categories"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="FILTER_ALL">
                                        All categories
                                    </SelectItem>
                                    <SelectItem
                                        v-for="cat in productCategories ?? []"
                                        :key="cat.id"
                                        :value="String(cat.id)"
                                    >
                                        {{ cat.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid min-w-0 gap-1.5">
                            <Label
                                class="text-xs font-medium text-muted-foreground"
                                for="pos-filter-brand"
                                >Brand</Label
                            >
                            <Select
                                id="pos-filter-brand"
                                :model-value="brandFilterSelectValue()"
                                :disabled="
                                    !form.business_location_id ||
                                    !(brands ?? []).length
                                "
                                @update:model-value="
                                    (v) =>
                                        onBrandFilterChange(v as string)
                                "
                            >
                                <SelectTrigger class="h-10 w-full">
                                    <SelectValue placeholder="All brands" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem :value="FILTER_ALL">
                                        All brands
                                    </SelectItem>
                                    <SelectItem
                                        v-for="b in brands ?? []"
                                        :key="b.id"
                                        :value="String(b.id)"
                                    >
                                        {{ b.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>
                <div
                    class="min-h-0 flex-1 overflow-y-auto p-2 sm:p-2.5"
                >
                    <div
                        v-if="form.business_location_id"
                        class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4"
                    >
                        <button
                            v-for="h in catalogGridProducts"
                            :key="`tile-${h.id}`"
                            type="button"
                            :disabled="!productCanAdd(h)"
                            class="border-border hover:border-emerald-500/60 flex flex-col overflow-hidden rounded-lg border bg-background text-left text-xs shadow-sm transition-all hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                            @click="tryAddProduct(h)"
                        >
                            <div
                                class="bg-muted relative h-16 w-full shrink-0 overflow-hidden sm:h-[4.25rem]"
                            >
                                <img
                                    v-if="h.image_url"
                                    :src="h.image_url"
                                    :alt="h.name"
                                    class="size-full object-contain"
                                />
                                <div
                                    v-else
                                    class="text-muted-foreground flex size-full items-center justify-center"
                                >
                                    <Package class="size-7 opacity-40 sm:size-8" />
                                </div>
                            </div>
                            <div class="flex min-h-0 flex-1 flex-col gap-0.5 p-1.5 sm:gap-1 sm:p-2">
                                <p
                                    class="text-muted-foreground line-clamp-2 font-mono text-[9px] uppercase leading-tight sm:text-[10px]"
                                >
                                    {{ h.sku || '—' }}
                                </p>
                                <p
                                    class="line-clamp-2 text-[11px] font-semibold leading-snug sm:text-xs"
                                >
                                    {{ h.name }}
                                </p>
                                <p
                                    class="text-primary text-sm font-bold tabular-nums sm:text-base"
                                >
                                    {{ h.default_unit_price }}
                                </p>
                                <span
                                    class="mt-auto inline-flex max-w-full truncate rounded px-1 py-0.5 text-[9px] font-bold uppercase leading-none sm:text-[10px]"
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
        </div>

        <footer
            class="sticky bottom-0 z-[100] shrink-0 rounded-t-xl border-t border-slate-200/80 bg-[#f8fafc] shadow-[0_-2px_10px_rgba(0,0,0,0.06)] dark:border-border dark:bg-card dark:shadow-lg"
        >
            <div
                class="mx-auto flex min-h-[52px] w-full max-w-[1920px] flex-col gap-2 overflow-x-auto px-3 py-1.5 sm:flex-row sm:items-center sm:justify-between sm:gap-3 sm:px-4 sm:py-1.5"
            >
                <div
                    class="flex w-full min-w-0 flex-wrap items-center gap-2 sm:flex-1 sm:gap-3 md:flex-nowrap"
                >
                    <div
                        class="flex shrink-0 items-center gap-3 border-b border-slate-200 pb-2 sm:border-b-0 sm:pb-0 md:hidden dark:border-border"
                    >
                        <div class="flex flex-col leading-tight">
                            <span class="text-foreground text-xs font-bold"
                                >Amount due</span
                            >
                            <span
                                class="text-lg font-bold tabular-nums text-emerald-800 dark:text-emerald-400"
                                >{{ formatMoney(grandTotal) }}</span
                            >
                        </div>
                    </div>
                    <div
                        class="flex min-w-0 flex-1 flex-wrap items-center gap-x-3 gap-y-2 overflow-x-auto sm:justify-start"
                    >
                        <button
                            type="button"
                            class="text-foreground hover:bg-muted/80 flex shrink-0 flex-col items-center gap-0.5 rounded-md px-2 py-1 text-xs font-bold disabled:opacity-40"
                            :disabled="!canSubmitDraft || form.processing"
                            @click="draftConfirmOpen = true"
                        >
                            <Pencil class="size-4 text-sky-500" aria-hidden="true" />
                            Draft
                        </button>
                        <button
                            type="button"
                            class="text-foreground hover:bg-muted/80 flex shrink-0 flex-col items-center gap-0.5 rounded-md px-2 py-1 text-xs font-bold disabled:opacity-40"
                            :disabled="!canSubmitSale || form.processing"
                            @click="quotationConfirmOpen = true"
                        >
                            <FileText
                                class="size-4 text-amber-500"
                                aria-hidden="true"
                            />
                            Quotation
                        </button>
                        <button
                            type="button"
                            class="text-foreground hover:bg-muted/80 flex shrink-0 flex-col items-center gap-0.5 rounded-md px-2 py-1 text-xs font-bold"
                            @click="toggleTaxExempt"
                        >
                            <Percent
                                class="size-4 text-amber-700 dark:text-amber-400"
                                aria-hidden="true"
                            />
                            {{ taxExemptActive ? 'Tax on' : 'Tax off' }}
                        </button>
                        <span
                            class="bg-border hidden h-7 w-px shrink-0 md:inline-block"
                            aria-hidden="true"
                        />
                        <Button
                            type="button"
                            class="hidden h-9 min-w-[7.5rem] shrink-0 gap-1.5 rounded-md bg-[#001F3E] px-3 text-xs font-bold text-white hover:bg-[#00152e] md:inline-flex md:items-center md:justify-center md:text-sm"
                            :disabled="!canSubmitSale || form.processing"
                            @click="openPayDialog"
                        >
                            <Banknote class="size-4 shrink-0" />
                            Multiple pay
                        </Button>
                        <Button
                            type="button"
                            class="hidden h-9 min-w-[5.5rem] shrink-0 gap-1.5 rounded-md border-0 bg-[rgb(40,183,123)] px-3 text-xs font-bold text-white hover:bg-[rgb(34,160,108)] md:inline-flex md:min-w-[8.5rem] md:items-center md:justify-center md:text-sm"
                            :disabled="!canSubmitSale || form.processing"
                            @click="quickCash"
                        >
                            <Wallet class="size-4 shrink-0" />
                            Cash
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            class="hidden h-9 min-w-[5.5rem] shrink-0 gap-1 border-2 border-red-400 bg-white px-3 text-xs font-bold text-red-600 hover:bg-red-50 md:inline-flex md:min-w-[8.5rem] md:items-center md:justify-center md:text-sm dark:bg-background dark:hover:bg-red-950/30"
                            :disabled="form.processing"
                            @click="clearCart"
                        >
                            Cancel
                        </Button>
                        <div
                            class="hidden items-center gap-2 px-1 md:flex md:shrink-0"
                        >
                            <span
                                class="text-foreground text-base font-bold whitespace-nowrap md:text-lg"
                                >Amount due</span
                            >
                            <span
                                class="text-xl font-bold tabular-nums text-emerald-800 md:text-[26px] dark:text-emerald-400"
                                >{{ formatMoney(grandTotal) }}</span
                            >
                        </div>
                    </div>
                    <div
                        class="flex w-full shrink-0 items-center justify-center gap-2 border-t border-slate-200 pt-2 sm:hidden dark:border-border"
                    >
                        <Button
                            type="button"
                            class="h-9 min-w-[7rem] flex-1 gap-1 rounded-md bg-[#001F3E] px-2 text-xs font-bold text-white hover:bg-[#00152e]"
                            :disabled="!canSubmitSale || form.processing"
                            @click="openPayDialog"
                        >
                            <Banknote class="size-4 shrink-0" />
                            Multiple pay
                        </Button>
                        <Button
                            type="button"
                            class="h-9 min-w-[5rem] flex-1 gap-1 rounded-md border-0 bg-[rgb(40,183,123)] px-2 text-xs font-bold text-white hover:bg-[rgb(34,160,108)]"
                            :disabled="!canSubmitSale || form.processing"
                            @click="quickCash"
                        >
                            <Wallet class="size-4 shrink-0" />
                            Cash
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            class="h-9 min-w-[5rem] flex-1 gap-1 border-2 border-red-400 bg-white px-2 text-xs font-bold text-red-600 hover:bg-red-50 dark:bg-background"
                            :disabled="form.processing"
                            @click="clearCart"
                        >
                            Cancel
                        </Button>
                    </div>
                </div>
                <div
                    class="flex w-full shrink-0 justify-end sm:w-auto sm:justify-end"
                >
                    <Button
                        type="button"
                        class="h-9 w-full gap-1.5 rounded-full bg-indigo-500 px-5 text-xs font-bold text-white hover:bg-indigo-600 sm:w-auto sm:text-sm md:inline-flex"
                        @click="recentOpen = true"
                    >
                        <Clock class="size-4 shrink-0" />
                        Recent transactions
                    </Button>
                </div>
            </div>
        </footer>

        <Dialog :open="payOpen" @update:open="(v) => (payOpen = v)">
            <DialogContent class="max-h-[90vh] max-w-lg overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Multiple pay</DialogTitle>
                </DialogHeader>
                <div class="grid max-h-[55vh] gap-4 overflow-y-auto py-2 pr-1">
                    <div
                        v-for="(row, idx) in paymentRows"
                        :key="idx"
                        class="bg-muted/40 grid gap-3 rounded-lg border border-border p-3"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span
                                class="text-muted-foreground text-xs font-semibold uppercase tracking-wide"
                                >Payment {{ idx + 1 }}</span
                            >
                            <Button
                                v-if="paymentRows.length > 1"
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="text-destructive h-8 px-2"
                                @click="removePaymentRow(idx)"
                            >
                                Remove
                            </Button>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="grid gap-1.5">
                                <Label>Amount *</Label>
                                <Input
                                    v-model="row.amount"
                                    inputmode="decimal"
                                />
                            </div>
                            <div class="grid gap-1.5">
                                <Label>Method *</Label>
                                <Select v-model="row.method">
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
                            <div class="grid gap-1.5 sm:col-span-2">
                                <Label>Payment account</Label>
                                <Select v-model="row.payment_account_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="None" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="NONE">None</SelectItem>
                                        <SelectItem
                                            v-for="a in accountsForPayMethod(
                                                row.method,
                                            )"
                                            :key="a.id"
                                            :value="String(a.id)"
                                        >
                                            {{ a.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div
                                v-if="row.method === 'bank_transfer'"
                                class="grid gap-1.5 sm:col-span-2"
                            >
                                <Label>Bank reference</Label>
                                <Input v-model="row.bank_account_number" />
                            </div>
                            <div class="grid gap-1.5 sm:col-span-2">
                                <Label>Note</Label>
                                <Input v-model="row.note" />
                            </div>
                        </div>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="w-full"
                        @click="addPaymentRow"
                    >
                        Add payment row
                    </Button>
                    <div
                        class="flex items-center justify-between border-t border-border pt-2 text-sm"
                    >
                        <span class="text-muted-foreground">Rows total</span>
                        <span class="font-semibold tabular-nums">{{
                            formatMoney(paymentRowsTotal)
                        }}</span>
                    </div>
                    <div
                        class="flex items-center justify-between text-sm font-medium"
                    >
                        <span>Amount due</span>
                        <span class="tabular-nums text-emerald-700 dark:text-emerald-400">{{
                            formatMoney(grandTotal)
                        }}</span>
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
                        @click="finalizeMultiPay"
                    >
                        <Spinner v-if="form.processing" />
                        Complete sale
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="draftConfirmOpen"
            @update:open="(v) => (draftConfirmOpen = v)"
        >
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Save draft</DialogTitle>
                </DialogHeader>
                <p class="text-muted-foreground text-sm">
                    Save this sale as a draft so you can finish it later from
                    Sales.
                </p>
                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="draftConfirmOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        :disabled="!canSubmitDraft || form.processing"
                        @click="confirmDraftFromModal"
                    >
                        <Spinner v-if="form.processing" />
                        Save draft
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="quotationConfirmOpen"
            @update:open="(v) => (quotationConfirmOpen = v)"
        >
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Save quotation</DialogTitle>
                </DialogHeader>
                <p class="text-muted-foreground text-sm">
                    Save this sale as a quotation. You can convert it to a final
                    sale later.
                </p>
                <DialogFooter class="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="quotationConfirmOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        :disabled="!canSubmitSale || form.processing"
                        @click="confirmQuotationFromModal"
                    >
                        <Spinner v-if="form.processing" />
                        Save quotation
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog :open="recentOpen" @update:open="(v) => (recentOpen = v)">
            <DialogContent class="max-h-[85vh] max-w-3xl overflow-hidden">
                <DialogHeader>
                    <DialogTitle>Recent transactions</DialogTitle>
                </DialogHeader>
                <div class="flex gap-2 border-b border-border pb-2">
                    <Button
                        type="button"
                        size="sm"
                        :variant="recentTab === 'final' ? 'default' : 'outline'"
                        @click="recentTab = 'final'"
                    >
                        Final
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :variant="
                            recentTab === 'quotation' ? 'default' : 'outline'
                        "
                        @click="recentTab = 'quotation'"
                    >
                        Quotation
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        :variant="recentTab === 'draft' ? 'default' : 'outline'"
                        @click="recentTab = 'draft'"
                    >
                        Draft
                    </Button>
                </div>
                <div class="max-h-[50vh] overflow-y-auto py-2">
                    <div
                        v-if="recentLoading"
                        class="text-muted-foreground py-8 text-center text-sm"
                    >
                        Loading…
                    </div>
                    <table
                        v-else-if="recentData"
                        class="w-full border-collapse text-left text-sm"
                    >
                        <thead>
                            <tr class="border-b border-border">
                                <th class="px-2 py-2 font-medium">Invoice</th>
                                <th class="px-2 py-2 font-medium">When</th>
                                <th class="px-2 py-2 font-medium">Customer</th>
                                <th class="px-2 py-2 text-right font-medium">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="r in recentData[recentTab]"
                                :key="r.id"
                                class="border-b border-border/70"
                            >
                                <td class="px-2 py-1.5 font-mono text-xs">
                                    {{ r.invoice_no || '—' }}
                                </td>
                                <td class="text-muted-foreground px-2 py-1.5 text-xs">
                                    {{
                                        r.transaction_date
                                            ? new Date(
                                                  r.transaction_date,
                                              ).toLocaleString()
                                            : '—'
                                    }}
                                </td>
                                <td class="px-2 py-1.5">
                                    {{ r.customer?.display_name ?? '—' }}
                                </td>
                                <td
                                    class="px-2 py-1.5 text-right font-medium tabular-nums"
                                >
                                    {{ r.final_total }}
                                </td>
                            </tr>
                            <tr v-if="!recentData[recentTab]?.length">
                                <td
                                    colspan="4"
                                    class="text-muted-foreground px-2 py-6 text-center"
                                >
                                    No records.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="recentOpen = false"
                        >Close</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="registerDetailsOpen"
            @update:open="(v) => (registerDetailsOpen = v)"
        >
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Register details</DialogTitle>
                </DialogHeader>
                <div
                    v-if="cashRegisterSession"
                    class="grid gap-3 py-2 text-sm"
                >
                    <div class="flex justify-between gap-2">
                        <span class="text-muted-foreground">Location</span>
                        <span class="font-medium">{{
                            cashRegisterSession.location_name
                        }}</span>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-muted-foreground">Opened</span>
                        <span class="font-medium">{{
                            cashRegisterSession.opened_at
                                ? new Date(
                                      cashRegisterSession.opened_at,
                                  ).toLocaleString()
                                : '—'
                        }}</span>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-muted-foreground">Opening cash</span>
                        <span class="font-medium tabular-nums">{{
                            cashRegisterSession.opening_cash
                        }}</span>
                    </div>
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="registerDetailsOpen = false"
                        >Close</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="editDiscountOpen"
            @update:open="(v) => (editDiscountOpen = v)"
        >
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit discount</DialogTitle>
                </DialogHeader>
                <div class="grid gap-4 py-2">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <div class="grid gap-1.5">
                            <Label>Type</Label>
                            <Select v-model="form.discount_type">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">None</SelectItem>
                                    <SelectItem value="fixed">Fixed amount</SelectItem>
                                    <SelectItem value="percentage"
                                        >Percentage</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="grid gap-1.5">
                            <Label>Amount</Label>
                            <Input
                                v-model="form.discount_amount"
                                inputmode="decimal"
                                placeholder="0"
                            />
                        </div>
                    </div>
                    <p
                        v-if="form.discount_type === 'percentage'"
                        class="text-muted-foreground text-xs"
                    >
                        Percent is applied to the line total before order tax.
                    </p>
                    <p
                        v-if="form.discount_type !== 'none' && headerDiscountAmount > 0"
                        class="text-muted-foreground text-xs"
                    >
                        {{ headerDiscountLabel }} — order discount
                        {{ formatMoney(headerDiscountAmount) }}
                    </p>
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        @click="editDiscountOpen = false"
                    >
                        Done
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog :open="editTaxOpen" @update:open="(v) => (editTaxOpen = v)">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>Edit order tax</DialogTitle>
                </DialogHeader>
                <div class="grid gap-3 py-2">
                    <p
                        v-if="selectedTaxRate"
                        class="text-muted-foreground text-xs"
                    >
                        Tax base (after discount):
                        {{ formatMoney(afterDiscountTotal) }}
                    </p>
                    <div class="grid gap-1.5">
                        <Label>Tax rate</Label>
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
                </div>
                <DialogFooter>
                    <Button type="button" @click="editTaxOpen = false"
                        >Done</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="editShippingOpen"
            @update:open="(v) => (editShippingOpen = v)"
        >
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Shipping</DialogTitle>
                </DialogHeader>
                <div class="grid max-h-[70vh] gap-4 overflow-y-auto py-2 pr-1">
                    <div class="grid gap-1.5 sm:grid-cols-2">
                        <div class="grid gap-1.5 sm:col-span-2">
                            <Label for="pos-ship-charges">Shipping charges</Label>
                            <Input
                                id="pos-ship-charges"
                                v-model="form.shipping_charges"
                                inputmode="decimal"
                            />
                        </div>
                        <div class="grid gap-1.5 sm:col-span-2">
                            <Label for="pos-ship-details">Details</Label>
                            <Input
                                id="pos-ship-details"
                                v-model="form.shipping_details"
                                placeholder="Carrier, reference, etc."
                            />
                        </div>
                        <div class="grid gap-1.5 sm:col-span-2">
                            <Label for="pos-ship-address">Shipping address</Label>
                            <textarea
                                id="pos-ship-address"
                                v-model="form.shipping_address"
                                rows="3"
                                class="border-input bg-background w-full resize-y rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                                placeholder="Optional"
                            />
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" @click="editShippingOpen = false"
                        >Done</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="editPackingOpen"
            @update:open="(v) => (editPackingOpen = v)"
        >
            <DialogContent class="max-w-lg">
                <DialogHeader>
                    <DialogTitle>Packing &amp; other charges</DialogTitle>
                </DialogHeader>
                <div class="grid gap-3 py-2">
                    <p class="text-muted-foreground text-xs">
                        Only rows with a name are included in the sale total.
                    </p>
                    <div
                        v-for="(row, i) in form.additional_expenses"
                        :key="i"
                        class="grid gap-2 sm:grid-cols-2"
                    >
                        <div class="grid gap-1.5">
                            <Label :for="`pos-pack-name-${i}`">Name</Label>
                            <Input
                                :id="`pos-pack-name-${i}`"
                                v-model="row.name"
                                placeholder="e.g. Packing"
                            />
                        </div>
                        <div class="grid gap-1.5">
                            <Label :for="`pos-pack-amt-${i}`">Amount</Label>
                            <Input
                                :id="`pos-pack-amt-${i}`"
                                v-model="row.amount"
                                inputmode="decimal"
                            />
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" @click="editPackingOpen = false"
                        >Done</Button
                    >
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
