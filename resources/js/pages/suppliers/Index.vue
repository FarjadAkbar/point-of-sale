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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import SupplierForm from '@/pages/suppliers/SupplierForm.vue';
import {
    applyEditingSupplier,
    supplierFormFields,
    transformSupplierSubmit,
} from '@/pages/suppliers/supplierFormState';
import supplierRoutes from '@/routes/suppliers';
import type { Team } from '@/types';

type SupplierRow = {
    id: number;
    supplier_code: string | null;
    contact_type: string;
    display_name: string;
    mobile: string;
    email: string | null;
    city: string | null;
    opening_balance: string;
    created_at: string | null;
};

type Paginated = {
    data: SupplierRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    suppliers: Paginated;
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
        contact_type: string;
    };
    teamMembers: { id: number; name: string; email: string }[];
    editingSupplier:
        | (Record<string, unknown> & {
              id: number;
              display_name?: string;
              supplier_code?: string | null;
          })
        | null;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Suppliers',
                href: supplierRoutes.index.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

const search = ref(props.filters.search ?? '');
const contactTypeFilter = ref(props.filters.contact_type || 'all');
const perPage = ref(String(props.filters.per_page ?? 15));

type ColId =
    | 'supplier_code'
    | 'contact_type'
    | 'display_name'
    | 'mobile'
    | 'email'
    | 'city'
    | 'opening_balance'
    | 'created_at';

const allColumns: {
    id: ColId;
    label: string;
    sortKey: string;
}[] = [
    { id: 'supplier_code', label: 'Code', sortKey: 'supplier_code' },
    { id: 'contact_type', label: 'Type', sortKey: 'contact_type' },
    { id: 'display_name', label: 'Name', sortKey: 'display_name' },
    { id: 'mobile', label: 'Mobile', sortKey: 'mobile' },
    { id: 'email', label: 'Email', sortKey: 'email' },
    { id: 'city', label: 'City', sortKey: 'city' },
    { id: 'opening_balance', label: 'Opening bal.', sortKey: 'opening_balance' },
    { id: 'created_at', label: 'Created', sortKey: 'created_at' },
];

const columnVisibility = useStorage<Record<string, boolean>>(
    'suppliers.datatable.columns',
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
        contact_type:
            contactTypeFilter.value === 'all' ? '' : contactTypeFilter.value,
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
        supplierRoutes.index.url(teamSlug.value),
        indexQuery(overrides),
        {
            preserveState: true,
            replace: true,
        },
    );
}

const debouncedSearch = useDebounceFn(() => visitWithFilters(), 350);

watch(search, () => debouncedSearch());

watch(contactTypeFilter, () => visitWithFilters());

watch(perPage, (v) => visitWithFilters({ per_page: Number(v) }));

function toggleSort(sortKey: string) {
    const isCurrent = props.filters.sort === sortKey;
    const dir =
        isCurrent && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(
        supplierRoutes.index.url(teamSlug.value),
        indexQuery({ sort: sortKey, direction: dir }),
        { preserveState: true, replace: true },
    );
}

const createModalOpen = ref(false);
const editModalOpen = ref(false);

const indexDismissHref = computed(() => {
    const path = supplierRoutes.index.url(teamSlug.value);
    const qs = new URLSearchParams(indexQuery()).toString();

    return qs ? `${path}?${qs}` : path;
});

const createForm = useForm(supplierFormFields());
const editForm = useForm(supplierFormFields());

watch(
    () => props.editingSupplier,
    (s) => {
        if (s) {
            editModalOpen.value = true;

            applyEditingSupplier(editForm, s);
        } else {
            editModalOpen.value = false;
        }
    },
    { immediate: true },
);

function openCreateModal() {
    createForm.defaults(supplierFormFields());
    createForm.reset();
    createForm.clearErrors();
    createModalOpen.value = true;
}

function openEditModal(row: SupplierRow) {
    router.get(
        supplierRoutes.index.url(teamSlug.value),
        indexQuery({ edit: row.id }),
        {
            preserveState: true,
            replace: true,
            only: ['editingSupplier'],
        },
    );
}

function submitCreate() {
    createForm
        .transform((data) => transformSupplierSubmit(data))
        .post(supplierRoutes.store.url(teamSlug.value), {
            onSuccess: () => {
                createModalOpen.value = false;
                createForm.defaults(supplierFormFields());
                createForm.reset();
            },
        });
}

function submitEdit() {
    const s = props.editingSupplier;

    if (!s) {
        return;
    }

    editForm
        .transform((data) => transformSupplierSubmit(data))
        .put(
            supplierRoutes.update.url({
                current_team: teamSlug.value,
                supplier: s.id,
            }),
            { onSuccess: () => (editModalOpen.value = false) },
        );
}

