<script setup lang="ts">
import { Form, Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import barcodeSettingsRoutes from '@/routes/barcode-settings';
import type { Team } from '@/types';

export type BarcodeSettingsForm = {
    sticker_sheet_name: string;
    sticker_sheet_description: string;
    continuous_feed_or_rolls: boolean;
    additional_top_margin_inches: string;
    additional_left_margin_inches: string;
    width_of_sticker_inches: string;
    height_of_sticker_inches: string;
    paper_width_inches: string;
    paper_height_inches: string;
    stickers_in_one_row: string;
    distance_between_two_rows_inches: string;
    distance_between_two_columns_inches: string;
    stickers_per_sheet: string;
    set_as_default: boolean;
};

const props = defineProps<{
    barcodeSettings: BarcodeSettingsForm;
}>();

const page = usePage();
const teamSlug = computed(() => page.props.currentTeam?.slug ?? '');

const form = useForm({ ...props.barcodeSettings });

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Barcode settings',
                href: barcodeSettingsRoutes.edit.url(p.currentTeam!.slug),
            },
        ],
    }),
});

function submit() {
    if (!teamSlug.value) {
        return;
    }

    form.patch(barcodeSettingsRoutes.update.url(teamSlug.value));
}

function fieldError(name: keyof BarcodeSettingsForm): string | undefined {
    return form.errors[name as string];
}
</script>

