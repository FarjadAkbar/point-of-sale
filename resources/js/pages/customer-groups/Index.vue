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
import cgRoutes from '@/routes/customer-groups';
import StandardDataTable from '@/components/StandardDataTable.vue';
import StandardFormModal from '@/components/StandardFormModal.vue';
import GroupForm from '@/pages/customer-groups/GroupForm.vue';
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

type GroupRow = {
    id: number;
    name: string;
    price_calculation_type: string;
    amount: string | null;
    selling_price_group: { id: number; name: string } | null;
    created_at: string | null;
};

type Paginated = {
    data: GroupRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type SpgOption = { id: number; name: string };

type EditingCg = {
    id: number;
    name: string;
    price_calculation_type: string;
    amount: string | number | null;
    selling_price_group_id: number | null;
};

const props = defineProps<{
    customerGroups: Paginated;
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
        price_calculation_type: string;
    };
    sellingPriceGroups: SpgOption[];
    editingCustomerGroup: EditingCg | null;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Customer groups',
                href: cgRoutes.index.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

const search = ref(props.filters.search ?? '');
const calcTypeFilter = ref(props.filters.price_calculation_type || 'all');
const perPage = ref(String(props.filters.per_page ?? 15));

type ColId =
    | 'name'
    | 'price_calculation_type'
    | 'amount'
    | 'selling_price_group'
    | 'created_at';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    { id: 'name', label: 'Name', sortKey: 'name' },
    {
        id: 'price_calculation_type',
        label: 'Calculation',
        sortKey: 'price_calculation_type',
    },
    { id: 'amount', label: 'Amount %', sortKey: 'amount' },
    {
        id: 'selling_price_group',
        label: 'Price group',
        sortKey: null,
    },
    { id: 'created_at', label: 'Created', sortKey: 'created_at' },
];

const columnVisibility = useStorage<Record<string, boolean>>(
    'customer-groups.datatable.columns',
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
        price_calculation_type:
            calcTypeFilter.value === 'all' ? '' : calcTypeFilter.value,
    };
    for (const [k, v] of Object.entries(overrides)) {
        if (v !== undefined && v !== '') {
            q[k] = String(v);
        }
    }
    return q;
}

function visitWithFilters(overrides: Record<string, string | number> = {}) {
    router.get(cgRoutes.index.url(teamSlug.value), indexQuery(overrides), {
        preserveState: true,
        replace: true,
    });
}

const debouncedSearch = useDebounceFn(() => visitWithFilters(), 350);

watch(search, () => debouncedSearch());
watch(calcTypeFilter, () => visitWithFilters());
watch(perPage, (v) => visitWithFilters({ per_page: Number(v) }));

function toggleSort(sortKey: string) {
    const isCurrent = props.filters.sort === sortKey;
    const dir =
        isCurrent && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(
        cgRoutes.index.url(teamSlug.value),
        indexQuery({ sort: sortKey, direction: dir }),
        { preserveState: true, replace: true },
    );
}

const createModalOpen = ref(false);
const editModalOpen = ref(false);

const indexDismissHref = computed(() => {
    const path = cgRoutes.index.url(teamSlug.value);
    const qs = new URLSearchParams(indexQuery()).toString();
    return qs ? `${path}?${qs}` : path;
});

const createForm = useForm({
    name: '',
    price_calculation_type: 'percentage',
    amount: '',
    selling_price_group_id: '',
});

const editForm = useForm({
    name: '',
    price_calculation_type: 'percentage',
    amount: '',
    selling_price_group_id: '',
});

