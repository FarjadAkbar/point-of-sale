<script setup lang="ts">
import {
    Form,
    Head,
    router,
    useForm,
    usePage,
} from '@inertiajs/vue3';
import { useDebounceFn, useStorage } from '@vueuse/core';
import { MapPin, Pencil, Trash2 } from 'lucide-vue-next';
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
import BusinessLocationForm from '@/pages/business-locations/BusinessLocationForm.vue';
import businessLocationRoutes from '@/routes/business-locations';
import type { Team } from '@/types';

const NONE = '__none__';

type Row = {
    id: number;
    name: string;
    location_id: string | null;
    city: string | null;
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

type EditingLoc = {
    id: number;
    name: string;
    location_id: string | null;
    landmark: string | null;
    city: string | null;
    zip_code: string | null;
    state: string | null;
    country: string | null;
    mobile: string | null;
    alternate_contact_number: string | null;
    email: string | null;
    website: string | null;
    default_selling_price_group_id: number | null;
    featured_product_ids: number[];
    featured_products: { id: number; name: string; sku: string | null }[];
};

const props = defineProps<{
    businessLocations: Paginated;
    sellingPriceGroups: { id: number; name: string }[];
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
    };
    editingBusinessLocation: EditingLoc | null;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Business locations',
                href: businessLocationRoutes.index.url(p.currentTeam!.slug),
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

type ColId = 'name' | 'location_id' | 'city' | 'created_at';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    { id: 'name', label: 'Name', sortKey: 'name' },
    { id: 'location_id', label: 'Location ID', sortKey: null },
    { id: 'city', label: 'City', sortKey: 'city' },
    { id: 'created_at', label: 'Created', sortKey: 'created_at' },
];

const columnVisibility = useStorage<Record<string, boolean>>(
    'business-locations.datatable.columns',
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
        businessLocationRoutes.index.url(teamSlug.value),
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
        businessLocationRoutes.index.url(teamSlug.value),
        indexQuery({ sort: sortKey, direction: dir }),
        { preserveState: true, replace: true },
    );
}

const createModalOpen = ref(false);
const editModalOpen = ref(false);
const createFormKey = ref(0);

const indexDismissHref = computed(() => {
    const path = businessLocationRoutes.index.url(teamSlug.value);
    const qs = new URLSearchParams(indexQuery()).toString();

    return qs ? `${path}?${qs}` : path;
});

function emptyFormState() {
    return {
        name: '',
        location_id: '',
        landmark: '',
        city: '',
        zip_code: '',
        state: '',
        country: '',
        mobile: '',
        alternate_contact_number: '',
        email: '',
        website: '',
        default_selling_price_group_id: NONE,
        featured_product_ids: [] as number[],
    };
}

const createForm = useForm(emptyFormState());
const editForm = useForm(emptyFormState());

watch(
    () => props.editingBusinessLocation,
    (loc) => {
        if (loc) {
            editModalOpen.value = true;
            editForm.name = String(loc.name ?? '');
            editForm.location_id = String(loc.location_id ?? '');
            editForm.landmark = String(loc.landmark ?? '');
            editForm.city = String(loc.city ?? '');
            editForm.zip_code = String(loc.zip_code ?? '');
            editForm.state = String(loc.state ?? '');
            editForm.country = String(loc.country ?? '');
            editForm.mobile = String(loc.mobile ?? '');
            editForm.alternate_contact_number = String(
                loc.alternate_contact_number ?? '',
            );
            editForm.email = String(loc.email ?? '');
            editForm.website = String(loc.website ?? '');
            editForm.default_selling_price_group_id = loc.default_selling_price_group_id
                ? String(loc.default_selling_price_group_id)
                : NONE;
            editForm.featured_product_ids = [...(loc.featured_product_ids ?? [])];
        } else {
            editModalOpen.value = false;
        }
    },
    { immediate: true },
);

function openCreateModal() {
    createFormKey.value += 1;
    createForm.reset();
    createForm.clearErrors();
    createModalOpen.value = true;
}

