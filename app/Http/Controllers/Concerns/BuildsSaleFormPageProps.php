<?php

namespace App\Http\Controllers\Concerns;

use App\Models\BusinessLocation;
use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Models\TaxRate;
use App\Models\Team;

trait BuildsSaleFormPageProps
{
    /**
     * @return array<string, mixed>
     */
    protected function saleFormPageProps(Team $current_team, bool $isDraftSale = false): array
    {
        $customers = Customer::query()
            ->forTeam($current_team)
            ->orderBy('business_name')
            ->orderBy('first_name')
            ->get();

        $customerRows = $customers->map(fn (Customer $c) => [
            'id' => $c->id,
            'display_name' => $c->display_name,
            'address_line_1' => $c->address_line_1,
            'address_line_2' => $c->address_line_2,
            'city' => $c->city,
            'state' => $c->state,
            'zip_code' => $c->zip_code,
            'country' => $c->country,
            'shipping_address' => $c->shipping_address,
        ]);

        $locations = BusinessLocation::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name']);

        $taxRates = TaxRate::query()
            ->forTeam($current_team)
            ->orderBy('name')
            ->get(['id', 'name', 'amount']);

        $paymentAccounts = PaymentAccount::query()
            ->forTeam($current_team)
            ->where('is_active', true)
            ->orderBy('payment_method')
            ->orderBy('name')
            ->get(['id', 'name', 'payment_method']);

        $members = $current_team->members()
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email']);

        $groups = $current_team->customerGroups()->orderBy('name')->get(['id', 'name']);

        return [
            'customers' => $customerRows,
            'businessLocations' => $locations,
            'taxRates' => $taxRates,
            'paymentAccounts' => $paymentAccounts,
            'paymentSettings' => $current_team->resolvedPaymentSettings(),
            'teamMembers' => $members->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ]),
            'customerGroups' => $groups,
            'isDraftSale' => $isDraftSale,
        ];
    }
}
