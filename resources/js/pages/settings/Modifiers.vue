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
import ModifierSetForm from '@/pages/modifier-sets/ModifierSetForm.vue';
import type { ModifierRow } from '@/pages/modifier-sets/ModifierSetForm.vue';
import settingsModifiersRoutes from '@/routes/settings/modifiers';
import type { Team } from '@/types';

type Row = {
    id: number;
    name: string;
    items_count: number;
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

type ItemRow = {
    id: number;
    name: string;
    price: string;
    sort_order: number;
};

type EditingModifierSet = {
    id: number;
    name: string;
    items?: ItemRow[];
};

const props = defineProps<{
    modifierSets: Paginated;
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
    };
    editingModifierSet: EditingModifierSet | null;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Modifiers',
                href: settingsModifiersRoutes.index.url(p.currentTeam!.slug),
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

type ColId = 'name' | 'items_count' | 'created_at';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    { id: 'name', label: 'Modifier set', sortKey: 'name' },
    { id: 'items_count', label: 'Modifiers', sortKey: null },
    { id: 'created_at', label: 'Created', sortKey: 'created_at' },
];

const columnVisibility = useStorage<Record<string, boolean>>(
    'settings.modifiers.datatable.columns',
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
        settingsModifiersRoutes.index.url(teamSlug.value),
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
        settingsModifiersRoutes.index.url(teamSlug.value),
        indexQuery({ sort: sortKey, direction: dir }),
        { preserveState: true, replace: true },
    );
}

const createModalOpen = ref(false);
const editModalOpen = ref(false);

const indexDismissHref = computed(() => {
    const path = settingsModifiersRoutes.index.url(teamSlug.value);
    const qs = new URLSearchParams(indexQuery()).toString();

    return qs ? `${path}?${qs}` : path;
});

function itemsToRows(items: ItemRow[] | undefined): ModifierRow[] {
    if (items?.length) {
        return items.map((it) => ({
            name: it.name,
            price: String(it.price ?? '0'),
        }));
    }

    return [{ name: '', price: '0' }];
}

const createForm = useForm({
    name: '',
    items: [{ name: '', price: '0' }] as ModifierRow[],
});

const editForm = useForm({
    name: '',
    items: [{ name: '', price: '0' }] as ModifierRow[],
});

watch(
    () => props.editingModifierSet,
    (m) => {
        if (m) {
            editModalOpen.value = true;
            editForm.name = String(m.name ?? '');
            editForm.items = itemsToRows(m.items);
        } else {
            editModalOpen.value = false;
        }
    },
    { immediate: true },
);

function openCreateModal() {
    createForm.reset();
    createForm.items = [{ name: '', price: '0' }];
    createForm.clearErrors();
    createModalOpen.value = true;
}

function openEditModal(row: Row) {
    router.get(
        settingsModifiersRoutes.index.url(teamSlug.value),
        indexQuery({ edit: row.id }),
        {
            preserveState: true,
            replace: true,
            only: ['editingModifierSet'],
        },
    );
}

function payloadFromForm(data: { name: string; items: ModifierRow[] }) {
    return {
        name: data.name,
        items: data.items
            .map((row) => ({
                name: row.name.trim(),
                price:
                    row.price === '' || row.price === null
                        ? 0
                        : Number(row.price),
            }))
            .filter((row) => row.name !== ''),
    };
}

function submitCreate() {
    createForm
        .transform((d) => payloadFromForm(d))
        .post(settingsModifiersRoutes.store.url(teamSlug.value), {
            onSuccess: () => {
                createModalOpen.value = false;
                createForm.reset();
                createForm.items = [{ name: '', price: '0' }];
            },
        });
}

function submitEdit() {
    const m = props.editingModifierSet;

    if (!m) {
        return;
    }

    editForm
        .transform((d) => payloadFromForm(d))
        .put(
            settingsModifiersRoutes.update.url({
                current_team: teamSlug.value,
                modifier_set: m.id,
            }),
            { onSuccess: () => (editModalOpen.value = false) },
        );
}

function destroySet(row: Row) {
    if (!confirm('Delete this modifier set?')) {
        return;
    }

    router.delete(
        settingsModifiersRoutes.destroy.url({
            current_team: teamSlug.value,
            modifier_set: row.id,
        }),
    );
}

function goToPage(url: string | null) {
    if (url) {
        router.visit(url, { preserveState: true, replace: true });
    }
}

function displayCell(row: Row, col: ColId): string {
    if (col === 'name') {
        return row.name;
    }

    if (col === 'items_count') {
        return String(row.items_count ?? 0);
    }

    return row.created_at
        ? new Date(row.created_at).toLocaleDateString()
        : '—';
}

function sortIndicator(sortKey: string | null): string {
    if (!sortKey || props.filters.sort !== sortKey) {
        return '';
    }

    return props.filters.direction === 'asc' ? ' ↑' : ' ↓';
}
</script>

<template>
    <Head title="Modifiers" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Modifiers</h1>
                <p class="text-sm text-muted-foreground">
                    Modifier sets and per-option prices (e.g. add-ons for POS items).
                </p>
            </div>
            <Button type="button" @click="openCreateModal">Add modifier</Button>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Modifier set or modifier name…"
            :per-page-options="[10, 15, 25, 50]"
            :paginator="modifierSets"
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

            <table class="w-full min-w-[520px] border-collapse text-sm">
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
                        v-for="row in modifierSets?.data ?? []"
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
                                    @click="destroySet(row)"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!(modifierSets?.data?.length)">
                        <td
                            :colspan="visibleColumns.length + 1"
                            class="px-3 py-8 text-center text-muted-foreground"
                        >
                            No modifier sets yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </StandardDataTable>

        <StandardFormModal
            v-model:open="createModalOpen"
            title="Add Modifier"
            description="Modifier set name and one or more modifiers with prices."
            size="xl"
            :dismiss-href="indexDismissHref"
        >
            <Form class="contents" @submit.prevent="submitCreate">
                <ModifierSetForm :form="createForm" />
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
            title="Edit modifier"
            :description="editingModifierSet?.name"
            size="xl"
            :dismiss-href="indexDismissHref"
        >
            <Form
                v-if="editingModifierSet"
                class="contents"
                @submit.prevent="submitEdit"
            >
                <ModifierSetForm :form="editForm" />
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
