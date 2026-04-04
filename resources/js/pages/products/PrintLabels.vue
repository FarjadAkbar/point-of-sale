<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import products from '@/routes/products';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import type { Team } from '@/types';

type SellingPriceGroupRef = { id: number; name: string };

type LabelLine = {
    productId: number;
    name: string;
    sku: string | null;
    labelCount: string;
    packingDate: string;
    sellingPriceGroupId: string;
};

type SearchHit = { id: number; name: string; sku: string | null; text: string };

const props = defineProps<{
    sellingPriceGroups: SellingPriceGroupRef[];
}>();

defineOptions({
    layout: (p: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            { title: 'Products', href: products.index.url(p.currentTeam!.slug) },
            { title: 'Print labels', href: products.printLabels.url(p.currentTeam!.slug) },
        ],
    }),
});

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);
const businessName = computed(
    () => (page.props.currentTeam as Team | null)?.name ?? 'Business',
);

const defaultSpg = computed(() =>
    props.sellingPriceGroups[0] ? String(props.sellingPriceGroups[0].id) : '',
);

const lines = ref<LabelLine[]>([]);
const productSearch = ref('');
const searchHits = ref<SearchHit[]>([]);
const showPreview = ref(false);

const printName = ref(true);
const printNameSize = ref('15');
const printVariations = ref(true);
const printVariationsSize = ref('17');
const printPrice = ref(true);
const printPriceSize = ref('17');
const printPriceType = ref<'inclusive' | 'exclusive'>('inclusive');
const printBusinessName = ref(true);
const printBusinessNameSize = ref('20');
const printPackingDate = ref(true);
const printPackingDateSize = ref('12');

const barcodeSetting = ref('1');

const barcodeOptions = [
    {
        value: '1',
        label: '20 labels/sheet — 8.5"×11", label 4"×1"',
    },
    {
        value: '2',
        label: '30 labels/sheet — 8.5"×11", label 2.625"×1"',
    },
    {
        value: '3',
        label: '32 labels/sheet — 8.5"×11", label 2"×1.25"',
    },
    {
        value: '4',
        label: '40 labels/sheet — 8.5"×11", label 2"×1"',
    },
    {
        value: '5',
        label: '50 labels/sheet — 8.5"×11", label 1.5"×1"',
    },
    {
        value: '6',
        label: 'Continuous rolls — 31.75mm×25.4mm, gap 3.18mm',
    },
] as const;

let searchTimer: ReturnType<typeof setTimeout> | undefined;

watch(productSearch, (q) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
        const t = q.trim();
        if (t.length < 1) {
            searchHits.value = [];
            return;
        }
        const url = products.search.url(teamSlug.value, { query: { q: t } });
        const r = await fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        const j = (await r.json()) as { data: SearchHit[] };
        searchHits.value = j.data ?? [];
    }, 300);
});

