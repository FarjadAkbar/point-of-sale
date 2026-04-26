<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useDebounceFn, useStorage } from '@vueuse/core';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import productRoutes from '@/routes/products';
import type { Team } from '@/types';

type Row = {
    id: number;
    name: string;
    sku: string | null;
    product_type: string;
    not_for_selling: boolean;
    manage_stock: boolean;
    stock_total: string | null;
    single_dsp: string | null;
    combo_selling_price: string | null;
    unit: { id: number; name: string; short_name: string } | null;
    brand: { id: number; name: string } | null;
    category: { id: number; name: string } | null;
    image_url: string | null;
    created_at: string | null;
};

type Paginated = {
    data: Row[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    products: Paginated;
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
        product_type: string;
    };
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            { title: 'Products', href: productRoutes.index.url(p.currentTeam!.slug) },
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
const hasProductPermission = (permission: string): boolean =>
    posPermissions.value.includes(permission);
const canCreateProduct = computed(() => hasProductPermission('product.create'));
const canUpdateProduct = computed(() => hasProductPermission('product.update'));
const canDeleteProduct = computed(() => hasProductPermission('product.delete'));
const showActionColumn = computed(
    () => canUpdateProduct.value || canDeleteProduct.value,
);

const search = ref(props.filters.search ?? '');
const typeFilter = ref(props.filters.product_type || 'all');
const perPage = ref(String(props.filters.per_page ?? 15));

type ColId =
    | 'name'
    | 'sku'
    | 'product_type'
    | 'stock'
    | 'price'
    | 'unit'
    | 'brand'
    | 'created_at';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    { id: 'name', label: 'Name', sortKey: 'name' },
    { id: 'sku', label: 'SKU', sortKey: 'sku' },
    { id: 'product_type', label: 'Type', sortKey: 'product_type' },
    { id: 'stock', label: 'Stock', sortKey: null },
    { id: 'price', label: 'Selling price', sortKey: null },
    { id: 'unit', label: 'Unit', sortKey: null },
    { id: 'brand', label: 'Brand', sortKey: null },
    { id: 'created_at', label: 'Created', sortKey: 'created_at' },
];

const columnVisibility = useStorage<Record<string, boolean>>(
    'products.datatable.columns',
    Object.fromEntries(allColumns.map((c) => [c.id, true])),
);

function setColumnVisible(id: string, visible: boolean) {
    columnVisibility.value = { ...columnVisibility.value, [id]: visible };
}

const visibleColumns = computed(() =>
    allColumns.filter((c) => columnVisibility.value[c.id] !== false),
);

function visitWithFilters(overrides: Record<string, string | number> = {}) {
    router.get(
        productRoutes.index.url(teamSlug.value),
        {
            search: search.value,
            sort: props.filters.sort,
            direction: props.filters.direction,
            per_page: String(overrides.per_page ?? props.filters.per_page),
            product_type: typeFilter.value === 'all' ? '' : typeFilter.value,
            ...Object.fromEntries(
                Object.entries(overrides).map(([k, v]) => [k, String(v)]),
            ),
        },
        { preserveState: true, replace: true },
    );
}

const debouncedSearch = useDebounceFn(() => visitWithFilters(), 350);
watch(search, () => debouncedSearch());
watch(typeFilter, () => visitWithFilters());
watch(perPage, (v) => visitWithFilters({ per_page: Number(v) }));

function toggleSort(sortKey: string) {
    const isCurrent = props.filters.sort === sortKey;
    const dir =
        isCurrent && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(
        productRoutes.index.url(teamSlug.value),
        {
            search: search.value,
            sort: sortKey,
            direction: dir,
            per_page: String(props.filters.per_page),
            product_type: typeFilter.value === 'all' ? '' : typeFilter.value,
        },
        { preserveState: true, replace: true },
    );
}

function goToPage(url: string | null) {
    if (url) {
        router.visit(url, { preserveState: true, replace: true });
    }
}

function importCsv() {
    if (!canCreateProduct.value && !canUpdateProduct.value) {
        return;
    }
    router.post(productRoutes.import.csv.url(teamSlug.value));
}

function importXlsx() {
    if (!canCreateProduct.value && !canUpdateProduct.value) {
        return;
    }
    router.post(productRoutes.import.xlsx.url(teamSlug.value));
}

function destroyProduct(row: Row) {
    if (!canDeleteProduct.value) {
        return;
    }

    if (
        !confirm(
            `Delete “${row.name}”? This cannot be undone if the product is not referenced elsewhere.`,
        )
    ) {
        return;
    }

    router.delete(
        productRoutes.destroy.url({
            current_team: teamSlug.value,
            product: row.id,
        }),
    );
}

function displayPrice(row: Row): string {
    if (row.product_type === 'combo' && row.combo_selling_price) {
        return row.combo_selling_price;
    }

    if (row.single_dsp) {
        return row.single_dsp;
    }

    return '—';
}

