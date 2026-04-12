<script setup lang="ts">
import { Form, Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import StandardFormModal from '@/components/StandardFormModal.vue';
import { Button } from '@/components/ui/button';
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
import { Spinner } from '@/components/ui/spinner';
import paymentAccountRoutes from '@/routes/payment-accounts';
import paymentSettingsRoutes from '@/routes/payment-settings';
import type { Team } from '@/types';

type AccountRow = {
    id: number;
    name: string;
    payment_method: string;
    bank_name: string | null;
    account_number: string | null;
    notes: string | null;
    is_active: boolean;
};

const props = defineProps<{
    paymentSettings: {
        cash_enabled: boolean;
        bank_transfer_enabled: boolean;
    };
    paymentAccounts: AccountRow[];
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Payment accounts & methods',
                href: paymentSettingsRoutes.edit.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

const settingsForm = useForm({
    cash_enabled: props.paymentSettings.cash_enabled,
    bank_transfer_enabled: props.paymentSettings.bank_transfer_enabled,
});

watch(
    () => props.paymentSettings,
    (s) => {
        settingsForm.cash_enabled = s.cash_enabled;
        settingsForm.bank_transfer_enabled = s.bank_transfer_enabled;
    },
    { deep: true },
);

function saveSettings() {
    settingsForm.patch(
        paymentSettingsRoutes.update.url(teamSlug.value),
        { preserveScroll: true },
    );
}

const createModalOpen = ref(false);
const editModalOpen = ref(false);
const editingAccount = ref<AccountRow | null>(null);

const accountForm = useForm({
    name: '',
    payment_method: 'cash',
    bank_name: '',
    account_number: '',
    notes: '',
    is_active: true,
});

const editForm = useForm({
    name: '',
    payment_method: 'cash',
    bank_name: '',
    account_number: '',
    notes: '',
    is_active: true,
});

const methodOptions = computed(() => {
    const o: { value: string; label: string }[] = [];

    if (props.paymentSettings.cash_enabled) {
        o.push({ value: 'cash', label: 'Cash' });
    }

    if (props.paymentSettings.bank_transfer_enabled) {
        o.push({ value: 'bank_transfer', label: 'Bank transfer' });
    }

    return o;
});

function openCreateAccount() {
    accountForm.reset();
    accountForm.clearErrors();
    accountForm.name = '';
    accountForm.payment_method = methodOptions.value[0]?.value ?? 'cash';
    accountForm.bank_name = '';
    accountForm.account_number = '';
    accountForm.notes = '';
    accountForm.is_active = true;
    createModalOpen.value = true;
}

function openEditAccount(row: AccountRow) {
    editingAccount.value = row;
    editForm.name = row.name;
    editForm.payment_method = row.payment_method;
    editForm.bank_name = row.bank_name ?? '';
    editForm.account_number = row.account_number ?? '';
    editForm.notes = row.notes ?? '';
    editForm.is_active = row.is_active;
    editForm.clearErrors();
    editModalOpen.value = true;
}

function submitCreateAccount() {
    accountForm
        .transform((d) => ({
            is_ledger: false,
            redirect_to: 'settings' as const,
            name: d.name,
            payment_method: d.payment_method,
            bank_name: d.bank_name?.trim() || null,
            account_number: d.account_number?.trim() || null,
            notes: d.notes?.trim() || null,
            is_active: d.is_active,
        }))
        .post(paymentAccountRoutes.store.url(teamSlug.value), {
            preserveScroll: true,
            onSuccess: () => {
                createModalOpen.value = false;
                accountForm.reset();
            },
        });
}

function submitEditAccount() {
    const row = editingAccount.value;

    if (!row) {
        return;
    }

    editForm
        .transform((d) => ({
            redirect_to: 'settings' as const,
            name: d.name,
            payment_method: d.payment_method,
            bank_name: d.bank_name?.trim() || null,
            account_number: d.account_number?.trim() || null,
            notes: d.notes?.trim() || null,
            is_active: d.is_active,
        }))
        .put(
            paymentAccountRoutes.update.url({
                current_team: teamSlug.value,
                payment_account: row.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    editModalOpen.value = false;
                    editingAccount.value = null;
                },
            },
        );
}

function destroyAccount(row: AccountRow) {
    if (!confirm(`Delete payment account “${row.name}”?`)) {
        return;
    }

    router.delete(
        paymentAccountRoutes.destroy.url(
            {
                current_team: teamSlug.value,
                payment_account: row.id,
            },
            { query: { redirect: 'settings' } },
        ),
        { preserveScroll: true },
    );
}

function methodLabel(m: string): string {
    return m === 'bank_transfer' ? 'Bank transfer' : 'Cash';
}
</script>

<template>
    <Head title="Payment accounts & methods" />

    <div class="flex flex-1 flex-col gap-8 p-4 md:p-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                Payment accounts & methods
            </h1>
            <p class="text-muted-foreground text-sm">
                Control which payment methods appear on purchases, and manage
                linked accounts (e.g. tills, bank accounts).
            </p>
        </div>

        <section class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6">
            <h2 class="mb-4 text-lg font-medium">Payment methods</h2>
            <p class="text-muted-foreground mb-4 text-sm">
                Disable a method to hide it on new purchases. At least one must
                stay on.
            </p>
            <Form class="space-y-4" @submit.prevent="saveSettings">
                <div class="flex flex-wrap items-center gap-6">
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="pm-cash"
                            :model-value="settingsForm.cash_enabled"
                            @update:model-value="
                                (v) => (settingsForm.cash_enabled = v === true)
                            "
                        />
                        <Label for="pm-cash" class="font-normal">Cash</Label>
                    </div>
                    <div class="flex items-center gap-2">
                        <Checkbox
                            id="pm-bank"
                            :model-value="settingsForm.bank_transfer_enabled"
                            @update:model-value="
                                (v) =>
                                    (settingsForm.bank_transfer_enabled =
                                        v === true)
                            "
                        />
                        <Label for="pm-bank" class="font-normal"
                            >Bank transfer</Label
                        >
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button
                        type="submit"
                        :disabled="settingsForm.processing"
                    >
                        <Spinner v-if="settingsForm.processing" />
                        Save methods
                    </Button>
                </div>
            </Form>
        </section>

        <section class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6">
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-lg font-medium">Payment accounts</h2>
                <Button
                    type="button"
                    size="sm"
                    :disabled="methodOptions.length === 0"
                    @click="openCreateAccount"
                >
                    <Plus class="mr-1 size-4" />
                    Add account
                </Button>
            </div>
            <p
                v-if="methodOptions.length === 0"
                class="text-muted-foreground text-sm"
            >
                Enable at least one payment method above before adding accounts.
            </p>
            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[520px] border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-border">
                            <th class="px-3 py-2 text-left font-medium">Name</th>
                            <th class="px-3 py-2 text-left font-medium">Method</th>
                            <th class="px-3 py-2 text-left font-medium">Details</th>
                            <th class="px-3 py-2 text-left font-medium">Active</th>
                            <th class="px-3 py-2 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in paymentAccounts"
                            :key="row.id"
                            class="border-b border-border/80"
                        >
                            <td class="px-3 py-2">{{ row.name }}</td>
                            <td class="px-3 py-2">
                                {{ methodLabel(row.payment_method) }}
                            </td>
                            <td class="text-muted-foreground px-3 py-2">
                                <template
                                    v-if="
                                        row.bank_name ||
                                        row.account_number ||
                                        row.notes
                                    "
                                >
                                    <span v-if="row.bank_name">{{
                                        row.bank_name
                                    }}</span>
                                    <span v-if="row.account_number">
                                        <span v-if="row.bank_name"> · </span
                                        >{{ row.account_number }}
                                    </span>
                                    <span
                                        v-if="row.notes"
                                        class="mt-0.5 block text-xs"
                                    >
                                        {{ row.notes }}
                                    </span>
                                </template>
                                <span v-else>—</span>
                            </td>
                            <td class="px-3 py-2">
                                {{ row.is_active ? 'Yes' : 'No' }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    title="Edit"
                                    @click="openEditAccount(row)"
                                >
                                    <Pencil />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    class="text-destructive"
                                    title="Delete"
                                    @click="destroyAccount(row)"
                                >
                                    <Trash2 />
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="!paymentAccounts.length">
                            <td
                                colspan="5"
                                class="text-muted-foreground px-3 py-6 text-center"
                            >
                                No accounts yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <StandardFormModal
            v-model:open="createModalOpen"
            title="Add payment account"
            description="Link a cash drawer or bank account to a payment method."
            size="lg"
            :visit-on-dismiss="false"
        >
            <Form class="grid gap-4" @submit.prevent="submitCreateAccount">
                <div class="grid gap-2">
                    <Label for="pa-name">Name *</Label>
                    <Input id="pa-name" v-model="accountForm.name" required />
                </div>
                <div class="grid gap-2">
                    <Label>Payment method *</Label>
                    <Select v-model="accountForm.payment_method">
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in methodOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="pa-bank">Bank name</Label>
                        <Input id="pa-bank" v-model="accountForm.bank_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="pa-acct">Account number</Label>
                        <Input
                            id="pa-acct"
                            v-model="accountForm.account_number"
                        />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="pa-notes">Notes</Label>
                    <textarea
                        id="pa-notes"
                        v-model="accountForm.notes"
                        rows="2"
                        class="border-input bg-background min-h-[60px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="pa-active"
                        :model-value="accountForm.is_active"
                        @update:model-value="
                            (v) => (accountForm.is_active = v === true)
                        "
                    />
                    <Label for="pa-active" class="font-normal">Active</Label>
                </div>
            </Form>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="createModalOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        :disabled="accountForm.processing"
                        @click="submitCreateAccount"
                    >
                        <Spinner v-if="accountForm.processing" />
                        Save
                    </Button>
                </div>
            </template>
        </StandardFormModal>

        <StandardFormModal
            v-model:open="editModalOpen"
            title="Edit payment account"
            size="lg"
            :visit-on-dismiss="false"
        >
            <Form
                v-if="editingAccount"
                class="grid gap-4"
                @submit.prevent="submitEditAccount"
            >
                <div class="grid gap-2">
                    <Label for="pe-name">Name *</Label>
                    <Input id="pe-name" v-model="editForm.name" required />
                </div>
                <div class="grid gap-2">
                    <Label>Payment method *</Label>
                    <Select v-model="editForm.payment_method">
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="opt in methodOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="pe-bank">Bank name</Label>
                        <Input id="pe-bank" v-model="editForm.bank_name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="pe-acct">Account number</Label>
                        <Input id="pe-acct" v-model="editForm.account_number" />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="pe-notes">Notes</Label>
                    <textarea
                        id="pe-notes"
                        v-model="editForm.notes"
                        rows="2"
                        class="border-input bg-background min-h-[60px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="pe-active"
                        :model-value="editForm.is_active"
                        @update:model-value="
                            (v) => (editForm.is_active = v === true)
                        "
                    />
                    <Label for="pe-active" class="font-normal">Active</Label>
                </div>
            </Form>
            <template #footer>
                <div class="flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="editModalOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        :disabled="editForm.processing"
                        @click="submitEditAccount"
                    >
                        <Spinner v-if="editForm.processing" />
                        Update
                    </Button>
                </div>
            </template>
        </StandardFormModal>
    </div>
</template>
