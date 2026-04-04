<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type SpgFormData = {
    name: string;
    description: string;
};

defineProps<{
    form: InertiaForm<SpgFormData>;
}>();
</script>

<template>
    <div class="grid max-w-xl gap-6">
        <div class="grid gap-2">
            <Label for="spg-name">Name *</Label>
            <Input id="spg-name" v-model="form.name" required placeholder="Group name" />
        </div>
        <div class="grid gap-2">
            <Label for="spg-desc">Description</Label>
            <textarea
                id="spg-desc"
                v-model="form.description"
                rows="3"
                class="border-input bg-background min-h-[60px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                placeholder="Optional"
            />
        </div>
    </div>
</template>
