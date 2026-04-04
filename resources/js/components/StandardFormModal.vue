<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

const open = defineModel<boolean>('open', { required: true });

const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        size?: 'md' | 'lg' | 'xl' | '2xl' | 'full';
        /** Navigated to when the dialog closes via overlay or the X control. */
        dismissHref: string;
    }>(),
    {
        size: 'lg',
    },
);

const sizeClass = computed(() => {
    const map = {
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-3xl',
        '2xl': 'sm:max-w-5xl',
        full: 'sm:max-w-[min(96vw,80rem)]',
    } as const;

    return map[props.size];
});

watch(open, (next, prev) => {
    if (next === false && prev === true) {
        router.visit(props.dismissHref, { preserveScroll: true });
    }
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent
            :class="
                cn(
                    'flex max-h-[92vh] w-full flex-col gap-0 overflow-hidden border border-border p-0 shadow-lg sm:max-w-[calc(100%-2rem)]',
                    sizeClass,
                )
            "
        >
            <div class="shrink-0 border-b border-border px-6 py-4">
                <DialogHeader>
                    <DialogTitle>{{ title }}</DialogTitle>
                    <DialogDescription v-if="description">
                        {{ description }}
                    </DialogDescription>
                </DialogHeader>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
                <slot />
            </div>
            <div
                v-if="$slots.footer"
                class="flex shrink-0 flex-col-reverse gap-2 border-t border-border px-6 py-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between"
            >
                <slot name="footer" />
            </div>
        </DialogContent>
    </Dialog>
</template>
