<script setup lang="ts">
import { ListFilter, Search } from 'lucide-vue-next';
import { computed, useSlots } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

/** Laravel `LengthAwarePaginator` / `Paginator` JSON from Inertia. */
export type LaravelPaginatorLink = {
    url: string | null;
    label: string;
    active?: boolean;
    page?: number | null;
};

export type LaravelPaginator<T = unknown> = {
    data?: T[];
    from?: number | null;
    to?: number | null;
    total?: number;
    current_page?: number;
    last_page?: number;
    per_page?: number;
    links?: LaravelPaginatorLink[];
};

export type DataTablePageLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const search = defineModel<string>('search', { default: '' });
const perPage = defineModel<string>('perPage', { required: true });

const props = withDefaults(
    defineProps<{
        searchPlaceholder?: string;
        perPageOptions?: number[];
        /**
         * Full Laravel paginator object (standard Inertia payload).
         * When set, `from` / `to` / `total` / `links` are read from here unless overridden below.
         */
        paginator?: LaravelPaginator | null;
        from?: number | null;
        to?: number | null;
        total?: number;
        links?: LaravelPaginatorLink[];
        tableWrapperId?: string;
        tableWrapperClass?: string;
        showPagination?: boolean;
        /** When false, hides the rows-per-page control (e.g. client-only report tables). */
        showPerPage?: boolean;
        /** When false, hides the search input (filters dropdown / toolbar still available). */
        showSearch?: boolean;
    }>(),
    {
        searchPlaceholder: 'Search…',
        perPageOptions: () => [10, 15, 25, 50, 100],
        paginator: null,
        from: null,
        to: null,
        total: 0,
        links: () => [],
        tableWrapperClass: 'overflow-x-auto',
        showPagination: true,
        showPerPage: true,
        showSearch: true,
    },
);

const emit = defineEmits<{
    page: [url: string | null];
}>();

const slots = useSlots();
const hasFiltersSlot = computed(() => !!slots.filters);

const displayFrom = computed(
    () => props.paginator?.from ?? props.from ?? null,
);
const displayTo = computed(() => props.paginator?.to ?? props.to ?? null);
const displayTotal = computed(
    () => props.paginator?.total ?? props.total ?? 0,
);

const displayLinks = computed<DataTablePageLink[]>(() => {
    const raw = props.paginator?.links ?? props.links ?? [];

    if (!Array.isArray(raw)) {
        return [];
    }

    return raw.map((l) => ({
        url: l.url ?? null,
        label: String(l.label ?? ''),
        active: Boolean(l.active),
    }));
});

function onPage(url: string | null) {
    emit('page', url);
}
</script>

<template>
    <div
        class="flex flex-col gap-3 rounded-xl border border-border bg-card p-4 shadow-sm print:border-0 print:shadow-none"
    >
        <div
            class="flex flex-col gap-3 print:hidden sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                <div v-if="showSearch" class="relative min-w-[12rem] max-w-xl flex-1">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        v-model="search"
                        type="search"
                        class="pl-9"
                        :placeholder="searchPlaceholder"
                        aria-label="Search"
                    />
                </div>
                <DropdownMenu v-if="hasFiltersSlot" :modal="false">
                    <DropdownMenuTrigger as-child>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="shrink-0 gap-1.5"
                        >
                            <ListFilter class="size-3.5" aria-hidden="true" />
                            Filters
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        align="start"
                        side="bottom"
                        :side-offset="6"
                        class="max-h-[min(70vh,28rem)] w-[min(100vw-2rem,22rem)] space-y-3 overflow-y-auto p-3"
                        @close-auto-focus.prevent
                    >
                        <p class="text-xs text-muted-foreground">
                            Optional refinements; they stack with the search box.
                        </p>
                        <div class="flex flex-col gap-2">
                            <slot name="filters" />
                        </div>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
            <div
                class="flex shrink-0 flex-wrap items-center justify-start gap-2 sm:justify-end"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <slot name="toolbar-actions" />
                </div>
                <div v-if="showPerPage" class="inline-flex">
                    <Select v-model="perPage">
                        <SelectTrigger
                            class="h-9 w-[4.25rem]"
                            aria-label="Rows per page"
                        >
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent
                            position="popper"
                            side="bottom"
                            align="end"
                            :side-offset="6"
                            class="min-w-[var(--reka-select-trigger-width)]"
                        >
                            <SelectItem
                                v-for="n in perPageOptions"
                                :key="n"
                                :value="String(n)"
                            >
                                {{ n }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
        </div>

        <div
            :id="tableWrapperId"
            :class="tableWrapperClass"
        >
            <slot />
        </div>

        <div
            v-if="showPagination"
            class="flex flex-col gap-2 border-t border-border pt-3 text-sm text-muted-foreground print:hidden sm:flex-row sm:items-center sm:justify-between"
        >
            <span>
                Showing
                {{ displayFrom ?? 0 }}–{{ displayTo ?? 0 }}
                of
                {{ displayTotal }}
            </span>
            <div class="flex flex-wrap items-center gap-1">
                <template v-for="(link, i) in displayLinks" :key="i">
                    <Button
                        v-if="link.url"
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="link.active"
                        @click="onPage(link.url)"
                    >
                        <span v-html="link.label" />
                    </Button>
                    <span
                        v-else
                        class="px-2 py-1 text-xs text-muted-foreground"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>
</template>
