<script setup lang="ts">
import { Form, Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import receiptPrinterRoutes from '@/routes/receipt-printer';
import type { Team } from '@/types';

const props = defineProps<{
    receiptPrinter: {
        connection_type: 'network' | 'windows' | 'linux';
    };
}>();

const page = usePage();
const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const form = useForm({
    connection_type: props.receiptPrinter.connection_type,
});

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Receipt printer',
                href: receiptPrinterRoutes.edit.url(p.currentTeam!.slug),
            },
        ],
    }),
});

function submit() {
    if (!teamSlug.value) {
        return;
    }

    form.patch(receiptPrinterRoutes.update.url(teamSlug.value));
}
</script>

<template>
    <Head title="Receipt printer" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            variant="small"
            title="Receipt printer"
            description="Choose how this team connects to a receipt printer (POS)."
        />

        <Form class="max-w-xl space-y-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="connection_type">Connection type *</Label>
                <Select
                    id="connection_type"
                    :model-value="form.connection_type"
                    @update:model-value="
                        (v) => {
                            if (v === 'network' || v === 'windows' || v === 'linux') {
                                form.connection_type = v;
                            }
                        }
                    "
                >
                    <SelectTrigger class="w-full" aria-label="Connection type">
                        <SelectValue placeholder="Select connection type" />
                    </SelectTrigger>
                    <SelectContent position="popper" align="start">
                        <SelectItem value="network">Network</SelectItem>
                        <SelectItem value="windows">Windows</SelectItem>
                        <SelectItem value="linux">Linux</SelectItem>
                    </SelectContent>
                </Select>
                <p
                    v-if="form.errors.connection_type"
                    class="text-destructive text-sm"
                >
                    {{ form.errors.connection_type }}
                </p>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    Save
                </Button>
            </div>
        </Form>
    </div>
</template>
