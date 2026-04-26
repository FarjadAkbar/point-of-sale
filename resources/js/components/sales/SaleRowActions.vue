<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Bell,
    Copy,
    CreditCard,
    Eye,
    FileText,
    MoreHorizontal,
    Pencil,
    Printer,
    Ship,
    Trash2,
    Truck,
    Undo2,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
import { xsrfToken } from '@/lib/xsrf';
import salesRoutes from '@/routes/sales';

export type SaleRow = {
    id: number;
    created_by?: number | null;
    invoice_no: string | null;
    transaction_date: string | null;
    status: string;
    final_total: string;
    customer: { id: number; display_name: string } | null;
    business_location: { id: number; name: string } | null;
};

type CustomerOption = { id: number; display_name: string };

type SaleDetail = {
    id: number;
    invoice_no: string | null;
    transaction_date: string | null;
    status: string;
    final_total: string;
    lines_total: string;
    sale_note: string | null;
    discount_type: string | null;
    discount_amount: string;
    sale_tax_amount: string;
    shipping_details: string | null;
    shipping_charges: string;
    shipping_address: string | null;
    shipping_status: string | null;
    delivered_to: string | null;
    delivery_person: string | null;
    shipping_customer_note: string | null;
    shipping_document_path: string | null;
    document_path: string | null;
    customer: { id: number; display_name: string } | null;
    business_location: { id: number; name: string } | null;
    tax_rate: { id: number; name: string; amount: string } | null;
    lines: Array<{
        id: number;
        product_id: number;
        product_name: string;
        sku: string | null;
        quantity: string;
        unit_price_exc_tax: string;
        line_total: string;
    }>;
    payments: Array<{
        id: number;
        amount: string;
        paid_on: string | null;
        method: string | null;
        note: string | null;
        payment_account: string | null;
    }>;
    activities: Array<{
        id: number;
        action: string;
        note: string | null;
        by: string | null;
        date: string | null;
    }>;
};

const props = defineProps<{
    row: SaleRow;
    teamSlug: string;
    customers: CustomerOption[];
}>();

const page = usePage();
const posPermissions = computed<string[]>(() => {
    const value = page.props.posPermissions;
    return Array.isArray(value) ? (value as string[]) : [];
});
const authUserId = computed(
    () =>
        (page.props.auth as { user?: { id: number } } | undefined)?.user?.id ??
        null,
);
const hasPerm = (p: string): boolean => posPermissions.value.includes(p);
const isDraft = computed(() => props.row.status === 'draft');
const isQuotation = computed(() => props.row.status === 'quotation');
const canUpdateSale = computed(() =>
    isDraft.value
        ? hasPerm('draft.update')
        : isQuotation.value
          ? hasPerm('quotation.update')
          : hasPerm('direct_sell.update'),
);
const canDeleteSale = computed(() =>
    isDraft.value
        ? hasPerm('draft.delete')
        : isQuotation.value
          ? hasPerm('quotation.delete')
          : hasPerm('direct_sell.delete'),
);
const canEditShipping = computed(() => hasPerm('direct_sell.update'));
const canViewPayments = computed(
    () =>
        hasPerm('sell.payments') ||
        hasPerm('edit_sell_payment') ||
        hasPerm('delete_sell_payment'),
);
const canSellReturn = computed(
    () =>
        hasPerm('access_sell_return') ||
        (hasPerm('access_own_sell_return') &&
            authUserId.value != null &&
            Number(props.row.created_by ?? 0) === authUserId.value),
);
const canEditInvoiceNumber = computed(() => hasPerm('edit_invoice_number'));

const detailCache = new Map<number, SaleDetail>();

const viewOpen = ref(false);
const editOpen = ref(false);
const shippingOpen = ref(false);
const paymentsOpen = ref(false);
const notifyOpen = ref(false);

const loading = ref(false);
const loadError = ref<string | null>(null);
const detail = ref<SaleDetail | null>(null);

const editForm = ref({
    invoice_no: '',
    transaction_date: '',
    customer_id: '',
    sale_note: '',
});

const shippingForm = ref({
    shipping_details: '',
    shipping_charges: '',
    shipping_address: '',
    shipping_status: '',
    delivered_to: '',
    delivery_person: '',
    shipping_customer_note: '',
});
const shippingFile = ref<File | null>(null);

const savingEdit = ref(false);
const savingShipping = ref(false);
const deleting = ref(false);

const origin = typeof window !== 'undefined' ? window.location.origin : '';

const isFinal = computed(() => props.row.status === 'final');

