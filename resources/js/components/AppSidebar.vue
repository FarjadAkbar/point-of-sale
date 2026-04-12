<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Award,
    ArrowLeftRight,
    BarChart3,
    FileText,
    Barcode,
    Building2,
    ChevronRight,
    ClipboardList,
    CircleDollarSign,
    Contact,
    FolderTree,
    GitBranch,
    Landmark,
    Home,
    LayoutList,
    ListOrdered,
    MapPin,
    Package,
    Percent,
    Printer as PrinterIcon,
    Receipt,
    Ruler,
    ScanLine,
    Settings,
    Shield,
    ShoppingCart,
    SlidersHorizontal,
    SquarePlus,
    Store,
    Undo2,
    Tags,
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
import businessLocations from '@/routes/business-locations';
import brands from '@/routes/brands';
import customerGroups from '@/routes/customer-groups';
import customers from '@/routes/customers';
import expenseCategories from '@/routes/expense-categories';
import expenses from '@/routes/expenses';
import paymentAccounts from '@/routes/payment-accounts';
import paymentSettingsRoutes from '@/routes/payment-settings';
import posRoutes from '@/routes/pos';
import productCategories from '@/routes/product-categories';
import products from '@/routes/products';
import purchaseReturnRoutes from '@/routes/purchase-returns';
import purchases from '@/routes/purchases';
import reportRoutes from '@/routes/reports';
import receiptPrinterRoutes from '@/routes/receipt-printer';
import salesCommissionAgentRoutes from '@/routes/sales-commission-agents';
import salesDraftRoutes from '@/routes/sales/drafts';
import salesQuotationsRoutes from '@/routes/sales/quotations';
import salesRoutes from '@/routes/sales';
import sellingPriceGroups from '@/routes/selling-price-groups';
import stockAdjustments from '@/routes/stock-adjustments';
import stockTransfers from '@/routes/stock-transfers';
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

const businessLocationsUrl = computed(() =>
    page.props.currentTeam
        ? businessLocations.index.url(page.props.currentTeam.slug)
        : '/',
);

const paymentSettingsUrl = computed(() =>
    page.props.currentTeam
        ? paymentSettingsRoutes.edit.url(page.props.currentTeam.slug)
        : '/',
);

const paymentAccountsListUrl = computed(() =>
    page.props.currentTeam
        ? paymentAccounts.index.url(page.props.currentTeam.slug)
        : '/',
);

const purchasesListUrl = computed(() =>
    page.props.currentTeam
        ? purchases.index.url(page.props.currentTeam.slug)
        : '/',
);

const purchasesCreateUrl = computed(() =>
    page.props.currentTeam
        ? purchases.create.url(page.props.currentTeam.slug)
        : '/',
);

const purchaseReturnsListUrl = computed(() =>
    page.props.currentTeam
        ? purchaseReturnRoutes.index.url(page.props.currentTeam.slug)
        : '/',
);

const stockTransfersListUrl = computed(() =>
    page.props.currentTeam
        ? stockTransfers.index.url(page.props.currentTeam.slug)
        : '/',
);

const stockTransfersCreateUrl = computed(() =>
    page.props.currentTeam
        ? stockTransfers.create.url(page.props.currentTeam.slug)
        : '/',
);

const stockAdjustmentsListUrl = computed(() =>
    page.props.currentTeam
        ? stockAdjustments.index.url(page.props.currentTeam.slug)
        : '/',
);

const stockAdjustmentsCreateUrl = computed(() =>
    page.props.currentTeam
        ? stockAdjustments.create.url(page.props.currentTeam.slug)
        : '/',
);

const expensesListUrl = computed(() =>
    page.props.currentTeam
        ? expenses.index.url(page.props.currentTeam.slug)
        : '/',
);

const expensesCreateUrl = computed(() =>
    page.props.currentTeam
        ? expenses.create.url(page.props.currentTeam.slug)
        : '/',
);

const expenseCategoriesIndexUrl = computed(() =>
    page.props.currentTeam
        ? expenseCategories.index.url(page.props.currentTeam.slug)
        : '/',
);

