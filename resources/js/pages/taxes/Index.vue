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
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Spinner } from '@/components/ui/spinner';
import { formatTaxPercent } from '@/lib/taxPercent';
import TaxGroupForm from '@/pages/taxes/TaxGroupForm.vue';
import TaxRateForm from '@/pages/taxes/TaxRateForm.vue';
import taxGroupRoutes from '@/routes/tax-groups';
import taxRateRoutes from '@/routes/tax-rates';
import taxesPageRoutes from '@/routes/taxes';
import type { Team } from '@/types';

type TaxRateRow = {
    id: number;
    name: string;
    amount: string;
    for_tax_group: boolean;
    created_at: string | null;
};

type TaxGroupRow = {
    id: number;
    name: string;
    combined_rate_percent: string;
    sub_taxes_label: string;
    tax_rate_ids: number[];
    created_at: string | null;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type EditingTaxRate = {
    id: number;
    name: string;
    amount: string;
    for_tax_group: boolean;
};

type EditingTaxGroup = {
    id: number;
    name: string;
    tax_rate_ids: number[];
};

const props = defineProps<{
    taxRates: Paginated<TaxRateRow>;
    taxGroups: Paginated<TaxGroupRow>;
    rateFilters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
    };
    groupFilters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
    };
    editingTaxRate: EditingTaxRate | null;
    editingTaxGroup: EditingTaxGroup | null;
    taxRateOptions: { id: number; name: string; amount: string }[];
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Taxes',
                href: taxesPageRoutes.index.url(p.currentTeam!.slug),
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
const hasTaxRatePermission = (permission: string): boolean =>
    posPermissions.value.includes(permission);
const canCreateTaxRate = computed(() => hasTaxRatePermission('tax_rate.create'));
const canUpdateTaxRate = computed(() => hasTaxRatePermission('tax_rate.update'));
const canDeleteTaxRate = computed(() => hasTaxRatePermission('tax_rate.delete'));
const showRateActionColumn = computed(
    () => canUpdateTaxRate.value || canDeleteTaxRate.value,
);

const rateSearch = ref(props.rateFilters.search ?? '');
const ratePerPage = ref(String(props.rateFilters.per_page ?? 15));
const groupSearch = ref(props.groupFilters.search ?? '');
const groupPerPage = ref(String(props.groupFilters.per_page ?? 15));

type RateColId = 'name' | 'amount' | 'for_tax_group' | 'created_at';

const rateColumns: { id: RateColId; label: string; sortKey: string | null }[] =
    [
        { id: 'name', label: 'Name', sortKey: 'name' },
        { id: 'amount', label: 'Tax rate %', sortKey: 'amount' },
        {
            id: 'for_tax_group',
            label: 'Group only',
            sortKey: 'for_tax_group',
        },
        { id: 'created_at', label: 'Created', sortKey: 'created_at' },
    ];

const rateColumnVisibility = useStorage<Record<string, boolean>>(
    'taxes.rates.datatable.columns',
    Object.fromEntries(rateColumns.map((c) => [c.id, true])),
);

function setRateColumnVisible(id: string, visible: boolean) {
    rateColumnVisibility.value = {
        ...rateColumnVisibility.value,
        [id]: visible,
    };
}

const visibleRateColumns = computed(() =>
    rateColumns.filter((c) => rateColumnVisibility.value[c.id] !== false),
);

type GroupColId = 'name' | 'combined_rate_percent' | 'sub_taxes' | 'created_at';

const groupColumns: {
    id: GroupColId;
    label: string;
    sortKey: string | null;
}[] = [
    { id: 'name', label: 'Name', sortKey: 'name' },
    {
        id: 'combined_rate_percent',
        label: 'Tax rate %',
        sortKey: null,
    },
    { id: 'sub_taxes', label: 'Sub taxes', sortKey: null },
    { id: 'created_at', label: 'Created', sortKey: 'created_at' },
];

const groupColumnVisibility = useStorage<Record<string, boolean>>(
    'taxes.groups.datatable.columns',
    Object.fromEntries(groupColumns.map((c) => [c.id, true])),
);

function setGroupColumnVisible(id: string, visible: boolean) {
    groupColumnVisibility.value = {
        ...groupColumnVisibility.value,
        [id]: visible,
    };
}

const visibleGroupColumns = computed(() =>
    groupColumns.filter((c) => groupColumnVisibility.value[c.id] !== false),
);

