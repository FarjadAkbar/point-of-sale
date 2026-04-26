<script setup lang="ts">
import {
    Form,
    Head,
    router,
    useForm,
    usePage,
} from '@inertiajs/vue3';
import { useDebounceFn, useStorage } from '@vueuse/core';
import { Pencil, Trash2 } from 'lucide-vue-next';
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
import BrandForm from '@/pages/brands/BrandForm.vue';
import brandRoutes from '@/routes/brands';
import type { Team } from '@/types';

type Row = {
    id: number;
    name: string;
    description: string | null;
    user_for_repair: boolean;
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

type EditingBrand = {
    id: number;
    name: string;
    description: string | null;
    user_for_repair: boolean;
};

const props = defineProps<{
    brands: Paginated;
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
    };
    editingBrand: EditingBrand | null;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            { title: 'Brands', href: brandRoutes.index.url(p.currentTeam!.slug) },
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
const hasBrandPermission = (permission: string): boolean =>
    posPermissions.value.includes(permission);
const canCreateBrand = computed(() => hasBrandPermission('brand.create'));
const canUpdateBrand = computed(() => hasBrandPermission('brand.update'));
const canDeleteBrand = computed(() => hasBrandPermission('brand.delete'));
const showActionColumn = computed(
    () => canUpdateBrand.value || canDeleteBrand.value,
);

const search = ref(props.filters.search ?? '');
const perPage = ref(String(props.filters.per_page ?? 15));

type ColId = 'name' | 'description' | 'user_for_repair' | 'created_at';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    { id: 'name', label: 'Name', sortKey: 'name' },
    { id: 'description', label: 'Description', sortKey: null },
    {
        id: 'user_for_repair',
        label: 'User for repair',
        sortKey: 'user_for_repair',
    },
    { id: 'created_at', label: 'Created', sortKey: 'created_at' },
];

const columnVisibility = useStorage<Record<string, boolean>>(
    'brands.datatable.columns',
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
    router.get(brandRoutes.index.url(teamSlug.value), indexQuery(overrides), {
        preserveState: true,
        replace: true,
    });
}

const debouncedSearch = useDebounceFn(() => visitWithFilters(), 350);

watch(search, () => debouncedSearch());
watch(perPage, (v) => visitWithFilters({ per_page: Number(v) }));

function toggleSort(sortKey: string) {
    const isCurrent = props.filters.sort === sortKey;
    const dir =
        isCurrent && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(
        brandRoutes.index.url(teamSlug.value),
        indexQuery({ sort: sortKey, direction: dir }),
        { preserveState: true, replace: true },
    );
}

const createModalOpen = ref(false);
const editModalOpen = ref(false);

const indexDismissHref = computed(() => {
    const path = brandRoutes.index.url(teamSlug.value);
    const qs = new URLSearchParams(indexQuery()).toString();

    return qs ? `${path}?${qs}` : path;
});

const createForm = useForm({
    name: '',
    description: '',
    user_for_repair: false,
});

const editForm = useForm({
    name: '',
    description: '',
    user_for_repair: false,
});

watch(
    () => props.editingBrand,
    (b) => {
        if (b) {
            editModalOpen.value = true;
            editForm.name = String(b.name ?? '');
            editForm.description = String(b.description ?? '');
            editForm.user_for_repair = Boolean(b.user_for_repair);
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
    if (!canUpdateBrand.value) {
        return;
    }

    router.get(
        brandRoutes.index.url(teamSlug.value),
        indexQuery({ edit: row.id }),
        {
            preserveState: true,
            replace: true,
            only: ['editingBrand'],
        },
    );
}

function submitCreate() {
    createForm
        .transform((data) => ({
            name: data.name,
            description: data.description || null,
            user_for_repair: data.user_for_repair === true,
        }))
        .post(brandRoutes.store.url(teamSlug.value), {
            onSuccess: () => {
                createModalOpen.value = false;
                createForm.reset();
            },
        });
}

function submitEdit() {
    const b = props.editingBrand;

    if (!b) {
        return;
    }

    editForm
        .transform((data) => ({
            name: data.name,
            description: data.description || null,
            user_for_repair: data.user_for_repair === true,
        }))
        .put(
            brandRoutes.update.url({
                current_team: teamSlug.value,
                brand: b.id,
            }),
            {
                onSuccess: () => {
                    editModalOpen.value = false;
                },
            },
        );
}

function destroyBrand(row: Row) {
    if (!canDeleteBrand.value) {
        return;
    }

    if (!confirm('Delete this brand?')) {
        return;
    }

    router.delete(
        brandRoutes.destroy.url({
            current_team: teamSlug.value,
            brand: row.id,
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
    return brandRoutes.export.url(
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
        case 'description':
            return row.description?.trim() ? row.description : '—';
        case 'user_for_repair':
            return row.user_for_repair ? 'Yes' : 'No';
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
    <Head title="Brands" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div
            class="flex flex-col gap-4 print:hidden md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Brands</h1>
                <p class="text-sm text-muted-foreground">
                    Brand catalog, exports, and sorting.
                </p>
            </div>
            <Button v-if="canCreateBrand" type="button" @click="openCreateModal">
                Add brand
            </Button>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Name or description…"
            :paginator="brands"
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

            <table class="w-full min-w-[640px] border-collapse text-sm">
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
                                class="bg-muted/40 px-3 py-2 text-right font-medium print:hidden"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in brands.data ?? []"
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
                            <td
                                v-if="showActionColumn"
                                class="px-3 py-2 text-right print:hidden"
                            >
                                <div
                                    class="flex flex-wrap items-center justify-end gap-0.5"
                                >
                                    <Button
                                        v-if="canUpdateBrand"
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-primary hover:text-primary"
                                        aria-label="Edit"
                                        title="Edit"
                                        @click="openEditModal(row)"
                                    >
                                        <Pencil />
                                    </Button>
                                    <Button
                                        v-if="canDeleteBrand"
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-destructive hover:text-destructive"
                                        aria-label="Delete"
                                        title="Delete"
                                        @click="destroyBrand(row)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!(brands?.data?.length)">
                            <td
                                :colspan="visibleColumns.length + (showActionColumn ? 1 : 0)"
                                class="px-3 py-8 text-center text-muted-foreground"
                            >
                                No brands match your filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
        </StandardDataTable>

        <StandardFormModal
            v-if="canCreateBrand"
            v-model:open="createModalOpen"
            title="Add brand"
            description="Name, description, and repair flag."
            size="lg"
            :dismiss-href="indexDismissHref"
        >
            <Form class="contents" @submit.prevent="submitCreate">
                <BrandForm :form="createForm" />
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
            v-if="canUpdateBrand"
            v-model:open="editModalOpen"
            title="Edit brand"
            :description="editingBrand?.name"
            size="lg"
            :dismiss-href="indexDismissHref"
        >
            <Form
                v-if="editingBrand"
                class="contents"
                @submit.prevent="submitEdit"
            >
                <BrandForm :form="editForm" />
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
                        :disabled="editForm.processing"
                        @click="submitEdit"
                    >
                        <Spinner v-if="editForm.processing" />
                        Update
                    </Button>
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
