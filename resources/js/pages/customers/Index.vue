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
import customerRoutes from '@/routes/customers';
import StandardDataTable from '@/components/StandardDataTable.vue';
import StandardFormModal from '@/components/StandardFormModal.vue';
import CustomerForm from '@/pages/customers/CustomerForm.vue';
import {
    applyEditingCustomer,
    customerFormFields,
    transformCustomerSubmit,
} from '@/pages/customers/customerFormState';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
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
import type { Team } from '@/types';

type CustomerRow = {
    id: number;
    customer_code: string | null;
    party_role: string;
    entity_type: string;
    display_name: string;
    mobile: string;
    email: string | null;
    city: string | null;
    opening_balance: string;
    credit_limit: string | null;
    created_at: string | null;
};

type Paginated = {
    data: CustomerRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    customers: Paginated;
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
        party_role: string;
        entity_type: string;
    };
    teamMembers: { id: number; name: string; email: string }[];
    customerGroups: { id: number; name: string }[];
    editingCustomer:
        | (Record<string, unknown> & {
              id: number;
              display_name?: string;
              customer_code?: string | null;
          })
        | null;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Customers',
                href: customerRoutes.index.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

const search = ref(props.filters.search ?? '');
const partyRoleFilter = ref(
    props.filters.party_role ? props.filters.party_role : 'default',
);
const entityTypeFilter = ref(props.filters.entity_type || 'all');
const perPage = ref(String(props.filters.per_page ?? 15));

type ColId =
    | 'customer_code'
    | 'party_role'
    | 'entity_type'
    | 'display_name'
    | 'mobile'
    | 'email'
    | 'city'
    | 'opening_balance'
    | 'credit_limit'
    | 'created_at';

const allColumns: {
    id: ColId;
    label: string;
    sortKey: string;
}[] = [
    { id: 'customer_code', label: 'Code', sortKey: 'customer_code' },
    { id: 'party_role', label: 'Role', sortKey: 'party_role' },
    { id: 'entity_type', label: 'Entity', sortKey: 'entity_type' },
    { id: 'display_name', label: 'Name', sortKey: 'display_name' },
    { id: 'mobile', label: 'Mobile', sortKey: 'mobile' },
    { id: 'email', label: 'Email', sortKey: 'email' },
    { id: 'city', label: 'City', sortKey: 'city' },
    { id: 'opening_balance', label: 'Opening bal.', sortKey: 'opening_balance' },
    { id: 'credit_limit', label: 'Credit limit', sortKey: 'credit_limit' },
    { id: 'created_at', label: 'Created', sortKey: 'created_at' },
];

const columnVisibility = useStorage<Record<string, boolean>>(
    'customers.datatable.columns',
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
        party_role:
            partyRoleFilter.value === 'default' ? '' : partyRoleFilter.value,
        entity_type:
            entityTypeFilter.value === 'all' ? '' : entityTypeFilter.value,
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
        customerRoutes.index.url(teamSlug.value),
        indexQuery(overrides),
        {
            preserveState: true,
            replace: true,
        },
    );
}

const debouncedSearch = useDebounceFn(() => visitWithFilters(), 350);

watch(search, () => debouncedSearch());

watch(partyRoleFilter, () => visitWithFilters());

watch(entityTypeFilter, () => visitWithFilters());

watch(perPage, (v) => visitWithFilters({ per_page: Number(v) }));

function toggleSort(sortKey: string) {
    const isCurrent = props.filters.sort === sortKey;
    const dir =
        isCurrent && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(
        customerRoutes.index.url(teamSlug.value),
        indexQuery({ sort: sortKey, direction: dir }),
        { preserveState: true, replace: true },
    );
}

const createModalOpen = ref(false);
const editModalOpen = ref(false);

const indexDismissHref = computed(() => {
    const path = customerRoutes.index.url(teamSlug.value);
    const qs = new URLSearchParams(indexQuery()).toString();
    return qs ? `${path}?${qs}` : path;
});

const createForm = useForm(customerFormFields());
const editForm = useForm(customerFormFields());

watch(
    () => props.editingCustomer,
    (c) => {
        if (c) {
            editModalOpen.value = true;
            applyEditingCustomer(editForm, c);
        } else {
            editModalOpen.value = false;
        }
    },
    { immediate: true },
);

function openCreateModal() {
    createForm.defaults(customerFormFields());
    createForm.reset();
    createForm.clearErrors();
    createModalOpen.value = true;
}

function openEditModal(row: CustomerRow) {
    router.get(
        customerRoutes.index.url(teamSlug.value),
        indexQuery({ edit: row.id }),
        {
            preserveState: true,
            replace: true,
            only: ['editingCustomer'],
        },
    );
}