const salesListUrl = computed(() =>
    page.props.currentTeam
        ? salesRoutes.index.url(page.props.currentTeam.slug)
        : '/',
);

const salesCreateUrl = computed(() =>
    page.props.currentTeam
        ? salesRoutes.create.url(page.props.currentTeam.slug)
        : '/',
);

const salesDraftsListUrl = computed(() =>
    page.props.currentTeam
        ? salesDraftRoutes.index.url(page.props.currentTeam.slug)
        : '/',
);

const salesDraftsCreateUrl = computed(() =>
    page.props.currentTeam
        ? salesDraftRoutes.create.url(page.props.currentTeam.slug)
        : '/',
);

const salesQuotationsListUrl = computed(() =>
    page.props.currentTeam
        ? salesQuotationsRoutes.index.url(page.props.currentTeam.slug)
        : '/',
);

const salesQuotationsCreateUrl = computed(() =>
    page.props.currentTeam
        ? salesQuotationsRoutes.create.url(page.props.currentTeam.slug)
        : '/',
);

const salesReturnsListUrl = computed(() =>
    page.props.currentTeam
        ? salesRoutes.returns.index.url(page.props.currentTeam.slug)
        : '/',
);

const salesShipmentsListUrl = computed(() =>
    page.props.currentTeam
        ? salesRoutes.shipments.index.url(page.props.currentTeam.slug)
        : '/',
);

const salesDiscountsUrl = computed(() =>
    page.props.currentTeam
        ? salesRoutes.discounts.index.url(page.props.currentTeam.slug)
        : '/',
);

const posMainUrl = computed(() =>
    page.props.currentTeam
        ? posRoutes.index.url(page.props.currentTeam.slug)
        : '/',
);

const posListUrl = computed(() =>
    page.props.currentTeam
        ? posRoutes.list.url(page.props.currentTeam.slug)
        : '/',
);

const profitLossReportUrl = computed(() =>
    page.props.currentTeam
        ? reportRoutes.profitLoss.url(page.props.currentTeam.slug)
        : '/',
);

const purchaseSellReportUrl = computed(() =>
    page.props.currentTeam
        ? reportRoutes.purchaseSell.url(page.props.currentTeam.slug)
        : '/',
);

const taxReportUrl = computed(() =>
    page.props.currentTeam
        ? reportRoutes.taxReport.url(page.props.currentTeam.slug)
        : '/',
);

const customerSuppliersReportUrl = computed(() =>
    page.props.currentTeam
        ? reportRoutes.customerSuppliers.url(page.props.currentTeam.slug)
        : '/',
);

const customerGroupReportUrl = computed(() =>
    page.props.currentTeam
        ? reportRoutes.customerGroup.url(page.props.currentTeam.slug)
        : '/',
);

const stockReportUrl = computed(() =>
    page.props.currentTeam ? reportRoutes.stock.url(page.props.currentTeam.slug) : '/',
);

const stockAdjustmentReportUrl = computed(() =>
    page.props.currentTeam
        ? reportRoutes.stockAdjustment.url(page.props.currentTeam.slug)
        : '/',
);

const trendingProductsReportUrl = computed(() =>
    page.props.currentTeam
        ? reportRoutes.trendingProducts.url(page.props.currentTeam.slug)
        : '/',
);

const itemsReportUrl = computed(() =>
    page.props.currentTeam ? reportRoutes.items.url(page.props.currentTeam.slug) : '/',
);

const purchasePaymentsReportUrl = computed(() =>
    page.props.currentTeam
        ? reportRoutes.purchasePayments.url(page.props.currentTeam.slug)
        : '/',
);

const sellPaymentsReportUrl = computed(() =>
    page.props.currentTeam ? reportRoutes.sellPayments.url(page.props.currentTeam.slug) : '/',
);

const expenseReportUrl = computed(() =>
    page.props.currentTeam ? reportRoutes.expense.url(page.props.currentTeam.slug) : '/',
);

const registerReportUrl = computed(() =>
    page.props.currentTeam ? reportRoutes.register.url(page.props.currentTeam.slug) : '/',
);

