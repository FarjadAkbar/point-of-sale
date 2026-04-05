<script setup lang="ts">
import {
    Form,
    Head,
    Link,
    useForm,
    usePage,
} from '@inertiajs/vue3';
import { ChevronDown, Plus, Trash2 } from 'lucide-vue-next';
import { computed, reactive, ref, watch } from 'vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import StandardFormModal from '@/components/StandardFormModal.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
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
import BrandForm from '@/pages/brands/BrandForm.vue';
import UnitForm from '@/pages/units/UnitForm.vue';
import brandRoutes from '@/routes/brands';
import productRoutes from '@/routes/products';
import unitRoutes from '@/routes/units';
import type { Team } from '@/types';

/** Matches `Row` in `units/Index.vue` (list payload; `base_unit` omitted on create page). */
type UnitRow = {
    id: number;
    name: string;
    short_name: string;
    allow_decimal: boolean;
    is_multiple_of_base: boolean;
    base_unit_multiplier: string | null;
    created_at: string | null;
};

/** Matches `Row` in `brands/Index.vue`. */
type BrandRow = {
    id: number;
    name: string;
    description: string | null;
    user_for_repair: boolean;
    created_at: string | null;
};
type Cat = {
    id: number;
    name: string;
    parent_id: number | null;
    is_sub_taxonomy: boolean;
};
type VarTpl = {
    id: number;
    name: string;
    values?: { id: number; value: string }[];
};
type ComboLine = {
    product_id: number;
    product_name: string;
    quantity: string;
    purchase_price_exc_tax: string;
    line_total_exc_tax: string;
};
type VarRow = {
    sub_sku: string;
    value: string;
    dpp: string;
    dpp_inc_tax: string;
    profit_percent: string;
    dsp: string;
    dsp_inc_tax: string;
};

const page = usePage();
const teamSlug = computed(() => (page.props.currentTeam as Team).slug);

type BaseUnitOption = { id: number; name: string; short_name: string };

const props = defineProps<{
    units: UnitRow[];
    brands: BrandRow[];
    baseUnits: BaseUnitOption[];
    categories: Cat[];
    variationTemplates: VarTpl[];
    barcodeTypes: { value: string; label: string }[];
    taxOptions: { id: string; label: string; rate: number }[];
    businessLocations: { id: number; name: string }[];
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Products',
                href: productRoutes.index.url(p.currentTeam!.slug),
            },
            {
                title: 'Add product',
                href: productRoutes.create.url(p.currentTeam!.slug),
            },
        ],
    }),
});

const extraBrands = ref<BrandRow[]>([]);
const extraUnits = ref<UnitRow[]>([]);

const brandsList = computed(() => [...props.brands, ...extraBrands.value]);
const unitsList = computed(() => [...props.units, ...extraUnits.value]);

const parentCategories = computed(() =>
    props.categories.filter((c) => !c.is_sub_taxonomy),
);

const NONE = '__none__';
const categoryId = ref('');
const subcategoryId = ref(NONE);

const subcategories = computed(() => {
    if (!categoryId.value) {
        return [];
    }

    const pid = Number(categoryId.value);

    return props.categories.filter(
        (c) => c.is_sub_taxonomy && c.parent_id === pid,
    );
});

watch(categoryId, () => {
    subcategoryId.value = NONE;
});

const selectedLocations = ref<string[]>([]);

function toggleLocation(id: number, on: boolean) {
    const key = String(id);

    if (on && !selectedLocations.value.includes(key)) {
        selectedLocations.value = [...selectedLocations.value, key];
    }

    if (!on) {
        selectedLocations.value = selectedLocations.value.filter((x) => x !== key);
    }
}

const locationsTriggerLabel = computed(() => {
    const locs = props.businessLocations;

    if (!locs.length) {
        return 'No locations';
    }

    const selected = locs.filter((l) =>
        selectedLocations.value.includes(String(l.id)),
    );

    if (selected.length === 0) {
        return 'Select business locations';
    }

    if (selected.length === locs.length) {
        return 'All locations';
    }

    if (selected.length <= 2) {
        return selected.map((l) => l.name).join(', ');
    }

    return `${selected.length} locations selected`;
});

const selectedLocationRows = computed(() =>
    props.businessLocations.filter((l) =>
        selectedLocations.value.includes(String(l.id)),
    ),
);

