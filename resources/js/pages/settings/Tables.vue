<script setup lang="ts">
import {
    Form,
    Head,
    Link,
    router,
    useForm,
    usePage,
} from '@inertiajs/vue3';
import { useDebounceFn, useStorage } from '@vueuse/core';
import { Pencil, Table, Trash2 } from 'lucide-vue-next';
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
import businessLocationsRoutes from '@/routes/business-locations';
import settingsTableRoutes from '@/routes/settings/tables';
import type { Team } from '@/types';

type Row = {
    id: number;
    name: string;
    description: string | null;
    business_location_id: number;
    business_location?: { id: number; name: string } | null;
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

type EditingTable = {
    id: number;
    name: string;
    description: string | null;
    business_location_id: number;
};

const props = defineProps<{
    tables: Paginated;
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
    };
    businessLocations: { id: number; name: string }[];
    editingTable: EditingTable | null;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Tables',
                href: settingsTableRoutes.index.url(p.currentTeam!.slug),
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
    | 'business_location'
    | 'name'
    | 'description'
    | 'created_at';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    {
        id: 'business_location',
        label: 'Business location',
        sortKey: 'business_location_id',
    },
    { id: 'name', label: 'Table name', sortKey: 'name' },
    { id: 'description', label: 'Description', sortKey: null },
    { id: 'created_at', label: 'Created', sortKey: 'created_at' },
];

const columnVisibility = useStorage<Record<string, boolean>>(
    'settings.tables.datatable.columns',
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
        settingsTableRoutes.index.url(teamSlug.value),
        indexQuery(overrides),
        { preserveState: true, replace: true },
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
        settingsTableRoutes.index.url(teamSlug.value),
        indexQuery({ sort: sortKey, direction: dir }),
        { preserveState: true, replace: true },
    );
}

const createModalOpen = ref(false);
const editModalOpen = ref(false);

const indexDismissHref = computed(() => {
    const path = settingsTableRoutes.index.url(teamSlug.value);
    const qs = new URLSearchParams(indexQuery()).toString();

    return qs ? `${path}?${qs}` : path;
});

const defaultLocationId = computed(() =>
    props.businessLocations[0] ? String(props.businessLocations[0].id) : '',
);

const createForm = useForm({
    business_location_id: '',
    name: '',
    description: '',
});

const editForm = useForm({
    business_location_id: '',
    name: '',
    description: '',
});

watch(
    () => props.editingTable,
    (t) => {
        if (t) {
            editModalOpen.value = true;
            editForm.business_location_id = String(t.business_location_id);
            editForm.name = String(t.name ?? '');
            editForm.description = String(t.description ?? '');
        } else {
            editModalOpen.value = false;
        }
    },
    { immediate: true },
);

function openCreateModal() {
    createForm.reset();
    createForm.business_location_id = defaultLocationId.value;
    createForm.clearErrors();
    createModalOpen.value = true;
}

function openEditModal(row: Row) {
    router.get(
        settingsTableRoutes.index.url(teamSlug.value),
        indexQuery({ edit: row.id }),
        {
            preserveState: true,
            replace: true,
            only: ['editingTable'],
        },
    );
}

function submitCreate() {
    createForm
        .transform((d) => ({
            business_location_id: Number(d.business_location_id),
            name: d.name.trim(),
            description: d.description?.trim() || null,
        }))
        .post(settingsTableRoutes.store.url(teamSlug.value), {
            onSuccess: () => {
                createModalOpen.value = false;
                createForm.reset();
            },
        });
}

function submitEdit() {
    const t = props.editingTable;

    if (!t) {
        return;
    }

    editForm
        .transform((d) => ({
            business_location_id: Number(d.business_location_id),
            name: d.name.trim(),
            description: d.description?.trim() || null,
        }))
        .patch(
            settingsTableRoutes.update.url({
                current_team: teamSlug.value,
                restaurant_table: t.id,
            }),
            { onSuccess: () => (editModalOpen.value = false) },
        );
}

function destroyTable(row: Row) {
    if (!confirm('Delete this table?')) {
        return;
    }

    router.delete(
        settingsTableRoutes.destroy.url({
            current_team: teamSlug.value,
            restaurant_table: row.id,
        }),
    );
}

function goToPage(url: string | null) {
    if (!url) {
        return;
    }

    router.visit(url, { preserveState: true, replace: true });
}