watch(
    () => props.editingCustomerGroup,
    (g) => {
        if (g) {
            editModalOpen.value = true;
            editForm.name = String(g.name ?? '');
            editForm.price_calculation_type = String(
                g.price_calculation_type ?? 'percentage',
            );
            editForm.amount =
                g.amount !== null && g.amount !== undefined
                    ? String(g.amount)
                    : '';
            editForm.selling_price_group_id =
                g.selling_price_group_id != null
                    ? String(g.selling_price_group_id)
                    : '';
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

function openEditModal(row: GroupRow) {
    router.get(
        cgRoutes.index.url(teamSlug.value),
        indexQuery({ edit: row.id }),
        {
            preserveState: true,
            replace: true,
            only: ['editingCustomerGroup'],
        },
    );
}

function transformCgPayload(data: {
    name: string;
    price_calculation_type: string;
    amount: string;
    selling_price_group_id: string;
}) {
    return {
        name: data.name,
        price_calculation_type: data.price_calculation_type,
        amount:
            data.price_calculation_type === 'percentage' && data.amount !== ''
                ? data.amount
                : null,
        selling_price_group_id:
            data.price_calculation_type === 'selling_price_group' &&
            data.selling_price_group_id !== ''
                ? Number(data.selling_price_group_id)
                : null,
    };
}

function submitCreate() {
    createForm
        .transform((data) => transformCgPayload(data))
        .post(cgRoutes.store.url(teamSlug.value), {
            onSuccess: () => {
                createModalOpen.value = false;
                createForm.reset();
            },
        });
}

function submitEdit() {
    const g = props.editingCustomerGroup;
    if (!g) {
        return;
    }
    editForm
        .transform((data) => transformCgPayload(data))
        .put(
            cgRoutes.update.url({
                current_team: teamSlug.value,
                customer_group: g.id,
            }),
            { onSuccess: () => (editModalOpen.value = false) },
        );
}

function destroyGroup() {
    const g = props.editingCustomerGroup;
    if (!g || !confirm('Delete this customer group?')) {
        return;
    }
    router.delete(
        cgRoutes.destroy.url({
            current_team: teamSlug.value,
            customer_group: g.id,
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
    return cgRoutes.export.url(
        { current_team: teamSlug.value, format },
        {
            query: {
                search: search.value || undefined,
                sort: props.filters.sort,
                direction: props.filters.direction,
                price_calculation_type:
                    calcTypeFilter.value === 'all'
                        ? undefined
                        : calcTypeFilter.value,
            },
        },
    );
}

function printTable() {
    window.print();
}

function displayCell(row: GroupRow, col: ColId): string {
    switch (col) {
        case 'name':
            return row.name;
        case 'price_calculation_type':
            return row.price_calculation_type === 'selling_price_group'
                ? 'Selling price group'
                : 'Percentage';
        case 'amount':
            return row.amount != null && row.amount !== ''
                ? String(row.amount)
                : '—';
        case 'selling_price_group':
            return row.selling_price_group?.name ?? '—';
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
    <Head title="Customer groups" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div
            class="flex flex-col gap-4 print:hidden md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Customer groups
                </h1>
                <p class="text-sm text-muted-foreground">
                    Pricing rules, exports, column visibility, paging, and sort.
                </p>
            </div>
            <Button type="button" @click="openCreateModal">
                Add customer group
            </Button>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Group name…"
            :paginator="customerGroups"
            @page="goToPage"
        >
            <template #filters>
                <Select v-model="calcTypeFilter">
                    <SelectTrigger class="w-full" aria-label="Calculation type">
                        <SelectValue placeholder="All" />
                    </SelectTrigger>
                    <SelectContent
                        position="popper"
                        side="bottom"
                        align="start"
                        :side-offset="4"
                    >
                        <SelectItem value="all">All</SelectItem>
                        <SelectItem value="percentage">Percentage</SelectItem>
                        <SelectItem value="selling_price_group"
                            >Selling price group</SelectItem
                        >
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
                                class="bg-muted/40 px-3 py-2 text-right font-medium print:hidden"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in customerGroups.data ?? []"
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
                        <tr v-if="!(customerGroups?.data?.length)">
                            <td
                                :colspan="visibleColumns.length + 1"
                                class="px-3 py-8 text-center text-muted-foreground"
                            >
                                No customer groups match your filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
        </StandardDataTable>

        <StandardFormModal
            v-model:open="createModalOpen"
            title="Add customer group"
            description="Name, price calculation type, and percentage or selling price group."
            size="lg"
            :dismiss-href="indexDismissHref"
        >
            <Form class="contents" @submit.prevent="submitCreate">
                <GroupForm
                    :form="createForm"
                    :selling-price-groups="props.sellingPriceGroups"
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
            title="Edit customer group"
            :description="editingCustomerGroup?.name"
            size="lg"
            :dismiss-href="indexDismissHref"
        >
            <Form
                v-if="editingCustomerGroup"
                class="contents"
                @submit.prevent="submitEdit"
            >
                <GroupForm
                    :form="editForm"
                    :selling-price-groups="props.sellingPriceGroups"
                />
            </Form>
            <template #footer>
                <div class="flex w-full flex-wrap items-center justify-between gap-2">
                    <Button
                        type="button"
                        variant="destructive"
                        @click="destroyGroup"
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
