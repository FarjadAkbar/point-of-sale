<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Award,
    BarChart3,
    Barcode,
    Building2,
    ChevronRight,
    CircleDollarSign,
    Contact,
    FolderTree,
    GitBranch,
    Landmark,
    Home,
    LayoutList,
    Package,
    Percent,
    Printer as PrinterIcon,
    Receipt,
    Ruler,
    Settings,
    Shield,
    ShoppingCart,
    SlidersHorizontal,
    SquarePlus,
    Store,
    Tags,
    Truck,
    UserCircle,
    Users,
    Wallet,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavUser from '@/components/NavUser.vue';
import TeamSwitcher from '@/components/TeamSwitcher.vue';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import barcodeSettingsRoutes from '@/routes/barcode-settings';
import brands from '@/routes/brands';
import customerGroups from '@/routes/customer-groups';
import customers from '@/routes/customers';
import productCategories from '@/routes/product-categories';
import products from '@/routes/products';
import receiptPrinterRoutes from '@/routes/receipt-printer';
import salesCommissionAgentRoutes from '@/routes/sales-commission-agents';
import sellingPriceGroups from '@/routes/selling-price-groups';
import suppliers from '@/routes/suppliers';
import taxesPageRoutes from '@/routes/taxes';
import units from '@/routes/units';
import variationTemplates from '@/routes/variation-templates';
import warranties from '@/routes/warranties';

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();

const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
);

const suppliersUrl = computed(() =>
    page.props.currentTeam
        ? suppliers.index.url(page.props.currentTeam.slug)
        : '/',
);

const customersUrl = computed(() =>
    page.props.currentTeam
        ? customers.index.url(page.props.currentTeam.slug)
        : '/',
);

const customerGroupsUrl = computed(() =>
    page.props.currentTeam
        ? customerGroups.index.url(page.props.currentTeam.slug)
        : '/',
);

const warrantiesUrl = computed(() =>
    page.props.currentTeam
        ? warranties.index.url(page.props.currentTeam.slug)
        : '/',
);

const brandsUrl = computed(() =>
    page.props.currentTeam
        ? brands.index.url(page.props.currentTeam.slug)
        : '/',
);

const productCategoriesUrl = computed(() =>
    page.props.currentTeam
        ? productCategories.index.url(page.props.currentTeam.slug)
        : '/',
);

const unitsUrl = computed(() =>
    page.props.currentTeam
        ? units.index.url(page.props.currentTeam.slug)
        : '/',
);

const sellingPriceGroupsUrl = computed(() =>
    page.props.currentTeam
        ? sellingPriceGroups.index.url(page.props.currentTeam.slug)
        : '/',
);

const variationTemplatesUrl = computed(() =>
    page.props.currentTeam
        ? variationTemplates.index.url(page.props.currentTeam.slug)
        : '/',
);

const productsListUrl = computed(() =>
    page.props.currentTeam
        ? products.index.url(page.props.currentTeam.slug)
        : '/',
);

const productsAddUrl = computed(() =>
    page.props.currentTeam
        ? products.create.url(page.props.currentTeam.slug)
        : '/',
);

const productsPrintLabelsUrl = computed(() =>
    page.props.currentTeam
        ? products.printLabels.url(page.props.currentTeam.slug)
        : '/',
);

const salesCommissionAgentsUrl = computed(() =>
    page.props.currentTeam
        ? salesCommissionAgentRoutes.index.url(page.props.currentTeam.slug)
        : '/',
);

const taxesUrl = computed(() =>
    page.props.currentTeam
        ? taxesPageRoutes.index.url(page.props.currentTeam.slug)
        : '/',
);

const receiptPrinterUrl = computed(() =>
    page.props.currentTeam
        ? receiptPrinterRoutes.edit.url(page.props.currentTeam.slug)
        : '/',
);

const barcodeSettingsUrl = computed(() =>
    page.props.currentTeam
        ? barcodeSettingsRoutes.edit.url(page.props.currentTeam.slug)
        : '/',
);

const contactsOpen = ref(false);
const userManagementOpen = ref(false);
const settingsOpen = ref(false);
const productsOpen = ref(false);

