<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type WarrantyFormData = {
    name: string;
    description: string;
    duration_value: string;
    duration_unit: string;
};

defineProps<{
    form: InertiaForm<WarrantyFormData>;
}>();
</script>

<template>
    <div class="grid max-w-xl gap-6">
        <div class="grid gap-2">
            <Label for="w-name">Name *</Label>
            <Input
                id="w-name"
                v-model="form.name"
                required
                placeholder="Warranty name"
            />
        </div>

        <div class="grid gap-2">
            <Label for="w-desc">Description</Label>
            <textarea
                id="w-desc"
                v-model="form.description"
                rows="3"
                class="border-input bg-background min-h-[60px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                placeholder="Optional details"
            />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="w-dur-val">Duration *</Label>
                <Input
                    id="w-dur-val"
                    v-model="form.duration_value"
                    type="number"
                    min="1"
                    required
                    placeholder="e.g. 12"
                />
            </div>
            <div class="grid gap-2">
                <Label>Unit *</Label>
                <Select v-model="form.duration_unit">
                    <SelectTrigger>
                        <SelectValue placeholder="Select unit" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="day">Day(s)</SelectItem>
                        <SelectItem value="week">Week(s)</SelectItem>
                        <SelectItem value="month">Month(s)</SelectItem>
                        <SelectItem value="year">Year(s)</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>
    </div>
</template>