function fullIndexQuery(
    overrides: Record<string, string | number | undefined> = {},
): Record<string, string> {
    const q: Record<string, string> = {
        rate_search: rateSearch.value,
        rate_sort: props.rateFilters.sort,
        rate_direction: props.rateFilters.direction,
        rate_per_page: String(
            overrides.rate_per_page ?? props.rateFilters.per_page,
        ),
        group_search: groupSearch.value,
        group_sort: props.groupFilters.sort,
        group_direction: props.groupFilters.direction,
        group_per_page: String(
            overrides.group_per_page ?? props.groupFilters.per_page,
        ),
    };

    for (const [k, v] of Object.entries(overrides)) {
        if (v !== undefined && v !== '') {
            q[k] = String(v);
        }
    }

    return q;
}

function visitWithAllFilters(
    overrides: Record<string, string | number | undefined> = {},
) {
    router.get(
        taxesPageRoutes.index.url(teamSlug.value),
        fullIndexQuery(overrides),
        {
            preserveState: true,
            replace: true,
        },
    );
}

const debouncedRateSearch = useDebounceFn(() => visitWithAllFilters(), 350);
const debouncedGroupSearch = useDebounceFn(() => visitWithAllFilters(), 350);

watch(rateSearch, () => debouncedRateSearch());
watch(ratePerPage, (v) =>
    visitWithAllFilters({ rate_per_page: Number(v) }),
);
watch(groupSearch, () => debouncedGroupSearch());
watch(groupPerPage, (v) =>
    visitWithAllFilters({ group_per_page: Number(v) }),
);

function toggleRateSort(sortKey: string) {
    const isCurrent = props.rateFilters.sort === sortKey;
    const dir =
        isCurrent && props.rateFilters.direction === 'asc' ? 'desc' : 'asc';
    visitWithAllFilters({ rate_sort: sortKey, rate_direction: dir });
}

function toggleGroupSort(sortKey: string) {
    const isCurrent = props.groupFilters.sort === sortKey;
    const dir =
        isCurrent && props.groupFilters.direction === 'asc' ? 'desc' : 'asc';
    visitWithAllFilters({ group_sort: sortKey, group_direction: dir });
}

const indexDismissHref = computed(() => {
    const path = taxesPageRoutes.index.url(teamSlug.value);
    const q = { ...fullIndexQuery() };
    delete q.edit_rate;
    delete q.edit_group;
    const qs = new URLSearchParams(q).toString();

    return qs ? `${path}?${qs}` : path;
});

const createRateModalOpen = ref(false);
const editRateModalOpen = ref(false);
const createGroupModalOpen = ref(false);
const editGroupModalOpen = ref(false);

const createRateForm = useForm({
    name: '',
    amount: '',
    for_tax_group: false,
});

const editRateForm = useForm({
    name: '',
    amount: '',
    for_tax_group: false,
});

const createGroupForm = useForm({
    name: '',
    tax_rate_ids: [] as number[],
});

const editGroupForm = useForm({
    name: '',
    tax_rate_ids: [] as number[],
});

watch(
    () => props.editingTaxRate,
    (r) => {
        if (r) {
            editRateModalOpen.value = true;
            editRateForm.name = String(r.name ?? '');
            editRateForm.amount =
                r.amount != null && r.amount !== '' ? String(r.amount) : '';
            editRateForm.for_tax_group = Boolean(r.for_tax_group);
        } else {
            editRateModalOpen.value = false;
        }
    },
    { immediate: true },
);

watch(
    () => props.editingTaxGroup,
    (g) => {
        if (g) {
            editGroupModalOpen.value = true;
            editGroupForm.name = String(g.name ?? '');
            editGroupForm.tax_rate_ids = [...(g.tax_rate_ids ?? [])];
        } else {
            editGroupModalOpen.value = false;
        }
    },
    { immediate: true },
);

function openCreateRateModal() {
    createRateForm.reset();
    createRateForm.clearErrors();
    createRateModalOpen.value = true;
}

function openEditRateModal(row: TaxRateRow) {
    if (!canUpdateTaxRate.value) {
        return;
    }

    router.get(
        taxesPageRoutes.index.url(teamSlug.value),
        { ...fullIndexQuery(), edit_rate: row.id },
        {
            preserveState: true,
            replace: true,
            only: ['editingTaxRate'],
        },
    );
}

function openCreateGroupModal() {
    createGroupForm.reset();
    createGroupForm.clearErrors();
    createGroupForm.name = '';
    createGroupForm.tax_rate_ids = [];
    createGroupModalOpen.value = true;
}

