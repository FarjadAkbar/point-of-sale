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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import BookingForm from '@/pages/booking/BookingForm.vue';
import bookingRoutes from '@/routes/booking';
import type { Team } from '@/types';

const NONE = '__none__';

type Row = {
    id: number;
    starts_at: string | null;
    ends_at: string | null;
    status: string;
    customer?: { display_name: string; customer_code: string | null };
    business_location?: { name: string };
    restaurant_table?: { name: string } | null;
    service_staff?: { name: string } | null;
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

type EditingBooking = {
    id: number;
    business_location_id: number;
    customer_id: number;
    correspondent_user_id: number | null;
    restaurant_table_id: number | null;
    service_staff_user_id: number | null;
    starts_at: string | null;
    ends_at: string | null;
    customer_note: string | null;
    send_notification: boolean;
    status: string;
};

const props = defineProps<{
    bookings: Paginated;
    todaysBookings: Paginated;
    filters: {
        search: string;
        sort: string;
        direction: string;
        per_page: number;
        status: string;
    };
    businessLocations: { id: number; name: string }[];
    customerOptions: { id: number; label: string }[];
    teamMembers: { id: number; name: string }[];
    restaurantTables: {
        id: number;
        name: string;
        business_location_id: number;
    }[];
    editingBooking: EditingBooking | null;
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Booking',
                href: bookingRoutes.index.url(p.currentTeam!.slug),
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
const statusFilter = ref(
    props.filters.status && props.filters.status !== ''
        ? props.filters.status
        : 'all',
);
const todayPerPage = ref('25');
const todaySearch = ref('');

type ColId =
    | 'customer'
    | 'starts_at'
    | 'ends_at'
    | 'table'
    | 'location'
    | 'staff'
    | 'status';

const allColumns: { id: ColId; label: string; sortKey: string | null }[] = [
    { id: 'customer', label: 'Customer', sortKey: null },
    { id: 'starts_at', label: 'Booking starts', sortKey: 'starts_at' },
    { id: 'ends_at', label: 'Booking ends', sortKey: 'ends_at' },
    { id: 'table', label: 'Table', sortKey: null },
    { id: 'location', label: 'Location', sortKey: null },
    { id: 'staff', label: 'Service staff', sortKey: null },
    { id: 'status', label: 'Status', sortKey: 'status' },
];

const columnVisibility = useStorage<Record<string, boolean>>(
    'booking.datatable.columns',
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

    if (statusFilter.value !== 'all') {
        q.status = statusFilter.value;
    }

    for (const [k, v] of Object.entries(overrides)) {
        if (v !== undefined && v !== '') {
            q[k] = String(v);
        }
    }

    return q;
}

function visitWithFilters(overrides: Record<string, string | number> = {}) {
    router.get(
        bookingRoutes.index.url(teamSlug.value),
        indexQuery(overrides),
        { preserveState: true, replace: true },
    );
}

const debouncedSearch = useDebounceFn(() => visitWithFilters(), 350);
watch(search, () => debouncedSearch());
watch(perPage, (v) => visitWithFilters({ per_page: Number(v) }));
watch(statusFilter, () => visitWithFilters());

function toggleSort(sortKey: string) {
    const isCurrent = props.filters.sort === sortKey;
    const dir =
        isCurrent && props.filters.direction === 'asc' ? 'desc' : 'asc';
    router.get(
        bookingRoutes.index.url(teamSlug.value),
        indexQuery({ sort: sortKey, direction: dir }),
        { preserveState: true, replace: true },
    );
}

const createModalOpen = ref(false);
const editModalOpen = ref(false);

const indexDismissHref = computed(() => {
    const path = bookingRoutes.index.url(teamSlug.value);
    const qs = new URLSearchParams(indexQuery()).toString();

    return qs ? `${path}?${qs}` : path;
});

function toDatetimeLocalInput(iso: string | null | undefined): string {
    if (!iso) {
        return '';
    }

    const d = new Date(iso);

    if (Number.isNaN(d.getTime())) {
        return '';
    }

    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function optUserId(id: number | null | undefined): string {
    return id != null && id > 0 ? String(id) : NONE;
}

const createForm = useForm({
    business_location_id: '',
    customer_id: '',
    correspondent_user_id: NONE,
    restaurant_table_id: NONE,
    service_staff_user_id: NONE,
    starts_at: '',
    ends_at: '',
    customer_note: '',
    send_notification: true,
    status: 'booked',
});

const editForm = useForm({
    business_location_id: '',
    customer_id: '',
    correspondent_user_id: NONE,
    restaurant_table_id: NONE,
    service_staff_user_id: NONE,
    starts_at: '',
    ends_at: '',
    customer_note: '',
    send_notification: true,
    status: 'booked',
});

watch(
    () => props.editingBooking,
    (b) => {
        if (b) {
            editModalOpen.value = true;
            editForm.business_location_id = String(b.business_location_id);
            editForm.customer_id = String(b.customer_id);
            editForm.correspondent_user_id = optUserId(b.correspondent_user_id);
            editForm.restaurant_table_id =
                b.restaurant_table_id != null
                    ? String(b.restaurant_table_id)
                    : NONE;
            editForm.service_staff_user_id = optUserId(
                b.service_staff_user_id,
            );
            editForm.starts_at = toDatetimeLocalInput(b.starts_at);
            editForm.ends_at = toDatetimeLocalInput(b.ends_at);
            editForm.customer_note = String(b.customer_note ?? '');
            editForm.send_notification = Boolean(b.send_notification);
            editForm.status = String(b.status ?? 'booked');
        } else {
            editModalOpen.value = false;
        }
    },
    { immediate: true },
);

function openCreateModal() {
    createForm.reset();
    createForm.correspondent_user_id = NONE;
    createForm.restaurant_table_id = NONE;
    createForm.service_staff_user_id = NONE;
    createForm.send_notification = true;
    createForm.status = 'booked';
    createForm.clearErrors();
    createModalOpen.value = true;
}

function openEditModal(row: Row) {
    router.get(
        bookingRoutes.index.url(teamSlug.value),
        indexQuery({ edit: row.id }),
        {
            preserveState: true,
            replace: true,
            only: ['editingBooking'],
        },
    );
}

function transformPayload(
    data: {
        business_location_id: string;
        customer_id: string;
        correspondent_user_id: string;
        restaurant_table_id: string;
        service_staff_user_id: string;
        starts_at: string;
        ends_at: string;
        customer_note: string;
        send_notification: boolean;
        status?: string;
    },
    includeStatus: boolean,
) {
    const base = {
        business_location_id: Number(data.business_location_id),
        customer_id: Number(data.customer_id),
        correspondent_user_id:
            data.correspondent_user_id === NONE
                ? null
                : Number(data.correspondent_user_id),
        restaurant_table_id:
            data.restaurant_table_id === NONE
                ? null
                : Number(data.restaurant_table_id),
        service_staff_user_id:
            data.service_staff_user_id === NONE
                ? null
                : Number(data.service_staff_user_id),
        starts_at: data.starts_at,
        ends_at: data.ends_at,
        customer_note: data.customer_note || null,
        send_notification: data.send_notification === true,
    };

    if (includeStatus && data.status != null) {
        return { ...base, status: data.status };
    }

    return base;
}

function submitCreate() {
    createForm
        .transform((d) => transformPayload(d, false))
        .post(bookingRoutes.store.url(teamSlug.value), {
            onSuccess: () => {
                createModalOpen.value = false;
                createForm.reset();
                createForm.correspondent_user_id = NONE;
                createForm.restaurant_table_id = NONE;
                createForm.service_staff_user_id = NONE;
                createForm.send_notification = true;
            },
        });
}

function submitEdit() {
    const b = props.editingBooking;

    if (!b) {
        return;
    }

    editForm
        .transform((d) => transformPayload(d, true))
        .put(
            bookingRoutes.update.url({
                current_team: teamSlug.value,
                booking: b.id,
            }),
            { onSuccess: () => (editModalOpen.value = false) },
        );
}

function destroyBooking(row: Row) {
    if (!confirm('Delete this booking?')) {
        return;
    }

    router.delete(
        bookingRoutes.destroy.url({
            current_team: teamSlug.value,
            booking: row.id,
        }),
    );
}

function goToPage(url: string | null) {
    if (url) {
        router.visit(url, { preserveState: true, replace: true });
    }
}

function formatDt(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    const d = new Date(iso);

    return Number.isNaN(d.getTime()) ? '—' : d.toLocaleString();
}

function displayCell(row: Row, col: ColId): string {
    switch (col) {
        case 'customer':
            return row.customer?.display_name ?? '—';
        case 'starts_at':
            return formatDt(row.starts_at);
        case 'ends_at':
            return formatDt(row.ends_at);
        case 'table':
            return row.restaurant_table?.name ?? '—';
        case 'location':
            return row.business_location?.name ?? '—';
        case 'staff':
            return row.service_staff?.name ?? '—';
        case 'status':
            return row.status ?? '—';
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

function statusLabel(s: string): string {
    const map: Record<string, string> = {
        waiting: 'Waiting',
        booked: 'Booked',
        completed: 'Completed',
        cancelled: 'Cancelled',
    };

    return map[s] ?? s;
}
</script>

<template>
    <Head title="Booking" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Booking</h1>
                <p class="text-sm text-muted-foreground">
                    Today’s schedule and all reservations for your team.
                </p>
            </div>
            <Button type="button" @click="openCreateModal">
                Add new booking
            </Button>
        </div>

        <div class="space-y-2">
            <h2 class="text-lg font-medium">Today’s bookings</h2>
            <StandardDataTable
                v-model:search="todaySearch"
                v-model:per-page="todayPerPage"
                :show-pagination="false"
                :show-per-page="false"
                :show-search="false"
                :paginator="todaysBookings"
            >
                <table class="w-full min-w-[640px] border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-border">
                            <th
                                v-for="col in visibleColumns"
                                :key="'t-' + col.id"
                                class="bg-muted/40 px-3 py-2 text-left font-medium"
                            >
                                {{ col.label }}
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
                            v-for="row in todaysBookings?.data ?? []"
                            :key="'today-' + row.id"
                            class="border-b border-border/80 hover:bg-muted/20"
                        >
                            <td
                                v-for="col in visibleColumns"
                                :key="'td-' + row.id + col.id"
                                class="px-3 py-2 align-middle"
                            >
                                {{
                                    col.id === 'status'
                                        ? statusLabel(row.status)
                                        : displayCell(row, col.id)
                                }}
                            </td>
                            <td class="px-3 py-2 text-right print:hidden">
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
                            </td>
                        </tr>
                        <tr v-if="!(todaysBookings?.data?.length)">
                            <td
                                :colspan="visibleColumns.length + 1"
                                class="px-3 py-8 text-center text-muted-foreground"
                            >
                                No bookings for today.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </StandardDataTable>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            search-placeholder="Customer, location, table, note…"
            :paginator="bookings"
            @page="goToPage"
        >
            <template #filters>
                <div class="grid gap-2">
                    <Label class="text-xs">Status</Label>
                    <Select v-model="statusFilter">
                        <SelectTrigger class="h-9">
                            <SelectValue placeholder="All" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All</SelectItem>
                            <SelectItem value="waiting">Waiting</SelectItem>
                            <SelectItem value="booked">Booked</SelectItem>
                            <SelectItem value="completed">Completed</SelectItem>
                            <SelectItem value="cancelled">Cancelled</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </template>
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

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button type="button" variant="outline" size="sm">
                            Legend
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-48">
                        <DropdownMenuLabel>Status colors</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem disabled>Waiting</DropdownMenuItem>
                        <DropdownMenuItem disabled>Booked</DropdownMenuItem>
                        <DropdownMenuItem disabled>Completed</DropdownMenuItem>
                        <DropdownMenuItem disabled>Cancelled</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </template>

            <table class="w-full min-w-[900px] border-collapse text-sm">
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
                        v-for="row in bookings?.data ?? []"
                        :key="row.id"
                        class="border-b border-border/80 hover:bg-muted/20"
                    >
                        <td
                            v-for="col in visibleColumns"
                            :key="row.id + '-' + col.id"
                            class="px-3 py-2 align-middle"
                        >
                            {{
                                col.id === 'status'
                                    ? statusLabel(row.status)
                                    : displayCell(row, col.id)
                            }}
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
                                    @click="destroyBooking(row)"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!(bookings?.data?.length)">
                        <td
                            :colspan="visibleColumns.length + 1"
                            class="px-3 py-8 text-center text-muted-foreground"
                        >
                            No bookings match your filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </StandardDataTable>

        <StandardFormModal
            v-model:open="createModalOpen"
            title="Add new booking"
            description="Location, customer, optional table and staff, and time window."
            size="xl"
            :dismiss-href="indexDismissHref"
        >
            <Form class="contents" @submit.prevent="submitCreate">
                <BookingForm
                    :form="createForm"
                    :business-locations="businessLocations"
                    :customer-options="customerOptions"
                    :team-members="teamMembers"
                    :restaurant-tables="restaurantTables"
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
            title="Edit booking"
            :description="editingBooking ? `#${editingBooking.id}` : ''"
            size="xl"
            :dismiss-href="indexDismissHref"
        >
            <Form
                v-if="editingBooking"
                class="contents"
                @submit.prevent="submitEdit"
            >
                <BookingForm
                    :form="editForm"
                    :business-locations="businessLocations"
                    :customer-options="customerOptions"
                    :team-members="teamMembers"
                    :restaurant-tables="restaurantTables"
                    show-status
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

<style scoped>
@media print {
    .print\:hidden {
        display: none !important;
    }
}
</style>
