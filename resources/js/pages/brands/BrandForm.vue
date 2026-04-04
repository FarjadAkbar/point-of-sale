<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type BrandFormData = {
    name: string;
    description: string;
    user_for_repair: boolean;
};

defineProps<{
    form: InertiaForm<BrandFormData>;
}>();
</script>

<template>
    <div class="grid max-w-xl gap-6">
        <div class="grid gap-2">
            <Label for="b-name">Name *</Label>
            <Input
                id="b-name"
                v-model="form.name"
                required
                placeholder="Brand name"
            />
        </div>

        <div class="grid gap-2">
            <Label for="b-desc">Description</Label>
            <textarea
                id="b-desc"
                v-model="form.description"
                rows="3"
                class="border-input bg-background min-h-[60px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                placeholder="Optional details"
            />
        </div>

        <div class="flex items-center gap-2">
            <Checkbox
                id="b-repair"
                :model-value="form.user_for_repair"
                @update:model-value="
                    (v) => (form.user_for_repair = v === true)
                "
            />
            <Label for="b-repair" class="font-normal">
                User for repair
            </Label>
        </div>
    </div>
</template>
