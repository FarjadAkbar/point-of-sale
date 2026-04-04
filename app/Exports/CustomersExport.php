<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\Team;
use App\Services\CustomerService;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomersExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected Team $team,
        protected array $filters,
        protected CustomerService $customerService,
    ) {}

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Customer>
     */
    public function query()
    {
        return $this->customerService->filteredQuery($this->team, $this->filters);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Code',
            'Role',
            'Entity',
            'Display name',
            'Mobile',
            'Email',
            'City',
            'Opening balance',
            'Credit limit',
            'Created at',
        ];
    }

    /**
     * @param  Customer  $customer
     * @return list<int|string|float|null>
     */
    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->customer_code,
            $customer->party_role,
            $customer->entity_type,
            $customer->display_name,
            $customer->mobile,
            $customer->email,
            $customer->city,
            (float) $customer->opening_balance,
            $customer->credit_limit !== null ? (float) $customer->credit_limit : null,
            $customer->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
