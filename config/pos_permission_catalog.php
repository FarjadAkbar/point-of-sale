<?php

/**
 * POS role permission groups (checkbox + optional radio clusters per Ultimate POS).
 *
 * @phpstan-return list<array{
 *     id: string,
 *     title: string,
 *     select_all?: bool,
 *     help?: string,
 *     checkboxes?: list<array{value: string, label: string}>,
 *     radios?: list<array{name: string, options: list<array{value: string, label: string}>}>
 * }>
 */
return [
    [
        'id' => 'others',
        'title' => 'Others',
        'select_all' => true,
        'checkboxes' => [
            ['value' => 'view_export_buttons', 'label' => 'View export to buttons (csv/excel/print/pdf) on tables'],
            ['value' => 'send_payment_received_notification', 'label' => 'Payment Received'],
            ['value' => 'send_payment_reminder_notification', 'label' => 'Payment Reminder'],
        ],
    ],
    [
        'id' => 'user',
        'title' => 'User',
        'select_all' => true,
        'checkboxes' => [
            ['value' => 'user.view', 'label' => 'View user'],
            ['value' => 'user.create', 'label' => 'Add user'],
            ['value' => 'user.update', 'label' => 'Edit user'],
            ['value' => 'user.delete', 'label' => 'Delete user'],
        ],
    ],
    [
        'id' => 'roles',
        'title' => 'Roles',
        'select_all' => true,
        'checkboxes' => [
            ['value' => 'roles.view', 'label' => 'View role'],
            ['value' => 'roles.create', 'label' => 'Add Role'],
            ['value' => 'roles.update', 'label' => 'Edit Role'],
            ['value' => 'roles.delete', 'label' => 'Delete role'],
        ],
    ],
    [
        'id' => 'supplier',
        'title' => 'Supplier',
        'select_all' => true,
        'radios' => [
            [
                'name' => 'supplier_view',
                'options' => [
                    ['value' => 'supplier.view', 'label' => 'View all supplier'],
                    ['value' => 'supplier.view_own', 'label' => 'View own supplier'],
                ],
            ],
        ],
        'checkboxes' => [
            ['value' => 'supplier.create', 'label' => 'Add supplier'],
            ['value' => 'supplier.update', 'label' => 'Edit supplier'],
            ['value' => 'supplier.delete', 'label' => 'Delete supplier'],
        ],
    ],
    [
        'id' => 'customer',
        'title' => 'Customer',
        'select_all' => true,
        'help' => 'To view all customers with no sell from a specific time, View all customer permission is required.',
        'radios' => [
            [
                'name' => 'customer_view',
                'options' => [
                    ['value' => 'customer.view', 'label' => 'View all customer'],
                    ['value' => 'customer.view_own', 'label' => 'View own customer'],
                ],
            ],
            [
                'name' => 'customer_view_by_sell',
                'options' => [
                    ['value' => 'customer_with_no_sell_one_month', 'label' => 'View customers with no sell from one month only'],
                    ['value' => 'customer_with_no_sell_three_month', 'label' => 'View customers with no sell from three months only'],
                    ['value' => 'customer_with_no_sell_six_month', 'label' => 'View customers with no sell from six months only'],
                    ['value' => 'customer_with_no_sell_one_year', 'label' => 'View customers with no sell from one year only'],
                    ['value' => 'customer_irrespective_of_sell', 'label' => 'View customers irrespective of their sell'],
                ],
            ],
        ],
        'checkboxes' => [
            ['value' => 'customer.create', 'label' => 'Add customer'],
            ['value' => 'customer.update', 'label' => 'Edit customer'],
            ['value' => 'customer.delete', 'label' => 'Delete customer'],
        ],
    ],
    [
        'id' => 'product',
        'title' => 'Product',
        'select_all' => true,
        'checkboxes' => [
            ['value' => 'product.view', 'label' => 'View product'],
            ['value' => 'product.create', 'label' => 'Add product'],
            ['value' => 'product.update', 'label' => 'Edit product'],
            ['value' => 'product.delete', 'label' => 'Delete product'],
            ['value' => 'product.print_labels', 'label' => 'Print labels'],
            ['value' => 'product.opening_stock', 'label' => 'Add Opening Stock'],
            ['value' => 'view_purchase_price', 'label' => 'View Purchase Price'],
        ],
    ],
    [
        'id' => 'warranty',
        'title' => 'Warranty',
        'select_all' => true,
        'radios' => [
            [
                'name' => 'warranty_view',
                'options' => [
                    ['value' => 'warranty.view', 'label' => 'View all warranty'],
                    ['value' => 'warranty.view_own', 'label' => 'View own warranty'],
                ],
            ],
        ],
        'checkboxes' => [
            ['value' => 'warranty.create', 'label' => 'Add warranty'],
            ['value' => 'warranty.update', 'label' => 'Edit warranty'],
            ['value' => 'warranty.delete', 'label' => 'Delete warranty'],
        ],
    ],
    [
        'id' => 'variation',
        'title' => 'Variation',
        'select_all' => true,
        'checkboxes' => [
            ['value' => 'variation.view', 'label' => 'View variation template'],
            ['value' => 'variation.create', 'label' => 'Add variation template'],
            ['value' => 'variation.update', 'label' => 'Edit variation template'],
            ['value' => 'variation.delete', 'label' => 'Delete variation template'],
        ],
    ],
    [
        'id' => 'purchase',
        'title' => 'Purchase',
        'select_all' => true,
        'radios' => [
            [
                'name' => 'purchase_view',
                'options' => [
                    ['value' => 'purchase.view', 'label' => 'View all Purchase'],
                    ['value' => 'view_own_purchase', 'label' => 'View own Purchase'],
                ],
            ],
        ],
        'checkboxes' => [
            ['value' => 'purchase.create', 'label' => 'Add purchase'],
            ['value' => 'purchase.update', 'label' => 'Edit purchase'],
            ['value' => 'purchase.delete', 'label' => 'Delete purchase'],
            ['value' => 'purchase.payments', 'label' => 'Add purchase payment'],
            ['value' => 'edit_purchase_payment', 'label' => 'Edit purchase payment'],
            ['value' => 'delete_purchase_payment', 'label' => 'Delete purchase payment'],
            ['value' => 'purchase.update_status', 'label' => 'Update Status'],
        ],
    ],
    [
        'id' => 'stock_adjustment',
        'title' => 'Stock Adjustment',
        'select_all' => true,
        'radios' => [
            [
                'name' => 'stock_adjustment_view',
                'options' => [
                    ['value' => 'stock_adjustment.view', 'label' => 'View all stock adjustment'],
                    ['value' => 'view_own_stock_adjustment', 'label' => 'View own stock adjustment'],
                ],
            ],
        ],
        'checkboxes' => [
            ['value' => 'stock_adjustment.create', 'label' => 'Add stock adjustment'],
            ['value' => 'stock_adjustment.update', 'label' => 'Edit stock adjustment'],
            ['value' => 'stock_adjustment.delete', 'label' => 'Delete stock adjustment'],
        ],
    ],
    [
        'id' => 'stock_transfer',
        'title' => 'Stock Transfer',
        'select_all' => true,
        'radios' => [
            [
                'name' => 'stock_transfer_view',
                'options' => [
                    ['value' => 'stock_transfer.view', 'label' => 'View all stock transfer'],
                    ['value' => 'stock_transfer.view_own', 'label' => 'View own stock transfer'],
                ],
            ],
        ],
        'checkboxes' => [
            ['value' => 'stock_transfer.create', 'label' => 'Add stock transfer'],
            ['value' => 'stock_transfer.update', 'label' => 'Edit stock transfer'],
            ['value' => 'stock_transfer.delete', 'label' => 'Delete stock transfer'],
        ],
    ],
    [
        'id' => 'pos',
        'title' => 'POS',
        'select_all' => true,
        'checkboxes' => [
            ['value' => 'sell.view', 'label' => 'View POS sell'],
            ['value' => 'sell.create', 'label' => 'Add POS sell'],
            ['value' => 'sell.update', 'label' => 'Edit POS sell'],
            ['value' => 'sell.delete', 'label' => 'Delete POS sell'],
            ['value' => 'edit_product_price_from_pos_screen', 'label' => 'Edit product price from POS screen'],
            ['value' => 'edit_product_discount_from_pos_screen', 'label' => 'Edit product discount from POS screen'],
            ['value' => 'edit_pos_payment', 'label' => 'Add/Edit Payment'],
            ['value' => 'print_invoice', 'label' => 'Print Invoice'],
            ['value' => 'disable_pay_checkout', 'label' => 'Disable Multiple Pay'],
            ['value' => 'disable_draft', 'label' => 'Disable Draft'],
            ['value' => 'disable_express_checkout', 'label' => 'Disable Express Checkout'],
            ['value' => 'disable_discount', 'label' => 'Disable Discount'],
            ['value' => 'disable_suspend_sale', 'label' => 'Disable Suspend Sale'],
            ['value' => 'disable_credit_sale', 'label' => 'Disable credit sale button'],
            ['value' => 'disable_quotation', 'label' => 'Disable Quotation'],
            ['value' => 'disable_card', 'label' => 'Disable Card'],
        ],
    ],
    [
        'id' => 'sell',
        'title' => 'Sell',
        'select_all' => true,
        'radios' => [
            [
                'name' => 'sell_view',
                'options' => [
                    ['value' => 'direct_sell.view', 'label' => 'View all sell'],
                    ['value' => 'view_own_sell_only', 'label' => 'View own sell only'],
                ],
            ],
        ],
        'checkboxes' => [
            ['value' => 'view_paid_sells_only', 'label' => 'View paid sells only'],
            ['value' => 'view_due_sells_only', 'label' => 'View due sells only'],
            ['value' => 'view_partial_sells_only', 'label' => 'View partially paid sells only'],
            ['value' => 'view_overdue_sells_only', 'label' => 'View overdue sells only'],
            ['value' => 'direct_sell.access', 'label' => 'Add Sell'],
            ['value' => 'direct_sell.update', 'label' => 'Update Sell'],
            ['value' => 'direct_sell.delete', 'label' => 'Delete Sell'],
            ['value' => 'view_commission_agent_sell', 'label' => 'Commission agent can view their own sell'],
            ['value' => 'sell.payments', 'label' => 'Add sell payment'],
            ['value' => 'edit_sell_payment', 'label' => 'Edit sell payment'],
            ['value' => 'delete_sell_payment', 'label' => 'Delete sell payment'],
            ['value' => 'edit_product_price_from_sale_screen', 'label' => 'Edit product price from sales screen'],
            ['value' => 'edit_product_discount_from_sale_screen', 'label' => 'Edit product discount from Sale screen'],
            ['value' => 'discount.access', 'label' => 'Add/Edit/Delete Discount'],
            ['value' => 'access_types_of_service', 'label' => 'Access types of service'],
            ['value' => 'access_sell_return', 'label' => 'Access all sell return'],
            ['value' => 'access_own_sell_return', 'label' => 'Access own sell return'],
            ['value' => 'edit_invoice_number', 'label' => 'Add edit invoice number'],
        ],
    ],
    [
        'id' => 'draft',
        'title' => 'Draft',
        'select_all' => true,
        'radios' => [
            [
                'name' => 'draft_view',
                'options' => [
                    ['value' => 'draft.view_all', 'label' => 'View all drafts'],
                    ['value' => 'draft.view_own', 'label' => 'View own drafts'],
                ],
            ],
        ],
        'checkboxes' => [
            ['value' => 'draft.update', 'label' => 'Edit draft'],
            ['value' => 'draft.delete', 'label' => 'Delete draft'],
        ],
    ],
    [
        'id' => 'quotation',
        'title' => 'Quotation',
        'select_all' => true,
        'radios' => [
            [
                'name' => 'quotation_view',
                'options' => [
                    ['value' => 'quotation.view_all', 'label' => 'View all quotations'],
                    ['value' => 'quotation.view_own', 'label' => 'View own quotations'],
                ],
            ],
        ],
        'checkboxes' => [
            ['value' => 'quotation.update', 'label' => 'Edit quotation'],
            ['value' => 'quotation.delete', 'label' => 'Delete quotation'],
        ],
    ],
    [
        'id' => 'shipments',
        'title' => 'Shipments',
        'select_all' => true,
        'radios' => [
            [
                'name' => 'shipping_view',
                'options' => [
                    ['value' => 'access_shipping', 'label' => 'Access all shipments'],
                    ['value' => 'access_own_shipping', 'label' => 'Access own shipments'],
                ],
            ],
        ],
        'checkboxes' => [
            ['value' => 'access_pending_shipments_only', 'label' => 'Access pending shipments only'],
            ['value' => 'access_commission_agent_shipping', 'label' => 'Commission agent can access their own shipments'],
        ],
    ],
    [
        'id' => 'cash_register',
        'title' => 'Cash Register',
        'select_all' => true,
        'checkboxes' => [
            ['value' => 'view_cash_register', 'label' => 'View cash register'],
            ['value' => 'close_cash_register', 'label' => 'Close cash register'],
        ],
    ],
    [
        'id' => 'brand',
        'title' => 'Brand',
        'select_all' => true,
        'radios' => [
            [
                'name' => 'brand_view',
                'options' => [
                    ['value' => 'brand.view', 'label' => 'View all brand'],
                    ['value' => 'brand.view_own', 'label' => 'View own brand'],
                ],
            ],
        ],
        'checkboxes' => [
            ['value' => 'brand.create', 'label' => 'Add brand'],
            ['value' => 'brand.update', 'label' => 'Edit brand'],
            ['value' => 'brand.delete', 'label' => 'Delete brand'],
        ],
    ],
    [
        'id' => 'tax_rate',
        'title' => 'Tax rate',
        'select_all' => true,
        'checkboxes' => [
            ['value' => 'tax_rate.view', 'label' => 'View tax rate'],
            ['value' => 'tax_rate.create', 'label' => 'Add tax rate'],
            ['value' => 'tax_rate.update', 'label' => 'Edit tax rate'],
            ['value' => 'tax_rate.delete', 'label' => 'Delete tax rate'],
        ],
    ],
    [
        'id' => 'unit',
        'title' => 'Unit',
        'select_all' => true,
        'radios' => [
            [
                'name' => 'unit_view',
                'options' => [
                    ['value' => 'unit.view', 'label' => 'View all unit'],
                    ['value' => 'unit.view_own', 'label' => 'View own unit'],
                ],
            ],
        ],
        'checkboxes' => [
            ['value' => 'unit.create', 'label' => 'Add unit'],
            ['value' => 'unit.update', 'label' => 'Edit unit'],
            ['value' => 'unit.delete', 'label' => 'Delete unit'],
        ],
    ],
    [
        'id' => 'category',
        'title' => 'Category',
        'select_all' => true,
        'checkboxes' => [
            ['value' => 'category.view', 'label' => 'View category'],
            ['value' => 'category.create', 'label' => 'Add category'],
            ['value' => 'category.update', 'label' => 'Edit category'],
            ['value' => 'category.delete', 'label' => 'Delete category'],
        ],
    ],
    [
        'id' => 'report',
        'title' => 'Report',
        'select_all' => true,
        'checkboxes' => [
            ['value' => 'purchase_n_sell_report.view', 'label' => 'View purchase & sell report'],
            ['value' => 'tax_report.view', 'label' => 'View Tax report'],
            ['value' => 'contacts_report.view', 'label' => 'View Supplier & Customer report'],
            ['value' => 'expense_report.view', 'label' => 'View expense report'],
            ['value' => 'profit_loss_report.view', 'label' => 'View profit/loss report'],
            ['value' => 'stock_report.view', 'label' => 'View stock report, stock adjustment report & stock expiry report'],
            ['value' => 'trending_product_report.view', 'label' => 'View trending product report'],
            ['value' => 'register_report.view', 'label' => 'View register report'],
            ['value' => 'sales_representative.view', 'label' => 'View sales representative report'],
            ['value' => 'view_product_stock_value', 'label' => 'View product stock value'],
        ],
    ],
    [
        'id' => 'settings',
        'title' => 'Settings',
        'select_all' => true,
        'checkboxes' => [
            ['value' => 'business_settings.access', 'label' => 'Access business settings'],
            ['value' => 'barcode_settings.access', 'label' => 'Access barcode settings'],
            ['value' => 'invoice_settings.access', 'label' => 'Access invoice settings'],
            ['value' => 'access_printers', 'label' => 'Access printers'],
        ],
    ],
    [
        'id' => 'expense',
        'title' => 'Expense',
        'select_all' => true,
        'radios' => [
            [
                'name' => 'expense_view',
                'options' => [
                    ['value' => 'all_expense.access', 'label' => 'Access all expenses'],
                    ['value' => 'view_own_expense', 'label' => 'View own expense only'],
                ],
            ],
        ],
        'checkboxes' => [
            ['value' => 'expense.add', 'label' => 'Add Expense'],
            ['value' => 'expense.edit', 'label' => 'Edit Expense'],
            ['value' => 'expense.delete', 'label' => 'Delete Expense'],
        ],
    ],
    [
        'id' => 'home',
        'title' => 'Home',
        'checkboxes' => [
            ['value' => 'dashboard.data', 'label' => 'View Home data'],
        ],
    ],
    [
        'id' => 'account',
        'title' => 'Account',
        'checkboxes' => [
            ['value' => 'account.access', 'label' => 'Access Accounts'],
            ['value' => 'edit_account_transaction', 'label' => 'Edit account transaction'],
            ['value' => 'delete_account_transaction', 'label' => 'Delete account transaction'],
        ],
    ],
    [
        'id' => 'bookings',
        'title' => 'Bookings',
        'select_all' => true,
        'radios' => [
            [
                'name' => 'bookings_view',
                'options' => [
                    ['value' => 'crud_all_bookings', 'label' => 'Add/Edit/View all bookings'],
                    ['value' => 'crud_own_bookings', 'label' => 'Add/Edit/View own bookings'],
                ],
            ],
        ],
    ],
    [
        'id' => 'selling_price',
        'title' => 'Access selling price groups',
        'checkboxes' => [
            ['value' => 'access_default_selling_price', 'label' => 'Default Selling Price'],
        ],
    ],
    [
        'id' => 'restaurant',
        'title' => 'Restaurant',
        'checkboxes' => [
            ['value' => 'access_tables', 'label' => 'Access tables'],
            ['value' => 'kitchen.access', 'label' => 'Access kitchen'],
            ['value' => 'order.access', 'label' => 'Access orders'],
        ],
    ],
];