/** Opening quantity per location id (string keys); only used when manage stock is on. */
const openingStockByLocationId = reactive<Record<string, string>>({});

watch(
    () => [...selectedLocations.value].sort().join(','),
    () => {
        const sel = new Set(selectedLocations.value);
        for (const key of Object.keys(openingStockByLocationId)) {
            if (!sel.has(key)) {
                delete openingStockByLocationId[key];
            }
        }
        for (const id of selectedLocations.value) {
            if (!(id in openingStockByLocationId)) {
                openingStockByLocationId[id] = '';
            }
        }
    },
    { immediate: true },
);

const form = useForm({
    name: '',
    sku: '',
    barcode_type: 'none',
    unit_id: '',
    brand_id: '',
    category_id: '',
    subcategory_id: '',
    manage_stock: false,
    alert_quantity: '',
    description: '',
    product_image: null as File | null,
    product_brochure: null as File | null,
    enable_imei_serial: false,
    not_for_selling: false,
    weight: '',
    preparation_time_minutes: '',
    application_tax: 'none',
    selling_price_tax_type: 'exclusive',
    product_type: 'single',
    single_dpp: '',
    single_dpp_inc_tax: '',
    profit_percent: '25',
    single_dsp: '',
    single_dsp_inc_tax: '',
    combo_lines: [] as ComboLine[],
    combo_profit_percent: '25',
    combo_selling_price: '0',
    combo_selling_price_inc_tax: '0',
    combo_purchase_total_exc_tax: '0',
    combo_purchase_total_inc_tax: '0',
    variation_sku_format: 'with_out_variation',
    variation_matrix: [] as unknown[],
});

const comboSearch = ref('');
const comboHits = ref<{ id: number; name: string; text: string }[]>([]);
let comboTimer: ReturnType<typeof setTimeout> | undefined;

watch(comboSearch, (q) => {
    clearTimeout(comboTimer);
    comboTimer = setTimeout(async () => {
        const t = q.trim();

        if (t.length < 1) {
            comboHits.value = [];

            return;
        }

        const url = productRoutes.search.url(teamSlug.value, {
            query: { q: t },
        });
        const r = await fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        const j = (await r.json()) as {
            data: { id: number; name: string; text: string }[];
        };
        comboHits.value = j.data ?? [];
    }, 300);
});

function pickCombo(p: { id: number; name: string }) {
    form.combo_lines.push({
        product_id: p.id,
        product_name: p.name,
        quantity: '1',
        purchase_price_exc_tax: '0',
        line_total_exc_tax: '0',
    });
    comboSearch.value = '';
    comboHits.value = [];
    recalcComboTotals();
}

function removeCombo(i: number) {
    form.combo_lines.splice(i, 1);
    recalcComboTotals();
}

function recalcComboRow(i: number) {
    const row = form.combo_lines[i];

    if (!row) {
        return;
    }

    const q = Number(row.quantity) || 0;
    const p = Number(row.purchase_price_exc_tax) || 0;
    row.line_total_exc_tax = (q * p).toFixed(4);
    recalcComboTotals();
}

function taxRate(): number {
    return (
        props.taxOptions.find((t) => t.id === form.application_tax)?.rate ?? 0
    );
}

function recalcComboTotals() {
    let net = 0;

    for (const row of form.combo_lines) {
        net += Number(row.line_total_exc_tax) || 0;
    }

    form.combo_purchase_total_exc_tax = net.toFixed(4);
    const inc = net * (1 + taxRate() / 100);
    form.combo_purchase_total_inc_tax = inc.toFixed(4);
    const margin = Number(form.combo_profit_percent) || 0;
    const sp = net * (1 + margin / 100);
    form.combo_selling_price = sp.toFixed(4);
    form.combo_selling_price_inc_tax = (sp * (1 + taxRate() / 100)).toFixed(4);
}

watch(
    () => [form.application_tax, form.combo_profit_percent],
    () => {
        if (form.product_type === 'combo') {
            recalcComboTotals();
        }
    },
);

const variationTemplateId = ref(NONE);
const variationRows = ref<VarRow[]>([
    {
        sub_sku: '',
        value: '',
        dpp: '',
        dpp_inc_tax: '',
        profit_percent: '25',
        dsp: '',
        dsp_inc_tax: '',
    },
]);

