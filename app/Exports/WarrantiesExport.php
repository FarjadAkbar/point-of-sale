<?php

namespace App\Exports;

use App\Models\Team;
use App\Models\Warranty;
use App\Services\WarrantyService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WarrantiesExport implements FromQuery, WithHeadings, WithMapping
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected Team $team,
        protected array $filters,
        protected WarrantyService $warrantyService,
    ) {}

    /**
     * @return Builder<Warranty>
     */
    public function query()
    {
        return $this->warrantyService->filteredQuery($this->team, $this->filters);
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
            'Duration value',
            'Duration unit',
            'Created at',
        ];
    }

    /**
     * @param  Warranty  $row
     * @return list<int|string|null>
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->description,
            $row->duration_value,
            $row->duration_unit,
            $row->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
