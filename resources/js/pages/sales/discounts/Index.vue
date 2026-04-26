<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Plus } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
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
import productRoutes from '@/routes/products';
import salesRoutes from '@/routes/sales';
import type { Team } from '@/types';

type DiscountRow = {
    id: number;
    name: string;
    priority: number;
    discount_type: string;
    discount_amount: string;
    starts_at: string | null;
    ends_at: string | null;
    is_active: boolean;
    applicable_in_customer_groups: boolean;
    business_location: { id: number; name: string } | null;
    brand: { id: number; name: string } | null;
    category: { id: number; name: string } | null;
    selling_price_group: { id: number; name: string } | null;
    products_count: number;
};

type Paginated = {
    data: DiscountRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type Option = { id: number; name: string };

const props = defineProps<{
    discounts: Paginated;
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
    };
    brands: Option[];
    productCategories: Option[];
    businessLocations: Option[];
    sellingPriceGroups: Option[];
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Sales',
                href: salesRoutes.index.url(p.currentTeam!.slug),
            },
            {
                title: 'Discounts',
                href: salesRoutes.discounts.index.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

const posPermissions = computed<string[]>(() => {
    const value = page.props.posPermissions;
    return Array.isArray(value) ? (value as string[]) : [];
});
const canDiscountAccess = computed(() =>
    posPermissions.value.includes('discount.access'),
);

const listSearch = ref(props.filters.search ?? '');
const listPerPage = ref(String(props.filters.per_page ?? 15));

type DiscountColId =
    | 'name'
    | 'location'
    | 'priority'
    | 'type'
    | 'amount'
    | 'products'
    | 'active';

const discountListColumns: {
    id: DiscountColId;
    label: string;
    sortKey: string | null;
}[] = [
    { id: 'name', label: 'Name', sortKey: 'name' },
    { id: 'location', label: 'Location', sortKey: null },
    { id: 'priority', label: 'Priority', sortKey: 'priority' },
    { id: 'type', label: 'Type', sortKey: null },
    { id: 'amount', label: 'Amount', sortKey: 'discount_amount' },
    { id: 'products', label: 'Products', sortKey: null },
    { id: 'active', label: 'Active', sortKey: 'is_active' },
];

function discountIndexQuery(
    overrides: Record<string, string | number | undefined> = {},
): Record<string, string> {
    const q: Record<string, string> = {
        search: listSearch.value,
        sort: props.filters.sort,
        direction: props.filters.direction,
        per_page: String(overrides.per_page ?? props.filters.per_page),
    };

    for (const [k, v] of Object.entries(overrides)) {
        if (v !== undefined && v !== '') {
            q[k] = String(v);
        }
    }

    return q;
}

function visitDiscountIndex(overrides: Record<string, string | number> = {}) {
    router.get(
        salesRoutes.discounts.index.url(teamSlug.value),
        discountIndexQuery(overrides),
        { preserveState: true, replace: true },
    );
}

const debouncedDiscountIndexSearch = useDebounceFn(
    () => visitDiscountIndex(),
    350,
);
watch(listSearch, () => debouncedDiscountIndexSearch());
watch(listPerPage, (v) => visitDiscountIndex({ per_page: Number(v) }));

function toggleDiscountSort(sortKey: string) {
    const isCurrent = props.filters.sort === sortKey;
    const dir =
        isCurrent && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(
        salesRoutes.discounts.index.url(teamSlug.value),
        discountIndexQuery({ sort: sortKey, direction: dir }),
        { preserveState: true, replace: true },
    );
}

function discountSortIndicator(sortKey: string | null): string {
    if (!sortKey || props.filters.sort !== sortKey) {
        return '';
    }

    return props.filters.direction === 'asc' ? ' ↑' : ' ↓';
}

function typeLabel(t: string): string {
    return t === 'percentage' ? '%' : t === 'fixed' ? 'Fixed' : t;
}

function displayDiscountCell(row: DiscountRow, col: DiscountColId): string {
    if (col === 'name') {
        return row.name;
    }

    if (col === 'location') {
        return row.business_location?.name ?? '—';
    }

    if (col === 'priority') {
        return String(row.priority);
    }

    if (col === 'type') {
        return typeLabel(row.discount_type);
    }

    if (col === 'amount') {
        return row.discount_amount;
    }

    if (col === 'products') {
        return `${row.products_count} selected`;
    }

    if (col === 'active') {
        return row.is_active ? 'Yes' : 'No';
    }

    return '—';
}

const modalOpen = ref(false);

/** Radix Select rejects `value=""`; use a sentinel for “any / all”. */
const SELECT_NONE = '__none__';

type ProductHit = { id: number; name: string; sku: string | null };
const productQuery = ref('');
const productHits = ref<ProductHit[]>([]);
const productSearchLoading = ref(false);
const selectedProducts = ref<Map<number, ProductHit>>(new Map());

const form = useForm({
    name: '',
    product_ids: [] as number[],
    brand_id: SELECT_NONE,
    product_category_id: SELECT_NONE,
    business_location_id: '',
    priority: '0',
    discount_type: '' as '' | 'fixed' | 'percentage',
    discount_amount: '',
    starts_at: '',
    ends_at: '',
    selling_price_group_id: SELECT_NONE,
    applicable_in_customer_groups: false,
    is_active: true,
});

function resetModal() {
    form.reset();
    form.clearErrors();
    form.product_ids = [];
    form.priority = '0';
    form.is_active = true;
    form.applicable_in_customer_groups = false;
    productQuery.value = '';
    productHits.value = [];
    selectedProducts.value = new Map();
}

function openModal() {
    resetModal();
    modalOpen.value = true;
}

watch(modalOpen, (open) => {
    if (!open) {
        nextTick(() => resetModal());
    }
});

function syncProductIdsFromMap() {
    form.product_ids = [...selectedProducts.value.keys()];
}

function addProduct(p: ProductHit) {
    selectedProducts.value.set(p.id, p);
    selectedProducts.value = new Map(selectedProducts.value);
    syncProductIdsFromMap();
}

function removeProduct(id: number) {
    selectedProducts.value.delete(id);
    selectedProducts.value = new Map(selectedProducts.value);
    syncProductIdsFromMap();
}

async function searchProducts() {
    if (!form.business_location_id) {
        productHits.value = [];

        return;
    }

    productSearchLoading.value = true;

    try {
        const url = productRoutes.search.url(teamSlug.value, {
            query: {
                q: productQuery.value,
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
        const j = (await r.json()) as { data: ProductHit[] };
        productHits.value = j.data ?? [];
    } catch {
        productHits.value = [];
    } finally {
        productSearchLoading.value = false;
    }
}

const debouncedProductSearch = useDebounceFn(searchProducts, 300);
watch([productQuery, () => form.business_location_id], () => {
    debouncedProductSearch();
});

function toLaravelDateTime(dtLocal: string): string | null {
    if (!dtLocal.trim()) {
        return null;
    }

    if (!dtLocal.includes('T')) {
        return dtLocal;
    }

    const [date, time] = dtLocal.split('T');
    const t = (time ?? '00:00').slice(0, 5);

    return `${date} ${t}:00`;
}

function optionalFk(v: string): number | null {
    if (v === '' || v === SELECT_NONE) {
        return null;
    }

    const n = Number(v);

    return Number.isFinite(n) ? n : null;
}

function submitDiscount() {
    form
        .transform((d) => ({
            name: d.name,
            product_ids: d.product_ids,
            brand_id: optionalFk(d.brand_id),
            product_category_id: optionalFk(d.product_category_id),
            business_location_id: Number(d.business_location_id),
            priority: Number(d.priority),
            discount_type: d.discount_type,
            discount_amount: Number(d.discount_amount),
            starts_at: toLaravelDateTime(d.starts_at),
            ends_at: toLaravelDateTime(d.ends_at),
            selling_price_group_id: optionalFk(d.selling_price_group_id),
            applicable_in_customer_groups: d.applicable_in_customer_groups,
            is_active: d.is_active,
        }))
        .post(salesRoutes.discounts.store.url(teamSlug.value), {
            preserveScroll: true,
            onSuccess: () => {
                modalOpen.value = false;
                nextTick(() => resetModal());
            },
        });
}

function goToPage(url: string | null) {
    if (url) {
        router.visit(url, { preserveState: true, replace: true });
    }
}
</script>

<template>
    <Head title="Discounts" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Discounts</h1>
                <p class="text-muted-foreground text-sm">
                    Promotional pricing rules by location, brand, category, or
                    specific products.
                </p>
            </div>
            <Button
                v-if="canDiscountAccess"
                type="button"
                @click="openModal"
            >
                <Plus class="mr-1 size-4" />
                Add discount
            </Button>
        </div>

        <StandardDataTable
            v-model:search="listSearch"
            v-model:per-page="listPerPage"
            search-placeholder="Name, location, brand…"
            :per-page-options="[10, 15, 25, 50]"
            :paginator="discounts"
            @page="goToPage"
        >
            <table class="w-full min-w-[880px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-border">
                        <th
                            v-for="col in discountListColumns"
                            :key="col.id"
                            class="bg-muted/40 px-3 py-2 font-medium"
                            :class="{
                                'text-left':
                                    col.id !== 'priority' &&
                                    col.id !== 'active' &&
                                    col.id !== 'amount',
                                'text-center':
                                    col.id === 'priority' || col.id === 'active',
                                'text-right': col.id === 'amount',
                            }"
                        >
                            <button
                                v-if="col.sortKey"
                                type="button"
                                class="inline-flex items-center gap-1 hover:text-primary"
                                :class="{
                                    'mx-auto flex':
                                        col.id === 'priority' ||
                                        col.id === 'active',
                                    'float-right': col.id === 'amount',
                                }"
                                @click="toggleDiscountSort(col.sortKey)"
                            >
                                {{ col.label
                                }}<span class="text-xs text-muted-foreground">{{
                                    discountSortIndicator(col.sortKey)
                                }}</span>
                            </button>
                            <span v-else>{{ col.label }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in discounts.data ?? []"
                        :key="row.id"
                        class="border-b border-border/80 hover:bg-muted/20"
                    >
                        <td
                            v-for="col in discountListColumns"
                            :key="col.id"
                            class="px-3 py-2"
                            :class="{
                                'font-medium': col.id === 'name',
                                'text-center':
                                    col.id === 'priority' || col.id === 'active',
                                'text-right': col.id === 'amount',
                                'text-muted-foreground': col.id === 'products',
                            }"
                        >
                            {{ displayDiscountCell(row, col.id) }}
                        </td>
                    </tr>
                    <tr v-if="!(discounts?.data?.length)">
                        <td
                            :colspan="discountListColumns.length"
                            class="text-muted-foreground px-3 py-8 text-center"
                        >
                            No discounts yet. Use “Add discount” to create one.
                        </td>
                    </tr>
                </tbody>
            </table>
        </StandardDataTable>
    </div>

    <Dialog v-model:open="modalOpen">
        <DialogContent class="max-h-[90vh] max-w-2xl overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Add discount</DialogTitle>
                <DialogDescription>
                    Higher priority rules are evaluated first when multiple
                    discounts could apply.
                </DialogDescription>
            </DialogHeader>

            <form class="grid gap-4" @submit.prevent="submitDiscount">
                <div class="grid gap-2">
                    <Label for="d-name">Name *</Label>
                    <Input
                        id="d-name"
                        v-model="form.name"
                        required
                        placeholder="Name"
                        autocomplete="off"
                    />
                    <p v-if="form.errors.name" class="text-destructive text-xs">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label>Location *</Label>
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
                    <p
                        v-if="form.errors.business_location_id"
                        class="text-destructive text-xs"
                    >
                        {{ form.errors.business_location_id }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label>Products</Label>
                    <p class="text-muted-foreground text-xs">
                        Search sellable products for this location. Leave empty to
                        match by brand / category only.
                    </p>
                    <Input
                        v-model="productQuery"
                        placeholder="Search products…"
                        :disabled="!form.business_location_id"
                    />
                    <div
                        v-if="productSearchLoading"
                        class="text-muted-foreground flex items-center gap-2 text-xs"
                    >
                        <Spinner class="size-3" /> Searching…
                    </div>
                    <div
                        v-else-if="form.business_location_id && productHits.length"
                        class="max-h-36 overflow-y-auto rounded-md border"
                    >
                        <button
                            v-for="p in productHits"
                            :key="p.id"
                            type="button"
                            class="hover:bg-muted/50 flex w-full items-center justify-between border-b px-2 py-1.5 text-left text-sm last:border-0"
                            @click="addProduct(p)"
                        >
                            <span>{{ p.name }}</span>
                            <span class="text-muted-foreground text-xs">{{
                                p.sku ?? '—'
                            }}</span>
                        </button>
                    </div>
                    <div
                        v-if="selectedProducts.size"
                        class="flex flex-wrap gap-1.5"
                    >
                        <span
                            v-for="[id, p] in selectedProducts"
                            :key="id"
                            class="bg-muted inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs"
                        >
                            {{ p.name }}
                            <button
                                type="button"
                                class="text-muted-foreground hover:text-foreground"
                                aria-label="Remove"
                                @click="removeProduct(id)"
                            >
                                ×
                            </button>
                        </span>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Brand</Label>
                        <Select v-model="form.brand_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Any brand" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="SELECT_NONE">Any brand</SelectItem>
                                <SelectItem
                                    v-for="b in brands"
                                    :key="b.id"
                                    :value="String(b.id)"
                                >
                                    {{ b.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label>Category</Label>
                        <Select v-model="form.product_category_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Any category" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="SELECT_NONE">
                                    Any category
                                </SelectItem>
                                <SelectItem
                                    v-for="c in productCategories"
                                    :key="c.id"
                                    :value="String(c.id)"
                                >
                                    {{ c.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="d-pri">Priority *</Label>
                    <p class="text-muted-foreground text-xs">
                        Higher numbers are preferred when several discounts overlap.
                    </p>
                    <Input
                        id="d-pri"
                        v-model="form.priority"
                        type="number"
                        min="0"
                        required
                    />
                    <p v-if="form.errors.priority" class="text-destructive text-xs">
                        {{ form.errors.priority }}
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label>Discount type *</Label>
                        <Select v-model="form.discount_type" required>
                            <SelectTrigger>
                                <SelectValue placeholder="Select type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="fixed">Fixed</SelectItem>
                                <SelectItem value="percentage">Percentage</SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="form.errors.discount_type"
                            class="text-destructive text-xs"
                        >
                            {{ form.errors.discount_type }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="d-amt">Discount amount *</Label>
                        <Input
                            id="d-amt"
                            v-model="form.discount_amount"
                            type="text"
                            inputmode="decimal"
                            required
                            placeholder="Amount"
                        />
                        <p
                            v-if="form.errors.discount_amount"
                            class="text-destructive text-xs"
                        >
                            {{ form.errors.discount_amount }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="d-start">Starts at</Label>
                        <Input
                            id="d-start"
                            v-model="form.starts_at"
                            type="datetime-local"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label for="d-end">Ends at</Label>
                        <Input
                            id="d-end"
                            v-model="form.ends_at"
                            type="datetime-local"
                        />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label>Selling price group</Label>
                    <Select v-model="form.selling_price_group_id">
                        <SelectTrigger>
                            <SelectValue placeholder="All groups" />
                        </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="SELECT_NONE">All</SelectItem>
                            <SelectItem
                                v-for="g in sellingPriceGroups"
                                :key="g.id"
                                :value="String(g.id)"
                            >
                                {{ g.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <label
                        class="flex cursor-pointer items-center gap-2 text-sm"
                    >
                        <Checkbox
                            :checked="form.applicable_in_customer_groups"
                            @update:checked="
                                (v: boolean | 'indeterminate') =>
                                    (form.applicable_in_customer_groups = !!v)
                            "
                        />
                        Apply in customer groups
                    </label>
                    <label
                        class="flex cursor-pointer items-center gap-2 text-sm"
                    >
                        <Checkbox
                            :checked="form.is_active"
                            @update:checked="
                                (v: boolean | 'indeterminate') =>
                                    (form.is_active = !!v)
                            "
                        />
                        Is active
                    </label>
                </div>

                <p
                    v-if="form.hasErrors && form.errors.product_ids"
                    class="text-destructive text-sm"
                >
                    {{ form.errors.product_ids }}
                </p>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="modalOpen = false"
                    >
                        Close
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-2 size-4" />
                        {{ form.processing ? 'Saving…' : 'Save' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