const salesRepresentativeReportUrl = computed(() =>
    page.props.currentTeam ? reportRoutes.salesRepresentative.url(page.props.currentTeam.slug) : '/',
);

const contactsOpen = ref(false);
const purchasesOpen = ref(false);
const stockTransfersOpen = ref(false);
const stockAdjustmentsOpen = ref(false);
const expensesOpen = ref(false);
const sellOpen = ref(false);
const userManagementOpen = ref(false);
const settingsOpen = ref(false);
const paymentNavOpen = ref(false);
const productsOpen = ref(false);
const reportsOpen = ref(false);

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
            businessLocationsUrl.value,
        ] as const,
    () => {
        settingsOpen.value =
            isCurrentUrl(taxesUrl.value) ||
            isCurrentUrl(receiptPrinterUrl.value) ||
            isCurrentUrl(barcodeSettingsUrl.value) ||
            isCurrentUrl(businessLocationsUrl.value);
    },
    { immediate: true },
);

watch(
    () =>
        [
            page.url,
            purchasesListUrl.value,
            purchasesCreateUrl.value,
            purchaseReturnsListUrl.value,
        ] as const,
    () => {
        purchasesOpen.value =
            isCurrentUrl(purchasesListUrl.value) ||
            isCurrentUrl(purchasesCreateUrl.value) ||
            isCurrentUrl(purchaseReturnsListUrl.value);
    },
    { immediate: true },
);

watch(
    () =>
        [
            page.url,
            salesListUrl.value,
            salesCreateUrl.value,
            salesDraftsListUrl.value,
            salesDraftsCreateUrl.value,
            salesQuotationsListUrl.value,
            salesQuotationsCreateUrl.value,
            salesReturnsListUrl.value,
            salesShipmentsListUrl.value,
            salesDiscountsUrl.value,
            posMainUrl.value,
            posListUrl.value,
        ] as const,
    () => {
        sellOpen.value =
            isCurrentUrl(salesListUrl.value) ||
            isCurrentUrl(salesCreateUrl.value) ||
            isCurrentUrl(salesDraftsListUrl.value) ||
            isCurrentUrl(salesDraftsCreateUrl.value) ||
            isCurrentUrl(salesQuotationsListUrl.value) ||
            isCurrentUrl(salesQuotationsCreateUrl.value) ||
            isCurrentUrl(salesReturnsListUrl.value) ||
            isCurrentUrl(salesShipmentsListUrl.value) ||
            isCurrentUrl(salesDiscountsUrl.value) ||
            isCurrentUrl(posMainUrl.value) ||
            isCurrentUrl(posListUrl.value);
    },
    { immediate: true },
);

watch(
    () =>
        [
            page.url,
            stockTransfersListUrl.value,
            stockTransfersCreateUrl.value,
        ] as const,
    () => {
        stockTransfersOpen.value =
            isCurrentUrl(stockTransfersListUrl.value) ||
            isCurrentUrl(stockTransfersCreateUrl.value);
    },
    { immediate: true },
);

watch(
    () =>
        [
            page.url,
            stockAdjustmentsListUrl.value,
            stockAdjustmentsCreateUrl.value,
        ] as const,
    () => {
        stockAdjustmentsOpen.value =
            isCurrentUrl(stockAdjustmentsListUrl.value) ||
            isCurrentUrl(stockAdjustmentsCreateUrl.value);
    },
    { immediate: true },
);

watch(
    () =>
        [
            page.url,
            expensesListUrl.value,
            expensesCreateUrl.value,
            expenseCategoriesIndexUrl.value,
        ] as const,
    () => {
        expensesOpen.value =
            isCurrentUrl(expensesListUrl.value) ||
            isCurrentUrl(expensesCreateUrl.value) ||
            isCurrentUrl(expenseCategoriesIndexUrl.value);
    },
    { immediate: true },
);