function toDatetimeLocal(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const d = new Date(iso);
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

function shippingDocUrl(path: string | null): string | null {
    if (!path) {
        return null;
    }

    return `${origin}/storage/${path.replace(/^\/+/, '')}`;
}

async function fetchDetail(saleId: number): Promise<SaleDetail> {
    const cached = detailCache.get(saleId);

    if (cached) {
        return cached;
    }

    const res = await fetch(salesRoutes.detail.url(props.teamSlug, saleId), {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        credentials: 'same-origin',
    });

    if (!res.ok) {
        throw new Error('Could not load sale details.');
    }

    const body = (await res.json()) as { sale: SaleDetail };
    detailCache.set(saleId, body.sale);

    return body.sale;
}

function invalidateDetail(saleId: number) {
    detailCache.delete(saleId);
}

async function openView() {
    viewOpen.value = true;
    loadError.value = null;
    loading.value = true;

    try {
        detail.value = await fetchDetail(props.row.id);
    } catch (e) {
        loadError.value =
            e instanceof Error ? e.message : 'Could not load sale details.';
        detail.value = null;
    } finally {
        loading.value = false;
    }
}

async function openEdit() {
    editOpen.value = true;
    loadError.value = null;
    loading.value = true;

    try {
        const d = await fetchDetail(props.row.id);
        detail.value = d;
        editForm.value = {
            invoice_no: d.invoice_no ?? '',
            transaction_date: toDatetimeLocal(d.transaction_date),
            customer_id: d.customer ? String(d.customer.id) : '',
            sale_note: d.sale_note ?? '',
        };
    } catch (e) {
        loadError.value =
            e instanceof Error ? e.message : 'Could not load sale details.';
    } finally {
        loading.value = false;
    }
}

async function openShipping() {
    shippingOpen.value = true;
    loadError.value = null;
    shippingFile.value = null;
    loading.value = true;

    try {
        const d = await fetchDetail(props.row.id);
        detail.value = d;
        shippingForm.value = {
            shipping_details: d.shipping_details ?? '',
            shipping_charges: d.shipping_charges ?? '',
            shipping_address: d.shipping_address ?? '',
            shipping_status: d.shipping_status ?? '',
            delivered_to: d.delivered_to ?? '',
            delivery_person: d.delivery_person ?? '',
            shipping_customer_note: d.shipping_customer_note ?? '',
        };
    } catch (e) {
        loadError.value =
            e instanceof Error ? e.message : 'Could not load sale details.';
    } finally {
        loading.value = false;
    }
}

async function openPayments() {
    paymentsOpen.value = true;
    loadError.value = null;
    loading.value = true;

    try {
        detail.value = await fetchDetail(props.row.id);
    } catch (e) {
        loadError.value =
            e instanceof Error ? e.message : 'Could not load sale details.';
    } finally {
        loading.value = false;
    }
}

function submitEdit() {
    savingEdit.value = true;
    router.patch(
        salesRoutes.update.url(props.teamSlug, props.row.id),
        {
            invoice_no: editForm.value.invoice_no || null,
            transaction_date: fromDatetimeLocal(editForm.value.transaction_date),
            customer_id: Number(editForm.value.customer_id),
            sale_note: editForm.value.sale_note || null,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                savingEdit.value = false;
            },
            onSuccess: () => {
                invalidateDetail(props.row.id);
                editOpen.value = false;
            },
        },
    );
}

function submitShipping() {
    savingShipping.value = true;
    const fields = {
        shipping_details: shippingForm.value.shipping_details || null,
        shipping_charges: shippingForm.value.shipping_charges || null,
        shipping_address: shippingForm.value.shipping_address || null,
        shipping_status: shippingForm.value.shipping_status || null,
        delivered_to: shippingForm.value.delivered_to || null,
        delivery_person: shippingForm.value.delivery_person || null,
        shipping_customer_note:
            shippingForm.value.shipping_customer_note || null,
    };
    const opts = {
        preserveScroll: true,
        onFinish: () => {
            savingShipping.value = false;
        },
        onSuccess: () => {
            invalidateDetail(props.row.id);
            shippingOpen.value = false;
            shippingFile.value = null;
        },
    };

    if (shippingFile.value) {
        router.post(
            salesRoutes.shipping.update.url(props.teamSlug, props.row.id),
            {
                _method: 'patch',
                ...fields,
                shipping_document: shippingFile.value,
            },
            { ...opts, forceFormData: true },
        );
    } else {
        router.patch(
            salesRoutes.shipping.update.url(props.teamSlug, props.row.id),
            fields,
            opts,
        );
    }
}