function displayCell(row: Row, col: ColId): string {
    switch (col) {
        case 'business_location':
            return row.business_location?.name?.trim()
                ? row.business_location.name
                : '—';
        case 'name':
            return row.name;
        case 'description':
            return row.description?.trim() ? row.description : '—';
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
    <Head title="Tables" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div
            class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Tables</h1>
                <p class="text-sm text-muted-foreground">
                    Dining or service tables by business location for POS and
                    reservations.
                </p>
            </div>
            <Button
                type="button"
                :disabled="!businessLocations.length"
                @click="openCreateModal"
            >
                Add table
            </Button>
        </div>

        <p
            v-if="!businessLocations.length"
            class="rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-950 dark:text-amber-100"
        >
            Add a
            <Button variant="link" class="h-auto p-0" as-child>
                <Link
                    :href="businessLocationsRoutes.index.url(teamSlug)"
                >
                    business location
                </Link>
            </Button>
            first; tables are scoped to a location.
        </p>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Name, location…"
            :per-page-options="[10, 15, 25, 50]"
            :paginator="tables"
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
                            class="bg-muted/40 px-3 py-2 text-right font-medium"
                        >
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in tables.data ?? []"
                        :key="row.id"
                        class="border-b border-border/80 hover:bg-muted/20"
                    >
                        <td
                            v-for="col in visibleColumns"
                            :key="col.id"
                            class="px-3 py-2"
                        >
                            {{ displayCell(row, col.id) }}
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
                                    @click="destroyTable(row)"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!(tables?.data?.length)">
                        <td
                            :colspan="visibleColumns.length + 1"
                            class="px-3 py-8 text-center text-muted-foreground"
                        >
                            <Table
                                class="mx-auto mb-2 size-8 opacity-40"
                                aria-hidden="true"
                            />
                            No tables yet. Add one for each physical table at a
                            location.
                        </td>
                    </tr>
                </tbody>
            </table>
        </StandardDataTable>

        <StandardFormModal
            v-model:open="createModalOpen"
            title="Add table"
            description="Choose the location, then name the table."
            :dismiss-href="indexDismissHref"
        >
            <Form class="contents" @submit.prevent="submitCreate">
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="create-location">Business location *</Label>
                        <Select
                            id="create-location"
                            v-model="createForm.business_location_id"
                            required
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Please select" />
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
                            v-if="createForm.errors.business_location_id"
                            class="text-destructive text-sm"
                        >
                            {{ createForm.errors.business_location_id }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="create-name">Table name *</Label>
                        <Input
                            id="create-name"
                            v-model="createForm.name"
                            required
                            placeholder="Table name"
                            autocomplete="off"
                        />
                        <p
                            v-if="createForm.errors.name"
                            class="text-destructive text-sm"
                        >
                            {{ createForm.errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="create-desc">Short description</Label>
                        <Input
                            id="create-desc"
                            v-model="createForm.description"
                            placeholder="Short description"
                            autocomplete="off"
                        />
                        <p
                            v-if="createForm.errors.description"
                            class="text-destructive text-sm"
                        >
                            {{ createForm.errors.description }}
                        </p>
                    </div>
                </div>
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
            title="Edit table"
            :description="editingTable?.name"
            :dismiss-href="indexDismissHref"
        >
            <Form
                v-if="editingTable"
                class="contents"
                @submit.prevent="submitEdit"
            >
                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="edit-location">Business location *</Label>
                        <Select
                            id="edit-location"
                            v-model="editForm.business_location_id"
                            required
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Please select" />
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
                            v-if="editForm.errors.business_location_id"
                            class="text-destructive text-sm"
                        >
                            {{ editForm.errors.business_location_id }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-name">Table name *</Label>
                        <Input
                            id="edit-name"
                            v-model="editForm.name"
                            required
                            placeholder="Table name"
                            autocomplete="off"
                        />
                        <p
                            v-if="editForm.errors.name"
                            class="text-destructive text-sm"
                        >
                            {{ editForm.errors.name }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-desc">Short description</Label>
                        <Input
                            id="edit-desc"
                            v-model="editForm.description"
                            placeholder="Short description"
                            autocomplete="off"
                        />
                        <p
                            v-if="editForm.errors.description"
                            class="text-destructive text-sm"
                        >
                            {{ editForm.errors.description }}
                        </p>
                    </div>
                </div>
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