function openEditModal(row: Row) {
    router.get(
        businessLocationRoutes.index.url(teamSlug.value),
        indexQuery({ edit: row.id }),
        {
            preserveState: true,
            replace: true,
            only: ['editingBusinessLocation'],
        },
    );
}

function payloadFromForm(d: {
    name: string;
    location_id: string;
    landmark: string;
    city: string;
    zip_code: string;
    state: string;
    country: string;
    mobile: string;
    alternate_contact_number: string;
    email: string;
    website: string;
    default_selling_price_group_id: string;
    featured_product_ids: number[];
}): Record<string, unknown> {
    return {
        name: d.name,
        location_id: d.location_id || null,
        landmark: d.landmark || null,
        city: d.city || null,
        zip_code: d.zip_code || null,
        state: d.state || null,
        country: d.country || null,
        mobile: d.mobile || null,
        alternate_contact_number: d.alternate_contact_number || null,
        email: d.email || null,
        website: d.website || null,
        default_selling_price_group_id:
            d.default_selling_price_group_id === NONE ||
            d.default_selling_price_group_id === ''
                ? null
                : Number(d.default_selling_price_group_id),
        featured_product_ids: d.featured_product_ids,
    };
}

function submitCreate() {
    createForm
        .transform((d) => payloadFromForm(d))
        .post(businessLocationRoutes.store.url(teamSlug.value), {
            onSuccess: () => {
                createModalOpen.value = false;
                createForm.reset();
            },
        });
}

function submitEdit() {
    const loc = props.editingBusinessLocation;

    if (!loc) {
        return;
    }

    editForm
        .transform((d) => payloadFromForm(d))
        .put(
            businessLocationRoutes.update.url({
                current_team: teamSlug.value,
                business_location: loc.id,
            }),
            { onSuccess: () => (editModalOpen.value = false) },
        );
}

function destroyLocation(row: Row) {
    if (!confirm('Delete this business location?')) {
        return;
    }

    router.delete(
        businessLocationRoutes.destroy.url({
            current_team: teamSlug.value,
            business_location: row.id,
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

    if (col === 'location_id') {
        return row.location_id?.trim() ? row.location_id : '—';
    }

    if (col === 'city') {
        return row.city?.trim() ? row.city : '—';
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
    <Head title="Business locations" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Business locations
                </h1>
                <p class="text-sm text-muted-foreground">
                    Stores and warehouses used on the POS and product
                    assignments.
                </p>
            </div>
            <Button type="button" @click="openCreateModal">
                Add location
            </Button>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Name, ID, city…"
            :per-page-options="[10, 15, 25, 50]"
            :paginator="businessLocations"
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

            <table class="w-full min-w-[560px] border-collapse text-sm">
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
                        v-for="row in businessLocations.data ?? []"
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
                                    @click="destroyLocation(row)"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!(businessLocations?.data?.length)">
                        <td
                            :colspan="visibleColumns.length + 1"
                            class="px-3 py-8 text-center text-muted-foreground"
                        >
                            <MapPin
                                class="mx-auto mb-2 size-8 opacity-40"
                                aria-hidden="true"
                            />
                            No locations yet. Add one to use it on products and
                            the POS.
                        </td>
                    </tr>
                </tbody>
            </table>
        </StandardDataTable>

        <StandardFormModal
            v-model:open="createModalOpen"
            title="Add business location"
            description="Contact details, default price group, and POS featured products."
            size="2xl"
            :dismiss-href="indexDismissHref"
        >
            <Form class="contents" @submit.prevent="submitCreate">
                <BusinessLocationForm
                    :key="createFormKey"
                    :form="createForm"
                    :selling-price-groups="sellingPriceGroups"
                    :initial-featured-products="[]"
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
            title="Edit business location"
            :description="editingBusinessLocation?.name"
            size="2xl"
            :dismiss-href="indexDismissHref"
        >
            <Form
                v-if="editingBusinessLocation"
                class="contents"
                @submit.prevent="submitEdit"
            >
                <BusinessLocationForm
                    :form="editForm"
                    :selling-price-groups="sellingPriceGroups"
                    :initial-featured-products="
                        editingBusinessLocation.featured_products ?? []
                    "
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
