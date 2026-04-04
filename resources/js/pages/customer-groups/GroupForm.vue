<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type CustomerGroupFormData = {
    name: string;
    price_calculation_type: string;
    amount: string;
    selling_price_group_id: string;
};

const props = withDefaults(
    defineProps<{
        form: InertiaForm<CustomerGroupFormData>;
        sellingPriceGroups?: { id: number; name: string }[];
    }>(),
    {
        sellingPriceGroups: () => [],
    },
);

const isPercentage = computed(
    () => props.form.price_calculation_type === 'percentage',
);
const isSellingPriceGroup = computed(
    () => props.form.price_calculation_type === 'selling_price_group',
);
</script>

<template>
    <div class="grid max-w-xl gap-6">
        <div class="grid gap-2">
            <Label for="cg-name">Customer group name *</Label>
            <Input
                id="cg-name"
                v-model="form.name"
                required
                placeholder="Customer group name"
            />
        </div>

        <div class="grid gap-2">
            <Label>Price calculation type</Label>
            <Select v-model="form.price_calculation_type">
                <SelectTrigger>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="percentage">Percentage</SelectItem>
                    <SelectItem value="selling_price_group"
                        >Selling price group</SelectItem
                    >
                </SelectContent>
            </Select>
            <p class="text-xs text-muted-foreground">
                Percentage adjusts selling price by the amount below. Selling
                price group uses a defined price list for the team.
            </p>
        </div>

        <div v-if="isPercentage" class="grid gap-2">
            <Label for="cg-amount">Calculation percentage (%)</Label>
            <Input
                id="cg-amount"
                v-model="form.amount"
                type="text"
                inputmode="decimal"
                placeholder="e.g. 10 or -5"
            />
            <p class="text-xs text-muted-foreground">
                Positive increases selling price; negative decreases it (relative
                to the product selling price).
            </p>
        </div>

        <div v-if="isSellingPriceGroup" class="grid gap-2">
            <Label>Selling price group</Label>
            <Select v-model="form.selling_price_group_id">
                <SelectTrigger>
                    <SelectValue placeholder="Select a group" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="g in sellingPriceGroups"
                        :key="g.id"
                        :value="String(g.id)"
                    >
                        {{ g.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <p
                v-if="!sellingPriceGroups.length"
                class="text-xs text-amber-600 dark:text-amber-500"
            >
                No selling price groups exist yet for this team. Create one in
                Products / pricing when available, or use percentage instead.
            </p>
        </div>
    </div>
</template>
