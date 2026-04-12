<?php

namespace App\Exports;

use App\Models\CustomerGroup;
use App\Models\Team;
use App\Services\CustomerGroupService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerGroupsExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected Team $team,
        protected array $filters,
        protected CustomerGroupService $customerGroupService,
    ) {}

    /**
     * @return Builder<CustomerGroup>
     */
    public function query()
    {
        return $this->customerGroupService
            ->filteredQuery($this->team, $this->filters)
            ->with('sellingPriceGroup:id,name');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Price calculation',
            'Amount %',
            'Selling price group',
            'Created at',
        ];
    }

    /**
     * @param  CustomerGroup  $group
     * @return list<int|string|float|null>
     */
    public function map($group): array
    {
        return [
            $group->id,
            $group->name,
            $group->price_calculation_type,
            $group->amount !== null ? (float) $group->amount : null,
            $group->sellingPriceGroup?->name,
            $group->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
