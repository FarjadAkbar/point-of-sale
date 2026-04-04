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

export type UnitFormData = {
    name: string;
    short_name: string;
    allow_decimal: boolean;
    is_multiple_of_base: boolean;
    base_unit_multiplier: string;
    base_unit_id: string;
};

const props = withDefaults(
    defineProps<{
        form: InertiaForm<UnitFormData>;
        baseUnits?: { id: number; name: string; short_name: string }[];
    }>(),
    {
        baseUnits: () => [],
    },
);

const showBase = computed(
    () => props.form.is_multiple_of_base === true,
);

function baseLabel(u: { name: string; short_name: string }) {
    return `${u.name} (${u.short_name})`;
}
</script>

<template>
    <div class="grid max-w-2xl gap-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="u-name">Name *</Label>
                <Input
                    id="u-name"
                    v-model="form.name"
                    required
                    placeholder="e.g. Pieces"
                />
            </div>
            <div class="grid gap-2">
                <Label for="u-short">Short name *</Label>
                <Input
                    id="u-short"
                    v-model="form.short_name"
                    required
                    placeholder="e.g. pc"
                />
            </div>
        </div>

        <div class="flex items-center gap-2">
            <Checkbox
                id="u-dec"
                :model-value="form.allow_decimal"
                @update:model-value="
                    (v) => (form.allow_decimal = v === true)
                "
            />
            <Label for="u-dec" class="font-normal">Allow decimal quantities</Label>
        </div>

        <div class="flex items-center gap-2">
            <Checkbox
                id="u-mult"
                :model-value="form.is_multiple_of_base"
                @update:model-value="
                    (v) => (form.is_multiple_of_base = v === true)
                "
            />
            <Label for="u-mult" class="font-normal">
                Add as multiple of another unit
            </Label>
        </div>

        <div
            v-if="showBase"
            class="rounded-md border border-border bg-muted/20 p-4"
        >
            <Label class="mb-3 block text-sm font-medium">
                Conversion to base unit
            </Label>
            <table class="w-full border-collapse text-sm">
                <tbody>
                    <tr>
                        <th
                            class="w-24 px-2 py-2 text-left align-middle font-medium"
                        >
                            1
                            <span id="unit_name">{{
                                form.name.trim() || 'Unit'
                            }}</span>
                        </th>
                        <th
                            class="w-8 px-1 py-2 text-center align-middle font-medium"
                        >
                            =
                        </th>
                        <td class="px-2 py-2 align-middle">
                            <Input
                                v-model="form.base_unit_multiplier"
                                type="text"
                                inputmode="decimal"
                                class="max-w-[140px]"
                                placeholder="times base unit"
                            />
                        </td>
                        <td class="min-w-[200px] px-2 py-2 align-middle">
                            <Select v-model="form.base_unit_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select base unit" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="u in baseUnits"
                                        :key="u.id"
                                        :value="String(u.id)"
                                    >
                                        {{ baseLabel(u) }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p
                v-if="!baseUnits.length"
                class="mt-2 text-xs text-amber-600 dark:text-amber-500"
            >
                Create a base unit first (leave “multiple of another unit”
                unchecked), then define derived units.
            </p>
        </div>
    </div>
</template>
