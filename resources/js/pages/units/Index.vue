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
import UnitForm from '@/pages/units/UnitForm.vue';
import unitRoutes from '@/routes/units';
import type { Team } from '@/types';

type BaseRef = {
    id: number;
    name: string;
    short_name: string;
} | null;

type Row = {
    id: number;
    name: string;
    short_name: string;
    allow_decimal: boolean;
    is_multiple_of_base: boolean;
    base_unit_multiplier: string | null;
    base_unit: BaseRef;
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

type BaseUnitOption = { id: number; name: string; short_name: string };

type EditingUnit = {
    id: number;
    name: string;
    short_name: string;
    allow_decimal: boolean;
    is_multiple_of_base: boolean;
    base_unit_id: number | null;
    base_unit_multiplier: string | null;
};

const props = defineProps<{
    units: Paginated;
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
    };
    baseUnits: BaseUnitOption[];
    editingUnit: EditingUnit | null;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            { title: 'Units', href: unitRoutes.index.url(p.currentTeam!.slug) },
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
    | 'short_name'
    | 'allow_decimal'
    | 'base_relation'
    | 'created_at';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    { id: 'name', label: 'Name', sortKey: 'name' },
    { id: 'short_name', label: 'Short', sortKey: 'short_name' },
    {
        id: 'allow_decimal',
        label: 'Decimals',
        sortKey: 'allow_decimal',
    },
    { id: 'base_relation', label: 'Base relation', sortKey: null },
    { id: 'created_at', label: 'Created', sortKey: 'created_at' },
];

const columnVisibility = useStorage<Record<string, boolean>>(
    'units.datatable.columns',
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
    router.get(unitRoutes.index.url(teamSlug.value), indexQuery(overrides), {
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
        unitRoutes.index.url(teamSlug.value),
        indexQuery({ sort: sortKey, direction: dir }),
        { preserveState: true, replace: true },
    );
}

const createModalOpen = ref(false);
const editModalOpen = ref(false);

const indexDismissHref = computed(() => {
    const path = unitRoutes.index.url(teamSlug.value);
    const qs = new URLSearchParams(indexQuery()).toString();

    return qs ? `${path}?${qs}` : path;
});

const createForm = useForm({
    name: '',
    short_name: '',
    allow_decimal: false,
    is_multiple_of_base: false,
    base_unit_multiplier: '',
    base_unit_id: '',
});

const editForm = useForm({
    name: '',
    short_name: '',
    allow_decimal: false,
    is_multiple_of_base: false,
    base_unit_multiplier: '',
    base_unit_id: '',
});

watch(
    () => props.editingUnit,
    (u) => {
        if (u) {
            editModalOpen.value = true;
            editForm.name = String(u.name ?? '');
            editForm.short_name = String(u.short_name ?? '');
            editForm.allow_decimal = Boolean(u.allow_decimal);
            editForm.is_multiple_of_base = Boolean(u.is_multiple_of_base);
            editForm.base_unit_multiplier =
                u.base_unit_multiplier != null && u.base_unit_multiplier !== ''
                    ? String(u.base_unit_multiplier)
                    : '';
            editForm.base_unit_id =
                u.base_unit_id != null ? String(u.base_unit_id) : '';
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
        unitRoutes.index.url(teamSlug.value),
        indexQuery({ edit: row.id }),
        {
            preserveState: true,
            replace: true,
            only: ['editingUnit', 'baseUnits'],
        },
    );
}

function transformUnitPayload(data: {
    name: string;
    short_name: string;
    allow_decimal: boolean;
    is_multiple_of_base: boolean;
    base_unit_multiplier: string;
    base_unit_id: string;
}) {
    return {
        name: data.name,
        short_name: data.short_name,
        allow_decimal: data.allow_decimal === true,
        is_multiple_of_base: data.is_multiple_of_base === true,
        base_unit_multiplier:
            data.is_multiple_of_base === true && data.base_unit_multiplier !== ''
                ? data.base_unit_multiplier
                : null,
        base_unit_id:
            data.is_multiple_of_base === true && data.base_unit_id !== ''
                ? Number(data.base_unit_id)
                : null,
    };
}

function submitCreate() {
    createForm
        .transform((data) => transformUnitPayload(data))
        .post(unitRoutes.store.url(teamSlug.value), {
            onSuccess: () => {
                createModalOpen.value = false;
                createForm.reset();
            },
        });
}

function submitEdit() {
    const u = props.editingUnit;

    if (!u) {
        return;
    }

    editForm
        .transform((data) => transformUnitPayload(data))
        .put(
            unitRoutes.update.url({
                current_team: teamSlug.value,
                unit: u.id,
            }),
            { onSuccess: () => (editModalOpen.value = false) },
        );
}

function destroyUnit(row: Row) {
    if (!confirm('Delete this unit?')) {
        return;
    }

    router.delete(
        unitRoutes.destroy.url({
            current_team: teamSlug.value,
            unit: row.id,
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
    return unitRoutes.export.url(
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

function baseRelation(row: Row): string {
    if (
        row.is_multiple_of_base &&
        row.base_unit &&
        row.base_unit_multiplier != null &&
        row.base_unit_multiplier !== ''
    ) {
        return `1 ${row.name} = ${row.base_unit_multiplier} × ${row.base_unit.name}`;
    }

    return '—';
}

function displayCell(row: Row, col: ColId): string {
    switch (col) {
        case 'name':
            return row.name;
        case 'short_name':
            return row.short_name;
        case 'allow_decimal':
            return row.allow_decimal ? 'Yes' : 'No';
        case 'base_relation':
            return baseRelation(row);
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
    <Head title="Units" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div
            class="flex flex-col gap-4 print:hidden md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Units</h1>
                <p class="text-sm text-muted-foreground">
                    Measurement units, conversions, exports, and sorting.
                </p>
            </div>
            <Button type="button" @click="openCreateModal">Add unit</Button>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Name or short name…"
            :paginator="units"
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
                            v-for="row in units?.data ?? []"
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
                                        @click="destroyUnit(row)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!(units?.data?.length)">
                            <td
                                :colspan="visibleColumns.length + 1"
                                class="px-3 py-8 text-center text-muted-foreground"
                            >
                                No units match your filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
        </StandardDataTable>

        <StandardFormModal
            v-model:open="createModalOpen"
            title="Add unit"
            description="Names, decimals, and optional conversion to a base unit."
            size="xl"
            :dismiss-href="indexDismissHref"
        >
            <Form class="contents" @submit.prevent="submitCreate">
                <UnitForm :form="createForm" :base-units="props.baseUnits" />
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
            title="Edit unit"
            :description="editingUnit?.name"
            size="xl"
            :dismiss-href="indexDismissHref"
        >
            <Form
                v-if="editingUnit"
                class="contents"
                @submit.prevent="submitEdit"
            >
                <UnitForm :form="editForm" :base-units="props.baseUnits" />
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