function confirmDelete() {
    if (
        !confirm(
            `Delete sale ${props.row.invoice_no?.trim() ? props.row.invoice_no : '#' + props.row.id}? This cannot be undone.`,
        )
    ) {
        return;
    }

    deleting.value = true;
    router.delete(salesRoutes.destroy.url(props.teamSlug, props.row.id), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
        },
    });
}

function openPrint(kind: 'invoice' | 'packingSlip' | 'deliveryNote') {
    let path: string;

    if (kind === 'invoice') {
        path = salesRoutes.documents.invoice.url(props.teamSlug, props.row.id);
    } else if (kind === 'packingSlip') {
        path = salesRoutes.documents.packingSlip.url(
            props.teamSlug,
            props.row.id,
        );
    } else {
        path = salesRoutes.documents.deliveryNote.url(
            props.teamSlug,
            props.row.id,
        );
    }

    window.open(`${origin}${path}`, '_blank', 'noopener,noreferrer');
}

async function copyInvoiceUrl() {
    try {
        const res = await fetch(
            salesRoutes.invoiceLink.url(props.teamSlug, props.row.id),
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                credentials: 'same-origin',
            },
        );

        if (!res.ok) {
            throw new Error('Failed');
        }

        const body = (await res.json()) as { url: string };
        const absolute = body.url.startsWith('http')
            ? body.url
            : `${origin}${body.url}`;
        await navigator.clipboard.writeText(absolute);
    } catch {
        window.alert('Could not copy invoice URL.');
    }
}

watch(viewOpen, (open) => {
    if (!open) {
        loadError.value = null;
    }
});
watch(editOpen, (open) => {
    if (!open) {
        loadError.value = null;
    }
});
watch(shippingOpen, (open) => {
    if (!open) {
        loadError.value = null;
    }
});
watch(paymentsOpen, (open) => {
    if (!open) {
        loadError.value = null;
    }
});