function openEditGroupModal(row: TaxGroupRow) {
    router.get(
        taxesPageRoutes.index.url(teamSlug.value),
        { ...fullIndexQuery(), edit_group: row.id },
        {
            preserveState: true,
            replace: true,
            only: ['editingTaxGroup'],
        },
    );
}

function submitCreateRate() {
    createRateForm
        .transform((data) => ({
            name: data.name.trim(),
            amount: data.amount.trim(),
            for_tax_group: data.for_tax_group === true,
        }))
        .post(taxRateRoutes.store.url(teamSlug.value), {
            onSuccess: () => {
                createRateModalOpen.value = false;
                createRateForm.reset();
            },
        });
}

function submitEditRate() {
    const r = props.editingTaxRate;

    if (!r) {
        return;
    }

    editRateForm
        .transform((data) => ({
            name: data.name.trim(),
            amount: data.amount.trim(),
            for_tax_group: data.for_tax_group === true,
        }))
        .put(
            taxRateRoutes.update.url({
                current_team: teamSlug.value,
                tax_rate: r.id,
            }),
            {
                onSuccess: () => {
                    editRateModalOpen.value = false;
                },
            },
        );
}

function destroyRate(row: TaxRateRow) {
    if (!canDeleteTaxRate.value) {
        return;
    }

    if (!confirm('Delete this tax rate?')) {
        return;
    }

    router.delete(
        taxRateRoutes.destroy.url({
            current_team: teamSlug.value,
            tax_rate: row.id,
        }),
    );
}

function submitCreateGroup() {
    if (!createGroupForm.tax_rate_ids.length) {
        return;
    }

    createGroupForm
        .transform((data) => ({
            name: data.name.trim(),
            tax_rate_ids: data.tax_rate_ids,
        }))
        .post(taxGroupRoutes.store.url(teamSlug.value), {
            onSuccess: () => {
                createGroupModalOpen.value = false;
                createGroupForm.reset();
            },
        });
}

function submitEditGroup() {
    const g = props.editingTaxGroup;

    if (!g) {
        return;
    }

    if (!editGroupForm.tax_rate_ids.length) {
        return;
    }

    editGroupForm
        .transform((data) => ({
            name: data.name.trim(),
            tax_rate_ids: data.tax_rate_ids,
        }))
        .put(
            taxGroupRoutes.update.url({
                current_team: teamSlug.value,
                tax_group: g.id,
            }),
            {
                onSuccess: () => {
                    editGroupModalOpen.value = false;
                },
            },
        );
}

function destroyTaxGroup(row: TaxGroupRow) {
    if (!confirm('Delete this tax group?')) {
        return;
    }

    router.delete(
        taxGroupRoutes.destroy.url({
            current_team: teamSlug.value,
            tax_group: row.id,
        }),
    );
}

function goToRatePage(url: string | null) {
    if (!url) {
        return;
    }

    router.visit(url, { preserveState: true, replace: true });
}

function goToGroupPage(url: string | null) {
    if (!url) {
        return;
    }

    router.visit(url, { preserveState: true, replace: true });
}

function displayRateCell(row: TaxRateRow, col: RateColId): string {
    switch (col) {
        case 'name':
            return row.name;
        case 'amount':
            return row.amount != null && row.amount !== ''
                ? `${formatTaxPercent(row.amount)}%`
                : '—';
        case 'for_tax_group':
            return row.for_tax_group ? 'Yes' : 'No';
        case 'created_at':
            return row.created_at
                ? new Date(row.created_at).toLocaleDateString()
                : '—';
        default:
            return '';
    }
}

function displayGroupCell(row: TaxGroupRow, col: GroupColId): string {
    switch (col) {
        case 'name':
            return row.name;
        case 'combined_rate_percent':
            return row.combined_rate_percent != null &&
                row.combined_rate_percent !== ''
                ? `${formatTaxPercent(row.combined_rate_percent)}%`
                : '—';
        case 'sub_taxes':
            return row.sub_taxes_label?.trim() ? row.sub_taxes_label : '—';
        case 'created_at':
            return row.created_at
                ? new Date(row.created_at).toLocaleDateString()
                : '—';
        default:
            return '';
    }
}

function rateSortIndicator(sortKey: string | null): string {
    if (!sortKey || props.rateFilters.sort !== sortKey) {
        return '';
    }

    return props.rateFilters.direction === 'asc' ? ' ↑' : ' ↓';
}