function displayCell(row: Row, col: ColId): string {
    switch (col) {
        case 'name':
            return row.name;
        case 'sku':
            return row.sku ?? '—';
        case 'product_type':
            return row.product_type;
        case 'stock':
            return row.manage_stock ? (row.stock_total ?? '0') : '—';
        case 'price':
            return displayPrice(row);
        case 'unit':
            return row.unit
                ? `${row.unit.name} (${row.unit.short_name})`
                : '—';
        case 'brand':
            return row.brand?.name ?? '—';
        case 'created_at':
            return row.created_at
                ? new Date(row.created_at).toLocaleDateString()
                : '—';
        default:
            return '';
    }
}

function sortIndicator(sortKey: string | null): string {
    if (!sortKey || props.filters.sort !== sortKey) {
        return '';
    }

    return props.filters.direction === 'asc' ? ' ↑' : ' ↓';
}
</script>

<template>
    <Head title="Products" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Products</h1>
                <p class="text-sm text-muted-foreground">
                    List, import, and open the add-product flow.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="!canCreateProduct && !canUpdateProduct"
                        >
                            Import
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuLabel>Bulk import</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem @click="importCsv">
                            CSV (stub)
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="importXlsx">
                            Excel (stub)
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
                <Button v-if="canCreateProduct" as-child>
                    <Link :href="productRoutes.create.url(teamSlug)">
                        Add product
                    </Link>
                </Button>
            </div>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Name or SKU…"
            :per-page-options="[10, 15, 25, 50]"
            :paginator="products"
            @page="goToPage"
        >
            <template #filters>
                <Select v-model="typeFilter">
                    <SelectTrigger class="w-full" aria-label="Product type">
                        <SelectValue placeholder="All" />
                    </SelectTrigger>
                    <SelectContent
                        position="popper"
                        side="bottom"
                        align="start"
                        :side-offset="4"
                    >
                        <SelectItem value="all">All</SelectItem>
                        <SelectItem value="single">Single</SelectItem>
                        <SelectItem value="variation">Variation</SelectItem>
                        <SelectItem value="combo">Combo</SelectItem>
                    </SelectContent>
                </Select>
            </template>
            <template #toolbar-actions>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button type="button" variant="outline" size="sm">
                            Columns
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-52">
                        <DropdownMenuLabel>Columns</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuCheckboxItem
                            v-for="col in allColumns"
                            :key="col.id"
                            :model-value="columnVisibility[col.id] !== false"
                            @update:model-value="
                                (v) => setColumnVisible(col.id, !!v)
                            "
                        >
                            {{ col.label }}
                        </DropdownMenuCheckboxItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </template>

            <table class="w-full min-w-[880px] border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-border">
                            <th
                                v-for="col in visibleColumns"
                                :key="col.id"
                                class="bg-muted/40 px-3 py-2 text-left font-medium"
                            >
                                <button
                                    v-if="col.sortKey"
                                    type="button"
                                    class="inline-flex items-center gap-1 hover:text-primary"
                                    @click="toggleSort(col.sortKey)"
                                >
                                    {{ col.label
                                    }}<span class="text-xs text-muted-foreground">{{
                                        sortIndicator(col.sortKey)
                                    }}</span>
                                </button>
                                <span v-else>{{ col.label }}</span>
                            </th>
                            <th
                                v-if="showActionColumn"
                                class="bg-muted/40 px-3 py-2 text-right font-medium"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in products.data ?? []"
                            :key="row.id"
                            class="border-b border-border/80 hover:bg-muted/20"
                        >
                            <td
                                v-for="col in visibleColumns"
                                :key="col.id"
                                class="px-3 py-2 align-middle"
                            >
                                <div
                                    v-if="col.id === 'name' && row.image_url"
                                    class="flex items-center gap-2"
                                >
                                    <img
                                        :src="row.image_url"
                                        alt=""
                                        class="size-8 rounded border object-cover"
                                    />
                                    <span>{{ row.name }}</span>
                                </div>
                                <template v-else>
                                    {{ displayCell(row, col.id) }}
                                </template>
                            </td>
                            <td
                                v-if="showActionColumn"
                                class="px-3 py-2 text-right"
                            >
                                <div
                                    class="flex flex-wrap items-center justify-end gap-0.5"
                                >
                                    <Button
                                        v-if="canUpdateProduct"
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-primary hover:text-primary"
                                        as-child
                                    >
                                        <Link
                                            :href="
                                                productRoutes.edit.url({
                                                    current_team: teamSlug,
                                                    product: row.id,
                                                })
                                            "
                                            aria-label="Edit"
                                            title="Edit"
                                        >
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="canDeleteProduct"
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-destructive hover:text-destructive"
                                        aria-label="Delete"
                                        title="Delete"
                                        @click="destroyProduct(row)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!(products?.data?.length)">
                            <td
                                :colspan="visibleColumns.length + (showActionColumn ? 1 : 0)"
                                class="px-3 py-8 text-center text-muted-foreground"
                            >
                                No products yet. Use “Add product” to create one.
                            </td>
                        </tr>
                    </tbody>
                </table>
        </StandardDataTable>
    </div>
</template>
