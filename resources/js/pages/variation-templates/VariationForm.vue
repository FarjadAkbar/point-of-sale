<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Minus, Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type VariationFormData = {
    name: string;
    values: string[];
};

const props = defineProps<{
    form: InertiaForm<VariationFormData>;
}>();

function addRow() {
    props.form.values.push('');
}

function removeRow(i: number) {
    if (props.form.values.length <= 1) {
        props.form.values[0] = '';

        return;
    }

    props.form.values.splice(i, 1);
}
</script>

<template>
    <div class="grid max-w-xl gap-6">
        <div class="grid gap-2">
            <Label for="vt-name">Name *</Label>
            <Input
                id="vt-name"
                v-model="form.name"
                required
                placeholder="e.g. Size (T-shirts)"
            />
        </div>

        <div class="grid gap-2">
            <div class="flex items-center justify-between">
                <Label>Variation values</Label>
                <Button type="button" variant="outline" size="sm" @click="addRow">
                    <Plus class="mr-1 size-4" />
                    Add value
                </Button>
            </div>
            <p class="text-xs text-muted-foreground">
                Add each option (e.g. S, M, L). Remove rows you do not need.
            </p>
            <div class="flex flex-col gap-2">
                <div
                    v-for="(_, i) in form.values"
                    :key="i"
                    class="flex items-center gap-2"
                >
                    <Input
                        v-model="form.values[i]"
                        :placeholder="`Value ${i + 1}`"
                        class="flex-1"
                    />
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        :disabled="form.values.length === 1 && !form.values[0]"
                        @click="removeRow(i)"
                    >
                        <Minus class="size-4" />
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
