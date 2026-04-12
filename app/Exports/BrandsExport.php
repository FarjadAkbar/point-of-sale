<?php

namespace App\Exports;

use App\Models\Brand;
use App\Models\Team;
use App\Services\BrandService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BrandsExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected Team $team,
        protected array $filters,
        protected BrandService $brandService,
    ) {}

    /**
     * @return Builder<Brand>
     */
    public function query()
    {
        return $this->brandService->filteredQuery($this->team, $this->filters);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Description',
            'User for repair',
            'Created at',
        ];
    }

    /**
     * @param  Brand  $row
     * @return list<int|string|null>
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->description,
            $row->user_for_repair ? 'Yes' : 'No',
            $row->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
