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
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Spinner } from '@/components/ui/spinner';
import VariationForm from '@/pages/variation-templates/VariationForm.vue';
import variationTemplateRoutes from '@/routes/variation-templates';
import type { Team } from '@/types';

type Row = {
    id: number;
    name: string;
    values_count: number;
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

type EditingVt = {
    id: number;
    name: string;
    values: { id: number; value: string; sort_order: number }[];
};

const props = defineProps<{
    variationTemplates: Paginated;
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
    };
    editingVariationTemplate: EditingVt | null;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Variations',
                href: variationTemplateRoutes.index.url(p.currentTeam!.slug),
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

type ColId = 'name' | 'values_count' | 'created_at';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    { id: 'name', label: 'Name', sortKey: 'name' },
    { id: 'values_count', label: 'Values', sortKey: null },
    { id: 'created_at', label: 'Created', sortKey: 'created_at' },
];

const columnVisibility = useStorage<Record<string, boolean>>(
    'variation-templates.datatable.columns',
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
        variationTemplateRoutes.index.url(teamSlug.value),
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
        variationTemplateRoutes.index.url(teamSlug.value),
        indexQuery({ sort: sortKey, direction: dir }),
        { preserveState: true, replace: true },
    );
}

const createModalOpen = ref(false);
const editModalOpen = ref(false);

const indexDismissHref = computed(() => {
    const path = variationTemplateRoutes.index.url(teamSlug.value);
    const qs = new URLSearchParams(indexQuery()).toString();

    return qs ? `${path}?${qs}` : path;
});

const createForm = useForm({ name: '', values: [''] });
const editForm = useForm({ name: '', values: [''] as string[] });

function valuesFromTemplate(t: EditingVt): string[] {
    const vals =
        t.values?.length && t.values.some((v) => v.value)
            ? t.values.map((v) => v.value)
            : [''];

    return [...vals];
}

watch(
    () => props.editingVariationTemplate,
    (t) => {
        if (t) {
            editModalOpen.value = true;
            editForm.name = String(t.name ?? '');
            editForm.values = valuesFromTemplate(t);
        } else {
            editModalOpen.value = false;
        }
    },
    { immediate: true },
);

function openCreateModal() {
    createForm.reset();
    createForm.name = '';
    createForm.values = [''];
    createForm.clearErrors();
    createModalOpen.value = true;
}

function openEditModal(row: Row) {
    router.get(
        variationTemplateRoutes.index.url(teamSlug.value),
        indexQuery({ edit: row.id }),
        {
            preserveState: true,
            replace: true,
            only: ['editingVariationTemplate'],
        },
    );
}

function submitCreate() {
    createForm
        .transform((d) => ({
            name: d.name,
            values: d.values.map((v) => v.trim()).filter((v) => v !== ''),
        }))
        .post(variationTemplateRoutes.store.url(teamSlug.value), {
            onSuccess: () => {
                createModalOpen.value = false;
                createForm.reset();
                createForm.values = [''];
            },
        });
}

function submitEdit() {
    const t = props.editingVariationTemplate;

    if (!t) {
        return;
    }

    editForm
        .transform((d) => ({
            name: d.name,
            values: d.values.map((v) => v.trim()).filter((v) => v !== ''),
        }))
        .put(
            variationTemplateRoutes.update.url({
                current_team: teamSlug.value,
                variation_template: t.id,
            }),
            { onSuccess: () => (editModalOpen.value = false) },
        );
}

function destroyVt() {
    const t = props.editingVariationTemplate;

    if (!t || !confirm('Delete this variation template?')) {
        return;
    }

    router.delete(
        variationTemplateRoutes.destroy.url({
            current_team: teamSlug.value,
            variation_template: t.id,
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

    if (col === 'values_count') {
        return String(row.values_count ?? 0);
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
    <Head title="Variation templates" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Variations</h1>
                <p class="text-sm text-muted-foreground">
                    Reusable variation names and value lists for variable products.
                </p>
            </div>
            <Button type="button" @click="openCreateModal">
                Add variation
            </Button>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Name…"
            :per-page-options="[10, 15, 25, 50]"
            :paginator="variationTemplates"
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
                            <th class="bg-muted/40 px-3 py-2 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in variationTemplates.data ?? []"
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
                                <button
                                    type="button"
                                    class="text-primary text-sm font-medium hover:underline"
                                    @click="openEditModal(row)"
                                >
                                    Edit
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!(variationTemplates?.data?.length)">
                            <td
                                :colspan="visibleColumns.length + 1"
                                class="px-3 py-8 text-center text-muted-foreground"
                            >
                                No variation templates yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
        </StandardDataTable>

        <StandardFormModal
            v-model:open="createModalOpen"
            title="Add variation template"
            description="Template name and a list of allowed values."
            size="lg"
            :dismiss-href="indexDismissHref"
        >
            <Form class="contents" @submit.prevent="submitCreate">
                <VariationForm :form="createForm" />
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
            title="Edit variation template"
            :description="editingVariationTemplate?.name"
            size="lg"
            :dismiss-href="indexDismissHref"
        >
            <Form
                v-if="editingVariationTemplate"
                class="contents"
                @submit.prevent="submitEdit"
            >
                <VariationForm :form="editForm" />
            </Form>
            <template #footer>
                <div class="flex w-full flex-wrap items-center justify-between gap-2">
                    <Button type="button" variant="destructive" @click="destroyVt">
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
