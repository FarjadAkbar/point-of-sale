<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { Minus, Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type ModifierRow = {
    name: string;
    price: string;
};

export type ModifierSetFormData = {
    name: string;
    items: ModifierRow[];
};

const props = defineProps<{
    form: InertiaForm<ModifierSetFormData>;
}>();

function addRow() {
    props.form.items.push({ name: '', price: '0' });
}

function removeRow(i: number) {
    if (props.form.items.length <= 1) {
        props.form.items[0] = { name: '', price: '0' };

        return;
    }

    props.form.items.splice(i, 1);
}
</script>

<template>
    <div class="grid max-w-2xl gap-6">
        <div class="grid gap-2">
            <Label for="ms-name">Modifier set *</Label>
            <Input
                id="ms-name"
                v-model="form.name"
                required
                placeholder="Name"
            />
        </div>

        <div class="grid gap-2">
            <div class="flex items-center justify-between">
                <Label>Modifiers</Label>
                <Button type="button" variant="outline" size="sm" @click="addRow">
                    <Plus class="mr-1 size-4" />
                    Add row
                </Button>
            </div>
            <p class="text-xs text-muted-foreground">
                Each row is one modifier option with its add-on price.
            </p>
            <div class="rounded-md border border-border">
                <div
                    class="grid grid-cols-[1fr_minmax(6rem,8rem)_auto] gap-2 border-b border-border bg-muted/40 px-3 py-2 text-xs font-medium text-muted-foreground"
                >
                    <span>Modifier</span>
                    <span>Price</span>
                    <span class="w-9" />
                </div>
                <div
                    v-for="(_, i) in form.items"
                    :key="i"
                    class="grid grid-cols-[1fr_minmax(6rem,8rem)_auto] items-center gap-2 border-b border-border/80 px-3 py-2 last:border-b-0"
                >
                    <Input
                        v-model="form.items[i].name"
                        placeholder="Name"
                        class="min-w-0"
                    />
                    <Input
                        v-model="form.items[i].price"
                        type="text"
                        inputmode="decimal"
                        placeholder="0"
                        class="min-w-0"
                    />
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        :disabled="
                            form.items.length === 1 &&
                            !form.items[0].name &&
                            form.items[0].price === '0'
                        "
                        @click="removeRow(i)"
                    >
                        <Minus class="size-4" />
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
