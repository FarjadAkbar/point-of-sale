<script setup lang="ts">
/* eslint-disable vue/no-mutating-props -- Inertia useForm is mutated by fields */
import type { InertiaForm } from '@inertiajs/vue3';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type SalesCommissionAgentFormData = {
    prefix: string;
    first_name: string;
    last_name: string;
    email: string;
    contact_no: string;
    address: string;
    cmmsn_percent: string;
};

defineProps<{
    form: InertiaForm<SalesCommissionAgentFormData>;
}>();
</script>

<template>
    <div class="grid max-w-3xl gap-6">
        <div class="grid gap-4 sm:grid-cols-12">
            <div class="grid gap-2 sm:col-span-2">
                <Label for="sca-prefix">Prefix</Label>
                <Input
                    id="sca-prefix"
                    v-model="form.prefix"
                    placeholder="Mr / Mrs / Miss"
                />
            </div>
            <div class="grid gap-2 sm:col-span-5">
                <Label for="sca-first">First name *</Label>
                <Input
                    id="sca-first"
                    v-model="form.first_name"
                    required
                    placeholder="First name"
                />
            </div>
            <div class="grid gap-2 sm:col-span-5">
                <Label for="sca-last">Last name</Label>
                <Input
                    id="sca-last"
                    v-model="form.last_name"
                    placeholder="Last name"
                />
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="grid gap-2">
                <Label for="sca-email">Email</Label>
                <Input
                    id="sca-email"
                    v-model="form.email"
                    type="email"
                    placeholder="Email"
                />
            </div>
            <div class="grid gap-2">
                <Label for="sca-contact">Contact number</Label>
                <Input
                    id="sca-contact"
                    v-model="form.contact_no"
                    placeholder="Contact number"
                />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="sca-address">Address</Label>
            <textarea
                id="sca-address"
                v-model="form.address"
                rows="3"
                class="border-input bg-background min-h-[60px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                placeholder="Address"
            />
        </div>

        <div class="grid max-w-xs gap-2">
            <Label for="sca-pct">Sales commission percentage (%) *</Label>
            <Input
                id="sca-pct"
                v-model="form.cmmsn_percent"
                required
                inputmode="decimal"
                placeholder="e.g. 5"
            />
        </div>
    </div>
</template>