watch(
    () =>
        [
            page.url,
            suppliersUrl.value,
            customersUrl.value,
            customerGroupsUrl.value,
        ] as const,
    () => {
        contactsOpen.value =
            isCurrentUrl(suppliersUrl.value) ||
            isCurrentUrl(customersUrl.value) ||
            isCurrentUrl(customerGroupsUrl.value);
    },
    { immediate: true },
);

watch(
    () =>
        [
            page.url,
            sellingPriceGroupsUrl.value,
            variationTemplatesUrl.value,
            productsListUrl.value,
            productsAddUrl.value,
            productsPrintLabelsUrl.value,
            warrantiesUrl.value,
            brandsUrl.value,
            productCategoriesUrl.value,
            unitsUrl.value,
        ] as const,
    () => {
        productsOpen.value =
            isCurrentUrl(sellingPriceGroupsUrl.value) ||
            isCurrentUrl(variationTemplatesUrl.value) ||
            isCurrentUrl(productsListUrl.value) ||
            isCurrentUrl(productsAddUrl.value) ||
            isCurrentUrl(productsPrintLabelsUrl.value) ||
            isCurrentUrl(warrantiesUrl.value) ||
            isCurrentUrl(brandsUrl.value) ||
            isCurrentUrl(productCategoriesUrl.value) ||
            isCurrentUrl(unitsUrl.value);
    },
    { immediate: true },
);

watch(
    () => [page.url, salesCommissionAgentsUrl.value] as const,
    () => {
        userManagementOpen.value = isCurrentUrl(salesCommissionAgentsUrl.value);
    },
    { immediate: true },
);

watch(
    () =>
        [
            page.url,
            taxesUrl.value,
            receiptPrinterUrl.value,
            barcodeSettingsUrl.value,
        ] as const,
    () => {
        settingsOpen.value =
            isCurrentUrl(taxesUrl.value) ||
            isCurrentUrl(receiptPrinterUrl.value) ||
            isCurrentUrl(barcodeSettingsUrl.value);
    },
    { immediate: true },
);

