<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';
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

export type CategoryFormData = {
    name: string;
    code: string;
    description: string;
    is_sub_taxonomy: boolean;
    parent_id: string;
};

const props = withDefaults(
    defineProps<{
        form: InertiaForm<CategoryFormData>;
        parentCategories?: { id: number; name: string }[];
    }>(),
    {
        parentCategories: () => [],
    },
);

const showParent = computed(() => props.form.is_sub_taxonomy === true);
</script>

<template>
    <div class="grid max-w-xl gap-6">
        <div class="grid gap-2">
            <Label for="pc-name">Name *</Label>
            <Input
                id="pc-name"
                v-model="form.name"
                required
                placeholder="Category name"
            />
        </div>

        <div class="grid gap-2">
            <Label for="pc-code">Code</Label>
            <Input
                id="pc-code"
                v-model="form.code"
                placeholder="Optional internal code"
            />
        </div>

        <div class="grid gap-2">
            <Label for="pc-desc">Description</Label>
            <textarea
                id="pc-desc"
                v-model="form.description"
                rows="3"
                class="border-input bg-background min-h-[60px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                placeholder="Optional details"
            />
        </div>

        <div class="flex items-center gap-2">
            <Checkbox
                id="pc-sub"
                :model-value="form.is_sub_taxonomy"
                @update:model-value="
                    (v) => (form.is_sub_taxonomy = v === true)
                "
            />
            <Label for="pc-sub" class="font-normal"> Sub-taxonomy </Label>
        </div>

        <div v-if="showParent" class="grid gap-2">
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
                v-if="!parentCategories.length"
                class="text-xs text-amber-600 dark:text-amber-500"
            >
                Create a top-level category first (leave sub-taxonomy off), then
                add sub-categories under it.
            </p>
        </div>
    </div>
</template>
