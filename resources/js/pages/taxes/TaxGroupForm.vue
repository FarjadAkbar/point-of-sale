<script setup lang="ts">
/* eslint-disable vue/no-mutating-props -- Inertia useForm is mutated by fields */
import type { InertiaForm } from '@inertiajs/vue3';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { taxRateOptionLabel } from '@/lib/taxPercent';

export type TaxGroupFormData = {
    name: string;
    tax_rate_ids: number[];
};

const props = defineProps<{
    form: InertiaForm<TaxGroupFormData>;
    taxRateOptions: { id: number; name: string; amount: string }[];
}>();

function optionLabel(opt: { name: string; amount: string }) {
    return taxRateOptionLabel(opt);
}

function isSelected(id: number) {
    return props.form.tax_rate_ids.includes(id);
}

function toggleTax(id: number, on: boolean) {
    const cur = props.form.tax_rate_ids;

    if (on && !cur.includes(id)) {
        props.form.tax_rate_ids = [...cur, id];
    }

    if (!on) {
        props.form.tax_rate_ids = cur.filter((x) => x !== id);
    }
}
</script>

<template>
    <div class="grid max-w-xl gap-6">
        <div class="grid gap-2">
            <Label for="tg-name">Name *</Label>
            <Input
                id="tg-name"
                v-model="form.name"
                required
                placeholder="Name"
            />
        </div>

        <div class="grid gap-2">
            <Label>Sub taxes *</Label>
            <p class="text-xs text-muted-foreground">
                Select one or more tax rates to combine in this group.
            </p>
            <div
                v-if="!taxRateOptions.length"
                class="rounded-md border border-dashed border-border px-3 py-4 text-sm text-muted-foreground"
            >
                Add at least one tax rate above before creating a group.
            </div>
            <ul
                v-else
                class="max-h-56 space-y-2 overflow-y-auto rounded-md border border-border p-3"
            >
                <li
                    v-for="opt in taxRateOptions"
                    :key="opt.id"
                    class="flex items-center gap-2 text-sm"
                >
                    <Checkbox
                        :id="`tg-rate-${opt.id}`"
                        :model-value="isSelected(opt.id)"
                        @update:model-value="
                            (v) => toggleTax(opt.id, v === true)
                        "
                    />
                    <Label
                        :for="`tg-rate-${opt.id}`"
                        class="cursor-pointer font-normal"
                    >
                        {{ optionLabel(opt) }}
                    </Label>
                </li>
            </ul>
        </div>
    </div>
</template>
