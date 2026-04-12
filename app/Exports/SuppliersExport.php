<?php

namespace App\Exports;

use App\Models\Supplier;
use App\Models\Team;
use App\Services\SupplierService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SuppliersExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected Team $team,
        protected array $filters,
        protected SupplierService $supplierService,
    ) {}

    /**
     * @return Builder<Supplier>
     */
    public function query()
    {
        return $this->supplierService->filteredQuery($this->team, $this->filters);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Code',
            'Type',
            'Display name',
            'Mobile',
            'Email',
            'City',
            'Opening balance',
            'Created at',
        ];
    }

    /**
     * @param  Supplier  $supplier
     * @return list<int|string|float|null>
     */
    public function map($supplier): array
    {
        return [
            $supplier->id,
            $supplier->supplier_code,
            $supplier->contact_type,
            $supplier->display_name,
            $supplier->mobile,
            $supplier->email,
            $supplier->city,
            (float) $supplier->opening_balance,
            $supplier->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