function destroySupplier(row: SupplierRow) {
    if (!confirm('Delete this supplier? This cannot be undone.')) {
        return;
    }

    router.delete(
        supplierRoutes.destroy.url({
            current_team: teamSlug.value,
            supplier: row.id,
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
    return supplierRoutes.export.url(
        { current_team: teamSlug.value, format },
        {
            query: {
                search: search.value || undefined,
                sort: props.filters.sort,
                direction: props.filters.direction,
                contact_type:
                    contactTypeFilter.value === 'all'
                        ? undefined
                        : contactTypeFilter.value,
            },
        },
    );
}

function printTable() {
    window.print();
}

function displayCell(row: SupplierRow, col: ColId): string {
    switch (col) {
        case 'supplier_code':
            return row.supplier_code ?? '—';
        case 'contact_type':
            return row.contact_type;
        case 'display_name':
            return row.display_name;
        case 'mobile':
            return row.mobile;
        case 'email':
            return row.email ?? '—';
        case 'city':
            return row.city ?? '—';
        case 'opening_balance':
            return row.opening_balance;
        case 'created_at':
            return row.created_at
                ? new Date(row.created_at).toLocaleDateString()
                : '—';
        default:
            return '';
    }
}

function sortIndicator(sortKey: string): string {
    if (props.filters.sort !== sortKey) {
        return '';
    }

    return props.filters.direction === 'asc' ? ' ↑' : ' ↓';
}
</script>

<template>
    <Head title="Suppliers" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div
            class="flex flex-col gap-4 print:hidden md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Suppliers</h1>
                <p class="text-sm text-muted-foreground">
                    Filters, column visibility, exports, print, paging, and sort.
                </p>
            </div>
            <Button type="button" @click="openCreateModal">
                Add supplier
            </Button>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Code, name, mobile, email…"
            :paginator="suppliers"
            table-wrapper-id="suppliers-print-area"
            @page="goToPage"
        >
            <template #filters>
                <Select v-model="contactTypeFilter">
                    <SelectTrigger class="w-full" aria-label="Contact type">
                        <SelectValue placeholder="All types" />
                    </SelectTrigger>
                    <SelectContent
                        position="popper"
                        side="bottom"
                        align="start"
                        :side-offset="4"
                    >
                        <SelectItem value="all">All types</SelectItem>
                        <SelectItem value="individual">Individual</SelectItem>
                        <SelectItem value="business">Business</SelectItem>
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
                                    type="button"
                                    class="inline-flex items-center gap-1 hover:text-primary"
                                    @click="toggleSort(col.sortKey)"
                                >
                                    {{ col.label
                                    }}<span class="text-xs text-muted-foreground">{{
                                        sortIndicator(col.sortKey)
                                    }}</span>
                                </button>
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
                            v-for="row in suppliers.data ?? []"
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
                                <div
                                    class="flex flex-wrap items-center justify-end gap-0.5"
                                >
                                    <Button
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
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-destructive hover:text-destructive"
                                        aria-label="Delete"
                                        title="Delete"
                                        @click="destroySupplier(row)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!(suppliers?.data?.length)">
                            <td
                                :colspan="visibleColumns.length + 1"
                                class="px-3 py-8 text-center text-muted-foreground"
                            >
                                No suppliers match your filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
        </StandardDataTable>

        <StandardFormModal
            v-model:open="createModalOpen"
            title="Add supplier"
            description="Fields mirror your reference contact form (supplier module)."
            size="full"
            :dismiss-href="indexDismissHref"
        >
            <Form class="contents" @submit.prevent="submitCreate">
                <SupplierForm
                    :form="createForm"
                    :team-members="props.teamMembers"
                />
            </Form>
            <template #footer>
                <div class="flex w-full flex-wrap items-center justify-between gap-2">
                    <div />
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
                            :disabled="createForm.processing"
                            @click="submitCreate"
                        >
                            <Spinner v-if="createForm.processing" />
                            Save
                        </Button>
                    </div>
                </div>
            </template>
        </StandardFormModal>

        <StandardFormModal
            v-model:open="editModalOpen"
            title="Edit supplier"
            :description="
                String(
                    editingSupplier?.display_name ??
                        editingSupplier?.supplier_code ??
                        '',
                )
            "
            size="full"
            :dismiss-href="indexDismissHref"
        >
            <Form
                v-if="editingSupplier"
                class="contents"
                @submit.prevent="submitEdit"
            >
                <SupplierForm
                    :form="editForm"
                    :team-members="props.teamMembers"
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