function addVariationRow() {
    variationRows.value.push({
        sub_sku: '',
        value: '',
        dpp: '',
        dpp_inc_tax: '',
        profit_percent: '25',
        dsp: '',
        dsp_inc_tax: '',
    });
}

function removeVariationRow(i: number) {
    if (variationRows.value.length <= 1) {
        return;
    }

    variationRows.value.splice(i, 1);
}

function buildVariationMatrix(): unknown[] {
    const tid =
        variationTemplateId.value && variationTemplateId.value !== NONE
            ? Number(variationTemplateId.value)
            : null;

    return [
        {
            variation_template_id: tid,
            variations: variationRows.value.map((r) => ({
                sub_sku: r.sub_sku,
                value: r.value,
                default_purchase_price: r.dpp,
                dpp_inc_tax: r.dpp_inc_tax,
                profit_percent: r.profit_percent,
                default_sell_price: r.dsp,
                sell_price_inc_tax: r.dsp_inc_tax,
            })),
        },
    ];
}

const brandOpen = ref(false);
const unitOpen = ref(false);
const quickSaving = ref(false);

const quickBrandCreateForm = useForm({
    name: '',
    description: '',
    user_for_repair: false,
});

const quickUnitCreateForm = useForm({
    name: '',
    short_name: '',
    allow_decimal: false,
    is_multiple_of_base: false,
    base_unit_multiplier: '',
    base_unit_id: '',
});

function openQuickBrandModal() {
    quickBrandCreateForm.reset();
    quickBrandCreateForm.clearErrors();
    brandOpen.value = true;
}

function openQuickUnitModal() {
    quickUnitCreateForm.reset();
    quickUnitCreateForm.clearErrors();
    unitOpen.value = true;
}

function transformQuickUnitPayload(data: {
    name: string;
    short_name: string;
    allow_decimal: boolean;
    is_multiple_of_base: boolean;
    base_unit_multiplier: string;
    base_unit_id: string;
}) {
    return {
        name: data.name,
        short_name: data.short_name,
        allow_decimal: data.allow_decimal === true,
        is_multiple_of_base: data.is_multiple_of_base === true,
        base_unit_multiplier:
            data.is_multiple_of_base === true &&
            data.base_unit_multiplier !== ''
                ? data.base_unit_multiplier
                : null,
        base_unit_id:
            data.is_multiple_of_base === true && data.base_unit_id !== ''
                ? Number(data.base_unit_id)
                : null,
    };
}

async function submitQuickBrand() {
    if (!quickBrandCreateForm.name.trim()) {
        return;
    }

    quickSaving.value = true;

    try {
        const r = await fetch(brandRoutes.quickStore.url(teamSlug.value), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': xsrfToken(),
            },
            body: JSON.stringify({
                name: quickBrandCreateForm.name.trim(),
                description:
                    quickBrandCreateForm.description?.trim() || null,
                user_for_repair:
                    quickBrandCreateForm.user_for_repair === true,
            }),
        });
        const b = (await r.json()) as {
            id: number;
            name: string;
            description: string | null;
            user_for_repair: boolean;
            created_at: string | null;
        };
        const row: BrandRow = {
            id: b.id,
            name: b.name,
            description: b.description,
            user_for_repair: b.user_for_repair,
            created_at: b.created_at,
        };
        extraBrands.value = [...extraBrands.value, row];
        form.brand_id = String(b.id);
        brandOpen.value = false;
        quickBrandCreateForm.reset();
    } finally {
        quickSaving.value = false;
    }
}

async function submitQuickUnit() {
    if (
        !quickUnitCreateForm.name.trim() ||
        !quickUnitCreateForm.short_name.trim()
    ) {
        return;
    }

    quickSaving.value = true;

    try {
        const r = await fetch(unitRoutes.quickStore.url(teamSlug.value), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': xsrfToken(),
            },
            body: JSON.stringify(
                transformQuickUnitPayload({
                    name: quickUnitCreateForm.name,
                    short_name: quickUnitCreateForm.short_name,
                    allow_decimal: quickUnitCreateForm.allow_decimal,
                    is_multiple_of_base:
                        quickUnitCreateForm.is_multiple_of_base,
                    base_unit_multiplier:
                        quickUnitCreateForm.base_unit_multiplier,
                    base_unit_id: quickUnitCreateForm.base_unit_id,
                }),
            ),
        });
        const u = (await r.json()) as {
            id: number;
            name: string;
            short_name: string;
            allow_decimal: boolean;
            is_multiple_of_base: boolean;
            base_unit_multiplier: string | null;
            created_at: string | null;
        };
        const row: UnitRow = {
            id: u.id,
            name: u.name,
            short_name: u.short_name,
            allow_decimal: u.allow_decimal,
            is_multiple_of_base: u.is_multiple_of_base,
            base_unit_multiplier: u.base_unit_multiplier,
            created_at: u.created_at,
        };
        extraUnits.value = [...extraUnits.value, row];
        form.unit_id = String(u.id);
        unitOpen.value = false;
        quickUnitCreateForm.reset();
    } finally {
        quickSaving.value = false;
    }
}