function submitCreate() {
    createForm
        .transform((data) => transformCustomerSubmit(data))
        .post(customerRoutes.store.url(teamSlug.value), {
            onSuccess: () => {
                createModalOpen.value = false;
                createForm.defaults(customerFormFields());
                createForm.reset();
            },
        });
}

function submitEdit() {
    const c = props.editingCustomer;
    if (!c) {
        return;
    }
    editForm
        .transform((data) => transformCustomerSubmit(data))
        .put(
            customerRoutes.update.url({
                current_team: teamSlug.value,
                customer: c.id,
            }),
            { onSuccess: () => (editModalOpen.value = false) },
        );
}

function destroyCustomer() {
    const c = props.editingCustomer;
    if (!c || !confirm('Delete this customer? This cannot be undone.')) {
        return;
    }
    router.delete(
        customerRoutes.destroy.url({
            current_team: teamSlug.value,
            customer: c.id,
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
    return customerRoutes.export.url(
        { current_team: teamSlug.value, format },
        {
            query: {
                search: search.value || undefined,
                sort: props.filters.sort,
                direction: props.filters.direction,
                party_role:
                    partyRoleFilter.value === 'default'
                        ? undefined
                        : partyRoleFilter.value,
                entity_type:
                    entityTypeFilter.value === 'all'
                        ? undefined
                        : entityTypeFilter.value,
            },
        },
    );
}

function printTable() {
    window.print();
}

function displayCell(row: CustomerRow, col: ColId): string {
    switch (col) {
        case 'customer_code':
            return row.customer_code ?? '—';
        case 'party_role':
            return row.party_role;
        case 'entity_type':
            return row.entity_type;
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
        case 'credit_limit':
            return row.credit_limit != null && row.credit_limit !== ''
                ? String(row.credit_limit)
                : '—';
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
    <Head title="Customers" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div
            class="flex flex-col gap-4 print:hidden md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Customers</h1>
                <p class="text-sm text-muted-foreground">
                    Default list shows customer and “both” roles; widen with role
                    filters. Export, print, columns, paging, and sort.
                </p>
            </div>
            <Button type="button" @click="openCreateModal">
                Add customer
            </Button>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Code, name, mobile, email…"
            :paginator="customers"
            table-wrapper-id="customers-print-area"
            @page="goToPage"
        >
            <template #filters>
                <Select v-model="partyRoleFilter">
                    <SelectTrigger class="w-full" aria-label="Party role">
                        <SelectValue placeholder="Default" />
                    </SelectTrigger>
                    <SelectContent
                        position="popper"
                        side="bottom"
                        align="start"
                        :side-offset="4"
                    >
                        <SelectItem value="default"
                            >Default (customer + both)</SelectItem
                        >
                        <SelectItem value="all">All roles</SelectItem>
                        <SelectItem value="customer">Customer</SelectItem>
                        <SelectItem value="supplier">Supplier</SelectItem>
                        <SelectItem value="both">Both</SelectItem>
                    </SelectContent>
                </Select>
                <Select v-model="entityTypeFilter">
                    <SelectTrigger class="w-full" aria-label="Entity type">
                        <SelectValue placeholder="All" />
                    </SelectTrigger>
                    <SelectContent
                        position="popper"
                        side="bottom"
                        align="start"
                        :side-offset="4"
                    >
                        <SelectItem value="all">All</SelectItem>
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

            <table class="w-full min-w-[880px] border-collapse text-sm">
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
                            v-for="row in customers?.data ?? []"
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
                        <tr v-if="!(customers?.data?.length)">
                            <td
                                :colspan="visibleColumns.length + 1"
                                class="px-3 py-8 text-center text-muted-foreground"
                            >
                                No customers match your filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
        </StandardDataTable>

        <StandardFormModal
            v-model:open="createModalOpen"
            title="Add customer"
            description="Customer contact profile, assignments, and related fields."
            size="full"
            :dismiss-href="indexDismissHref"
        >
            <Form class="contents" @submit.prevent="submitCreate">
                <CustomerForm
                    :form="createForm"
                    :team-members="props.teamMembers"
                    :customer-groups="props.customerGroups"
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
            title="Edit customer"
            :description="
                String(
                    editingCustomer?.display_name ??
                        editingCustomer?.customer_code ??
                        '',
                )
            "
            size="full"
            :dismiss-href="indexDismissHref"
        >
            <Form
                v-if="editingCustomer"
                class="contents"
                @submit.prevent="submitEdit"
            >
                <CustomerForm
                    :form="editForm"
                    :team-members="props.teamMembers"
                    :customer-groups="props.customerGroups"
                />
            </Form>
            <template #footer>
                <div class="flex w-full flex-wrap items-center justify-between gap-2">
                    <Button
                        type="button"
                        variant="destructive"
                        @click="destroyCustomer"
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