const collapsibleNav = [
    { title: 'Purchases', icon: ShoppingCart },
    { title: 'Sell', icon: Store },
    { title: 'Stock Transfers', icon: Truck },
    { title: 'Stock Adjustment', icon: SlidersHorizontal },
    { title: 'Expenses', icon: Receipt },
    { title: 'Payment Accounts', icon: Wallet },
    { title: 'Reports', icon: BarChart3 },
] as const;
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboardUrl">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <SidebarMenu>
                <SidebarMenuItem>
                    <TeamSwitcher />
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Menu</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            as-child
                            :is-active="isCurrentUrl(dashboardUrl)"
                            tooltip="Home"
                        >
                            <Link :href="dashboardUrl">
                                <Home />
                                <span>Home</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>

                    <Collapsible v-model:open="contactsOpen" class="group/collapsible">
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton
                                    :is-active="
                                        isCurrentUrl(suppliersUrl) ||
                                        isCurrentUrl(customersUrl) ||
                                        isCurrentUrl(customerGroupsUrl)
                                    "
                                    tooltip="Contacts"
                                >
                                    <Contact />
                                    <span>Contacts</span>
                                    <ChevronRight
                                        class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                    />
                                </SidebarMenuButton>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <SidebarMenuSub>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(suppliersUrl)
                                            "
                                        >
                                            <Link :href="suppliersUrl">
                                                <Building2 />
                                                <span>Suppliers</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(customersUrl)
                                            "
                                        >
                                            <Link :href="customersUrl">
                                                <UserCircle />
                                                <span>Customers</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(customerGroupsUrl)
                                            "
                                        >
                                            <Link :href="customerGroupsUrl">
                                                <Tags />
                                                <span>Customer groups</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>

                    <Collapsible v-model:open="productsOpen" class="group/collapsible">
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton
                                    :is-active="
                                        isCurrentUrl(sellingPriceGroupsUrl) ||
                                        isCurrentUrl(variationTemplatesUrl) ||
                                        isCurrentUrl(productsListUrl) ||
                                        isCurrentUrl(productsAddUrl) ||
                                        isCurrentUrl(productsPrintLabelsUrl) ||
                                        isCurrentUrl(warrantiesUrl) ||
                                        isCurrentUrl(brandsUrl) ||
                                        isCurrentUrl(productCategoriesUrl) ||
                                        isCurrentUrl(unitsUrl)
                                    "
                                    tooltip="Products"
                                >
                                    <Package />
                                    <span>Products</span>
                                    <ChevronRight
                                        class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                    />
                                </SidebarMenuButton>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <SidebarMenuSub>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(productsListUrl)
                                            "
                                        >
                                            <Link :href="productsListUrl">
                                                <LayoutList />
                                                <span>List product</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(productsAddUrl)
                                            "
                                        >
                                            <Link :href="productsAddUrl">
                                                <SquarePlus />
                                                <span>Add product</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(productsPrintLabelsUrl)
                                            "
                                        >
                                            <Link :href="productsPrintLabelsUrl">
                                                <PrinterIcon />
                                                <span>Print labels</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(
                                                    sellingPriceGroupsUrl,
                                                )
                                            "
                                        >
                                            <Link :href="sellingPriceGroupsUrl">
                                                <CircleDollarSign />
                                                <span
                                                    >Selling price group</span
                                                >
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(
                                                    variationTemplatesUrl,
                                                )
                                            "
                                        >
                                            <Link :href="variationTemplatesUrl">
                                                <GitBranch />
                                                <span>Variations</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(warrantiesUrl)
                                            "
                                        >
                                            <Link :href="warrantiesUrl">
                                                <Shield />
                                                <span>Warranties</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(brandsUrl)
                                            "
                                        >
                                            <Link :href="brandsUrl">
                                                <Award />
                                                <span>Brands</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(productCategoriesUrl)
                                            "
                                        >
                                            <Link :href="productCategoriesUrl">
                                                <FolderTree />
                                                <span>Categories</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="isCurrentUrl(unitsUrl)"
                                        >
                                            <Link :href="unitsUrl">
                                                <Ruler />
                                                <span>Units</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>

                    <Collapsible
                        v-model:open="userManagementOpen"
                        class="group/collapsible"
                    >
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton
                                    :is-active="
                                        isCurrentUrl(salesCommissionAgentsUrl)
                                    "
                                    tooltip="User management"
                                >
                                    <Users />
                                    <span>User Management</span>
                                    <ChevronRight
                                        class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                    />
                                </SidebarMenuButton>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <SidebarMenuSub>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(
                                                    salesCommissionAgentsUrl,
                                                )
                                            "
                                        >
                                            <Link :href="salesCommissionAgentsUrl">
                                                <Percent />
                                                <span
                                                    >Sales commission agents</span
                                                >
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>

                    <Collapsible v-model:open="settingsOpen" class="group/collapsible">
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton
                                    :is-active="
                                        isCurrentUrl(taxesUrl) ||
                                        isCurrentUrl(receiptPrinterUrl) ||
                                        isCurrentUrl(barcodeSettingsUrl)
                                    "
                                    tooltip="Settings"
                                >
                                    <Settings />
                                    <span>Settings</span>
                                    <ChevronRight
                                        class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                    />
                                </SidebarMenuButton>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <SidebarMenuSub>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="isCurrentUrl(taxesUrl)"
                                        >
                                            <Link :href="taxesUrl">
                                                <Landmark />
                                                <span>Taxes</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(receiptPrinterUrl)
                                            "
                                        >
                                            <Link :href="receiptPrinterUrl">
                                                <PrinterIcon />
                                                <span>Receipt printer</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(barcodeSettingsUrl)
                                            "
                                        >
                                            <Link :href="barcodeSettingsUrl">
                                                <Barcode />
                                                <span>Barcode settings</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>

                    <Collapsible
                        v-for="item in collapsibleNav"
                        :key="item.title"
                        class="group/collapsible"
                    >
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton>
                                    <component :is="item.icon" />
                                    <span>{{ item.title }}</span>
                                    <ChevronRight
                                        class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                    />
                                </SidebarMenuButton>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <SidebarMenuSub>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton as-child size="sm">
                                            <Link :href="dashboardUrl">
                                                <span>Overview</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
