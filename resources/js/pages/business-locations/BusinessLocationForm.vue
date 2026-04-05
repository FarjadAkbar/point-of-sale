<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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
import productRoutes from '@/routes/products';
import type { Team } from '@/types';

const NONE = '__none__';

export type BusinessLocationFormData = {
    name: string;
    location_id: string;
    landmark: string;
    city: string;
    zip_code: string;
    state: string;
    country: string;
    mobile: string;
    alternate_contact_number: string;
    email: string;
    website: string;
    default_selling_price_group_id: string;
    featured_product_ids: number[];
};

const props = defineProps<{
    form: InertiaForm<BusinessLocationFormData>;
    sellingPriceGroups: { id: number; name: string }[];
    initialFeaturedProducts: { id: number; name: string; sku: string | null }[];
}>();

const page = usePage();
const teamSlug = computed(
    () => (page.props.currentTeam as Team | null)?.slug ?? '',
);

const selectedProducts = ref<
    { id: number; name: string; sku: string | null }[]
>([]);

function syncFormIds() {
    props.form.featured_product_ids = selectedProducts.value.map((p) => p.id);
}

watch(
    () => props.initialFeaturedProducts,
    (v) => {
        selectedProducts.value = v.map((p) => ({ ...p }));
        syncFormIds();
    },
    { immediate: true, deep: true },
);

const productSearch = ref('');
const productHits = ref<
    { id: number; name: string; sku: string | null; text: string }[]
>([]);
let searchTimer: ReturnType<typeof setTimeout> | undefined;

watch(productSearch, (q) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
        const t = q.trim();

        if (t.length < 1) {
            productHits.value = [];

            return;
        }

        const url = productRoutes.search.url(teamSlug.value, {
            query: { q: t, active_only: '1' },
        });
        const r = await fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        const j = (await r.json()) as {
            data: {
                id: number;
                name: string;
                sku: string | null;
                text: string;
            }[];
        };
        productHits.value = j.data ?? [];
    }, 300);
});

function addFeaturedProduct(p: {
    id: number;
    name: string;
    sku: string | null;
}) {
    if (selectedProducts.value.some((x) => x.id === p.id)) {
        productSearch.value = '';
        productHits.value = [];

        return;
    }

    selectedProducts.value = [
        ...selectedProducts.value,
        { id: p.id, name: p.name, sku: p.sku },
    ];
    syncFormIds();
    productSearch.value = '';
    productHits.value = [];
}

function removeFeaturedProduct(id: number) {
    selectedProducts.value = selectedProducts.value.filter((p) => p.id !== id);
    syncFormIds();
}
</script>

<template>
    <div class="grid max-w-4xl gap-6 md:grid-cols-2">
        <div class="grid gap-2 md:col-span-2">
            <Label for="bl-name">Name *</Label>
            <Input
                id="bl-name"
                v-model="form.name"
                required
                placeholder="Location name"
            />
        </div>
        <div class="grid gap-2">
            <Label for="bl-location-id">Location ID</Label>
            <Input
                id="bl-location-id"
                v-model="form.location_id"
                placeholder="External or internal ID"
            />
        </div>
        <div class="grid gap-2">
            <Label for="bl-landmark">Landmark</Label>
            <Input id="bl-landmark" v-model="form.landmark" placeholder="" />
        </div>
        <div class="grid gap-2">
            <Label for="bl-city">City</Label>
            <Input id="bl-city" v-model="form.city" placeholder="" />
        </div>
        <div class="grid gap-2">
            <Label for="bl-zip">Zip code</Label>
            <Input id="bl-zip" v-model="form.zip_code" placeholder="" />
        </div>
        <div class="grid gap-2">
            <Label for="bl-state">State</Label>
            <Input id="bl-state" v-model="form.state" placeholder="" />
        </div>
        <div class="grid gap-2">
            <Label for="bl-country">Country</Label>
            <Input id="bl-country" v-model="form.country" placeholder="" />
        </div>
        <div class="grid gap-2">
            <Label for="bl-mobile">Mobile</Label>
            <Input
                id="bl-mobile"
                v-model="form.mobile"
                type="tel"
                placeholder=""
            />
        </div>
        <div class="grid gap-2">
            <Label for="bl-alt">Alternate contact number</Label>
            <Input
                id="bl-alt"
                v-model="form.alternate_contact_number"
                type="tel"
                placeholder=""
            />
        </div>
        <div class="grid gap-2">
            <Label for="bl-email">Email</Label>
            <Input
                id="bl-email"
                v-model="form.email"
                type="email"
                placeholder=""
            />
        </div>
        <div class="grid gap-2 md:col-span-2">
            <Label for="bl-website">Website</Label>
            <Input
                id="bl-website"
                v-model="form.website"
                type="url"
                placeholder="https://"
            />
        </div>
        <div class="grid gap-2 md:col-span-2">
            <Label>Default selling price group</Label>
            <Select v-model="form.default_selling_price_group_id">
                <SelectTrigger class="w-full">
                    <SelectValue placeholder="None" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="NONE">None</SelectItem>
                    <SelectItem
                        v-for="g in sellingPriceGroups"
                        :key="g.id"
                        :value="String(g.id)"
                    >
                        {{ g.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
        <div class="grid gap-2 md:col-span-2">
            <Label for="bl-feat-search">POS featured products</Label>
            <p class="text-muted-foreground text-xs">
                Search sellable products and add multiple. Shown on the POS for
                this location.
            </p>
            <div class="relative">
                <Input
                    id="bl-feat-search"
                    v-model="productSearch"
                    placeholder="Search by name or SKU…"
                    autocomplete="off"
                />
                <div
                    v-if="productHits.length > 0"
                    class="bg-popover text-popover-foreground absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded-md border border-border shadow-md"
                >
                    <button
                        v-for="h in productHits"
                        :key="h.id"
                        type="button"
                        class="hover:bg-muted block w-full px-3 py-2 text-left text-sm"
                        @click="addFeaturedProduct(h)"
                    >
                        {{ h.text }}
                    </button>
                </div>
            </div>
            <div
                v-if="selectedProducts.length > 0"
                class="flex flex-wrap gap-2 pt-1"
            >
                <div
                    v-for="p in selectedProducts"
                    :key="p.id"
                    class="bg-muted flex items-center gap-1 rounded-md border border-border px-2 py-1 text-sm"
                >
                    <span class="max-w-[200px] truncate">{{ p.name }}</span>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        class="size-6 shrink-0"
                        :aria-label="`Remove ${p.name}`"
                        @click="removeFeaturedProduct(p.id)"
                    >
                        <X class="size-3.5" />
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
