<script setup lang="ts">
import {
    Form,
    Head,
    router,
    useForm,
    usePage,
} from '@inertiajs/vue3';
import { useDebounceFn, useStorage } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import StandardFormModal from '@/components/StandardFormModal.vue';
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
import { Spinner } from '@/components/ui/spinner';
import CategoryForm from '@/pages/product-categories/CategoryForm.vue';
import productCategoryRoutes from '@/routes/product-categories';
import type { Team } from '@/types';

type Row = {
    id: number;
    name: string;
    code: string | null;
    description: string | null;
    is_sub_taxonomy: boolean;
    parent: { id: number; name: string } | null;
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

type ParentOption = { id: number; name: string };

type EditingProductCategory = {
    id: number;
    name: string;
    code: string | null;
    description: string | null;
    is_sub_taxonomy: boolean;
    parent_id: number | null;
};

const props = defineProps<{
    productCategories: Paginated;
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
    };
    parentCategories: ParentOption[];
    editingProductCategory: EditingProductCategory | null;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Categories',
                href: productCategoryRoutes.index.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

const search = ref(props.filters.search ?? '');
const perPage = ref(String(props.filters.per_page ?? 15));

type ColId =
    | 'name'
    | 'code'
    | 'description'
    | 'is_sub_taxonomy'
    | 'parent'
    | 'created_at';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    { id: 'name', label: 'Name', sortKey: 'name' },
    { id: 'code', label: 'Code', sortKey: 'code' },
    { id: 'description', label: 'Description', sortKey: null },
    {
        id: 'is_sub_taxonomy',
        label: 'Sub-taxonomy',
        sortKey: 'is_sub_taxonomy',
    },
    { id: 'parent', label: 'Parent', sortKey: null },
    { id: 'created_at', label: 'Created', sortKey: 'created_at' },
];

const columnVisibility = useStorage<Record<string, boolean>>(
    'product-categories.datatable.columns',
    Object.fromEntries(allColumns.map((c) => [c.id, true])),
);

function setColumnVisible(id: string, visible: boolean) {
    columnVisibility.value = { ...columnVisibility.value, [id]: visible };
}

const visibleColumns = computed(() =>
    allColumns.filter((c) => columnVisibility.value[c.id] !== false),
);

