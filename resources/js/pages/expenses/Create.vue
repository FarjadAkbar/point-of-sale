<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, watch } from 'vue';
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
import expenseCategoryRoutes from '@/routes/expense-categories';
import expenseRoutes from '@/routes/expenses';
import type { Team } from '@/types';

type ChildCat = { id: number; name: string; code: string; parent_id: number };
type ParentCat = { id: number; name: string; code: string };

const props = defineProps<{
    businessLocations: { id: number; name: string }[];
    expenseCategoryParents: ParentCat[];
    expenseCategoryChildren: ChildCat[];
    taxRates: { id: number; name: string; amount: string }[];
    paymentAccounts: {
        id: number;
        name: string;
        payment_method: string;
    }[];
    paymentSettings: {
        cash_enabled: boolean;
        bank_transfer_enabled: boolean;
    };
    teamMembers: { id: number; name: string; email: string }[];
    customers: { id: number; display_name: string }[];
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Expenses',
                href: expenseRoutes.index.url(p.currentTeam!.slug),
            },
            {
                title: 'Add expense',
                href: expenseRoutes.create.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

const NONE = '__none__';

function toLocalInput(d = new Date()): string {
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

function fromDatetimeLocal(v: string): string {
    if (!v.includes('T')) {
        return v;
    }
    const [date, time] = v.split('T');
    const t = (time ?? '00:00').slice(0, 5);

    return `${date} ${t}:00`;
}

type PaymentRow = {
    amount: string;
    paid_on: string;
    method: string;
    payment_account_id: string;
    note: string;
    bank_account_number: string;
};

const form = useForm({
    business_location_id: '',
    parent_category_id: NONE,
    sub_category_id: NONE,
    ref_no: '',
    transaction_date: toLocalInput(),
    expense_for_user_id: NONE,
    contact_id: NONE,
    document: null as File | null,
    tax_rate_id: NONE,
    final_total: '',
    additional_notes: '',
    is_refund: false,
    is_recurring: false,
    recur_interval: '1',
    recur_interval_type: 'days',
    recur_repetitions: '',
    subscription_repeat_on: NONE,
    payments: [
        {
            amount: '0',
            paid_on: toLocalInput(),
            method: 'cash',
            payment_account_id: NONE,
            note: '',
            bank_account_number: '',
        },
    ] as PaymentRow[],
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

const defaultMethod = computed(() => {
    if (props.paymentSettings.cash_enabled) {
        return 'cash';
    }
    if (props.paymentSettings.bank_transfer_enabled) {
        return 'bank_transfer';
    }

    return 'cash';
});

watch(
    defaultMethod,
    (m) => {
        for (const row of form.payments) {
            if (!row.method || !methodOptions.value.some((x) => x.value === row.method)) {
                row.method = m;
            }
        }
    },
    { immediate: true },
);

const childrenForParent = computed(() => {
    if (form.parent_category_id === NONE) {
        return [];
    }

    const pid = Number(form.parent_category_id);

    return props.expenseCategoryChildren.filter((c) => c.parent_id === pid);
});

watch(
    () => form.parent_category_id,
    () => {
        form.sub_category_id = NONE;
    },
);

watch(
    () => form.final_total,
    (t) => {
        if (form.payments.length === 1) {
            const n = Number(t);
            form.payments[0].amount = Number.isFinite(n) ? n.toFixed(2) : '0';
        }
    },
);

function accountsForMethod(method: string) {
    return props.paymentAccounts.filter((a) => a.payment_method === method);
}

function addPaymentRow() {
    form.payments.push({
        amount: '0',
        paid_on: toLocalInput(),
        method: defaultMethod.value,
        payment_account_id: NONE,
        note: '',
        bank_account_number: '',
    });
}

function removePaymentRow(i: number) {
    if (form.payments.length <= 1) {
        return;
    }
    form.payments.splice(i, 1);
}

const paymentsSum = computed(() =>
    form.payments.reduce((s, p) => s + (Number(p.amount) || 0), 0),
);

function resolvedCategoryId(): number | null {
    if (form.sub_category_id !== NONE) {
        return Number(form.sub_category_id);
    }
    if (form.parent_category_id !== NONE) {
        return Number(form.parent_category_id);
    }

    return null;
}

function submit() {
    form
        .transform((d) => {
            const payments = d.payments.map((row) => ({
                amount: Number(row.amount) || 0,
                paid_on: fromDatetimeLocal(row.paid_on),
                method: row.method,
                payment_account_id:
                    row.payment_account_id === NONE
                        ? null
                        : Number(row.payment_account_id),
                note: row.note?.trim() || null,
                bank_account_number:
                    row.bank_account_number?.trim() || null,
            }));

            return {
                business_location_id: Number(d.business_location_id),
                expense_category_id: resolvedCategoryId(),
                ref_no: d.ref_no?.trim() || null,
                transaction_date: fromDatetimeLocal(d.transaction_date),
                expense_for_user_id:
                    d.expense_for_user_id === NONE
                        ? null
                        : Number(d.expense_for_user_id),
                contact_id:
                    d.contact_id === NONE ? null : Number(d.contact_id),
                tax_rate_id:
                    d.tax_rate_id === NONE ? null : Number(d.tax_rate_id),
                final_total: Number(d.final_total) || 0,
                additional_notes: d.additional_notes?.trim() || null,
                is_refund: d.is_refund,
                is_recurring: d.is_recurring,
                recur_interval: d.is_recurring
                    ? Number(d.recur_interval) || null
                    : null,
                recur_interval_type: d.is_recurring
                    ? d.recur_interval_type
                    : null,
                recur_repetitions:
                    d.is_recurring && d.recur_repetitions !== ''
                        ? Number(d.recur_repetitions)
                        : null,
                subscription_repeat_on:
                    d.is_recurring && d.subscription_repeat_on !== NONE
                        ? Number(d.subscription_repeat_on)
                        : null,
                payments: JSON.stringify(payments),
                document: d.document,
            };
        })
        .post(expenseRoutes.store.url(teamSlug.value), {
            forceFormData: true,
            preserveScroll: true,
        });
}
</script>

<template>
    <Head title="Add expense" />

    <div class="mx-auto flex max-w-5xl flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-semibold tracking-tight">Add expense</h1>
            <div class="flex flex-wrap gap-2">
                <Button variant="outline" as-child>
                    <Link :href="expenseCategoryRoutes.index.url(teamSlug)">
                        Expense categories
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="expenseRoutes.index.url(teamSlug)">
                        Back to list
                    </Link>
                </Button>
            </div>
        </div>

        <form class="space-y-8" @submit.prevent="submit">
            <section
                class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6"
            >
                <h2 class="mb-4 text-lg font-medium">Details</h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div class="grid gap-2">
                        <Label>Business location *</Label>
                        <Select v-model="form.business_location_id" required>
                            <SelectTrigger>
                                <SelectValue placeholder="Select location" />
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
                        <p
                            v-if="form.errors.business_location_id"
                            class="text-destructive text-sm"
                        >
                            {{ form.errors.business_location_id }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label>Expense category</Label>
                        <Select v-model="form.parent_category_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Category" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">None</SelectItem>
                                <SelectItem
                                    v-for="c in expenseCategoryParents"
                                    :key="c.id"
                                    :value="String(c.id)"
                                >
                                    {{ c.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label>Sub category</Label>
                        <Select
                            v-model="form.sub_category_id"
                            :disabled="
                                form.parent_category_id === NONE ||
                                !childrenForParent.length
                            "
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Sub category" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">None</SelectItem>
                                <SelectItem
                                    v-for="c in childrenForParent"
                                    :key="c.id"
                                    :value="String(c.id)"
                                >
                                    {{ c.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="ex-ref">Reference no.</Label>
                        <Input
                            id="ex-ref"
                            v-model="form.ref_no"
                            autocomplete="off"
                        />
                        <p class="text-muted-foreground text-xs">
                            Leave empty to auto-generate.
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="ex-date">Date *</Label>
                        <Input
                            id="ex-date"
                            v-model="form.transaction_date"
                            type="datetime-local"
                            required
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label>Expense for (user)</Label>
                        <Select v-model="form.expense_for_user_id">
                            <SelectTrigger>
                                <SelectValue placeholder="None" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">None</SelectItem>
                                <SelectItem
                                    v-for="u in teamMembers"
                                    :key="u.id"
                                    :value="String(u.id)"
                                >
                                    {{ u.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2 md:col-span-2">
                        <Label>Expense for contact</Label>
                        <Select v-model="form.contact_id">
                            <SelectTrigger>
                                <SelectValue placeholder="None" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">None</SelectItem>
                                <SelectItem
                                    v-for="c in customers"
                                    :key="c.id"
                                    :value="String(c.id)"
                                >
                                    {{ c.display_name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="ex-doc">Attach document</Label>
                        <Input
                            id="ex-doc"
                            type="file"
                            accept=".pdf,.csv,.zip,.doc,.docx,.jpeg,.jpg,.png"
                            @change="
                                (e) => {
                                    const f = (e.target as HTMLInputElement)
                                        .files?.[0];
                                    form.document = f ?? null;
                                }
                            "
                        />
                        <p class="text-muted-foreground text-xs">
                            Max 5 MB. PDF, CSV, ZIP, Word, JPEG, PNG.
                        </p>
                        <p
                            v-if="form.errors.document"
                            class="text-destructive text-sm"
                        >
                            {{ form.errors.document }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label>Applicable tax</Label>
                        <Select v-model="form.tax_rate_id">
                            <SelectTrigger>
                                <SelectValue placeholder="None" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">None</SelectItem>
                                <SelectItem
                                    v-for="t in taxRates"
                                    :key="t.id"
                                    :value="String(t.id)"
                                >
                                    {{ t.name }} ({{ t.amount }}%)
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p class="text-muted-foreground text-xs">
                            Total amount is tax-inclusive when a tax is selected.
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="ex-total">Total amount *</Label>
                        <Input
                            id="ex-total"
                            v-model="form.final_total"
                            type="text"
                            inputmode="decimal"
                            required
                        />
                    </div>
                    <div class="grid gap-2 md:col-span-3">
                        <Label for="ex-note">Expense note</Label>
                        <textarea
                            id="ex-note"
                            v-model="form.additional_notes"
                            rows="3"
                            class="border-input bg-background min-h-[72px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                        />
                    </div>
                    <div class="flex items-center gap-2 md:col-span-2">
                        <Checkbox
                            id="ex-refund"
                            :model-value="form.is_refund"
                            @update:model-value="
                                (v) => {
                                    form.is_refund = v === true;
                                }
                            "
                        />
                        <Label for="ex-refund" class="cursor-pointer font-normal">
                            Is refund?
                        </Label>
                    </div>
                </div>
            </section>

            <section
                class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6"
            >
                <h2 class="mb-4 text-lg font-medium">Recurring</h2>
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="ex-rec"
                        :model-value="form.is_recurring"
                        @update:model-value="
                            (v) => {
                                form.is_recurring = v === true;
                            }
                        "
                    />
                    <Label for="ex-rec" class="cursor-pointer font-normal">
                        Is recurring?
                    </Label>
                </div>
                <div
                    v-if="form.is_recurring"
                    class="mt-4 grid gap-4 md:grid-cols-3"
                >
                    <div class="grid gap-2 md:col-span-1">
                        <Label for="ex-int">Recurring interval *</Label>
                        <div class="flex gap-2">
                            <Input
                                id="ex-int"
                                v-model="form.recur_interval"
                                type="number"
                                min="1"
                                class="w-24"
                            />
                            <Select v-model="form.recur_interval_type">
                                <SelectTrigger class="flex-1">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="days">Days</SelectItem>
                                    <SelectItem value="months">Months</SelectItem>
                                    <SelectItem value="years">Years</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <Label for="ex-rep">No. of repetitions</Label>
                        <Input
                            id="ex-rep"
                            v-model="form.recur_repetitions"
                            type="number"
                            min="1"
                            placeholder="Blank = unlimited"
                        />
                    </div>
                    <div
                        v-if="form.recur_interval_type === 'months'"
                        class="grid gap-2"
                    >
                        <Label>Repeat on (day of month)</Label>
                        <Select v-model="form.subscription_repeat_on">
                            <SelectTrigger>
                                <SelectValue placeholder="Optional" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">Not set</SelectItem>
                                <SelectItem
                                    v-for="d in 30"
                                    :key="d"
                                    :value="String(d)"
                                >
                                    Day {{ d }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </section>

            <section
                class="rounded-xl border border-border bg-card p-4 shadow-sm md:p-6"
            >
                <div class="mb-4 flex items-center justify-between gap-2">
                    <h2 class="text-lg font-medium">Payments</h2>
                    <Button type="button" variant="outline" size="sm" @click="addPaymentRow">
                        <Plus class="mr-1 size-4" />
                        Add payment
                    </Button>
                </div>
                <div class="space-y-6">
                    <div
                        v-for="(row, i) in form.payments"
                        :key="i"
                        class="space-y-4 rounded-lg border border-border/80 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground text-sm">
                                Payment {{ i + 1 }}
                            </span>
                            <Button
                                v-if="form.payments.length > 1"
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="size-8"
                                @click="removePaymentRow(i)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="grid gap-2">
                                <Label>Amount *</Label>
                                <Input
                                    v-model="row.amount"
                                    type="text"
                                    inputmode="decimal"
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label>Paid on *</Label>
                                <Input
                                    v-model="row.paid_on"
                                    type="datetime-local"
                                    required
                                />
                            </div>
                            <div class="grid gap-2">
                                <Label>Method *</Label>
                                <Select
                                    v-model="row.method"
                                    required
                                    @update:model-value="
                                        () => {
                                            row.payment_account_id = NONE;
                                        }
                                    "
                                >
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
                            <div class="grid gap-2 md:col-span-2">
                                <Label>Payment account</Label>
                                <Select v-model="row.payment_account_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="None" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem :value="NONE">None</SelectItem>
                                        <SelectItem
                                            v-for="a in accountsForMethod(row.method)"
                                            :key="a.id"
                                            :value="String(a.id)"
                                        >
                                            {{ a.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2 md:col-span-3">
                                <Label>Payment note</Label>
                                <textarea
                                    v-model="row.note"
                                    rows="2"
                                    class="border-input bg-background min-h-[56px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                                />
                            </div>
                            <div
                                v-if="row.method === 'bank_transfer'"
                                class="grid gap-2 md:col-span-2"
                            >
                                <Label>Bank account no.</Label>
                                <Input v-model="row.bank_account_number" />
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-muted-foreground mt-4 text-right text-sm">
                    Payments total:
                    <span class="font-semibold text-foreground">{{
                        paymentsSum.toFixed(2)
                    }}</span>
                    · Expense total:
                    <span class="font-semibold text-foreground">{{
                        (Number(form.final_total) || 0).toFixed(2)
                    }}</span>
                </p>
                <p v-if="form.errors.payments" class="text-destructive mt-2 text-sm">
                    {{ form.errors.payments }}
                </p>
            </section>

            <div class="flex justify-end gap-2">
                <Button variant="outline" type="button" as-child>
                    <Link :href="expenseRoutes.index.url(teamSlug)">Cancel</Link>
                </Button>
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" class="mr-2 size-4" />
                    {{ form.processing ? 'Saving…' : 'Save' }}
                </Button>
            </div>
        </form>
    </div>
</template>