<template>
    <Head title="Barcode settings" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <Heading
            variant="small"
            title="Barcode settings"
            description="Sticker sheet layout and margins for barcode label printing."
        />

        <Form class="max-w-2xl space-y-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="sticker_sheet_name">Sticker sheet setting name *</Label>
                <Input
                    id="sticker_sheet_name"
                    v-model="form.sticker_sheet_name"
                    required
                    autocomplete="off"
                />
                <p
                    v-if="fieldError('sticker_sheet_name')"
                    class="text-destructive text-sm"
                >
                    {{ fieldError('sticker_sheet_name') }}
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="sticker_sheet_description"
                    >Sticker sheet setting description</Label
                >
                <textarea
                    id="sticker_sheet_description"
                    v-model="form.sticker_sheet_description"
                    rows="3"
                    class="border-input bg-background min-h-[60px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                />
                <p
                    v-if="fieldError('sticker_sheet_description')"
                    class="text-destructive text-sm"
                >
                    {{ fieldError('sticker_sheet_description') }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Checkbox
                    id="continuous_feed_or_rolls"
                    :model-value="form.continuous_feed_or_rolls"
                    @update:model-value="
                        (v) => (form.continuous_feed_or_rolls = v === true)
                    "
                />
                <Label for="continuous_feed_or_rolls" class="font-normal">
                    Continuous feed or rolls
                </Label>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="additional_top_margin_inches"
                        >Additional top margin (in inches) *</Label
                    >
                    <Input
                        id="additional_top_margin_inches"
                        v-model="form.additional_top_margin_inches"
                        type="number"
                        step="any"
                        min="0"
                        required
                    />
                    <p
                        v-if="fieldError('additional_top_margin_inches')"
                        class="text-destructive text-sm"
                    >
                        {{ fieldError('additional_top_margin_inches') }}
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label for="additional_left_margin_inches"
                        >Additional left margin (in inches) *</Label
                    >
                    <Input
                        id="additional_left_margin_inches"
                        v-model="form.additional_left_margin_inches"
                        type="number"
                        step="any"
                        min="0"
                        required
                    />
                    <p
                        v-if="fieldError('additional_left_margin_inches')"
                        class="text-destructive text-sm"
                    >
                        {{ fieldError('additional_left_margin_inches') }}
                    </p>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="width_of_sticker_inches"
                        >Width of sticker (in inches) *</Label
                    >
                    <Input
                        id="width_of_sticker_inches"
                        v-model="form.width_of_sticker_inches"
                        type="number"
                        step="any"
                        min="0"
                        required
                    />
                    <p
                        v-if="fieldError('width_of_sticker_inches')"
                        class="text-destructive text-sm"
                    >
                        {{ fieldError('width_of_sticker_inches') }}
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label for="height_of_sticker_inches"
                        >Height of sticker (in inches) *</Label
                    >
                    <Input
                        id="height_of_sticker_inches"
                        v-model="form.height_of_sticker_inches"
                        type="number"
                        step="any"
                        min="0"
                        required
                    />
                    <p
                        v-if="fieldError('height_of_sticker_inches')"
                        class="text-destructive text-sm"
                    >
                        {{ fieldError('height_of_sticker_inches') }}
                    </p>
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="paper_width_inches">Paper width (in inches) *</Label>
                <Input
                    id="paper_width_inches"
                    v-model="form.paper_width_inches"
                    type="number"
                    step="any"
                    min="0"
                    required
                />
                <p
                    v-if="fieldError('paper_width_inches')"
                    class="text-destructive text-sm"
                >
                    {{ fieldError('paper_width_inches') }}
                </p>
            </div>

            <template v-if="form.continuous_feed_or_rolls">
                <div class="grid gap-2">
                    <Label for="paper_height_inches"
                        >Paper height (in inches) *</Label
                    >
                    <Input
                        id="paper_height_inches"
                        v-model="form.paper_height_inches"
                        type="number"
                        step="any"
                        min="0"
                        :required="form.continuous_feed_or_rolls"
                    />
                    <p
                        v-if="fieldError('paper_height_inches')"
                        class="text-destructive text-sm"
                    >
                        {{ fieldError('paper_height_inches') }}
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="stickers_in_one_row">Stickers in one row *</Label>
                    <Input
                        id="stickers_in_one_row"
                        v-model="form.stickers_in_one_row"
                        type="number"
                        step="1"
                        min="1"
                        :required="form.continuous_feed_or_rolls"
                    />
                    <p
                        v-if="fieldError('stickers_in_one_row')"
                        class="text-destructive text-sm"
                    >
                        {{ fieldError('stickers_in_one_row') }}
                    </p>
                </div>
            </template>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="distance_between_two_rows_inches"
                        >Distance between two rows (in inches) *</Label
                    >
                    <Input
                        id="distance_between_two_rows_inches"
                        v-model="form.distance_between_two_rows_inches"
                        type="number"
                        step="any"
                        min="0"
                        required
                    />
                    <p
                        v-if="fieldError('distance_between_two_rows_inches')"
                        class="text-destructive text-sm"
                    >
                        {{ fieldError('distance_between_two_rows_inches') }}
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label for="distance_between_two_columns_inches"
                        >Distance between two columns (in inches) *</Label
                    >
                    <Input
                        id="distance_between_two_columns_inches"
                        v-model="form.distance_between_two_columns_inches"
                        type="number"
                        step="any"
                        min="0"
                        required
                    />
                    <p
                        v-if="fieldError('distance_between_two_columns_inches')"
                        class="text-destructive text-sm"
                    >
                        {{ fieldError('distance_between_two_columns_inches') }}
                    </p>
                </div>
            </div>

            <div v-if="form.continuous_feed_or_rolls" class="grid gap-2">
                <Label for="stickers_per_sheet"
                    >No. of stickers per sheet *</Label
                >
                <Input
                    id="stickers_per_sheet"
                    v-model="form.stickers_per_sheet"
                    type="number"
                    step="1"
                    min="1"
                    :required="form.continuous_feed_or_rolls"
                />
                <p
                    v-if="fieldError('stickers_per_sheet')"
                    class="text-destructive text-sm"
                >
                    {{ fieldError('stickers_per_sheet') }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Checkbox
                    id="set_as_default"
                    :model-value="form.set_as_default"
                    @update:model-value="
                        (v) => (form.set_as_default = v === true)
                    "
                />
                <Label for="set_as_default" class="font-normal">
                    Set as default
                </Label>
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