function indexQuery(
    overrides: Record<string, string | number | undefined> = {},
): Record<string, string> {
    const q: Record<string, string> = {
        search: search.value,
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

function visitWithFilters(overrides: Record<string, string | number> = {}) {
    router.get(
        productCategoryRoutes.index.url(teamSlug.value),
        indexQuery(overrides),
        {
            preserveState: true,
            replace: true,
        },
    );
}

const debouncedSearch = useDebounceFn(() => visitWithFilters(), 350);

watch(search, () => debouncedSearch());
watch(perPage, (v) => visitWithFilters({ per_page: Number(v) }));

function toggleSort(sortKey: string) {
    const isCurrent = props.filters.sort === sortKey;
    const dir =
        isCurrent && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(
        productCategoryRoutes.index.url(teamSlug.value),
        indexQuery({ sort: sortKey, direction: dir }),
        { preserveState: true, replace: true },
    );
}

const createModalOpen = ref(false);
const editModalOpen = ref(false);

const indexDismissHref = computed(() => {
    const path = productCategoryRoutes.index.url(teamSlug.value);
    const qs = new URLSearchParams(indexQuery()).toString();

    return qs ? `${path}?${qs}` : path;
});

const createForm = useForm({
    name: '',
    code: '',
    description: '',
    is_sub_taxonomy: false,
    parent_id: '',
});

const editForm = useForm({
    name: '',
    code: '',
    description: '',
    is_sub_taxonomy: false,
    parent_id: '',
});

watch(
    () => props.editingProductCategory,
    (c) => {
        if (c) {
            editModalOpen.value = true;
            editForm.name = String(c.name ?? '');
            editForm.code = String(c.code ?? '');
            editForm.description = String(c.description ?? '');
            editForm.is_sub_taxonomy = Boolean(c.is_sub_taxonomy);
            editForm.parent_id =
                c.parent_id != null ? String(c.parent_id) : '';
        } else {
            editModalOpen.value = false;
        }
    },
    { immediate: true },
);

function openCreateModal() {
    createForm.reset();
    createForm.clearErrors();
    createModalOpen.value = true;
}

function openEditModal(row: Row) {
    router.get(
        productCategoryRoutes.index.url(teamSlug.value),
        indexQuery({ edit: row.id }),
        {
            preserveState: true,
            replace: true,
            only: ['editingProductCategory'],
        },
    );
}

function submitCreate() {
    createForm
        .transform((data) => ({
            name: data.name,
            code: data.code?.trim() ? data.code : null,
            description: data.description?.trim() ? data.description : null,
            is_sub_taxonomy: data.is_sub_taxonomy === true,
            parent_id:
                data.is_sub_taxonomy === true && data.parent_id
                    ? Number(data.parent_id)
                    : null,
        }))
        .post(productCategoryRoutes.store.url(teamSlug.value), {
            onSuccess: () => {
                createModalOpen.value = false;
                createForm.reset();
            },
        });
}

function submitEdit() {
    const c = props.editingProductCategory;

    if (!c) {
        return;
    }

    editForm
        .transform((data) => ({
            name: data.name,
            code: data.code?.trim() ? data.code : null,
            description: data.description?.trim() ? data.description : null,
            is_sub_taxonomy: data.is_sub_taxonomy === true,
            parent_id:
                data.is_sub_taxonomy === true && data.parent_id
                    ? Number(data.parent_id)
                    : null,
        }))
        .put(
            productCategoryRoutes.update.url({
                current_team: teamSlug.value,
                product_category: c.id,
            }),
            { onSuccess: () => (editModalOpen.value = false) },
        );
}

function destroyCategory() {
    const c = props.editingProductCategory;

    if (!c || !confirm('Delete this product category?')) {
        return;
    }

    router.delete(
        productCategoryRoutes.destroy.url({
            current_team: teamSlug.value,
            product_category: c.id,
        }),
    );
}

function goToPage(url: string | null) {
    if (!url) {
        return;
    }

    router.visit(url, { preserveState: true, replace: true });
}

function exportUrl(format: string): string {
    return productCategoryRoutes.export.url(
        { current_team: teamSlug.value, format },
        {
            query: {
                search: search.value || undefined,
                sort: props.filters.sort,
                direction: props.filters.direction,
            },
        },
    );
}

function printTable() {
    window.print();
}

function displayCell(row: Row, col: ColId): string {
    switch (col) {
        case 'name':
            return row.name;
        case 'code':
            return row.code?.trim() ? row.code : '—';
        case 'description':
            return row.description?.trim() ? row.description : '—';
        case 'is_sub_taxonomy':
            return row.is_sub_taxonomy ? 'Yes' : 'No';
        case 'parent':
            return row.parent?.name ?? '—';
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
    <Head title="Product categories" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div
            class="flex flex-col gap-4 print:hidden md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Product categories
                </h1>
                <p class="text-sm text-muted-foreground">
                    Hierarchy-friendly categories, exports, and sorting.
                </p>
            </div>
            <Button type="button" @click="openCreateModal">
                Add category
            </Button>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Name, code, description…"
            :paginator="productCategories"
            @page="goToPage"
        >
            <template #toolbar-actions>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button type="button" variant="outline" size="sm">
                            Columns
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-52">
                        <DropdownMenuLabel>Toggle columns</DropdownMenuLabel>
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

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button type="button" variant="outline" size="sm">
                            Export
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem as-child>
                            <a :href="exportUrl('xlsx')">Excel (.xlsx)</a>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <a :href="exportUrl('csv')">CSV</a>
                        </DropdownMenuItem>
                        <DropdownMenuItem as-child>
                            <a :href="exportUrl('pdf')">PDF</a>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="printTable"
                >
                    Print
                </Button>
            </template>

            <table class="w-full min-w-[720px] border-collapse text-sm">
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
                                class="bg-muted/40 px-3 py-2 text-right font-medium print:hidden"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in productCategories.data ?? []"
                            :key="row.id"
                            class="border-b border-border/80 hover:bg-muted/20"
                        >
                            <td
                                v-for="col in visibleColumns"
                                :key="col.id"
                                class="px-3 py-2 align-middle"
                            >
                                {{ displayCell(row, col.id) }}
                            </td>
                            <td class="px-3 py-2 text-right print:hidden">
                                <button
                                    type="button"
                                    class="text-primary text-sm font-medium hover:underline"
                                    @click="openEditModal(row)"
                                >
                                    Edit
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!(productCategories?.data?.length)">
                            <td
                                :colspan="visibleColumns.length + 1"
                                class="px-3 py-8 text-center text-muted-foreground"
                            >
                                No categories match your filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
        </StandardDataTable>

        <StandardFormModal
            v-model:open="createModalOpen"
            title="Add product category"
            description="Top-level categories or sub-taxonomies with a parent."
            size="lg"
            :dismiss-href="indexDismissHref"
        >
            <Form class="contents" @submit.prevent="submitCreate">
                <CategoryForm
                    :form="createForm"
                    :parent-categories="props.parentCategories"
                />
            </Form>
            <template #footer>
                <div class="flex w-full flex-wrap justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="router.visit(indexDismissHref)"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        :disabled="createForm.processing"
                        @click="submitCreate"
                    >
                        <Spinner v-if="createForm.processing" />
                        Save
                    </Button>
                </div>
            </template>
        </StandardFormModal>

        <StandardFormModal
            v-model:open="editModalOpen"
            title="Edit product category"
            :description="editingProductCategory?.name"
            size="lg"
            :dismiss-href="indexDismissHref"
        >
            <Form
                v-if="editingProductCategory"
                class="contents"
                @submit.prevent="submitEdit"
            >
                <CategoryForm
                    :form="editForm"
                    :parent-categories="props.parentCategories"
                />
            </Form>
            <template #footer>
                <div class="flex w-full flex-wrap items-center justify-between gap-2">
                    <Button
                        type="button"
                        variant="destructive"
                        @click="destroyCategory"
                    >
                        Delete
                    </Button>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="router.visit(indexDismissHref)"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            :disabled="editForm.processing"
                            @click="submitEdit"
                        >
                            <Spinner v-if="editForm.processing" />
                            Update
                        </Button>
                    </div>
                </div>
            </template>
        </StandardFormModal>
    </div>
</template>

<style scoped>
@media print {
    .print\:hidden {
        display: none !important;
    }
}
</style>
