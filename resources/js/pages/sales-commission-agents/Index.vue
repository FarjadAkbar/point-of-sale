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
import SalesCommissionAgentForm from '@/pages/sales-commission-agents/SalesCommissionAgentForm.vue';
import salesCommissionAgentRoutes from '@/routes/sales-commission-agents';
import type { Team } from '@/types';

type Row = {
    id: number;
    prefix: string | null;
    first_name: string;
    last_name: string | null;
    email: string | null;
    contact_no: string | null;
    address: string | null;
    cmmsn_percent: string;
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

type EditingSalesCommissionAgent = {
    id: number;
    prefix: string | null;
    first_name: string;
    last_name: string | null;
    email: string | null;
    contact_no: string | null;
    address: string | null;
    cmmsn_percent: string;
};

const props = defineProps<{
    salesCommissionAgents: Paginated;
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
    };
    editingSalesCommissionAgent: EditingSalesCommissionAgent | null;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Sales commission agents',
                href: salesCommissionAgentRoutes.index.url(p.currentTeam!.slug),
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
    | 'name'
    | 'email'
    | 'contact_no'
    | 'cmmsn_percent'
    | 'created_at';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    { id: 'name', label: 'Name', sortKey: 'first_name' },
    { id: 'email', label: 'Email', sortKey: 'email' },
    { id: 'contact_no', label: 'Contact', sortKey: null },
    { id: 'cmmsn_percent', label: 'Commission %', sortKey: 'cmmsn_percent' },
    { id: 'created_at', label: 'Created', sortKey: 'created_at' },
];

const columnVisibility = useStorage<Record<string, boolean>>(
    'sales-commission-agents.datatable.columns',
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
        salesCommissionAgentRoutes.index.url(teamSlug.value),
        indexQuery(overrides),
        {
            preserveState: true,
            replace: true,
        },
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
        salesCommissionAgentRoutes.index.url(teamSlug.value),
        indexQuery({ sort: sortKey, direction: dir }),
        { preserveState: true, replace: true },
    );
}

const createModalOpen = ref(false);
const editModalOpen = ref(false);

const indexDismissHref = computed(() => {
    const path = salesCommissionAgentRoutes.index.url(teamSlug.value);
    const qs = new URLSearchParams(indexQuery()).toString();

    return qs ? `${path}?${qs}` : path;
});

function emptyAgentForm() {
    return {
        prefix: '',
        first_name: '',
        last_name: '',
        email: '',
        contact_no: '',
        address: '',
        cmmsn_percent: '',
    };
}

const createForm = useForm(emptyAgentForm());
const editForm = useForm(emptyAgentForm());

watch(
    () => props.editingSalesCommissionAgent,
    (a) => {
        if (a) {
            editModalOpen.value = true;
            editForm.prefix = String(a.prefix ?? '');
            editForm.first_name = String(a.first_name ?? '');
            editForm.last_name = String(a.last_name ?? '');
            editForm.email = String(a.email ?? '');
            editForm.contact_no = String(a.contact_no ?? '');
            editForm.address = String(a.address ?? '');
            editForm.cmmsn_percent =
                a.cmmsn_percent != null && a.cmmsn_percent !== ''
                    ? String(a.cmmsn_percent)
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

function openEditModal(row: Row) {
    router.get(
        salesCommissionAgentRoutes.index.url(teamSlug.value),
        indexQuery({ edit: row.id }),
        {
            preserveState: true,
            replace: true,
            only: ['editingSalesCommissionAgent'],
        },
    );
}

function transformPayload(data: {
    prefix: string;
    first_name: string;
    last_name: string;
    email: string;
    contact_no: string;
    address: string;
    cmmsn_percent: string;
}) {
    return {
        prefix: data.prefix?.trim() ? data.prefix.trim() : null,
        first_name: data.first_name.trim(),
        last_name: data.last_name?.trim() ? data.last_name.trim() : null,
        email: data.email?.trim() ? data.email.trim() : null,
        contact_no: data.contact_no?.trim() ? data.contact_no.trim() : null,
        address: data.address?.trim() ? data.address.trim() : null,
        cmmsn_percent: data.cmmsn_percent.trim(),
    };
}

function submitCreate() {
    createForm
        .transform((data) => transformPayload(data))
        .post(salesCommissionAgentRoutes.store.url(teamSlug.value), {
            onSuccess: () => {
                createModalOpen.value = false;
                createForm.reset();
            },
        });
}

function submitEdit() {
    const a = props.editingSalesCommissionAgent;

    if (!a) {
        return;
    }

    editForm
        .transform((data) => transformPayload(data))
        .put(
            salesCommissionAgentRoutes.update.url({
                current_team: teamSlug.value,
                sales_commission_agent: a.id,
            }),
            {
                onSuccess: () => {
                    editModalOpen.value = false;
                },
            },
        );
}

function destroyAgent(row: Row) {
    if (!confirm('Delete this sales commission agent?')) {
        return;
    }

    router.delete(
        salesCommissionAgentRoutes.destroy.url({
            current_team: teamSlug.value,
            sales_commission_agent: row.id,
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
    return salesCommissionAgentRoutes.export.url(
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

function displayName(row: {
    prefix: string | null;
    first_name: string;
    last_name: string | null;
}): string {
    const parts = [row.prefix, row.first_name, row.last_name].filter(
        (p) => p != null && String(p).trim() !== '',
    );

    return parts.length ? parts.join(' ') : '—';
}

function displayCell(row: Row, col: ColId): string {
    switch (col) {
        case 'name':
            return displayName(row);
        case 'email':
            return row.email?.trim() ? row.email : '—';
        case 'contact_no':
            return row.contact_no?.trim() ? row.contact_no : '—';
        case 'cmmsn_percent':
            return row.cmmsn_percent != null && row.cmmsn_percent !== ''
                ? `${row.cmmsn_percent}%`
                : '—';
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
    <Head title="Sales commission agents" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div
            class="flex flex-col gap-4 print:hidden md:flex-row md:items-center md:justify-between"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Sales commission agents
                </h1>
                <p class="text-sm text-muted-foreground">
                    People who earn a percentage on sales; search, export, and
                    sort.
                </p>
            </div>
            <Button type="button" @click="openCreateModal">
                Add agent
            </Button>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Name, email, phone…"
            :paginator="salesCommissionAgents"
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
                            class="bg-muted/40 px-3 py-2 text-right font-medium print:hidden"
                        >
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in salesCommissionAgents.data ?? []"
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
                                    @click="destroyAgent(row)"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!(salesCommissionAgents?.data?.length)">
                        <td
                            :colspan="visibleColumns.length + 1"
                            class="px-3 py-8 text-center text-muted-foreground"
                        >
                            No sales commission agents match your filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </StandardDataTable>

        <StandardFormModal
            v-model:open="createModalOpen"
            title="Add sales commission agent"
            description="Prefix, name, contact details, and commission rate."
            size="xl"
            :dismiss-href="indexDismissHref"
        >
            <Form class="contents" @submit.prevent="submitCreate">
                <SalesCommissionAgentForm :form="createForm" />
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
            title="Edit sales commission agent"
            :description="
                editingSalesCommissionAgent
                    ? displayName(editingSalesCommissionAgent)
                    : undefined
            "
            size="xl"
            :dismiss-href="indexDismissHref"
        >
            <Form
                v-if="editingSalesCommissionAgent"
                class="contents"
                @submit.prevent="submitEdit"
            >
                <SalesCommissionAgentForm :form="editForm" />
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
