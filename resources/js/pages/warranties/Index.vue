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
import WarrantyForm from '@/pages/warranties/WarrantyForm.vue';
import warrantyRoutes from '@/routes/warranties';
import type { Team } from '@/types';

type Row = {
    id: number;
    name: string;
    description: string | null;
    duration_value: number;
    duration_unit: string;
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

type EditingWarranty = {
    id: number;
    name: string;
    description: string | null;
    duration_value: number;
    duration_unit: string;
};

const props = defineProps<{
    warranties: Paginated;
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
        duration_unit: string;
    };
    editingWarranty: EditingWarranty | null;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Warranties',
                href: warrantyRoutes.index.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

const search = ref(props.filters.search ?? '');
const unitFilter = ref(props.filters.duration_unit || 'all');
const perPage = ref(String(props.filters.per_page ?? 15));

type ColId = 'name' | 'description' | 'duration' | 'created_at';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    { id: 'name', label: 'Name', sortKey: 'name' },
    { id: 'description', label: 'Description', sortKey: null },
    { id: 'duration', label: 'Duration', sortKey: 'duration_value' },
    { id: 'created_at', label: 'Created', sortKey: 'created_at' },
];

const columnVisibility = useStorage<Record<string, boolean>>(
    'warranties.datatable.columns',
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
        duration_unit: unitFilter.value === 'all' ? '' : unitFilter.value,
    };

    for (const [k, v] of Object.entries(overrides)) {
        if (v !== undefined && v !== '') {
            q[k] = String(v);
        }
    }

    return q;
}

function visitWithFilters(overrides: Record<string, string | number> = {}) {
    router.get(warrantyRoutes.index.url(teamSlug.value), indexQuery(overrides), {
        preserveState: true,
        replace: true,
    });
}

const debouncedSearch = useDebounceFn(() => visitWithFilters(), 350);

watch(search, () => debouncedSearch());
watch(unitFilter, () => visitWithFilters());
watch(perPage, (v) => visitWithFilters({ per_page: Number(v) }));

function toggleSort(sortKey: string) {
    const isCurrent = props.filters.sort === sortKey;
    const dir =
        isCurrent && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(
        warrantyRoutes.index.url(teamSlug.value),
        indexQuery({ sort: sortKey, direction: dir }),
        { preserveState: true, replace: true },
    );
}

const createModalOpen = ref(false);
const editModalOpen = ref(false);

const indexDismissHref = computed(() => {
    const path = warrantyRoutes.index.url(teamSlug.value);
    const qs = new URLSearchParams(indexQuery()).toString();

    return qs ? `${path}?${qs}` : path;
});

const createForm = useForm({
    name: '',
    description: '',
    duration_value: '1',
    duration_unit: 'month',
});

const editForm = useForm({
    name: '',
    description: '',
    duration_value: '1',
    duration_unit: 'month',
});

watch(
    () => props.editingWarranty,
    (w) => {
        if (w) {
            editModalOpen.value = true;
            editForm.name = String(w.name ?? '');
            editForm.description = String(w.description ?? '');
            editForm.duration_value = String(w.duration_value ?? '1');
            editForm.duration_unit = String(w.duration_unit ?? 'month');
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
        warrantyRoutes.index.url(teamSlug.value),
        indexQuery({ edit: row.id }),
        {
            preserveState: true,
            replace: true,
            only: ['editingWarranty'],
        },
    );
}

function submitCreate() {
    createForm
        .transform((data) => ({
            name: data.name,
            description: data.description || null,
            duration_value: Number(data.duration_value),
            duration_unit: data.duration_unit,
        }))
        .post(warrantyRoutes.store.url(teamSlug.value), {
            onSuccess: () => {
                createModalOpen.value = false;
                createForm.reset();
            },
        });
}

function submitEdit() {
    const w = props.editingWarranty;

    if (!w) {
        return;
    }

    editForm
        .transform((data) => ({
            name: data.name,
            description: data.description || null,
            duration_value: Number(data.duration_value),
            duration_unit: data.duration_unit,
        }))
        .put(
            warrantyRoutes.update.url({
                current_team: teamSlug.value,
                warranty: w.id,
            }),
            { onSuccess: () => (editModalOpen.value = false) },
        );
}

function destroyWarranty(row: Row) {
    if (!confirm('Delete this warranty?')) {
        return;
    }

    router.delete(
        warrantyRoutes.destroy.url({
            current_team: teamSlug.value,
            warranty: row.id,
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
    return warrantyRoutes.export.url(
        { current_team: teamSlug.value, format },
        {
            query: {
                search: search.value || undefined,
                sort: props.filters.sort,
                direction: props.filters.direction,
                duration_unit:
                    unitFilter.value === 'all' ? undefined : unitFilter.value,
            },
        },
    );
}

function printTable() {
    window.print();
}

function unitLabel(u: string): string {
    const m: Record<string, string> = {
        day: 'day(s)',
        week: 'week(s)',
        month: 'month(s)',
        year: 'year(s)',
    };

    return m[u] ?? u;
}

function displayCell(row: Row, col: ColId): string {
    switch (col) {
        case 'name':
            return row.name;
        case 'description':
            return row.description?.trim() ? row.description : '—';
        case 'duration':
            return `${row.duration_value} ${unitLabel(row.duration_unit)}`;
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
    <Head title="Warranties" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div
            class="flex flex-col gap-4 print:hidden md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Warranties
                </h1>
                <p class="text-sm text-muted-foreground">
                    Product warranty terms, exports, and sorting.
                </p>
            </div>
            <Button type="button" @click="openCreateModal">
                Add warranty
            </Button>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Name or description…"
            :paginator="warranties"
            @page="goToPage"
        >
            <template #filters>
                <Select v-model="unitFilter">
                    <SelectTrigger class="w-full" aria-label="Duration unit">
                        <SelectValue placeholder="All" />
                    </SelectTrigger>
                    <SelectContent
                        position="popper"
                        side="bottom"
                        align="start"
                        :side-offset="4"
                    >
                        <SelectItem value="all">All</SelectItem>
                        <SelectItem value="day">Day</SelectItem>
                        <SelectItem value="week">Week</SelectItem>
                        <SelectItem value="month">Month</SelectItem>
                        <SelectItem value="year">Year</SelectItem>
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
                            v-for="row in warranties?.data ?? []"
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
                                        @click="destroyWarranty(row)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!(warranties?.data?.length)">
                            <td
                                :colspan="visibleColumns.length + 1"
                                class="px-3 py-8 text-center text-muted-foreground"
                            >
                                No warranties match your filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
        </StandardDataTable>

        <StandardFormModal
            v-model:open="createModalOpen"
            title="Add warranty"
            description="Name, description, and duration."
            size="lg"
            :dismiss-href="indexDismissHref"
        >
            <Form class="contents" @submit.prevent="submitCreate">
                <WarrantyForm :form="createForm" />
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
            title="Edit warranty"
            :description="editingWarranty?.name"
            size="lg"
            :dismiss-href="indexDismissHref"
        >
            <Form
                v-if="editingWarranty"
                class="contents"
                @submit.prevent="submitEdit"
            >
                <WarrantyForm :form="editForm" />
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
