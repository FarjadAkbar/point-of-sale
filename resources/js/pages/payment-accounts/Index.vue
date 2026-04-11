<script setup lang="ts">
import {
    Form,
    Head,
    Link,
    router,
    useForm,
    usePage,
} from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import StandardFormModal from '@/components/StandardFormModal.vue';
import { Button } from '@/components/ui/button';
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
import accountTypeRoutes from '@/routes/account-types';
import paymentAccountRoutes from '@/routes/payment-accounts';
import paymentSettingsRoutes from '@/routes/payment-settings';
import type { Team } from '@/types';

type AccountRow = {
    id: number;
    name: string;
    payment_method: string;
    account_type: string;
    account_sub_type: string;
    account_number: string | null;
    notes: string | null;
    opening_balance: string;
    account_details_preview: string;
    is_active: boolean;
    created_by: string | null | undefined;
    account_type_id: number | null;
    account_details: { label: string; value: string }[];
};

type TypeRow = {
    id: number;
    name: string;
    parent: { id: number; name: string } | null;
};

type Paginated = {
    data: AccountRow[];
    current_page: number;
    last_page: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    accounts: Paginated;
    accountTypes: TypeRow[];
    parentTypeOptions: { id: number; name: string }[];
    ledgerTypeOptions: { id: number; name: string; parent_id: number | null }[];
    filters: { status: string };
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Payment accounts',
                href: paymentAccountRoutes.index.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

const activeTab = ref<'accounts' | 'types'>('accounts');
const statusFilter = ref(props.filters.status ?? 'active');

watch(
    () => props.filters.status,
    (s) => {
        statusFilter.value = s ?? 'active';
    },
);

function applyStatus() {
    const current = props.filters.status ?? 'active';
    if (statusFilter.value === current) {
        return;
    }

    router.get(
        paymentAccountRoutes.index.url(teamSlug.value, {
            query: { status: statusFilter.value },
        }),
        {},
        { preserveState: true, replace: true },
    );
}

const NONE = '__none__';

const addAccountOpen = ref(false);
const editAccountOpen = ref(false);
const editingAccount = ref<AccountRow | null>(null);

const detailTemplate = () =>
    Array.from({ length: 6 }, () => ({ label: '', value: '' }));

const accountForm = useForm({
    is_ledger: true,
    redirect_to: 'list' as const,
    name: '',
    account_number: '',
    account_type_id: NONE,
    opening_balance: '0',
    account_details: detailTemplate(),
    notes: '',
});

const editForm = useForm({
    redirect_to: 'list' as const,
    name: '',
    account_number: '',
    account_type_id: NONE,
    opening_balance: '0',
    account_details: detailTemplate(),
    notes: '',
});

const typeForm = useForm({
    name: '',
    parent_account_type_id: NONE,
});

const addTypeOpen = ref(false);

function openAddAccount() {
    accountForm.reset();
    accountForm.clearErrors();
    accountForm.is_ledger = true;
    accountForm.redirect_to = 'list';
    accountForm.account_type_id = NONE;
    accountForm.opening_balance = '0';
    accountForm.account_details = detailTemplate();
    addAccountOpen.value = true;
}

function openEditAccount(row: AccountRow) {
    editingAccount.value = row;
    editForm.reset();
    editForm.clearErrors();
    editForm.redirect_to = 'list';
    editForm.name = row.name;
    editForm.account_number = row.account_number ?? '';
    editForm.account_type_id = row.account_type_id
        ? String(row.account_type_id)
        : NONE;
    editForm.opening_balance = row.opening_balance ?? '0';
    const d = [...(row.account_details ?? [])];
    while (d.length < 6) {
        d.push({ label: '', value: '' });
    }
    editForm.account_details = d.slice(0, 6);
    editForm.notes = row.notes ?? '';
    editAccountOpen.value = true;
}

function submitAccount() {
    accountForm
        .transform((d) => ({
            is_ledger: true,
            redirect_to: 'list',
            name: d.name.trim(),
            account_number: d.account_number.trim(),
            account_type_id:
                d.account_type_id === NONE ? null : Number(d.account_type_id),
            opening_balance: Number(d.opening_balance) || 0,
            account_details: d.account_details,
            notes: d.notes?.trim() || null,
        }))
        .post(paymentAccountRoutes.store.url(teamSlug.value), {
            preserveScroll: true,
            onSuccess: () => {
                addAccountOpen.value = false;
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
            redirect_to: 'list',
            name: d.name.trim(),
            account_number: d.account_number.trim(),
            account_type_id:
                d.account_type_id === NONE ? null : Number(d.account_type_id),
            opening_balance: Number(d.opening_balance) || 0,
            account_details: d.account_details,
            notes: d.notes?.trim() || null,
        }))
        .put(
            paymentAccountRoutes.update.url({
                current_team: teamSlug.value,
                payment_account: row.id,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    editAccountOpen.value = false;
                    editingAccount.value = null;
                },
            },
        );
}

function destroyAccount(row: AccountRow) {
    if (row.payment_method !== 'ledger') {
        return;
    }
    if (!confirm(`Delete account “${row.name}”?`)) {
        return;
    }

    router.delete(
        paymentAccountRoutes.destroy.url(
            {
                current_team: teamSlug.value,
                payment_account: row.id,
            },
            { query: { redirect: 'list' } },
        ),
        { preserveScroll: true },
    );
}

function openAddType() {
    typeForm.reset();
    typeForm.clearErrors();
    typeForm.parent_account_type_id = NONE;
    addTypeOpen.value = true;
}

function submitType() {
    typeForm
        .transform((d) => ({
            name: d.name.trim(),
            parent_account_type_id:
                d.parent_account_type_id === NONE
                    ? null
                    : Number(d.parent_account_type_id),
        }))
        .post(accountTypeRoutes.store.url(teamSlug.value), {
            preserveScroll: true,
            onSuccess: () => {
                addTypeOpen.value = false;
            },
        });
}

function goToPage(url: string | null) {
    if (url) {
        router.visit(url, { preserveState: true, replace: true });
    }
}

function methodLabel(m: string): string {
    if (m === 'ledger') {
        return 'Ledger';
    }
    if (m === 'bank_transfer') {
        return 'Bank transfer';
    }

    return 'Cash';
}

const pageBalanceTotal = computed(() =>
    (props.accounts?.data ?? []).reduce(
        (s, r) => s + (Number(r.opening_balance) || 0),
        0,
    ),
);

function isLedger(row: AccountRow): boolean {
    return row.payment_method === 'ledger';
}
</script>

<template>
    <Head title="Payment accounts" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Payment accounts
                </h1>
                <p class="text-muted-foreground text-sm">
                    Ledger accounts, types, and links to payment methods.
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="paymentSettingsRoutes.edit.url(teamSlug)">
                    Payment methods &amp; POS accounts
                </Link>
            </Button>
        </div>

        <div class="border-b border-border">
            <div class="flex gap-1">
                <button
                    type="button"
                    class="border-b-2 px-4 py-2 text-sm font-medium transition-colors"
                    :class="
                        activeTab === 'accounts'
                            ? 'border-primary text-foreground'
                            : 'border-transparent text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'accounts'"
                >
                    Accounts
                </button>
                <button
                    type="button"
                    class="border-b-2 px-4 py-2 text-sm font-medium transition-colors"
                    :class="
                        activeTab === 'types'
                            ? 'border-primary text-foreground'
                            : 'border-transparent text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'types'"
                >
                    Account types
                </button>
            </div>
        </div>

        <div v-show="activeTab === 'accounts'" class="space-y-4">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="grid max-w-xs gap-2">
                    <Label>Status</Label>
                    <div class="flex gap-2">
                        <Select
                            v-model="statusFilter"
                            @update:model-value="applyStatus"
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="closed">Closed</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                <Button type="button" @click="openAddAccount">
                    <Plus class="mr-1 size-4" />
                    Add account
                </Button>
            </div>

            <div class="overflow-x-auto rounded-lg border border-border">
                <table class="w-full min-w-[960px] border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/40">
                            <th class="px-3 py-2 text-left font-medium">Name</th>
                            <th class="px-3 py-2 text-left font-medium">Method</th>
                            <th class="px-3 py-2 text-left font-medium">Account type</th>
                            <th class="px-3 py-2 text-left font-medium">Sub type</th>
                            <th class="px-3 py-2 text-left font-medium">Account no.</th>
                            <th class="px-3 py-2 text-left font-medium">Note</th>
                            <th class="px-3 py-2 text-right font-medium">Balance</th>
                            <th class="px-3 py-2 text-left font-medium">Details</th>
                            <th class="px-3 py-2 text-left font-medium">Added by</th>
                            <th class="px-3 py-2 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in accounts.data ?? []"
                            :key="row.id"
                            class="border-b border-border/80 hover:bg-muted/20"
                        >
                            <td class="px-3 py-2">{{ row.name }}</td>
                            <td class="px-3 py-2">{{ methodLabel(row.payment_method) }}</td>
                            <td class="px-3 py-2">{{ row.account_type }}</td>
                            <td class="px-3 py-2">{{ row.account_sub_type }}</td>
                            <td class="px-3 py-2 font-mono text-xs">
                                {{ row.account_number ?? '—' }}
                            </td>
                            <td class="text-muted-foreground max-w-[140px] truncate px-3 py-2">
                                {{ row.notes ?? '—' }}
                            </td>
                            <td class="px-3 py-2 text-right">{{ row.opening_balance }}</td>
                            <td class="text-muted-foreground max-w-[160px] truncate px-3 py-2 text-xs">
                                {{ row.account_details_preview }}
                            </td>
                            <td class="px-3 py-2">{{ row.created_by ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">
                                <div
                                    v-if="isLedger(row)"
                                    class="flex justify-end gap-1"
                                >
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="size-8"
                                        title="Edit"
                                        @click="openEditAccount(row)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive size-8"
                                        title="Delete"
                                        @click="destroyAccount(row)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                        </tr>
                        <tr v-if="!(accounts?.data?.length)">
                            <td
                                colspan="10"
                                class="text-muted-foreground px-3 py-8 text-center"
                            >
                                No accounts in this view.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="(accounts?.data?.length ?? 0) > 0">
                        <tr class="bg-muted/30 font-medium">
                            <td colspan="6" class="px-3 py-2 text-right">Page total (opening)</td>
                            <td class="px-3 py-2 text-right">
                                {{ pageBalanceTotal.toFixed(2) }}
                            </td>
                            <td colspan="3" />
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div
                v-if="accounts.last_page > 1"
                class="flex flex-wrap justify-center gap-1"
            >
                <Button
                    v-for="(link, i) in accounts.links"
                    :key="i"
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="!link.url"
                    :class="link.active ? 'border-primary' : ''"
                    @click="goToPage(link.url)"
                >
                    <span v-html="link.label" />
                </Button>
            </div>
        </div>

        <div v-show="activeTab === 'types'" class="space-y-4">
            <div class="flex justify-end">
                <Button type="button" @click="openAddType">
                    <Plus class="mr-1 size-4" />
                    Add account type
                </Button>
            </div>
            <div class="overflow-x-auto rounded-lg border border-border">
                <table class="w-full min-w-[400px] border-collapse text-sm">
                    <thead>
                        <tr class="border-b border-border bg-muted/40">
                            <th class="px-3 py-2 text-left font-medium">Name</th>
                            <th class="px-3 py-2 text-left font-medium">Parent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="t in accountTypes"
                            :key="t.id"
                            class="border-b border-border/80"
                        >
                            <td class="px-3 py-2">{{ t.name }}</td>
                            <td class="px-3 py-2">{{ t.parent?.name ?? '—' }}</td>
                        </tr>
                        <tr v-if="!accountTypes.length">
                            <td
                                colspan="2"
                                class="text-muted-foreground px-3 py-8 text-center"
                            >
                                No account types yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <StandardFormModal
            v-model:open="addAccountOpen"
            title="Add account"
            description="Ledger-style account (not used as a POS payment method)."
            size="lg"
            :visit-on-dismiss="false"
        >
            <Form class="space-y-4" @submit.prevent="submitAccount">
                <div class="grid gap-2">
                    <Label for="pa-name">Name *</Label>
                    <Input id="pa-name" v-model="accountForm.name" required />
                    <p v-if="accountForm.errors.name" class="text-destructive text-sm">
                        {{ accountForm.errors.name }}
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label for="pa-num">Account number *</Label>
                    <Input id="pa-num" v-model="accountForm.account_number" required />
                    <p
                        v-if="accountForm.errors.account_number"
                        class="text-destructive text-sm"
                    >
                        {{ accountForm.errors.account_number }}
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label>Account type</Label>
                    <Select v-model="accountForm.account_type_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Optional" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="NONE">None</SelectItem>
                            <SelectItem
                                v-for="t in ledgerTypeOptions"
                                :key="t.id"
                                :value="String(t.id)"
                            >
                                {{ t.parent_id ? `— ${t.name}` : t.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label for="pa-ob">Opening balance</Label>
                    <Input
                        id="pa-ob"
                        v-model="accountForm.opening_balance"
                        type="text"
                        inputmode="decimal"
                    />
                </div>
                <div>
                    <Label class="mb-2 block">Account details</Label>
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="py-1 text-left font-medium">Label</th>
                                <th class="py-1 text-left font-medium">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(pair, i) in accountForm.account_details"
                                :key="i"
                                class="border-b border-border/60"
                            >
                                <td class="py-1 pr-2">
                                    <Input v-model="pair.label" />
                                </td>
                                <td class="py-1">
                                    <Input v-model="pair.value" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="grid gap-2">
                    <Label for="pa-note">Note</Label>
                    <textarea
                        id="pa-note"
                        v-model="accountForm.notes"
                        rows="3"
                        class="border-input bg-background min-h-[72px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                    />
                </div>
            </Form>
            <template #footer>
                <div class="flex w-full flex-wrap justify-end gap-2">
                    <Button type="button" variant="outline" @click="addAccountOpen = false">
                        Close
                    </Button>
                    <Button type="button" :disabled="accountForm.processing" @click="submitAccount">
                        <Spinner v-if="accountForm.processing" class="mr-2 size-4" />
                        Save
                    </Button>
                </div>
            </template>
        </StandardFormModal>

        <StandardFormModal
            v-model:open="editAccountOpen"
            title="Edit account"
            size="lg"
            :visit-on-dismiss="false"
        >
            <Form v-if="editingAccount" class="space-y-4" @submit.prevent="submitEditAccount">
                <div class="grid gap-2">
                    <Label for="pe-name">Name *</Label>
                    <Input id="pe-name" v-model="editForm.name" required />
                </div>
                <div class="grid gap-2">
                    <Label for="pe-num">Account number *</Label>
                    <Input id="pe-num" v-model="editForm.account_number" required />
                </div>
                <div class="grid gap-2">
                    <Label>Account type</Label>
                    <Select v-model="editForm.account_type_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Optional" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="NONE">None</SelectItem>
                            <SelectItem
                                v-for="t in ledgerTypeOptions"
                                :key="t.id"
                                :value="String(t.id)"
                            >
                                {{ t.parent_id ? `— ${t.name}` : t.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label for="pe-ob">Opening balance</Label>
                    <Input
                        id="pe-ob"
                        v-model="editForm.opening_balance"
                        type="text"
                        inputmode="decimal"
                    />
                </div>
                <div>
                    <Label class="mb-2 block">Account details</Label>
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="py-1 text-left font-medium">Label</th>
                                <th class="py-1 text-left font-medium">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(pair, i) in editForm.account_details"
                                :key="i"
                                class="border-b border-border/60"
                            >
                                <td class="py-1 pr-2">
                                    <Input v-model="pair.label" />
                                </td>
                                <td class="py-1">
                                    <Input v-model="pair.value" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="grid gap-2">
                    <Label for="pe-note">Note</Label>
                    <textarea
                        id="pe-note"
                        v-model="editForm.notes"
                        rows="3"
                        class="border-input bg-background min-h-[72px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                    />
                </div>
                <p v-if="editForm.errors.name" class="text-destructive text-sm">
                    {{ editForm.errors.name }}
                </p>
            </Form>
            <template #footer>
                <div class="flex w-full flex-wrap justify-end gap-2">
                    <Button type="button" variant="outline" @click="editAccountOpen = false">
                        Close
                    </Button>
                    <Button type="button" :disabled="editForm.processing" @click="submitEditAccount">
                        <Spinner v-if="editForm.processing" class="mr-2 size-4" />
                        Save
                    </Button>
                </div>
            </template>
        </StandardFormModal>

        <StandardFormModal
            v-model:open="addTypeOpen"
            title="Add account type"
            size="md"
            :visit-on-dismiss="false"
        >
            <Form class="space-y-4" @submit.prevent="submitType">
                <div class="grid gap-2">
                    <Label for="pt-name">Name *</Label>
                    <Input id="pt-name" v-model="typeForm.name" required />
                    <p v-if="typeForm.errors.name" class="text-destructive text-sm">
                        {{ typeForm.errors.name }}
                    </p>
                </div>
                <div class="grid gap-2">
                    <Label>Parent account type</Label>
                    <Select v-model="typeForm.parent_account_type_id">
                        <SelectTrigger>
                            <SelectValue placeholder="None (top-level)" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="NONE">None</SelectItem>
                            <SelectItem
                                v-for="p in parentTypeOptions"
                                :key="p.id"
                                :value="String(p.id)"
                            >
                                {{ p.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </Form>
            <template #footer>
                <div class="flex w-full flex-wrap justify-end gap-2">
                    <Button type="button" variant="outline" @click="addTypeOpen = false">
                        Close
                    </Button>
                    <Button type="button" :disabled="typeForm.processing" @click="submitType">
                        <Spinner v-if="typeForm.processing" class="mr-2 size-4" />
                        Save
                    </Button>
                </div>
            </template>
        </StandardFormModal>
    </div>
</template>
