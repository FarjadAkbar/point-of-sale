<script setup lang="ts">
import { Form, Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import StandardFormModal from '@/components/StandardFormModal.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import expenseCategoryRoutes from '@/routes/expense-categories';
import expenseRoutes from '@/routes/expenses';
import type { Team } from '@/types';

type Row = {
    id: number;
    name: string;
    code: string;
    parent: { id: number; name: string } | null;
    created_at: string | null;
};

type Paginated = {
    data: Row[];
    current_page: number;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type ParentOption = { id: number; name: string };

const props = defineProps<{
    categories: Paginated;
    parentCategories: ParentOption[];
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Expense categories',
                href: expenseCategoryRoutes.index.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

const modalOpen = ref(false);

const form = useForm({
    name: '',
    code: '',
    is_subcategory: false,
    parent_id: '',
});

watch(modalOpen, (open) => {
    if (open) {
        form.reset();
        form.clearErrors();
        form.is_subcategory = false;
        form.parent_id = '';
    }
});

function submitCategory() {
    form.transform((d) => ({
        name: d.name.trim(),
        code: d.code.trim(),
        is_subcategory: d.is_subcategory,
        parent_id:
            d.is_subcategory && d.parent_id ? Number(d.parent_id) : null,
    })).post(expenseCategoryRoutes.store.url(teamSlug.value), {
        preserveScroll: true,
        onSuccess: () => {
            modalOpen.value = false;
        },
    });
}

function goToPage(url: string | null) {
    if (url) {
        router.visit(url, { preserveState: true, replace: true });
    }
}
</script>

<template>
    <Head title="Expense categories" />

    <div class="flex flex-1 flex-col gap-4 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Expense categories
                </h1>
                <p class="text-muted-foreground text-sm">
                    Organize expenses with codes and optional subcategories.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="expenseRoutes.index.url(teamSlug)">
                        List expenses
                    </Link>
                </Button>
                <Button type="button" @click="modalOpen = true">
                    Add category
                </Button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="w-full min-w-[640px] border-collapse text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/40">
                        <th class="px-3 py-2 text-left font-medium">Name</th>
                        <th class="px-3 py-2 text-left font-medium">Code</th>
                        <th class="px-3 py-2 text-left font-medium">Parent</th>
                        <th class="px-3 py-2 text-left font-medium">Created</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="row in categories.data ?? []"
                        :key="row.id"
                        class="border-b border-border/80 hover:bg-muted/20"
                    >
                        <td class="px-3 py-2">{{ row.name }}</td>
                        <td class="px-3 py-2 font-mono text-xs">{{ row.code }}</td>
                        <td class="px-3 py-2">
                            {{ row.parent?.name ?? '—' }}
                        </td>
                        <td class="text-muted-foreground px-3 py-2 whitespace-nowrap">
                            {{
                                row.created_at
                                    ? new Date(row.created_at).toLocaleString()
                                    : '—'
                            }}
                        </td>
                    </tr>
                    <tr v-if="!(categories?.data?.length)">
                        <td
                            colspan="4"
                            class="text-muted-foreground px-3 py-8 text-center"
                        >
                            No expense categories yet. Add one to get started.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div
            v-if="categories.last_page > 1"
            class="flex flex-wrap justify-center gap-1"
        >
            <Button
                v-for="(link, i) in categories.links"
                :key="i"
                type="button"
                variant="outline"
                size="sm"
                :disabled="!link.url"
                :class="link.active ? 'border-primary' : ''"
                @click="goToPage(link.url)"
            >
                <span v-html="link.label" />
            </Button>
        </div>

        <StandardFormModal
            v-model:open="modalOpen"
            title="Add expense category"
            description="Name and code are required. Subcategories belong to a parent."
            size="md"
            :visit-on-dismiss="false"
        >
            <Form class="space-y-4" @submit.prevent="submitCategory">
                <div class="grid gap-2">
                    <Label for="ec-name">Category name *</Label>
                    <Input
                        id="ec-name"
                        v-model="form.name"
                        required
                        autocomplete="off"
                    />
                    <p v-if="form.errors.name" class="text-destructive text-sm">
                        {{ form.errors.name }}
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label for="ec-code">Category code *</Label>
                    <Input
                        id="ec-code"
                        v-model="form.code"
                        required
                        autocomplete="off"
                    />
                    <p v-if="form.errors.code" class="text-destructive text-sm">
                        {{ form.errors.code }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="ec-sub"
                        :model-value="form.is_subcategory"
                        @update:model-value="
                            (v) => {
                                form.is_subcategory = v === true;
                                if (!form.is_subcategory) {
                                    form.parent_id = '';
                                }
                            }
                        "
                    />
                    <Label for="ec-sub" class="cursor-pointer font-normal">
                        Add as subcategory
                    </Label>
                </div>
                <div v-if="form.is_subcategory" class="grid gap-2">
                    <Label>Parent category *</Label>
                    <Select v-model="form.parent_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Select parent" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="p in parentCategories"
                                :key="p.id"
                                :value="String(p.id)"
                            >
                                {{ p.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="form.errors.parent_id"
                        class="text-destructive text-sm"
                    >
                        {{ form.errors.parent_id }}
                    </p>
                </div>
            </Form>
            <template #footer>
                <div class="flex w-full flex-wrap justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="modalOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        :disabled="form.processing"
                        @click="submitCategory"
                    >
                        <Spinner v-if="form.processing" class="mr-2 size-4" />
                        Save
                    </Button>
                </div>
            </template>
        </StandardFormModal>
    </div>
</template>