function onImage(e: Event) {
    const f = (e.target as HTMLInputElement).files?.[0];
    form.product_image = f ?? null;
}

function onBrochure(e: Event) {
    const f = (e.target as HTMLInputElement).files?.[0];
    form.product_brochure = f ?? null;
}

function submit() {
    form.category_id = categoryId.value;
    form.subcategory_id = subcategoryId.value || '';

    form.transform((data) => {
        const out: Record<string, unknown> = { ...data };
        out.business_location_ids = JSON.stringify(
            selectedLocations.value.map((id) => Number(id)),
        );
        out.combo_lines = JSON.stringify(data.combo_lines ?? []);

        if (data.product_type === 'variation') {
            out.variation_matrix = JSON.stringify(buildVariationMatrix());
        } else {
            out.variation_matrix = JSON.stringify([]);
        }

        if (
            data.manage_stock === true &&
            selectedLocationRows.value.length > 0
        ) {
            out.opening_stocks = JSON.stringify(
                selectedLocationRows.value.map((l) => ({
                    business_location_id: l.id,
                    quantity:
                        Number(
                            openingStockByLocationId[String(l.id)] ?? '',
                        ) || 0,
                })),
            );
        } else {
            out.opening_stocks = JSON.stringify([]);
        }

        out.unit_id = data.unit_id === '' ? '' : data.unit_id;
        out.brand_id = data.brand_id === '' ? '' : data.brand_id;
        out.category_id = categoryId.value === '' ? '' : categoryId.value;
        out.subcategory_id =
            subcategoryId.value === NONE ? '' : subcategoryId.value;

        if (!data.product_image) {
            delete out.product_image;
        }

        if (!data.product_brochure) {
            delete out.product_brochure;
        }

        return out as typeof data;
    }).post(productRoutes.store.url(teamSlug.value), { forceFormData: true });
}
</script>

