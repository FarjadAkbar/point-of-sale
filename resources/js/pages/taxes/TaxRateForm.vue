<script setup lang="ts">
/* eslint-disable vue/no-mutating-props -- Inertia useForm is mutated by fields */
import type { InertiaForm } from '@inertiajs/vue3';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type TaxRateFormData = {
    name: string;
    amount: string;
    for_tax_group: boolean;
};

defineProps<{
    form: InertiaForm<TaxRateFormData>;
}>();
</script>

<template>
    <div class="grid max-w-xl gap-6">
        <div class="grid gap-2">
            <Label for="tr-name">Name *</Label>
            <Input
                id="tr-name"
                v-model="form.name"
                required
                placeholder="Name"
            />
        </div>

        <div class="grid gap-2">
            <Label for="tr-amount">Tax rate (%) *</Label>
            <p class="text-xs text-muted-foreground">
                Zero percent is treated as tax exempt. Lists show a short form
                (e.g. 8 instead of 8.0000).
            </p>
            <Input
                id="tr-amount"
                v-model="form.amount"
                required
                inputmode="decimal"
                placeholder="e.g. 10"
            />
        </div>

        <div class="flex items-start gap-2">
            <Checkbox
                id="tr-group-only"
                :model-value="form.for_tax_group"
                class="mt-0.5"
                @update:model-value="
                    (v) => (form.for_tax_group = v === true)
                "
            />
            <div class="grid gap-1">
                <Label for="tr-group-only" class="font-normal leading-snug">
                    For tax group only
                </Label>
                <p class="text-xs text-muted-foreground">
                    When checked, this rate is hidden from individual tax
                    dropdowns and can only be used inside tax groups.
                </p>
            </div>
        </div>
    </div>
</template>