function groupSortIndicator(sortKey: string | null): string {
    if (!sortKey || props.groupFilters.sort !== sortKey) {
        return '';
    }

    return props.groupFilters.direction === 'asc' ? ' ↑' : ' ↓';
}
</script>

<template>
    <Head title="Taxes" />

    <div class="flex flex-1 flex-col gap-10 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Taxes</h1>
            <p class="text-sm text-muted-foreground">
                Manage tax rates and combine them into tax groups.
            </p>
        </div>

        <section class="flex flex-col gap-3">
            <div
                class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between"
            >
                <h2 class="text-lg font-semibold tracking-tight">
                    All your tax rates
                </h2>
                <Button v-if="canCreateTaxRate" type="button" @click="openCreateRateModal">
                    Add tax rate
                </Button>
            </div>

            <StandardDataTable
                v-model:search="rateSearch"
                v-model:per-page="ratePerPage"
                search-placeholder="Search rates…"
                :paginator="taxRates"
                @page="goToRatePage"
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
                                v-for="col in rateColumns"
                                :key="col.id"
                                :model-value="
                                    rateColumnVisibility[col.id] !== false
                                "
                                @update:model-value="
                                    (v) => setRateColumnVisible(col.id, !!v)
                                "
                            >
                                {{ col.label }}
                            </DropdownMenuCheckboxItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>

                <table class="w-full min-w-[640px] border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-border">
                            <th
                                v-for="col in visibleRateColumns"
                                :key="col.id"
                                class="bg-muted/40 px-3 py-2 text-left font-medium"
                            >
                                <button
                                    v-if="col.sortKey"
                                    type="button"
                                    class="inline-flex items-center gap-1 hover:text-primary"
                                    @click="toggleRateSort(col.sortKey)"
                                >
                                    {{ col.label
                                    }}<span
                                        class="text-xs text-muted-foreground"
                                        >{{
                                            rateSortIndicator(col.sortKey)
                                        }}</span
                                    >
                                </button>
                                <span v-else>{{ col.label }}</span>
                            </th>
                            <th
                                v-if="showRateActionColumn"
                                class="bg-muted/40 px-3 py-2 text-right font-medium"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in taxRates.data ?? []"
                            :key="row.id"
                            class="border-b border-border/80 hover:bg-muted/20"
                        >
                            <td
                                v-for="col in visibleRateColumns"
                                :key="col.id"
                                class="px-3 py-2 align-middle"
                            >
                                {{ displayRateCell(row, col.id) }}
                            </td>
                            <td
                                v-if="showRateActionColumn"
                                class="px-3 py-2 text-right"
                            >
                                <div
                                    class="flex flex-wrap items-center justify-end gap-0.5"
                                >
                                    <Button
                                        v-if="canUpdateTaxRate"
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-primary hover:text-primary"
                                        aria-label="Edit tax rate"
                                        title="Edit tax rate"
                                        @click="openEditRateModal(row)"
                                    >
                                        <Pencil />
                                    </Button>
                                    <Button
                                        v-if="canDeleteTaxRate"
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-destructive hover:text-destructive"
                                        aria-label="Delete tax rate"
                                        title="Delete tax rate"
                                        @click="destroyRate(row)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!(taxRates?.data?.length)">
                            <td
                                :colspan="visibleRateColumns.length + (showRateActionColumn ? 1 : 0)"
                                class="px-3 py-8 text-center text-muted-foreground"
                            >
                                No tax rates yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </StandardDataTable>
        </section>

        <section class="flex flex-col gap-3">
            <div
                class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between"
            >
                <h2 class="text-lg font-semibold tracking-tight">
                    Tax groups (combination of multiple taxes)
                </h2>
                <Button type="button" @click="openCreateGroupModal">
                    Add tax group
                </Button>
            </div>

            <StandardDataTable
                v-model:search="groupSearch"
                v-model:per-page="groupPerPage"
                search-placeholder="Search groups…"
                :paginator="taxGroups"
                @page="goToGroupPage"
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
                                v-for="col in groupColumns"
                                :key="col.id"
                                :model-value="
                                    groupColumnVisibility[col.id] !== false
                                "
                                @update:model-value="
                                    (v) => setGroupColumnVisible(col.id, !!v)
                                "
                            >
                                {{ col.label }}
                            </DropdownMenuCheckboxItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>

                <table class="w-full min-w-[640px] border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-border">
                            <th
                                v-for="col in visibleGroupColumns"
                                :key="col.id"
                                class="bg-muted/40 px-3 py-2 text-left font-medium"
                            >
                                <button
                                    v-if="col.sortKey"
                                    type="button"
                                    class="inline-flex items-center gap-1 hover:text-primary"
                                    @click="toggleGroupSort(col.sortKey)"
                                >
                                    {{ col.label
                                    }}<span
                                        class="text-xs text-muted-foreground"
                                        >{{
                                            groupSortIndicator(col.sortKey)
                                        }}</span
                                    >
                                </button>
                                <span v-else>{{ col.label }}</span>
                            </th>
                            <th
                                class="bg-muted/40 px-3 py-2 text-right font-medium"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in taxGroups.data ?? []"
                            :key="row.id"
                            class="border-b border-border/80 hover:bg-muted/20"
                        >
                            <td
                                v-for="col in visibleGroupColumns"
                                :key="col.id"
                                class="px-3 py-2 align-middle"
                            >
                                {{ displayGroupCell(row, col.id) }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div
                                    class="flex flex-wrap items-center justify-end gap-0.5"
                                >
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-primary hover:text-primary"
                                        aria-label="Edit tax group"
                                        title="Edit tax group"
                                        @click="openEditGroupModal(row)"
                                    >
                                        <Pencil />
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-destructive hover:text-destructive"
                                        aria-label="Delete tax group"
                                        title="Delete tax group"
                                        @click="destroyTaxGroup(row)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!(taxGroups?.data?.length)">
                            <td
                                :colspan="visibleGroupColumns.length + 1"
                                class="px-3 py-8 text-center text-muted-foreground"
                            >
                                No tax groups yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </StandardDataTable>
        </section>

        <StandardFormModal
            v-if="canCreateTaxRate"
            v-model:open="createRateModalOpen"
            title="Add tax rate"
            description="Name, percentage, and whether it is only used inside groups."
            size="lg"
            :dismiss-href="indexDismissHref"
        >
            <Form class="contents" @submit.prevent="submitCreateRate">
                <TaxRateForm :form="createRateForm" />
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
                        :disabled="createRateForm.processing"
                        @click="submitCreateRate"
                    >
                        <Spinner v-if="createRateForm.processing" />
                        Save
                    </Button>
                </div>
            </template>
        </StandardFormModal>

        <StandardFormModal
            v-if="canUpdateTaxRate"
            v-model:open="editRateModalOpen"
            title="Edit tax rate"
            :description="editingTaxRate?.name"
            size="lg"
            :dismiss-href="indexDismissHref"
        >
            <Form
                v-if="editingTaxRate"
                class="contents"
                @submit.prevent="submitEditRate"
            >
                <TaxRateForm :form="editRateForm" />
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
                        :disabled="editRateForm.processing"
                        @click="submitEditRate"
                    >
                        <Spinner v-if="editRateForm.processing" />
                        Update
                    </Button>
                </div>
            </template>
        </StandardFormModal>

        <StandardFormModal
            v-model:open="createGroupModalOpen"
            title="Add tax group"
            description="Combine multiple tax rates into one group."
            size="lg"
            :dismiss-href="indexDismissHref"
        >
            <Form class="contents" @submit.prevent="submitCreateGroup">
                <TaxGroupForm
                    :form="createGroupForm"
                    :tax-rate-options="taxRateOptions"
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
                        :disabled="
                            createGroupForm.processing ||
                            !createGroupForm.tax_rate_ids.length
                        "
                        @click="submitCreateGroup"
                    >
                        <Spinner v-if="createGroupForm.processing" />
                        Save
                    </Button>
                </div>
            </template>
        </StandardFormModal>

        <StandardFormModal
            v-model:open="editGroupModalOpen"
            title="Edit tax group"
            :description="editingTaxGroup?.name"
            size="lg"
            :dismiss-href="indexDismissHref"
        >
            <Form
                v-if="editingTaxGroup"
                class="contents"
                @submit.prevent="submitEditGroup"
            >
                <TaxGroupForm
                    :form="editGroupForm"
                    :tax-rate-options="taxRateOptions"
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
                        :disabled="
                            editGroupForm.processing ||
                            !editGroupForm.tax_rate_ids.length
                        "
                        @click="submitEditGroup"
                    >
                        <Spinner v-if="editGroupForm.processing" />
                        Update
                    </Button>
                </div>
            </template>
        </StandardFormModal>
    </div>
</template>