watch(
    () =>
        [page.url, paymentAccountsListUrl.value, paymentSettingsUrl.value] as const,
    () => {
        paymentNavOpen.value =
            isCurrentUrl(paymentAccountsListUrl.value) ||
            isCurrentUrl(paymentSettingsUrl.value);
    },
    { immediate: true },
);

watch(
    () =>
        [
            page.url,
            profitLossReportUrl.value,
            purchaseSellReportUrl.value,
            taxReportUrl.value,
            customerSuppliersReportUrl.value,
            customerGroupReportUrl.value,
            stockReportUrl.value,
            stockAdjustmentReportUrl.value,
            trendingProductsReportUrl.value,
            itemsReportUrl.value,
            purchasePaymentsReportUrl.value,
            sellPaymentsReportUrl.value,
        ] as const,
    () => {
        reportsOpen.value =
            isCurrentUrl(profitLossReportUrl.value) ||
            isCurrentUrl(purchaseSellReportUrl.value) ||
            isCurrentUrl(taxReportUrl.value) ||
            isCurrentUrl(customerSuppliersReportUrl.value) ||
            isCurrentUrl(customerGroupReportUrl.value) ||
            isCurrentUrl(stockReportUrl.value) ||
            isCurrentUrl(stockAdjustmentReportUrl.value) ||
            isCurrentUrl(trendingProductsReportUrl.value) ||
            isCurrentUrl(itemsReportUrl.value) ||
            isCurrentUrl(purchasePaymentsReportUrl.value) ||
            isCurrentUrl(sellPaymentsReportUrl.value);
    },
    { immediate: true },
);
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

                    <Collapsible v-model:open="paymentNavOpen" class="group/collapsible">
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton
                                    :is-active="
                                        isCurrentUrl(paymentAccountsListUrl) ||
                                        isCurrentUrl(paymentSettingsUrl)
                                    "
                                    tooltip="Payment accounts"
                                >
                                    <Wallet />
                                    <span>Payment accounts</span>
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
                                                isCurrentUrl(paymentAccountsListUrl)
                                            "
                                        >
                                            <Link :href="paymentAccountsListUrl">
                                                <ListOrdered />
                                                <span>List accounts</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(paymentSettingsUrl)
                                            "
                                        >
                                            <Link :href="paymentSettingsUrl">
                                                <CircleDollarSign />
                                                <span
                                                    >Payment methods &amp;
                                                    POS accounts</span
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
                                        isCurrentUrl(barcodeSettingsUrl) ||
                                        isCurrentUrl(businessLocationsUrl)
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
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(businessLocationsUrl)
                                            "
                                        >
                                            <Link :href="businessLocationsUrl">
                                                <MapPin />
                                                <span>Business location</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>

                    <Collapsible v-model:open="purchasesOpen" class="group/collapsible">
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton
                                    :is-active="
                                        isCurrentUrl(purchasesListUrl) ||
                                        isCurrentUrl(purchasesCreateUrl) ||
                                        isCurrentUrl(purchaseReturnsListUrl)
                                    "
                                    tooltip="Purchases"
                                >
                                    <ShoppingCart />
                                    <span>Purchases</span>
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
                                                isCurrentUrl(purchasesListUrl)
                                            "
                                        >
                                            <Link :href="purchasesListUrl">
                                                <ListOrdered />
                                                <span>List purchases</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(
                                                    purchaseReturnsListUrl,
                                                )
                                            "
                                        >
                                            <Link :href="purchaseReturnsListUrl">
                                                <Undo2 />
                                                <span>List purchase return</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(purchasesCreateUrl)
                                            "
                                        >
                                            <Link :href="purchasesCreateUrl">
                                                <SquarePlus />
                                                <span>Add purchase</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>

                    <Collapsible
                        v-model:open="stockTransfersOpen"
                        class="group/collapsible"
                    >
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton
                                    :is-active="
                                        isCurrentUrl(stockTransfersListUrl) ||
                                        isCurrentUrl(stockTransfersCreateUrl)
                                    "
                                    tooltip="Stock transfer"
                                >
                                    <ArrowLeftRight />
                                    <span>Stock transfer</span>
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
                                                    stockTransfersListUrl,
                                                )
                                            "
                                        >
                                            <Link :href="stockTransfersListUrl">
                                                <ListOrdered />
                                                <span>List stock transfer</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(
                                                    stockTransfersCreateUrl,
                                                )
                                            "
                                        >
                                            <Link :href="stockTransfersCreateUrl">
                                                <SquarePlus />
                                                <span>Add stock transfer</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>

                    <Collapsible
                        v-model:open="stockAdjustmentsOpen"
                        class="group/collapsible"
                    >
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton
                                    :is-active="
                                        isCurrentUrl(stockAdjustmentsListUrl) ||
                                        isCurrentUrl(stockAdjustmentsCreateUrl)
                                    "
                                    tooltip="Stock adjustments"
                                >
                                    <SlidersHorizontal />
                                    <span>Stock adjustments</span>
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
                                                    stockAdjustmentsListUrl,
                                                )
                                            "
                                        >
                                            <Link :href="stockAdjustmentsListUrl">
                                                <ListOrdered />
                                                <span>List stock adjustment</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(
                                                    stockAdjustmentsCreateUrl,
                                                )
                                            "
                                        >
                                            <Link
                                                :href="stockAdjustmentsCreateUrl"
                                            >
                                                <SquarePlus />
                                                <span>Add stock adjustment</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>

                    <Collapsible v-model:open="expensesOpen" class="group/collapsible">
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton
                                    :is-active="
                                        isCurrentUrl(expensesListUrl) ||
                                        isCurrentUrl(expensesCreateUrl) ||
                                        isCurrentUrl(expenseCategoriesIndexUrl)
                                    "
                                    tooltip="Expenses"
                                >
                                    <Receipt />
                                    <span>Expenses</span>
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
                                                isCurrentUrl(expensesListUrl)
                                            "
                                        >
                                            <Link :href="expensesListUrl">
                                                <ListOrdered />
                                                <span>List expenses</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(expensesCreateUrl)
                                            "
                                        >
                                            <Link :href="expensesCreateUrl">
                                                <SquarePlus />
                                                <span>Add expense</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(
                                                    expenseCategoriesIndexUrl,
                                                )
                                            "
                                        >
                                            <Link :href="expenseCategoriesIndexUrl">
                                                <FolderTree />
                                                <span>Expense categories</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>

                    <Collapsible v-model:open="sellOpen" class="group/collapsible">
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton
                                    :is-active="
                                        isCurrentUrl(salesListUrl) ||
                                        isCurrentUrl(salesCreateUrl) ||
                                        isCurrentUrl(salesDraftsListUrl) ||
                                        isCurrentUrl(salesDraftsCreateUrl) ||
                                        isCurrentUrl(salesQuotationsListUrl) ||
                                        isCurrentUrl(salesQuotationsCreateUrl) ||
                                        isCurrentUrl(salesReturnsListUrl) ||
                                        isCurrentUrl(salesShipmentsListUrl) ||
                                        isCurrentUrl(salesDiscountsUrl) ||
                                        isCurrentUrl(posMainUrl) ||
                                        isCurrentUrl(posListUrl)
                                    "
                                    tooltip="Sell"
                                >
                                    <Store />
                                    <span>Sell</span>
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
                                                isCurrentUrl(salesListUrl)
                                            "
                                        >
                                            <Link :href="salesListUrl">
                                                <ListOrdered />
                                                <span>All sales</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(salesCreateUrl)
                                            "
                                        >
                                            <Link :href="salesCreateUrl">
                                                <SquarePlus />
                                                <span>Add Sale</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(posListUrl)
                                            "
                                        >
                                            <Link :href="posListUrl">
                                                <ListOrdered />
                                                <span>List POS</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(posMainUrl)
                                            "
                                        >
                                            <Link :href="posMainUrl">
                                                <ScanLine />
                                                <span>POS</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(
                                                    salesDraftsCreateUrl,
                                                )
                                            "
                                        >
                                            <Link :href="salesDraftsCreateUrl">
                                                <SquarePlus />
                                                <span>Add Draft</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(salesDraftsListUrl)
                                            "
                                        >
                                            <Link :href="salesDraftsListUrl">
                                                <FileText />
                                                <span>List Drafts</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(
                                                    salesQuotationsCreateUrl,
                                                )
                                            "
                                        >
                                            <Link
                                                :href="salesQuotationsCreateUrl"
                                            >
                                                <SquarePlus />
                                                <span>Add Quotation</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(
                                                    salesQuotationsListUrl,
                                                )
                                            "
                                        >
                                            <Link :href="salesQuotationsListUrl">
                                                <ClipboardList />
                                                <span>List quotations</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(salesReturnsListUrl)
                                            "
                                        >
                                            <Link :href="salesReturnsListUrl">
                                                <Undo2 />
                                                <span>List Sell Return</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(salesShipmentsListUrl)
                                            "
                                        >
                                            <Link :href="salesShipmentsListUrl">
                                                <Package />
                                                <span>Shipments</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="
                                                isCurrentUrl(salesDiscountsUrl)
                                            "
                                        >
                                            <Link :href="salesDiscountsUrl">
                                                <Percent />
                                                <span>Discounts</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>

                    <Collapsible v-model:open="reportsOpen" class="group/collapsible">
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton
                                    :is-active="
                                        isCurrentUrl(profitLossReportUrl) ||
                                        isCurrentUrl(purchaseSellReportUrl) ||
                                        isCurrentUrl(taxReportUrl) ||
                                        isCurrentUrl(customerSuppliersReportUrl) ||
                                        isCurrentUrl(customerGroupReportUrl) ||
                                        isCurrentUrl(stockReportUrl) ||
                                        isCurrentUrl(stockAdjustmentReportUrl) ||
                                        isCurrentUrl(trendingProductsReportUrl) ||
                                        isCurrentUrl(itemsReportUrl) ||
                                        isCurrentUrl(purchasePaymentsReportUrl) ||
                                        isCurrentUrl(sellPaymentsReportUrl)
                                    "
                                    tooltip="Reports"
                                >
                                    <BarChart3 />
                                    <span>Reports</span>
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
                                            :is-active="isCurrentUrl(profitLossReportUrl)"
                                        >
                                            <Link :href="profitLossReportUrl">
                                                <span>Profit / Loss report</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="isCurrentUrl(purchaseSellReportUrl)"
                                        >
                                            <Link :href="purchaseSellReportUrl">
                                                <span>Purchase & Sell</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="isCurrentUrl(taxReportUrl)"
                                        >
                                            <Link :href="taxReportUrl">
                                                <span>Tax report</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="isCurrentUrl(customerSuppliersReportUrl)"
                                        >
                                            <Link :href="customerSuppliersReportUrl">
                                                <span>Customer and suppliers report</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="isCurrentUrl(customerGroupReportUrl)"
                                        >
                                            <Link :href="customerGroupReportUrl">
                                                <span>Customer group report</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="isCurrentUrl(stockReportUrl)"
                                        >
                                            <Link :href="stockReportUrl">
                                                <span>Stock report</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="isCurrentUrl(stockAdjustmentReportUrl)"
                                        >
                                            <Link :href="stockAdjustmentReportUrl">
                                                <span>Stock adjustment report</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="isCurrentUrl(trendingProductsReportUrl)"
                                        >
                                            <Link :href="trendingProductsReportUrl">
                                                <span>Trending products</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="isCurrentUrl(itemsReportUrl)"
                                        >
                                            <Link :href="itemsReportUrl">
                                                <span>Items report</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="isCurrentUrl(purchasePaymentsReportUrl)"
                                        >
                                            <Link :href="purchasePaymentsReportUrl">
                                                <span>Purchase payment report</span>
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>
                                    <SidebarMenuSubItem>
                                        <SidebarMenuSubButton
                                            as-child
                                            size="sm"
                                            :is-active="isCurrentUrl(sellPaymentsReportUrl)"
                                        >
                                            <Link :href="sellPaymentsReportUrl">
                                                <span>Sell payment report</span>
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
