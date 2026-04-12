<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Printer } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import StandardDataTable from '@/components/StandardDataTable.vue';
import type { LaravelPaginatorLink } from '@/components/StandardDataTable.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { reportRowMatchesSearch } from '@/lib/reportTableSearch';
import reportRoutes from '@/routes/reports';
import type { Team } from '@/types';

type ActivityRow = {
    id: number;
    date: string;
    subject_type: string;
    action: string;
    by: string;
    note: string;
};

const props = defineProps<{
    filters: {
        start_date: string;
        end_date: string;
        user_id: number | null;
        subject_type: string;
    };
    users: Array<{ id: number; name: string }>;
    subjectTypes: Array<{ value: string; label: string }>;
    rows: ActivityRow[];
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Reports',
                href: p.currentTeam ? reportRoutes.profitLoss.url(p.currentTeam.slug) : '#',
            },
            {
                title: 'Activity log',
                href: p.currentTeam ? reportRoutes.activityLog.url(p.currentTeam.slug) : '#',
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(() => (page.props.currentTeam as Team | null)?.slug ?? '');

const search = ref('');
const perPage = ref('25');
const currentPage = ref(1);

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const userId = ref<string>(props.filters.user_id != null ? String(props.filters.user_id) : '');
const subjectType = ref<string>(props.filters.subject_type ?? '');

watch(
    () => props.filters,
    (f) => {
        startDate.value = f.start_date;
        endDate.value = f.end_date;
        userId.value = f.user_id != null ? String(f.user_id) : '';
        subjectType.value = f.subject_type ?? '';
    },
    { deep: true },
);

const filteredRows = computed(() =>
    props.rows.filter((r) => reportRowMatchesSearch(r, search.value)),
);

const perPageNum = computed(() => {
    const n = Number(perPage.value);

    return Number.isFinite(n) && n > 0 ? n : 25;
});

const PAGE_PREFIX = '__al_page__:';

function clientPaginatorLinks(lastPage: number, page: number): LaravelPaginatorLink[] {
    const links: LaravelPaginatorLink[] = [
        {
            url: page > 1 ? `${PAGE_PREFIX}${page - 1}` : null,
            label: '&laquo; Previous',
            active: false,
        },
    ];

    for (let i = 1; i <= lastPage; i++) {
        links.push({
            url: i === page ? null : `${PAGE_PREFIX}${i}`,
            label: String(i),
            active: i === page,
        });
    }

    links.push({
        url: page < lastPage ? `${PAGE_PREFIX}${page + 1}` : null,
        label: 'Next &raquo;',
        active: false,
    });

    return links;
}

const clientPaginator = computed(() => {
    const total = filteredRows.value.length;
    const per = perPageNum.value;
    const lastPage = Math.max(1, Math.ceil(total / per) || 1);
    const pageNum = Math.min(Math.max(1, currentPage.value), lastPage);
    const from = total === 0 ? 0 : (pageNum - 1) * per + 1;
    const to = total === 0 ? 0 : Math.min(pageNum * per, total);

    return {
        from,
        to,
        total,
        current_page: pageNum,
        last_page: lastPage,
        per_page: per,
        links: clientPaginatorLinks(lastPage, pageNum),
    };
});

const pagedRows = computed(() => {
    const rows = filteredRows.value;
    const per = perPageNum.value;
    const page = Math.min(
        Math.max(1, currentPage.value),
        Math.max(1, Math.ceil(rows.length / per) || 1),
    );
    const start = (page - 1) * per;

    return rows.slice(start, start + per);
});

watch([filteredRows, perPage], () => {
    currentPage.value = 1;
});

function onClientPage(url: string | null) {
    if (!url?.startsWith(PAGE_PREFIX)) {
        return;
    }

    const n = Number(url.slice(PAGE_PREFIX.length));

    if (Number.isFinite(n) && n >= 1) {
        currentPage.value = n;
    }
}

function triggerPrint(): void {
    globalThis.print();
}

function formatDt(iso: string) {
    if (!iso) {
        return '—';
    }

    const d = new Date(iso);

    if (Number.isNaN(d.getTime())) {
        return '—';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(d);
}

function applyFilters() {
    const q: Record<string, string> = {
        start_date: startDate.value,
        end_date: endDate.value,
    };

    if (userId.value) {
        q.user_id = userId.value;
    }

    if (subjectType.value) {
        q.subject_type = subjectType.value;
    }

    router.get(reportRoutes.activityLog.url(teamSlug.value), q, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}
</script>

<template>
    <Head title="Activity log" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6 print:p-2">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Activity log</h1>
            <p class="text-muted-foreground text-sm">
                Sale-related events recorded for this team (created sale, status changes, notes). Other subject types
                appear in the filter list for parity with the legacy report; only sell activity rows are returned until
                those sources are implemented.
            </p>
        </div>

        <StandardDataTable
            v-model:search="search"
            v-model:per-page="perPage"
            table-wrapper-id="activity-log-table"
            table-wrapper-class="max-h-[75vh] overflow-x-auto overflow-y-auto"
            search-placeholder="Search table…"
            :per-page-options="[25, 50, 100, 200, 500]"
            :paginator="clientPaginator"
            :show-pagination="true"
            :show-per-page="true"
            @page="onClientPage"
        >
            <template #filters>
                <div class="space-y-2">
                    <Label for="al-user">By</Label>
                    <select
                        id="al-user"
                        v-model="userId"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option value="">All</option>
                        <option v-for="u in users" :key="u.id" :value="String(u.id)">
                            {{ u.name }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label for="al-subject">Subject type</Label>
                    <select
                        id="al-subject"
                        v-model="subjectType"
                        class="border-input bg-background ring-offset-background focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <option v-for="st in subjectTypes" :key="st.value || '__all__'" :value="st.value">
                            {{ st.label }}
                        </option>
                    </select>
                </div>
                <div class="space-y-2">
                    <Label>Date range</Label>
                    <div class="flex flex-wrap items-center gap-2">
                        <Input v-model="startDate" type="date" class="min-w-[10rem]" />
                        <span class="text-muted-foreground text-sm">to</span>
                        <Input v-model="endDate" type="date" class="min-w-[10rem]" />
                    </div>
                </div>
                <div class="pt-1">
                    <Button type="button" size="sm" class="w-full" @click="applyFilters">Apply filters</Button>
                </div>
            </template>
            <template #toolbar-actions>
                <Button variant="outline" type="button" size="sm" class="print:hidden" @click="triggerPrint">
                    <Printer class="mr-2 size-4" />
                    Print
                </Button>
            </template>

            <table class="min-w-[900px] w-full border-collapse text-xs sm:text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/40">
                        <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Date</th>
                        <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Subject type</th>
                        <th class="px-2 py-2 text-left font-medium whitespace-nowrap">Action</th>
                        <th class="px-2 py-2 text-left font-medium whitespace-nowrap">By</th>
                        <th class="px-2 py-2 text-left font-medium">Note</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="rows.length === 0">
                        <td colspan="5" class="text-muted-foreground px-3 py-6 text-center text-sm">
                            No activity found for these filters.
                        </td>
                    </tr>
                    <tr v-else-if="filteredRows.length === 0">
                        <td colspan="5" class="text-muted-foreground px-3 py-6 text-center text-sm">
                            No rows match your search.
                        </td>
                    </tr>
                    <tr
                        v-for="r in pagedRows"
                        :key="r.id"
                        class="border-b border-border/80 hover:bg-muted/20"
                    >
                        <td class="px-2 py-2 whitespace-nowrap">{{ formatDt(r.date) }}</td>
                        <td class="px-2 py-2 whitespace-nowrap">{{ r.subject_type }}</td>
                        <td class="px-2 py-2">{{ r.action }}</td>
                        <td class="px-2 py-2 whitespace-nowrap">{{ r.by }}</td>
                        <td class="px-2 py-2 max-w-md break-words">{{ r.note }}</td>
                    </tr>
                </tbody>
            </table>
        </StandardDataTable>
    </div>
</template>
