<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type BookingFormData = {
    business_location_id: string;
    customer_id: string;
    correspondent_user_id: string;
    restaurant_table_id: string;
    service_staff_user_id: string;
    starts_at: string;
    ends_at: string;
    customer_note: string;
    send_notification: boolean;
    status: string;
};

const NONE = '__none__';

const props = withDefaults(
    defineProps<{
        form: InertiaForm<BookingFormData>;
        businessLocations: { id: number; name: string }[];
        customerOptions: { id: number; label: string }[];
        teamMembers: { id: number; name: string }[];
        restaurantTables: {
            id: number;
            name: string;
            business_location_id: number;
        }[];
        showStatus?: boolean;
    }>(),
    {
        showStatus: false,
    },
);

const tablesForLocation = computed(() => {
    const loc = props.form.business_location_id;

    if (!loc) {
        return [];
    }

    const id = Number(loc);

    return props.restaurantTables.filter((t) => t.business_location_id === id);
});

const statusOptions = [
    { value: 'waiting', label: 'Waiting' },
    { value: 'booked', label: 'Booked' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];
</script>

<template>
    <div class="grid max-w-2xl gap-6">
        <div class="grid gap-2">
            <Label>Business location *</Label>
            <Select v-model="form.business_location_id">
                <SelectTrigger>
                    <SelectValue placeholder="Business location" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="loc in businessLocations"
                        :key="loc.id"
                        :value="String(loc.id)"
                    >
                        {{ loc.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="grid gap-2">
            <Label>Customer *</Label>
            <Select v-model="form.customer_id">
                <SelectTrigger>
                    <SelectValue placeholder="Customer" />
                </SelectTrigger>
                <SelectContent class="max-h-60">
                    <SelectItem
                        v-for="c in customerOptions"
                        :key="c.id"
                        :value="String(c.id)"
                    >
                        {{ c.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="grid gap-2">
            <Label>Correspondent</Label>
            <Select v-model="form.correspondent_user_id">
                <SelectTrigger>
                    <SelectValue placeholder="Select correspondent" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="NONE">None</SelectItem>
                    <SelectItem
                        v-for="m in teamMembers"
                        :key="m.id"
                        :value="String(m.id)"
                    >
                        {{ m.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="grid gap-2">
            <Label>Table</Label>
            <Select v-model="form.restaurant_table_id">
                <SelectTrigger>
                    <SelectValue placeholder="Select table" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="NONE">None</SelectItem>
                    <SelectItem
                        v-for="t in tablesForLocation"
                        :key="t.id"
                        :value="String(t.id)"
                    >
                        {{ t.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="grid gap-2">
            <Label>Service staff</Label>
            <Select v-model="form.service_staff_user_id">
                <SelectTrigger>
                    <SelectValue placeholder="Select service staff" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="NONE">None</SelectItem>
                    <SelectItem
                        v-for="m in teamMembers"
                        :key="'s-' + m.id"
                        :value="String(m.id)"
                    >
                        {{ m.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="bk-start">Start time *</Label>
                <Input
                    id="bk-start"
                    v-model="form.starts_at"
                    type="datetime-local"
                    required
                />
            </div>
            <div class="grid gap-2">
                <Label for="bk-end">End time *</Label>
                <Input
                    id="bk-end"
                    v-model="form.ends_at"
                    type="datetime-local"
                    required
                />
            </div>
        </div>

        <div v-if="showStatus" class="grid gap-2">
            <Label>Status *</Label>
            <Select v-model="form.status">
                <SelectTrigger>
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="s in statusOptions"
                        :key="s.value"
                        :value="s.value"
                    >
                        {{ s.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="grid gap-2">
            <Label for="bk-note">Customer note</Label>
            <textarea
                id="bk-note"
                v-model="form.customer_note"
                rows="3"
                class="border-input bg-background min-h-[60px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                placeholder="Customer note"
            />
        </div>

        <div class="flex items-center gap-2">
            <Checkbox
                id="bk-notify"
                :model-value="form.send_notification"
                @update:model-value="
                    (v) => (form.send_notification = v === true)
                "
            />
            <Label for="bk-notify" class="font-normal">
                Send email/SMS notification to customer
            </Label>
        </div>
    </div>
</template>