<template>
    <Head title="Add product" />

    <div class="mx-auto flex max-w-5xl flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Add product
                </h1>
                <p class="text-sm text-muted-foreground">
                    Single, variation, or combo pricing; rich description; files.
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link :href="productRoutes.index.url(teamSlug)">Back to list</Link>
            </Button>
        </div>

        <Form
            class="flex flex-col gap-8 rounded-xl border border-border bg-card p-6 shadow-sm"
            @submit.prevent="submit"
        >
            <section class="grid gap-4 md:grid-cols-2">
                <div class="grid gap-2 md:col-span-2">
                    <Label for="p-name">Product name *</Label>
                    <Input id="p-name" v-model="form.name" required />
                </div>
                <div class="grid gap-2">
                    <Label for="p-sku">SKU</Label>
                    <Input id="p-sku" v-model="form.sku" />
                </div>
                <div class="grid gap-2">
                    <Label>Barcode type</Label>
                    <Select v-model="form.barcode_type">
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="b in barcodeTypes"
                                :key="b.value"
                                :value="b.value"
                            >
                                {{ b.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <div class="flex items-end gap-2">
                        <div class="grid flex-1 gap-2">
                            <Label>Unit</Label>
                            <Select v-model="form.unit_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select unit" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="u in unitsList"
                                        :key="u.id"
                                        :value="String(u.id)"
                                    >
                                        {{ u.name }} ({{ u.short_name }})
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            class="shrink-0"
                            @click="openQuickUnitModal"
                        >
                            <Plus class="size-4" />
                        </Button>
                    </div>
                </div>
                <div class="grid gap-2">
                    <div class="flex items-end gap-2">
                        <div class="grid flex-1 gap-2">
                            <Label>Brand</Label>
                            <Select v-model="form.brand_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select brand" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="b in brandsList"
                                        :key="b.id"
                                        :value="String(b.id)"
                                    >
                                        {{ b.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            class="shrink-0"
                            @click="openQuickBrandModal"
                        >
                            <Plus class="size-4" />
                        </Button>
                    </div>
                </div>
                <div class="grid gap-2">
                    <Label>Category</Label>
                    <Select v-model="categoryId">
                        <SelectTrigger>
                            <SelectValue placeholder="Select category" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="c in parentCategories"
                                :key="c.id"
                                :value="String(c.id)"
                            >
                                {{ c.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label>Subcategory (optional)</Label>
                    <Select v-model="subcategoryId" :disabled="!subcategories.length">
                        <SelectTrigger>
                            <SelectValue placeholder="None" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="NONE">None</SelectItem>
                            <SelectItem
                                v-for="c in subcategories"
                                :key="c.id"
                                :value="String(c.id)"
                            >
                                {{ c.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </section>

            <section class="grid gap-2">
                <Label>Business locations</Label>
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            type="button"
                            variant="outline"
                            class="h-10 w-full justify-between font-normal"
                        >
                            <span class="truncate text-left">{{
                                locationsTriggerLabel
                            }}</span>
                            <ChevronDown class="size-4 shrink-0 opacity-60" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent class="w-[var(--radix-dropdown-menu-trigger-width)] max-h-64 overflow-y-auto" align="start">
                        <DropdownMenuLabel class="font-normal text-muted-foreground">
                            Select one or more
                        </DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuCheckboxItem
                            v-for="loc in businessLocations"
                            :key="loc.id"
                            class="cursor-pointer"
                            :model-value="selectedLocations.includes(String(loc.id))"
                            @update:model-value="
                                (v) => toggleLocation(loc.id, v === true)
                            "
                            @select.prevent
                        >
                            {{ loc.name }}
                        </DropdownMenuCheckboxItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </section>

            <section class="grid gap-4 md:grid-cols-2">
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="p-stock"
                        :model-value="form.manage_stock"
                        @update:model-value="
                            (v) => (form.manage_stock = v === true)
                        "
                    />
                    <Label for="p-stock" class="font-normal">Manage stock</Label>
                </div>
                <div
                    v-if="form.manage_stock"
                    class="grid gap-2"
                >
                    <Label for="p-alert">Alert quantity *</Label>
                    <Input
                        id="p-alert"
                        v-model="form.alert_quantity"
                        inputmode="decimal"
                    />
                </div>
                <div
                    v-if="form.manage_stock && selectedLocationRows.length"
                    class="grid gap-3 md:col-span-2"
                >
                    <Label class="text-sm font-medium">
                        Opening stock by location
                    </Label>
                    <p class="text-xs text-muted-foreground">
                        Optional starting quantity for each selected location
                        (zero is fine).
                    </p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div
                            v-for="loc in selectedLocationRows"
                            :key="loc.id"
                            class="grid gap-1"
                        >
                            <Label
                                class="text-xs font-normal text-muted-foreground"
                                :for="`open-stock-${loc.id}`"
                            >
                                {{ loc.name }}
                            </Label>
                            <Input
                                :id="`open-stock-${loc.id}`"
                                v-model="openingStockByLocationId[String(loc.id)]"
                                inputmode="decimal"
                                placeholder="0"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="p-imei"
                        :model-value="form.enable_imei_serial"
                        @update:model-value="
                            (v) => (form.enable_imei_serial = v === true)
                        "
                    />
                    <Label for="p-imei" class="font-normal">
                        Enable IMEI / serial on description
                    </Label>
                </div>
                <div class="flex items-center gap-2">
                    <Checkbox
                        id="p-nfs"
                        :model-value="form.not_for_selling"
                        @update:model-value="
                            (v) => (form.not_for_selling = v === true)
                        "
                    />
                    <Label for="p-nfs" class="font-normal">Not for selling</Label>
                </div>
                <div class="grid gap-2">
                    <Label for="p-weight">Weight</Label>
                    <Input
                        id="p-weight"
                        v-model="form.weight"
                        inputmode="decimal"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="p-prep">Preparation time (minutes)</Label>
                    <Input
                        id="p-prep"
                        v-model="form.preparation_time_minutes"
                        inputmode="numeric"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>Application tax</Label>
                    <Select v-model="form.application_tax">
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="t in taxOptions"
                                :key="t.id"
                                :value="t.id"
                            >
                                {{ t.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label>Selling price tax type *</Label>
                    <Select v-model="form.selling_price_tax_type">
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="inclusive">Inclusive</SelectItem>
                            <SelectItem value="exclusive">Exclusive</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2 md:col-span-2">
                    <Label>Product type *</Label>
                    <Select v-model="form.product_type">
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="single">Single</SelectItem>
                            <SelectItem value="variation">Variation</SelectItem>
                            <SelectItem value="combo">Combo</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </section>

            <section class="grid gap-2">
                <Label>Product description</Label>
                <RichTextEditor v-model="form.description" />
            </section>

            <section class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="p-img">Product image</Label>
                    <Input
                        id="p-img"
                        type="file"
                        accept="image/*"
                        @change="onImage"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="p-bro">Product brochure</Label>
                    <Input
                        id="p-bro"
                        type="file"
                        accept=".pdf,image/*"
                        @change="onBrochure"
                    />
                </div>
            </section>

            <section
                v-if="form.product_type === 'single'"
                class="overflow-x-auto rounded-md border"
            >
                <table class="w-full min-w-[640px] border-collapse text-sm">
                    <thead>
                        <tr class="border-b bg-muted/40">
                            <th class="px-3 py-2 text-left">Default purchase</th>
                            <th class="px-3 py-2 text-left">× Margin (%)</th>
                            <th class="px-3 py-2 text-left">Default selling</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-3 py-2 align-top">
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <div>
                                        <Label class="text-xs">Exc. tax *</Label>
                                        <Input
                                            v-model="form.single_dpp"
                                            inputmode="decimal"
                                        />
                                    </div>
                                    <div>
                                        <Label class="text-xs">Inc. tax *</Label>
                                        <Input
                                            v-model="form.single_dpp_inc_tax"
                                            inputmode="decimal"
                                        />
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2 align-top">
                                <Label class="text-xs">&nbsp;</Label>
                                <Input
                                    v-model="form.profit_percent"
                                    inputmode="decimal"
                                />
                            </td>
                            <td class="px-3 py-2 align-top">
                                <Label class="text-xs">Exc. tax *</Label>
                                <Input
                                    v-model="form.single_dsp"
                                    inputmode="decimal"
                                />
                                <Label class="mt-2 block text-xs">Inc. tax</Label>
                                <Input
                                    v-model="form.single_dsp_inc_tax"
                                    inputmode="decimal"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section v-if="form.product_type === 'combo'" class="grid gap-4">
                <div class="grid gap-2">
                    <Label>Add component product</Label>
                    <Input
                        v-model="comboSearch"
                        placeholder="Search name / SKU…"
                    />
                    <ul
                        v-if="comboHits.length"
                        class="max-h-40 overflow-auto rounded-md border text-sm"
                    >
                        <li
                            v-for="h in comboHits"
                            :key="h.id"
                            class="cursor-pointer border-b px-3 py-2 last:border-0 hover:bg-muted/50"
                            @click="pickCombo(h)"
                        >
                            {{ h.text }}
                        </li>
                    </ul>
                </div>
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full min-w-[640px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b bg-muted/40">
                                <th class="px-3 py-2 text-left">Product</th>
                                <th class="px-3 py-2 text-left">Qty</th>
                                <th class="px-3 py-2 text-left">Purchase (exc.)</th>
                                <th class="px-3 py-2 text-left">Line total</th>
                                <th class="w-10 px-2" />
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(row, i) in form.combo_lines"
                                :key="i"
                                class="border-b"
                            >
                                <td class="px-3 py-2">{{ row.product_name }}</td>
                                <td class="px-3 py-2">
                                    <Input
                                        v-model="row.quantity"
                                        class="h-8"
                                        inputmode="decimal"
                                        @update:model-value="recalcComboRow(i)"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <Input
                                        v-model="row.purchase_price_exc_tax"
                                        class="h-8"
                                        inputmode="decimal"
                                        @update:model-value="recalcComboRow(i)"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    {{ row.line_total_exc_tax }}
                                </td>
                                <td class="px-2">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        @click="removeCombo(i)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <Label>× Margin (%)</Label>
                        <Input
                            v-model="form.combo_profit_percent"
                            inputmode="decimal"
                            @update:model-value="recalcComboTotals"
                        />
                    </div>
                    <div>
                        <Label>Default selling price</Label>
                        <Input
                            v-model="form.combo_selling_price"
                            inputmode="decimal"
                        />
                    </div>
                </div>
                <p class="text-xs text-muted-foreground">
                    Net purchase (exc. tax): {{ form.combo_purchase_total_exc_tax }}
                </p>
            </section>

            <section v-if="form.product_type === 'variation'" class="grid gap-4">
                <div class="grid gap-2 sm:grid-cols-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="form.variation_sku_format"
                            type="radio"
                            value="with_out_variation"
                            class="size-4"
                        />
                        SKU-Number (ABC-1, ABC-2)
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="form.variation_sku_format"
                            type="radio"
                            value="with_variation"
                            class="size-4"
                        />
                        SKU + variation letters
                    </label>
                </div>
                <div class="grid gap-2">
                    <Label>Variation template (optional)</Label>
                    <Select v-model="variationTemplateId">
                        <SelectTrigger>
                            <SelectValue placeholder="Select template" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="NONE">None</SelectItem>
                            <SelectItem
                                v-for="vt in variationTemplates"
                                :key="vt.id"
                                :value="String(vt.id)"
                            >
                                {{ vt.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="overflow-x-auto rounded-md border">
                    <table class="w-full min-w-[720px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b bg-muted/40">
                                <th class="px-2 py-2 text-left">SKU</th>
                                <th class="px-2 py-2 text-left">Value *</th>
                                <th class="px-2 py-2 text-left">DPP exc.</th>
                                <th class="px-2 py-2 text-left">DPP inc.</th>
                                <th class="px-2 py-2 text-left">Margin %</th>
                                <th class="px-2 py-2 text-left">DSP exc.</th>
                                <th class="w-10" />
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(r, i) in variationRows"
                                :key="i"
                                class="border-b"
                            >
                                <td class="px-2 py-1">
                                    <Input v-model="r.sub_sku" class="h-8" />
                                </td>
                                <td class="px-2 py-1">
                                    <Input v-model="r.value" class="h-8" required />
                                </td>
                                <td class="px-2 py-1">
                                    <Input v-model="r.dpp" class="h-8" />
                                </td>
                                <td class="px-2 py-1">
                                    <Input v-model="r.dpp_inc_tax" class="h-8" />
                                </td>
                                <td class="px-2 py-1">
                                    <Input v-model="r.profit_percent" class="h-8" />
                                </td>
                                <td class="px-2 py-1">
                                    <Input v-model="r.dsp" class="h-8" />
                                </td>
                                <td class="px-1">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        @click="removeVariationRow(i)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <Button type="button" variant="outline" size="sm" @click="addVariationRow">
                    <Plus class="mr-1 size-4" />
                    Add variation row
                </Button>
            </section>

            <div class="flex flex-wrap gap-3">
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    Save product
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="productRoutes.index.url(teamSlug)">Cancel</Link>
                </Button>
            </div>
        </Form>

        <StandardFormModal
            v-model:open="brandOpen"
            title="Add brand"
            description="Name, description, and repair flag."
            size="lg"
            :visit-on-dismiss="false"
        >
            <Form class="contents" @submit.prevent="submitQuickBrand">
                <BrandForm :form="quickBrandCreateForm" />
            </Form>
            <template #footer>
                <div class="flex w-full flex-wrap justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="brandOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        :disabled="quickSaving"
                        @click="submitQuickBrand"
                    >
                        <Spinner v-if="quickSaving" />
                        Save
                    </Button>
                </div>
            </template>
        </StandardFormModal>

        <StandardFormModal
            v-model:open="unitOpen"
            title="Add unit"
            description="Names, decimals, and optional conversion to a base unit."
            size="xl"
            :visit-on-dismiss="false"
        >
            <Form class="contents" @submit.prevent="submitQuickUnit">
                <UnitForm
                    :form="quickUnitCreateForm"
                    :base-units="props.baseUnits"
                />
            </Form>
            <template #footer>
                <div class="flex w-full flex-wrap justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        @click="unitOpen = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        :disabled="quickSaving"
                        @click="submitQuickUnit"
                    >
                        <Spinner v-if="quickSaving" />
                        Save
                    </Button>
                </div>
            </template>
        </StandardFormModal>
    </div>
</template>