const sellReturnHref = computed(() =>
    salesRoutes.returns.create.url(props.teamSlug, {
        query: { sale_id: String(props.row.id) },
    }),
);
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8"
                :disabled="deleting"
            >
                <MoreHorizontal class="size-4" />
                <span class="sr-only">Row actions</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-52">
            <DropdownMenuLabel>Actions</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem @select.prevent="openView">
                <Eye class="mr-2 size-4" />
                View
            </DropdownMenuItem>
            <DropdownMenuItem v-if="canUpdateSale" @select.prevent="openEdit">
                <Pencil class="mr-2 size-4" />
                Edit
            </DropdownMenuItem>
            <DropdownMenuItem v-if="canDeleteSale" @select.prevent="confirmDelete">
                <Trash2 class="mr-2 size-4" />
                Delete
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem v-if="canEditShipping" @select.prevent="openShipping">
                <Ship class="mr-2 size-4" />
                Edit shipping
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem @select.prevent="() => openPrint('invoice')">
                <Printer class="mr-2 size-4" />
                Print invoice
            </DropdownMenuItem>
            <DropdownMenuItem @select.prevent="() => openPrint('packingSlip')">
                <FileText class="mr-2 size-4" />
                Packing slip
            </DropdownMenuItem>
            <DropdownMenuItem @select.prevent="() => openPrint('deliveryNote')">
                <Truck class="mr-2 size-4" />
                Delivery note
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem v-if="canViewPayments" @select.prevent="openPayments">
                <CreditCard class="mr-2 size-4" />
                View payments
            </DropdownMenuItem>
            <DropdownMenuItem v-if="isFinal && canSellReturn" as-child>
                <Link
                    :href="sellReturnHref"
                    class="flex w-full cursor-default items-center"
                >
                    <Undo2 class="mr-2 size-4" />
                    Sell return
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem v-else disabled class="opacity-50">
                <Undo2 class="mr-2 size-4" />
                Sell return
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem @select.prevent="copyInvoiceUrl">
                <Copy class="mr-2 size-4" />
                Invoice URL
            </DropdownMenuItem>
            <DropdownMenuItem @select.prevent="notifyOpen = true">
                <Bell class="mr-2 size-4" />
                New sale notification
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>

    <Dialog v-model:open="viewOpen">
        <DialogContent class="max-h-[90vh] max-w-3xl overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Sale details</DialogTitle>
                <DialogDescription>
                    Invoice
                    {{ row.invoice_no?.trim() ? row.invoice_no : '#' + row.id }}
                </DialogDescription>
            </DialogHeader>
            <div v-if="loading" class="flex justify-center py-8">
                <Spinner class="size-6" />
            </div>
            <p v-else-if="loadError" class="text-destructive text-sm">
                {{ loadError }}
            </p>
            <div v-else-if="detail" class="space-y-4 text-sm">
                <div class="grid gap-2 sm:grid-cols-2">
                    <div>
                        <div class="text-muted-foreground">Status</div>
                        <div class="font-medium">{{ detail.status }}</div>
                    </div>
                    <div>
                        <div class="text-muted-foreground">Total</div>
                        <div class="font-medium">{{ detail.final_total }}</div>
                    </div>
                    <div>
                        <div class="text-muted-foreground">Customer</div>
                        <div class="font-medium">
                            {{ detail.customer?.display_name ?? '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-muted-foreground">Location</div>
                        <div class="font-medium">
                            {{ detail.business_location?.name ?? '—' }}
                        </div>
                    </div>
                </div>
                <div v-if="detail.sale_note" class="rounded-md border p-3">
                    <div class="text-muted-foreground mb-1 text-xs">Sale note</div>
                    <div class="whitespace-pre-wrap">{{ detail.sale_note }}</div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-xs">
                        <thead>
                            <tr class="border-b bg-muted/40">
                                <th class="px-2 py-2 text-left">Product</th>
                                <th class="px-2 py-2 text-left">Qty</th>
                                <th class="px-2 py-2 text-left">Unit</th>
                                <th class="px-2 py-2 text-left">Line</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="line in detail.lines"
                                :key="line.id"
                                class="border-b border-border/60"
                            >
                                <td class="px-2 py-2">
                                    {{ line.product_name }}
                                    <span
                                        v-if="line.sku"
                                        class="text-muted-foreground"
                                        >({{ line.sku }})</span
                                    >
                                </td>
                                <td class="px-2 py-2">{{ line.quantity }}</td>
                                <td class="px-2 py-2">
                                    {{ line.unit_price_exc_tax }}
                                </td>
                                <td class="px-2 py-2">{{ line.line_total }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="editOpen">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>Edit sale</DialogTitle>
                <DialogDescription>
                    Update invoice, date, customer, and note.
                </DialogDescription>
            </DialogHeader>
            <div v-if="loading" class="flex justify-center py-8">
                <Spinner class="size-6" />
            </div>
            <p v-else-if="loadError" class="text-destructive text-sm">
                {{ loadError }}
            </p>
            <form
                v-else
                class="grid gap-4"
                @submit.prevent="submitEdit"
            >
                <div class="grid gap-2">
                    <Label for="edit-inv">Invoice no.</Label>
                    <Input
                        id="edit-inv"
                        v-model="editForm.invoice_no"
                        autocomplete="off"
                        :disabled="!canEditInvoiceNumber"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="edit-dt">Transaction date</Label>
                    <Input
                        id="edit-dt"
                        v-model="editForm.transaction_date"
                        type="datetime-local"
                        required
                    />
                </div>
                <div class="grid gap-2">
                    <Label>Customer</Label>
                    <Select v-model="editForm.customer_id" required>
                        <SelectTrigger>
                            <SelectValue placeholder="Customer" />
                        </SelectTrigger>
                        <SelectContent>
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
                    <Label for="edit-note">Sale note</Label>
                    <textarea
                        id="edit-note"
                        v-model="editForm.sale_note"
                        rows="3"
                        class="border-input bg-background min-h-[72px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                    />
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="editOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="savingEdit">
                        {{ savingEdit ? 'Saving…' : 'Save' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="shippingOpen">
        <DialogContent class="max-h-[90vh] max-w-2xl overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Shipping details</DialogTitle>
                <DialogDescription>
                    Update shipping fields and optional document.
                </DialogDescription>
            </DialogHeader>
            <div v-if="loading" class="flex justify-center py-8">
                <Spinner class="size-6" />
            </div>
            <p v-else-if="loadError" class="text-destructive text-sm">
                {{ loadError }}
            </p>
            <form
                v-else
                class="grid gap-4"
                @submit.prevent="submitShipping"
            >
                <div class="grid gap-2">
                    <Label for="sh-det">Shipping details</Label>
                    <Input id="sh-det" v-model="shippingForm.shipping_details" />
                </div>
                <div class="grid gap-2">
                    <Label for="sh-addr">Shipping address</Label>
                    <textarea
                        id="sh-addr"
                        v-model="shippingForm.shipping_address"
                        rows="2"
                        class="border-input bg-background min-h-[56px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                    />
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="sh-st">Shipping status</Label>
                        <Input id="sh-st" v-model="shippingForm.shipping_status" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="sh-ch">Shipping charges</Label>
                        <Input
                            id="sh-ch"
                            v-model="shippingForm.shipping_charges"
                            type="text"
                            inputmode="decimal"
                        />
                    </div>
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="sh-dt">Delivered to</Label>
                        <Input id="sh-dt" v-model="shippingForm.delivered_to" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="sh-dp">Delivery person</Label>
                        <Input
                            id="sh-dp"
                            v-model="shippingForm.delivery_person"
                        />
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label for="sh-note">Shipping note</Label>
                    <textarea
                        id="sh-note"
                        v-model="shippingForm.shipping_customer_note"
                        rows="2"
                        class="border-input bg-background min-h-[56px] w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="sh-doc">Shipping document</Label>
                    <Input
                        id="sh-doc"
                        type="file"
                        @change="
                            shippingFile = ($event.target as HTMLInputElement)
                                .files?.[0] ?? null
                        "
                    />
                    <a
                        v-if="shippingDocUrl(detail?.shipping_document_path ?? null)"
                        :href="
                            shippingDocUrl(detail?.shipping_document_path ?? null)!
                        "
                        class="text-primary text-xs underline"
                        target="_blank"
                        rel="noopener noreferrer"
                        >Current file</a
                    >
                </div>
                <div v-if="detail?.activities?.length" class="rounded-md border">
                    <div class="bg-muted/40 px-3 py-2 text-xs font-medium">
                        Activities
                    </div>
                    <div class="max-h-48 overflow-y-auto">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-2 py-1.5 text-left">Date</th>
                                    <th class="px-2 py-1.5 text-left">Action</th>
                                    <th class="px-2 py-1.5 text-left">By</th>
                                    <th class="px-2 py-1.5 text-left">Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="a in detail.activities"
                                    :key="a.id"
                                    class="border-b border-border/50"
                                >
                                    <td class="px-2 py-1.5 whitespace-nowrap">
                                        {{
                                            a.date
                                                ? new Date(a.date).toLocaleString()
                                                : '—'
                                        }}
                                    </td>
                                    <td class="px-2 py-1.5">{{ a.action }}</td>
                                    <td class="px-2 py-1.5">{{ a.by ?? '—' }}</td>
                                    <td class="px-2 py-1.5">{{ a.note ?? '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="shippingOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="savingShipping">
                        {{ savingShipping ? 'Saving…' : 'Save' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="paymentsOpen">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>Payments</DialogTitle>
                <DialogDescription>
                    Recorded payments for this sale.
                </DialogDescription>
            </DialogHeader>
            <div v-if="loading" class="flex justify-center py-8">
                <Spinner class="size-6" />
            </div>
            <p v-else-if="loadError" class="text-destructive text-sm">
                {{ loadError }}
            </p>
            <div v-else-if="detail" class="max-h-72 overflow-y-auto">
                <table
                    v-if="detail.payments.length"
                    class="w-full border-collapse text-sm"
                >
                    <thead>
                        <tr class="border-b bg-muted/40">
                            <th class="px-2 py-2 text-left">Date</th>
                            <th class="px-2 py-2 text-left">Method</th>
                            <th class="px-2 py-2 text-left">Account</th>
                            <th class="px-2 py-2 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="p in detail.payments"
                            :key="p.id"
                            class="border-b border-border/60"
                        >
                            <td class="px-2 py-2 whitespace-nowrap text-xs">
                                {{
                                    p.paid_on
                                        ? new Date(p.paid_on).toLocaleString()
                                        : '—'
                                }}
                            </td>
                            <td class="px-2 py-2">{{ p.method ?? '—' }}</td>
                            <td class="px-2 py-2 text-xs">
                                {{ p.payment_account ?? '—' }}
                            </td>
                            <td class="px-2 py-2 text-right">{{ p.amount }}</td>
                        </tr>
                    </tbody>
                </table>
                <p v-else class="text-muted-foreground py-6 text-center text-sm">
                    No payments recorded.
                </p>
            </div>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="notifyOpen">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>New sale notification</DialogTitle>
                <DialogDescription>
                    Automatic customer or staff alerts for new sales are not
                    configured in this build. You can extend the app with Laravel
                    notifications or webhooks when you are ready.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button type="button" @click="notifyOpen = false">Close</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
