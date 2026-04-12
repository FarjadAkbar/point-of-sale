<?php

namespace App\Exports;

use App\Models\ProductCategory;
use App\Models\Team;
use App\Services\ProductCategoryService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductCategoriesExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected Team $team,
        protected array $filters,
        protected ProductCategoryService $productCategoryService,
    ) {}

    /**
     * @return Builder<ProductCategory>
     */
    public function query()
    {
        return $this->productCategoryService
            ->filteredQuery($this->team, $this->filters)
            ->with('parent:id,name');
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Code',
            'Description',
            'Sub-taxonomy',
            'Parent',
            'Created at',
        ];
    }

    /**
     * @param  ProductCategory  $row
     * @return list<int|string|null>
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->code,
            $row->description,
            $row->is_sub_taxonomy ? 'Yes' : 'No',
            $row->parent?->name,
            $row->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