function todayIsoDate(): string {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function addLine(hit: SearchHit) {
    if (lines.value.some((l) => l.productId === hit.id)) {
        return;
    }
    lines.value.push({
        productId: hit.id,
        name: hit.name,
        sku: hit.sku,
        labelCount: '1',
        packingDate: todayIsoDate(),
        sellingPriceGroupId: defaultSpg.value,
    });
    productSearch.value = '';
    searchHits.value = [];
}

function removeLine(productId: number) {
    lines.value = lines.value.filter((l) => l.productId !== productId);
}

function runPreview() {
    showPreview.value = true;
}

function runPrint() {
    window.print();
}

function spgName(id: string): string {
    const g = props.sellingPriceGroups.find((x) => String(x.id) === id);
    return g?.name ?? '—';
}
</script>

<template>
    <Head title="Print labels" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="no-print">
            <h1 class="text-2xl font-semibold tracking-tight">Print labels</h1>
            <p class="text-sm text-muted-foreground">
                Add products, set quantities and packing dates, choose what
                appears on each label, then preview and print.
            </p>
        </div>

        <Card class="no-print shadow-sm">
            <CardHeader class="border-b border-border pb-4">
                <CardTitle class="text-lg">Add products to generate labels</CardTitle>
            </CardHeader>
            <CardContent class="space-y-6 pt-6">
                <div class="relative mx-auto max-w-2xl">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="productSearch"
                        class="pl-10"
                        type="search"
                        placeholder="Enter product name to add for labels"
                        autocomplete="off"
                    />
                    <div
                        v-if="searchHits.length"
                        class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-border bg-popover shadow-md"
                    >
                        <button
                            v-for="h in searchHits"
                            :key="h.id"
                            type="button"
                            class="block w-full px-3 py-2 text-left text-sm hover:bg-muted"
                            @click="addLine(h)"
                        >
                            {{ h.text }}
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-lg border border-border">
                    <table class="w-full min-w-[640px] border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-border bg-muted/50">
                                <th class="px-3 py-2 text-left font-medium">
                                    Products
                                </th>
                                <th class="px-3 py-2 text-left font-medium">
                                    No. of labels
                                </th>
                                <th class="px-3 py-2 text-left font-medium">
                                    Packing date
                                </th>
                                <th class="px-3 py-2 text-left font-medium">
                                    Selling price group
                                </th>
                                <th
                                    class="w-24 px-3 py-2 text-right font-medium print:hidden"
                                />
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="line in lines"
                                :key="line.productId"
                                class="border-b border-border/80"
                            >
                                <td class="px-3 py-2 align-middle">
                                    <div class="font-medium">{{ line.name }}</div>
                                    <div
                                        v-if="line.sku"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ line.sku }}
                                    </div>
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    <Input
                                        v-model="line.labelCount"
                                        class="h-9 w-24"
                                        type="number"
                                        min="1"
                                        inputmode="numeric"
                                    />
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    <Input
                                        v-model="line.packingDate"
                                        class="h-9 w-40"
                                        type="date"
                                    />
                                </td>
                                <td class="px-3 py-2 align-middle">
                                    <Select v-model="line.sellingPriceGroupId">
                                        <SelectTrigger class="h-9 w-full max-w-[220px]">
                                            <SelectValue placeholder="Group" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem
                                                v-for="g in sellingPriceGroups"
                                                :key="g.id"
                                                :value="String(g.id)"
                                            >
                                                {{ g.name }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </td>
                                <td class="px-3 py-2 text-right align-middle">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive"
                                        @click="removeLine(line.productId)"
                                    >
                                        Remove
                                    </Button>
                                </td>
                            </tr>
                            <tr v-if="!lines.length">
                                <td
                                    colspan="5"
                                    class="px-3 py-10 text-center text-muted-foreground"
                                >
                                    Search and select products above. They will
                                    appear here.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <Card class="no-print shadow-sm">
            <CardHeader class="border-b border-border pb-4">
                <CardTitle class="text-lg">Information to show on labels</CardTitle>
            </CardHeader>
            <CardContent class="space-y-8 pt-6">
                <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-4">
                    <div class="space-y-3 rounded-lg border border-border p-4">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="lbl-name"
                                :checked="printName"
                                @update:checked="
                                    (v) => (printName = v === true)
                                "
                            />
                            <Label for="lbl-name" class="font-semibold">
                                Product name
                            </Label>
                        </div>
                        <div class="flex items-center gap-2">
                            <Label class="w-14 shrink-0 text-muted-foreground">
                                Size
                            </Label>
                            <Input v-model="printNameSize" type="text" />
                        </div>
                    </div>
                    <div class="space-y-3 rounded-lg border border-border p-4">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="lbl-var"
                                :checked="printVariations"
                                @update:checked="
                                    (v) => (printVariations = v === true)
                                "
                            />
                            <Label for="lbl-var" class="font-semibold">
                                Product variation (recommended)
                            </Label>
                        </div>
                        <div class="flex items-center gap-2">
                            <Label class="w-14 shrink-0 text-muted-foreground">
                                Size
                            </Label>
                            <Input v-model="printVariationsSize" type="text" />
                        </div>
                    </div>
                    <div class="space-y-3 rounded-lg border border-border p-4">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="lbl-price"
                                :checked="printPrice"
                                @update:checked="
                                    (v) => (printPrice = v === true)
                                "
                            />
                            <Label for="lbl-price" class="font-semibold">
                                Product price
                            </Label>
                        </div>
                        <div class="flex items-center gap-2">
                            <Label class="w-14 shrink-0 text-muted-foreground">
                                Size
                            </Label>
                            <Input v-model="printPriceSize" type="text" />
                        </div>
                    </div>
                    <div class="space-y-3 rounded-lg border border-border p-4">
                        <Label for="price-type" class="font-medium">
                            Show price
                        </Label>
                        <Select v-model="printPriceType">
                            <SelectTrigger id="price-type" class="h-9">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="inclusive">
                                    Inc. tax
                                </SelectItem>
                                <SelectItem value="exclusive">
                                    Exc. tax
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="space-y-3 rounded-lg border border-border p-4">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="lbl-biz"
                                :checked="printBusinessName"
                                @update:checked="
                                    (v) => (printBusinessName = v === true)
                                "
                            />
                            <Label for="lbl-biz" class="font-semibold">
                                Business name
                            </Label>
                        </div>
                        <div class="flex items-center gap-2">
                            <Label class="w-14 shrink-0 text-muted-foreground">
                                Size
                            </Label>
                            <Input v-model="printBusinessNameSize" type="text" />
                        </div>
                    </div>
                    <div class="space-y-3 rounded-lg border border-border p-4">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="lbl-pack"
                                :checked="printPackingDate"
                                @update:checked="
                                    (v) => (printPackingDate = v === true)
                                "
                            />
                            <Label for="lbl-pack" class="font-semibold">
                                Print packing date
                            </Label>
                        </div>
                        <div class="flex items-center gap-2">
                            <Label class="w-14 shrink-0 text-muted-foreground">
                                Size
                            </Label>
                            <Input
                                v-model="printPackingDateSize"
                                type="text"
                            />
                        </div>
                    </div>
                </div>

                <div class="border-t border-border pt-6">
                    <Label for="barcode" class="mb-2 block font-medium">
                        Barcode / sheet layout
                    </Label>
                    <Select v-model="barcodeSetting">
                        <SelectTrigger id="barcode" class="h-10 max-w-3xl">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent class="max-w-xl">
                            <SelectItem
                                v-for="opt in barcodeOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="flex justify-center pt-2">
                    <Button type="button" size="lg" @click="runPreview">
                        Preview
                    </Button>
                </div>
            </CardContent>
        </Card>

        <div v-if="showPreview" class="no-print rounded-xl border border-dashed border-border p-6">
            <h2 class="mb-2 text-lg font-semibold">Preview</h2>
            <p class="mb-4 text-sm text-muted-foreground">
                Sheet layout option #{{ barcodeSetting }} applies when you
                export to PDF from the print dialog; on-screen grid is
                approximate.
            </p>
            <Button type="button" class="mb-6" @click="runPrint">
                Print
            </Button>
        </div>

        <section
            v-if="showPreview"
            aria-label="Label preview"
            class="label-sheet rounded-xl border border-border bg-card p-4 print:border-0 print:bg-white print:p-2"
        >
            <div
                class="grid gap-4"
                style="
                    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                "
            >
                <template v-for="line in lines" :key="line.productId">
                    <div
                        v-for="n in Math.min(
                            200,
                            Math.max(1, Number(line.labelCount) || 1),
                        )"
                        :key="`${line.productId}-${n}`"
                        class="flex min-h-[120px] flex-col border border-border p-3 text-foreground print:border-black print:text-black"
                    >
                        <div
                            v-if="printName"
                            class="font-semibold leading-tight"
                            :style="{ fontSize: `${printNameSize}px` }"
                        >
                            {{ line.name }}
                        </div>
                        <div
                            v-if="printVariations"
                            class="mt-1 text-muted-foreground print:text-neutral-700"
                            :style="{ fontSize: `${printVariationsSize}px` }"
                        >
                            —
                        </div>
                        <div
                            v-if="printPrice"
                            class="mt-auto pt-2 font-medium"
                            :style="{ fontSize: `${printPriceSize}px` }"
                        >
                            {{ printPriceType === 'inclusive' ? 'Inc.' : 'Exc.' }}
                            tax — {{ spgName(line.sellingPriceGroupId) }}
                        </div>
                        <div
                            v-if="printBusinessName"
                            class="mt-1 truncate"
                            :style="{ fontSize: `${printBusinessNameSize}px` }"
                        >
                            {{ businessName }}
                        </div>
                        <div
                            v-if="printPackingDate"
                            class="mt-1"
                            :style="{ fontSize: `${printPackingDateSize}px` }"
                        >
                            {{ line.packingDate }}
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </div>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
}
</style>
